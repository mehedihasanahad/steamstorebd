<?php

namespace Database\Seeders;

use App\Models\GiftCardCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'USD Cards', 'slug' => 'usd-cards', 'description' => 'Steam gift cards in US Dollar denominations', 'sort_order' => 1]
        ];

        foreach ($categories as $category) {
            GiftCardCategory::firstOrCreate(['slug' => $category['slug']], array_merge($category, ['is_active' => true]));
        }
    }
}
