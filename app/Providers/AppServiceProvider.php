<?php

namespace App\Providers;

use App\Contracts\SmsContract;
use App\Services\Sms\SmsManager;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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

        // Scramble: register Bearer token auth so docs show the Authorize input
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
                    ->setDescription('Sanctum Bearer token obtained via the OTP login flow.')
            );
        });
    }
}
