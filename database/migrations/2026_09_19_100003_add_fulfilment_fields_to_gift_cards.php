<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a card is delivered.
 *
 * `code_pool` is the default and is exactly what every existing row does
 * today: pull one pre-stocked GiftCardCode per unit. Software licence keys
 * work the same way. Subscriptions and game top-ups do not, so they get
 * `credentials` and `manual`.
 *
 * manual_stock is a NEW column rather than a reuse of `stock_count`. That
 * column is shadowed by an accessor on read, so its stored value has never had
 * to be correct — OrderEditService::syncStockCounts() exists precisely because
 * it drifts. Promoting a stale value to authoritative stock the moment an admin
 * flips a card to `manual` would silently oversell a product that cannot be
 * auto-delivered. A new column starts empty on every existing row.
 *
 * `string` not `enum`: adding a value to a MySQL enum needs a table rebuild,
 * and this repo has already paid that cost twice on orders.status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->string('fulfilment_type', 20)->default('code_pool')->after('is_active');
            $table->unsignedInteger('manual_stock')->default(0)->after('fulfilment_type');
            $table->string('delivery_eta_label')->nullable()->after('manual_stock');

            $table->index('fulfilment_type');
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropIndex(['fulfilment_type']);
            $table->dropColumn(['fulfilment_type', 'manual_stock', 'delivery_eta_label']);
        });
    }
};
