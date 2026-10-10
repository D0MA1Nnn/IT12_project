<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RealInventoryReplacementSeeder extends Seeder
{
    /**
     * Explicit destructive replacement of business data; never called by DatabaseSeeder.
     * Preserves login accounts and operational tables, and does not create a backup.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $accountFingerprint = $this->accountFingerprint();

            foreach ([
                'sale_items', 'sales', 'purchase_items', 'purchases', 'activity_logs',
                'inventories', 'product_supplier', 'product_units', 'products',
                'suppliers', 'categories', 'units_of_measure',
            ] as $table) {
                DB::table($table)->delete();
            }

            $suppliers = [];
            $categories = [];
            $units = [];

            foreach ($this->catalog() as [$supplierName, $categoryName, $name, $size, $unitName, $quantity, $purchaseCost]) {
                $supplier = $suppliers[$supplierName] ??= Supplier::create([
                    'supplier_name' => $supplierName,
                    'is_active' => true,
                ]);
                $category = $categories[$categoryName] ??= Category::create([
                    'category_name' => $categoryName,
                    'is_active' => true,
                ]);
                $unit = $units[$unitName] ??= UnitOfMeasure::create([
                    'unit_name' => $unitName,
                    'unit_symbol' => match ($unitName) {
                        'Piece' => 'pc', 'Pack' => 'pack', 'Bag' => 'bag',
                        'Length' => 'length', 'Roll' => 'roll', 'Can' => 'can',
                    },
                    'unit_type' => 'COUNT',
                    'is_active' => true,
                ]);

                $product = Product::create([
                    'category_id' => $category->category_id,
                    'product_name' => $size !== null ? $name.' — '.$size : $name,
                    'group_name' => $size !== null ? $name : null,
                    'size_name' => $size,
                    'is_active' => true,
                ]);
                $product->suppliers()->attach($supplier->supplier_id);
                $product->productUnits()->create([
                    'unit_id' => $unit->unit_id,
                    'purchase_cost' => $purchaseCost,
                    'selling_price' => round($purchaseCost * 1.10, 2, PHP_ROUND_HALF_UP),
                    'conversion_factor' => 1,
                    'is_base_unit' => true,
                    'is_active' => true,
                ]);
                $product->inventory()->create([
                    'quantity_on_hand' => $quantity,
                    'reorder_level' => 0,
                    'last_updated' => now(),
                ]);
            }

            if (Product::count() !== 37 || Supplier::count() !== 7 || $this->accountFingerprint() !== $accountFingerprint) {
                throw new RuntimeException('Replacement verification failed; no business data was committed.');
            }
        });
    }

    private function accountFingerprint(): string
    {
        return hash('sha256', DB::table('users')->orderBy('user_id')->get()->toJson());
    }

    /**
     * Only the 37 clear entries approved from the four stock photographs.
     * Quantities and costs are per listed stock unit; no unknown package conversions.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: ?string, 4: string, 5: int, 6: float|int}>
     */
    private function catalog(): array
    {
        return [
            ['MZ Trading', 'Hardware', 'Yestar Hose Clamp', '#1', 'Pack', 4, 27.90],
            ['MZ Trading', 'Hardware', 'Yestar Double Hole Metal Pipe Clamp', '#3 (20 mm)', 'Piece', 30, 10.70],
            ['MZ Trading', 'Hardware', 'Yestar Double Hole Metal Pipe Clamp', '#4', 'Piece', 30, 15.30],
            ['Sunreach Distribution Corp.', 'Construction Materials', 'Tile Adhesive', null, 'Bag', 10, 370],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PPR Coupling', null, 'Piece', 100, 4.20],
            ['Trust Hardware', 'Electrical Materials', 'Safety Breaker', '20 A', 'Piece', 10, 345],
            ['Trust Hardware', 'Electrical Materials', 'Safety Breaker', '30 A', 'Piece', 10, 345],
            ['Trust Hardware', 'Electrical Materials', 'Safety Breaker with Metal Cover', null, 'Piece', 10, 506.25],
            ['Trust Hardware', 'Electrical Materials', 'Switch', '1 Gang', 'Piece', 10, 59.25],
            ['Trust Hardware', 'Electrical Materials', 'Outlet with Ground', '2 Gang', 'Piece', 10, 123.75],
            ['MZ Trading', 'Hardware', 'Hinge', '#2', 'Piece', 30, 47.20],
            ['MZ Trading', 'Hardware', 'Hinge', '#3', 'Piece', 50, 74.50],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PVC Pipe', '#6 / 160 mm', 'Length', 6, 918],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PVC Pipe', '#8 / 200 mm', 'Length', 4, 1407.60],
            ['YSL', 'Hardware', 'Tie Box', '800 g', 'Piece', 36, 58],
            ['YSL', 'Hardware', 'Lever Handle', null, 'Piece', 10, 155],
            ['YSL', 'Hardware', 'Stanley Hinge', '3×3', 'Piece', 50, 40],
            ['YSL', 'Hardware', 'Stanley Hinge', '3.5×3.5', 'Piece', 30, 48],
            ['YSL', 'Hardware', 'Stanley Hinge', '4×4', 'Piece', 30, 55],
            ['YSL', 'Hardware', 'Tex Metal Screw', '12×55', 'Piece', 3000, 0.68],
            ['YSL', 'Tools', 'Tex Screw Adaptor', '45 mm', 'Pack', 5, 80],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PVC Tech Elbow', '4″×90°, DH', 'Piece', 100, 34.51],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PVC Tech Elbow', '2″×90°, DH', 'Piece', 100, 11.50],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PVC Tech Elbow', '4″×45°, DH', 'Piece', 60, 26.96],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'Sani-Tech Pipe', '4″×3 m, with Hub', 'Length', 30, 306],
            ['Techno Trade Resources Inc.', 'Plumbing Materials', 'PE Tech TRI Straight Coupler', '20×20 mm', 'Piece', 100, 27.50],
            ['YSL', 'Plumbing Materials', 'PVC Faucet', 'P/B, Blue', 'Piece', 50, 16],
            ['YSL', 'Plumbing Materials', 'PVC Faucet', 'H/B, Blue', 'Piece', 50, 17],
            ['MZ Trading', 'Hardware', 'Yestar LPG Regulator with Gauge', null, 'Piece', 10, 357.60],
            ['UNIUP', 'Hardware', 'GI Double Clamp', '½″ (4×2)', 'Piece', 100, 2.36],
            ['UNIUP', 'Plumbing Materials', 'Brass Ball Valve', '½″ (120)', 'Piece', 24, 120],
            ['UNIUP', 'Tools', 'Superthin Cutting Wheel', '105×1.0×16 mm', 'Piece', 550, 11],
            ['UNIUP', 'Plumbing Materials', 'Stainless Duplex Strainer', '2½″', 'Piece', 30, 80],
            ['Davao Metal Flow', 'Electrical Materials', 'Amak Electrical Butyl-Rubber Tape', null, 'Roll', 10, 101],
            ['Davao Metal Flow', 'Tools', 'Putty Knife Blade without Handle', '4″', 'Piece', 12, 5.75],
            ['Davao Metal Flow', 'Hardware', 'Bosny', '#183, Gray', 'Can', 12, 144.50],
            ['Davao Metal Flow', 'Electrical Materials', 'Flat Cord Wire', '#16/2, 1.25 mm²×150 m', 'Roll', 2, 3045.75],
        ];
    }
}
