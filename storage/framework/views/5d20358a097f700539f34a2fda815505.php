<?php $__env->startSection('title', 'Products & Inventory'); ?>

<?php $__env->startSection('content'); ?>

<div class="top">
    <div>
        <h1>Products & Inventory</h1>

        <div class="muted">
            Maintain construction materials and monitor available quantities
        </div>
    </div>

    <div class="who">
        Owner
    </div>
</div>

<div class="tabs">
    <a href="<?php echo e(route('products.index')); ?>">Products</a>
    <a href="<?php echo e(route('categories.index')); ?>">Categories</a>
    <a href="<?php echo e(route('units.index')); ?>">Units of Measure</a>
    <a class="active" href="<?php echo e(route('inventory.index')); ?>">Inventory</a>
</div>




<form
    method="GET"
    action="<?php echo e(route('inventory.index')); ?>"
    class="inventory-toolbar"
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
            value="<?php echo e(request('search')); ?>"
            placeholder="Search materials..."
        >
    </div>

    <select
        name="stock"
        class="inventory-select"
    >
        <option value="">
            All Stock
        </option>

        <option
            value="in_stock"
            <?php if(request('stock') === 'in_stock'): echo 'selected'; endif; ?>
        >
            In Stock
        </option>

        <option
            value="low_stock"
            <?php if(request('stock') === 'low_stock'): echo 'selected'; endif; ?>
        >
            Low Stock
        </option>

        <option
            value="out_of_stock"
            <?php if(request('stock') === 'out_of_stock'): echo 'selected'; endif; ?>
        >
            Out of Stock
        </option>
    </select>

    <button
        type="submit"
        class="btn primary"
    >
        Filter
    </button>

    <?php if(
        request()->filled('search') ||
        request()->filled('stock')
    ): ?>
        <a
            href="<?php echo e(route('inventory.index')); ?>"
            class="btn light"
        >
            Clear
        </a>
    <?php endif; ?>
</form>




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
            <?php echo e($inventories->count()); ?>

            <?php echo e($inventories->count() === 1 ? 'material' : 'materials'); ?>

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
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php $__empty_1 = true; $__currentLoopData = $inventories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inventory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                <?php
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
                ?>

                <tr>

                    <td>
                        <strong>
                            <?php echo e($product->product_name); ?>

                        </strong>
                    </td>

                    <td>
                        <?php echo e($product->category?->category_name ?? '—'); ?>

                    </td>

                    <td>
                        <span
                            class="<?php echo e($outOfStock ? 'qty-zero' : ''); ?>"
                        >
                            <?php echo e(number_format($quantity, 0)); ?>

                        </span>
                    </td>

                    <td>
                        <?php echo e($baseUnit?->unit?->unit_name ?? '—'); ?>

                    </td>

                    <td>

                        <span class="reorder-value">
                            <?php echo e(number_format($reorder, 0)); ?>

                        </span>

                    </td>

                    <td>

                        <?php if($outOfStock): ?>

                            <span class="stock-badge out">
                                Out of Stock
                            </span>

                        <?php elseif($lowStock): ?>

                            <span class="stock-badge low">
                                Low Stock
                            </span>

                        <?php else: ?>

                            <span class="stock-badge good">
                                In Stock
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <button
                            type="button"
                            class="btn primary small edit-inventory-product"

                            data-update-url="<?php echo e(route('products.update', $product)); ?>"

                            data-name="<?php echo e($product->product_name); ?>"

                            data-category="<?php echo e($product->category_id); ?>"

                            data-unit="<?php echo e($baseUnit?->unit_id ?? ''); ?>"

                            data-price="<?php echo e($baseUnit?->selling_price ?? 0); ?>"

                            data-cost="<?php echo e($baseUnit?->purchase_cost ?? 0); ?>"

                            data-reorder="<?php echo e(number_format($reorder, 0, '.', '')); ?>"

                            data-description="<?php echo e($product->description ?? ''); ?>"
                        >
                            Edit Product
                        </button>

                    </td>

                </tr>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                <tr>
                    <td
                        colspan="7"
                        class="inventory-empty"
                    >
                        No inventory records found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>




<div
    class="inventory-modal-overlay"
    id="inventoryEditModal"
>

    <div class="inventory-modal">

        <div class="inventory-modal-header">

            <div>
                <h2>
                    Edit Product
                </h2>

                <p>
                    Update product, pricing, inventory and base unit information.
                </p>
            </div>

            <button
                type="button"
                class="inventory-modal-close"
                id="closeInventoryEdit"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            id="inventoryEditForm"
        >
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>


            <div class="inventory-modal-grid">

                <div class="field">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        id="inventoryEditName"
                        class="input"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Category
                    </label>

                    <select
                        name="category_id"
                        id="inventoryEditCategory"
                        required
                    >

                        <option value="">
                            Select category
                        </option>

                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                            <option
                                value="<?php echo e($category->category_id); ?>"
                            >
                                <?php echo e($category->category_name); ?>

                            </option>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </select>

                </div>


                <div class="field">

                    <label>
                        Base Unit
                    </label>

                    <select
                        name="unit_id"
                        id="inventoryEditUnit"
                        required
                    >

                        <option value="">
                            Select unit
                        </option>

                        <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                            <option
                                value="<?php echo e($unit->unit_id); ?>"
                            >
                                <?php echo e($unit->unit_name); ?>

                                (<?php echo e($unit->unit_symbol); ?>)
                            </option>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </select>

                </div>


                <div class="field">

                    <label>
                        Selling Price
                    </label>

                    <input
                        type="number"
                        name="selling_price"
                        id="inventoryEditPrice"
                        class="input"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Purchase Cost
                    </label>

                    <input
                        type="number"
                        name="purchase_cost"
                        id="inventoryEditCost"
                        class="input"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Reorder Level
                    </label>

                    <input
                        type="number"
                        name="reorder_level"
                        id="inventoryEditReorder"
                        class="input"
                        min="0"
                        step="1"
                        required
                    >

                </div>

            </div>


            <div class="field">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    id="inventoryEditDescription"
                    class="input"
                    rows="4"
                ></textarea>

            </div>


            <div class="inventory-modal-actions">

                <button
                    type="button"
                    class="btn light"
                    id="cancelInventoryEdit"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn primary"
                    id="saveInventoryEdit"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<style>

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
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #edf1f6;
}

.inventory-card-header {
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
    overflow-x: auto;
}

.inventory-table {
    width: 100%;
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
   MODAL
========================================================= */

.inventory-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 24px;

    background: rgba(15, 23, 42, .58);
    backdrop-filter: blur(2px);
}

.inventory-modal-overlay.show {
    display: flex;
}

.inventory-modal {
    width: min(850px, 100%);
    max-height: 90vh;
    overflow-y: auto;

    background: #fff;
    border-radius: 16px;

    padding: 26px;

    box-shadow:
        0 25px 60px rgba(15, 23, 42, .25);
}

.inventory-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    margin-bottom: 24px;
}

.inventory-modal-header h2 {
    margin: 0 0 5px;
    color: #0f172a;
}

.inventory-modal-header p {
    margin: 0;
    color: #8492aa;
    font-size: 13px;
}

.inventory-modal-close {
    width: 36px;
    height: 36px;

    border: 0;
    border-radius: 8px;

    background: #f1f5f9;
    color: #475569;

    font-size: 25px;
    line-height: 1;

    cursor: pointer;
}

.inventory-modal-close:hover {
    background: #e2e8f0;
}

.inventory-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.inventory-modal .field {
    margin-bottom: 16px;
}

.inventory-modal .field label {
    display: block;
    margin-bottom: 7px;

    color: #0f172a;
    font-size: 13px;
    font-weight: 700;
}

.inventory-modal .input,
.inventory-modal select,
.inventory-modal textarea {
    width: 100%;
    box-sizing: border-box;

    border: 1px solid #dbe3ef;
    border-radius: 8px;

    background: #f8fafc;

    padding: 11px 13px;

    font: inherit;
    color: #0f172a;

    outline: none;
}

.inventory-modal select {
    height: 44px;
}

.inventory-modal textarea {
    resize: vertical;
}

.inventory-modal .input:focus,
.inventory-modal select:focus,
.inventory-modal textarea:focus {
    background: #fff;
    border-color: #93b4ef;
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}

.inventory-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 6px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 700px) {

    .inventory-search,
    .inventory-select {
        width: 100%;
    }

    .inventory-modal-grid {
        grid-template-columns: 1fr;
    }

    .inventory-modal {
        padding: 20px;
    }

    .inventory-modal-actions {
        flex-direction: column-reverse;
    }

    .inventory-modal-actions .btn {
        width: 100%;
    }
}

</style>


<?php $__env->startPush('scripts'); ?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal =
            document.getElementById(
                'inventoryEditModal'
            );

        const form =
            document.getElementById(
                'inventoryEditForm'
            );

        const nameInput =
            document.getElementById(
                'inventoryEditName'
            );

        const categoryInput =
            document.getElementById(
                'inventoryEditCategory'
            );

        const unitInput =
            document.getElementById(
                'inventoryEditUnit'
            );

        const priceInput =
            document.getElementById(
                'inventoryEditPrice'
            );

        const costInput =
            document.getElementById(
                'inventoryEditCost'
            );

        const reorderInput =
            document.getElementById(
                'inventoryEditReorder'
            );

        const descriptionInput =
            document.getElementById(
                'inventoryEditDescription'
            );

        const closeButton =
            document.getElementById(
                'closeInventoryEdit'
            );

        const cancelButton =
            document.getElementById(
                'cancelInventoryEdit'
            );

        const saveButton =
            document.getElementById(
                'saveInventoryEdit'
            );


        /*
        |--------------------------------------------------------------------------
        | OPEN EDIT PRODUCT
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '.edit-inventory-product'
            )
            .forEach(function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        form.action =
                            button.dataset.updateUrl;

                        nameInput.value =
                            button.dataset.name || '';

                        categoryInput.value =
                            button.dataset.category || '';

                        unitInput.value =
                            button.dataset.unit || '';

                        priceInput.value =
                            button.dataset.price || '0';

                        costInput.value =
                            button.dataset.cost || '0';

                        reorderInput.value =
                            button.dataset.reorder || '0';

                        descriptionInput.value =
                            button.dataset.description || '';

                        modal.classList.add(
                            'show'
                        );

                        document.body.style.overflow =
                            'hidden';

                        setTimeout(
                            function () {
                                nameInput.focus();
                            },
                            100
                        );
                    }
                );

            });


        /*
        |--------------------------------------------------------------------------
        | CLOSE
        |--------------------------------------------------------------------------
        */

        function closeModal() {

            modal.classList.remove(
                'show'
            );

            document.body.style.overflow =
                '';

            saveButton.disabled =
                false;

            saveButton.textContent =
                'Save Changes';
        }


        closeButton.addEventListener(
            'click',
            closeModal
        );


        cancelButton.addEventListener(
            'click',
            closeModal
        );


        modal.addEventListener(
            'click',
            function (event) {

                if (event.target === modal) {
                    closeModal();
                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        form.addEventListener(
            'submit',
            function () {

                saveButton.disabled =
                    true;

                saveButton.textContent =
                    'Saving...';

            }
        );


        /*
        |--------------------------------------------------------------------------
        | ESCAPE
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    modal.classList.contains('show')
                ) {
                    closeModal();
                }

            }
        );

    }
);

</script>

<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/inventory/index.blade.php ENDPATH**/ ?>