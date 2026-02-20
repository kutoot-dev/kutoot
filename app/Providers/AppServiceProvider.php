<?php

namespace App\Providers;

use App\Contracts\SmsContract;
use App\Services\Sms\SmsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsContract::class, function ($app) {
            return new SmsManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Model::unguard();
        Model::shouldBeStrict(! $this->app->isProduction());

        LogViewer::auth(function ($request) {
            return true; // Allow access to log viewer for all users
        });
        Gate::before(static function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
