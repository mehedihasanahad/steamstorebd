<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('gift_card_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('denomination_usd', 8, 2);
            $table->decimal('denomination_bdt', 10, 2);
            $table->decimal('price_bdt', 10, 2);
            $table->text('description')->nullable();
            $table->string('badge_text')->nullable();
            $table->integer('stock_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
