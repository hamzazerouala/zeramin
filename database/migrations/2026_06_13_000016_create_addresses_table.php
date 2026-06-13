<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone')->nullable();
            $table->string('address');
            $table->string('address_complement')->nullable();
            $table->string('postal_code', 20);
            $table->string('city');
            $table->string('province')->nullable();
            $table->string('country', 2);
            $table->enum('type', ['shipping', 'billing', 'both'])->default('both');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
