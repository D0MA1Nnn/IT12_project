<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $units = ProductUnit::with(['product', 'unit'])->where('is_active', true)->whereHas('product', fn ($q) => $q->where('is_active', true))->get();
        $suppliers = Supplier::with('products')->where('is_active', true)->orderBy('supplier_name')->get();
        $purchaseQuery = Purchase::with(['supplier', 'user', 'items.productUnit.product', 'items.productUnit.unit'])
            ->latest('purchase_date');

        if ($search !== '') {
            $purchaseQuery->where(function ($query) use ($search) {
                $referenceId = (int) preg_replace('/\D/', '', $search);

                if ($referenceId > 0) {
                    $query->where('purchase_id', $referenceId);
                }

                $query->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('supplier_name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('username', 'like', "%{$search}%"));
            });
        }

        $unitData = $units->map(function ($u) {
            return [
                'id' => $u->product_unit_id,
                'product_id' => $u->product_id,
                'product_name' => $u->product?->product_name,
                'unit_name' => $u->unit?->unit_name,
                'unit_symbol' => $u->unit?->unit_symbol,
                'purchase_cost' => (float) $u->purchase_cost,
                'conversion_factor' => (float) $u->conversion_factor,
                'is_base_unit' => (bool) $u->is_base_unit,
            ];
        })->values()->all();
        $supplierProducts = $suppliers->mapWithKeys(fn ($supplier) => [
            $supplier->supplier_id => $supplier->products->pluck('product_id')->values()->all(),
        ])->all();

        return view('purchases.index', ['purchases' => $purchaseQuery->get(), 'suppliers' => $suppliers, 'units' => $units, 'unitData' => $unitData, 'supplierProducts' => $supplierProducts, 'search' => $search]);
    }

    public function create()
    {
        $units = ProductUnit::with(['product', 'unit'])->where('is_active', true)->whereHas('product', fn ($q) => $q->where('is_active', true))->get();
        $unitData = $units->map(function ($u) {
            return [
                'id' => $u->product_unit_id,
                'product_id' => $u->product_id,
                'product_name' => $u->product?->product_name,
                'unit_name' => $u->unit?->unit_name,
                'unit_symbol' => $u->unit?->unit_symbol,
                'purchase_cost' => (float) $u->purchase_cost,
                'conversion_factor' => (float) $u->conversion_factor,
                'is_base_unit' => (bool) $u->is_base_unit,
            ];
        })->values()->all();

        return view('purchases.create', ['suppliers' => Supplier::where('is_active', true)->orderBy('supplier_name')->get(), 'units' => $units, 'unitData' => $unitData]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['supplier_id' => 'required|exists:suppliers,supplier_id', 'purchase_date' => 'required|date', 'items' => 'required|array|min:1', 'items.*.product_unit_id' => 'required|exists:product_units,product_unit_id', 'items.*.quantity' => 'required|integer|min:1', 'items.*.unit_cost' => 'required|numeric|min:0']);
        $supplier = Supplier::with('products')->findOrFail($d['supplier_id']);
        $offeredProductIds = $supplier->products->pluck('product_id')->map(fn ($productId) => (int) $productId);
        $p = DB::transaction(function () use ($d, $offeredProductIds) {
            $p = Purchase::create(['supplier_id' => $d['supplier_id'], 'user_id' => auth()->id(), 'purchase_date' => $d['purchase_date'], 'total_amount' => 0, 'status' => 'COMPLETED']);
            $total = 0;
            foreach ($d['items'] as $i) {
                $pu = ProductUnit::findOrFail($i['product_unit_id']);

                if (! $offeredProductIds->contains((int) $pu->product_id)) {
                    throw ValidationException::withMessages([
                        'items' => 'Please select only products offered by the chosen supplier.',
                    ]);
                }

                $sub = round($i['quantity'] * $i['unit_cost'], 2);
                PurchaseItem::create(['purchase_id' => $p->purchase_id, 'product_unit_id' => $pu->product_unit_id, 'quantity' => $i['quantity'], 'unit_cost' => $i['unit_cost'], 'subtotal' => $sub]);
                $base = round($i['quantity'] * $pu->conversion_factor, 8);
                $inv = Inventory::firstOrCreate(['product_id' => $pu->product_id], ['quantity_on_hand' => 0, 'reorder_level' => 0]);
                $inv->increment('quantity_on_hand', $base);
                $inv->update(['last_updated' => now()]);
                $total += $sub;
            }$p->update(['total_amount' => $total]);

            return $p;
        });
        ActivityLog::create(['user_id' => auth()->id(), 'module' => 'PURCHASE', 'action' => 'CREATE', 'description' => "Recorded completed purchase #{$p->purchase_id}.", 'reference_type' => 'Purchase', 'reference_id' => $p->purchase_id]);

        return redirect()->route('purchases.index')->with('success', 'Purchase recorded and inventory updated.');
    }
}
