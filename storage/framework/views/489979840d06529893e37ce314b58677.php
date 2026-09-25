<?php $__env->startSection('title', 'Record Purchase'); ?>

<?php $__env->startSection('content'); ?>
<div class="top">
    <div>
        <h1>Record Purchase</h1>
        <div class="muted">Enter materials only after delivery has been counted, inspected and accepted.</div>
    </div>
    <div class="who">Owner</div>
</div>

<?php if($errors->any()): ?>
    <div class="purchase-alert error">
        <strong>Please check the purchase details.</strong>
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<div class="purchase-card">
    <form method="POST" action="<?php echo e(route('purchases.store')); ?>" id="purchaseForm">
        <?php echo csrf_field(); ?>

        <div class="purchase-grid two">
            <div class="field">
                <label>Supplier</label>
                <select name="supplier_id" required>
                    <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($supplier->supplier_id); ?>" <?php if(old('supplier_id') == $supplier->supplier_id): echo 'selected'; endif; ?>>
                            <?php echo e($supplier->supplier_name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="field">
                <label>Purchase Date</label>
                <input class="input" type="datetime-local" name="purchase_date"
                       value="<?php echo e(old('purchase_date', now()->format('Y-m-d\TH:i'))); ?>" required>
            </div>
        </div>

        <div class="purchase-divider"></div>

        <div class="purchase-section-head">
            <div>
                <h3>Purchase Items</h3>
                <p>Select a product first, then choose any active purchase unit assigned to that product.</p>
            </div>
            <button type="button" class="btn light" id="addPurchaseItem">+ Add Item</button>
        </div>

        <div id="purchaseRows"></div>

        <div class="purchase-summary">
            <span>Purchase Total</span>
            <strong id="purchaseTotal">₱0.00</strong>
        </div>

        <div class="purchase-actions">
            <a href="<?php echo e(route('purchases.index')); ?>" class="btn light">Cancel</a>
            <button type="submit" class="btn primary">Save Completed Purchase</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const productUnits = <?php echo json_encode($unitData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    const rowsContainer = document.getElementById('purchaseRows');
    const addButton = document.getElementById('addPurchaseItem');
    const totalLabel = document.getElementById('purchaseTotal');
    let rowIndex = 0;

    const products = [];
    const seen = new Set();
    productUnits.forEach(unit => {
        if (!seen.has(String(unit.product_id))) {
            seen.add(String(unit.product_id));
            products.push({ id: unit.product_id, name: unit.product_name });
        }
    });
    products.sort((a, b) => (a.name || '').localeCompare(b.name || ''));

    const peso = value => '₱' + Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

    function productOptions(selected = '') {
        return products.map(product =>
            `<option value="${product.id}" ${String(product.id) === String(selected) ? 'selected' : ''}>${escapeHtml(product.name)}</option>`
        ).join('');
    }

    function unitsFor(productId) {
        return productUnits
            .filter(unit => String(unit.product_id) === String(productId))
            .sort((a, b) => Number(b.is_base_unit) - Number(a.is_base_unit));
    }

    function fillUnitSelect(row, selectedUnitId = null) {
        const productSelect = row.querySelector('.purchase-product');
        const unitSelect = row.querySelector('.purchase-unit');
        const costInput = row.querySelector('.purchase-cost');
        const conversionText = row.querySelector('.purchase-conversion');
        const available = unitsFor(productSelect.value);

        unitSelect.innerHTML = available.map(unit => {
            const base = unit.is_base_unit ? ' (Base)' : '';
            return `<option value="${unit.id}" ${String(unit.id) === String(selectedUnitId) ? 'selected' : ''}>${escapeHtml(unit.unit_name)}${base} — ${peso(unit.purchase_cost)}</option>`;
        }).join('');

        if (!unitSelect.value && available.length) unitSelect.value = available[0].id;
        updateSelectedUnit(row, true);
    }

    function updateSelectedUnit(row, resetCost = false) {
        const unitSelect = row.querySelector('.purchase-unit');
        const costInput = row.querySelector('.purchase-cost');
        const quantityInput = row.querySelector('.purchase-qty');
        const conversionText = row.querySelector('.purchase-conversion');
        const unit = productUnits.find(item => String(item.id) === String(unitSelect.value));

        if (!unit) {
            costInput.value = '0.00';
            conversionText.textContent = 'No unit available.';
            calculateTotal();
            return;
        }

        if (resetCost) costInput.value = Number(unit.purchase_cost || 0).toFixed(2);
        const qty = Math.max(0, Number(quantityInput.value) || 0);
        const baseQty = qty * Number(unit.conversion_factor || 0);
        const baseUnit = unitsFor(unit.product_id).find(item => item.is_base_unit) || unitsFor(unit.product_id)[0];
        conversionText.textContent = `${qty || 0} ${unit.unit_name} × ${Number(unit.conversion_factor)} = ${Number(baseQty.toFixed(3))} ${baseUnit?.unit_name || 'base unit'} added to inventory`;
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        rowsContainer.querySelectorAll('.purchase-row').forEach(row => {
            const qty = Math.max(0, Number(row.querySelector('.purchase-qty').value) || 0);
            const cost = Math.max(0, Number(row.querySelector('.purchase-cost').value) || 0);
            total += qty * cost;
            row.querySelector('.purchase-subtotal').textContent = peso(qty * cost);
        });
        totalLabel.textContent = peso(total);
    }

    function addRow(initial = {}) {
        if (!products.length) return;
        const index = rowIndex++;
        const defaultProduct = initial.product_id || products[0].id;
        const row = document.createElement('div');
        row.className = 'purchase-row';
        row.innerHTML = `
            <div class="purchase-row-top">
                <strong>Purchase Item</strong>
                <button type="button" class="btn danger small purchase-remove">Remove</button>
            </div>
            <div class="purchase-grid item-grid">
                <div class="field">
                    <label>Product</label>
                    <select class="purchase-product" required>${productOptions(defaultProduct)}</select>
                </div>
                <div class="field">
                    <label>Purchase Unit</label>
                    <select class="purchase-unit" name="items[${index}][product_unit_id]" required></select>
                </div>
                <div class="field">
                    <label>Quantity</label>
                    <input class="input purchase-qty" type="number" step="0.001" min="0.001"
                           name="items[${index}][quantity]" value="${initial.quantity || 1}" required>
                </div>
                <div class="field">
                    <label>Purchase Cost / Unit</label>
                    <input class="input purchase-cost" type="number" step="0.01" min="0"
                           name="items[${index}][unit_cost]" required>
                </div>
            </div>
            <div class="purchase-row-foot">
                <span class="purchase-conversion"></span>
                <span>Subtotal: <strong class="purchase-subtotal">₱0.00</strong></span>
            </div>`;

        rowsContainer.appendChild(row);
        const productSelect = row.querySelector('.purchase-product');
        const unitSelect = row.querySelector('.purchase-unit');
        const qtyInput = row.querySelector('.purchase-qty');
        const costInput = row.querySelector('.purchase-cost');

        fillUnitSelect(row, initial.product_unit_id || null);
        if (initial.unit_cost !== undefined) costInput.value = Number(initial.unit_cost).toFixed(2);
        updateSelectedUnit(row, false);

        productSelect.addEventListener('change', () => fillUnitSelect(row));
        unitSelect.addEventListener('change', () => updateSelectedUnit(row, true));
        qtyInput.addEventListener('input', () => updateSelectedUnit(row, false));
        costInput.addEventListener('input', calculateTotal);
        row.querySelector('.purchase-remove').addEventListener('click', () => {
            row.remove();
            if (!rowsContainer.querySelector('.purchase-row')) addRow();
            calculateTotal();
        });
    }

    addButton.addEventListener('click', () => addRow());
    addRow();
})();
</script>
<?php $__env->stopPush(); ?>

<style>
.purchase-card{background:#fff;border:1px solid #edf1f6;border-radius:14px;padding:22px}.purchase-grid{display:grid;gap:16px}.purchase-grid.two{grid-template-columns:repeat(2,minmax(0,1fr))}.purchase-grid.item-grid{grid-template-columns:1.35fr 1.35fr .7fr .8fr}.purchase-divider{height:1px;background:#e8eef6;margin:22px 0}.purchase-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px}.purchase-section-head h3{margin:0;color:#0f172a}.purchase-section-head p{margin:4px 0 0;color:#8492aa;font-size:13px}.purchase-row{border:1px solid #e4eaf2;border-radius:12px;padding:16px;margin-bottom:14px;background:#fff}.purchase-row-top,.purchase-row-foot{display:flex;align-items:center;justify-content:space-between;gap:14px}.purchase-row-top{margin-bottom:14px}.purchase-row-foot{margin-top:12px;padding-top:12px;border-top:1px solid #eef2f7;font-size:13px}.purchase-conversion{color:#64748b}.purchase-summary{display:flex;align-items:center;justify-content:flex-end;gap:24px;padding:18px 0;font-size:17px}.purchase-summary strong{font-size:22px;color:#0f172a}.purchase-actions{display:flex;justify-content:flex-end;gap:10px}.purchase-alert{border-radius:10px;padding:14px 16px;margin-bottom:16px}.purchase-alert.error{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3}.purchase-alert ul{margin:8px 0 0 18px}@media(max-width:1050px){.purchase-grid.item-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.purchase-grid.two,.purchase-grid.item-grid{grid-template-columns:1fr}.purchase-section-head,.purchase-row-foot{align-items:stretch;flex-direction:column}.purchase-actions{flex-wrap:wrap}}
</style>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/purchases/create.blade.php ENDPATH**/ ?>