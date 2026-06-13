<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('seller_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            // Métadonnées AliExpress
            $table->string('aliexpress_product_id')->nullable();
            $table->text('aliexpress_url')->nullable();
            $table->json('aliexpress_raw')->nullable();

            // Vitrine
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('slug')->unique();

            // Pricing
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->string('cost_currency', 3)->default('EUR');
            $table->decimal('markup_coefficient', 5, 2)->default(2.0);
            $table->decimal('markup_fixed', 12, 2)->default(0);
            $table->decimal('final_price_calculated', 12, 2)->default(0);

            // Stats & stock
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->integer('stock_aliexpress')->default(0);
            $table->integer('stock_platform')->default(0);
            $table->unsignedSmallInteger('shipping_days_estimated')->nullable();

            // Visibilité & SEO
            $table->boolean('is_active')->default(true);
            $table->boolean('featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Index critiques (cf. CDC 4.1)
            $table->index(['seller_id', 'is_active'], 'idx_seller_active');
            $table->index(['category_id', 'is_active']);
            $table->index('featured');
            $table->index('aliexpress_product_id');
        });

        // Index FULLTEXT pour la recherche (MySQL InnoDB uniquement).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT ft_product_search (title, description)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
