<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shop_slug')->unique();
            $table->string('shop_name');
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->text('bio')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('country', 2)->nullable();

            // Intégrations
            $table->string('stripe_connect_id')->nullable();
            $table->string('aliexpress_account')->nullable();
            $table->text('aliexpress_password_encrypted')->nullable();

            // KYC & business
            $table->timestamp('kyc_verified_at')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
