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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

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
                    ->orWhere('group_name', 'like', "%{$search}%")
                    ->orWhere('size_name', 'like', "%{$search}%")
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

        $groups = Product::groupForDisplay($query->orderBy('product_name')->orderBy('product_id')->get());
        $page = max(1, min((int) $request->input('page', 1), max(1, (int) ceil($groups->count() / 10))));
        $products = (new LengthAwarePaginator($groups->forPage($page, 10)->values(), $groups->count(), 10, $page, [
            'path' => $request->url(),
        ]))->withQueryString();

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
                'group_name' => $data['group_name'],
                'size_name' => $data['size_name'],
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
            ->with('success', 'Product added successfully.')
            ->with('selected_product_size', $product->product_id);
    }

    public function edit(Product $product)
    {
        return redirect()->route('products.index');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->data($request, $product);

        DB::transaction(function () use ($data, $product) {

            $product->update([
                'category_id' => $data['category_id'],
                'product_name' => trim($data['product_name']),
                'group_name' => $data['group_name'],
                'size_name' => $data['size_name'],
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
            ->with('success', 'Product updated successfully.')
            ->with('selected_product_size', $product->product_id);
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

    private function data(Request $request, ?Product $product = null): array
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

            'has_sizes' => ['sometimes', 'boolean'],
            'size_name' => [Rule::requiredIf($request->boolean('has_sizes')), 'nullable', 'string', 'max:60'],

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

        $validator = Validator::make($request->all(), $rules, [
            'size_name.required' => 'Enter a size, or turn off Has sizes.',
        ]);
        $validator->after(function (ValidationValidator $validator) use ($request, $product): void {
            if ($validator->errors()->isNotEmpty() || ! $request->boolean('has_sizes')) {
                return;
            }

            $name = trim((string) $request->input('product_name'));
            $size = trim((string) $request->input('size_name'));
            if ($size === '') {
                $validator->errors()->add('size_name', 'Enter a size, or turn off Has sizes.');
            } elseif (mb_strlen($name.' — '.$size) > 150) {
                $validator->errors()->add('product_name', 'The product name and size together must not exceed 150 characters.');
            } elseif (Product::where('category_id', $request->input('category_id'))
                ->whereRaw('LOWER(group_name) = ?', [mb_strtolower($name)])
                ->whereRaw('LOWER(size_name) = ?', [mb_strtolower($size)])
                ->when($product, fn ($query) => $query->where('product_id', '!=', $product->product_id))
                ->exists()) {
                $validator->errors()->add('size_name', 'This size already exists for this product. Edit the existing size instead.');
            }
        });
        $data = $validator->validate();
        $hasSizes = $request->boolean('has_sizes');
        $data['group_name'] = $hasSizes ? trim($data['product_name']) : null;
        $data['size_name'] = $hasSizes ? trim($data['size_name']) : null;
        $data['product_name'] = $hasSizes ? $data['group_name'].' — '.$data['size_name'] : trim($data['product_name']);

        return $data;
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
