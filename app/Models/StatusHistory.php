<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['registration_id', 'payment_id', 'type', 'from_status', 'to_status', 'remarks', 'actor_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(?Registration $reg, string $type, ?string $from, string $to, ?string $remarks = null, ?Payment $payment = null): self
    {
        return static::create([
            'registration_id' => $reg?->id,
            'payment_id' => $payment?->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'remarks' => $remarks,
            'actor_id' => auth()->id(),
        ]);
    }
}
