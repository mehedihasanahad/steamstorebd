<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marks every account that already exists as verified.
 *
 * `User` now implements MustVerifyEmail and the checkout routes carry the
 * `verified` middleware, which stops a script registering an address it does
 * not own and placing send-money orders that reserve real stock.
 *
 * Nothing ever wrote `email_verified_at` before this, so without a back-fill
 * that same gate would shut out every existing customer -- people who have
 * been buying here for months would hit a confirm-your-address wall on their
 * next order, for an address we already deliver their codes to. This fills it
 * in for all of them, once, so the gate only ever applies to accounts created
 * from here on.
 *
 * It must run before the new routes serve traffic. Deploying the usual way --
 * `php artisan migrate` as part of the release -- does that.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Two statements rather than COALESCE(created_at, NOW()), because the
        // test suite runs on SQLite, which has no NOW().
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNotNull('created_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);

        // Whatever is left has no created_at either, so it predates the rows
        // that do; stamping it now is the closest honest answer.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Deliberately empty. Rolling back cannot tell an address this
        // migration filled in from one a customer has since confirmed by
        // e-mail, and clearing both would lock out the second group. Leaving
        // the timestamps in place is the harmless direction to be wrong in.
    }
};
