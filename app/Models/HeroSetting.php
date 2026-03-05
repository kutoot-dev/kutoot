<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HeroSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'is_active',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Hero setting was {$eventName}");
    }

    /**
     * Scope a query to the given locale.
     */
    public function scopeForLocale($query, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $query->where('locale', $locale);
    }

    /**
     * Get the active hero setting (singleton pattern).
     *
     * @param string|null $locale
     */
    public static function active(?string $locale = null): ?self
    {
        $locale = $locale ?? app()->getLocale();
        return static::where('is_active', true)
            ->where('locale', $locale)
            ->latest()
            ->first();
    }
}
