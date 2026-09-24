<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    public function index(Product $product)
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
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

        $productUnit = $product->productUnits()->create([
            'unit_id' => $data['unit_id'],
            'selling_price' => $data['selling_price'],
            'purchase_cost' => $data['purchase_cost'],
            'conversion_factor' => $data['conversion_factor'],
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
    ) {
        abort_unless(
            $productUnit->product_id === $product->product_id,
            404
        );

        $data = $request->validate([
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

            'conversion_factor' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $productUnit->update($data);

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
    ) {
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
            'is_active' => !$productUnit->is_active,
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
    ) {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'PRODUCT',
            'action' => $action,
            'description' =>
                "{$action} unit option for {$product->product_name}.",
            'reference_type' => 'ProductUnit',
            'reference_id' => $productUnit->product_unit_id,
        ]);
    }
}