<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Registration;
use App\Models\StatusHistory;
use App\Models\Tournament;
use App\Notifications\PlayerUpdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * All state transitions for registrations and payments live here so the
 * player API and the admin panel follow exactly the same rules.
 */
class RegistrationWorkflow
{
    public function __construct(private FileStorage $files) {}

    /* ------------------------------------------------------------------ */
    /* Player side                                                         */
    /* ------------------------------------------------------------------ */

    public function createRegistration(Player $player, Tournament $tournament, array $data): Registration
    {
        if (! $tournament->isRegistrationOpen()) {
            throw ValidationException::withMessages(['tournament' => $tournament->registrationClosedReason()]);
        }
        if ($missing = $player->missingProfileFields()) {
            throw ValidationException::withMessages(['profile' => 'Please complete your profile first ('.implode(', ', $missing).').']);
        }
        if ($missing = $player->missingDocuments()) {
            throw ValidationException::withMessages(['documents' => 'Please upload all documents first ('.implode(', ', $missing).').']);
        }
        if (Registration::where('tournament_id', $tournament->id)->where('player_id', $player->id)->exists()) {
            throw ValidationException::withMessages(['registration' => 'You have already registered for this tournament.']);
        }

        return DB::transaction(function () use ($player, $tournament, $data) {
            $fee = $tournament->currentFee();
            $reg = Registration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'jersey_number' => $data['jersey_number'] ?? null,
                'jersey_name' => isset($data['jersey_name']) ? mb_strtoupper($data['jersey_name']) : null,
                'status' => 'pending',
                'payment_status' => 'payment_pending',
                'fee_amount' => $fee,          // locked: later fee changes do not affect this registration
                'submitted_at' => now(),
            ]);
            StatusHistory::record($reg, 'registration', null, 'pending', 'Registration submitted by player');

            if ($fee <= 0) { // free tournament: nothing to pay
                $this->completeRegistration($reg, 'No registration fee for this tournament');
            }

            return $reg->fresh();
        });
    }

    /** After a rejection, the player fixes details/documents and resubmits. */
    public function resubmit(Registration $reg): Registration
    {
        if ($reg->status !== 'rejected') {
            throw ValidationException::withMessages(['registration' => 'Only rejected registrations can be resubmitted.']);
        }
        $reg->update(['status' => 'pending', 'submitted_at' => now()]);
        StatusHistory::record($reg, 'registration', 'rejected', 'pending', 'Resubmitted by player after corrections');

        return $reg;
    }

    public function submitPaymentProof(Registration $reg, array $data, UploadedFile $screenshot): Payment
    {
        $setting = $reg->tournament->paymentSetting;
        if (! $setting || ! $setting->online_payment_enabled) {
            throw ValidationException::withMessages(['payment' => 'Online QR payment is not enabled for this tournament. Please follow the payment instructions.']);
        }

        return DB::transaction(function () use ($reg, $data, $screenshot) {
            /** @var Registration $reg */
            $reg = Registration::whereKey($reg->id)->lockForUpdate()->first();

            if (! $reg->canSubmitPayment()) {
                throw ValidationException::withMessages(['payment' => $reg->status === 'rejected'
                    ? 'Your registration was rejected. Please correct it and resubmit before paying.'
                    : 'A payment proof is already submitted ('.$reg->paymentStatusLabel().').']);
            }
            if (\App\Models\Payment::toPaise($data['amount']) !== \App\Models\Payment::toPaise($reg->fee_amount)) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be exactly ₹'.number_format((float) $reg->fee_amount, 2).'.']);
            }
            $utr = strtoupper(trim($data['transaction_id']));
            $duplicate = Payment::where('transaction_id', $utr)->where('status', '!=', 'payment_rejected')->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['transaction_id' => 'This UPI Transaction ID / UTR has already been submitted.']);
            }

            $payment = Payment::create([
                'registration_id' => $reg->id,
                'method' => 'upi',
                'expected_amount' => $reg->fee_amount,
                'amount' => $data['amount'],
                'transaction_id' => $utr,
                'payment_date' => $data['payment_date'],
                'screenshot_path' => $this->files->storePaymentScreenshot($screenshot, $reg),
                'status' => 'proof_submitted',
            ]);
            $from = $reg->payment_status;
            $reg->update(['payment_status' => 'proof_submitted']);
            StatusHistory::record($reg, 'payment', $from, 'proof_submitted', 'Payment proof submitted (UTR '.$utr.')', $payment);

            return $payment;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Admin side                                                          */
    /* ------------------------------------------------------------------ */

    public function markUnderVerification(Payment $payment): void
    {
        if ($payment->status !== 'proof_submitted') {
            return;
        }
        $payment->update(['status' => 'under_verification']);
        $payment->registration->update(['payment_status' => 'under_verification']);
        StatusHistory::record($payment->registration, 'payment', 'proof_submitted', 'under_verification', 'Opened for verification by admin', $payment);
    }

    public function approvePayment(Payment $payment, ?string $remarks = null): void
    {
        DB::transaction(function () use ($payment, $remarks) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if (! in_array($payment->status, ['proof_submitted', 'under_verification'], true)) {
                throw ValidationException::withMessages(['payment' => 'This payment is already '.$payment->statusLabel().'.']);
            }
            $reg = $payment->registration;
            if ($reg->status === 'rejected') {
                throw ValidationException::withMessages(['payment' => 'Registration is rejected. Approve the registration first or reject this payment.']);
            }
            $from = $payment->status;
            $payment->update([
                'status' => 'payment_approved',
                'admin_remarks' => $remarks,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            StatusHistory::record($reg, 'payment', $from, 'payment_approved', $remarks ?: 'Payment verified', $payment);
            ActivityLog::log('payment.approved', 'Approved payment UTR '.$payment->transaction_id.' for '.$reg->player->full_name, $payment);

            $this->completeRegistration($reg, 'Payment approved');
        });
    }

    public function rejectPayment(Payment $payment, string $remarks): void
    {
        DB::transaction(function () use ($payment, $remarks) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if (! in_array($payment->status, ['proof_submitted', 'under_verification'], true)) {
                throw ValidationException::withMessages(['payment' => 'This payment is already '.$payment->statusLabel().'.']);
            }
            $reg = $payment->registration;
            $from = $payment->status;
            $payment->update([
                'status' => 'payment_rejected',
                'admin_remarks' => $remarks,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            $reg->update(['payment_status' => 'payment_rejected']);
            StatusHistory::record($reg, 'payment', $from, 'payment_rejected', $remarks, $payment);
            ActivityLog::log('payment.rejected', 'Rejected payment UTR '.$payment->transaction_id.' for '.$reg->player->full_name, $payment, ['remarks' => $remarks]);

            $this->notify($reg, 'Payment proof rejected', 'Your payment proof was rejected: '.$remarks.' Please submit the payment proof again.', 'danger');
        });
    }

    /** Cash / offline payment recorded directly by the admin. */
    public function recordOfflinePayment(Registration $reg, array $data): Payment
    {
        return DB::transaction(function () use ($reg, $data) {
            $reg = Registration::whereKey($reg->id)->lockForUpdate()->first();
            if ($reg->payment_status === 'payment_approved') {
                throw ValidationException::withMessages(['payment' => 'Payment is already approved for this registration.']);
            }
            if ($reg->status === 'rejected') {
                throw ValidationException::withMessages(['payment' => 'Registration is rejected. Approve it first.']);
            }
            // close any open UPI proof so there is a single source of truth
            $reg->payments()->whereIn('status', ['proof_submitted', 'under_verification'])
                ->update(['status' => 'payment_rejected', 'admin_remarks' => 'Superseded by offline payment', 'verified_by' => auth()->id(), 'verified_at' => now()]);

            $payment = Payment::create([
                'registration_id' => $reg->id,
                'method' => 'cash',
                'expected_amount' => $reg->fee_amount,
                'amount' => $data['amount'],
                'transaction_id' => ! empty($data['reference']) ? strtoupper($data['reference']) : null,
                'payment_date' => $data['payment_date'],
                'status' => 'payment_approved',
                'admin_remarks' => $data['remarks'] ?? 'Offline payment recorded by admin',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            StatusHistory::record($reg, 'payment', $reg->payment_status, 'payment_approved', 'Offline payment recorded', $payment);
            ActivityLog::log('payment.offline', 'Recorded offline payment ₹'.$data['amount'].' for '.$reg->player->full_name, $payment);
            $this->completeRegistration($reg, 'Offline payment recorded');

            return $payment;
        });
    }

    public function approveRegistration(Registration $reg, ?string $remarks = null): void
    {
        $from = $reg->status;
        $reg->update(['status' => 'approved', 'rejection_remarks' => null, 'reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
        StatusHistory::record($reg, 'registration', $from, 'approved', $remarks ?: 'Registration approved');
        ActivityLog::log('registration.approved', 'Approved registration of '.$reg->player->full_name, $reg);
        $this->notify($reg, 'Registration approved', 'Your details and documents have been approved for '.$reg->tournament->title.'.'
            .($reg->isCompleted() ? '' : ' Please complete the registration payment if not done yet.'), 'success');
    }

    public function rejectRegistration(Registration $reg, string $remarks): void
    {
        $from = $reg->status;
        $reg->update(['status' => 'rejected', 'rejection_remarks' => $remarks, 'reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
        StatusHistory::record($reg, 'registration', $from, 'rejected', $remarks);
        ActivityLog::log('registration.rejected', 'Rejected registration of '.$reg->player->full_name, $reg, ['remarks' => $remarks]);
        $this->notify($reg, 'Registration rejected', 'Your registration was rejected: '.$remarks.' Please correct your details and resubmit.', 'danger');
    }

    /* ------------------------------------------------------------------ */

    /** Payment approved ⇒ registration completed + unique Registration ID. */
    public function completeRegistration(Registration $reg, string $reason): void
    {
        $reg->refresh();
        $reg->payment_status = 'payment_approved';
        if ($reg->status !== 'approved') {
            StatusHistory::record($reg, 'registration', $reg->status, 'approved', $reason);
            $reg->status = 'approved';
            $reg->reviewed_at = now();
            $reg->reviewed_by = auth()->id();
        }
        $reg->completed_at = now();
        $reg->registration_no = $reg->registration_no ?: $this->generateRegistrationNo($reg);
        $reg->save();

        $this->notify($reg, 'Registration confirmed 🎉',
            'Your payment is approved and your registration for '.$reg->tournament->title.' is complete. Registration ID: '.$reg->registration_no.'.', 'success');
    }

    public function generateRegistrationNo(Registration $reg): string
    {
        $prefix = strtoupper($reg->tournament->code);
        $last = Registration::where('tournament_id', $reg->tournament_id)
            ->whereNotNull('registration_no')->lockForUpdate()
            ->pluck('registration_no')
            ->map(fn ($no) => (int) substr($no, strrpos($no, '-') + 1))
            ->max() ?? 0;

        return sprintf('%s-%04d', $prefix, $last + 1);
    }

    private function notify(Registration $reg, string $title, string $message, string $kind): void
    {
        try {
            $reg->player->user->notify(new PlayerUpdate($title, $message, $kind));
        } catch (\Throwable $e) {
            // mail failures must never break the approval workflow
            Log::warning('Notification failed: '.$e->getMessage());
            try {
                $reg->player->user->notifyNow(new class($title, $message, $kind) extends PlayerUpdate {
                    public function via(object $notifiable): array { return ['database']; }
                });
            } catch (\Throwable) {
            }
        }
    }
}
