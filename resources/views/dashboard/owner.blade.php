@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- =========================================================
     PAGE HEADER
========================================================= --}}
<div class="top">
    <div>
        <h1>Dashboard</h1>
        <div class="muted">
            Daily overview of inventory, purchasing and sales
        </div>
    </div>

    <div class="who">Owner</div>
</div>


{{-- =========================================================
     MAIN SUMMARY CARDS
========================================================= --}}
<div class="owner-summary-grid">

    {{-- PRODUCTS --}}
    <a href="{{ route('products.index') }}" class="owner-stat-card">
        <div class="owner-stat-header">
            <div class="owner-stat-icon blue">
                P
            </div>

            <span>Active Products</span>
        </div>

        <div class="owner-stat-value">
            {{ number_format($productCount) }}
        </div>

        <div class="owner-stat-footer">
            Construction materials
        </div>
    </a>


    {{-- LOW STOCK --}}
    <a
        href="{{ route('products.index', ['stock' => 'low']) }}"
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


    {{-- TODAY'S PURCHASES --}}
    <a href="{{ route('purchases.index') }}" class="owner-stat-card">
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
            {{ $todayPurchaseCount == 1 ? 'purchase' : 'purchases' }}
            today
        </div>
    </a>


    {{-- TODAY'S SALES --}}
    <a
        href="{{ route('sales.index') }}"
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

</div>


{{-- =========================================================
     SECONDARY SUMMARY
========================================================= --}}
<div class="owner-secondary-grid">

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
     INVENTORY ATTENTION
========================================================= --}}
<div class="owner-section">

    <div class="owner-section-header">

        <div>
            <h2>Inventory Attention</h2>

            <p>
                Materials that have reached or fallen below their reorder level
            </p>
        </div>

        <a
            href="{{ route('products.index', ['stock' => 'low']) }}"
            class="owner-section-link"
        >
            View Products →
        </a>

    </div>


    <div class="owner-table-wrapper">

        <table class="table owner-dashboard-table">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Available Qty</th>
                    <th>Unit</th>
                    <th>Reorder</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                @forelse($lowStockItems as $inventory)

                    @php
                        $product = $inventory->product;

                        $baseUnit = $product?->productUnits
                            ?->firstWhere('is_base_unit', true)
                            ?? $product?->productUnits?->first();

                        $quantity = (float) $inventory->quantity_on_hand;
                        $reorder = (float) $inventory->reorder_level;
                    @endphp

                    <tr>

                        <td>
                            <strong>
                                {{ $product?->product_name ?? '—' }}
                            </strong>
                        </td>

                        <td>
                            {{ number_format($quantity, 0) }}
                        </td>

                        <td>
                            {{ $baseUnit?->unit?->unit_name ?? '—' }}
                        </td>

                        <td>
                            {{ number_format($reorder, 0) }}
                        </td>

                        <td>

                            @if($quantity <= 0)

                                <span class="owner-status out">
                                    Out of Stock
                                </span>

                            @else

                                <span class="owner-status low-stock">
                                    Low Stock
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="owner-empty">
                            No low-stock materials at this time.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- =========================================================
     RECENT TRANSACTIONS
========================================================= --}}
<div class="owner-section">

    <div class="owner-section-header">

        <div>
            <h2>Recent Transactions</h2>

            <p>
                Latest purchasing and sales activities
            </p>
        </div>

        <a
            href="{{ route('sales.index') }}"
            class="owner-section-link"
        >
            View Sales →
        </a>

    </div>


    <div class="owner-transaction-grid">

        {{-- =================================================
             RECENT PURCHASES
        ================================================== --}}
        <div class="owner-transaction-column">

            <div class="owner-sub-header">

                <div>
                    <h3>Purchases</h3>
                    <span>Recently received materials</span>
                </div>

            </div>


            @forelse($recentPurchases as $purchase)

                <div class="owner-transaction-row">

                    <div class="owner-transaction-info">

                        <strong>
                            PUR-{{ str_pad(
                                $purchase->purchase_id,
                                4,
                                '0',
                                STR_PAD_LEFT
                            ) }}
                        </strong>

                        <span>
                            @if($purchase->purchase_date)

                                {{ \Carbon\Carbon::parse(
                                    $purchase->purchase_date
                                )->format('M d, Y g:i A') }}

                            @else

                                —

                            @endif
                        </span>

                        @if($purchase->supplier)

                            <small>
                                {{ $purchase->supplier->supplier_name }}
                            </small>

                        @endif

                    </div>


                    <div class="owner-transaction-amount">
                        ₱{{ number_format(
                            (float) $purchase->total_amount,
                            2
                        ) }}
                    </div>

                </div>

            @empty

                <div class="owner-transaction-empty">
                    No purchase transactions yet.
                </div>

            @endforelse

        </div>


        {{-- =================================================
             RECENT SALES
        ================================================== --}}
        <div class="owner-transaction-column">

            <div class="owner-sub-header">

                <div>
                    <h3>Sales</h3>
                    <span>Recently completed customer sales</span>
                </div>

            </div>


            @forelse($recentSales as $sale)

                <div class="owner-transaction-row">

                    <div class="owner-transaction-info">

                        <strong>
                            SALE-{{ str_pad(
                                $sale->sale_id,
                                4,
                                '0',
                                STR_PAD_LEFT
                            ) }}
                        </strong>

                        <span>

                            @if($sale->sale_date)

                                {{ \Carbon\Carbon::parse(
                                    $sale->sale_date
                                )->format('M d, Y g:i A') }}

                            @else

                                —

                            @endif

                        </span>


                        @if($sale->user)

                            <small>
                                Recorded by {{ $sale->user->username }}
                            </small>

                        @endif

                    </div>


                    <div class="owner-transaction-amount">
                        ₱{{ number_format(
                            (float) $sale->total_amount,
                            2
                        ) }}
                    </div>

                </div>

            @empty

                <div class="owner-transaction-empty">
                    No sales transactions yet.
                </div>

            @endforelse

        </div>

    </div>

</div>


{{-- =========================================================
     QUICK ACTIONS
========================================================= --}}
<div class="owner-section">

    <div class="owner-section-header">

        <div>
            <h2>Quick Actions</h2>

            <p>
                Frequently used management functions
            </p>
        </div>

    </div>


    <div class="owner-quick-grid">

        <a
            href="{{ route('products.create') }}"
            class="owner-quick-action"
        >
            <div class="owner-quick-icon blue">
                +
            </div>

            <div>
                <strong>Add Product</strong>

                <span>
                    Register a construction material
                </span>
            </div>
        </a>


        <a
            href="{{ route('purchases.create') }}"
            class="owner-quick-action"
        >
            <div class="owner-quick-icon orange">
                ↓
            </div>

            <div>
                <strong>Record Purchase</strong>

                <span>
                    Record materials received from a supplier
                </span>
            </div>
        </a>


        <a
            href="{{ route('inventory.index') }}"
            class="owner-quick-action"
        >
            <div class="owner-quick-icon green">
                I
            </div>

            <div>
                <strong>View Inventory</strong>

                <span>
                    Monitor available material quantities
                </span>
            </div>
        </a>


        <a
            href="{{ route('sales.index') }}"
            class="owner-quick-action"
        >
            <div class="owner-quick-icon dark">
                S
            </div>

            <div>
                <strong>Sales Management</strong>

                <span>
                    Record sales and review daily sales records
                </span>
            </div>
        </a>

    </div>

</div>


{{-- =========================================================
     RECENT SYSTEM ACTIVITY
========================================================= --}}
<div class="owner-section">

    <div class="owner-section-header">

        <div>
            <h2>Recent System Activity</h2>

            <p>
                Latest recorded actions performed by system users
            </p>
        </div>

        <a
            href="{{ route('activity.index') }}"
            class="owner-section-link"
        >
            View Activity Logs →
        </a>

    </div>


    <div class="owner-table-wrapper">

        <table class="table owner-dashboard-table">

            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Activity</th>
                </tr>
            </thead>

            <tbody>

                @forelse($recentActivities as $activity)

                    <tr>

                        <td>
                            {{ $activity->created_at
                                ?->format('m/d/Y g:i A') ?? '—'
                            }}
                        </td>

                        <td>
                            {{ $activity->user?->username ?? 'System' }}
                        </td>

                        <td>
                            {{ $activity->module ?? '—' }}
                        </td>

                        <td>
                            <span class="owner-action-badge">
                                {{ $activity->action ?? '—' }}
                            </span>
                        </td>

                        <td>
                            {{ $activity->description }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="owner-empty">
                            No system activity recorded yet.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

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
    grid-template-columns: repeat(3, minmax(0, 1fr));

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
}


/* =========================================================
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

</style>

@endsection