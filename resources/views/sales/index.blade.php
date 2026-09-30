@extends('layouts.app')

@section('title', 'Sales Management')

@section('content')
@include('sales.partials.module-tabs', ['activeSalesTab' => 'pending'])

@if(session('success'))
    <div class="alert success" style="margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert danger" style="margin-bottom:16px;">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert danger" style="margin-bottom:16px;">
        <strong>Please check the delivery form.</strong>
        <ul style="margin:8px 0 0 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="GET" action="{{ route('sales.index') }}" class="toolbar sales-filter-bar">
    <div class="sales-filter-controls">
        <input
            class="input"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search sale reference or user..."
        >

        <button class="btn primary">Filter</button>

        @if(request()->filled('q'))
            <a class="btn light" href="{{ route('sales.index') }}">Clear</a>
        @endif
    </div>
</form>

<div class="card sales-table-card">
    <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date / Time</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td><strong>SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $sale->sale_date->format('m/d/Y g:i A') }}</td>
                        <td>{{ $sale->items->count() }}</td>
                        <td>₱{{ number_format((float) $sale->total_amount, 2) }}</td>
                        <td>Delivery</td>
                        <td>
                            @if($sale->status === 'PENDING')
                                <span class="sale-status pending">For Delivery</span>
                            @elseif($sale->status === 'CANCELLED')
                                <span class="sale-status cancelled">Cancelled Delivery</span>
                            @else
                                <span class="sale-status completed">Delivered Successfully</span>
                            @endif
                        </td>
                        <td>{{ $sale->user?->username ?? '—' }}</td>
                        <td>
                            @if($sale->delivery_required && $sale->status === 'PENDING')
                                <div class="sales-actions">
                                    <button
                                        type="button"
                                        class="btn light small"
                                        data-open-modal="deliveryDetailsModal{{ $sale->sale_id }}"
                                    >
                                        View Details
                                    </button>

                                    <form method="POST" action="{{ route('sales.complete-delivery', $sale) }}" onsubmit="return confirm('Confirm that the customer successfully received this delivery?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn success small">Delivered Successfully</button>
                                    </form>

                                    <button
                                        type="button"
                                        class="btn danger small"
                                        data-open-modal="cancelDeliveryModal{{ $sale->sale_id }}"
                                    >
                                        Cancelled Delivery
                                    </button>
                                </div>

                                <div class="delivery-modal" id="deliveryDetailsModal{{ $sale->sale_id }}">
                                    <div class="delivery-modal-card delivery-details-card">
                                        <div class="delivery-modal-head">
                                            <div>
                                                <h2>Delivery Details</h2>
                                                <div class="muted">SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</div>
                                            </div>

                                            <button type="button" class="delivery-modal-close" data-close-modal>&times;</button>
                                        </div>

                                        <div class="delivery-detail-grid">
                                            <div>
                                                <span>Date / Time</span>
                                                <strong>{{ $sale->sale_date->format('m/d/Y g:i A') }}</strong>
                                            </div>

                                            <div>
                                                <span>Status</span>
                                                <strong>For Delivery</strong>
                                            </div>

                                            <div>
                                                <span>Total Amount</span>
                                                <strong>₱{{ number_format((float) $sale->total_amount, 2) }}</strong>
                                            </div>

                                            <div>
                                                <span>Recorded By</span>
                                                <strong>{{ $sale->user?->username ?? '—' }}</strong>
                                            </div>
                                        </div>

                                        <div class="delivery-customer-box">
                                            <strong>Customer Delivery Information</strong>
                                            <div><span>Customer Name:</span> {{ $sale->customer_name ?? '—' }}</div>
                                            <div><span>Contact Number:</span> {{ $sale->customer_contact_number ?? '—' }}</div>
                                            <div><span>Address:</span> {{ $sale->delivery_address ?? '—' }}</div>
                                        </div>

                                        <div class="delivery-items-box">
                                            <strong>Products</strong>

                                            @foreach($sale->items as $item)
                                                @php
                                                    $productUnit = $item->productUnit;
                                                    $product = $productUnit?->product;
                                                    $unit = $productUnit?->unit;
                                                    $quantity = rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.');
                                                @endphp

                                                <div class="delivery-item-row">
                                                    <div>
                                                        <strong>{{ $product?->product_name ?? 'Product' }}</strong>
                                                        <div class="muted">
                                                            {{ $quantity }} {{ $unit?->unit_name ?? 'Unit' }}
                                                            × ₱{{ number_format((float) $item->unit_price, 2) }}
                                                        </div>
                                                    </div>

                                                    <strong>₱{{ number_format((float) $item->subtotal, 2) }}</strong>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="delivery-modal-actions">
                                            <button type="button" class="btn light" data-close-modal>Close</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="delivery-modal" id="cancelDeliveryModal{{ $sale->sale_id }}">
                                    <div class="delivery-modal-card">
                                        <div class="delivery-modal-head">
                                            <div>
                                                <h2>Cancelled Delivery</h2>
                                                <div class="muted">SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</div>
                                            </div>

                                            <button type="button" class="delivery-modal-close" data-close-modal>&times;</button>
                                        </div>

                                        <form method="POST" action="{{ route('sales.cancel-delivery', $sale) }}" class="cancel-delivery-form" onsubmit="return confirm('Cancel this delivery? All products will be returned to inventory.')">
                                            @csrf
                                            @method('PATCH')

                                            <label for="delivery_cancel_reason_{{ $sale->sale_id }}">
                                                Why is this delivery cancelled?
                                            </label>

                                            <textarea
                                                id="delivery_cancel_reason_{{ $sale->sale_id }}"
                                                name="delivery_cancel_reason"
                                                class="input"
                                                rows="5"
                                                required
                                                placeholder="Enter the cancellation reason..."
                                            ></textarea>

                                            <div class="muted cancel-inventory-note">
                                                After submitting, all products in this sale will be returned to inventory.
                                            </div>

                                            <div class="delivery-modal-actions">
                                                <button type="button" class="btn light" data-close-modal>Go Back</button>
                                                <button class="btn danger">Submit Cancellation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center;padding:30px">No deliveries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($sales->hasPages())
    <div class="sales-pagination">
        <a class="btn light small {{ $sales->onFirstPage() ? 'disabled-link' : '' }}" href="{{ $sales->previousPageUrl() ?: '#' }}">Previous</a>
        <span class="muted">Page {{ $sales->currentPage() }} of {{ $sales->lastPage() }}</span>
        <a class="btn light small {{ $sales->hasMorePages() ? '' : 'disabled-link' }}" href="{{ $sales->nextPageUrl() ?: '#' }}">Next</a>
    </div>
@endif

<style>
.sales-filter-bar,
.sales-filter-controls,
.sales-actions,
.sales-pagination {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.sales-actions {
    align-items: center;
}

.delivery-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(8, 18, 34, .58);
}

.delivery-modal.open {
    display: flex;
}

.delivery-modal-card {
    width: min(520px, 100%);
    max-height: min(720px, 92vh);
    overflow-y: auto;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .25);
}

.delivery-details-card {
    width: min(640px, 100%);
}

.delivery-modal-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px;
    border-bottom: 1px solid #edf0f4;
}

.delivery-modal-head h2 {
    margin: 0 0 4px 0;
}

.delivery-modal-close {
    border: 0;
    color: #718096;
    background: transparent;
    font-size: 27px;
    cursor: pointer;
}

.cancel-delivery-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 800;
}

.cancel-delivery-form,
.delivery-customer-box,
.delivery-items-box {
    padding: 18px 24px;
}

.cancel-delivery-form textarea {
    width: 100%;
    resize: vertical;
}

.cancel-inventory-note {
    margin-top: 8px;
}

.delivery-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding: 18px 24px 0;
}

.delivery-detail-grid > div {
    padding: 12px;
    border: 1px solid #edf0f4;
    border-radius: 10px;
    background: #f8fafc;
}

.delivery-detail-grid span,
.delivery-customer-box span {
    display: block;
    margin-bottom: 4px;
    color: #718096;
    font-size: 12px;
    font-weight: 800;
}

.delivery-customer-box,
.delivery-items-box {
    border-top: 1px solid #edf0f4;
}

.delivery-customer-box > strong,
.delivery-items-box > strong {
    display: block;
    margin-bottom: 10px;
}

.delivery-customer-box div {
    margin-top: 8px;
}

.delivery-item-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 0;
    border-top: 1px solid #edf0f4;
}

.delivery-item-row:first-of-type {
    border-top: 0;
}

.delivery-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 18px 24px;
    background: #f7f9fc;
}

.sales-filter-bar {
    justify-content: space-between;
}

.sales-filter-controls {
    flex: 1;
}

.sales-filter-controls input {
    max-width: 260px;
}

.sales-filter-controls select {
    max-width: 190px;
}

.sales-table-card {
    padding: 0;
    overflow: hidden;
}

.sales-pagination {
    justify-content: center;
    margin-top: 18px;
}

.sale-status {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.sale-status.pending { background: #fff3cd; color: #9a6700; }
.sale-status.completed { background: #dff7e9; color: #08783d; }
.sale-status.cancelled { background: #fde2e4; color: #b4232d; }
.disabled-link { pointer-events: none; opacity: .45; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document
        .querySelectorAll('[data-open-modal]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = document.getElementById(button.dataset.openModal);

                if (modal) {
                    modal.classList.add('open');
                }
            });
        });

    document
        .querySelectorAll('[data-close-modal]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = button.closest('.delivery-modal');

                if (modal) {
                    modal.classList.remove('open');
                }
            });
        });

    document
        .querySelectorAll('.delivery-modal')
        .forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.classList.remove('open');
                }
            });
        });
});
</script>
@endsection
