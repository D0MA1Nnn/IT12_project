@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- =========================================================
     PAGE HEADER
========================================================= --}}
<div class="top">
    <div>
            <h1 class="legacy-dashboard-heading">Owner Dashboard</h1>
        <div class="muted">
            {{ now()->format('l, M d, Y') }}
        </div>
    </div>
</div>


{{-- =========================================================
     MAIN SUMMARY CARDS
========================================================= --}}
<div class="owner-summary-grid">

    {{-- TODAY'S SALES --}}
    <a
        href="{{ route('sales.report') }}"
        class="owner-stat-card"
    >
        <div class="owner-stat-header">
            <div class="owner-stat-icon green">
                ₱
            </div>

            <span>Today's Sales</span>
        </div>

        <div class="owner-stat-value">
            ₱{{ number_format((float) $todaySalesAmount, 2) }}
        </div>

        <div class="owner-stat-footer">
            {{ number_format($todaySalesCount) }}
            {{ $todaySalesCount == 1 ? 'transaction' : 'transactions' }}
            today
        </div>
    </a>


    {{-- TODAY'S PURCHASES --}}
    <a
        href="{{ route('purchases.index') }}"
        class="owner-stat-card"
    >
        <div class="owner-stat-header">
            <div class="owner-stat-icon orange">
                ↓
            </div>

            <span>Today's Purchases</span>
        </div>

        <div class="owner-stat-value">
            ₱{{ number_format((float) $todayPurchaseAmount, 2) }}
        </div>

        <div class="owner-stat-footer">
            {{ number_format($todayPurchaseCount) }}
            {{ $todayPurchaseCount == 1 ? 'record' : 'records' }}
            today
        </div>
    </a>


    {{-- LOW STOCK --}}
    <a
        href="{{ route('inventory.index', ['stock' => 'low_stock']) }}"
        class="owner-stat-card"
    >
        <div class="owner-stat-header">
            <div class="owner-stat-icon red">
                !
            </div>

            <span>Low Stock</span>
        </div>

        <div class="owner-stat-value {{ $lowStockCount > 0 ? 'text-danger' : '' }}">
            {{ number_format($lowStockCount) }}
        </div>

        <div class="owner-stat-footer">
            Items requiring attention
        </div>
    </a>


    {{-- PENDING DELIVERIES --}}
    <a
        href="{{ route('sales.index') }}"
        class="owner-stat-card"
    >
        <div class="owner-stat-header">
            <div class="owner-stat-icon orange">
                D
            </div>

            <span>Pending Deliveries</span>
        </div>

        <div class="owner-stat-value {{ $pendingDeliveryCount > 0 ? 'text-warning' : '' }}">
            {{ number_format($pendingDeliveryCount) }}
        </div>

        <div class="owner-stat-footer">
            Orders waiting for delivery
        </div>
    </a>

</div>


{{-- =========================================================
     SECONDARY SUMMARY
========================================================= --}}
<div class="owner-secondary-grid">

    <a href="{{ route('products.index') }}" class="owner-mini-card">
        <div>
            <span>Products</span>
            <small>Active construction materials</small>
        </div>

        <strong>{{ number_format($productCount) }}</strong>
    </a>


    <a href="{{ route('categories.index') }}" class="owner-mini-card">
        <div>
            <span>Categories</span>
            <small>Material classifications</small>
        </div>

        <strong>{{ number_format($categoryCount) }}</strong>
    </a>


    <a href="{{ route('suppliers.index') }}" class="owner-mini-card">
        <div>
            <span>Suppliers</span>
            <small>Active supplier records</small>
        </div>

        <strong>{{ number_format($supplierCount) }}</strong>
    </a>


    <a href="{{ route('users.index') }}" class="owner-mini-card">
        <div>
            <span>Authorized Users</span>
            <small>Active system accounts</small>
        </div>

        <strong>{{ number_format($userCount) }}</strong>
    </a>

</div>

{{-- =========================================================
     OPERATION SNAPSHOT
========================================================= --}}
<div class="owner-dashboard-grid">

    <div class="owner-section">

        <div class="owner-section-header">
            <div>
                <h2>Needs Attention</h2>

                <p>
                    Low-stock materials based on reorder levels
                </p>
            </div>

            <a href="{{ route('inventory.index', ['stock' => 'low_stock']) }}" class="owner-section-link">
                View inventory
            </a>
        </div>

        <div class="owner-table-wrapper owner-compact-list">
            <table class="table owner-dashboard-table">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Available</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($lowStockItems as $item)
                        @php
                            $baseUnit =
                                $item->product?->productUnits
                                    ->firstWhere('is_base_unit', true)
                                ?? $item->product?->productUnits->first();
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $item->product?->product_name ?? 'Unknown product' }}</strong>
                                <small>{{ $baseUnit?->unit?->unit_name ?? 'unit' }}</small>
                            </td>
                            <td>
                                <span class="{{ (float) $item->quantity_on_hand <= 0 ? 'owner-status out' : 'owner-status low-stock' }}">
                                    {{ number_format((float) $item->quantity_on_hand, 0) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="owner-empty">
                                Inventory levels look good.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>


    <div class="owner-section">

        <div class="owner-section-header">
            <div>
                <h2>Recent Movement</h2>

                <p>
                    Latest sales and purchases
                </p>
            </div>
        </div>

        <div class="owner-transaction-grid">

            <div class="owner-transaction-column">
                <div class="owner-sub-header">
                    <h3>Sales</h3>
                    <span>{{ number_format($saleCount) }} total records</span>
                </div>

                @forelse($recentSales as $sale)
                    <div class="owner-transaction-row">
                        <div class="owner-transaction-info">
                            <strong>SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</strong>
                            <span>{{ $sale->sale_date->format('M d, g:i A') }}</span>
                            <small>{{ $sale->user?->username ?? 'Unknown user' }}</small>
                        </div>

                        <div class="owner-transaction-amount">
                            ₱{{ number_format((float) $sale->total_amount, 2) }}
                        </div>
                    </div>
                @empty
                    <div class="owner-transaction-empty">
                        No sales recorded yet.
                    </div>
                @endforelse
            </div>


            <div class="owner-transaction-column">
                <div class="owner-sub-header">
                    <h3>Purchases</h3>
                    <span>{{ number_format($purchaseCount) }} total records</span>
                </div>

                @forelse($recentPurchases as $purchase)
                    <div class="owner-transaction-row">
                        <div class="owner-transaction-info">
                            <strong>PUR-{{ str_pad($purchase->purchase_id, 4, '0', STR_PAD_LEFT) }}</strong>
                            <span>{{ $purchase->purchase_date->format('M d, g:i A') }}</span>
                            <small>{{ $purchase->supplier?->supplier_name ?? 'Unknown supplier' }}</small>
                        </div>

                        <div class="owner-transaction-amount">
                            ₱{{ number_format((float) $purchase->total_amount, 2) }}
                        </div>
                    </div>
                @empty
                    <div class="owner-transaction-empty">
                        No purchases recorded yet.
                    </div>
                @endforelse
            </div>

        </div>

    </div>

</div>

<style>

/* =========================================================
   OWNER DASHBOARD
========================================================= */

.owner-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}


/* =========================================================
   SUMMARY CARDS
========================================================= */

.owner-stat-card {
    min-height: 145px;
    display: flex;
    flex-direction: column;

    padding: 20px;

    border: 1px solid #e8edf5;
    border-radius: 12px;

    background: #ffffff;
    color: #182033;

    text-decoration: none;

    transition:
        transform .15s ease,
        box-shadow .15s ease,
        border-color .15s ease;
}


.owner-stat-card:hover {
    transform: translateY(-2px);

    border-color: #d8e1ed;

    box-shadow:
        0 10px 30px
        rgba(15, 23, 42, .06);
}


.owner-stat-header {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 18px;

    color: #69758b;

    font-size: 12px;
    font-weight: 700;
}


.owner-stat-icon,
.owner-quick-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    font-weight: 800;
}


.owner-stat-icon {
    width: 30px;
    height: 30px;

    border-radius: 8px;

    font-size: 12px;
}


.owner-stat-value {
    margin-bottom: 7px;

    font-size: 25px;
    font-weight: 800;

    line-height: 1.15;
}


.owner-stat-footer {
    color: #7b879d;

    font-size: 11px;
}


.text-danger {
    color: #dc3545;
}


.text-warning {
    color: #d97706;
}


/* =========================================================
   ICON COLORS
========================================================= */

.owner-stat-icon.blue,
.owner-quick-icon.blue {
    background: #edf4ff;
    color: #2468ee;
}


.owner-stat-icon.red {
    background: #fff0f1;
    color: #dc3545;
}


.owner-stat-icon.orange,
.owner-quick-icon.orange {
    background: #fff7e8;
    color: #d97706;
}


.owner-stat-icon.green,
.owner-quick-icon.green {
    background: #ecfdf3;
    color: #12a957;
}


.owner-quick-icon.dark {
    background: #eef2f8;
    color: #46536a;
}


/* =========================================================
   SECONDARY CARDS
========================================================= */

.owner-secondary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));

    gap: 16px;

    margin-bottom: 22px;
}


.owner-dashboard-grid {
    display: grid;
    grid-template-columns: minmax(380px, .9fr) minmax(0, 1.6fr);
    gap: 16px;
    margin-bottom: 22px;
}


.owner-mini-card {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 16px 18px;

    border: 1px solid #e8edf5;
    border-radius: 10px;

    background: #ffffff;
    color: #182033;

    text-decoration: none;

    transition:
        border-color .15s ease,
        box-shadow .15s ease;
}


.owner-mini-card:hover {
    border-color: #ccd8e8;

    box-shadow:
        0 6px 18px
        rgba(15, 23, 42, .04);
}


.owner-mini-card span {
    display: block;

    margin-bottom: 4px;

    font-size: 12px;
    font-weight: 700;
}


.owner-mini-card small {
    display: block;

    color: #7b879d;

    font-size: 10px;
}


.owner-mini-card strong {
    font-size: 21px;
}


/* =========================================================
   SECTIONS
========================================================= */

.owner-section {
    margin-bottom: 22px;

    border: 1px solid #e8edf5;
    border-radius: 12px;

    background: #ffffff;

    overflow: hidden;
}


.owner-dashboard-grid .owner-section {
    margin-bottom: 0;
}


.owner-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    padding: 19px 22px;

    border-bottom: 1px solid #edf0f5;
}


.owner-section-header h2 {
    margin: 0 0 4px;

    font-size: 17px;
}


.owner-section-header p {
    margin: 0;

    color: #7b879d;

    font-size: 11px;
}


.owner-section-link {
    color: #2468ee;

    font-size: 11px;
    font-weight: 700;

    text-decoration: none;

    white-space: nowrap;
}


.owner-section-link:hover {
    text-decoration: underline;
}


/* =========================================================
   TABLE
========================================================= */

.owner-table-wrapper {
    width: 100%;

    overflow-x: auto;
}


.owner-compact-list {
    max-height: 355px;
    overflow: auto;
}


.owner-dashboard-table {
    min-width: 650px;

    margin: 0;
}


.owner-dashboard-table th,
.owner-dashboard-table td {
    vertical-align: middle;
}


.owner-dashboard-table td strong {
    font-weight: 700;
}


.owner-dashboard-table td small {
    display: block;
    margin-top: 3px;
    color: #7b879d;
    font-size: 10px;
}


.owner-empty {
    padding: 32px !important;

    text-align: center;

    color: #7b879d;
}


/* =========================================================
   INVENTORY STATUS
========================================================= */

.owner-status {
    display: inline-flex;

    align-items: center;

    font-size: 11px;
    font-weight: 600;
}


.owner-status.low-stock {
    color: #dc3545;
}


.owner-status.out {
    color: #b42318;

    font-weight: 700;
}


/* =========================================================
   TRANSACTION SECTION
========================================================= */

.owner-transaction-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    min-height: 355px;
}


.owner-transaction-column {
    padding: 20px 22px;
}


.owner-transaction-column:first-child {
    border-right: 1px solid #edf0f5;
}


.owner-sub-header {
    margin-bottom: 7px;
}


.owner-sub-header h3 {
    margin: 0 0 4px;

    font-size: 14px;
}


.owner-sub-header span {
    color: #7b879d;

    font-size: 10px;
}


.owner-transaction-row {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    min-height: 68px;

    padding: 12px 0;

    border-bottom: 1px solid #edf0f5;
}


.owner-transaction-row:last-child {
    border-bottom: 0;
}


.owner-transaction-info {
    min-width: 0;
}


.owner-transaction-info strong {
    display: block;

    margin-bottom: 4px;

    font-size: 12px;
}


.owner-transaction-info span,
.owner-transaction-info small {
    display: block;

    color: #7b879d;

    font-size: 10px;

    line-height: 1.5;
}


.owner-transaction-amount {
    flex-shrink: 0;

    font-size: 12px;
    font-weight: 800;

    white-space: nowrap;
}


.owner-transaction-empty {
    padding: 25px 0;

    color: #7b879d;

    font-size: 11px;
}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.owner-quick-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 12px;

    padding: 20px 22px;
}


.owner-quick-action {
    display: flex;
    align-items: center;

    gap: 12px;

    min-height: 82px;

    padding: 14px;

    border: 1px solid #dfe5ee;
    border-radius: 9px;

    background: #f8fafc;

    color: #182033;

    text-decoration: none;

    transition:
        transform .15s ease,
        border-color .15s ease,
        background .15s ease;
}


.owner-quick-action:hover {
    transform: translateY(-1px);

    border-color: #2468ee;

    background: #f4f8ff;
}


.owner-quick-icon {
    width: 36px;
    height: 36px;

    border-radius: 8px;

    font-size: 14px;
}


.owner-quick-action strong {
    display: block;

    margin-bottom: 4px;

    color: #182033;

    font-size: 12px;
}


.owner-quick-action span {
    display: block;

    color: #7b879d;

    font-size: 10px;

    line-height: 1.4;
}/* =========================================================
   ACTIVITY
========================================================= */

.owner-action-badge {
    display: inline-block;

    padding: 4px 7px;

    border-radius: 5px;

    background: #eef2f8;

    color: #46536a;

    font-size: 10px;
    font-weight: 700;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 1150px) {

    .owner-summary-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .owner-dashboard-grid {
        grid-template-columns: 1fr;
    }


    .owner-quick-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

}


@media(max-width: 800px) {

    .owner-secondary-grid,
    .owner-transaction-grid {
        grid-template-columns: 1fr;
    }


    .owner-transaction-column:first-child {
        border-right: 0;

        border-bottom:
            1px solid #edf0f5;
    }


    .owner-section-header {
        align-items: flex-start;

        flex-direction: column;
    }

}


@media(max-width: 600px) {

    .owner-summary-grid,
    .owner-secondary-grid,
    .owner-quick-grid {
        grid-template-columns: 1fr;
    }

}

    .owner-dashboard h1:first-of-type,
    .owner-dashboard h1:first-of-type + .muted,
    .owner-dashboard h1:first-of-type + p {
        display: none !important;
    }

    .owner-dashboard .top:has(h1:first-of-type) {
        display: none !important;
    }

    .owner-dashboard table {
        min-width: 0 !important;
    }

    .owner-dashboard th,
    .owner-dashboard td {
        overflow-wrap: anywhere;
    }

    .owner-dashboard .table-wrap,
    .owner-dashboard .table-scroll,
    .owner-dashboard .owner-table-wrap,
    .owner-dashboard .owner-table-scroll,
    .owner-dashboard .owner-compact-list {
        overflow-x: hidden !important;
    }

    .page-dashboard .main h1:first-of-type,
    .page-dashboard .main h1:first-of-type + .muted,
    .page-dashboard .main h1:first-of-type + p,
    .page-dashboard .main h1:first-of-type + div {
        display: none !important;
    }

    .page-dashboard .main > div:has(> h1:first-child),
    .page-dashboard .main > div:has(> div > h1:first-child) {
        display: none !important;
    }

    .page-dashboard .main {
        overflow-x: hidden !important;
    }

    .page-dashboard,
    .page-dashboard body {
        overflow-x: hidden !important;
    }

    .page-dashboard .main > * {
        max-width: 100%;
        box-sizing: border-box;
    }

    .page-dashboard .main table {
        width: 100% !important;
        min-width: 0 !important;
    }
    .legacy-dashboard-heading,
    .legacy-dashboard-heading + .muted,
    .legacy-dashboard-heading + p,
    .legacy-dashboard-heading + div {
        display: none !important;
    }

    .main > div:has(.legacy-dashboard-heading),
    .main > section:has(.legacy-dashboard-heading) {
        display: none !important;
    }

    body.page-dashboard .main > .app-module-heading,
    body.page-dashboard .main > .app-module-heading h1,
    body.page-dashboard .main > .app-module-heading p {
        display: block !important;
        visibility: visible !important;
    }

    body.page-dashboard .main > .app-module-heading {
        min-height: 48px !important;
        margin-bottom: 18px !important;
    }

    body:has(.sidebar a.active[href*="dashboard"]) .main > .app-module-heading,
    body:has(.sidebar a.active[href*="dashboard"]) .main > .app-module-heading h1,
    body:has(.sidebar a.active[href*="dashboard"]) .main > .app-module-heading p {
        display: block !important;
        visibility: visible !important;
    }

    body:has(.sidebar a.active[href*="dashboard"]) .main > .app-module-heading {
        min-height: 48px !important;
        margin-bottom: 18px !important;
    }
</style>

@php
    $transactionPeriods = collect([
        [
            'label' => 'Today',
            'start' => now()->copy()->startOfDay(),
            'end' => now()->copy()->endOfDay(),
        ],
        [
            'label' => 'This Week',
            'start' => now()->copy()->startOfWeek(),
            'end' => now()->copy()->endOfWeek(),
        ],
        [
            'label' => 'This Month',
            'start' => now()->copy()->startOfMonth(),
            'end' => now()->copy()->endOfMonth(),
        ],
        [
            'label' => 'This Year',
            'start' => now()->copy()->startOfYear(),
            'end' => now()->copy()->endOfYear(),
        ],
    ])->map(function (array $period): array {
        $salesCount = \Illuminate\Support\Facades\DB::table('sales')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

        $purchaseCount = \Illuminate\Support\Facades\DB::table('purchases')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();

        return [
            'label' => $period['label'],
            'sales' => $salesCount,
            'purchases' => $purchaseCount,
            'total' => $salesCount + $purchaseCount,
        ];
    })->values();

    $topSellingProducts = collect();

    if (
        \Illuminate\Support\Facades\Schema::hasTable('sale_items')
        && \Illuminate\Support\Facades\Schema::hasTable('products')
        && \Illuminate\Support\Facades\Schema::hasColumn('sale_items', 'product_id')
        && \Illuminate\Support\Facades\Schema::hasColumn('sale_items', 'quantity')
        && \Illuminate\Support\Facades\Schema::hasColumn('products', 'name')
    ) {
        $topSellingProducts = \Illuminate\Support\Facades\DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->select('products.name', \Illuminate\Support\Facades\DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
    }
@endphp

@push('scripts')
    <style>
        .transaction-chart-card {
            display: block !important;
            width: 100% !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            height: 100% !important;
            padding: 18px !important;
            overflow: hidden !important;
            align-self: stretch !important;
            justify-self: stretch !important;
            align-content: start !important;
            justify-content: start !important;
        }

        .transaction-chart-card > * {
            max-width: 100%;
        }

        .transaction-chart-card .transaction-chart-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e8edf5;
        }

        .transaction-chart-card .transaction-chart-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 500;
        }

        .transaction-chart-card .transaction-chart-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 400;
        }

        .transaction-chart-card .transaction-total {
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
            line-height: 1;
            text-align: right;
        }

        .transaction-chart-card .transaction-total span {
            display: block;
            margin-top: 7px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .transaction-chart-card .transaction-bars {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: end;
            gap: 12px;
            margin-top: 16px;
            min-height: 160px;
        }

        .transaction-chart-card .transaction-row {
            display: flex;
            min-height: 160px;
            flex-direction: column;
            justify-content: flex-end;
            gap: 8px;
            text-align: center;
        }

        .transaction-chart-card .transaction-label strong {
            display: block;
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .transaction-chart-card .transaction-label span,
        .transaction-chart-card .transaction-count {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .transaction-chart-card .transaction-count {
            text-align: center;
        }

        .transaction-chart-card .transaction-track {
            display: flex;
            width: 46px;
            height: 86px;
            align-items: flex-end;
            overflow: hidden;
            border-radius: 12px;
            background: #eef4ff;
            box-shadow: inset 0 0 0 1px #dbe5f5;
            margin: 0 auto;
        }

        .transaction-chart-card .transaction-sales {
            display: block;
            width: 100%;
            background: #2563eb;
            min-width: 0;
        }

        .transaction-chart-card .transaction-procurement {
            display: block;
            width: 100%;
            background: #f59e0b;
            min-width: 0;
        }

        .top-products-card {
            border: 1px solid #e5ebf3;
            border-radius: 14px;
            background: #fff;
            padding: 18px;
            min-height: 100%;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .top-products-card h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 500;
        }

        .top-products-card p {
            margin: 6px 0 14px;
            color: #64748b;
            font-size: 12px;
            font-weight: 400;
        }

        .top-products-list {
            display: grid;
            gap: 10px;
        }

        .top-products-empty {
            display: grid;
            min-height: 145px;
            place-items: center;
            border: 1px dashed #d9e2ef;
            border-radius: 12px;
            background: #f8fbff;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
        }


        .top-products-item {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr) 90px;
            align-items: center;
            gap: 12px;
        }

        .top-products-rank {
            display: grid;
            width: 32px;
            height: 32px;
            place-items: center;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 900;
        }

        .top-products-name {
            min-width: 0;
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .top-products-sold {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
        }

        .dashboard-overview-card {
            grid-column: 1 / -1 !important;
            width: 100% !important;
            padding: 0 !important;
            overflow: visible !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        .transaction-overview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            width: 100%;
            align-items: stretch;
        }

        .page-dashboard .main {
            padding-bottom: 28px;
        }

        .page-dashboard .main > .app-module-heading {
            margin-bottom: 20px !important;
        }

        .page-dashboard .main .transaction-overview-grid {
            margin-top: 10px;
        }

        .page-dashboard .main .transaction-chart-card {
            border: 1px solid #e5ebf3;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .page-dashboard .main .transaction-chart-card,
        .page-dashboard .main .top-products-card {
            min-height: 315px;
        }

        .dashboard-hidden-card {
            display: none !important;
        }

        .transaction-chart-card .transaction-empty {
            width: 100%;
            background: #e5e7eb;
        }

        .transaction-chart-card .transaction-legend {
            display: flex;
            gap: 18px;
            margin-top: 22px;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
        }

        .transaction-chart-card .transaction-legend span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .transaction-chart-card .transaction-legend i {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .transaction-chart-card .transaction-legend i.transaction-sales {
            background: #2563eb;
        }

        .transaction-chart-card .transaction-legend i.transaction-procurement {
            background: #f59e0b;
        }

        @media (max-width: 860px) {
            .transaction-overview-grid {
                grid-template-columns: 1fr;
            }

            .transaction-chart-card .transaction-row {
                min-height: 150px;
            }

            .transaction-chart-card .transaction-bars {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const transactionPeriods = @json($transactionPeriods);
            const topSellingProducts = @json($topSellingProducts);

            const findDashboardCard = (title) => {
                const titleElement = Array.from(document.querySelectorAll('h1, h2, h3, h4, strong, b, div, span'))
                    .find((element) => element.textContent.trim() === title);

                if (!titleElement) {
                    return null;
                }

                return titleElement.closest('.owner-card')
                    || titleElement.closest('.card')
                    || titleElement.closest('[class*="card"]')
                    || titleElement.parentElement;
            };

            const getLowAndOutOfStockCounts = () => {
                const materialHeader = Array.from(document.querySelectorAll('th'))
                    .find((header) => header.textContent.trim() === 'Material');

                if (!materialHeader) {
                    return null;
                }

                const table = materialHeader.closest('table');

                if (!table) {
                    return null;
                }

                const rows = Array.from(table.querySelectorAll('tbody tr'));
                let lowStockCount = 0;
                let outOfStockCount = 0;

                rows.forEach((row) => {
                    const cells = Array.from(row.querySelectorAll('td'));
                    const availableText = cells[cells.length - 1]?.textContent.trim() ?? '';
                    const available = Number.parseFloat(availableText.replace(/[^0-9.-]/g, ''));

                    if (Number.isNaN(available)) {
                        return;
                    }

                    if (available <= 0) {
                        outOfStockCount += 1;
                    } else {
                        lowStockCount += 1;
                    }
                });

                return { lowStockCount, outOfStockCount };
            };

            const stockCounts = getLowAndOutOfStockCounts();

            if (stockCounts) {
                const lowStockCard = findDashboardCard('Low Stock');

                if (lowStockCard) {
                    const numberElement = Array.from(lowStockCard.querySelectorAll('*'))
                        .find((element) => /^\d+$/.test(element.textContent.trim()));

                    if (numberElement) {
                        numberElement.textContent = stockCounts.lowStockCount;
                    }

                    if (!findDashboardCard('Out of Stock')) {
                        const outOfStockCard = lowStockCard.cloneNode(true);
                        const titleElement = Array.from(outOfStockCard.querySelectorAll('*'))
                            .find((element) => element.textContent.trim() === 'Low Stock');
                        const valueElement = Array.from(outOfStockCard.querySelectorAll('*'))
                            .find((element) => /^\d+$/.test(element.textContent.trim()));
                        const descriptionElement = Array.from(outOfStockCard.querySelectorAll('*'))
                            .find((element) => element.textContent.includes('Items requiring attention'));

                        if (titleElement) {
                            titleElement.textContent = 'Out of Stock';
                        }

                        if (valueElement) {
                            valueElement.textContent = stockCounts.outOfStockCount;
                        }

                        if (descriptionElement) {
                            descriptionElement.textContent = 'Items with no available stock';
                        }

                        outOfStockCard.querySelectorAll('a').forEach((link) => {
                            const href = link.getAttribute('href') || '';

                            if (href.includes('inventory')) {
                                const nextHref = href.includes('low_stock')
                                    ? href.replace('low_stock', 'out_of_stock')
                                    : `${href}${href.includes('?') ? '&' : '?'}status=out_of_stock`;

                                link.setAttribute('href', nextHref);
                            }
                        });

                        if (outOfStockCard.tagName === 'A') {
                            const href = outOfStockCard.getAttribute('href') || '';

                            if (href.includes('inventory')) {
                                const nextHref = href.includes('low_stock')
                                    ? href.replace('low_stock', 'out_of_stock')
                                    : `${href}${href.includes('?') ? '&' : '?'}status=out_of_stock`;

                                outOfStockCard.setAttribute('href', nextHref);
                            }
                        } else {
                            outOfStockCard.style.cursor = 'pointer';
                            outOfStockCard.addEventListener('click', () => {
                                window.location.href = '/inventory?status=out_of_stock';
                            });
                        }

                        lowStockCard.insertAdjacentElement('afterend', outOfStockCard);

                        const summaryRow = lowStockCard.parentElement;

                        if (summaryRow) {
                            summaryRow.style.display = 'grid';
                            summaryRow.style.gridTemplateColumns = 'repeat(5, minmax(0, 1fr))';
                            summaryRow.style.gap = '18px';
                        }
                    }
                }
            }

            const removeDashboardCardByTitle = (title) => {
                const titleElement = Array.from(document.querySelectorAll('h1, h2, h3, h4, strong, b, div, span'))
                    .find((element) => element.textContent.trim() === title);

                if (!titleElement) {
                    return;
                }

                let card = titleElement.closest('.owner-card')
                    || titleElement.closest('.card')
                    || titleElement.closest('[class*="card"]')
                    || titleElement.parentElement;

                while (
                    card
                    && card.parentElement
                    && card.getBoundingClientRect().height < 95
                    && card.parentElement.children.length <= 6
                ) {
                    card = card.parentElement;
                }

                if (card) {
                    card.remove();
                }
            };

            ['Products', 'Categories', 'Suppliers', 'Authorized Users'].forEach(removeDashboardCardByTitle);

            const needsAttentionHeading = Array.from(document.querySelectorAll('h1, h2, h3'))
                .find((heading) => heading.textContent.trim() === 'Needs Attention');

            if (needsAttentionHeading) {
                let needsAttentionCard = needsAttentionHeading.closest('.owner-card')
                    || needsAttentionHeading.closest('.card')
                    || needsAttentionHeading.closest('[class*="card"]')
                    || needsAttentionHeading.closest('section')
                    || needsAttentionHeading.parentElement?.parentElement;

                if (needsAttentionCard) {
                    while (
                        needsAttentionCard.parentElement
                        && needsAttentionCard.parentElement.textContent.includes('Needs Attention')
                        && needsAttentionCard.getBoundingClientRect().height < 180
                    ) {
                        needsAttentionCard = needsAttentionCard.parentElement;
                    }

                    needsAttentionCard.remove();
                }
            }

            const materialHeader = Array.from(document.querySelectorAll('th'))
                .find((header) => header.textContent.trim() === 'Material');

            if (materialHeader) {
                let materialCard = materialHeader.closest('.owner-card')
                    || materialHeader.closest('.card')
                    || materialHeader.closest('[class*="card"]')
                    || materialHeader.closest('section')
                    || materialHeader.parentElement?.parentElement;

                if (materialCard) {
                    while (
                        materialCard.parentElement
                        && materialCard.parentElement.textContent.includes('Available')
                        && materialCard.getBoundingClientRect().height < 220
                    ) {
                        materialCard = materialCard.parentElement;
                    }

                    materialCard.remove();
                }
            }

            const recentHeading = Array.from(document.querySelectorAll('h1, h2, h3'))
                .find((heading) => heading.textContent.trim() === 'Recent Movement');

            if (!recentHeading) {
                return;
            }

            let recentCard = recentHeading.closest('.owner-card')
                || recentHeading.closest('.card')
                || recentHeading.closest('[class*="card"]')
                || recentHeading.closest('section')
                || recentHeading.parentElement?.parentElement;

            if (!recentCard) {
                return;
            }

            while (
                recentCard.parentElement
                && recentCard.parentElement.textContent.includes('Recent Movement')
                && !(
                    recentCard.getBoundingClientRect().width >= 500
                    && (
                        recentCard.textContent.includes('Purchases')
                        || recentCard.textContent.includes('SALE-')
                        || recentCard.textContent.includes('PUR-')
                    )
                )
            ) {
                recentCard = recentCard.parentElement;
            }

            const renderOverview = (title, subtitle, key, barClass, legendLabel) => {
                const maxTotal = Math.max(1, ...transactionPeriods.map((period) => period[key]));
                const yearTotal = transactionPeriods.find((period) => period.label === 'This Year')?.[key] ?? 0;
                const rows = transactionPeriods.map((period) => {
                    const value = period[key];
                    const filledHeight = value > 0 ? Math.max(8, (value / maxTotal) * 100) : 0;

                    return `
                        <div class="transaction-row">
                            <div class="transaction-count">${value} total</div>
                            <div class="transaction-track" aria-label="${period.label}: ${value} ${legendLabel.toLowerCase()}">
                                <span class="${barClass}" style="height: ${filledHeight}%"></span>
                            </div>
                            <div class="transaction-label">
                                <strong>${period.label}</strong>
                                <span>${value} ${legendLabel.toLowerCase()}</span>
                            </div>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="transaction-chart-card">
                        <div class="transaction-chart-head">
                            <div>
                                <h2>${title}</h2>
                                <p>${subtitle}</p>
                            </div>
                            <div class="transaction-total">
                                ${yearTotal}
                                <span>This year</span>
                            </div>
                        </div>
                        <div class="transaction-bars">${rows}</div>
                        <div class="transaction-legend">
                            <span><i class="${barClass}"></i> ${legendLabel}</span>
                        </div>
                    </div>
                `;
            };

            const renderTopSellingProducts = () => {
                const rows = topSellingProducts.length > 0
                    ? topSellingProducts.map((product, index) => `
                        <div class="top-products-item">
                            <div class="top-products-rank">${index + 1}</div>
                            <div class="top-products-name" title="${product.name}">${product.name}</div>
                            <div class="top-products-sold">${Number(product.total_sold).toLocaleString()} sold</div>
                        </div>
                    `).join('')
                    : '<div class="top-products-empty">No product ranking yet.<br>Products will appear here after sales are recorded.</div>';

                return `
                    <div class="top-products-card">
                        <h2>Top 5 Selling Products</h2>
                        <p>Best-performing products based on quantity sold</p>
                        <div class="top-products-list">${rows}</div>
                    </div>
                `;
            };

            recentCard.classList.remove('transaction-chart-card');
            recentCard.classList.add('dashboard-overview-card');
            recentCard.innerHTML = `
                <div class="transaction-overview-grid">
                    ${renderOverview('Transaction Overview', 'Sales by day, week, month, and year', 'sales', 'transaction-sales', 'Sales')}
                    ${renderOverview('Procurement Overview', 'Purchases by day, week, month, and year', 'purchases', 'transaction-procurement', 'Purchases')}
                    ${renderTopSellingProducts()}
                </div>
            `;
        });
    </script>
@endpush

@endsection
