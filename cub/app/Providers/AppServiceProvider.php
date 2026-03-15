<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureOtp();
        $this->configureRateLimiting();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureOtp(): void
    {
        config()->set('otp.length', 6);
        config()->set('otp.digits_only', true);
        config()->set('otp.validity', max(1, (int) ceil(config('lampa.device_code_ttl') / 60)));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('lampa', function (Request $request) {
            $signature = $request->header('token') ?: $request->ip();

            return Limit::perMinute(config('lampa.api_rate_limit_per_minute'))
                ->by($signature)
                ->response(fn () => response()->json([
                    'secuses' => false,
                    'code' => 'rate_limited',
                    'error' => 'Too many requests.',
                ], 429));
        });
    }
}
