<?php

namespace Database\Seeders\Product;

use App\Models\Product\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $SEED_COUNT = 5;

        Product::factory()
            ->count($SEED_COUNT)
            ->create();
    }
}
