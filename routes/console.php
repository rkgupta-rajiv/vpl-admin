<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/*
| Create or reset the Main Admin account from the server:
|   php artisan vpl:admin admin@example.com --password="Strong@123" --name="Main Admin"
*/
Artisan::command('vpl:admin {email} {--password=} {--name=Main Admin}', function (string $email) {
    $password = $this->option('password') ?: $this->secret('New password (min 8 chars)');
    if (strlen((string) $password) < 8) {
        $this->error('Password must be at least 8 characters.');

        return 1;
    }
    $user = User::updateOrCreate(['email' => strtolower($email)], [
        'name' => $this->option('name'),
        'password' => $password,
        'role' => User::ROLE_SUPER_ADMIN,
        'is_active' => true,
    ]);
    $this->info('Main Admin ready: '.$user->email);
})->purpose('Create or reset the Main Admin (super admin) account');
