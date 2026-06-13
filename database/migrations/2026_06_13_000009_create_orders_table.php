<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('seller_id')->constrained('seller_profiles')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email_guest')->nullable();

            // Montants
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Statut commande
            $table->enum('status', [
                'processing', 'shipped', 'in_transit', 'delivered', 'disputed', 'refunded', 'cancelled',
            ])->default('processing');

            // Paiement Stripe
            $table->string('payment_intent_id')->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');

            // Livraison
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('shipping_method')->nullable();

            // AliExpress
            $table->string('aliexpress_order_number')->nullable();
            $table->string('aliexpress_tracking_id')->nullable();
            $table->text('tracking_url')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->text('notes_internal')->nullable();
            $table->timestamps();

            // Index (cf. CDC 4.1)
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['seller_id', 'status']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
