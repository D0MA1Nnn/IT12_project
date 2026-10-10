@extends('layouts.app')

@section('title', 'Sales Management')

@section('content')
@include('sales.partials.module-tabs', ['activeSalesTab' => 'pending'])

<form method="GET" action="{{ route('sales.index') }}" class="toolbar sales-filter-bar" data-auto-filter>
    <div class="sales-filter-controls">
        <input
            class="input"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search sale reference or user..."
        >

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
                    <th>Payment</th>
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
                        <td>
                            <span class="sale-status {{ $sale->delivery_status === 'DELIVERED' ? 'completed' : 'pending' }}">
                                {{ $sale->delivery_status === 'DELIVERED' ? 'Delivered' : 'For Delivery' }}
                            </span>
                        </td>
                        <td>
                            <span class="sale-status {{ $sale->payment_status === 'PAID' ? 'completed' : 'pending' }}">
                                {{ $sale->payment_method === 'COD' ? 'COD · ' : '' }}{{ $sale->payment_status === 'PAID' ? 'Paid' : 'Unpaid' }}
                            </span>
                        </td>
                        <td>{{ $sale->user?->username ?? '—' }}</td>
                        <td>
                            @if($sale->delivery_required && $sale->status === 'PENDING')
                                <div class="sales-actions">
                                    <a class="btn light small" href="{{ route('sales.receipt', $sale) }}">View Invoice</a>

                                    @if($sale->delivery_status === 'PENDING')
                                        <button type="button" class="btn success small" data-open-modal="confirmDeliveryModal{{ $sale->sale_id }}">Mark Delivered</button>

                                        <button type="button" class="btn danger small" data-open-modal="cancelDeliveryModal{{ $sale->sale_id }}">
                                            Cancel Delivery
                                        </button>
                                    @elseif($sale->payment_status === 'UNPAID')
                                        <button type="button" class="btn success small" data-open-modal="confirmDeliveryModal{{ $sale->sale_id }}">Confirm Paid</button>
                                    @endif
                                </div>

                                @php
                                    $isPendingDelivery = $sale->delivery_status === 'PENDING';
                                    $confirmsCodPayment = $sale->payment_method === 'COD' && $sale->payment_status === 'UNPAID';
                                @endphp
                                <div class="delivery-modal" id="confirmDeliveryModal{{ $sale->sale_id }}" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="confirmDeliveryTitle{{ $sale->sale_id }}" aria-describedby="confirmDeliveryMessage{{ $sale->sale_id }}">
                                    <div class="delivery-modal-card">
                                        <div class="delivery-modal-head">
                                            <div>
                                                <h2 id="confirmDeliveryTitle{{ $sale->sale_id }}">{{ $isPendingDelivery ? 'Complete Delivery?' : 'Confirm COD Payment?' }}</h2>
                                                <div class="muted">SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                            <button type="button" class="delivery-modal-close" data-close-modal aria-label="Close confirmation">&times;</button>
                                        </div>
                                        <form method="POST" action="{{ route($isPendingDelivery ? 'sales.complete-delivery' : 'sales.collect-payment', $sale) }}" data-delivery-confirm-form>
                                            @csrf
                                            @method('PATCH')
                                            <div class="delivery-confirm-body" id="confirmDeliveryMessage{{ $sale->sale_id }}">
                                                <p>{{ $isPendingDelivery ? 'Confirm that the customer has received the products.' : 'This order is already delivered. Confirm that full payment has been collected.' }}</p>
                                                @if($confirmsCodPayment)
                                                    <div class="delivery-confirm-total">
                                                        <span>Full COD payment</span>
                                                        <strong>₱{{ number_format((float) $sale->total_amount, 2) }}</strong>
                                                        <small>including the delivery fee of ₱{{ number_format((float) $sale->delivery_fee, 2) }}</small>
                                                    </div>
                                                    <p class="muted">Only confirm after collecting the full payment. The order will be marked delivered and paid.</p>
                                                @else
                                                    <p class="muted">Payment is already recorded. This will mark the delivery completed without charging the customer again.</p>
                                                @endif
                                            </div>
                                            <div class="delivery-modal-actions">
                                                <button type="button" class="btn light" data-close-modal data-initial-focus>Go Back</button>
                                                <button type="submit" class="btn success">{{ $isPendingDelivery ? ($confirmsCodPayment ? 'Yes, Delivered & Paid' : 'Yes, Mark Delivered') : 'Yes, Confirm Paid' }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="delivery-modal" id="cancelDeliveryModal{{ $sale->sale_id }}" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="cancelDeliveryTitle{{ $sale->sale_id }}">
                                    <div class="delivery-modal-card">
                                        <div class="delivery-modal-head">
                                            <div>
                                                <h2 id="cancelDeliveryTitle{{ $sale->sale_id }}">Cancel Delivery</h2>
                                                <div class="muted">SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</div>
                                            </div>

                                            <button type="button" class="delivery-modal-close" data-close-modal aria-label="Close cancellation">&times;</button>
                                        </div>

                                        <form method="POST" action="{{ route('sales.cancel-delivery', $sale) }}" class="cancel-delivery-form" id="cancelDeliveryForm{{ $sale->sale_id }}" data-cancel-confirm-modal="confirmCancellationModal{{ $sale->sale_id }}">
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
                                                data-initial-focus
                                                placeholder="Enter the cancellation reason..."
                                            ></textarea>

                                            <div class="muted cancel-inventory-note">
                                                After submitting, all products in this sale will be returned to inventory.
                                            </div>

                                            <div class="delivery-modal-actions">
                                                <button type="button" class="btn light" data-close-modal>Go Back</button>
                                                <button type="submit" class="btn danger">Submit Cancellation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="delivery-modal" id="confirmCancellationModal{{ $sale->sale_id }}" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="confirmCancellationTitle{{ $sale->sale_id }}" aria-describedby="confirmCancellationMessage{{ $sale->sale_id }}" data-return-modal="cancelDeliveryModal{{ $sale->sale_id }}">
                                    <div class="delivery-modal-card">
                                        <div class="delivery-modal-head">
                                            <div>
                                                <h2 id="confirmCancellationTitle{{ $sale->sale_id }}">Cancel this delivery?</h2>
                                                <div class="muted">SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                            <button type="button" class="delivery-modal-close" data-close-modal aria-label="Close confirmation">&times;</button>
                                        </div>
                                        <div class="delivery-confirm-body" id="confirmCancellationMessage{{ $sale->sale_id }}">
                                            <p>All products in this sale will be returned to inventory.</p>
                                        </div>
                                        <div class="delivery-modal-actions">
                                            <button type="button" class="btn light" data-close-modal data-initial-focus>Go Back</button>
                                            <button type="button" class="btn danger" data-confirm-cancellation="cancelDeliveryForm{{ $sale->sale_id }}">Yes, Cancel Delivery</button>
                                        </div>
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

    @if($sales->hasPages())
        @php
            $currentPage = $sales->currentPage();
            $lastPage = $sales->lastPage();
            $startPage = max(1, min($currentPage - 1, $lastPage - 2));
            $endPage = min($lastPage, $startPage + 2);
        @endphp

        <div class="sales-pagination">
            <div class="muted">
                Showing {{ $sales->firstItem() }} to {{ $sales->lastItem() }} of {{ $sales->total() }} results
            </div>

            <div class="sales-page-links">
                @if($sales->onFirstPage())
                    <span class="sales-page-link disabled-link">&lsaquo; Previous</span>
                @else
                    <a class="sales-page-link" href="{{ $sales->previousPageUrl() }}">&lsaquo; Previous</a>
                @endif

                @for($page = $startPage; $page <= $endPage; $page++)
                    @if($page === $currentPage)
                        <span class="sales-page-link active">{{ $page }}</span>
                    @else
                        <a class="sales-page-link" href="{{ $sales->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($sales->hasMorePages())
                    <a class="sales-page-link" href="{{ $sales->nextPageUrl() }}">Next &rsaquo;</a>
                @else
                    <span class="sales-page-link disabled-link">Next &rsaquo;</span>
                @endif
            </div>
        </div>
    @endif
</div>

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
.delivery-confirm-body {
    padding: 18px 24px;
}

.delivery-confirm-body p {
    margin: 0;
    line-height: 1.6;
}

.delivery-confirm-total {
    display: grid;
    gap: 6px;
    margin: 18px 0;
    padding: 16px;
    border: 1px solid #e1e8f3;
    border-radius: 12px;
    background: #f7f9fc;
}

.delivery-confirm-total strong {
    font-size: 26px;
}

.delivery-confirm-total small {
    color: #64748b;
}

.cancel-delivery-form textarea {
    width: 100%;
    resize: vertical;
}

.cancel-inventory-note {
    margin-top: 8px;
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
    justify-content: space-between;
    padding: 16px 18px;
    border-top: 1px solid #edf1f6;
}

.sales-page-links {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    flex-wrap: wrap;
}

.sales-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    background: #eef2f8;
    color: #0f172a;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
}

.sales-page-link.active {
    background: #2468ee;
    color: #ffffff;
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
    let activeTrigger = null;

    function openModal(modal) {
        document.querySelectorAll('.delivery-modal.open').forEach(function (openModal) {
            openModal.classList.remove('open');
            openModal.setAttribute('aria-hidden', 'true');
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        const initialFocus = modal.querySelector('[data-initial-focus]') ?? modal.querySelector('[data-close-modal]');
        if (initialFocus) {
            initialFocus.focus();
        }
    }

    function closeModal(modal) {
        if (modal.dataset.submitting === 'true' || modal.querySelector('form[data-submitting="true"]')) {
            return;
        }

        if (modal.dataset.returnModal) {
            openModal(document.getElementById(modal.dataset.returnModal));
            return;
        }

        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (activeTrigger) {
            activeTrigger.focus();
            activeTrigger = null;
        }
    }

    document
        .querySelectorAll('[data-open-modal]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = document.getElementById(button.dataset.openModal);

                if (modal) {
                    activeTrigger = button;
                    openModal(modal);
                }
            });
        });

    document
        .querySelectorAll('[data-close-modal]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = button.closest('.delivery-modal');

                if (modal) {
                    closeModal(modal);
                }
            });
        });

    document
        .querySelectorAll('.delivery-modal')
        .forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });

    document.addEventListener('keydown', function (event) {
        const modal = document.querySelector('.delivery-modal.open');
        if (!modal) {
            return;
        }

        if (event.key === 'Escape') {
            closeModal(modal);
        } else if (event.key === 'Tab') {
            const focusable = modal.querySelectorAll('button:not([disabled]), input:not([disabled]), textarea:not([disabled]), a[href]');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    document.querySelectorAll('[data-cancel-confirm-modal]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                openModal(form.closest('.delivery-modal'));
                form.reportValidity();
                return;
            }

            const confirmationModal = document.getElementById(form.dataset.cancelConfirmModal);
            if (form.dataset.cancellationConfirmed !== 'true') {
                event.preventDefault();
                openModal(confirmationModal);
                return;
            }

            form.dataset.submitting = 'true';
            confirmationModal.dataset.submitting = 'true';
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Cancelling...';
            const confirmButton = confirmationModal.querySelector('[data-confirm-cancellation]');
            confirmButton.disabled = true;
            confirmButton.textContent = 'Cancelling...';
        });
    });

    document.querySelectorAll('[data-confirm-cancellation]').forEach(function (button) {
        button.addEventListener('click', function () {
            const form = document.getElementById(button.dataset.confirmCancellation);
            if (form.dataset.submitting === 'true') {
                return;
            }

            if (!form.checkValidity()) {
                openModal(form.closest('.delivery-modal'));
                form.reportValidity();
                return;
            }

            form.dataset.cancellationConfirmed = 'true';
            form.requestSubmit();
            delete form.dataset.cancellationConfirmed;
        });
    });

    document.querySelectorAll('[data-delivery-confirm-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Completing...';
        });
    });
});
</script>
@endsection
