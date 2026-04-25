<?php

namespace Database\Seeders;

use App\Models\GiftCardCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'USD Cards', 'slug' => 'usd-cards', 'description' => 'Steam gift cards in US Dollar denominations', 'sort_order' => 1],
            ['name' => 'BDT Cards', 'slug' => 'bdt-cards', 'description' => 'Steam gift cards in Bangladeshi Taka', 'sort_order' => 2],
            ['name' => 'Gift Sets', 'slug' => 'gift-sets', 'description' => 'Special Steam gift card bundles', 'sort_order' => 3],
        ];

        foreach ($categories as $category) {
            GiftCardCategory::firstOrCreate(['slug' => $category['slug']], array_merge($category, ['is_active' => true]));
        }
    }
}
