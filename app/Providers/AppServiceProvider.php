<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Configure la cle Stripe globalement si le SDK est present.
        if (class_exists(\Stripe\Stripe::class) && config('stripe.secret_key')) {
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
        }
    }
}
