<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $lumber = Category::where(
            'category_name',
            'Lumber'
        )->firstOrFail();

        $construction = Category::where(
            'category_name',
            'Construction Materials'
        )->firstOrFail();

        $hardware = Category::where(
            'category_name',
            'Hardware'
        )->firstOrFail();

        $plumbing = Category::where(
            'category_name',
            'Plumbing Materials'
        )->firstOrFail();

        $electrical = Category::where(
            'category_name',
            'Electrical Materials'
        )->firstOrFail();

        $tools = Category::where(
            'category_name',
            'Tools'
        )->firstOrFail();

        Product::create([
            'category_id' => $lumber->category_id,
            'product_name' => '2x2 Lumber',
            'group_name' => 'Lumber',
            'size_name' => '2x2',
            'description' => 'Common construction lumber.',
        ]);

        Product::create([
            'category_id' => $lumber->category_id,
            'product_name' => '2x3 Lumber',
            'group_name' => 'Lumber',
            'size_name' => '2x3',
            'description' => 'Common construction lumber.',
        ]);

        Product::create([
            'category_id' => $lumber->category_id,
            'product_name' => '2x4 Lumber',
            'group_name' => 'Lumber',
            'size_name' => '2x4',
            'description' => 'Common construction lumber.',
        ]);

        Product::create([
            'category_id' => $construction->category_id,
            'product_name' => 'Portland Cement',
            'description' => 'General purpose cement.',
        ]);

        Product::create([
            'category_id' => $construction->category_id,
            'product_name' => 'Concrete Hollow Block',
            'description' => 'Standard concrete hollow block.',
        ]);

        Product::create([
            'category_id' => $hardware->category_id,
            'product_name' => 'Common Nail 2"',
            'group_name' => 'Common Nail',
            'size_name' => '2"',
            'description' => 'Common construction nail.',
        ]);

        Product::create([
            'category_id' => $plumbing->category_id,
            'product_name' => 'PVC Pipe 1/2"',
            'group_name' => 'PVC Pipe',
            'size_name' => '1/2"',
            'description' => 'PVC plumbing pipe.',
        ]);

        Product::create([
            'category_id' => $electrical->category_id,
            'product_name' => 'Electrical Wire',
            'description' => 'General purpose electrical wire.',
        ]);

        Product::create([
            'category_id' => $tools->category_id,
            'product_name' => 'Claw Hammer',
            'description' => 'General purpose claw hammer.',
        ]);
    }
}
