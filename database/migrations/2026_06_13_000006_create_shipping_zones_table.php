<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('seller_profiles')->cascadeOnDelete();
            $table->string('country', 10)->default('WORLD'); // code ISO ou 'WORLD'
            $table->enum('type', ['fixed', 'percentage', 'free'])->default('fixed');
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_free_above')->default(false);
            $table->decimal('free_above_amount', 10, 2)->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->timestamps();

            $table->unique(['seller_id', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
