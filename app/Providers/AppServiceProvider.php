<?php

namespace App\Providers;

use App\Contracts\SmsContract;
use App\Services\SettingService;
use App\Services\Sms\SmsManager;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsContract::class, function ($app) {
            return new SmsManager($app);
        });
    }

    public function boot(): void
    {
        // Force HTTPS for all generated URLs (fixes Mixed Content / Livewire behind Cloudflare/proxy)
        // Required when behind proxy where request may arrive as HTTP
        $appUrl = config('app.url');
        $forceHttps = $this->app->environment('production')
            || (bool) config('app.force_https', false)
            || ($appUrl && str_starts_with($appUrl, 'https://'));
        if ($forceHttps) {
            URL::forceScheme('https');
            $rootUrl = $appUrl ? rtrim($appUrl, '/') : null;
            if ($rootUrl && str_starts_with($rootUrl, 'http://')) {
                $rootUrl = 'https://' . substr($rootUrl, 7);
            }
            if ($rootUrl) {
                URL::forceRootUrl($rootUrl);
            }
        }

        // Register Observers
        \App\Models\QrCode::observe(\App\Observers\QrCodeObserver::class);

        // API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Vite prefetching for faster page transitions
        Vite::prefetch(concurrency: 3);

        // Prevent mass-assignment protection (app uses validated form requests)
        Model::unguard();

        // Enable strict mode parts to catch N+1 queries early,
        // but allow accessing missing attributes to prevent crashes.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Prevent lazy loading in production too (log instead of throw)
        if ($this->app->isProduction()) {
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                $class = get_class($model);
                logger()->warning("Lazy loading detected: {$class}::{$relation}");
            });
        }

        // Disable destructive DB commands in production
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Intercept all outgoing mail in non-production environments
        if (! $this->app->isProduction()) {
            Mail::alwaysTo(config('mail.dev_address', 'sathishreddy@kutoot.com'));
        }

        LogViewer::auth(function ($request) {
            return true;
        });

        Gate::before(static function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Storage: override disk and upload size from admin settings
        try {
            $storageDisk = SettingService::getStorageDisk();
            Config::set('media-library.disk_name', $storageDisk);
            Config::set('filament.default_filesystem_disk', $storageDisk);

            $maxMb = (int) SettingService::get('max_upload_size_mb', 100);
            if ($maxMb > 0) {
                Config::set('upload.max_file_size_kb', $maxMb * 1024);
                Config::set('media-library.max_file_size', $maxMb * 1024 * 1024);
            }

            if ($storageDisk === 's3') {
                Config::set('filesystems.disks.s3', array_merge(config('filesystems.disks.s3', []), [
                    'key' => SettingService::get('aws_access_key_id') ?: config('filesystems.disks.s3.key'),
                    'secret' => SettingService::get('aws_secret_access_key') ?: config('filesystems.disks.s3.secret'),
                    'region' => SettingService::get('aws_default_region') ?: config('filesystems.disks.s3.region'),
                    'bucket' => SettingService::get('aws_bucket') ?: config('filesystems.disks.s3.bucket'),
                    'url' => SettingService::get('aws_url') ?: config('filesystems.disks.s3.url'),
                    'endpoint' => SettingService::get('aws_endpoint') ?: config('filesystems.disks.s3.endpoint'),
                    'use_path_style_endpoint' => in_array(
                        strtolower((string) SettingService::get('aws_use_path_style_endpoint', false)),
                        ['1', 'true', 'yes'],
                        true
                    ),
                ]));
            }
        } catch (\Throwable $e) {
            // Skip when DB/config not ready (e.g. config:cache, migrations)
        }

        // Scramble: register Bearer token auth so docs show the Authorize input
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
                    ->setDescription('Sanctum Bearer token obtained via the OTP login flow.')
            );
        });
    }
}
