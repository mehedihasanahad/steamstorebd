<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_given', 10, 2)->default(0);
            $table->decimal('owner_reward', 10, 2)->default(0);
            $table->enum('status', ['pending', 'credited', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index('referrer_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_usages');
    }
};
