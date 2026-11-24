<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $key = fn (Request $r) => $r->user()?->id ?: $r->ip();

        // Each limiter has its own bucket so uploads don't eat into payment attempts.
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute((int) env('AUTH_RATE_LIMIT', 10))->by('auth|'.$r->ip()));
        RateLimiter::for('otp', fn (Request $r) => Limit::perMinutes(10, 3)->by('otp|'.$r->ip().'|'.strtolower((string) $r->input('email'))));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(20)->by('uploads|'.$key($r)));
        RateLimiter::for('payment', fn (Request $r) => Limit::perMinute(5)->by('payment|'.$key($r)));
    }
}
