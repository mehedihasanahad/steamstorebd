<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Instructions and FAQ tabs on the product page.
 *
 * long_description already exists and becomes the Description tab. Brand-level
 * how_to_redeem stays the fallback for Instructions, so products that set
 * nothing inherit what is already written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->text('instructions')->nullable()->after('long_description');
            $table->json('faq')->nullable()->after('instructions');
        });
    }

    public function down(): void
    {
        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->dropColumn(['instructions', 'faq']);
        });
    }
};
