<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    protected $fillable = ['tournament_id', 'registration_fee', 'upi_id', 'payee_name', 'qr_code_path', 'instructions', 'online_payment_enabled'];

    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
            'online_payment_enabled' => 'boolean',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function qrCodeUrl(): ?string
    {
        // asset() uses the host of the current request, so the QR loads on emulator / LAN / production alike
        return $this->qr_code_path ? asset('storage/'.$this->qr_code_path) : null;
    }
}
