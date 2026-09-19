<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Region belongs to the product, not the card: a $10 and a $50 Steam HKD card
 * are both Hong Kong.
 *
 * `region_group` is what powers the region switcher on the product page. Steam
 * Wallet HKD, USA and Turkey are three separate products sharing the group
 * 'steam-wallet', so each can list the others. A null group means no switcher,
 * which is every product that exists today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_card_categories', function (Blueprint $table) {
            // ISO-3166 alpha-2, or GL for a globally redeemable product.
            $table->string('region', 2)->nullable()->after('slug');
            $table->string('region_group')->nullable()->after('region');

            $table->index('region');
            $table->index('region_group');
        });
    }

    public function down(): void
    {
        Schema::table('gift_card_categories', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropIndex(['region_group']);
            $table->dropColumn(['region', 'region_group']);
        });
    }
};
