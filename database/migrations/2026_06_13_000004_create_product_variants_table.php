<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('aliexpress_sku_id')->nullable();
            $table->string('variant_name');
            $table->json('variant_values')->nullable();
            $table->decimal('cost_price_variant', 12, 2)->nullable();
            $table->decimal('markup_coefficient', 5, 2)->nullable();
            $table->decimal('markup_fixed', 12, 2)->nullable();
            $table->integer('stock_aliexpress_variant')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
