@extends('layouts.app')

@section('title', 'Cashiering')

@section('content')

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

        </div>

    </div>


            <div
                id="productScrollArea"
                class="cashier-product-scroll"
            >

                @forelse($productGroups as $productGroup)
                    @php
                        $groupSearchText = mb_strtolower($productGroup->map(fn ($sizeProduct) =>
                            $sizeProduct->product_name.' '.$sizeProduct->groupLabel().' '.($sizeProduct->size_name ?? '').' '.
                            ($sizeProduct->category?->category_name ?? '').' '.
                            $sizeProduct->productUnits->map(fn ($unit) => $unit->unit?->unit_name)->implode(' ')
                        )->implode(' '));
                    @endphp
                    <div class="cashier-product-group" data-size-group="{{ $productGroup->first()->product_id }}"
                        data-search="{{ $groupSearchText }}"
                        data-category="{{ mb_strtolower($productGroup->first()->category?->category_name ?? 'Uncategorized') }}">
                @foreach($productGroup as $product)

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
                            );

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
                        data-base-stock="{{ $baseStock }}"
                        data-search="{{ $searchText }}"
                        data-category="{{ strtolower($categoryName) }}"
                        data-stock="{{ $baseStock > 0 ? 'in' : 'out' }}"
                        data-size-product="{{ $product->product_id }}"
                        @if(! $loop->first) hidden @endif
                    >

                        {{-- PRODUCT INFO --}}

                        <div class="cashier-product-info">

                            <div class="cashier-product-title">
                                {{ $product->groupLabel() }}
                            </div>

                            <div class="muted cashier-product-meta">

                                {{ $categoryName }}

                                <span>•</span>

                                Available stock:

                                <strong data-available-stock>
                                    {{ rtrim(rtrim(number_format($baseStock, 8, '.', ''), '0'), '.') }}
                                </strong>

                                {{ $baseUnitName }}

                            </div>


                            @if($baseStock > 0)

                                <span class="cashier-stock-badge in-stock" data-stock-badge>
                                    In Stock
                                </span>

                            @else

                                <span class="cashier-stock-badge out-stock" data-stock-badge>
                                    Out of Stock
                                </span>

                            @endif

                            @if($product->size_name !== null)
                                <label class="cashier-size-label">
                                    Size
                                    <select data-size-selector aria-label="Size for {{ $product->groupLabel() }}">
                                        @foreach($productGroup as $sizeOption)
                                            <option value="{{ $sizeOption->product_id }}" @selected($sizeOption->product_id === $product->product_id)>
                                                {{ $sizeOption->size_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
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
                                                0.00000001
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
                                                round((float) ($baseProductUnit?->selling_price ?? 0) * $conversionFactor, 2),

                                            'factor' =>
                                                $conversionFactor,

                                            'available' =>
                                                $availableSellingUnits,

                                            'base_stock' =>
                                                $baseStock,

                                            'base_id' =>
                                                $baseProductUnit?->product_unit_id,

                                            'base_unit' =>
                                                $baseUnitName,

                                            'base_unit_id' => $baseProductUnit?->unit_id,

                                            'base_price' =>
                                                (float) ($baseProductUnit?->selling_price ?? 0),
                                        ];
                                    @endphp

                                    <button
                                        type="button"
                                        class="cashier-unit-button"
                                        data-unit="{{ json_encode($unitData) }}"
                                        @disabled($baseStock <= 0 || ! $baseProductUnit || (float) $baseProductUnit->conversion_factor !== 1.0 || (float) $productUnit->conversion_factor <= 0)
                                    >
                                        <span>
                                            {{ $productUnit->unit?->unit_name ?? 'Unit' }}
                                        </span>

                                        <strong>
                                            ₱{{ number_format($unitData['price'], 2) }}
                                        </strong>
                                    </button>

                                @endforeach

                            </div>

                        </div>

                    </div>
                @endforeach
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
                <label for="paymentMethod">Payment Option</label>
                <select class="input" name="payment_method" id="paymentMethod">
                    <option value="PAY_NOW" @selected(old('payment_method', 'PAY_NOW') === 'PAY_NOW')>Pay now</option>
                    <option value="COD" @selected(old('payment_method') === 'COD')>Cash on delivery (COD)</option>
                </select>
            </div>

            <div class="field cashier-payment" id="paymentField">

                <label>
                    Customer Payment
                </label>

                <input
                    class="input"
                    type="text"
                    inputmode="decimal"
                    pattern="^\d+(\.\d{1,2})?$"
                    data-decimal-places="2"
                    name="payment"
                    id="payment"
                    value="{{ old('payment') }}"
                    required
                    placeholder="0.00"
                >

            </div>


            <div class="cashier-change-row" id="changeRow">

                <span class="muted">
                    Change
                </span>

                <strong id="change">
                    ₱0.00
                </strong>

            </div>

            <div class="cashier-change-row" id="codAmountDueRow" hidden>
                <span class="muted">Amount Due on Delivery</span>
                <strong id="codAmountDue">₱0.00</strong>
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


            <div
                class="cashier-delivery-fields"
                id="deliveryFields"
            >

                <div class="field cashier-delivery-fee">
                    <label>Delivery Fee</label>
                    <input
                        class="input"
                        type="text"
                        inputmode="decimal"
                        pattern="^\d+(\.\d{1,2})?$"
                        data-decimal-places="2"
                        name="delivery_fee"
                        id="deliveryFee"
                        value="{{ old('delivery_fee') }}"
                        placeholder="0.00"
                    >
                </div>

                <div class="field">
                    <label>Customer Name</label>
                    <input
                        class="input"
                        type="text"
                        name="customer_name"
                        id="customerName"
                        value="{{ old('customer_name') }}"
                        autocomplete="name"
                        maxlength="30"
                    >
                </div>

                <div class="field">
                    <label>Contact Number</label>
                    <input
                        class="input"
                        type="tel"
                        name="customer_contact_number"
                        id="customerContactNumber"
                        value="{{ old('customer_contact_number') }}"
                        autocomplete="tel"
                        maxlength="11"
                        minlength="11"
                        inputmode="numeric"
                        pattern="09[0-9]{9}"
                        data-digits-only
                        title="Contact number must start with 09 and be exactly 11 digits."
                    >
                </div>

                <div class="field cashier-delivery-address">
                    <label>Address</label>
                    <textarea
                        name="delivery_address"
                        id="deliveryAddress"
                        rows="3"
                        maxlength="60"
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
    $receiptPayment = $completedSale?->receivedPayment() ?? 0;
    $receiptChange = $completedSale?->paymentChange() ?? 0;
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

                <div class="sale-receipt-shop-address">
                    km 5, encabo st, guadalupe village, matina crossing, davao city
                </div>

                <h2>
                    Sales Invoice
                </h2>

            </div>


        </div>

        @if($completedSale)

            <div class="sale-receipt-meta">
                <span>Invoice No.</span>
                <strong>SALE-{{ str_pad($completedSale->sale_id, 4, '0', STR_PAD_LEFT) }}</strong>

                <span>Date / Time</span>
                <strong>{{ $completedSale->sale_date->format('m/d/Y g:i A') }}</strong>

                <span>Payment Option</span>
                <strong>{{ $completedSale->payment_method === 'COD' ? 'Cash on Delivery (COD)' : 'Pay now' }}</strong>

            </div>


            <div id="modalItems">
                <div class="sale-modal-item sale-modal-item-header">
                    <span>Qty</span>
                    <span>Unit</span>
                    <span>Name</span>
                    <span>Price</span>
                    <span>Amount</span>
                </div>

                @foreach($completedSale->items as $item)
                    @php
                        $productUnit = $item->productUnit;
                        $product = $productUnit?->product;
                        $unit = $productUnit?->unit;
                        $quantity = rtrim(rtrim(number_format($item->sellingQuantity(), 8, '.', ''), '0'), '.');
                    @endphp

                    <div class="sale-modal-item">
                        <span>{{ $quantity }}</span>
                        <span>{{ $item->sellingUnitName() }}</span>
                        <strong>{{ $product?->product_name ?? 'Product' }}</strong>
                        <span>₱{{ number_format($item->sellingUnitPrice(), 2) }}</span>
                        <strong>₱{{ number_format((float) $item->subtotal, 2) }}</strong>
                    </div>
                @endforeach
            </div>


            <div class="sale-summary">

                @if($completedSale->delivery_required)
                    <div>
                        <span>Delivery Fee</span>
                        <strong>₱{{ number_format((float) $completedSale->delivery_fee, 2) }}</strong>
                    </div>
                @endif

                <div>
                    <span>Total</span>
                    <strong id="mTotal">₱{{ number_format((float) $completedSale->total_amount, 2) }}</strong>
                </div>

                @if($completedSale->payment_status === 'UNPAID')
                    <div>
                        <span>Amount Due{{ $completedSale->delivery_status === 'PENDING' ? ' on Delivery' : '' }}</span>
                        <strong id="mAmountDue">₱{{ number_format($completedSale->amountDue(), 2) }}</strong>
                    </div>
                @else
                    <div>
                        <span>Payment Received</span>
                        <strong id="mPayment">₱{{ number_format($receiptPayment, 2) }}</strong>
                    </div>
                    <div>
                        <span>Change</span>
                        <strong id="mChange" class="sale-green">₱{{ number_format($receiptChange, 2) }}</strong>
                    </div>
                @endif

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

<div
    id="cashierNoticeModal"
    class="cashier-notice-modal"
    aria-hidden="true"
>
    <div
        class="cashier-notice-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cashierNoticeTitle"
    >
        <div class="cashier-notice-icon">
            !
        </div>

        <h3 id="cashierNoticeTitle">
            Please check the order
        </h3>

        <p id="cashierNoticeMessage">
            Add at least one product to the order.
        </p>

        <button
            type="button"
            class="btn primary"
            id="cashierNoticeOk"
        >
            OK
        </button>
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
            180px;
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

    [data-size-product][hidden] { display: none !important; }
    .cashier-product-group { min-width: 0; flex-direction: column; }
    .cashier-product-group .cashier-product-row { flex: 1; }
    .cashier-size-label { display: flex; align-items: center; gap: 8px; margin: 10px 0; font-size: 12px; color: #64748b; }
    .cashier-size-label select { flex: 1; padding: 8px 10px; }





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
        max-height: calc(100vh - 170px);
        overflow-y: auto;
        padding-right: 22px;
    }


    .cashier-order-card::-webkit-scrollbar {
        width: 7px;
    }


    .cashier-order-card::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: #cbd5e1;
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

    #paymentField[hidden],
    #changeRow[hidden],
    #codAmountDueRow[hidden] {
        display: none !important;
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


    .cashier-delivery-fee {
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

    .cashier-notice-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(8, 18, 34, .58);
        backdrop-filter: blur(2px);
    }


    .cashier-notice-modal.open {
        display: flex;
    }


    .cashier-notice-card {
        width: min(390px, 100%);
        padding: 28px;
        border-radius: 16px;
        background: #ffffff;
        text-align: center;
        box-shadow: 0 24px 70px rgba(0, 0, 0, .25);
    }


    .cashier-notice-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 16px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d97706;
        background: #fff7ed;
        font-size: 24px;
        font-weight: 900;
    }


    .cashier-notice-card h3 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 21px;
    }


    .cashier-notice-card p {
        margin: 0 0 22px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.6;
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
    }


    .sale-receipt-store {
        color: #0f172a;
        font-size: 17px;
        font-weight: 900;
        letter-spacing: .10em;
        text-transform: uppercase;
    }


    .sale-receipt-store-subtitle {
        margin-top: 2px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .03em;
    }


    .sale-receipt-shop-address {
        width: min(320px, 100%);
        margin-top: 6px;
        color: #0f172a;
        font-size: 11px;
        font-weight: 400;
        line-height: 1.4;
    }


    .sale-modal-head h2 {
        margin: 14px 0 4px 0;
        padding-top: 14px;
        border-top: 1px dashed #cbd5e1;
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

        padding: 10px 24px 4px;
    }


    .sale-modal-item {
        display: grid;
        grid-template-columns: 38px 62px minmax(0, 1fr) 70px 78px;
        gap: 8px;
        align-items: start;

        padding: 12px 0;

        border-bottom:
            1px dashed #d8dee8;

        font-size: 12px;
    }


    .sale-modal-item > :nth-child(4),
    .sale-modal-item > :nth-child(5) {
        text-align: right;
    }


    .sale-modal-item-header {
        padding: 8px 0;
        color: #64748b;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .05em;
        text-transform: uppercase;
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }

    .sale-receipt-meta {
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr);
        gap: 5px 12px;
        margin: 0 24px 12px;
        color: #0f172a;
    }


    .sale-receipt-meta span {
        padding: 0;
        border: 0;
        color: #0f172a;
        font-size: 12px;
        font-weight: 400;
        background: transparent;
    }


    .sale-receipt-meta strong {
        padding: 0;
        border: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 400;
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
        font-weight: 400;
    }


    .sale-delivery-summary span {
        font-weight: 400;
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
        color: #0f172a;
    }


    .sale-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;

        padding: 18px 24px;

        background: #ffffff;
    }


    @media print {

        @page {
            margin: 0;
        }

        html,
        body {
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #ffffff !important;
        }

        body * {
            visibility: hidden !important;
        }

        .shell,
        .main {
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #ffffff !important;
        }

        .sidebar,
        .app-date-time,
        .app-backup-status,
        .app-module-heading,
        .sales-module-tabs,
        .cashier-grid,
        .sale-modal-close,
        .sale-modal-actions {
            display: none !important;
            visibility: hidden !important;
        }

        .sale-modal {
            position: static !important;
            display: block !important;
            width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 10mm !important;
            background: #ffffff !important;
            visibility: visible !important;
        }

        .sale-modal *,
        .sale-modal-card {
            visibility: visible !important;
        }

        .sale-modal-card {
            width: 82mm !important;
            max-width: 82mm !important;
            margin: 0 auto !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            color: #111827 !important;
            font-size: 12px !important;
        }

        .sale-modal-head {
            display: block !important;
            padding: 12px 0 10px !important;
            text-align: center !important;
        }

        .sale-receipt-store {
            font-size: 18px !important;
        }

        .sale-receipt-store-subtitle {
            font-size: 12px !important;
            color: #0f172a !important;
        }

        .sale-receipt-shop-address {
            width: 100% !important;
            margin: 5px auto 0 !important;
            color: #0f172a !important;
            font-size: 10px !important;
            font-weight: 400 !important;
            line-height: 1.35 !important;
            text-align: center !important;
        }

        .sale-modal-head h2 {
            margin: 14px 0 0 !important;
            padding-top: 10px !important;
            border-top: 1px dashed #cbd5e1 !important;
            font-size: 16px !important;
        }

        .sale-receipt-meta {
            grid-template-columns: 75px minmax(0, 1fr) !important;
            gap: 3px 8px !important;
            margin: 6px 0 8px !important;
            border: 0 !important;
            background: #ffffff !important;
        }

        .sale-receipt-meta span,
        .sale-receipt-meta strong {
            padding: 0 !important;
            border: 0 !important;
            color: #0f172a !important;
            background: transparent !important;
            font-size: 10px !important;
            font-weight: 400 !important;
            line-height: 1.25 !important;
        }

        #modalItems {
            max-height: none !important;
            overflow: visible !important;
            padding: 6px 0 !important;
        }

        .sale-modal-item {
            grid-template-columns: 26px 42px minmax(0, 1fr) 54px 60px !important;
            gap: 5px !important;
            padding: 8px 0 !important;
            font-size: 10px !important;
        }

        .sale-modal-item-header {
            font-size: 8px !important;
        }

        .sale-summary,
        .sale-delivery-summary {
            margin-right: 0 !important;
            margin-left: 0 !important;
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
            max-height: none;
            overflow-y: visible;
            padding-right: 22px;
        }

        .cashier-filters {
            grid-template-columns:
                minmax(200px, 1fr)
                160px;
        }

    }


    @media (max-width: 700px) {

        .cashier-filters {
            grid-template-columns: 1fr;
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
            number.toFixed(8)
        ).toString();

    }


    function escapeHtml(value) {

        const div =
            document.createElement('div');

        div.textContent =
            String(value ?? '');

        return div.innerHTML;

    }


    function sellingQuantity(item) {
        return Number(item.selling_qty ?? Number(item.qty) / Number(item.factor));
    }

    function itemSubtotal(item) {
        const priceInCentavos = Math.round(Number(item.price) * 100);
        const quantityInMillionths = Math.round(sellingQuantity(item) * 1000000);

        return Math.round(priceInCentavos * quantityInMillionths / 1000000) / 100;
    }


    function calculateTotal() {

        const productTotal = Object
            .values(items)
            .reduce(
                function (sum, item) {

                    return sum + itemSubtotal(item);

                },
                0
            );

        const deliveryFee =
            byId('delivery').checked
                ? Math.max(
                    0,
                    Number(byId('deliveryFee').value || 0)
                )
                : 0;

        return Math.round((productTotal + deliveryFee) * 100) / 100;

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

        byId('codAmountDue').textContent = money(total);


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

    function updateAvailableStock() {
        document.querySelectorAll('.product-filter-row').forEach((row) => {
            const orderedQuantity = Object.values(items)
                .filter((item) => Number(item.product_id) === Number(row.dataset.productId))
                .reduce((total, item) => total + Number(item.qty), 0);
            const availableStock = Math.max(0,
                Number((Number(row.dataset.baseStock) - orderedQuantity).toFixed(8)));
            const hasStock = availableStock > 0;
            const badge = row.querySelector('[data-stock-badge]');

            row.querySelector('[data-available-stock]').textContent = cleanNumber(availableStock);
            row.dataset.stock = hasStock ? 'in' : 'out';
            badge.textContent = hasStock ? 'In Stock' : 'Out of Stock';
            badge.classList.toggle('in-stock', hasStock);
            badge.classList.toggle('out-stock', !hasStock);

            row.querySelectorAll('.cashier-unit-button').forEach((button) => {
                const unit = readUnitFromButton(button);
                const existingItem = unit ? items[String(unit.base_id ?? unit.id)] : null;
                const canSwitchUnit = existingItem
                    && Number(existingItem.selling_id) !== Number(unit.id)
                    && Number(unit.factor) <= Number(row.dataset.baseStock);

                button.disabled = button.dataset.stockLocked === 'true' || (!hasStock && !canSwitchUnit);
            });
        });
    }


    function renderOrder() {

        updateAvailableStock();

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
                                    value="${cleanNumber(sellingQuantity(item))}"
                                    min="0.000001"
                                    step="any"
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
                                value="${cleanNumber(sellingQuantity(item))}"
                            >



                            <div class="cashier-order-amount">

                                <strong>
                                    ${money(itemSubtotal(item))}
                                </strong>


                                <input
                                    type="hidden"
                                    name="items[${index}][product_unit_id]"
                                    value="${item.selling_id}"
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
    /* KEEP ONE PRODUCT LINE; RESET WHEN SWITCHING UNITS       */
    /* ====================================================== */

    function changeUnitQuantity(unit, changeBy) {

        const unitId =
            String(unit.base_id ?? unit.id);

        const existingItem = items[unitId];
        const sellingId = Number(unit.selling_id ?? unit.id);
        const switchingUnits = existingItem && Number(existingItem.selling_id) !== sellingId;

        const existingQuantity =
            existingItem && !switchingUnits
                ? sellingQuantity(existingItem)
                : 0;

        const newSellingQuantity =
            Number((existingQuantity + Number(changeBy)).toFixed(6));

        const newQuantity =
            Math.round((newSellingQuantity * Number(unit.factor) + Number.EPSILON) * 100000000) / 100000000;

        if (!Number.isFinite(newQuantity)) {
            showCashierNotice('Enter a valid quantity.');
            renderOrder();
            return;
        }


        if (newQuantity <= 0) {
            delete items[unitId];
            renderOrder();
            return;
        }


        if (
            newQuantity >
            Number(unit.base_stock)
        ) {
            showCashierNotice(
                'Only ' +
                cleanNumber(unit.base_stock) +
                ' ' +
                (unit.base_unit ?? unit.unit) +
                ' available.'
            );
            renderOrder();
            return;
        }


        const basePrice = Number(unit.base_price ?? unit.price);

        items[unitId] = {
            id: Number(unitId),
            base_id: Number(unitId),
            selling_id: sellingId,
            product_id: Number(unit.product_id),
            name: unit.name,
            unit: unit.unit,
            price: Number(unit.price),
            base_price: basePrice,
            factor: Number(unit.factor),
            available: Number(unit.base_stock),
            base_stock: Number(unit.base_stock),
            base_unit: unit.base_unit ?? unit.unit,
            base_unit_id: unit.base_unit_id,
            selling_qty: newSellingQuantity,
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
            showCashierNotice(
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
                button.dataset.stockLocked = String(button.disabled);

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
                    sellingQuantity(item);

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
                '.cashier-product-group'
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
                    '.cashier-product-group'
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

    function confirmSaleOnEnter(event) {

        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        byId('confirmSaleButton')
            .click();

    }


    byId('payment')
        .addEventListener(
            'keydown',
            confirmSaleOnEnter
        );


    function toggleDeliveryFields() {

        if (isCashOnDelivery()) {
            byId('delivery').checked = true;
        }

        const checked =
            byId('delivery').checked;


        byId('deliveryFields')
            .classList
            .toggle('open', checked);


        [
            byId('deliveryFee'),
            byId('customerName'),
            byId('customerContactNumber'),
            byId('deliveryAddress')
        ].forEach(
            function (field) {
                field.required = checked;
            }
        );

        togglePaymentFields();
        updatePayment();

    }

    function isCashOnDelivery() {
        return byId('paymentMethod').value === 'COD';
    }

    function togglePaymentFields() {
        const cod = isCashOnDelivery();
        byId('payment').required = !cod;
        byId('payment').disabled = cod;
        if (cod) {
            byId('payment').value = '';
        }
        byId('paymentField').hidden = cod;
        byId('changeRow').hidden = cod;
        byId('codAmountDueRow').hidden = !cod;
        byId('confirmSaleButton').textContent = cod ? 'SAVE COD ORDER' : 'CONFIRM SALE';
    }

    byId('paymentMethod').addEventListener('change', toggleDeliveryFields);


    byId('delivery')
        .addEventListener(
            'change',
            function () {
                if (!byId('delivery').checked && isCashOnDelivery()) {
                    byId('paymentMethod').value = 'PAY_NOW';
                }
                toggleDeliveryFields();
            }
        );


    byId('deliveryFee')
        .addEventListener(
            'input',
            updatePayment
        );


    byId('deliveryFee')
        .addEventListener(
            'keydown',
            confirmSaleOnEnter
        );


    /* ====================================================== */
    /* CONFIRM SALE / SAVE TRANSACTION                         */
    /* ====================================================== */

    function showCashierNotice(message, title = 'Please check the order') {

        byId('cashierNoticeTitle').textContent =
            title;

        byId('cashierNoticeMessage').textContent =
            message;

        byId('cashierNoticeModal')
            .classList
            .add('open');

        byId('cashierNoticeModal')
            .setAttribute('aria-hidden', 'false');

        byId('cashierNoticeOk').focus();

    }


    function closeCashierNotice() {

        byId('cashierNoticeModal')
            .classList
            .remove('open');

        byId('cashierNoticeModal')
            .setAttribute('aria-hidden', 'true');

    }


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

                    showCashierNotice(
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
                    !isCashOnDelivery() && (
                        !Number.isFinite(payment) || payment < total
                    )
                ) {

                    showCashierNotice(
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


    byId('cashierNoticeOk')
        .addEventListener(
            'click',
            closeCashierNotice
        );


    byId('cashierNoticeModal')
        .addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    byId('cashierNoticeModal')
                ) {
                    closeCashierNotice();
                }

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

@include('layouts.product-sizes')

@endsection
