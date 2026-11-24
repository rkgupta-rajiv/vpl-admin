<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerDocument extends Model
{
    protected $fillable = ['player_id', 'type', 'path', 'original_name', 'mime', 'size', 'status', 'remarks', 'verified_by', 'verified_at'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getLabelAttribute(): string
    {
        return Player::DOC_TYPES[$this->type] ?? $this->type;
    }
}
