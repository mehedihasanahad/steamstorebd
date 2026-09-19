<?php

namespace Database\Seeders;

use App\Services\StorefrontCatalog;
use Database\Seeders\Demo\AccountDemoSeeder;
use Database\Seeders\Demo\CatalogDemoSeeder;
use Database\Seeders\Demo\OrderDemoSeeder;
use Database\Seeders\Demo\ReferralDemoSeeder;
use Database\Seeders\Demo\ReviewDemoSeeder;
use Database\Seeders\Demo\SiteSettingsDemoSeeder;
use Illuminate\Database\Seeder;

/**
 * Content for every storefront page, so each one can be looked at and tested
 * with something real on it.
 *
 * Deliberately NOT registered in DatabaseSeeder: this is fixture data, not
 * production seed data, and running it against a live database would invent
 * products, orders and customers. Every step is idempotent, so it can be
 * re-run to refresh a demo environment.
 *
 *     php artisan db:seed --class=DemoStorefrontSeeder
 *
 * Sign in as shopper@steamstorebd.test / password to see the account pages.
 */
class DemoStorefrontSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Order matters: settings switch the feature blocks on, the
            // catalog gives the orders something to reference, and the account
            // seeders hang off the shopper the order seeder creates.
            SiteSettingsDemoSeeder::class,
            CatalogDemoSeeder::class,
            ReviewDemoSeeder::class,
            OrderDemoSeeder::class,
            ReferralDemoSeeder::class,
            AccountDemoSeeder::class,
        ]);

        StorefrontCatalog::flush();
    }
}
