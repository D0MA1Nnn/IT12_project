@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div class="tabs">
    <a href="{{ route('suppliers.index') }}">Suppliers</a>
    <a class="active" href="{{ route('purchases.index') }}">Purchases</a>
</div>

<div class="purchase-toolbar">
    <form method="GET" action="{{ route('purchases.index') }}" class="purchase-filters" data-auto-filter>
        <div class="purchase-search">
            <span class="purchase-search-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                    <path d="M20 20L16.65 16.65" stroke-width="2" stroke-linecap="round"></path>
                </svg>
            </span>

            <input
                type="text"
                name="search"
                class="input"
                value="{{ $search ?? '' }}"
                placeholder="Search purchases..."
                autocomplete="off"
            >
        </div>

        @if(!empty($search))
            <a href="{{ route('purchases.index') }}" class="btn light purchase-filter-button">Reset</a>
        @endif
    </form>

    <button type="button" class="btn primary" id="openPurchaseModal">
        + Record Purchase
    </button>
</div>

<div class="card purchase-records-card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date / Time</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Recorded By</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td><strong>PUR-{{ str_pad($purchase->purchase_id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $purchase->purchase_date->format('m/d/Y g:i A') }}</td>
                        <td>{{ $purchase->supplier?->supplier_name ?? '—' }}</td>
                        <td>{{ $purchase->items->count() }}</td>
                        <td>₱{{ number_format((float) $purchase->total_amount, 2) }}</td>
                        <td>{{ $purchase->user?->username ?? '—' }}</td>
                        <td>{{ $purchase->status }}</td>
                        <td>
                            <button type="button" class="btn light small" data-view-purchase="purchaseDetailsModal{{ $purchase->purchase_id }}" aria-haspopup="dialog" aria-controls="purchaseDetailsModal{{ $purchase->purchase_id }}">View</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted" style="text-align:center;padding:30px;">No purchase records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($purchases as $purchase)
    <div class="purchase-modal-overlay" id="purchaseDetailsModal{{ $purchase->purchase_id }}" data-purchase-details-modal role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="purchaseDetailsTitle{{ $purchase->purchase_id }}">
        <div class="purchase-modal">
            <div class="purchase-modal-header">
                <div>
                    <h2 id="purchaseDetailsTitle{{ $purchase->purchase_id }}">Purchase Details</h2>
                    <div class="muted">PUR-{{ str_pad($purchase->purchase_id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
                <button type="button" class="purchase-modal-close" data-close-purchase-details aria-label="Close purchase details">&times;</button>
            </div>
            <div class="purchase-modal-body">
                <dl class="purchase-details-meta">
                    <div><dt>Supplier</dt><dd>{{ $purchase->supplier?->supplier_name ?? '—' }}</dd></div>
                    <div><dt>Date / Time</dt><dd>{{ $purchase->purchase_date->format('m/d/Y g:i A') }}</dd></div>
                    <div><dt>Recorded By</dt><dd>{{ $purchase->user?->username ?? '—' }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $purchase->status }}</dd></div>
                </dl>
                <div class="purchase-divider"></div>
                <div class="purchase-section-head"><h3>Purchased Items</h3></div>
                <div class="purchase-details-items">
                    <table class="table">
                        <thead>
                            <tr><th>Product</th><th>Unit</th><th>Quantity</th><th>Purchase Cost / Unit</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                            @forelse($purchase->items as $item)
                                <tr>
                                    <td>{{ $item->productUnit?->product?->product_name ?? 'Product' }}</td>
                                    <td>{{ $item->productUnit?->unit?->unit_name ?? 'Unit' }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 6, '.', ''), '0'), '.') }}</td>
                                    <td>₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                                    <td>₱{{ number_format((float) $item->subtotal, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">No purchase items found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="purchase-summary"><span>Purchase Total</span><strong>₱{{ number_format((float) $purchase->total_amount, 2) }}</strong></div>
            </div>
            <div class="purchase-modal-footer">
                <button type="button" class="btn light" data-close-purchase-details>Close</button>
            </div>
        </div>
    </div>
@endforeach

<div class="purchase-modal-overlay {{ $errors->any() ? 'show' : '' }}" id="purchaseModal">
    <div class="purchase-modal">
        <div class="purchase-modal-header">
            <div>
                <h2>Record Purchase</h2>
                <div class="muted">Enter materials after supplier delivery is accepted.</div>
            </div>

            <button type="button" class="purchase-modal-close" id="closePurchaseModal">&times;</button>
        </div>

        <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm">
            @csrf

            <div class="purchase-modal-body">
                <div class="purchase-grid two">
                    <div class="field">
                        <label>Supplier</label>
                        <select name="supplier_id" id="purchaseSupplier" required>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Purchase Date</label>
                        <input class="input" type="datetime-local" name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                </div>

                <div class="purchase-divider"></div>

                <div class="purchase-section-head">
                    <div>
                        <h3>Purchase Items</h3>
                        <p>Select a product first, then choose the purchase unit.</p>
                    </div>
                    <button type="button" class="btn light" id="addPurchaseItem">+ Add Item</button>
                </div>

                <div id="purchaseRows"></div>

                <div class="purchase-summary">
                    <span>Purchase Total</span>
                    <strong id="purchaseTotal">₱0.00</strong>
                </div>
            </div>

            <div class="purchase-modal-footer">
                <button type="button" class="btn light" id="cancelPurchaseModal">Cancel</button>
                <button type="submit" class="btn primary">Save Completed Purchase</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script data-purchase-details-script>
(() => {
    let activeModal = null;
    let activeTrigger = null;
    let previousOverflow = '';

    function closePurchaseDetails() {
        if (!activeModal) {
            return;
        }

        activeModal.classList.remove('show');
        activeModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousOverflow;
        activeModal = null;
        activeTrigger?.focus();
        activeTrigger = null;
    }

    document.querySelectorAll('[data-view-purchase]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.viewPurchase);
            if (!modal) {
                return;
            }

            closePurchaseDetails();
            activeModal = modal;
            activeTrigger = button;
            previousOverflow = document.body.style.overflow;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            modal.querySelector('[data-close-purchase-details]').focus();
        });
    });

    document.querySelectorAll('[data-purchase-details-modal]').forEach(modal => {
        modal.querySelectorAll('[data-close-purchase-details]').forEach(button => {
            button.addEventListener('click', closePurchaseDetails);
        });

        modal.addEventListener('click', event => {
            if (event.target === modal) {
                closePurchaseDetails();
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (!activeModal) {
            return;
        }

        if (event.key === 'Escape') {
            closePurchaseDetails();
        } else if (event.key === 'Tab') {
            const buttons = activeModal.querySelectorAll('button:not([disabled])');
            const first = buttons[0];
            const last = buttons[buttons.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
})();
</script>
<script>
(() => {
    const modal = document.getElementById('purchaseModal');
    const openModal = document.getElementById('openPurchaseModal');
    const closeModal = document.getElementById('closePurchaseModal');
    const cancelModal = document.getElementById('cancelPurchaseModal');
    const supplierSelect = document.getElementById('purchaseSupplier');
    const productUnits = {!! json_encode($unitData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    const supplierProducts = {!! json_encode($supplierProducts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
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

    function showModal() {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    function productsForSelectedSupplier() {
        const productIds = supplierProducts[String(supplierSelect.value)] || [];
        const allowedIds = new Set(productIds.map(id => String(id)));

        return products.filter(product => allowedIds.has(String(product.id)));
    }

    function productOptions(selected = '') {
        return productsForSelectedSupplier().map(product =>
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
        const available = unitsFor(productSelect.value);

        unitSelect.innerHTML = available.map(unit => {
            const base = unit.is_base_unit ? ' (Base)' : '';
            return `<option value="${unit.id}" ${String(unit.id) === String(selectedUnitId) ? 'selected' : ''}>${escapeHtml(unit.unit_name)}${base} — ${peso(unit.purchase_cost)}</option>`;
        }).join('');

        if (!unitSelect.value && available.length) {
            unitSelect.value = available[0].id;
        }

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

        if (resetCost) {
            costInput.value = Number(unit.purchase_cost || 0).toFixed(2);
        }

        const qty = Math.max(1, Number.parseInt(quantityInput.value || '1', 10));
        quantityInput.value = qty;
        const baseQty = qty * Number(unit.conversion_factor || 0);
        const baseUnit = unitsFor(unit.product_id).find(item => item.is_base_unit) || unitsFor(unit.product_id)[0];

        conversionText.textContent = `${qty} ${unit.unit_name} × ${Number(unit.conversion_factor)} = ${Number(baseQty.toFixed(8))} ${baseUnit?.unit_name || 'base unit'} added to inventory`;
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;

        rowsContainer.querySelectorAll('.purchase-row').forEach(row => {
            const qty = Math.max(1, Number.parseInt(row.querySelector('.purchase-qty').value || '1', 10));
            const cost = Math.max(0, Number(row.querySelector('.purchase-cost').value) || 0);

            total += qty * cost;
            row.querySelector('.purchase-subtotal').textContent = peso(qty * cost);
        });

        totalLabel.textContent = peso(total);
    }

    function addRow(initial = {}) {
        const availableProducts = productsForSelectedSupplier();

        if (!availableProducts.length) {
            rowsContainer.innerHTML = '<div class="purchase-empty">No offered products are assigned to this supplier yet.</div>';
            calculateTotal();
            return;
        }

        const index = rowIndex++;
        const defaultProduct = initial.product_id || availableProducts[0].id;
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
                    <div class="purchase-quantity-control">
                        <button type="button" class="purchase-qty-btn purchase-qty-minus">-</button>
                        <input class="input purchase-qty" type="number" step="1" min="1" inputmode="numeric" name="items[${index}][quantity]" value="${Number.parseInt(initial.quantity || 1, 10)}" required>
                        <button type="button" class="purchase-qty-btn purchase-qty-plus">+</button>
                    </div>
                </div>
                <div class="field">
                    <label>Purchase Cost / Unit</label>
                    <input class="input purchase-cost" type="number" step="0.01" min="0" name="items[${index}][unit_cost]" required>
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
        const minusButton = row.querySelector('.purchase-qty-minus');
        const plusButton = row.querySelector('.purchase-qty-plus');

        fillUnitSelect(row, initial.product_unit_id || null);

        if (initial.unit_cost !== undefined) {
            costInput.value = Number(initial.unit_cost).toFixed(2);
        }

        updateSelectedUnit(row, false);

        productSelect.addEventListener('change', () => fillUnitSelect(row));
        unitSelect.addEventListener('change', () => updateSelectedUnit(row, true));
        qtyInput.addEventListener('input', () => updateSelectedUnit(row, false));
        qtyInput.addEventListener('change', () => updateSelectedUnit(row, false));
        minusButton.addEventListener('click', () => {
            qtyInput.value = Math.max(1, Number.parseInt(qtyInput.value || '1', 10) - 1);
            updateSelectedUnit(row, false);
        });
        plusButton.addEventListener('click', () => {
            qtyInput.value = Math.max(1, Number.parseInt(qtyInput.value || '1', 10) + 1);
            updateSelectedUnit(row, false);
        });
        costInput.addEventListener('input', calculateTotal);
        row.querySelector('.purchase-remove').addEventListener('click', () => {
            row.remove();

            if (!rowsContainer.querySelector('.purchase-row')) {
                addRow();
            }

            calculateTotal();
        });
    }

    openModal.addEventListener('click', showModal);
    closeModal.addEventListener('click', hideModal);
    cancelModal.addEventListener('click', hideModal);
    modal.addEventListener('click', event => {
        if (event.target === modal) {
            hideModal();
        }
    });

    addButton.addEventListener('click', () => addRow());
    supplierSelect.addEventListener('change', () => {
        rowsContainer.innerHTML = '';
        rowIndex = 0;
        addRow();
    });

    addRow();
})();
</script>
@endpush

<style>
.purchase-details-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin:0}.purchase-details-meta dt{font-size:12px;font-weight:800;color:#64748b;margin-bottom:6px}.purchase-details-meta dd{margin:0;overflow-wrap:anywhere}.purchase-details-items{overflow-x:auto}.purchase-details-items .table{min-width:580px}@media(max-width:700px){.purchase-details-meta{grid-template-columns:1fr}}
.purchase-records-card .table{font-size:12px}.purchase-records-card .table th{font-size:11px;font-weight:800}.purchase-records-card .table td{font-size:12px}
.purchase-search .input,.purchase-filter-button,#openPurchaseModal{font-family:Inter,"Segoe UI",Arial,sans-serif;font-size:12px;font-weight:400}
.purchase-toolbar{display:flex;align-items:center;justify-content:space-between;gap:15px;margin:14px 0 22px;flex-wrap:wrap}.purchase-filters{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.purchase-search{position:relative;width:280px}.purchase-search .input{height:40px;padding-left:42px}.purchase-search-icon{position:absolute;left:14px;top:50%;width:16px;height:16px;transform:translateY(-50%);color:#94a3b8;pointer-events:none}.purchase-search-icon svg{width:16px;height:16px;stroke:currentColor}.purchase-filter-button{height:40px;display:inline-flex;align-items:center;justify-content:center;padding-top:0;padding-bottom:0}@media(max-width:800px){.purchase-toolbar{align-items:stretch}.purchase-filters,.purchase-search{width:100%}.purchase-filter-button,#openPurchaseModal{width:100%}}
.purchase-modal-overlay{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(13,25,45,.55)}.purchase-modal-overlay.show{display:flex}.purchase-modal{width:min(900px,100%);max-height:92vh;overflow-y:auto;background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(13,25,45,.2)}.purchase-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;padding:22px 24px 16px;border-bottom:1px solid #edf0f5}.purchase-modal-header h2{margin:0 0 5px;font-size:21px}.purchase-modal-close{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0;border-radius:7px;background:#eef2f8;color:#46536a;font-size:20px;cursor:pointer}.purchase-modal-body{padding:22px 24px}.purchase-modal-footer{display:flex;justify-content:flex-end;gap:8px;padding:0 24px 22px}.purchase-grid{display:grid;gap:16px}.purchase-grid.two{grid-template-columns:repeat(2,minmax(0,1fr))}.purchase-grid.item-grid{grid-template-columns:1.35fr 1.35fr .85fr .8fr}.purchase-divider{height:1px;background:#e8eef6;margin:22px 0}.purchase-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px}.purchase-section-head h3{margin:0;color:#0f172a}.purchase-section-head p{margin:4px 0 0;color:#8492aa;font-size:13px}.purchase-row{border:1px solid #e4eaf2;border-radius:12px;padding:16px;margin-bottom:14px;background:#fff}.purchase-row-top,.purchase-row-foot{display:flex;align-items:center;justify-content:space-between;gap:14px}.purchase-row-top{margin-bottom:14px}.purchase-row-foot{margin-top:12px;padding-top:12px;border-top:1px solid #eef2f7;font-size:13px}.purchase-conversion{color:#64748b}.purchase-quantity-control{display:flex;align-items:center;gap:6px}.purchase-quantity-control .purchase-qty{height:40px;text-align:center;-moz-appearance:textfield}.purchase-quantity-control .purchase-qty::-webkit-outer-spin-button,.purchase-quantity-control .purchase-qty::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}.purchase-qty-btn{width:34px;height:40px;border:1px solid #dbe4f0;border-radius:8px;background:#f8fafc;color:#0f172a;font-weight:800;cursor:pointer}.purchase-qty-btn:hover{background:#eef4ff}.purchase-summary{display:flex;align-items:center;justify-content:flex-end;gap:24px;padding:18px 0;font-size:17px}.purchase-summary strong{font-size:22px;color:#0f172a}.purchase-alert{border-radius:10px;padding:14px 16px;margin-bottom:16px}.purchase-alert.error{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3}.purchase-alert ul{margin:8px 0 0 18px}@media(max-width:1050px){.purchase-grid.item-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.purchase-grid.two,.purchase-grid.item-grid{grid-template-columns:1fr}.purchase-section-head,.purchase-row-foot{align-items:stretch;flex-direction:column}.purchase-modal-footer{flex-wrap:wrap}}
</style>
