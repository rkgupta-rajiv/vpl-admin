<?php

namespace App\Support;

use App\Models\Tournament;

/** The tournament the admin is currently viewing (switchable from the top bar). */
class AdminContext
{
    private static ?Tournament $cached = null;

    public static function tournament(): ?Tournament
    {
        if (self::$cached) {
            return self::$cached;
        }
        $id = session('admin_tournament_id');
        $t = $id ? Tournament::with('paymentSetting')->find($id) : null;

        return self::$cached = $t ?? Tournament::with('paymentSetting')->where('is_active', true)->latest('id')->first()
            ?? Tournament::with('paymentSetting')->latest('id')->first();
    }

    public static function tournamentId(): int
    {
        return self::tournament()?->id ?? 0;
    }
}
