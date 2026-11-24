<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUSES = [
        'proof_submitted' => 'Proof Submitted',
        'under_verification' => 'Under Verification',
        'payment_approved' => 'Payment Approved',
        'payment_rejected' => 'Payment Rejected',
    ];

    protected $fillable = [
        'registration_id', 'method', 'expected_amount', 'amount', 'transaction_id', 'payment_date',
        'screenshot_path', 'status', 'admin_remarks', 'verified_by', 'verified_at', 'gateway', 'gateway_reference',
    ];

    protected $hidden = ['screenshot_path'];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function amountMatches(): bool
    {
        return self::toPaise($this->amount) === self::toPaise($this->expected_amount);
    }

    /** Compare money as integer paise (no bcmath dependency). */
    public static function toPaise($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
