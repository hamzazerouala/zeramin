<?php

use Illuminate\Support\Facades\Schedule;

// Planification (exécutée par le cron cPanel "php artisan schedule:run").
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('aliexpress:sync')->cron('0 */4 * * *');
Schedule::command('carts:cleanup')->hourly();
Schedule::command('cache:prune-stale-tags')->daily();
