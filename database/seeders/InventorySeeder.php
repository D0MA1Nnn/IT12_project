<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Inventory;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            Inventory::create([
                'product_id' => $product->product_id,
                'quantity_on_hand' => 0,
                'reorder_level' => 10,
                'last_updated' => now(),
            ]);
        }
    }
}