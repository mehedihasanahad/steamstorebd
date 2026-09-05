<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN ... ENUM is MySQL-only syntax. On other drivers (SQLite in
        // the test suite) widen the column to a plain string instead, which drops
        // the CHECK constraint the original enum() compiled to and lets every
        // status value through.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','pending_review','payment_initiated','paid','processing','completed','failed','refunded') DEFAULT 'pending'");
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('bkash_online')->after('status');
            $table->string('send_money_trx_id', 100)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'send_money_trx_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','payment_initiated','paid','processing','completed','failed','refunded') DEFAULT 'pending'");
        }
    }
};
