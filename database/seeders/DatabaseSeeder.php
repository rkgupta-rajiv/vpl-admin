<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use App\Models\Setting;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Main Admin (Super Admin) — change the password after first login!
        User::updateOrCreate(
            ['email' => strtolower(env('ADMIN_EMAIL', 'admin@admin.com'))],
            ['name' => env('ADMIN_NAME', 'Main Admin'), 'password' => env('ADMIN_PASSWORD', 'admin123'), 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]
        );

        Setting::put(Setting::DEFAULTS);

        $t = Tournament::firstOrCreate(['code' => 'VPL2'], [
            'name' => 'Vankar Premier League',
            'season' => 'Season 2',
            'registration_start_date' => now()->toDateString(),
            'registration_end_date' => now()->addDays(30)->toDateString(),
            'registration_status' => 'open',
            'is_active' => true,
            'description' => 'VPL Season 2 – the village cricket league is back! Register as a player, upload your documents and pay the registration fee to confirm your spot in the auction pool.',
            'rules' => "Only registered and approved players can participate.\nA valid Aadhaar card is mandatory for verification.\nRegistration fee is non-refundable.\nPlayers must report in their tournament jersey.\nThe organising committee's decision is final.",
            'instructions' => "Fill your personal and cricket details carefully.\nUpload a clear passport-size photo and both sides of your Aadhaar card.\nPay the registration fee using the UPI QR code.\nUpload the payment screenshot with the UTR / transaction ID.\nYour registration is confirmed only after the admin verifies the payment.",
        ]);

        PaymentSetting::firstOrCreate(['tournament_id' => $t->id], [
            'registration_fee' => 500,
            'upi_id' => 'vplcommittee@okicici',
            'payee_name' => 'VPL Committee',
            'online_payment_enabled' => true,
            'instructions' => "Scan the QR code with any UPI app (GPay, PhonePe, Paytm, BHIM).\nPay the exact registration fee shown above.\nAfter paying, copy the 12-digit UTR / transaction ID.\nUpload the payment screenshot and UTR below.",
        ]);
    }
}
