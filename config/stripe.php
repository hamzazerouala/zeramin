<?php

return [
    'public_key' => env('STRIPE_KEY'),
    'secret_key' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    // Devise par défaut quand le pays n'est pas mappé.
    'default_currency' => 'eur',

    // Pays -> devise (Stripe convertit selon le pays de facturation).
    'currency_by_country' => [
        'GB' => 'gbp', 'SE' => 'sek', 'DK' => 'dkk', 'NO' => 'nok',
        'CH' => 'chf', 'US' => 'usd', 'CA' => 'cad', 'AU' => 'aud',
        'JP' => 'jpy',
    ],

    // Moyens de paiement de base + spécifiques par pays.
    'base_payment_methods' => ['card', 'apple_pay', 'google_pay'],

    'country_payment_methods' => [
        'NL' => ['ideal'],
        'DE' => ['giropay', 'sepa_debit'],
        'AT' => ['eps'],
        'SE' => ['klarna'],
        'NO' => ['klarna'],
        'DK' => ['klarna'],
        'US' => ['us_bank_account'],
        'GB' => ['klarna'],
        'ES' => ['sepa_debit'],
        'FR' => ['sepa_debit'],
        'IT' => ['sepa_debit'],
        'BE' => ['sepa_debit', 'bancontact'],
    ],
];
