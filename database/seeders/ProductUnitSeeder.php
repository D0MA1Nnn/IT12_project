<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
{
    public function run(): void
    {
        $piece = UnitOfMeasure::where(
            'unit_symbol',
            'pc'
        )->firstOrFail();

        $meter = UnitOfMeasure::where(
            'unit_symbol',
            'm'
        )->firstOrFail();

        $kilogram = UnitOfMeasure::where(
            'unit_symbol',
            'kg'
        )->firstOrFail();

        $bag = UnitOfMeasure::where(
            'unit_symbol',
            'bag'
        )->firstOrFail();

        $box = UnitOfMeasure::where(
            'unit_symbol',
            'box'
        )->firstOrFail();

        $products = Product::all();

        foreach ($products as $product) {

            if (
                $product->product_name === '2x2 Lumber' ||
                $product->product_name === '2x3 Lumber' ||
                $product->product_name === '2x4 Lumber' ||
                $product->product_name === 'Electrical Wire' ||
                $product->product_name === 'PVC Pipe 1/2"'
            ) {
                ProductUnit::create([
                    'product_id' => $product->product_id,
                    'unit_id' => $meter->unit_id,
                    'selling_price' => 100.00,
                    'purchase_cost' => 70.00,
                    'conversion_factor' => 1,
                    'is_base_unit' => true,
                    'is_active' => true,
                ]);
            } elseif ($product->product_name === 'Portland Cement') {

                ProductUnit::create([
                    'product_id' => $product->product_id,
                    'unit_id' => $kilogram->unit_id,
                    'selling_price' => 8.00,
                    'purchase_cost' => 7.00,
                    'conversion_factor' => 1,
                    'is_base_unit' => true,
                    'is_active' => true,
                ]);

                ProductUnit::create([
                    'product_id' => $product->product_id,
                    'unit_id' => $bag->unit_id,
                    'selling_price' => 280.00,
                    'purchase_cost' => 245.00,
                    'conversion_factor' => 40,
                    'is_base_unit' => false,
                    'is_active' => true,
                ]);
            } elseif ($product->product_name === 'Common Nail 2"') {

                ProductUnit::create([
                    'product_id' => $product->product_id,
                    'unit_id' => $box->unit_id,
                    'selling_price' => 150.00,
                    'purchase_cost' => 120.00,
                    'conversion_factor' => 1,
                    'is_base_unit' => true,
                    'is_active' => true,
                ]);
            } else {

                ProductUnit::create([
                    'product_id' => $product->product_id,
                    'unit_id' => $piece->unit_id,
                    'selling_price' => 100.00,
                    'purchase_cost' => 75.00,
                    'conversion_factor' => 1,
                    'is_base_unit' => true,
                    'is_active' => true,
                ]);
            }
        }
    }
}
