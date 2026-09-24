<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::with([
            'product.category',
            'product.productUnits.unit',
        ])->whereHas('product');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('product', function ($q) use ($search) {
                $q->where(
                    'product_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if ($request->stock === 'in_stock') {
            $query->whereColumn(
                'quantity_on_hand',
                '>',
                'reorder_level'
            );
        }

        if ($request->stock === 'low_stock') {
            $query->where(
                'quantity_on_hand',
                '>',
                0
            )->whereColumn(
                'quantity_on_hand',
                '<=',
                'reorder_level'
            );
        }

        if ($request->stock === 'out_of_stock') {
            $query->where(
                'quantity_on_hand',
                '<=',
                0
            );
        }

        $inventories = $query
            ->orderBy('product_id')
            ->get();

        $categories = Category::where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $units = UnitOfMeasure::where('is_active', true)
            ->orderBy('unit_name')
            ->get();

        return view(
            'inventory.index',
            compact(
                'inventories',
                'categories',
                'units'
            )
        );
    }

    public function update(
        Request $request,
        Inventory $inventory
    ) {
        $data = $request->validate([
            'reorder_level' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $inventory->update([
            'reorder_level' => $data['reorder_level'],
            'last_updated' => now(),
        ]);

        $inventory->load('product');

        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'INVENTORY',
            'action' => 'UPDATE',
            'description' =>
                "Updated reorder level for {$inventory->product->product_name}.",
            'reference_type' => 'Inventory',
            'reference_id' => $inventory->inventory_id,
        ]);

        return back()->with(
            'success',
            'Reorder level updated successfully.'
        );
    }
}