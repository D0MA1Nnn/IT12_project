<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\DatabaseBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SaleController extends Controller
{
    public function __construct(private DatabaseBackupService $databaseBackupService) {}

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
            ->whereHas('inventory', function ($query) {
                $query->where('quantity_on_hand', '>', 0);
            })
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

        $completedSale = null;

        if (session()->has('completed_sale_id')) {
            $completedSale = Sale::with([
                'user',
                'items.productUnit.product',
                'items.productUnit.unit',
            ])->find(session('completed_sale_id'));
        }

        return view('sales.create', [
            'products' => $products,
            'productGroups' => Product::groupForDisplay($products),
            'categories' => $categories,
            'completedSale' => $completedSale,
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
        ])
            ->where('delivery_required', true)
            ->where('status', 'PENDING');

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

        return view('sales.index', [
            'sales' => $query
                ->latest('sale_date')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    // =========================================================
    // REPORT
    // =========================================================

    public function report(Request $request): View
    {
        $filters = $request->validate([
            'report_type' => ['nullable', 'string', Rule::in(['sales', 'purchases'])],
            'print' => ['nullable', 'boolean'],
            'q' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\s\-_@.]+$/',
            ],

            'from' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
                'after_or_equal:from',
            ],
        ], [
            'report_type.in' => 'Please select Sales List or Purchases List.',
            'q.max' => 'Search must not be longer than 100 characters.',
            'q.regex' => 'Search can only contain letters, numbers, spaces, dash, underscore, @, and dot.',
            'from.date_format' => 'From date must be a valid date.',
            'from.before_or_equal' => 'From date cannot be in the future.',
            'to.date_format' => 'To date must be a valid date.',
            'to.before_or_equal' => 'To date cannot be in the future.',
            'to.after_or_equal' => 'To date cannot be earlier than the From date.',
        ]);

        $reportType = $filters['report_type'] ?? 'sales';
        $isPurchaseReport = $reportType === 'purchases';
        abort_if($isPurchaseReport && $request->user()->role !== 'OWNER', 403);

        $dateColumn = $isPurchaseReport ? 'purchase_date' : 'sale_date';
        $referenceColumn = $isPurchaseReport ? 'purchase_id' : 'sale_id';
        $query = $isPurchaseReport
            ? Purchase::with(['supplier', 'user'])
            : Sale::with('user');
        $query->withCount('items')->where('status', 'COMPLETED');

        if (! empty($filters['from'])) {
            $query->whereDate(
                $dateColumn,
                '>=',
                $filters['from']
            );
        }

        if (! empty($filters['to'])) {
            $query->whereDate(
                $dateColumn,
                '<=',
                $filters['to']
            );
        }

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {

            $numeric = preg_replace('/\D/', '', $search);

            $query->where(function (Builder $query) use ($search, $numeric, $referenceColumn, $isPurchaseReport): void {
                if ($numeric !== '') {
                    $query->orWhere(
                        $referenceColumn,
                        (int) $numeric
                    );
                }

                $query->orWhereHas(
                    'user',
                    function (Builder $userQuery) use ($search): void {
                        $userQuery->where(
                            'username',
                            'like',
                            "%{$search}%"
                        );
                    }
                );

                if ($isPurchaseReport) {
                    $query->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                        $supplierQuery->where('supplier_name', 'like', "%{$search}%");
                    });
                }
            });
        }

        $summaryQuery = clone $query;

        $totalRecords = (clone $summaryQuery)->count();
        $totalAmount = (float) (clone $summaryQuery)->sum('total_amount');

        $isPrintReport = (bool) ($filters['print'] ?? false);
        $query->with(['items.productUnit.product', 'items.productUnit.unit'])
            ->latest($dateColumn)->orderByDesc($referenceColumn);

        return view($isPrintReport ? 'sales.report-print' : 'sales.report', [
            'records' => $isPrintReport ? $query->lazy(200) : $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'reportType' => $reportType,
            'isPurchaseReport' => $isPurchaseReport,
            'averageAmount' => $totalRecords > 0 ? $totalAmount / $totalRecords : 0,
            'deliverySales' => $isPurchaseReport ? 0 : (clone $summaryQuery)->where('delivery_required', true)->count(),
            'totalAmount' => $totalAmount,
            'totalRecords' => $totalRecords,
            'walkInSales' => $isPurchaseReport ? 0 : (clone $summaryQuery)->where('delivery_required', false)->count(),
        ]);
    }

    // =========================================================
    // STORE SALE
    // =========================================================

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => [
                'nullable',
                Rule::in(['PAY_NOW', 'COD']),
            ],
            'delivery_required' => [
                'nullable',
                'boolean',
            ],

            'customer_name' => [
                'required_if:delivery_required,1',
                'nullable',
                'string',
                'max:30',
            ],

            'customer_contact_number' => [
                'required_if:delivery_required,1',
                'nullable',
                'string',
                'regex:/\A09[0-9]{9}\z/',
            ],

            'delivery_address' => [
                'required_if:delivery_required,1',
                'nullable',
                'string',
                'max:60',
            ],

            'delivery_fee' => [
                'required_if:delivery_required,1',
                'nullable',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            'payment' => [
                'required_unless:payment_method,COD',
                'nullable',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
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

            'items.*' => ['array:product_unit_id,quantity'],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,6',
            ],
        ], [
            'customer_contact_number.regex' => 'Customer contact number must start with 09 and contain exactly 11 digits.',
            'customer_name.max' => 'Customer name must not be longer than 30 characters.',
            'delivery_address.max' => 'Address must not be longer than 60 characters.',
            'delivery_fee.required_if' => 'Delivery fee is required when delivery is selected.',
            'delivery_fee.numeric' => 'Delivery fee must be a number.',
            'delivery_fee.regex' => 'Delivery fee can only have up to 2 decimal places.',
            'payment.numeric' => 'Customer payment must be a number.',
            'payment.regex' => 'Customer payment can only have up to 2 decimal places.',
            'items.*.quantity.numeric' => 'Item quantity must be a number.',
            'items.*.quantity.decimal' => 'Item quantity can only have up to 6 decimal places.',
            'from.date_format' => 'Date must use the correct date format.',
            'to.date_format' => 'Date must use the correct date format.',
        ]);

        $deliveryRequired =
            (bool) ($data['delivery_required'] ?? false);

        $paymentMethod = $data['payment_method'] ?? 'PAY_NOW';
        $cashOnDelivery = $paymentMethod === 'COD';
        $payment = (float) ($data['payment'] ?? 0);

        if ($cashOnDelivery && ! $deliveryRequired) {
            throw ValidationException::withMessages([
                'delivery_required' => 'Cash on delivery requires a delivery order.',
            ]);
        }

        if ($cashOnDelivery && $payment !== 0.0) {
            throw ValidationException::withMessages([
                'payment' => 'For cash on delivery, record the payment after the customer receives the products.',
            ]);
        }

        $deliveryFee = $deliveryRequired
            ? (float) ($data['delivery_fee'] ?? 0)
            : 0.0;

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
            function () use ($data, $deliveryRequired, $deliveryFee, $paymentMethod, $cashOnDelivery, $payment) {

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

                    'status' => $deliveryRequired
                            ? 'PENDING'
                            : 'COMPLETED',

                    'delivery_required' => $deliveryRequired,

                    'delivery_fee' => $deliveryFee,

                    'payment_method' => $paymentMethod,
                    'payment_status' => $cashOnDelivery ? 'UNPAID' : 'PAID',
                    'payment_received' => $payment,
                    'paid_at' => $cashOnDelivery ? null : now(),
                    'delivery_status' => $deliveryRequired ? 'PENDING' : 'NOT_REQUIRED',

                    'customer_name' => $deliveryRequired
                            ? trim((string) $data['customer_name'])
                            : null,

                    'customer_contact_number' => $deliveryRequired
                            ? trim((string) $data['customer_contact_number'])
                            : null,

                    'delivery_address' => $deliveryRequired
                            ? trim((string) $data['delivery_address'])
                            : null,
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
                $normalizedItemsByProduct = [];

                foreach ($data['items'] as $item) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock ProductUnit for transaction.
                    |--------------------------------------------------------------------------
                    */

                    $productUnit = ProductUnit::with([
                        'unit',
                        'product.inventory',
                        'product.productUnits.unit',
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
                        ! $productUnit->is_active ||
                        ! $productUnit->product ||
                        ! $productUnit->product->is_active
                    ) {
                        throw ValidationException::withMessages([
                            'items' => 'One of the selected products or selling units is no longer active.',
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
                            'items' => "Invalid unit conversion configured for {$productUnit->product->product_name}.",
                        ]);
                    }

                    $baseProductUnit = $productUnit->product->productUnits
                        ->where('is_active', true)
                        ->firstWhere('is_base_unit', true);

                    if (! $baseProductUnit || (float) $baseProductUnit->conversion_factor !== 1.0) {
                        throw ValidationException::withMessages([
                            'items' => "An active base unit with a conversion of 1 is required for {$productUnit->product->product_name}.",
                        ]);
                    }

                    $quantity =
                        (float) $item['quantity'];

                    $neededBaseQuantity = round($quantity * $conversionFactor, 8);

                    if ($neededBaseQuantity <= 0) {
                        throw ValidationException::withMessages([
                            'items' => 'The quantity is too small for the configured unit conversion.',
                        ]);
                    }

                    $inventory =
                        $productUnit
                            ->product
                            ->inventory;

                    if (! $inventory) {
                        throw ValidationException::withMessages([
                            'items' => "No inventory record exists for {$productUnit->product->product_name}.",
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

                    $totalRequired = round($alreadyReserved + $neededBaseQuantity, 8);

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
                            'items' => "Insufficient stock for {$productUnit->product->product_name}.",
                        ]);
                    }

                    $reservedByProduct[$productId] =
                        $totalRequired;

                    $normalizedItemsByProduct[$productId] = [
                        'product_unit' => $baseProductUnit,
                        'quantity' => $totalRequired,
                        'selling_unit' => $productUnit,
                        'selling_quantity' => ($normalizedItemsByProduct[$productId]['selling_quantity'] ?? 0) + $quantity,
                        'mixed_units' => ($normalizedItemsByProduct[$productId]['mixed_units'] ?? false)
                            || (isset($normalizedItemsByProduct[$productId])
                                && $normalizedItemsByProduct[$productId]['selling_unit']->product_unit_id !== $productUnit->product_unit_id),
                    ];
                }

                foreach ($normalizedItemsByProduct as $normalizedItem) {
                    $baseProductUnit = $normalizedItem['product_unit'];
                    $quantity = $normalizedItem['quantity'];
                    $sellingUnit = $normalizedItem['mixed_units'] ? $baseProductUnit : $normalizedItem['selling_unit'];
                    $sellingQuantity = $normalizedItem['mixed_units'] ? $quantity : $normalizedItem['selling_quantity'];
                    $sellingPrice = round((float) $baseProductUnit->selling_price * (float) $sellingUnit->conversion_factor, 2);

                    /*
                    |--------------------------------------------------------------------------
                    | Calculate subtotal.
                    |--------------------------------------------------------------------------
                    */

                    $subtotal = round(
                        $sellingQuantity * $sellingPrice,
                        2
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Create sale item.
                    |--------------------------------------------------------------------------
                    */

                    SaleItem::create([
                        'sale_id' => $sale->sale_id,

                        'product_unit_id' => $baseProductUnit->product_unit_id,

                        'quantity' => $quantity,

                        'unit_price' => $baseProductUnit->selling_price,

                        'subtotal' => $subtotal,
                        'selling_details' => [
                            'quantity' => round($sellingQuantity, 8),
                            'unit' => $sellingUnit->unit?->unit_name ?? 'Unit',
                            'unit_price' => $sellingPrice,
                            'product_unit_id' => $sellingUnit->product_unit_id,
                            'conversion_factor' => (float) $sellingUnit->conversion_factor,
                            'base_quantity' => $quantity,
                        ],
                    ]);

                    $total +=
                        $subtotal;
                }

                /*
                |--------------------------------------------------------------------------
                | Validate payment BEFORE changing inventory.
                |--------------------------------------------------------------------------
                */

                $finalTotal = round($total + $deliveryFee, 2);

                if (
                    ! $cashOnDelivery && $payment <
                    $finalTotal
                ) {
                    throw ValidationException::withMessages([
                        'payment' => 'Customer payment is less than the total amount.',
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
                | Therefore, a for delivery sale cannot accidentally be
                | sold again to another customer.
                |
                */

                foreach (
                    $reservedByProduct as $productId => $quantity
                ) {

                    $product = Product::with('inventory')
                        ->lockForUpdate()
                        ->findOrFail($productId);

                    $inventory =
                        $product->inventory;

                    if (
                        ! $inventory ||
                        (float) $inventory->quantity_on_hand <
                        $quantity
                    ) {
                        throw ValidationException::withMessages([
                            'items' => "Insufficient stock for {$product->product_name}.",
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
                    'total_amount' => $finalTotal,
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
            'user_id' => auth()->id(),

            'module' => 'SALE',

            'action' => 'CREATE',

            'description' => $deliveryRequired

                    ? "Recorded sale #{$sale->sale_id} as for delivery."

                    : "Completed sale #{$sale->sale_id}.",

            'reference_type' => 'Sale',

            'reference_id' => $sale->sale_id,
        ]);

        $onlineBackupSaved = $sale->status === 'COMPLETED'
            && $this->saveOnlineBackupAfterCompletedTransaction($sale->sale_id);

        $successMessage = $deliveryRequired
            ? ($cashOnDelivery
                ? 'COD order recorded. Stock is reserved; payment will be collected after delivery.'
                : 'Sale recorded. Delivery is pending. You can now process the next customer.')
            : 'Sale completed successfully. You can now process the next customer.';

        if ($onlineBackupSaved) {
            $successMessage .= ' Online backup saved.';
        }

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
            ->with('completed_sale_id', $sale->sale_id)
            ->with('receipt_payment', $sale->receivedPayment())
            ->with('receipt_change', $sale->paymentChange())
            ->with('success', $successMessage);
    }

    // =========================================================
    // COMPLETE DELIVERY
    // =========================================================

    public function completeDelivery(Sale $sale): RedirectResponse
    {
        $sale = DB::transaction(function () use ($sale): Sale {
            $lockedSale = Sale::where('sale_id', $sale->sale_id)->lockForUpdate()->firstOrFail();

            if (! $lockedSale->delivery_required || $lockedSale->status !== 'PENDING' || $lockedSale->delivery_status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'sale' => 'Only pending deliveries can be marked delivered.',
                ]);
            }

            $updates = [
                'delivery_status' => 'DELIVERED',
                'status' => $lockedSale->payment_status === 'PAID' ? 'COMPLETED' : 'PENDING',
            ];

            $collectCodPayment = $lockedSale->payment_method === 'COD' && $lockedSale->payment_status === 'UNPAID';

            if ($collectCodPayment) {
                $updates['payment_received'] = $lockedSale->total_amount;
                $updates['payment_status'] = 'PAID';
                $updates['paid_at'] = now();
                $updates['status'] = 'COMPLETED';
            }

            $lockedSale->update($updates);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'module' => 'SALE',
                'action' => 'UPDATE',
                'description' => $collectCodPayment
                    ? "Confirmed delivery and collected full COD payment including the delivery fee for sale #{$lockedSale->sale_id}."
                    : "Marked delivery for sale #{$lockedSale->sale_id} as delivered successfully.",
                'reference_type' => 'Sale',
                'reference_id' => $lockedSale->sale_id,
            ]);

            return $lockedSale;
        });

        $onlineBackupSaved = $sale->status === 'COMPLETED'
            && $this->saveOnlineBackupAfterCompletedTransaction($sale->sale_id);

        $message = $sale->payment_method === 'COD'
            ? 'Delivery completed. Full COD payment, including the delivery fee, is recorded as paid.'
            : 'Delivery marked as delivered successfully.';

        $redirect = $sale->payment_method === 'COD'
            ? redirect()->route('sales.create')->with('completed_sale_id', $sale->sale_id)
            : back();

        return $redirect->with(
            'success',
            $message.($onlineBackupSaved ? ' Online backup saved.' : '')
        );
    }

    public function collectPayment(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'payment' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
        ], [
            'payment.regex' => 'Payment can only have up to 2 decimal places.',
        ]);

        $sale = DB::transaction(function () use ($sale, $data): Sale {
            $lockedSale = Sale::where('sale_id', $sale->sale_id)->lockForUpdate()->firstOrFail();

            if ($lockedSale->payment_method !== 'COD' || $lockedSale->payment_status !== 'UNPAID'
                || $lockedSale->delivery_status !== 'DELIVERED' || $lockedSale->status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'sale' => 'Payment can only be recorded for delivered, unpaid COD orders.',
                ]);
            }

            $payment = (float) ($data['payment'] ?? $lockedSale->total_amount);

            if ($payment < (float) $lockedSale->total_amount) {
                throw ValidationException::withMessages([
                    'payment' => 'The amount collected is less than the total amount due.',
                ]);
            }

            $lockedSale->update([
                'payment_received' => $payment,
                'payment_status' => 'PAID',
                'paid_at' => now(),
                'status' => 'COMPLETED',
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(), 'module' => 'SALE', 'action' => 'UPDATE',
                'description' => "Collected COD payment for sale #{$lockedSale->sale_id}.",
                'reference_type' => 'Sale', 'reference_id' => $lockedSale->sale_id,
            ]);

            return $lockedSale;
        });

        $onlineBackupSaved = $this->saveOnlineBackupAfterCompletedTransaction($sale->sale_id);

        return redirect()->route('sales.create')
            ->with('completed_sale_id', $sale->sale_id)
            ->with('success', 'COD payment recorded. The order is delivered and paid.'.($onlineBackupSaved ? ' Online backup saved.' : ''));
    }

    public function receipt(Sale $sale): RedirectResponse
    {
        return redirect()->route('sales.create')->with('completed_sale_id', $sale->sale_id);
    }

    private function saveOnlineBackupAfterCompletedTransaction(int $saleId): bool
    {
        if (! $this->databaseBackupService->onlineBackupConfigured()) {
            return false;
        }

        try {
            [, $fileName] = $this->databaseBackupService->saveLatestOnlineBackup();
        } catch (RuntimeException $exception) {
            report($exception);

            return false;
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'BACKUP',
            'action' => 'CREATE',
            'description' => "Auto-saved online backup {$fileName} after completed sale #{$saleId}.",
            'reference_type' => 'Sale',
            'reference_id' => $saleId,
        ]);

        return true;
    }

    // =========================================================
    // CANCEL DELIVERY
    // =========================================================

    public function cancelDelivery(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'delivery_cancel_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Only for delivery orders can be cancelled.
        |--------------------------------------------------------------------------
        */

        if (
            ! $sale->delivery_required ||
            $sale->status !== 'PENDING' ||
            $sale->delivery_status !== 'PENDING'
        ) {
            return back()->with(
                'error',
                'Only for delivery orders can be cancelled.'
            );
        }

        DB::transaction(
            function () use ($sale, $data) {

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
                    ! $lockedSale->delivery_required ||
                    $lockedSale->status !== 'PENDING' ||
                    $lockedSale->delivery_status !== 'PENDING'
                ) {
                    throw ValidationException::withMessages([
                        'sale' => 'This delivery is no longer for delivery.',
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
                    $lockedSale->items as $item
                ) {

                    $productUnit =
                        $item->productUnit;

                    if (
                        ! $productUnit ||
                        ! $productUnit->product
                    ) {
                        continue;
                    }

                    $productId =
                        $productUnit
                            ->product
                            ->product_id;

                    $returnQuantity = $item->baseStockQuantity();

                    if (
                        ! isset(
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
                    $returnByProduct as $productId => $quantity
                ) {

                    $product = Product::with('inventory')
                        ->lockForUpdate()
                        ->find($productId);

                    if (
                        ! $product ||
                        ! $product->inventory
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
                            'last_updated' => now(),
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Mark delivery cancelled.
                |--------------------------------------------------------------------------
                */

                $lockedSale->update([
                    'status' => 'CANCELLED',
                    'delivery_status' => 'CANCELLED',
                    'delivery_cancel_reason' => trim((string) $data['delivery_cancel_reason']),
                ]);
            }
        );

        ActivityLog::create([
            'user_id' => auth()->id(),

            'module' => 'SALE',

            'action' => 'UPDATE',

            'description' => "Cancelled delivery for sale #{$sale->sale_id} and returned reserved stock to inventory. Reason: ".trim((string) $data['delivery_cancel_reason']),

            'reference_type' => 'Sale',

            'reference_id' => $sale->sale_id,
        ]);

        return back()->with(
            'success',
            'Delivery cancelled and all products were returned to inventory.'
        );
    }
}
