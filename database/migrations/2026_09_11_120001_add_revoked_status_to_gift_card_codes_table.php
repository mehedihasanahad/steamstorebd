<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A code that was already emailed to a customer cannot go back on the shelf
     * when an admin edits the order — reselling it would hand two buyers the
     * same key. Such codes are burned as 'revoked' instead, which keeps them out
     * of every `available` lookup while leaving the audit trail intact.
     *
     * MODIFY COLUMN ... ENUM is MySQL-only syntax. On other drivers (SQLite in
     * the test suite) widen the column to a plain string, which drops the CHECK
     * constraint the original enum() compiled to.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE gift_card_codes MODIFY COLUMN status ENUM('available','reserved','sold','revoked') NOT NULL DEFAULT 'available'");

            return;
        }

        Schema::table('gift_card_codes', function (Blueprint $table) {
            $table->string('status')->default('available')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE gift_card_codes SET status = 'sold' WHERE status = 'revoked'");
        DB::statement("ALTER TABLE gift_card_codes MODIFY COLUMN status ENUM('available','reserved','sold') NOT NULL DEFAULT 'available'");
    }
};
