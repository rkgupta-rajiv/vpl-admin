<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\User;
use App\Notifications\PlayerUpdate;
use App\Support\PlayerPresenter;
use App\Support\PlayerRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Step 1: create the player account (personal information). */
    public function register(Request $request)
    {
        $data = $request->validate(PlayerRules::personal() + [
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'device_name' => ['nullable', 'string', 'max:60'],
        ], PlayerRules::messages());

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['surname']),
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'],
                'password' => $data['password'],
                'role' => User::ROLE_PLAYER,
            ]);
            Player::create(['user_id' => $user->id] + collect($data)->only(['first_name', 'surname', 'age', 'gender', 'village'])->all());

            return $user;
        });

        $token = $user->createToken($data['device_name'] ?? 'player-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'token' => $token,
            'player' => PlayerPresenter::player($user->player),
        ], 201);
    }

    /** Login with email, mobile number or full name + password. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);
        $login = trim($data['login']);

        $query = User::where('role', User::ROLE_PLAYER);
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $users = $query->where('email', strtolower($login))->get();
        } elseif (preg_match('/^[0-9]{10}$/', $login)) {
            $users = $query->where('mobile', $login)->get();
        } else {
            $users = $query->whereRaw('LOWER(name) = ?', [mb_strtolower(preg_replace('/\s+/', ' ', $login))])->get();
            if ($users->count() > 1) {
                throw ValidationException::withMessages(['login' => 'More than one player has this name. Please log in with your email or mobile number.']);
            }
        }
        $user = $users->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Invalid login details.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => 'Your account has been deactivated. Please contact the organiser.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'token' => $user->createToken($data['device_name'] ?? 'player-app')->plainTextToken,
            'player' => PlayerPresenter::player($user->player),
        ]);
    }

    /** Sends a 6-digit OTP to the registered email. Always returns the same message (no account enumeration). */
    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($data['email']);
        $user = User::where('email', $email)->where('role', User::ROLE_PLAYER)->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);
            DB::table('password_reset_otps')->where('email', $email)->delete();
            DB::table('password_reset_otps')->insert([
                'email' => $email,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
            ]);
            try {
                Notification::route('mail', $email)->notifyNow(new class($otp) extends PlayerUpdate {
                    public function __construct(public string $otp)
                    {
                        parent::__construct('Password reset code', 'Your password reset code is '.$otp.'. It is valid for 15 minutes. If you did not request this, ignore this email.');
                    }

                    public function via(object $notifiable): array
                    {
                        return ['mail'];
                    }

                    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
                    {
                        return (new \Illuminate\Notifications\Messages\MailMessage)
                            ->subject('Your password reset code')
                            ->line('Your password reset code is: **'.$this->otp.'**')
                            ->line('This code is valid for 15 minutes.');
                    }
                });
            } catch (\Throwable $e) {
                Log::error('OTP email failed: '.$e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'If this email is registered, a 6-digit reset code has been sent to it.']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $email = strtolower($data['email']);
        $row = DB::table('password_reset_otps')->where('email', $email)->latest('id')->first();

        if (! $row || now()->gt($row->expires_at) || $row->attempts >= 5) {
            throw ValidationException::withMessages(['otp' => 'The reset code has expired. Please request a new one.']);
        }
        if (! Hash::check($data['otp'], $row->otp_hash)) {
            DB::table('password_reset_otps')->where('id', $row->id)->increment('attempts');
            throw ValidationException::withMessages(['otp' => 'Invalid reset code.']);
        }

        $user = User::where('email', $email)->where('role', User::ROLE_PLAYER)->firstOrFail();
        $user->update(['password' => $data['password']]);
        $user->tokens()->delete(); // sign out all devices
        DB::table('password_reset_otps')->where('email', $email)->delete();

        return response()->json(['success' => true, 'message' => 'Password reset successfully. Please log in with your new password.']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $data['password']]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }
}
