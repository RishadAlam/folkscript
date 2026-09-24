<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\ArgvInput;

class PlatformSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Composer and installation/cache commands must boot before a database exists.
        // Runtime requests and workers still surface genuine database failures.
        if ($this->app->runningInConsole()) {
            $command = (new ArgvInput)->getFirstArgument() ?? (basename($_SERVER['argv'][0] ?? '') === 'artisan' ? 'list' : '');
            if (in_array($command, ['list', 'help', 'about', 'package:discover', 'key:generate', 'vendor:publish', 'config:cache', 'config:clear', 'route:cache', 'route:clear', 'view:cache', 'view:clear', 'optimize', 'optimize:clear'], true)
                || str_starts_with($command, 'migrate')) {
                return;
            }
        }

        $defaults = collect(SiteSetting::CONFIG_KEYS)->mapWithKeys(fn ($key) => [$key => config($key)])->all();
        $apply = static function () use ($defaults): void {
            config($defaults);
            foreach (SiteSetting::values() as $key => $value) {
                if (isset(SiteSetting::CONFIG_KEYS[$key])) {
                    config([SiteSetting::CONFIG_KEYS[$key] => $value]);
                }
            }
        };

        $apply();
        // Long-running queue workers must also pick up administration changes.
        Queue::before($apply);
    }
}
