<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            ));
        });

        RateLimiter::for('forgot-password', function (Request $request): array {
            $email = Str::transliterate(Str::lower((string) $request->input('email')));

            return [
                Limit::perMinute(3)->by('forgot-password:'.$email.'|'.$request->ip()),
                Limit::perMinute(10)->by('forgot-password-ip:'.$request->ip()),
            ];
        });
    }
}
