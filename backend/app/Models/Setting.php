<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
    ];

    /**
     * Cache duration in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * In-process cache to avoid repeated cache store lookups within the same request
     */
    protected static array $resolved = [];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$resolved)) {
            return static::$resolved[$key];
        }

        $cacheKey = "setting.{$key}";

        $value = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });

        static::$resolved[$key] = $value;

        return $value;
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $storedValue = self::prepareValue($value, $type);

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'group' => $group,
                'type' => $type,
            ]
        );

        // Clear cache
        Cache::forget("setting.{$key}");
        unset(static::$resolved[$key]);
    }

    /**
     * Get multiple settings by group
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("setting.{$setting->key}");
        }
        static::$resolved = [];
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            'encrypted' => Crypt::decryptString($value),
            default => $value,
        };
    }

    /**
     * Prepare value for storage based on type
     */
    protected static function prepareValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json' => json_encode($value),
            'encrypted' => Crypt::encryptString((string) $value),
            default => (string) $value,
        };
    }
}
