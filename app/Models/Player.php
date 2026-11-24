<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    public const GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
    public const ROLES = ['batsman' => 'Batsman', 'bowler' => 'Bowler', 'all_rounder' => 'All-Rounder', 'wicket_keeper' => 'Wicket Keeper'];
    public const BATTING = ['right' => 'Right Hand', 'left' => 'Left Hand'];
    public const BOWLING = ['right_arm' => 'Right Arm', 'left_arm' => 'Left Arm', 'na' => 'Not Applicable'];
    public const TSHIRT = ['S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', 'XXL' => 'XXL', 'XXXL' => 'XXXL'];
    public const DOC_TYPES = ['photo' => 'Profile Photo', 'aadhaar_front' => 'Aadhaar Front', 'aadhaar_back' => 'Aadhaar Back'];

    protected $fillable = [
        'user_id', 'first_name', 'surname', 'age', 'gender', 'village',
        'playing_role', 'batting_style', 'bowling_style', 'tshirt_size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PlayerDocument::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function document(string $type): ?PlayerDocument
    {
        return $this->documents->firstWhere('type', $type);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->surname);
    }

    public function missingProfileFields(): array
    {
        $required = ['first_name', 'surname', 'age', 'gender', 'village', 'playing_role', 'batting_style', 'bowling_style', 'tshirt_size'];

        return array_values(array_filter($required, fn ($f) => blank($this->{$f})));
    }

    public function missingDocuments(): array
    {
        $have = $this->documents->pluck('type')->all();

        return array_values(array_diff(array_keys(self::DOC_TYPES), $have));
    }

    /** Overall document verification status shown to player / admin. */
    public function documentStatus(): string
    {
        $docs = $this->documents;
        if ($docs->count() < count(self::DOC_TYPES)) {
            return 'incomplete';
        }
        if ($docs->contains('status', 'rejected')) {
            return 'rejected';
        }
        if ($docs->every(fn ($d) => $d->status === 'verified')) {
            return 'verified';
        }

        return 'pending';
    }

    public static function label(array $map, ?string $key): string
    {
        return $key !== null && isset($map[$key]) ? $map[$key] : ($key ?? '—');
    }
}
