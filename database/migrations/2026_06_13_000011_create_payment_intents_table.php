<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_payment_intent_id')->unique();
            $table->text('stripe_client_secret')->nullable();
            $table->unsignedBigInteger('amount'); // en centimes
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', [
                'requires_payment_method', 'requires_confirmation', 'requires_action',
                'processing', 'succeeded', 'canceled', 'failed',
            ])->default('requires_payment_method');
            $table->text('error_message')->nullable();
            $table->string('payment_method_used')->nullable();
            $table->string('charge_id')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
