<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\StatusHistory;
use App\Models\Tournament;
use App\Services\FileStorage;
use App\Services\RegistrationWorkflow;
use App\Support\PlayerPresenter;
use App\Support\PlayerRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function __construct(private RegistrationWorkflow $workflow) {}

    /** Submit tournament registration (after profile + documents). */
    public function store(Request $request)
    {
        $data = $request->validate(PlayerRules::jersey(), PlayerRules::messages());
        $tournament = Tournament::active() ?? throw ValidationException::withMessages(['tournament' => 'No tournament is open for registration.']);
        $reg = $this->workflow->createRegistration($request->user()->player->load('documents'), $tournament->load('paymentSetting'), $data);

        return response()->json(['success' => true, 'message' => 'Registration submitted. Please complete the payment.', 'registration' => PlayerPresenter::registration($reg)], 201);
    }

    public function update(Request $request)
    {
        $reg = $this->current($request);
        if ($reg->isCompleted()) {
            throw ValidationException::withMessages(['registration' => 'Registration is confirmed and can no longer be edited.']);
        }
        $data = $request->validate(PlayerRules::jersey(false), PlayerRules::messages());
        if (isset($data['jersey_name'])) {
            $data['jersey_name'] = mb_strtoupper($data['jersey_name']);
        }
        $reg->update($data);

        return response()->json(['success' => true, 'message' => 'Registration updated.', 'registration' => PlayerPresenter::registration($reg)]);
    }

    public function resubmit(Request $request)
    {
        $reg = $this->current($request);
        $player = $request->user()->player->load('documents');
        if ($player->missingProfileFields() || $player->missingDocuments()) {
            throw ValidationException::withMessages(['registration' => 'Please complete your profile and documents before resubmitting.']);
        }
        $this->workflow->resubmit($reg);

        return response()->json(['success' => true, 'message' => 'Registration resubmitted for review.', 'registration' => PlayerPresenter::registration($reg->fresh())]);
    }

    /** Status timeline for the player's own registration. */
    public function history(Request $request)
    {
        $reg = $this->current($request);

        return response()->json(['success' => true, 'history' => $reg->histories()->get()->map(fn (StatusHistory $h) => [
            'type' => $h->type,
            'from' => $h->from_status,
            'to' => $h->to_status,
            'remarks' => $h->remarks,
            'at' => $h->created_at?->toIso8601String(),
        ])]);
    }

    /* ------------------------------ Payment ------------------------------ */

    /** Data for the payment page (QR, UPI ID, locked fee). */
    public function payment(Request $request)
    {
        $reg = $this->current($request)->load('tournament.paymentSetting', 'player');
        $ps = $reg->tournament->paymentSetting;

        return response()->json([
            'success' => true,
            'payment' => [
                'tournament_name' => $reg->tournament->title,
                'player_name' => $reg->player->full_name,
                'registration_fee' => (float) $reg->fee_amount,
                'online_payment_enabled' => (bool) ($ps?->online_payment_enabled),
                'qr_code_url' => $ps?->qrCodeUrl(),
                'upi_id' => $ps?->upi_id,
                'payee_name' => $ps?->payee_name,
                'instructions' => $ps?->instructions,
                'payment_status' => $reg->payment_status,
                'payment_status_label' => $reg->paymentStatusLabel(),
                'can_submit_payment' => $reg->canSubmitPayment(),
            ],
            'payments' => $reg->payments()->get()->map(fn (Payment $p) => PlayerPresenter::payment($p)),
        ]);
    }

    public function submitPayment(Request $request)
    {
        $reg = $this->current($request)->load('tournament.paymentSetting', 'player');
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'transaction_id' => ['required', 'string', 'min:6', 'max:35', 'regex:/^[A-Za-z0-9]+$/'],
            'payment_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(60)->toDateString()],
            'screenshot' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ], ['transaction_id.regex' => 'Transaction ID / UTR may only contain letters and numbers.']);

        $payment = $this->workflow->submitPaymentProof($reg, $data, $request->file('screenshot'));

        return response()->json([
            'success' => true,
            'message' => 'Payment proof submitted. The organiser will verify it shortly — your registration is not confirmed until verification.',
            'payment' => PlayerPresenter::payment($payment),
            'registration' => PlayerPresenter::registration($reg->fresh()),
        ], 201);
    }

    public function screenshot(Request $request, Payment $payment)
    {
        $playerId = $request->user()->player->id;
        abort_unless($payment->registration->player_id === $playerId && $payment->screenshot_path, 404);

        return Storage::disk(FileStorage::DISK)->response($payment->screenshot_path);
    }

    /** Registration confirmation data — only after payment approval. */
    public function confirmation(Request $request)
    {
        $reg = $this->current($request)->load('tournament', 'player.user', 'latestPayment');
        if (! $reg->isCompleted()) {
            throw ValidationException::withMessages(['registration' => 'Your registration is not confirmed yet.']);
        }
        $p = $reg->player;

        return response()->json(['success' => true, 'confirmation' => [
            'registration_no' => $reg->registration_no,
            'tournament' => $reg->tournament->title,
            'player_name' => $p->full_name,
            'mobile' => $p->user->mobile,
            'email' => $p->user->email,
            'village' => $p->village,
            'age' => $p->age,
            'gender' => $p->gender,
            'playing_role' => $p->playing_role,
            'batting_style' => $p->batting_style,
            'bowling_style' => $p->bowling_style,
            'tshirt_size' => $p->tshirt_size,
            'jersey_number' => $reg->jersey_number,
            'jersey_name' => $reg->jersey_name,
            'fee_paid' => (float) $reg->fee_amount,
            'transaction_id' => $reg->latestPayment?->transaction_id,
            'confirmed_at' => $reg->completed_at?->toIso8601String(),
            'photo_url' => url('/api/documents/photo'),
        ]]);
    }

    private function current(Request $request): Registration
    {
        $tournament = Tournament::active();
        $reg = $tournament
            ? Registration::where('tournament_id', $tournament->id)->where('player_id', $request->user()->player->id)->first()
            : null;
        if (! $reg) {
            throw ValidationException::withMessages(['registration' => 'You have not registered for the current tournament yet.']);
        }

        return $reg;
    }
}
