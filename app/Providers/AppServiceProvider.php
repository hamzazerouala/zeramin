<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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

        // L'API ne sert pas de pages web : le lien de reinitialisation pointe vers le frontend.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontend = rtrim(config('cors.allowed_origins')[0] ?? config('app.url'), '/');

            return "{$frontend}/reset-password?token={$token}&email=".urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
