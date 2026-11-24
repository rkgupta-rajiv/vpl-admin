<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\Player;
use App\Models\PlayerDocument;
use App\Models\Registration;
use App\Models\Tournament;

/** Shapes the JSON the Flutter app consumes. */
class PlayerPresenter
{
    public static function tournament(?Tournament $t): ?array
    {
        if (! $t) {
            return null;
        }
        $ps = $t->paymentSetting;

        return [
            'id' => $t->id,
            'name' => $t->name,
            'season' => $t->season,
            'code' => $t->code,
            'title' => $t->title,
            'registration_start_date' => $t->registration_start_date?->toDateString(),
            'registration_end_date' => $t->registration_end_date?->toDateString(),
            'registration_status' => $t->registration_status,
            'is_registration_open' => $t->isRegistrationOpen(),
            'closed_reason' => $t->registrationClosedReason(),
            'description' => $t->description,
            'rules' => $t->rules,
            'instructions' => $t->instructions,
            'registration_fee' => (float) ($ps?->registration_fee ?? 0),
        ];
    }

    public static function player(Player $p): array
    {
        $p->loadMissing('user', 'documents');

        return [
            'id' => $p->id,
            'first_name' => $p->first_name,
            'surname' => $p->surname,
            'full_name' => $p->full_name,
            'age' => $p->age,
            'mobile' => $p->user->mobile,
            'email' => $p->user->email,
            'gender' => $p->gender,
            'village' => $p->village,
            'playing_role' => $p->playing_role,
            'batting_style' => $p->batting_style,
            'bowling_style' => $p->bowling_style,
            'tshirt_size' => $p->tshirt_size,
            'missing_profile_fields' => $p->missingProfileFields(),
            'missing_documents' => $p->missingDocuments(),
            'document_status' => $p->documentStatus(),
            'documents' => $p->documents->map(fn ($d) => self::document($d))->values(),
        ];
    }

    public static function document(PlayerDocument $d): array
    {
        return [
            'type' => $d->type,
            'label' => $d->label,
            'status' => $d->status,
            'remarks' => $d->remarks,
            'mime' => $d->mime,
            'size' => $d->size,
            'url' => url('/api/documents/'.$d->type).'?v='.$d->updated_at?->timestamp,
            'uploaded_at' => $d->updated_at?->toIso8601String(),
        ];
    }

    public static function registration(?Registration $r): ?array
    {
        if (! $r) {
            return null;
        }
        $r->loadMissing('latestPayment');

        return [
            'id' => $r->id,
            'registration_no' => $r->registration_no,
            'jersey_number' => $r->jersey_number,
            'jersey_name' => $r->jersey_name,
            'status' => $r->status,
            'status_label' => $r->statusLabel(),
            'rejection_remarks' => $r->rejection_remarks,
            'payment_status' => $r->payment_status,
            'payment_status_label' => $r->paymentStatusLabel(),
            'fee_amount' => (float) $r->fee_amount,
            'can_submit_payment' => $r->canSubmitPayment(),
            'is_completed' => $r->isCompleted(),
            'submitted_at' => $r->submitted_at?->toIso8601String(),
            'completed_at' => $r->completed_at?->toIso8601String(),
            'latest_payment' => $r->latestPayment ? self::payment($r->latestPayment) : null,
        ];
    }

    public static function payment(Payment $p): array
    {
        return [
            'id' => $p->id,
            'method' => $p->method,
            'amount' => (float) $p->amount,
            'expected_amount' => (float) $p->expected_amount,
            'transaction_id' => $p->transaction_id,
            'payment_date' => $p->payment_date?->toDateString(),
            'status' => $p->status,
            'status_label' => $p->statusLabel(),
            'admin_remarks' => $p->admin_remarks,
            'screenshot_url' => $p->screenshot_path ? url('/api/payments/'.$p->id.'/screenshot') : null,
            'submitted_at' => $p->created_at?->toIso8601String(),
            'verified_at' => $p->verified_at?->toIso8601String(),
        ];
    }
}
