<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    public function index(Product $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'unit_id' => [
                'required',
                'exists:units_of_measure,unit_id',
            ],

            'conversion_factor' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $exists = $product->productUnits()
            ->where('unit_id', $data['unit_id'])
            ->exists();

        if ($exists) {
            return back()->with(
                'error',
                'This unit is already assigned to the product.'
            );
        }

        $baseUnit = $product->productUnits()
            ->where('is_base_unit', true)
            ->first()
            ?? $product->productUnits()->first();

        if (! $baseUnit) {
            return back()->with(
                'error',
                'Base unit is missing for this product.'
            );
        }

        $conversionFactor = (float) $data['conversion_factor'];

        $productUnit = $product->productUnits()->create([
            'unit_id' => $data['unit_id'],
            'selling_price' => round((float) $baseUnit->selling_price * $conversionFactor, 2),
            'purchase_cost' => round((float) $baseUnit->purchase_cost * $conversionFactor, 2),
            'conversion_factor' => $conversionFactor,
            'is_base_unit' => false,
            'is_active' => true,
        ]);

        $this->log(
            $product,
            $productUnit,
            'CREATE'
        );

        return back()->with(
            'success',
            'Unit option added successfully.'
        );
    }

    public function update(
        Request $request,
        Product $product,
        ProductUnit $productUnit
    ): RedirectResponse {
        abort_unless(
            $productUnit->product_id === $product->product_id,
            404
        );

        $data = $request->validate([
            'selling_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'purchase_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'conversion_factor' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $conversionFactor = (float) $data['conversion_factor'];

        if ($productUnit->is_base_unit) {
            $productUnit->update([
                'selling_price' => $data['selling_price'] ?? $productUnit->selling_price,
                'purchase_cost' => $data['purchase_cost'] ?? $productUnit->purchase_cost,
                'conversion_factor' => 1,
            ]);
        } else {
            $baseUnit = $product->productUnits()
                ->where('is_base_unit', true)
                ->first()
                ?? $product->productUnits()
                    ->where('product_unit_id', '!=', $productUnit->product_unit_id)
                    ->first();

            if (! $baseUnit) {
                return back()->with(
                    'error',
                    'Base unit is missing for this product.'
                );
            }

            $productUnit->update([
                'selling_price' => round((float) $baseUnit->selling_price * $conversionFactor, 2),
                'purchase_cost' => round((float) $baseUnit->purchase_cost * $conversionFactor, 2),
                'conversion_factor' => $conversionFactor,
            ]);
        }

        $this->log(
            $product,
            $productUnit,
            'UPDATE'
        );

        return back()->with(
            'success',
            'Product unit updated successfully.'
        );
    }

    public function toggle(
        Product $product,
        ProductUnit $productUnit
    ): RedirectResponse {
        abort_unless(
            $productUnit->product_id === $product->product_id,
            404
        );

        if ($productUnit->is_base_unit) {
            return back()->with(
                'error',
                'The base unit cannot be archived.'
            );
        }

        $productUnit->update([
            'is_active' => ! $productUnit->is_active,
        ]);

        $this->log(
            $product,
            $productUnit,
            'UPDATE'
        );

        return back()->with(
            'success',
            $productUnit->is_active
                ? 'Product unit restored successfully.'
                : 'Product unit archived successfully.'
        );
    }

    private function log(
        Product $product,
        ProductUnit $productUnit,
        string $action
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'PRODUCT',
            'action' => $action,
            'description' => "{$action} unit option for {$product->product_name}.",
            'reference_type' => 'ProductUnit',
            'reference_id' => $productUnit->product_unit_id,
        ]);
    }
}
