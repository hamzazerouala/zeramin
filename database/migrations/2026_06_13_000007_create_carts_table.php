<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable()->index();

            // État du checkout (rempli au moment de create-payment-intent)
            $table->string('payment_intent_id')->nullable()->index();
            $table->json('shipping_address')->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();

            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
