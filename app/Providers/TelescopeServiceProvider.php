<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        $this->authorization();

        Telescope::filter(function (IncomingEntry $entry) {
            return true; // Log everything
        });

        Telescope::tag(function (IncomingEntry $entry) {
            if ($entry->type === 'request') {
                return ['request'];
            }

            if ($entry->type === 'log') {
                return ['log'];
            }

            return [];
        });
    }

    protected function authorization()
    {
        Gate::define('viewTelescope', function ($user = null) {
            return app()->environment('local') || 
                   app()->environment('development') || 
                   request()->getHost() === 'api.koricha-ecommerce.com';
        });
    }
}
