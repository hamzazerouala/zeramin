<?php

namespace App\Console\Commands;

use App\Jobs\SyncAliExpressStockJob;
use App\Models\Product;
use Illuminate\Console\Command;

class SyncAliExpressCommand extends Command
{
    protected $signature = 'aliexpress:sync {--limit=100 : Nombre max de produits à synchroniser}';

    protected $description = 'Synchronise prix et stocks des produits depuis AliExpress (cron toutes les 4h).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $products = Product::active()
            ->whereNotNull('aliexpress_product_id')
            ->orderBy('synced_at')
            ->limit($limit)
            ->get();

        $this->info("Produits à synchroniser : {$products->count()}");

        foreach ($products as $product) {
            SyncAliExpressStockJob::dispatch($product);
            $this->line("→ file d'attente : #{$product->id} {$product->title}");
        }

        $this->info('Jobs de synchronisation mis en file.');

        return self::SUCCESS;
    }
}
