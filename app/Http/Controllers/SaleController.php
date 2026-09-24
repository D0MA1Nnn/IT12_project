<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    // =========================================================
    // CASHIERING / CREATE SALE
    // =========================================================

    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | Load all active products for the Cashiering screen.
        |--------------------------------------------------------------------------
        |
        | We intentionally do NOT paginate here.
        |
        | The Cashiering page uses a scrollable product list and performs
        | search/category/stock filtering directly on the page.
        |
        | Each product appears only once. Its active ProductUnit records
        | are loaded so the cashier can choose the customer's selling unit.
        |
        */

        $products = Product::with([
            'category',
            'inventory',
            'productUnits' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->with('unit')
                    ->orderByDesc('is_base_unit');
            },
        ])
            ->where('is_active', true)
            ->whereHas('productUnits', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('product_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Categories for the Cashiering filter.
        |--------------------------------------------------------------------------
        */

        $categories = Category::where('is_active', true)
            ->orderBy('category_name')
            ->get();

        return view('sales.create', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }


    // =========================================================
    // SALES MANAGEMENT / SALES HISTORY
    // =========================================================

    public function index(Request $request)
    {
        $query = Sale::with([
            'user',
            'items.productUnit.product',
            'items.productUnit.unit',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        |
        | Allows searching by:
        |
        | SALE-1
        | 1
        | username
        |
        */

        if ($request->filled('q')) {
            $search = trim($request->q);

            $numeric = preg_replace('/\D/', '', $search);

            $query->where(function ($query) use ($search, $numeric) {
                if ($numeric !== '') {
                    $query->orWhere(
                        'sale_id',
                        (int) $numeric
                    );
                }

                $query->orWhereHas(
                    'user',
                    function ($userQuery) use ($search) {
                        $userQuery->where(
                            'username',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status filter
        |--------------------------------------------------------------------------
        |
        | PENDING
        | COMPLETED
        | CANCELLED
        |
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        return view('sales.index', [
            'sales' => $query
                ->latest('sale_date')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }


    // =========================================================
    // STORE SALE
    // =========================================================

    public function store(Request $request)
    {
        $data = $request->validate([
            'delivery_required' => [
                'nullable',
                'boolean',
            ],

            'payment' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_unit_id' => [
                'required',
                'exists:product_units,product_unit_id',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $deliveryRequired =
            (bool) ($data['delivery_required'] ?? false);


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | The transaction ensures that:
        |
        | - Sale
        | - Sale items
        | - Inventory deduction
        |
        | all succeed together.
        |
        | If anything fails, everything is rolled back.
        |
        */

        $sale = DB::transaction(
            function () use ($data, $deliveryRequired) {

                /*
                |--------------------------------------------------------------------------
                | Create sale header.
                |--------------------------------------------------------------------------
                |
                | Normal sale:
                |     COMPLETED
                |
                | Delivery sale:
                |     PENDING
                |
                */

                $sale = Sale::create([
                    'user_id' => auth()->id(),

                    'sale_date' => now(),

                    'total_amount' => 0,

                    'status' =>
                        $deliveryRequired
                            ? 'PENDING'
                            : 'COMPLETED',

                    'delivery_required' =>
                        $deliveryRequired,
                ]);


                $total = 0;


                /*
                |--------------------------------------------------------------------------
                | Keep track of base inventory used by each product.
                |--------------------------------------------------------------------------
                |
                | This is important when the SAME product is sold using
                | different selling units in one transaction.
                |
                | Example:
                |
                | Sand:
                |
                | 5 sacks
                | +
                | 1 cubic meter
                |
                | Both must consume the same Sand inventory record.
                |
                */

                $reservedByProduct = [];


                foreach ($data['items'] as $item) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock ProductUnit for transaction.
                    |--------------------------------------------------------------------------
                    */

                    $productUnit = ProductUnit::with([
                        'product.inventory',
                    ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $item['product_unit_id']
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Verify product and unit are still active.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$productUnit->is_active ||
                        !$productUnit->product ||
                        !$productUnit->product->is_active
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'One of the selected products or selling units is no longer active.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Conversion factor.
                    |--------------------------------------------------------------------------
                    |
                    | Example:
                    |
                    | Base Unit:
                    |     Cubic Meter
                    |
                    | Selling Unit:
                    |     Sack
                    |
                    | conversion_factor = amount of base inventory consumed
                    | by one selling unit.
                    |
                    */

                    $conversionFactor =
                        (float) $productUnit->conversion_factor;


                    if ($conversionFactor <= 0) {
                        throw ValidationException::withMessages([
                            'items' =>
                                "Invalid unit conversion configured for {$productUnit->product->product_name}.",
                        ]);
                    }


                    $quantity =
                        (float) $item['quantity'];


                    $neededBaseQuantity =
                        $quantity *
                        $conversionFactor;


                    $inventory =
                        $productUnit
                            ->product
                            ->inventory;


                    if (!$inventory) {
                        throw ValidationException::withMessages([
                            'items' =>
                                "No inventory record exists for {$productUnit->product->product_name}.",
                        ]);
                    }


                    $productId =
                        $productUnit
                            ->product
                            ->product_id;


                    /*
                    |--------------------------------------------------------------------------
                    | Total base stock already requested for this product
                    | in the current sale.
                    |--------------------------------------------------------------------------
                    */

                    $alreadyReserved =
                        $reservedByProduct[$productId]
                        ?? 0;


                    $totalRequired =
                        $alreadyReserved +
                        $neededBaseQuantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Validate inventory before creating the SaleItem.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (float) $inventory->quantity_on_hand <
                        $totalRequired
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                "Insufficient stock for {$productUnit->product->product_name}.",
                        ]);
                    }


                    $reservedByProduct[$productId] =
                        $totalRequired;


                    /*
                    |--------------------------------------------------------------------------
                    | Calculate subtotal.
                    |--------------------------------------------------------------------------
                    */

                    $subtotal = round(
                        $quantity *
                        (float) $productUnit->selling_price,
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create sale item.
                    |--------------------------------------------------------------------------
                    */

                    SaleItem::create([
                        'sale_id' =>
                            $sale->sale_id,

                        'product_unit_id' =>
                            $productUnit->product_unit_id,

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            $productUnit->selling_price,

                        'subtotal' =>
                            $subtotal,
                    ]);


                    $total +=
                        $subtotal;
                }


                /*
                |--------------------------------------------------------------------------
                | Validate payment BEFORE changing inventory.
                |--------------------------------------------------------------------------
                */

                if (
                    (float) $data['payment'] <
                    $total
                ) {
                    throw ValidationException::withMessages([
                        'payment' =>
                            'Customer payment is less than the total amount.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Deduct / reserve inventory.
                |--------------------------------------------------------------------------
                |
                | This happens for BOTH:
                |
                | COMPLETED sale
                | PENDING DELIVERY sale
                |
                | Therefore, a pending delivery cannot accidentally be
                | sold again to another customer.
                |
                */

                foreach (
                    $reservedByProduct
                    as $productId => $quantity
                ) {

                    $product = Product::with('inventory')
                        ->lockForUpdate()
                        ->findOrFail($productId);


                    $inventory =
                        $product->inventory;


                    if (
                        !$inventory ||
                        (float) $inventory->quantity_on_hand <
                        $quantity
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                "Insufficient stock for {$product->product_name}.",
                        ]);
                    }


                    $inventory->decrement(
                        'quantity_on_hand',
                        $quantity
                    );


                    $inventory->update([
                        'last_updated' => now(),
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Save final total.
                |--------------------------------------------------------------------------
                */

                $sale->update([
                    'total_amount' => $total,
                ]);


                return $sale;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'SALE',

            'action' =>
                'CREATE',

            'description' =>
                $deliveryRequired

                    ? "Recorded sale #{$sale->sale_id} as pending delivery."

                    : "Completed sale #{$sale->sale_id}.",

            'reference_type' =>
                'Sale',

            'reference_id' =>
                $sale->sale_id,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Redirect back to Cashiering.
        |--------------------------------------------------------------------------
        |
        | This clears the current cart and allows the Sales Clerk
        | to immediately process another customer.
        |
        */

        return redirect()
            ->route('sales.create')
            ->with(
                'success',

                $deliveryRequired

                    ? 'Sale recorded. Delivery is pending. You can now process the next customer.'

                    : 'Sale completed successfully. You can now process the next customer.'
            );
    }


    // =========================================================
    // COMPLETE DELIVERY
    // =========================================================

    public function completeDelivery(Sale $sale)
    {
        /*
        |--------------------------------------------------------------------------
        | Only pending delivery transactions can be completed.
        |--------------------------------------------------------------------------
        */

        if (
            !$sale->delivery_required ||
            $sale->status !== 'PENDING'
        ) {
            return back()->with(
                'error',
                'Only pending delivery orders can be marked completed.'
            );
        }


        $sale->update([
            'status' => 'COMPLETED',
        ]);


        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'SALE',

            'action' =>
                'UPDATE',

            'description' =>
                "Marked delivery for sale #{$sale->sale_id} as completed.",

            'reference_type' =>
                'Sale',

            'reference_id' =>
                $sale->sale_id,
        ]);


        return back()->with(
            'success',
            'Delivery marked as completed.'
        );
    }


    // =========================================================
    // CANCEL DELIVERY
    // =========================================================

    public function cancelDelivery(Sale $sale)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Pending Delivery orders can be cancelled.
        |--------------------------------------------------------------------------
        */

        if (
            !$sale->delivery_required ||
            $sale->status !== 'PENDING'
        ) {
            return back()->with(
                'error',
                'Only pending delivery orders can be cancelled.'
            );
        }


        DB::transaction(
            function () use ($sale) {

                /*
                |--------------------------------------------------------------------------
                | Lock the sale before changing its status.
                |--------------------------------------------------------------------------
                */

                $lockedSale = Sale::where(
                    'sale_id',
                    $sale->sale_id
                )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Re-check status after acquiring lock.
                |--------------------------------------------------------------------------
                |
                | Prevents stock from being returned twice if two requests
                | attempt to cancel the same delivery.
                |
                */

                if (
                    !$lockedSale->delivery_required ||
                    $lockedSale->status !== 'PENDING'
                ) {
                    throw ValidationException::withMessages([
                        'sale' =>
                            'This delivery is no longer pending.',
                    ]);
                }


                $lockedSale->load([
                    'items.productUnit.product.inventory',
                ]);


                /*
                |--------------------------------------------------------------------------
                | Group quantities by product.
                |--------------------------------------------------------------------------
                |
                | Necessary because one product may have been sold using
                | multiple selling units.
                |
                */

                $returnByProduct = [];


                foreach (
                    $lockedSale->items
                    as $item
                ) {

                    $productUnit =
                        $item->productUnit;


                    if (
                        !$productUnit ||
                        !$productUnit->product
                    ) {
                        continue;
                    }


                    $productId =
                        $productUnit
                            ->product
                            ->product_id;


                    $returnQuantity =
                        (float) $item->quantity *
                        (float) $productUnit->conversion_factor;


                    if (
                        !isset(
                            $returnByProduct[$productId]
                        )
                    ) {
                        $returnByProduct[$productId] = 0;
                    }


                    $returnByProduct[$productId] +=
                        $returnQuantity;
                }


                /*
                |--------------------------------------------------------------------------
                | Return reserved stock.
                |--------------------------------------------------------------------------
                */

                foreach (
                    $returnByProduct
                    as $productId => $quantity
                ) {

                    $product = Product::with('inventory')
                        ->lockForUpdate()
                        ->find($productId);


                    if (
                        !$product ||
                        !$product->inventory
                    ) {
                        continue;
                    }


                    $product
                        ->inventory
                        ->increment(
                            'quantity_on_hand',
                            $quantity
                        );


                    $product
                        ->inventory
                        ->update([
                            'last_updated' =>
                                now(),
                        ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Mark delivery cancelled.
                |--------------------------------------------------------------------------
                */

                $lockedSale->update([
                    'status' => 'CANCELLED',
                ]);
            }
        );


        ActivityLog::create([
            'user_id' =>
                auth()->id(),

            'module' =>
                'SALE',

            'action' =>
                'UPDATE',

            'description' =>
                "Cancelled delivery for sale #{$sale->sale_id} and returned reserved stock to inventory.",

            'reference_type' =>
                'Sale',

            'reference_id' =>
                $sale->sale_id,
        ]);


        return back()->with(
            'success',
            'Delivery cancelled and reserved stock returned to inventory.'
        );
    }
}