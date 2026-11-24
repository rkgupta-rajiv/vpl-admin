<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'app_name' => 'VPL Registration',
        'organizer_name' => 'Vankar Premier League Committee',
        'support_phone' => '',
        'support_email' => '',
        'email_notifications' => '1',
    ];

    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('app_settings', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('app_settings');
    }
}
