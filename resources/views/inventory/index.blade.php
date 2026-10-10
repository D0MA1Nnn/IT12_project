@extends('layouts.app')

@section('title', 'Products & Inventory')

@section('content')

<div class="tabs">
    <a href="{{ route('products.index') }}">Products</a>
    <a href="{{ route('categories.index') }}">Categories</a>
    <a href="{{ route('units.index') }}">Units of Measure</a>
    <a class="active" href="{{ route('inventory.index') }}">Inventory</a>
</div>


{{-- =========================================================
    FILTERS
========================================================= --}}

<form
    method="GET"
    action="{{ route('inventory.index') }}"
    class="inventory-toolbar"
    data-auto-filter
>
    <div class="inventory-search">
        <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search materials..."
        >
    </div>

    <select
        name="category"
        class="inventory-select"
        aria-label="Filter by category"
    >
        <option value="">All Categories</option>

        @foreach($categories as $category)
            <option
                value="{{ $category->category_id }}"
                @selected((string) request('category') === (string) $category->category_id)
            >
                {{ $category->category_name }}
            </option>
        @endforeach
    </select>

    <select
        name="stock"
        class="inventory-select"
    >
        <option value="">
            All Stock
        </option>

        <option
            value="in_stock"
            @selected(request('stock') === 'in_stock')
        >
            In Stock
        </option>

        <option
            value="low_stock"
            @selected(request('stock') === 'low_stock')
        >
            Low Stock
        </option>

        <option
            value="out_of_stock"
            @selected(request('stock') === 'out_of_stock')
        >
            Out of Stock
        </option>
    </select>

    @if(
        request()->filled('search') ||
        request()->filled('category') ||
        request()->filled('stock')
    )
        <a
            href="{{ route('inventory.index') }}"
            class="btn light"
        >
            Clear
        </a>
    @endif
</form>


{{-- =========================================================
    INVENTORY TABLE
========================================================= --}}

<div class="inventory-card">

    <div class="inventory-card-header">

        <div>
            <h3>
                Current Stock
            </h3>

            <p>
                Quantities are stored in each product's base unit.
            </p>
        </div>

        <span class="material-count">
            {{ $inventories->count() }}
            {{ $inventories->count() === 1 ? 'material' : 'materials' }}
        </span>

    </div>


    <div class="inventory-table-wrapper">

        <table class="table inventory-table">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Available Qty</th>
                    <th>Unit</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            @forelse($inventories as $inventory)

                @php
                    $product = $inventory->product;

                    $baseUnit =
                        $product->productUnits
                            ->firstWhere(
                                'is_base_unit',
                                true
                            )
                        ?? $product->productUnits->first();

                    $quantity =
                        (float) $inventory->quantity_on_hand;

                    $reorder =
                        (float) $inventory->reorder_level;

                    $outOfStock =
                        $quantity <= 0;

                    $lowStock =
                        $quantity > 0 &&
                        $quantity <= $reorder;
                @endphp

                <tr>

                    <td>
                        <strong>
                            {{ $product->product_name }}
                        </strong>
                    </td>

                    <td>
                        {{ $product->category?->category_name ?? '—' }}
                    </td>

                    <td>
                        <span
                            class="{{ $outOfStock ? 'qty-zero' : '' }}"
                        >
                            {{ number_format($quantity, 0) }}
                        </span>
                    </td>

                    <td>
                        {{ $baseUnit?->unit?->unit_name ?? '—' }}
                    </td>

                    <td>

                        <span class="reorder-value">
                            {{ number_format($reorder, 0) }}
                        </span>

                    </td>

                    <td>

                        @if($outOfStock)

                            <span class="stock-badge out">
                                Out of Stock
                            </span>

                        @elseif($lowStock)

                            <span class="stock-badge low">
                                Low Stock
                            </span>

                        @else

                            <span class="stock-badge good">
                                In Stock
                            </span>

                        @endif

                    </td>


                </tr>

            @empty

                <tr>
                    <td
                        colspan="6"
                        class="inventory-empty"
                    >
                        No inventory records found.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>


<style>

html,
body {
    overflow: hidden;
}

.main {
    display: flex;
    flex-direction: column;
    height: 100vh;
    min-height: 0;
    overflow: hidden;
}

.tabs,
.inventory-toolbar {
    flex: 0 0 auto;
}

/* =========================================================
   FILTER
========================================================= */

.inventory-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.inventory-search {
    width: 310px;
    height: 44px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
}

.inventory-search:focus-within {
    border-color: #93b4ef;
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}

.inventory-search svg {
    flex-shrink: 0;
    color: #94a3b8;
}

.inventory-search input {
    border: 0;
    outline: 0;
    width: 100%;
    background: transparent;
    font: inherit;
    color: #0f172a;
}

.inventory-search input::placeholder {
    color: #94a3b8;
}

.inventory-select {
    width: 220px;
    height: 44px;
    padding: 0 14px;
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    color: #0f172a;
    font: inherit;
}


/* =========================================================
   CARD
========================================================= */

.inventory-card {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #edf1f6;
}

.inventory-card-header {
    flex: 0 0 auto;
    min-height: 88px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.inventory-card-header h3 {
    margin: 0 0 4px;
    color: #0f172a;
}

.inventory-card-header p {
    margin: 0;
    color: #8492aa;
    font-size: 13px;
}

.material-count {
    background: #f1f5f9;
    color: #64748b;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}


/* =========================================================
   TABLE
========================================================= */

.inventory-table-wrapper {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.inventory-table {
    width: 100%;
}

.inventory-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.inventory-table th {
    white-space: nowrap;
}

.inventory-table td {
    vertical-align: middle;
}

.qty-zero {
    color: #ef3340;
    font-weight: 600;
}

.reorder-form {
    display: flex;
    align-items: center;
    gap: 6px;
}

.reorder-input {
    width: 78px;
    height: 38px;
    padding: 0 10px;
    border: 1px solid #dbe3ef;
    background: #f8fafc;
    border-radius: 8px;
    outline: none;
    color: #334155;
}

.reorder-input:focus {
    border-color: #93b4ef;
    background: #fff;
}

.stock-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.stock-badge.good {
    background: #eaf8ee;
    color: #18843c;
}

.stock-badge.low {
    background: #fff4dd;
    color: #b66a00;
}

.stock-badge.out {
    background: #ffe5e5;
    color: #e02f37;
}

.inventory-empty {
    text-align: center;
    color: #94a3b8;
    padding: 40px !important;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 700px) {

    .inventory-search,
    .inventory-select {
        width: 100%;
    }
}

</style>


@endsection
