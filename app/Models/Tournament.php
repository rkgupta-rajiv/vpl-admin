<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tournament extends Model
{
    protected $fillable = [
        'name', 'season', 'code', 'registration_start_date', 'registration_end_date',
        'registration_status', 'description', 'rules', 'instructions', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'registration_start_date' => 'date',
            'registration_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function paymentSetting(): HasOne
    {
        return $this->hasOne(PaymentSetting::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->latest('id')->first();
    }

    public function getTitleAttribute(): string
    {
        return trim($this->name.' – '.$this->season);
    }

    /** Registration is open only when the status is "open" AND today is inside the date window. */
    public function isRegistrationOpen(): bool
    {
        if ($this->registration_status !== 'open') {
            return false;
        }
        $today = now()->startOfDay();
        if ($this->registration_start_date && $today->lt($this->registration_start_date)) {
            return false;
        }
        if ($this->registration_end_date && $today->gt($this->registration_end_date)) {
            return false;
        }

        return true;
    }

    public function registrationClosedReason(): ?string
    {
        if ($this->registration_status !== 'open') {
            return 'Registration is currently closed.';
        }
        $today = now()->startOfDay();
        if ($this->registration_start_date && $today->lt($this->registration_start_date)) {
            return 'Registration opens on '.$this->registration_start_date->format('d M Y').'.';
        }
        if ($this->registration_end_date && $today->gt($this->registration_end_date)) {
            return 'Registration closed on '.$this->registration_end_date->format('d M Y').'.';
        }

        return null;
    }

    public function currentFee(): float
    {
        return (float) ($this->paymentSetting?->registration_fee ?? 0);
    }
}
