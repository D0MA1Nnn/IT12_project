<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'inventory',
            'productUnits.unit',
            'suppliers',
        ]);

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('category_name', 'like', "%{$search}%");
                    });
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Stock filter
        if ($request->stock === 'in_stock') {
            $query->whereHas('inventory', function ($q) {
                $q->whereColumn('quantity_on_hand', '>', 'reorder_level');
            });
        }

        if ($request->stock === 'low_stock') {
            $query->whereHas('inventory', function ($q) {
                $q->where('quantity_on_hand', '>', 0)
                    ->whereColumn('quantity_on_hand', '<=', 'reorder_level');
            });
        }

        if ($request->stock === 'out_of_stock') {
            $query->whereHas('inventory', function ($q) {
                $q->where('quantity_on_hand', '<=', 0);
            });
        }

        // Status filter
        if ($request->status === 'active') {
            $query->where('is_active', true);
        }

        if ($request->status === 'archived') {
            $query->where('is_active', false);
        }

        $products = $query
            ->orderBy('product_name')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $units = UnitOfMeasure::where('is_active', true)
            ->orderBy('unit_name')
            ->get();

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('supplier_name')
            ->get();

        return view('products.index', compact(
            'products',
            'categories',
            'units',
            'suppliers'
        ));
    }

    public function create()
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request)
    {
        $data = $this->data($request);

        $product = DB::transaction(function () use ($data) {

            $product = Product::create([
                'category_id' => $data['category_id'],
                'product_name' => trim($data['product_name']),
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);

            Inventory::create([
                'product_id' => $product->product_id,
                'quantity_on_hand' => 0,
                'reorder_level' => $data['reorder_level'],
                'last_updated' => now(),
            ]);

            ProductUnit::create([
                'product_id' => $product->product_id,
                'unit_id' => $data['unit_id'],
                'selling_price' => $data['selling_price'],
                'purchase_cost' => $data['purchase_cost'],
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'is_active' => true,
            ]);

            $product->suppliers()->sync([$data['supplier_id']]);

            return $product;
        });

        $this->log(
            'CREATE',
            "Created product '{$product->product_name}'.",
            $product
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Product added successfully.');
    }

    public function edit(Product $product)
    {
        return redirect()->route('products.index');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->data($request);

        DB::transaction(function () use ($data, $product) {

            $product->update([
                'category_id' => $data['category_id'],
                'product_name' => trim($data['product_name']),
                'description' => $data['description'] ?? null,
            ]);

            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $product->product_id,
                ],
                [
                    'quantity_on_hand' => 0,
                    'reorder_level' => 0,
                    'last_updated' => now(),
                ]
            );

            $productUnit = $product->productUnits()
                ->where('is_base_unit', true)
                ->first();

            $selectedUnit = $product->productUnits()
                ->where('unit_id', $data['unit_id'])
                ->first();

            if ($selectedUnit && $productUnit && $selectedUnit->product_unit_id !== $productUnit->product_unit_id) {
                $oldSelectedConversion = (float) $selectedUnit->conversion_factor;

                if ($oldSelectedConversion <= 0) {
                    $oldSelectedConversion = 1;
                }

                $product->productUnits()
                    ->where('product_unit_id', '!=', $selectedUnit->product_unit_id)
                    ->get()
                    ->each(function (ProductUnit $unit) use ($oldSelectedConversion) {
                        $unit->conversion_factor = (float) $unit->conversion_factor / $oldSelectedConversion;
                        $unit->is_base_unit = false;
                        $unit->save();
                    });

                $selectedUnit->selling_price = $data['selling_price'];
                $selectedUnit->purchase_cost = $data['purchase_cost'];
                $selectedUnit->conversion_factor = 1;
                $selectedUnit->is_base_unit = true;
                $selectedUnit->is_active = true;
                $selectedUnit->save();

                $inventory->update([
                    'quantity_on_hand' => (float) $inventory->quantity_on_hand / $oldSelectedConversion,
                    'reorder_level' => (float) $data['reorder_level'] / $oldSelectedConversion,
                    'last_updated' => now(),
                ]);
            } else {
                if (! $productUnit) {
                    $productUnit = new ProductUnit;
                    $productUnit->product_id = $product->product_id;
                    $productUnit->is_base_unit = true;
                }

                $productUnit->unit_id = $data['unit_id'];
                $productUnit->selling_price = $data['selling_price'];
                $productUnit->purchase_cost = $data['purchase_cost'];
                $productUnit->conversion_factor = 1;
                $productUnit->is_active = true;

                $productUnit->save();

                $inventory->update([
                    'reorder_level' => $data['reorder_level'],
                    'last_updated' => now(),
                ]);
            }

            $product->suppliers()->sync([$data['supplier_id']]);
        });

        $product->refresh();

        $this->log(
            'UPDATE',
            "Updated product '{$product->product_name}'.",
            $product
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function deactivate(Product $product)
    {
        $product->update([
            'is_active' => false,
        ]);

        $this->log(
            'UPDATE',
            "Archived product '{$product->product_name}'.",
            $product
        );

        return back()->with(
            'success',
            'Product archived successfully.'
        );
    }

    public function activate(Product $product)
    {
        $product->update([
            'is_active' => true,
        ]);

        $this->log(
            'UPDATE',
            "Restored product '{$product->product_name}'.",
            $product
        );

        return back()->with(
            'success',
            'Product restored successfully.'
        );
    }

    private function data(Request $request)
    {
        $rules = [
            'category_id' => [
                'required',
                'exists:categories,category_id',
            ],

            'product_name' => [
                'required',
                'string',
                'max:150',
            ],

            'supplier_id' => [
                'required',
                'exists:suppliers,supplier_id',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'unit_id' => [
                'required',
                'exists:units_of_measure,unit_id',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'purchase_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'reorder_level' => [
                'required',
                'integer',
                'min:0',
            ],
        ];

        return $request->validate($rules);
    }

    private function log(
        string $action,
        string $description,
        Product $product
    ) {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'PRODUCT',
            'action' => $action,
            'description' => $description,
            'reference_type' => 'Product',
            'reference_id' => $product->product_id,
        ]);
    }
}
