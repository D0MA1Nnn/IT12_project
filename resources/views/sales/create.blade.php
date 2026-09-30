@extends('layouts.app')

@section('title', 'Cashiering')

@section('content')

{{-- SUCCESS / ERROR --}}
@if(session('error'))
    <div class="alert danger" style="margin-bottom:16px;">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert danger" style="margin-bottom:16px;">
        <strong>Please check the transaction.</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('sales.partials.module-tabs', ['activeSalesTab' => 'cashiering'])

<form
    method="POST"
    action="{{ route('sales.store') }}"
    id="saleForm"
>
    @csrf

    {{-- ====================================================== --}}
    {{-- MAIN CASHIER AREA                                      --}}
    {{-- ====================================================== --}}

    <div class="cashier-grid">

        {{-- ================================================== --}}
        {{-- PRODUCTS                                           --}}
        {{-- ================================================== --}}

        <div class="card cashier-products-card">

            <div class="cashier-section-header">

                <h2>
                    Products
                </h2>

                <div class="muted">
                    Each product appears once. Select the selling unit requested by the customer.
                </div>

            </div>


<div class="cashier-filter-card">

        <div class="cashier-filters">

            <div class="cashier-search-wrap">

                <input
                    type="text"
                    id="productSearch"
                    class="input cashier-search"
                    placeholder="Search product, category or unit..."
                    autocomplete="off"
                >

            </div>


            <select
                id="categoryFilter"
                class="input cashier-filter-select"
            >
                <option value="">
                    All Categories
                </option>

                @foreach($categories as $category)

                    <option
                        value="{{ strtolower($category->category_name) }}"
                    >
                        {{ $category->category_name }}
                    </option>

                @endforeach
            </select>
            <button
                type="button"
                class="btn primary"
                id="filterButton"
            >
                Filter
            </button>

        </div>

    </div>


            <div
                id="productScrollArea"
                class="cashier-product-scroll"
            >

                @forelse($products as $product)

                    @php
                        $inventory = $product->inventory;

                        $baseStock = (float) (
                            $inventory?->quantity_on_hand ?? 0
                        );

                        $categoryName =
                            $product->category?->category_name
                            ?? 'Uncategorized';

                        $activeUnits =
                            $product->productUnits
                                ->where('is_active', true)
                                ->values();

                        $baseProductUnit =
                            $activeUnits->firstWhere(
                                'is_base_unit',
                                true
                            )
                            ?? $activeUnits->first();

                        $baseUnitName =
                            $baseProductUnit?->unit?->unit_name
                            ?? 'Unit';

                        $unitNames =
                            $activeUnits
                                ->map(
                                    fn ($productUnit) =>
                                        $productUnit->unit?->unit_name
                                )
                                ->filter()
                                ->implode(' ');

                        $searchText = strtolower(
                            $product->product_name
                            . ' '
                            . $categoryName
                            . ' '
                            . $unitNames
                        );
                    @endphp


                    <div
                        class="cashier-product-row product-filter-row"
                        data-product-id="{{ $product->product_id }}"
                        data-search="{{ $searchText }}"
                        data-category="{{ strtolower($categoryName) }}"
                        data-stock="{{ $baseStock > 0 ? 'in' : 'out' }}"
                    >

                        {{-- PRODUCT INFO --}}

                        <div class="cashier-product-info">

                            <div class="cashier-product-title">
                                {{ $product->product_name }}
                            </div>

                            <div class="muted cashier-product-meta">

                                {{ $categoryName }}

                                <span>•</span>

                                Base stock:

                                <strong>
                                    {{ rtrim(rtrim(number_format($baseStock, 3, '.', ''), '0'), '.') }}
                                </strong>

                                {{ $baseUnitName }}

                            </div>


                            @if($baseStock > 0)

                                <span class="cashier-stock-badge in-stock">
                                    In Stock
                                </span>

                            @else

                                <span class="cashier-stock-badge out-stock">
                                    Out of Stock
                                </span>

                            @endif

                        </div>
                        {{-- SELLING UNITS --}}

                        <div class="cashier-unit-actions">

                            <label>
                                Selling Unit
                            </label>

                            <div class="cashier-unit-buttons">

                                @foreach($activeUnits as $productUnit)

                                    @php
                                        $conversionFactor =
                                            max(
                                                (float) $productUnit->conversion_factor,
                                                0.000001
                                            );

                                        $availableSellingUnits =
                                            $baseStock / $conversionFactor;

                                        $unitData = [
                                            'id' =>
                                                (int) $productUnit->product_unit_id,

                                            'product_id' =>
                                                (int) $product->product_id,

                                            'name' =>
                                                $product->product_name,

                                            'unit' =>
                                                $productUnit->unit?->unit_name
                                                ?? 'Unit',

                                            'price' =>
                                                (float) $productUnit->selling_price,

                                            'factor' =>
                                                $conversionFactor,

                                            'available' =>
                                                $availableSellingUnits,

                                            'base_stock' =>
                                                $baseStock,
                                        ];
                                    @endphp

                                    <button
                                        type="button"
                                        class="cashier-unit-button"
                                        data-unit="{{ json_encode($unitData) }}"
                                        @disabled($baseStock <= 0)
                                    >
                                        <span>
                                            {{ $productUnit->unit?->unit_name ?? 'Unit' }}
                                        </span>

                                        <strong>
                                            ₱{{ number_format((float) $productUnit->selling_price, 2) }}
                                        </strong>
                                    </button>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="cashier-empty-products muted">
                        No active products are available for sale.
                    </div>

                @endforelse


                <div
                    id="noFilterResults"
                    class="cashier-empty-products muted"
                    style="display:none;"
                >
                    No products match your search or filter.
                </div>

            </div>
            <div class="cashier-product-pagination" id="productPagination">
                <button
                    type="button"
                    class="btn light small"
                    id="productPrevPage"
                >
                    Previous
                </button>

                <span class="muted" id="productPageInfo">
                    Page 1 of 1
                </span>

                <button
                    type="button"
                    class="btn light small"
                    id="productNextPage"
                >
                    Next
                </button>
            </div>

        </div>


        {{-- ================================================== --}}
        {{-- CURRENT ORDER                                      --}}
        {{-- ================================================== --}}

        <div class="card cashier-order-card">

            <h2>
                Current Order
            </h2>

            <div class="muted cashier-order-description">
                Pending deliveries do not block the next customer.
                Stock is reserved when the sale is saved.
            </div>


            <div id="order">

                <div class="muted">
                    No items added.
                </div>

            </div>


            <hr class="cashier-divider">


            <div class="cashier-total">

                <strong>
                    Total
                </strong>

                <strong id="total">
                    ₱0.00
                </strong>

            </div>


            <div class="field cashier-payment">

                <label>
                    Customer Payment
                </label>

                <input
                    class="input"
                    type="number"
                    step="0.01"
                    min="0"
                    name="payment"
                    id="payment"
                    value="{{ old('payment') }}"
                    required
                    placeholder="0.00"
                >

            </div>


            <div class="cashier-change-row">

                <span class="muted">
                    Change
                </span>

                <strong id="change">
                    ₱0.00
                </strong>

            </div>


            <label class="cashier-delivery-check">

                <input
                    type="checkbox"
                    name="delivery_required"
                    id="delivery"
                    value="1"
                    @checked(old('delivery_required'))
                >

                <span>
                    Delivery required
                </span>

            </label>


            <div class="muted cashier-delivery-help">

                If checked, the order is saved as
                <strong>For Delivery</strong>.

                You can immediately serve the next customer.

            </div>

            <div
                class="cashier-delivery-fields"
                id="deliveryFields"
            >

                <div class="field">
                    <label>Customer Name</label>
                    <input
                        class="input"
                        type="text"
                        name="customer_name"
                        id="customerName"
                        value="{{ old('customer_name') }}"
                        autocomplete="name"
                    >
                </div>

                <div class="field">
                    <label>Contact Number</label>
                    <input
                        class="input"
                        type="text"
                        name="customer_contact_number"
                        id="customerContactNumber"
                        value="{{ old('customer_contact_number') }}"
                        autocomplete="tel"
                    >
                </div>

                <div class="field cashier-delivery-address">
                    <label>Address</label>
                    <textarea
                        name="delivery_address"
                        id="deliveryAddress"
                        rows="3"
                    >{{ old('delivery_address') }}</textarea>
                </div>

            </div>


            <button
                type="button"
                id="confirmSaleButton"
                class="btn success cashier-review-button"
            >
                CONFIRM SALE
            </button>

        </div>

    </div>

</form>


{{-- ========================================================== --}}
{{-- SALE RECEIPT MODAL                                          --}}
{{-- ========================================================== --}}

@php
    $receiptPayment = $completedSale
        ? (float) session('receipt_payment', $completedSale->total_amount)
        : 0;

    $receiptChange = $completedSale
        ? (float) session('receipt_change', max(0, $receiptPayment - (float) $completedSale->total_amount))
        : 0;
@endphp

<div
    id="saleModal"
    class="sale-modal {{ $completedSale ? 'open' : '' }}"
>

    <div class="sale-modal-card">

        <div class="sale-modal-head">

            <div>
                <div class="sale-receipt-store">
                    SENADOR COCO
                </div>

                <div class="sale-receipt-store-subtitle">
                    Lumber & Construction Supplies
                </div>

                <h2>
                    Transaction Successfully Completed
                </h2>

                <div class="muted">
                    @if($completedSale)
                        Receipt for SALE-{{ str_pad($completedSale->sale_id, 4, '0', STR_PAD_LEFT) }}
                    @else
                        Receipt details will appear after saving.
                    @endif
                </div>

            </div>


        </div>

        @if($completedSale)

            <div class="sale-receipt-meta">
                <div>
                    <span>Date / Time</span>
                    <strong>{{ $completedSale->sale_date->format('m/d/Y g:i A') }}</strong>
                </div>

                <div>
                    <span>Recorded By</span>
                    <strong>{{ $completedSale->user?->username ?? '—' }}</strong>
                </div>
            </div>


            <div id="modalItems">
                @foreach($completedSale->items as $item)
                    @php
                        $productUnit = $item->productUnit;
                        $product = $productUnit?->product;
                        $unit = $productUnit?->unit;
                        $quantity = rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.');
                    @endphp

                    <div class="sale-modal-item">
                        <div>
                            <strong>{{ $product?->product_name ?? 'Product' }}</strong>
                            <div class="muted">
                                {{ $quantity }}
                                {{ $unit?->unit_name ?? 'Unit' }}
                                ×
                                ₱{{ number_format((float) $item->unit_price, 2) }}
                            </div>
                        </div>

                        <strong>₱{{ number_format((float) $item->subtotal, 2) }}</strong>
                    </div>
                @endforeach
            </div>


            <div class="sale-summary">

                <div>
                    <span>Total</span>
                    <strong id="mTotal">₱{{ number_format((float) $completedSale->total_amount, 2) }}</strong>
                </div>

                <div>
                    <span>Payment</span>
                    <strong id="mPayment">₱{{ number_format($receiptPayment, 2) }}</strong>
                </div>

                <div>
                    <span>Change</span>
                    <strong id="mChange" class="sale-green">
                        ₱{{ number_format($receiptChange, 2) }}
                    </strong>
                </div>

            </div>


            @if($completedSale->delivery_required)
                <div
                    class="sale-delivery-summary"
                    id="mDeliveryDetails"
                >
                    <strong>Delivery Details</strong>
                    <div>
                        <span>Customer Name:</span>
                        {{ $completedSale->customer_name }}
                    </div>
                    <div>
                        <span>Contact Number:</span>
                        {{ $completedSale->customer_contact_number }}
                    </div>
                    <div>
                        <span>Address:</span>
                        {{ $completedSale->delivery_address }}
                    </div>
                </div>
            @endif

        @endif


        <div class="sale-modal-actions">

            <button
                type="button"
                class="btn light"
                id="cancelModal"
            >
                Close
            </button>

            <button
                type="button"
                class="btn success"
                onclick="window.print()"
            >
                Print Receipt
            </button>

        </div>

    </div>

</div>


<style>

    /* ====================================================== */
    /* FILTERS                                                */
    /* ====================================================== */

    .cashier-filter-card {
        margin-top: 14px;
        padding: 14px;
        border: 1px solid #e8edf5;
        border-radius: 10px;
        background: #ffffff;
    }

    .cashier-filters {
        display: grid;
        grid-template-columns:
            minmax(260px, 1fr)
            180px
            auto;
        gap: 10px;
        align-items: center;
    }

    .cashier-search-wrap {
        position: relative;
        min-width: 0;
    }

    .cashier-search {
        width: 100%;
        padding-left: 14px !important;
    }

    .cashier-filter-select {
        width: 100%;
    }


    /* ====================================================== */
    /* MAIN LAYOUT                                            */
    /* ====================================================== */

    .cashier-grid {
        display: grid;
        grid-template-columns:
            minmax(0, 1.55fr)
            minmax(340px, 1fr);
        gap: 18px;
        align-items: start;
    }

    .cashier-products-card,
    .cashier-order-card {
        min-width: 0;
    }


    /* ====================================================== */
    /* PRODUCTS                                               */
    /* ====================================================== */

    .cashier-section-header h2,
    .cashier-order-card h2 {
        margin-top: 0;
        margin-bottom: 5px;
    }


    .cashier-product-scroll {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
        max-height: 55vh;
        overflow-y: auto;
        padding-right: 6px;
    }


    .cashier-product-row {
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-height: 168px;
        padding: 16px;
        border: 1px solid #e9edf3;
        border-radius: 10px;
        background: #ffffff;
    }





    .cashier-product-info {
        min-width: 0;
        align-self: stretch;
        text-align: left;
    }


    .cashier-product-title {
        color: #111827;
        font-weight: 700;
        font-size: 15px;
    }


    .cashier-product-meta {
        margin-top: 4px;
        font-size: 11px;
    }


    .cashier-stock-badge {
        display: inline-flex;

        margin-top: 7px;

        padding: 4px 8px;

        border-radius: 999px;

        font-size: 10px;
        font-weight: 700;
    }


    .cashier-stock-badge.in-stock {
        color: #148647;
        background: #e8f8ef;
    }


    .cashier-stock-badge.out-stock {
        color: #d13e3e;
        background: #fff0f0;
    }
    .cashier-unit-actions {
        min-width: 0;
        margin-top: auto;
    }


    .cashier-unit-actions label {
        display: block;
        margin-bottom: 8px;
        color: #778397;
        font-size: 11px;
        font-weight: 700;
    }


    .cashier-unit-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }


    .cashier-product-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
    }


    .cashier-unit-button {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid #dfe5ee;
        border-radius: 8px;
        background: #f8fafc;
        color: #182033;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
    }


    .cashier-unit-button:hover:not(:disabled) {
        border-color: #2468ee;
        background: #f4f8ff;
        color: #2468ee;
    }


    .cashier-unit-button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }


    .cashier-unit-button strong {
        font-size: 12px;
    }


    .cashier-order-stepper {
        display: grid;
        grid-template-columns: 30px minmax(34px, 1fr) 30px;
        align-items: center;
        gap: 6px;
    }


    .cashier-quantity-button {
        height: 34px;
        border: 1px solid #d9e0ea;
        border-radius: 8px;
        background: #ffffff;
        color: #182033;
        cursor: pointer;
        font-size: 16px;
        font-weight: 800;
    }


    .cashier-quantity-button:hover:not(:disabled) {
        border-color: #2468ee;
        color: #2468ee;
    }


    .cashier-order-qty-text {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        border: 1px solid #dfe5ee;
        border-radius: 8px;
        background: #f8fafc;
        color: #182033;
        font-weight: 700;
    }


    .cashier-empty-products {
        padding: 28px 10px;
        text-align: center;
    }


    /* ====================================================== */
    /* CURRENT ORDER                                          */
    /* ====================================================== */

    .cashier-order-card {
        position: sticky;
        top: 16px;
    }


    .cashier-order-description {
        margin-bottom: 18px;
        line-height: 1.5;
    }


    .cashier-order-line {
        display: grid;

        grid-template-columns:
            minmax(0, 1fr)
            116px
            95px
            34px;

        gap: 8px;
        align-items: center;

        padding: 11px 0;

        border-bottom: 1px solid #edf0f4;
    }


    .cashier-order-product {
        min-width: 0;
    }


    .cashier-order-product strong {
        display: block;
    }


    .cashier-order-unit {
        margin-top: 3px;
        font-size: 11px;
    }


    .cashier-order-amount {
        text-align: right;
    }


    .cashier-remove {
        width: 32px;
        height: 32px;

        border: 0;
        border-radius: 7px;

        color: #ca3737;
        background: #fff0f0;

        cursor: pointer;
        font-size: 18px;
    }


    .cashier-divider {
        margin: 22px 0;

        border: 0;
        border-top: 1px solid #e9edf3;
    }


    .cashier-total {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }


    .cashier-total #total {
        font-size: 25px;
    }


    .cashier-payment {
        margin-top: 22px;
    }


    .cashier-change-row {
        display: flex;
        align-items: center;
        justify-content: space-between;

        margin: 16px 0;
    }


    #change {
        color: #11a651;
        font-size: 19px;
    }


    .cashier-delivery-check {
        display: flex;
        align-items: center;
        gap: 9px;

        font-size: 13px;
        font-weight: 600;

        cursor: pointer;
    }


    .cashier-delivery-check input {
        width: 16px;
        height: 16px;
    }


    .cashier-delivery-help {
        margin-top: 8px;

        font-size: 11px;
        line-height: 1.5;
    }


    .cashier-delivery-fields {
        display: none;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px;
    }


    .cashier-delivery-fields.open {
        display: grid;
    }


    .cashier-delivery-address {
        grid-column: 1 / -1;
    }


    .cashier-delivery-fields textarea {
        width: 100%;
        resize: vertical;
    }


    .cashier-review-button {
        width: 100%;
        margin-top: 22px;
    }


    /* ====================================================== */
    /* MODAL                                                  */
    /* ====================================================== */

    .sale-modal {
        display: none;

        position: fixed;
        inset: 0;

        z-index: 9999;

        align-items: center;
        justify-content: center;

        padding: 20px;

        background: rgba(8, 18, 34, .60);
    }


    .sale-modal.open {
        display: flex;
    }


    .sale-modal-card {
        width: min(430px, 100%);

        overflow: hidden;

        border-radius: 8px;

        background: #ffffff;

        box-shadow:
            0 24px 70px
            rgba(0, 0, 0, .25);
    }


    .sale-modal-head {
        display: flex;
        justify-content: space-between;
        gap: 18px;

        padding: 22px 24px 18px;

        border-bottom:
            1px dashed #cbd5e1;
    }


    .sale-receipt-store {
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }


    .sale-receipt-store-subtitle {
        margin-top: 2px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }


    .sale-modal-head h2 {
        margin: 14px 0 4px 0;
        font-size: 20px;
    }


    .sale-modal-close {
        border: 0;

        color: #718096;
        background: transparent;

        font-size: 27px;

        cursor: pointer;
    }


    #modalItems {
        max-height: 260px;
        overflow-y: auto;

        padding: 10px 24px;
    }


    .sale-modal-item {
        display: flex;
        justify-content: space-between;
        gap: 14px;

        padding: 13px 0;

        border-bottom:
            1px dashed #d8dee8;
    }

    .sale-receipt-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        padding: 16px 24px 8px;
    }


    .sale-receipt-meta > div {
        padding: 12px;
        border: 1px dashed #d8dee8;
        border-radius: 8px;
        background: #fbfdff;
    }


    .sale-receipt-meta span {
        display: block;
        margin-bottom: 4px;
        color: #718096;
        font-size: 12px;
    }


    .sale-summary {
        margin: 0 24px;
        padding: 14px 0;
        border-bottom: 1px dashed #cbd5e1;
    }


    .sale-delivery-summary {
        margin: 14px 24px 0;
        padding: 12px 0 0;
        border-top: 1px dashed #cbd5e1;
        background: #ffffff;
        color: #182033;
        font-size: 12px;
        line-height: 1.6;
    }


    .sale-delivery-summary strong {
        display: block;
        margin-bottom: 6px;
        text-transform: uppercase;
    }


    .sale-delivery-summary span {
        font-weight: 800;
    }


    .cashier-order-qty-input {
        width: 54px;
        min-height: 34px;
        border: 1px solid #d9e1ec;
        border-radius: 8px;
        background: #f8fafc;
        color: #0f172a;
        text-align: center;
        font-weight: 800;
    }


    .sale-summary > div {
        display: flex;
        justify-content: space-between;
        gap: 14px;

        padding: 6px 0;
    }


    .sale-green {
        color: #11a651;
    }


    .sale-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;

        padding: 18px 24px;

        background: #ffffff;
    }


    @media print {

        .sidebar,
        .sales-module-tabs,
        .cashier-grid,
        .sale-modal-close,
        .sale-modal-actions {
            display: none !important;
        }

        .sale-modal {
            position: static;
            display: block !important;
            padding: 0;
            background: #ffffff;
        }

        .sale-modal-card {
            width: 100%;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }

    }


    /* ====================================================== */
    /* RESPONSIVE                                             */
    /* ====================================================== */

    @media (max-width: 1050px) {

        .cashier-product-row {
            grid-template-columns:
                minmax(150px, 1fr)
                minmax(170px, 1fr)
                82px
                82px;
        }

    }


    @media (max-width: 900px) {

        .cashier-grid {
            grid-template-columns: 1fr;
        }

        .cashier-order-card {
            position: static;
        }

        .cashier-filters {
            grid-template-columns:
                minmax(200px, 1fr)
                160px
                140px
                auto;
        }

    }


    @media (max-width: 700px) {

        .cashier-filters {
            grid-template-columns: 1fr 1fr;
        }

        .cashier-search-wrap {
            grid-column: 1 / -1;
        }

        .cashier-product-row {
            grid-template-columns: 1fr 1fr;
        }

        .cashier-product-info {
            grid-column: 1 / -1;
        }

    }


    @media (max-width: 500px) {

        .cashier-filters {
            grid-template-columns: 1fr;
        }

        .cashier-search-wrap {
            grid-column: auto;
        }

        .cashier-product-row {
            grid-template-columns: 1fr;
        }

        .cashier-product-info {
            grid-column: auto;
        }

        .cashier-order-line {
            grid-template-columns:
                minmax(0, 1fr)
                80px
                34px;
        }

        .cashier-order-amount {
            display: none;
        }

        .sale-modal-actions {
            flex-direction: column-reverse;
        }

        .sale-modal-actions .btn {
            width: 100%;
        }

    }

</style>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const items = {};


    /* ====================================================== */
    /* HELPERS                                                */
    /* ====================================================== */

    function byId(id) {
        return document.getElementById(id);
    }


    function money(value) {

        return '₱' + Number(value || 0).toLocaleString(
            'en-PH',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    }


    function cleanNumber(value) {

        const number = Number(value || 0);

        if (Number.isInteger(number)) {
            return String(number);
        }

        return Number(
            number.toFixed(3)
        ).toString();

    }


    function escapeHtml(value) {

        const div =
            document.createElement('div');

        div.textContent =
            String(value ?? '');

        return div.innerHTML;

    }


    function calculateTotal() {

        return Object
            .values(items)
            .reduce(
                function (sum, item) {

                    return sum +
                        (
                            Number(item.price) *
                            Number(item.qty)
                        );

                },
                0
            );

    }


    /* ====================================================== */
    /* PAYMENT                                                */
    /* ====================================================== */

    function updatePayment() {

        const total =
            calculateTotal();

        const payment =
            Math.max(
                0,
                Number(
                    byId('payment').value || 0
                )
            );


        byId('total').textContent =
            money(total);


        byId('change').textContent =
            money(
                Math.max(
                    0,
                    payment - total
                )
            );

    }


    /* ====================================================== */
    /* CART                                                   */
    /* ====================================================== */

    function renderOrder() {

        const orderItems =
            Object.values(items);


        if (orderItems.length === 0) {

            byId('order').innerHTML = `
                <div class="muted">
                    No items added.
                </div>
            `;

            updatePayment();

            return;

        }


        byId('order').innerHTML =
            orderItems.map(
                function (item, index) {

                    return `

                        <div class="cashier-order-line">

                            <div class="cashier-order-product">

                                <strong>
                                    ${escapeHtml(item.name)}
                                </strong>

                                <div class="muted cashier-order-unit">

                                    ${escapeHtml(item.unit)}
                                    •
                                    ${money(item.price)} each

                                </div>

                            </div>
                            <div class="cashier-order-stepper">
                                <button
                                    type="button"
                                    class="cashier-quantity-button"
                                    data-order-decrease="${item.id}"
                                >
                                    -
                                </button>

                                <input
                                    type="number"
                                    class="cashier-order-qty-input"
                                    data-order-quantity="${item.id}"
                                    value="${cleanNumber(item.qty)}"
                                    min="1"
                                    step="1"
                                >

                                <button
                                    type="button"
                                    class="cashier-quantity-button"
                                    data-order-increase="${item.id}"
                                >
                                    +
                                </button>
                            </div>

                            <input
                                type="hidden"
                                name="items[${index}][quantity]"
                                value="${item.qty}"
                            >


                            <div class="cashier-order-amount">

                                <strong>
                                    ${money(
                                        Number(item.price) *
                                        Number(item.qty)
                                    )}
                                </strong>


                                <input
                                    type="hidden"
                                    name="items[${index}][product_unit_id]"
                                    value="${item.id}"
                                >

                            </div>


                            <button
                                type="button"
                                class="cashier-remove"
                                data-remove="${item.id}"
                                title="Remove item"
                            >
                                &times;
                            </button>

                        </div>

                    `;

                }
            ).join('');


        updatePayment();

    }


    /* ====================================================== */
    /* CHECK BASE STOCK ACROSS DIFFERENT SELLING UNITS        */
    /* ====================================================== */

    function calculateUsedBaseStock(
        productId,
        excludeUnitId = null
    ) {

        let used = 0;


        Object
            .values(items)
            .forEach(
                function (item) {

                    if (
                        Number(item.product_id) !==
                        Number(productId)
                    ) {
                        return;
                    }


                    if (
                        excludeUnitId !== null &&
                        String(item.id) ===
                        String(excludeUnitId)
                    ) {
                        return;
                    }


                    used +=
                        Number(item.qty) *
                        Number(item.factor);

                }
            );


        return used;

    }


    /* ====================================================== */
    /* ADD SELLING UNIT                                       */
    /* ====================================================== */

    function changeUnitQuantity(unit, changeBy) {

        const unitId =
            String(unit.id);

        const existingQuantity =
            items[unitId]
                ? Number(items[unitId].qty)
                : 0;

        const newQuantity =
            existingQuantity +
            Number(changeBy);


        if (newQuantity <= 0) {
            delete items[unitId];
            renderOrder();
            return;
        }


        if (
            newQuantity >
            Number(unit.available) +
            0.000001
        ) {
            alert(
                'Only ' +
                cleanNumber(unit.available) +
                ' ' +
                unit.unit +
                ' available.'
            );

            return;
        }


        const usedByOtherUnits =
            calculateUsedBaseStock(
                unit.product_id,
                unitId
            );

        const requiredByThisUnit =
            newQuantity *
            Number(unit.factor);


        if (
            usedByOtherUnits +
            requiredByThisUnit >
            Number(unit.base_stock) +
            0.000001
        ) {
            alert(
                'The selected quantity exceeds the available stock for ' +
                unit.name +
                '.'
            );

            return;
        }


        items[unitId] = {
            id: Number(unit.id),
            product_id: Number(unit.product_id),
            name: unit.name,
            unit: unit.unit,
            price: Number(unit.price),
            factor: Number(unit.factor),
            available: Number(unit.available),
            base_stock: Number(unit.base_stock),
            qty: newQuantity
        };


        renderOrder();

    }


    function readUnitFromButton(button) {

        try {
            return JSON.parse(
                button.dataset.unit
            );
        }
        catch (error) {
            alert(
                'Unable to read the selected selling unit.'
            );

            return null;
        }

    }


    document
        .querySelectorAll(
            '.cashier-unit-button'
        )
        .forEach(
            function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        const unit =
                            readUnitFromButton(button);

                        if (unit) {
                            changeUnitQuantity(unit, 1);
                        }
                    }
                );
            }
        );


    byId('order')
        .addEventListener(
            'change',
            function (event) {

                const quantityInput =
                    event.target.closest(
                        '[data-order-quantity]'
                    );

                if (!quantityInput) {
                    return;
                }

                const item =
                    items[quantityInput.dataset.orderQuantity];

                if (!item) {
                    return;
                }

                const requestedQuantity =
                    Number(quantityInput.value || 0);

                const currentQuantity =
                    Number(item.qty);

                changeUnitQuantity(
                    item,
                    requestedQuantity - currentQuantity
                );

            }
        );

    /* ====================================================== */
    /* CURRENT ORDER CONTROLS                                 */
    /* ====================================================== */

    byId('order')
        .addEventListener(
            'click',
            function (event) {

                const increaseButton =
                    event.target.closest(
                        '[data-order-increase]'
                    );

                if (increaseButton) {
                    const item =
                        items[increaseButton.dataset.orderIncrease];

                    if (item) {
                        changeUnitQuantity(item, 1);
                    }

                    return;
                }


                const decreaseButton =
                    event.target.closest(
                        '[data-order-decrease]'
                    );

                if (decreaseButton) {
                    const item =
                        items[decreaseButton.dataset.orderDecrease];

                    if (item) {
                        changeUnitQuantity(item, -1);
                    }

                    return;
                }


                const removeButton =
                    event.target.closest(
                        '[data-remove]'
                    );

                if (!removeButton) {
                    return;
                }


                delete items[
                    removeButton.dataset.remove
                ];


                renderOrder();

            }
        );

    /* ====================================================== */
    /* FILTER / PAGINATE PRODUCTS                             */
    /* ====================================================== */

    const productsPerPage = 10;
    let currentProductPage = 1;
    let filteredProductRows = [];


    function renderProductPage() {

        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    filteredProductRows.length /
                    productsPerPage
                )
            );


        currentProductPage =
            Math.min(
                currentProductPage,
                totalPages
            );


        const start =
            (currentProductPage - 1) *
            productsPerPage;

        const end =
            start +
            productsPerPage;


        document
            .querySelectorAll(
                '.product-filter-row'
            )
            .forEach(
                function (row) {
                    row.style.display = 'none';
                }
            );


        filteredProductRows
            .slice(start, end)
            .forEach(
                function (row) {
                    row.style.display = 'flex';
                }
            );


        byId('noFilterResults')
            .style.display =
                filteredProductRows.length === 0
                    ? 'block'
                    : 'none';


        byId('productPagination')
            .style.display =
                filteredProductRows.length > productsPerPage
                    ? 'flex'
                    : 'none';


        byId('productPageInfo')
            .textContent =
                'Page ' +
                currentProductPage +
                ' of ' +
                totalPages;


        byId('productPrevPage').disabled =
            currentProductPage <= 1;

        byId('productNextPage').disabled =
            currentProductPage >= totalPages;

    }


    function filterProducts() {

        const search =
            byId('productSearch')
                .value
                .trim()
                .toLowerCase();


        const category =
            byId('categoryFilter')
                .value
                .trim()
                .toLowerCase();


        filteredProductRows =
            Array.from(
                document.querySelectorAll(
                    '.product-filter-row'
                )
            ).filter(
                function (row) {

                    const matchesSearch =
                        search === '' ||
                        row.dataset.search.includes(
                            search
                        );


                    const matchesCategory =
                        category === '' ||
                        row.dataset.category ===
                        category;


                    return matchesSearch && matchesCategory;

                }
            );


        currentProductPage = 1;
        renderProductPage();

    }


    byId('productSearch')
        .addEventListener(
            'input',
            filterProducts
        );


    byId('categoryFilter')
        .addEventListener(
            'change',
            filterProducts
        );


    byId('filterButton')
        .addEventListener(
            'click',
            filterProducts
        );


    byId('productPrevPage')
        .addEventListener(
            'click',
            function () {
                currentProductPage--;
                renderProductPage();
            }
        );


    byId('productNextPage')
        .addEventListener(
            'click',
            function () {
                currentProductPage++;
                renderProductPage();
            }
        );

    /* ====================================================== */
    /* PAYMENT                                                */
    /* ====================================================== */

    byId('payment')
        .addEventListener(
            'input',
            updatePayment
        );


    function toggleDeliveryFields() {

        const checked =
            byId('delivery').checked;


        byId('deliveryFields')
            .classList
            .toggle('open', checked);


        [
            byId('customerName'),
            byId('customerContactNumber'),
            byId('deliveryAddress')
        ].forEach(
            function (field) {
                field.required = checked;
            }
        );

    }


    byId('delivery')
        .addEventListener(
            'change',
            toggleDeliveryFields
        );


    /* ====================================================== */
    /* CONFIRM SALE / SAVE TRANSACTION                         */
    /* ====================================================== */

    function closeModal() {

        const saleModal =
            byId('saleModal');

        if (!saleModal) {
            return;
        }

        saleModal
            .classList
            .remove('open');

    }


    byId('confirmSaleButton')
        .addEventListener(
            'click',
            function () {

                const orderItems =
                    Object.values(items);


                if (
                    orderItems.length === 0
                ) {

                    alert(
                        'Add at least one product to the order.'
                    );

                    return;

                }


                toggleDeliveryFields();


                if (!byId('saleForm').reportValidity()) {
                    return;
                }


                const total =
                    calculateTotal();


                const payment =
                    Number(
                        byId('payment').value || 0
                    );


                if (
                    !Number.isFinite(payment) ||
                    payment < total
                ) {

                    alert(
                        'Customer payment is less than the total amount.'
                    );

                    byId('payment').focus();

                    return;

                }


                const button =
                    byId('confirmSaleButton');


                button.disabled =
                    true;


                button.textContent =
                    'PROCESSING...';


                byId('saleForm')
                    .submit();

            }
        );


    /* ====================================================== */
    /* CLOSE RECEIPT MODAL                                    */
    /* ====================================================== */

    const closeModalButton =
        byId('closeModal');

    const cancelModalButton =
        byId('cancelModal');

    const saleModal =
        byId('saleModal');


    if (closeModalButton) {
        closeModalButton
            .addEventListener(
                'click',
                closeModal
            );
    }


    if (cancelModalButton) {
        cancelModalButton
            .addEventListener(
                'click',
                closeModal
            );
    }


    if (saleModal) {
        saleModal
            .addEventListener(
                'click',
                function (event) {

                    if (
                        event.target ===
                        saleModal
                    ) {
                        closeModal();
                    }

                }
            );
    }


    /* ====================================================== */
    /* INITIALIZE                                             */
    /* ====================================================== */

    renderOrder();
    filterProducts();
    toggleDeliveryFields();

});

</script>

@endsection
