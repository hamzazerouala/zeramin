<?php

return [
    // Phase 1 : scraping HTML via Guzzle + DomCrawler.
    'scraper_enabled' => env('ALIEXPRESS_SCRAPER_ENABLED', true),
    'timeout' => (int) env('ALIEXPRESS_TIMEOUT', 30),
    'user_agent' => env('ALIEXPRESS_USER_AGENT', 'Mozilla/5.0 (compatible; DropShopBot/1.0)'),

    // Méthode de récupération HTML : 'http' (Guzzle) ou 'headless' (à brancher
    // sur un service navigateur en production pour contourner l'anti-bot JS).
    'fetcher' => env('ALIEXPRESS_FETCHER', 'http'),

    // Pricing par defaut a l'import.
    'default_markup_coefficient' => 2.0,
    'default_markup_fixed' => 2.50,

    // Frequence de synchro des stocks (heures) - pilote par cron.
    'sync_interval_hours' => 4,

    // Fulfillment : manual (cPanel, par defaut) ou api (Phase 2).
    'fulfillment_mode' => env('ALIEXPRESS_FULFILLMENT_MODE', 'manual'),

    // Devise interne de la boutique (prix de vente).
    'store_currency' => env('STORE_CURRENCY', 'EUR'),

    // Taux de change indicatifs vers la devise boutique.
    'fx_to_store' => [
        'EUR' => 1.0,
        'USD' => 0.92,
        'GBP' => 1.17,
        'CNY' => 0.13,
    ],
];
