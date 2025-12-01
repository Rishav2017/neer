<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon night mode based on user preference
        // Horizon::night();

        // Configure Horizon notification settings
        // Horizon::routeMailNotificationsTo('admin@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
        // Horizon::routeSmsNotificationsTo('15551234567');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // In production, only allow admins to access Horizon
            if (app()->environment('local')) {
                return true;
            }

            // Allow access for authenticated admin users
            if ($user && $user->role === 'admin') {
                return true;
            }

            // Allow access via specific IPs (optional)
            // $allowedIps = ['127.0.0.1'];
            // if (in_array(request()->ip(), $allowedIps)) {
            //     return true;
            // }

            return false;
        });
    }
}
