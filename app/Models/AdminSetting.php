<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
        ];
    }

    /**
     * Get a setting value with fallback to .env/config and then to the provided default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::remember("admin_setting:{$key}", 300, function () use ($key) {
            $setting = static::find($key);

            return $setting ? ['value' => $setting->value, 'type' => $setting->type] : null;
        });

        if ($cached === null || $cached['value'] === null || $cached['value'] === '') {
            return $default;
        }

        return static::castValue($cached['value'], $cached['type']);
    }

    /**
     * Set a setting value and clear its cache.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::find($key);

        if ($setting) {
            $setting->update(['value' => (string) $value]);
        }

        Cache::forget("admin_setting:{$key}");
    }

    /**
     * Get a setting from a specific group.
     */
    public static function fromGroup(string $group, string $key, mixed $default = null): mixed
    {
        return static::get("{$group}.{$key}", $default) ?? static::get($key, $default);
    }

    /**
     * Get all settings for a group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->is_sensitive ? '********' : static::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Check configuration status for a group — returns which required keys are missing.
     *
     * @param  array<string>  $requiredKeys
     * @return array{configured: bool, missing: array<string>}
     */
    public static function checkGroupStatus(string $group, array $requiredKeys): array
    {
        $settings = static::where('group', $group)->pluck('value', 'key');
        $missing = [];

        foreach ($requiredKeys as $key) {
            if (! $settings->has($key) || blank($settings->get($key))) {
                // Fallback: check .env
                $envValue = config($key) ?? env(strtoupper(str_replace('.', '_', $key)));
                if (blank($envValue)) {
                    $missing[] = $key;
                }
            }
        }

        return [
            'configured' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Clear all setting caches.
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');

        foreach ($keys as $key) {
            Cache::forget("admin_setting:{$key}");
        }
    }

    /**
     * Cast value to the appropriate type.
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
