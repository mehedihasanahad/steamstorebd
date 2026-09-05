<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MODIFY COLUMN ... ENUM is MySQL-only syntax. On other drivers (SQLite in
     * the test suite) the 2026_04_26 migration has already widened this column
     * to a plain string, so there is no constraint left to widen here.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_review', 'payment_initiated', 'paid', 'processing', 'completed', 'failed', 'refunded', 'cancelled')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_review', 'payment_initiated', 'paid', 'processing', 'completed', 'failed', 'refunded')");
        }
    }
};
