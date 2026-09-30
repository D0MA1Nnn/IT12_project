@extends('layouts.app')

@section('title', 'Products')

@section('content')

<div class="tabs">
    <a class="active" href="{{ route('products.index') }}">Products</a>
    <a href="{{ route('categories.index') }}">Categories</a>
    <a href="{{ route('units.index') }}">Units of Measure</a>
    <a href="{{ route('inventory.index') }}">Inventory</a>
</div>

{{-- FILTERS --}}
<form method="GET"
      action="{{ route('products.index') }}"
      class="product-toolbar">

    <div class="standard-search">
        <span class="search-icon">⌕</span>

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search products..."
        >
    </div>

    <select name="category" class="standard-select">
        <option value="">All Categories</option>

        @foreach($categories as $category)
            <option
                value="{{ $category->category_id }}"
                @selected(request('category') == $category->category_id)
            >
                {{ $category->category_name }}
            </option>
        @endforeach
    </select>


    <select name="status" class="standard-select">
        <option value="">All Status</option>

        <option value="active"
            @selected(request('status') === 'active')>
            Active
        </option>

        <option value="archived"
            @selected(request('status') === 'archived')>
            Archived
        </option>
    </select>

    <button class="btn primary" type="submit">
        Filter
    </button>

    @if(
        request()->filled('search') ||
        request()->filled('category') ||
        request()->filled('status')
    )
        <a href="{{ route('products.index') }}"
           class="btn light">
            Clear
        </a>
    @endif

    <div class="toolbar-spacer"></div>

    <button
        type="button"
        class="btn primary"
        id="openAddProduct"
    >
        + Add Product
    </button>
</form>


<div class="product-card">

    <div class="table-responsive">

        <table class="table product-table">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Units</th>
                    <th>Base Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($products as $product)

                @php
                    $baseUnit =
                        $product->productUnits
                            ->firstWhere('is_base_unit', true)
                        ?? $product->productUnits->first();

                    $unitOptions = $product->productUnits
                        ->sortByDesc('is_base_unit')
                        ->map(fn ($unitOption) => [
                            'unit_id' => $unitOption->unit_id,
                            'name' => $unitOption->unit?->unit_name ?? '—',
                            'selling_price' => (float) $unitOption->selling_price,
                            'purchase_cost' => (float) $unitOption->purchase_cost,
                            'conversion_factor' => (float) $unitOption->conversion_factor,
                            'is_base_unit' => (bool) $unitOption->is_base_unit,
                            'is_active' => (bool) $unitOption->is_active,
                        ])
                        ->values();

                    $reorder =
                        (float)($product->inventory?->reorder_level ?? 0);
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
                        <div class="unit-list">
                            @forelse($unitOptions as $unitOption)
                                <span class="unit-chip">
                                    {{ $unitOption['name'] }}
                                    @if($unitOptions->count() > 1 && $unitOption['is_base_unit'])
                                        <small>(Base)</small>
                                    @endif
                                </span>
                            @empty
                                —
                            @endforelse
                        </div>
                    </td>

                    <td>
                        ₱{{ number_format((float)($baseUnit?->selling_price ?? 0), 2) }}
                    </td>

                    <td>
                        @if($product->is_active)
                            <span class="status-pill active">
                                Active
                            </span>
                        @else
                            <span class="status-pill archived">
                                Archived
                            </span>
                        @endif
                    </td>

                    <td>

                        <div class="actions">

                            <button
                                type="button"
                                class="btn light small product-view-btn"
                                data-name="{{ $product->product_name }}"
                                data-category="{{ $product->category?->category_name ?? '—' }}"
                                data-supplier="{{ $product->suppliers->first()?->supplier_name ?? '—' }}"
                                data-status="{{ $product->is_active ? 'Active' : 'Archived' }}"
                                data-stock="{{ number_format((float)($product->inventory?->quantity_on_hand ?? 0), 3, '.', '') }}"
                                data-reorder="{{ number_format($reorder, 0, '.', '') }}"
                                data-description="{{ $product->description ?? '—' }}"
                                data-units='@json($unitOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                            >
                                View
                            </button>

                            <button
                                type="button"
                                class="btn light small edit-product-btn"
                                data-update-url="{{ route('products.update', $product) }}"
                                data-name="{{ $product->product_name }}"
                                data-category="{{ $product->category_id }}"
                                data-unit="{{ $baseUnit?->unit_id ?? '' }}"
                                data-supplier="{{ $product->suppliers->first()?->supplier_id ?? '' }}"
                                data-price="{{ $baseUnit?->selling_price ?? 0 }}"
                                data-cost="{{ $baseUnit?->purchase_cost ?? 0 }}"
                                data-reorder="{{ number_format($reorder, 0, '.', '') }}"
                                data-description="{{ $product->description ?? '' }}"
                                data-units='@json($unitOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                            >
                                Edit
                            </button>

                            <button
                                type="button"
                                class="btn primary small units-btn"
                                data-product-name="{{ $product->product_name }}"
                                data-store-url="{{ route('products.units.store', $product) }}"
                                data-base-unit-name="{{ $baseUnit?->unit?->unit_name ?? 'base unit' }}"
                                data-units='@json($unitOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                            >
                                Unit
                            </button>

                            <form
                                method="POST"
                                action="{{ route(
                                    $product->is_active
                                        ? 'products.deactivate'
                                        : 'products.activate',
                                    $product
                                ) }}"
                                class="product-status-form"
                                data-product-name="{{ $product->product_name }}"
                                data-status-action="{{ $product->is_active ? 'archive' : 'restore' }}"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="btn small {{ $product->is_active ? 'danger' : 'success' }}"
                                >
                                    {{ $product->is_active ? 'Archive' : 'Restore' }}
                                </button>
                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6" class="empty-row">
                        No products found.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>


    @if($products->hasPages())

        <div class="pagination-wrapper">

            <div class="pagination-info">
                Showing
                {{ $products->firstItem() }}
                to
                {{ $products->lastItem() }}
                of
                {{ $products->total() }}
                products
            </div>

            <div class="compact-pagination">
                @if ($products->onFirstPage())
                    <span class="page-link disabled">&lsaquo; Previous</span>
                @else
                    <a class="page-link" href="{{ $products->previousPageUrl() }}">&lsaquo; Previous</a>
                @endif

                @for ($page = 1; $page <= $products->lastPage(); $page++)
                    @if ($page === $products->currentPage())
                        <span class="page-link active">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $products->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if ($products->hasMorePages())
                    <a class="page-link" href="{{ $products->nextPageUrl() }}">Next &rsaquo;</a>
                @else
                    <span class="page-link disabled">Next &rsaquo;</span>
                @endif
            </div>

        </div>

    @endif

</div>


{{-- =========================================================
ARCHIVE / RESTORE CONFIRMATION MODAL
========================================================= --}}

<div class="modal-overlay" id="productStatusModal">
    <div class="system-modal product-confirm-modal">
        <div class="confirm-icon">!</div>

        <h2 id="productStatusTitle">Confirm Action</h2>
        <p id="productStatusMessage">Are you sure you want to continue?</p>

        <div class="modal-actions confirm-actions">
            <button type="button" class="btn light" id="cancelProductStatusModal">Cancel</button>
            <button type="button" class="btn danger" id="confirmProductStatusButton">Archive</button>
        </div>
    </div>
</div>


{{-- =========================================================
PRODUCT VIEW MODAL
========================================================= --}}

<div class="modal-overlay"
     id="productViewModal">

    <div class="system-modal">

        <div class="modal-header">

            <div>
                <h2 id="productViewTitle">
                    Product Details
                </h2>

                <p>
                    View product information and available units.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                id="closeProductViewModal"
            >
                &times;
            </button>

        </div>

        <div class="product-view-grid">
            <div>
                <span>Category</span>
                <strong id="productViewCategory">—</strong>
            </div>

            <div>
                <span>Supplier</span>
                <strong id="productViewSupplier">—</strong>
            </div>

            <div>
                <span>Status</span>
                <strong id="productViewStatus">—</strong>
            </div>

            <div>
                <span>Stock</span>
                <strong id="productViewStock">—</strong>
            </div>

            <div>
                <span>Reorder Level</span>
                <strong id="productViewReorder">—</strong>
            </div>
        </div>

        <div class="product-view-section">
            <span>Description</span>
            <p id="productViewDescription">—</p>
        </div>

        <div class="product-view-section">
            <span>Units</span>
            <div class="product-unit-view-list" id="productViewUnits"></div>
        </div>

        <div class="modal-actions">
            <button
                type="button"
                class="btn light"
                id="cancelProductViewModal"
            >
                Close
            </button>
        </div>

    </div>

</div>


{{-- =========================================================
ADD / EDIT PRODUCT MODAL
========================================================= --}}

<div class="modal-overlay"
     id="productModal">

    <div class="system-modal large-modal">

        <div class="modal-header">

            <div>
                <h2 id="productModalTitle">
                    Add Product
                </h2>

                <p>
                    Product, inventory and base unit information
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                id="closeProductModal"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            id="productForm"
            action="{{ route('products.store') }}"
        >
            @csrf

            <div id="productMethod"></div>


            <div class="modal-grid">

                <div class="field">
                    <label>Product Name</label>

                    <input
                        class="input"
                        type="text"
                        name="product_name"
                        id="productName"
                        required
                    >
                </div>


                <div class="field">
                    <label>Category</label>

                    <select
                        name="category_id"
                        id="productCategory"
                        required
                    >
                        <option value="">
                            Select category
                        </option>

                        @foreach($categories as $category)
                            <option value="{{ $category->category_id }}">
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div class="field">
                    <label>Supplier</label>

                    <select
                        name="supplier_id"
                        id="productSupplier"
                        required
                    >
                        <option value="">
                            Select supplier
                        </option>

                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->supplier_id }}">
                                {{ $supplier->supplier_name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div class="field">
                    <label>Base Unit</label>

                    <select
                        name="unit_id"
                        id="productUnit"
                        required
                    >
                        <option value="">
                            Select unit
                        </option>

                        @foreach($units as $unit)
                            <option
                                value="{{ $unit->unit_id }}"
                                data-name="{{ $unit->unit_name }}"
                                data-symbol="{{ $unit->unit_symbol }}"
                            >
                                {{ $unit->unit_name }}
                                ({{ $unit->unit_symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>


                <div class="field">
                    <label>Selling Price</label>

                    <input
                        class="input"
                        type="number"
                        name="selling_price"
                        id="productPrice"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>


                <div class="field">
                    <label>Purchase Cost</label>

                    <input
                        class="input"
                        type="number"
                        name="purchase_cost"
                        id="productCost"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>


                <div class="field">
                    <label>Reorder Level</label>

                    <input
                        class="input"
                        type="number"
                        name="reorder_level"
                        id="productReorder"
                        min="0"
                        step="1"
                        required
                    >
                </div>

            </div>


            <div class="field">
                <label>Description</label>

                <textarea
                    name="description"
                    id="productDescription"
                    rows="4"
                ></textarea>
            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn light"
                    id="cancelProductModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn primary"
                    id="saveProductButton"
                >
                    Save Product
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
PRODUCT UNIT MANAGEMENT MODAL
========================================================= --}}

<div class="modal-overlay"
     id="unitsModal">

    <div class="system-modal">

        <div class="modal-header">

            <div>
                <h2 id="unitsModalTitle">
                    Product Units
                </h2>

                <p>
                    View current units and add another unit option.
                </p>
            </div>

            <button
                type="button"
                class="modal-close"
                id="closeUnitsModal"
            >
                &times;
            </button>

        </div>


        <div class="product-unit-view-list" id="productUnitViewList"></div>

        <form
            method="POST"
            id="unitsForm"
        >
            @csrf

            <div class="modal-grid">

                <div class="field">
                    <label>Unit</label>

                    <select
                        name="unit_id"
                        id="unitOptionSelect"
                        required
                    >
                        <option value="">
                            Select unit
                        </option>

                        @foreach($units as $unit)
                            <option value="{{ $unit->unit_id }}">
                                {{ $unit->unit_name }}
                                ({{ $unit->unit_symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Easy Conversion</label>

                    <input
                        type="hidden"
                        name="conversion_factor"
                        id="unitConversionFactor"
                        value="1"
                    >

                    <div class="conversion-builder">
                        <input
                            class="input"
                            type="number"
                            id="conversionSelectedQty"
                            min="0.001"
                            step="0.001"
                            value="1"
                            required
                        >

                        <span id="conversionSelectedUnitName">selected unit</span>

                        <strong>=</strong>

                        <input
                            class="input"
                            type="number"
                            id="conversionBaseQty"
                            min="0.001"
                            step="0.001"
                            value="1"
                            required
                        >

                        <span id="conversionBaseUnitName">base unit</span>
                    </div>

                    <small class="conversion-preview" id="conversionPreview">
                        1 selected unit = 1 base unit
                    </small>
                </div>

                <div class="field">
                    <label>Selling Price</label>

                    <input
                        class="input"
                        type="number"
                        name="selling_price"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="field">
                    <label>Purchase Cost</label>

                    <input
                        class="input"
                        type="number"
                        name="purchase_cost"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

            </div>

            <div class="modal-actions">

                <button
                    type="button"
                    class="btn light"
                    id="cancelUnitsModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn primary"
                    id="saveUnitButton"
                >
                    Add Unit Option
                </button>

            </div>

        </form>

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
.product-toolbar {
    flex: 0 0 auto;
}

.product-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: nowrap;
    margin-bottom: 20px;
}

.standard-search {
    flex: 0 0 280px;
    width: 280px;
    height: 44px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
}

.search-icon {
    color: #94a3b8;
    font-size: 19px;
}

.standard-search input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #0f172a;
    font: inherit;
}

.standard-search input::placeholder {
    color: #94a3b8;
}

.standard-select {
    flex: 0 0 170px;
    width: 170px;
    height: 44px;
    min-width: 170px;
    padding: 0 13px;
    background: #fff;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    color: #0f172a;
    font: inherit;
}

.toolbar-spacer {
    flex: 1;
}








.product-card {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #edf1f6;
}

.table-responsive {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.product-table {
    width: 100%;
}

.product-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.product-table td {
    vertical-align: middle;
}

.unit-list{display:flex;flex-wrap:wrap;gap:4px 10px;color:#0f172a}.unit-chip{display:inline;color:#0f172a;font-size:12px;font-weight:700}.unit-chip:not(:last-child)::after{content:","}.unit-chip small{color:#0f172a;font-size:11px;font-weight:700;text-transform:none}

.actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.actions form {
    margin: 0;
}

.status-pill {
    display: inline-flex;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}

.status-pill.active {
    background: #e8f8ed;
    color: #15803d;
}

.status-pill.low {
    background: #fff4dc;
    color: #b66a00;
}

.status-pill.out {
    background: #ffe4e6;
    color: #dc2626;
}

.status-pill.archived {
    background: #e2e8f0;
    color: #475569;
}

.btn.danger {
    background: #e63946;
    color: white;
}

.btn.success {
    background: #22a447;
    color: white;
}

.empty-row {
    text-align: center;
    padding: 35px !important;
    color: #94a3b8;
}

.pagination-wrapper {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    border-top: 1px solid #edf1f6;
}

.pagination-info {
    font-size: 13px;
    color: #64748b;
}

.compact-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.compact-pagination .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    line-height: 1;
}

.compact-pagination .page-link:hover { background: #f1f5f9; }
.compact-pagination .page-link.active { background: #2563eb; border-color: #2563eb; color: #fff; }
.compact-pagination .page-link.disabled { color: #94a3b8; background: #f8fafc; cursor: default; }


/* MODAL */

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 24px;

    background: rgba(15,23,42,.58);
    backdrop-filter: blur(2px);
}

.modal-overlay.show {
    display: flex;
}

.system-modal {
    width: min(650px, 100%);
    max-height: 90vh;
    overflow-y: auto;

    background: #fff;
    border-radius: 16px;
    padding: 26px;

    box-shadow: 0 25px 60px rgba(15,23,42,.25);
}

.large-modal {
    width: min(850px, 100%);
}


/* PRODUCT ARCHIVE / RESTORE MODAL — MATCHES THE OTHER ARCHIVE MODALS */
#productStatusModal .product-confirm-modal {
    width: 390px !important;
    max-width: calc(100vw - 48px) !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    box-sizing: border-box !important;
    padding: 30px 28px 28px !important;
    border-radius: 16px !important;
    background: #ffffff !important;
    text-align: center !important;
    box-shadow: 0 25px 60px rgba(15, 23, 42, .25) !important;
}

#productStatusModal .confirm-icon {
    width: 48px !important;
    height: 48px !important;
    margin: 0 auto 18px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #fff0f1 !important;
    color: #ef3d4d !important;
    font-size: 23px !important;
    line-height: 1 !important;
    font-weight: 900 !important;
}

#productStatusModal .product-confirm-modal h2 {
    margin: 0 0 10px !important;
    color: #182033 !important;
    font-size: 22px !important;
    line-height: 1.25 !important;
    font-weight: 700 !important;
    text-align: center !important;
}

#productStatusModal .product-confirm-modal > p {
    width: 100% !important;
    max-width: 330px !important;
    margin: 0 auto !important;
    color: #7b879d !important;
    font-size: 13px !important;
    line-height: 1.55 !important;
    text-align: center !important;
}

#productStatusModal .confirm-actions {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    margin-top: 24px !important;
}

#productStatusModal .confirm-actions .btn {
    margin: 0 !important;
}


.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 22px;
}

.modal-header h2 {
    margin: 0 0 5px;
    color: #0f172a;
}

.modal-header p {
    margin: 0;
    color: #8492aa;
    font-size: 13px;
}

.modal-close {
    width: 36px;
    height: 36px;
    flex-shrink: 0;

    border: 0;
    border-radius: 8px;

    background: #f1f5f9;
    color: #475569;

    font-size: 25px;
    cursor: pointer;
}

.modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 16px;
}

.system-modal .field {
    margin-bottom: 16px;
}

.system-modal label {
    display: block;
    margin-bottom: 7px;

    color: #0f172a;
    font-size: 13px;
    font-weight: 700;
}

.system-modal .input,
.system-modal select,
.system-modal textarea {
    width: 100%;
    box-sizing: border-box;

    padding: 11px 13px;

    border: 1px solid #dbe3ef;
    border-radius: 8px;

    background: #f8fafc;
    color: #0f172a;

    font: inherit;
    outline: none;
}

.system-modal select {
    height: 44px;
}

.system-modal .input:focus,
.system-modal select:focus,
.system-modal textarea:focus {
    background: #fff;
    border-color: #93b4ef;
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}

.info-box {
    padding: 13px 15px;
    margin-bottom: 20px;

    background: #eff6ff;
    border-radius: 8px;

    color: #475569;
    font-size: 13px;
    line-height: 1.5;
}

.product-unit-view-list {
    display: grid;
    gap: 10px;
    margin-bottom: 18px;
}

.product-unit-view-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 10px;
    align-items: center;
    padding: 12px 14px;
    border: 1px solid #e5ecf5;
    border-radius: 10px;
    background: #f8fafc;
    color: #0f172a;
    font-size: 12px;
}

.product-unit-view-row strong {
    font-size: 13px;
}

.product-unit-view-row span {
    color: #0f172a;
}

.product-unit-view-label {
    display: block;
    margin-bottom: 3px;
    color: #64748b !important;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.product-view-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 18px;
}

.product-view-grid > div,
.product-view-section {
    padding: 12px 14px;
    border: 1px solid #e5ecf5;
    border-radius: 10px;
    background: #f8fafc;
}

.product-view-grid span,
.product-view-section > span {
    display: block;
    margin-bottom: 4px;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.product-view-grid strong {
    color: #0f172a;
    font-size: 13px;
}

.product-view-section {
    margin-bottom: 14px;
}

.product-view-section p {
    margin: 0;
    color: #0f172a;
    font-size: 13px;
    line-height: 1.5;
}

.conversion-builder {
    display: grid;
    grid-template-columns: 90px 1fr auto 90px 1fr;
    gap: 8px;
    align-items: center;
}

.conversion-builder .input {
    text-align: center;
}

.conversion-builder span,
.conversion-builder strong {
    color: #0f172a;
    font-size: 12px;
    font-weight: 800;
}

.conversion-preview {
    display: block;
    margin-top: 8px;
    color: #64748b;
    font-size: 11px;
    line-height: 1.4;
}

.product-unit-view-empty {
    padding: 12px;
    border-radius: 10px;
    background: #f8fafc;
    color: #64748b;
    font-size: 12px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 6px;
}

@media(max-width: 1050px) {
    .product-toolbar {
        flex-wrap: wrap;
    }

    .toolbar-spacer {
        display: none;
    }
}

@media(max-width: 750px) {

    .standard-search,
    .standard-select {
        width: 100%;
    }

    .toolbar-spacer {
        display: none;
    }

    .modal-grid {
        grid-template-columns: 1fr;
    }

    .pagination-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

</style>


@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    ============================================================
    PRODUCT MODAL
    ============================================================
    */

    const productModal =
        document.getElementById('productModal');

    const productForm =
        document.getElementById('productForm');

    const productMethod =
        document.getElementById('productMethod');

    const productModalTitle =
        document.getElementById('productModalTitle');

    const openAddProduct =
        document.getElementById('openAddProduct');

    const closeProductModal =
        document.getElementById('closeProductModal');

    const cancelProductModal =
        document.getElementById('cancelProductModal');

    const saveProductButton =
        document.getElementById('saveProductButton');

    const productUnitSelect =
        document.getElementById('productUnit');

    const productPriceInput =
        document.getElementById('productPrice');

    const productCostInput =
        document.getElementById('productCost');

    let editingProductUnits = [];

    function resetProductUnitChoices() {
        editingProductUnits = [];

        Array.from(productUnitSelect.options).forEach(function (option) {
            option.disabled = false;
            option.title = '';
        });
    }

    function syncProductBaseUnitPrices() {
        const selectedUnit =
            editingProductUnits.find(function (unit) {
                return String(unit.unit_id) === String(productUnitSelect.value);
            });

        if (! selectedUnit) {
            return;
        }

        productPriceInput.value =
            Number(selectedUnit.selling_price || 0).toFixed(2);

        productCostInput.value =
            Number(selectedUnit.purchase_cost || 0).toFixed(2);
    }


    function showProductModal() {
        productModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }


    function hideProductModal() {
        productModal.classList.remove('show');
        document.body.style.overflow = '';

        saveProductButton.disabled = false;
        saveProductButton.textContent = 'Save Product';
    }


    openAddProduct.addEventListener('click', function () {

        productForm.reset();
        resetProductUnitChoices();

        productForm.action =
            @json(route('products.store'));

        productMethod.innerHTML = '';

        productModalTitle.textContent =
            'Add Product';

        document.getElementById(
            'productReorder'
        ).value = 0;

        showProductModal();
    });


    document
        .querySelectorAll('.edit-product-btn')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                productForm.reset();
                resetProductUnitChoices();

                productForm.action =
                    button.dataset.updateUrl;

                productMethod.innerHTML =
                    '<input type="hidden" name="_method" value="PUT">';

                productModalTitle.textContent =
                    'Edit Product';

                document.getElementById(
                    'productName'
                ).value =
                    button.dataset.name || '';

                document.getElementById(
                    'productCategory'
                ).value =
                    button.dataset.category || '';

                document.getElementById(
                    'productSupplier'
                ).value =
                    button.dataset.supplier || '';

                document.getElementById(
                    'productUnit'
                ).value =
                    button.dataset.unit || '';

                productPriceInput.value =
                    button.dataset.price || 0;

                productCostInput.value =
                    button.dataset.cost || 0;

                document.getElementById(
                    'productReorder'
                ).value =
                    button.dataset.reorder || 0;

                document.getElementById(
                    'productDescription'
                ).value =
                    button.dataset.description || '';

                editingProductUnits =
                    JSON.parse(button.dataset.units || '[]');

                showProductModal();
            });

        });

    productUnitSelect.addEventListener(
        'change',
        syncProductBaseUnitPrices
    );


    closeProductModal.addEventListener(
        'click',
        hideProductModal
    );

    cancelProductModal.addEventListener(
        'click',
        hideProductModal
    );


    productModal.addEventListener(
        'click',
        function (event) {

            if (event.target === productModal) {
                hideProductModal();
            }
        }
    );


    productForm.addEventListener(
        'submit',
        function () {

            saveProductButton.disabled = true;

            saveProductButton.textContent =
                'Saving...';
        }
    );


    /*
    ============================================================
    PRODUCT VIEW MODAL
    ============================================================
    */

    const productViewModal =
        document.getElementById('productViewModal');

    const closeProductViewModal =
        document.getElementById('closeProductViewModal');

    const cancelProductViewModal =
        document.getElementById('cancelProductViewModal');

    const productViewUnits =
        document.getElementById('productViewUnits');

    function showProductViewModal() {
        productViewModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideProductViewModal() {
        productViewModal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document
        .querySelectorAll('.product-view-btn')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('productViewTitle').textContent =
                    button.dataset.name || 'Product Details';

                document.getElementById('productViewCategory').textContent =
                    button.dataset.category || '—';

                document.getElementById('productViewSupplier').textContent =
                    button.dataset.supplier || '—';

                document.getElementById('productViewStatus').textContent =
                    button.dataset.status || '—';

                document.getElementById('productViewStock').textContent =
                    button.dataset.stock || '0';

                document.getElementById('productViewReorder').textContent =
                    button.dataset.reorder || '0';

                document.getElementById('productViewDescription').textContent =
                    button.dataset.description || '—';

                renderProductUnits(
                    JSON.parse(button.dataset.units || '[]'),
                    productViewUnits
                );

                showProductViewModal();
            });
        });

    closeProductViewModal.addEventListener('click', hideProductViewModal);
    cancelProductViewModal.addEventListener('click', hideProductViewModal);
    productViewModal.addEventListener('click', function (event) {
        if (event.target === productViewModal) {
            hideProductViewModal();
        }
    });


    /*
    ============================================================
    UNITS MODAL
    ============================================================
    */

    const unitsModal =
        document.getElementById('unitsModal');

    const unitsForm =
        document.getElementById('unitsForm');

    const unitsModalTitle =
        document.getElementById('unitsModalTitle');

    const closeUnitsModal =
        document.getElementById('closeUnitsModal');

    const cancelUnitsModal =
        document.getElementById('cancelUnitsModal');

    const saveUnitButton =
        document.getElementById('saveUnitButton');

    const productUnitViewList =
        document.getElementById('productUnitViewList');

    const unitOptionSelect =
        document.getElementById('unitOptionSelect');

    const conversionSelectedQty =
        document.getElementById('conversionSelectedQty');

    const conversionBaseQty =
        document.getElementById('conversionBaseQty');

    const unitConversionFactor =
        document.getElementById('unitConversionFactor');

    const conversionSelectedUnitName =
        document.getElementById('conversionSelectedUnitName');

    const conversionBaseUnitName =
        document.getElementById('conversionBaseUnitName');

    const conversionPreview =
        document.getElementById('conversionPreview');

    let currentBaseUnitName =
        'base unit';

    const originalUnitOptions =
        Array.from(unitOptionSelect.options).map(option => ({
            value: option.value,
            label: option.textContent,
            name: option.dataset.name || option.textContent,
            symbol: option.dataset.symbol || '',
            isPlaceholder: option.value === ''
        }));

    function peso(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderProductUnits(units, target = productUnitViewList) {
        if (!units.length) {
            target.innerHTML =
                '<div class="product-unit-view-empty">No units added yet.</div>';

            return;
        }

        target.innerHTML = units.map(function (unit) {
            const baseLabel = unit.is_base_unit ? ' <span class="unit-chip">Base</span>' : '';
            const statusLabel = unit.is_active ? 'Active' : 'Archived';

            return `
                <div class="product-unit-view-row">
                    <div>
                        <span class="product-unit-view-label">Unit</span>
                        <strong>${escapeHtml(unit.name)}${baseLabel}</strong>
                    </div>
                    <div>
                        <span class="product-unit-view-label">Selling Price</span>
                        <span>${peso(unit.selling_price)}</span>
                    </div>
                    <div>
                        <span class="product-unit-view-label">Purchase Cost</span>
                        <span>${peso(unit.purchase_cost)}</span>
                    </div>
                    <div>
                        <span class="product-unit-view-label">Conversion / Status</span>
                        <span>${unit.conversion_factor}x • ${statusLabel}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function selectedUnitName() {
        const selectedOption =
            unitOptionSelect.options[unitOptionSelect.selectedIndex];

        return (selectedOption?.dataset.name || selectedOption?.textContent || 'selected unit')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function updateConversionFactor() {
        if (!unitOptionSelect.value) {
            unitConversionFactor.value = '1';
            conversionSelectedUnitName.textContent = 'selected unit';
            conversionBaseUnitName.textContent = currentBaseUnitName;
            conversionPreview.textContent = 'Choose a unit first.';

            return;
        }

        const selectedQty =
            Math.max(0.001, Number(conversionSelectedQty.value) || 1);

        const baseQty =
            Math.max(0.001, Number(conversionBaseQty.value) || 1);

        const factor =
            baseQty / selectedQty;

        conversionSelectedQty.value =
            selectedQty;

        conversionBaseQty.value =
            baseQty;

        unitConversionFactor.value =
            factor.toFixed(6);

        conversionSelectedUnitName.textContent =
            selectedUnitName();

        conversionBaseUnitName.textContent =
            currentBaseUnitName;

        conversionPreview.textContent =
            `System will save: 1 ${selectedUnitName()} = ${Number(factor.toFixed(6))} ${currentBaseUnitName}`;
    }

    function refreshUnitDropdown(existingUnits) {
        const usedUnitIds =
            new Set(existingUnits.map(unit => String(unit.unit_id)));

        const availableOptions =
            originalUnitOptions.filter(option => (
                option.isPlaceholder || !usedUnitIds.has(String(option.value))
            ));

        unitOptionSelect.innerHTML = availableOptions.map(option => (
            `<option value="${escapeHtml(option.value)}" data-name="${escapeHtml(option.name)}" data-symbol="${escapeHtml(option.symbol)}">${escapeHtml(option.label.trim())}</option>`
        )).join('');

        const selectableOption =
            Array.from(unitOptionSelect.options).find(option => option.value !== '');

        if (selectableOption) {
            unitOptionSelect.disabled = false;
            unitOptionSelect.value = selectableOption.value;
            saveUnitButton.disabled = false;
        } else {
            unitOptionSelect.disabled = true;
            saveUnitButton.disabled = true;
        }
    }


    function showUnitsModal() {
        unitsModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }


    function hideUnitsModal() {
        unitsModal.classList.remove('show');
        document.body.style.overflow = '';

        saveUnitButton.disabled = false;

        saveUnitButton.textContent =
            'Add Unit Option';
    }


    document
        .querySelectorAll('.units-btn')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    unitsForm.reset();

                    unitsForm.action =
                        button.dataset.storeUrl;

                    currentBaseUnitName =
                        button.dataset.baseUnitName || 'base unit';

                    const existingUnits =
                        JSON.parse(button.dataset.units || '[]');

                    conversionSelectedQty.value =
                        1;

                    conversionBaseQty.value =
                        1;

                    unitsModalTitle.textContent =
                        button.dataset.productName +
                        ' — Units';

                    renderProductUnits(existingUnits);

                    refreshUnitDropdown(existingUnits);

                    updateConversionFactor();

                    showUnitsModal();
                }
            );

        });


    closeUnitsModal.addEventListener(
        'click',
        hideUnitsModal
    );

    cancelUnitsModal.addEventListener(
        'click',
        hideUnitsModal
    );


    unitOptionSelect.addEventListener(
        'change',
        updateConversionFactor
    );

    conversionSelectedQty.addEventListener(
        'input',
        updateConversionFactor
    );

    conversionBaseQty.addEventListener(
        'input',
        updateConversionFactor
    );

    unitsForm.addEventListener(
        'submit',
        function (event) {

            if (!unitOptionSelect.value) {
                event.preventDefault();

                return;
            }

            updateConversionFactor();

            saveUnitButton.disabled = true;

            saveUnitButton.textContent =
                'Adding...';
        }
    );


    unitsModal.addEventListener(
        'click',
        function (event) {

            if (event.target === unitsModal) {
                hideUnitsModal();
            }
        }
    );


    /*
    ============================================================
    ARCHIVE / RESTORE CONFIRMATION MODAL
    ============================================================
    */

    const productStatusModal = document.getElementById('productStatusModal');
    const productStatusTitle = document.getElementById('productStatusTitle');
    const productStatusMessage = document.getElementById('productStatusMessage');
    const cancelProductStatusModal = document.getElementById('cancelProductStatusModal');
    const confirmProductStatusButton = document.getElementById('confirmProductStatusButton');
    let pendingProductStatusForm = null;

    function hideProductStatusModal() {
        productStatusModal.classList.remove('show');
        document.body.style.overflow = '';
        pendingProductStatusForm = null;
        confirmProductStatusButton.disabled = false;
    }

    document.querySelectorAll('.product-status-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            pendingProductStatusForm = form;

            const isArchive = form.dataset.statusAction === 'archive';
            const productName = form.dataset.productName || 'this product';

            productStatusTitle.textContent = isArchive ? 'Archive Product' : 'Restore Product';
            productStatusMessage.textContent = isArchive
                ? `Are you sure you want to archive “${productName}”? Existing records will remain preserved.`
                : `Are you sure you want to restore “${productName}”? The product will become active again.`;

            confirmProductStatusButton.textContent = isArchive ? 'Archive' : 'Restore';
            confirmProductStatusButton.classList.remove('danger', 'success');
            confirmProductStatusButton.classList.add(isArchive ? 'danger' : 'success');

            productStatusModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    });

    cancelProductStatusModal.addEventListener('click', hideProductStatusModal);

    productStatusModal.addEventListener('click', function (event) {
        if (event.target === productStatusModal) {
            hideProductStatusModal();
        }
    });

    confirmProductStatusButton.addEventListener('click', function () {
        if (!pendingProductStatusForm) return;
        confirmProductStatusButton.disabled = true;
        pendingProductStatusForm.submit();
    });


    /*
    ============================================================
    ESC
    ============================================================
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }

            if (productModal.classList.contains('show')) {
                hideProductModal();
            }

            if (productViewModal.classList.contains('show')) {
                hideProductViewModal();
            }

            if (unitsModal.classList.contains('show')) {
                hideUnitsModal();
            }

            if (productStatusModal.classList.contains('show')) {
                hideProductStatusModal();
            }
        }
    );

});

</script>

@endpush

@endsection
