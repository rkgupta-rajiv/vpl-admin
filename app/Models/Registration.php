<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public const PAYMENT_STATUSES = [
        'payment_pending' => 'Payment Pending',
        'proof_submitted' => 'Proof Submitted',
        'under_verification' => 'Under Verification',
        'payment_approved' => 'Payment Approved',
        'payment_rejected' => 'Payment Rejected',
    ];

    protected $fillable = [
        'tournament_id', 'player_id', 'registration_no', 'jersey_number', 'jersey_name', 'status',
        'rejection_remarks', 'payment_status', 'fee_amount', 'submitted_at', 'reviewed_at', 'reviewed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->latest('id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null && $this->payment_status === 'payment_approved';
    }

    /** Players may (re)submit payment proof only in these states. */
    public function canSubmitPayment(): bool
    {
        return in_array($this->payment_status, ['payment_pending', 'payment_rejected'], true)
            && $this->status !== 'rejected';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }
}
