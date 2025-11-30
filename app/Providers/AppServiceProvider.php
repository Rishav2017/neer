<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OtpProviderInterface;
use App\Services\FirebaseOtpService;
use App\Services\TwilioOtpService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpProviderInterface::class, function ($app) {
            $provider = config('otp.provider');

            return match($provider) {
                'twilio' => new TwilioOtpService(),
                default => new FirebaseOtpService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
