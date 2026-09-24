<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    public const CACHE_KEY = 'folkscript:site-settings:v1';

    public const CONFIG_KEYS = [
        'site_name' => 'app.name',
        'site_tagline' => 'folkscript.tagline',
        'block_training_bots' => 'seo.block_training_bots',
        'google_verification' => 'services.google.site_verification',
        'bing_verification' => 'services.bing.site_verification',
        'render_og' => 'seo.render_og',
        'digests_enabled' => 'folkscript.digests_enabled',
        'mail_notifications' => 'folkscript.mail_notifications',
        'mail_from_name' => 'mail.from.name',
        'mail_from_address' => 'mail.from.address',
        'analytics_enabled' => 'analytics.enabled',
        'plausible_domain' => 'analytics.plausible_domain',
    ];

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function values(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 60, fn () => static::query()->get()->pluck('value', 'key')->all());
    }

    public static function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::invalidate());
        static::deleted(fn () => static::invalidate());
    }
}
