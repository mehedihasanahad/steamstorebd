<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every admin rewrite of an order's line items lands here. Orders move money
     * and inventory, so each edit keeps a full before/after snapshot, the codes
     * it touched, and who did it — the order row itself only ever shows the
     * latest state.
     */
    public function up(): void
    {
        Schema::create('order_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_status', 30);
            $table->decimal('subtotal_before_bdt', 10, 2);
            $table->decimal('subtotal_after_bdt', 10, 2);
            $table->decimal('total_before_bdt', 10, 2);
            $table->decimal('total_after_bdt', 10, 2);
            // Positive: the customer still owes this much. Negative: we owe them.
            $table->decimal('balance_delta_bdt', 10, 2);
            $table->decimal('wallet_refunded_bdt', 10, 2)->default(0);
            $table->json('items_before');
            $table->json('items_after');
            $table->json('code_changes');
            $table->text('reason');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_edits');
    }
};
