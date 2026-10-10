<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleBulkMaterialsSeeder extends Seeder
{
    /** Adds explicitly labeled examples without resetting existing stock or prices. */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->catalog() as $sample) {
                if (Product::where('product_name', $sample['name'])->exists()) {
                    continue;
                }

                $category = Category::firstOrCreate(['category_name' => $sample['category']], ['is_active' => true]);
                $supplier = Supplier::firstOrCreate(['supplier_name' => 'Sample Supplier (Replace Before Use)'], ['is_active' => true]);
                $product = Product::create([
                    'category_id' => $category->category_id,
                    'product_name' => $sample['name'],
                    'group_name' => $sample['group'],
                    'size_name' => $sample['size'],
                    'description' => 'SAMPLE DATA: stock, purchase costs and package conversions are invented examples. Replace with actual values before selling. '.$sample['note'],
                    'is_active' => true,
                ]);
                $product->suppliers()->attach($supplier->supplier_id);

                foreach ($sample['units'] as [$unitName, $symbol, $type, $factor, $cost]) {
                    $unit = UnitOfMeasure::firstOrCreate(['unit_name' => $unitName], [
                        'unit_symbol' => $symbol, 'unit_type' => $type, 'is_active' => true,
                    ]);
                    $product->productUnits()->create([
                        'unit_id' => $unit->unit_id,
                        'purchase_cost' => $cost,
                        'selling_price' => round($cost * 1.10, 2, PHP_ROUND_HALF_UP),
                        'conversion_factor' => $factor,
                        'is_base_unit' => $factor === 1,
                        'is_active' => true,
                    ]);
                }

                $product->inventory()->create([
                    'quantity_on_hand' => $sample['stock'],
                    'reorder_level' => 0,
                    'last_updated' => now(),
                ]);
            }
        });
    }

    /**
     * Costs use the listed selling unit; inventory always uses the first/base unit.
     *
     * @return list<array{name: string, group: ?string, size: ?string, category: string, stock: int, note: string, units: list<array{0: string, 1: string, 2: string, 3: int|float, 4: int|float}>}>
     */
    private function catalog(): array
    {
        return [
            [
                'name' => 'Common Nail (Sample) — 2″', 'group' => 'Common Nail (Sample)', 'size' => '2″',
                'category' => 'Hardware', 'stock' => 50,
                'note' => 'Example: one box contains 5 kg. No piece conversion is assumed.',
                'units' => [
                    ['Kilogram', 'kg', 'WEIGHT', 1, 150],
                    ['Box', 'box', 'COUNT', 5, 750],
                ],
            ],
            [
                'name' => 'Sand (Sample)', 'group' => null, 'size' => null,
                'category' => 'Construction Materials', 'stock' => 10,
                'note' => 'Example only: one bag is measured as 0.025 cubic meter (25 liters). Bag volume varies; measure your actual bags.',
                'units' => [
                    ['Cubic Meter', 'm³', 'VOLUME', 1, 900],
                    ['Bag', 'bag', 'COUNT', 0.025, 22.50],
                ],
            ],
            [
                'name' => 'Portland Cement (Sample)', 'group' => null, 'size' => null,
                'category' => 'Construction Materials', 'stock' => 2000,
                'note' => 'Example: one bag contains 40 kg; 2000 kg equals 50 bags.',
                'units' => [
                    ['Kilogram', 'kg', 'WEIGHT', 1, 7.50],
                    ['Bag', 'bag', 'COUNT', 40, 300],
                ],
            ],
        ];
    }
}
