<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Non-secret key/value application settings. API keys are NEVER stored here;
 * they live in environment variables only.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());

        if (! array_key_exists($key, $all) || $all[$key] === null) {
            return $default;
        }

        $decoded = json_decode($all[$key], true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $all[$key];
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        Cache::forget('settings.all');
    }
}
