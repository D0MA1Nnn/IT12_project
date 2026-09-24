<?php $__env->startSection('title', 'Cashiering / Sales'); ?>

<?php $__env->startSection('content'); ?>

<div class="top">
    <div>
        <h1>Cashiering / Sales</h1>
        <div class="muted">
            Search products, choose the customer's selling unit, and process each sale without blocking pending deliveries.
        </div>
    </div>

    <div class="who">
        <?php echo e(auth()->user()->role === 'OWNER' ? 'Owner' : 'Sales Clerk'); ?>

    </div>
</div>


<?php if(session('success')): ?>
    <div class="alert success" style="margin-bottom:16px;">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="alert danger" style="margin-bottom:16px;">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="alert danger" style="margin-bottom:16px;">
        <strong>Please check the transaction.</strong>
        <ul style="margin:8px 0 0 18px;">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>


<form
    method="POST"
    action="<?php echo e(route('sales.store')); ?>"
    id="saleForm"
>
    <?php echo csrf_field(); ?>

    
    
    

    <div class="card cashier-filter-card">

        <div class="cashier-filters">

            <div class="cashier-search-wrap">

                <span class="cashier-search-icon">
                    &#128269;
                </span>

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

                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                    <option
                        value="<?php echo e(strtolower($category->category_name)); ?>"
                    >
                        <?php echo e($category->category_name); ?>

                    </option>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>


            <select
                id="stockFilter"
                class="input cashier-filter-select"
            >
                <option value="">
                    All Stock
                </option>

                <option value="in">
                    In Stock
                </option>

                <option value="out">
                    Out of Stock
                </option>
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


    
    
    

    <div class="cashier-grid">

        
        
        

        <div class="card cashier-products-card">

            <div class="cashier-section-header">

                <h2>
                    Products
                </h2>

                <div class="muted">
                    Each product appears once. Select the selling unit requested by the customer.
                </div>

            </div>


            <div
                id="productScrollArea"
                class="cashier-product-scroll"
            >

                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                    <?php
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
                    ?>


                    <div
                        class="cashier-product-row product-filter-row"
                        data-product-id="<?php echo e($product->product_id); ?>"
                        data-search="<?php echo e($searchText); ?>"
                        data-category="<?php echo e(strtolower($categoryName)); ?>"
                        data-stock="<?php echo e($baseStock > 0 ? 'in' : 'out'); ?>"
                    >

                        

                        <div class="cashier-product-info">

                            <div class="cashier-product-title">
                                <?php echo e($product->product_name); ?>

                            </div>

                            <div class="muted cashier-product-meta">

                                <?php echo e($categoryName); ?>


                                <span>•</span>

                                Base stock:

                                <strong>
                                    <?php echo e(rtrim(rtrim(number_format($baseStock, 3, '.', ''), '0'), '.')); ?>

                                </strong>

                                <?php echo e($baseUnitName); ?>


                            </div>


                            <?php if($baseStock > 0): ?>

                                <span class="cashier-stock-badge in-stock">
                                    In Stock
                                </span>

                            <?php else: ?>

                                <span class="cashier-stock-badge out-stock">
                                    Out of Stock
                                </span>

                            <?php endif; ?>

                        </div>


                        

                        <div class="cashier-control">

                            <label>
                                Selling Unit
                            </label>

                            <select
                                class="input cashier-unit-select"
                                <?php if($baseStock <= 0): echo 'disabled'; endif; ?>
                            >

                                <?php $__currentLoopData = $activeUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $productUnit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                    <?php
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
                                    ?>


                                    <option
                                        value="<?php echo e($productUnit->product_unit_id); ?>"
                                        data-unit="<?php echo e(json_encode($unitData)); ?>"
                                    >
                                        <?php echo e($productUnit->unit?->unit_name ?? 'Unit'); ?>

                                        — ₱<?php echo e(number_format((float) $productUnit->selling_price, 2)); ?>

                                    </option>

                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </select>

                        </div>


                        

                        <div class="cashier-control">

                            <label>
                                Quantity
                            </label>

                            <input
                                type="number"
                                class="input cashier-product-qty"
                                min="0.001"
                                step="0.001"
                                value="1"
                                <?php if($baseStock <= 0): echo 'disabled'; endif; ?>
                            >

                        </div>


                        

                        <div class="cashier-add-control">

                            <label>&nbsp;</label>

                            <button
                                type="button"
                                class="btn primary cashier-add-button"
                                <?php if($baseStock <= 0): echo 'disabled'; endif; ?>
                            >
                                + Add
                            </button>

                        </div>

                    </div>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                    <div class="cashier-empty-products muted">
                        No active products are available for sale.
                    </div>

                <?php endif; ?>


                <div
                    id="noFilterResults"
                    class="cashier-empty-products muted"
                    style="display:none;"
                >
                    No products match your search or filter.
                </div>

            </div>

        </div>


        
        
        

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
                    value="<?php echo e(old('payment')); ?>"
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
                    <?php if(old('delivery_required')): echo 'checked'; endif; ?>
                >

                <span>
                    Delivery required
                </span>

            </label>


            <div class="muted cashier-delivery-help">

                If checked, the order is saved as
                <strong>Pending Delivery</strong>.

                You can immediately serve the next customer.

            </div>


            <button
                type="button"
                id="reviewSale"
                class="btn success cashier-review-button"
            >
                REVIEW SALE
            </button>

        </div>

    </div>

</form>






<div
    id="saleModal"
    class="sale-modal"
>

    <div class="sale-modal-card">

        <div class="sale-modal-head">

            <div>

                <h2>
                    Confirm Sale
                </h2>

                <div class="muted">
                    Verify the transaction before saving.
                </div>

            </div>


            <button
                type="button"
                class="sale-modal-close"
                id="closeModal"
            >
                &times;
            </button>

        </div>


        <div id="modalItems"></div>


        <div class="sale-summary">

            <div>
                <span>Total</span>
                <strong id="mTotal">₱0.00</strong>
            </div>

            <div>
                <span>Payment</span>
                <strong id="mPayment">₱0.00</strong>
            </div>

            <div>
                <span>Change</span>
                <strong id="mChange" class="sale-green">
                    ₱0.00
                </strong>
            </div>

            <div>
                <span>Delivery</span>
                <strong id="mDelivery">
                    No
                </strong>
            </div>

            <div>
                <span>Status</span>
                <strong id="mStatus">
                    Completed
                </strong>
            </div>

        </div>


        <div class="sale-modal-actions">

            <button
                type="button"
                class="btn light"
                id="cancelModal"
            >
                Go Back
            </button>

            <button
                type="button"
                class="btn success"
                id="confirmSale"
            >
                Confirm Sale
            </button>

        </div>

    </div>

</div>


<style>

    /* ====================================================== */
    /* FILTERS                                                */
    /* ====================================================== */

    .cashier-filter-card {
        margin-bottom: 16px;
        padding: 14px;
    }

    .cashier-filters {
        display: grid;
        grid-template-columns:
            minmax(260px, 1fr)
            180px
            150px
            auto;
        gap: 10px;
        align-items: center;
    }

    .cashier-search-wrap {
        position: relative;
        min-width: 0;
    }

    .cashier-search-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1;
        color: #8793a5;
        pointer-events: none;
    }

    .cashier-search {
        width: 100%;
        padding-left: 40px !important;
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
        max-height: 570px;
        overflow-y: auto;
        overflow-x: hidden;

        margin-top: 20px;
        padding-right: 8px;

        scrollbar-gutter: stable;
    }


    .cashier-product-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .cashier-product-scroll::-webkit-scrollbar-track {
        background: #f1f4f8;
        border-radius: 10px;
    }

    .cashier-product-scroll::-webkit-scrollbar-thumb {
        background: #c7cfda;
        border-radius: 10px;
    }


    .cashier-product-row {
        display: grid;

        grid-template-columns:
            minmax(170px, 1fr)
            minmax(210px, 1.25fr)
            90px
            90px;

        gap: 12px;
        align-items: end;

        padding: 18px 0;

        border-bottom: 1px solid #e9edf3;
    }


    .cashier-product-row:first-child {
        padding-top: 4px;
    }


    .cashier-product-info {
        min-width: 0;
        align-self: center;
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


    .cashier-control,
    .cashier-add-control {
        min-width: 0;
    }


    .cashier-control label,
    .cashier-add-control label {
        display: block;

        margin-bottom: 6px;

        color: #778397;

        font-size: 11px;
        font-weight: 700;
    }


    .cashier-unit-select {
        width: 100%;
    }


    .cashier-product-qty {
        width: 100%;
        text-align: center;
    }


    .cashier-add-button {
        width: 100%;
        white-space: nowrap;
    }


    .cashier-add-button:disabled {
        opacity: .45;
        cursor: not-allowed;
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
            82px
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
        width: min(540px, 100%);

        overflow: hidden;

        border-radius: 16px;

        background: #ffffff;

        box-shadow:
            0 24px 70px
            rgba(0, 0, 0, .25);
    }


    .sale-modal-head {
        display: flex;
        justify-content: space-between;
        gap: 18px;

        padding: 24px;

        border-bottom:
            1px solid #edf0f4;
    }


    .sale-modal-head h2 {
        margin: 0 0 4px 0;
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

        padding: 8px 24px;
    }


    .sale-modal-item {
        display: flex;
        justify-content: space-between;
        gap: 14px;

        padding: 13px 0;

        border-bottom:
            1px solid #edf0f4;
    }


    .sale-summary {
        padding: 18px 24px;
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

        background: #f7f9fc;
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


                            <input
                                class="input cashier-order-qty"
                                data-id="${item.id}"
                                type="number"
                                step="0.001"
                                min="0.001"
                                max="${item.available}"
                                name="items[${index}][quantity]"
                                value="${item.qty}"
                                required
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
    /* ADD PRODUCT                                            */
    /* ====================================================== */

    document
        .querySelectorAll(
            '.cashier-add-button'
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const row =
                            button.closest(
                                '.cashier-product-row'
                            );


                        const select =
                            row.querySelector(
                                '.cashier-unit-select'
                            );


                        const quantityInput =
                            row.querySelector(
                                '.cashier-product-qty'
                            );


                        const selectedOption =
                            select.options[
                                select.selectedIndex
                            ];


                        if (!selectedOption) {
                            return;
                        }


                        let unit;


                        try {

                            unit =
                                JSON.parse(
                                    selectedOption.dataset.unit
                                );

                        }
                        catch (error) {

                            alert(
                                'Unable to read the selected selling unit.'
                            );

                            return;

                        }


                        const quantity =
                            Number(
                                quantityInput.value
                            );


                        if (
                            !Number.isFinite(quantity) ||
                            quantity <= 0
                        ) {

                            alert(
                                'Quantity must be greater than zero.'
                            );

                            quantityInput.focus();

                            return;

                        }


                        const unitId =
                            String(unit.id);


                        const existingQuantity =
                            items[unitId]
                                ? Number(
                                    items[unitId].qty
                                )
                                : 0;


                        const newQuantity =
                            existingQuantity +
                            quantity;


                        /*
                        |--------------------------------------------------------------------------
                        | Check selected selling-unit availability.
                        |--------------------------------------------------------------------------
                        */

                        if (
                            newQuantity >
                            Number(unit.available) +
                            0.000001
                        ) {

                            alert(
                                'Only ' +
                                cleanNumber(
                                    unit.available
                                ) +
                                ' ' +
                                unit.unit +
                                ' available.'
                            );

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Check the SAME product across ALL selling units.
                        |--------------------------------------------------------------------------
                        |
                        | Example:
                        |
                        | Sand:
                        | 10 sacks + 1 cubic meter
                        |
                        | Both use the same Sand base inventory.
                        |
                        */

                        const usedByOtherUnits =
                            calculateUsedBaseStock(
                                unit.product_id,
                                unitId
                            );


                        const requiredByThisUnit =
                            newQuantity *
                            Number(unit.factor);


                        const totalRequiredBase =
                            usedByOtherUnits +
                            requiredByThisUnit;


                        if (
                            totalRequiredBase >
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

                            id:
                                Number(unit.id),

                            product_id:
                                Number(
                                    unit.product_id
                                ),

                            name:
                                unit.name,

                            unit:
                                unit.unit,

                            price:
                                Number(
                                    unit.price
                                ),

                            factor:
                                Number(
                                    unit.factor
                                ),

                            available:
                                Number(
                                    unit.available
                                ),

                            base_stock:
                                Number(
                                    unit.base_stock
                                ),

                            qty:
                                newQuantity

                        };


                        quantityInput.value = 1;


                        renderOrder();

                    }
                );

            }
        );


    /* ====================================================== */
    /* UPDATE CART QUANTITY                                   */
    /* ====================================================== */

    byId('order')
        .addEventListener(
            'change',
            function (event) {

                if (
                    !event.target.classList.contains(
                        'cashier-order-qty'
                    )
                ) {
                    return;
                }


                const unitId =
                    event.target.dataset.id;


                const item =
                    items[unitId];


                if (!item) {
                    return;
                }


                const newQuantity =
                    Number(
                        event.target.value
                    );


                if (
                    !Number.isFinite(newQuantity) ||
                    newQuantity <= 0
                ) {

                    alert(
                        'Quantity must be greater than zero.'
                    );

                    event.target.value =
                        item.qty;

                    return;

                }


                if (
                    newQuantity >
                    Number(item.available) +
                    0.000001
                ) {

                    alert(
                        'Only ' +
                        cleanNumber(
                            item.available
                        ) +
                        ' ' +
                        item.unit +
                        ' available.'
                    );

                    event.target.value =
                        item.qty;

                    return;

                }


                const usedByOtherUnits =
                    calculateUsedBaseStock(
                        item.product_id,
                        unitId
                    );


                const requiredByThisUnit =
                    newQuantity *
                    Number(item.factor);


                if (
                    usedByOtherUnits +
                    requiredByThisUnit >
                    Number(item.base_stock) +
                    0.000001
                ) {

                    alert(
                        'The quantity exceeds the remaining stock for this product.'
                    );

                    event.target.value =
                        item.qty;

                    return;

                }


                item.qty =
                    newQuantity;


                renderOrder();

            }
        );


    /* ====================================================== */
    /* REMOVE CART ITEM                                       */
    /* ====================================================== */

    byId('order')
        .addEventListener(
            'click',
            function (event) {

                const button =
                    event.target.closest(
                        '[data-remove]'
                    );


                if (!button) {
                    return;
                }


                delete items[
                    button.dataset.remove
                ];


                renderOrder();

            }
        );


    /* ====================================================== */
    /* FILTER PRODUCTS                                        */
    /* ====================================================== */

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


        const stock =
            byId('stockFilter')
                .value;


        let visibleCount = 0;


        document
            .querySelectorAll(
                '.product-filter-row'
            )
            .forEach(
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


                    const matchesStock =
                        stock === '' ||
                        row.dataset.stock ===
                        stock;


                    const visible =
                        matchesSearch &&
                        matchesCategory &&
                        matchesStock;


                    row.style.display =
                        visible
                            ? 'grid'
                            : 'none';


                    if (visible) {
                        visibleCount++;
                    }

                }
            );


        byId('noFilterResults')
            .style.display =
                visibleCount === 0
                    ? 'block'
                    : 'none';

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


    byId('stockFilter')
        .addEventListener(
            'change',
            filterProducts
        );


    byId('filterButton')
        .addEventListener(
            'click',
            filterProducts
        );


    /* ====================================================== */
    /* PAYMENT                                                */
    /* ====================================================== */

    byId('payment')
        .addEventListener(
            'input',
            updatePayment
        );


    /* ====================================================== */
    /* REVIEW SALE                                            */
    /* ====================================================== */

    function closeModal() {

        byId('saleModal')
            .classList
            .remove('open');

    }


    byId('reviewSale')
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


                byId('modalItems')
                    .innerHTML =
                        orderItems.map(
                            function (item) {

                                return `

                                    <div class="sale-modal-item">

                                        <div>

                                            <strong>
                                                ${escapeHtml(
                                                    item.name
                                                )}
                                            </strong>

                                            <div class="muted">

                                                ${cleanNumber(
                                                    item.qty
                                                )}

                                                ${escapeHtml(
                                                    item.unit
                                                )}

                                                ×

                                                ${money(
                                                    item.price
                                                )}

                                            </div>

                                        </div>


                                        <strong>

                                            ${money(
                                                Number(
                                                    item.qty
                                                ) *
                                                Number(
                                                    item.price
                                                )
                                            )}

                                        </strong>

                                    </div>

                                `;

                            }
                        ).join('');


                byId('mTotal')
                    .textContent =
                        money(total);


                byId('mPayment')
                    .textContent =
                        money(payment);


                byId('mChange')
                    .textContent =
                        money(
                            payment - total
                        );


                const deliveryRequired =
                    byId('delivery').checked;


                byId('mDelivery')
                    .textContent =
                        deliveryRequired
                            ? 'Yes'
                            : 'No';


                byId('mStatus')
                    .textContent =
                        deliveryRequired
                            ? 'Pending Delivery'
                            : 'Completed';


                byId('confirmSale')
                    .textContent =
                        deliveryRequired
                            ? 'Confirm Pending Delivery'
                            : 'Confirm Sale';


                byId('saleModal')
                    .classList
                    .add('open');

            }
        );


    /* ====================================================== */
    /* CLOSE MODAL                                            */
    /* ====================================================== */

    byId('closeModal')
        .addEventListener(
            'click',
            closeModal
        );


    byId('cancelModal')
        .addEventListener(
            'click',
            closeModal
        );


    byId('saleModal')
        .addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    byId('saleModal')
                ) {
                    closeModal();
                }

            }
        );


    /* ====================================================== */
    /* CONFIRM / SUBMIT                                       */
    /* ====================================================== */

    byId('confirmSale')
        .addEventListener(
            'click',
            function () {

                if (
                    Object.keys(items).length === 0
                ) {
                    return;
                }


                const button =
                    byId('confirmSale');


                button.disabled =
                    true;


                button.textContent =
                    'Processing...';


                byId('saleForm')
                    .submit();

            }
        );


    /* ====================================================== */
    /* INITIALIZE                                             */
    /* ====================================================== */

    renderOrder();
    filterProducts();

});

</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/sales/create.blade.php ENDPATH**/ ?>