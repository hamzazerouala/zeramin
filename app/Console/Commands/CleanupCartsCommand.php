<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanupCartsCommand extends Command
{
    protected $signature = 'carts:cleanup {--days=7 : Âge max d\'un panier abandonné}';

    protected $description = 'Supprime les paniers expirés / abandonnés (cron horaire).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $deleted = Cart::query()
            ->whereNull('payment_intent_id')
            ->where('updated_at', '<', $threshold)
            ->delete();

        $this->info("Paniers supprimés : {$deleted}");

        return self::SUCCESS;
    }
}
