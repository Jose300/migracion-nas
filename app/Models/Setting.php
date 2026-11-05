<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use Auditable;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Retrieve a setting value by key, with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                return $setting !== null && $setting->value !== null ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Store or update a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'identity'): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting_{$key}");
        Cache::forget('all_settings');

        return $setting;
    }

    /**
     * Remove a setting by key.
     */
    public static function remove(string $key): bool
    {
        Cache::forget("setting_{$key}");
        Cache::forget('all_settings');

        return (bool) static::where('key', $key)->delete();
    }

    /**
     * Retrieve all settings as key-value array.
     */
    public static function allKeyed(): array
    {
        try {
            return Cache::rememberForever('all_settings', function () {
                return static::pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }
}
