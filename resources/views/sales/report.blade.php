@extends('layouts.app')

@section('title', 'Report')

@section('content')
<div class="print-report-title">
    <h1>Report — {{ $isPurchaseReport ? 'Purchases List' : 'Sales List' }}</h1>
    <p>
        Generated {{ now()->format('M d, Y g:i A') }}
    </p>
</div>

<div class="sales-report-summary {{ $isPurchaseReport ? 'purchase-report-summary' : '' }}">
    <div class="card sales-report-stat">
        <span class="stat-label">{{ $isPurchaseReport ? 'Completed Purchases' : 'Completed Sales' }}</span>
        <strong>{{ $totalRecords }}</strong>
    </div>

    <div class="card sales-report-stat">
        <span class="stat-label">{{ $isPurchaseReport ? 'Total Purchase Amount' : 'Total Sales Amount' }}</span>
        <strong>₱{{ number_format($totalAmount, 2) }}</strong>
    </div>

    <div class="card sales-report-stat">
        <span class="stat-label">{{ $isPurchaseReport ? 'Average Purchase' : 'Average Sale' }}</span>
        <strong>₱{{ number_format($averageAmount, 2) }}</strong>
    </div>

    @unless($isPurchaseReport)
    <div class="card sales-report-stat sales-report-split">
        <div>
            <span class="stat-label">Walk-in</span>
            <strong>{{ $walkInSales }}</strong>
        </div>

        <div>
            <span class="stat-label">Delivery</span>
            <strong>{{ $deliverySales }}</strong>
        </div>
    </div>
    @endunless
</div>

<form method="GET" action="{{ route('sales.report') }}" class="toolbar sales-report-filter no-print" data-auto-filter>
    <div class="sales-report-search">
        <span>⌕</span>
        <input
            name="q"
            value="{{ old('q', request('q')) }}"
            placeholder="{{ $isPurchaseReport ? 'Search purchase reference, supplier or user...' : 'Search sale reference or user...' }}"
        >
    </div>
    <select name="report_type" class="sales-report-list" aria-label="Report list">
        <option value="sales" @selected($reportType === 'sales')>Sales List</option>
        @if(auth()->user()->role === 'OWNER')
            <option value="purchases" @selected($reportType === 'purchases')>Purchases List</option>
        @endif
    </select>

    <div class="sales-report-date">
        <label for="report-from">From</label>
        <input
            type="date"
            id="report-from"
            name="from"
            value="{{ old('from', request('from')) }}"
            max="{{ now()->toDateString() }}"
            title="Use a valid date only."
        >
        <button type="button" class="report-date-picker" data-report-date-picker="report-from" aria-label="Choose From date">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2M8 18h2"></path></svg>
        </button>
    </div>

    <div class="sales-report-date">
        <label for="report-to">To</label>
        <input
            type="date"
            id="report-to"
            name="to"
            value="{{ old('to', request('to')) }}"
            max="{{ now()->toDateString() }}"
            title="Use a valid date only."
        >
        <button type="button" class="report-date-picker" data-report-date-picker="report-to" aria-label="Choose To date">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2M8 18h2"></path></svg>
        </button>
    </div>

    <div class="sales-report-filter-actions">
        <button type="button" class="btn primary sales-report-print" data-report-print-url="{{ route('sales.report', ['report_type' => $reportType, 'q' => $filters['q'] ?? null, 'from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null, 'print' => 1]) }}">
            Print Report
        </button>
    </div>
</form>
<div data-report-print-error role="alert" class="no-print" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;max-width:calc(100vw - 32px);padding:18px 22px;border:1px solid #fecaca;border-radius:12px;background:#fff1f2;color:#b91c1c;box-shadow:0 12px 30px rgba(15,23,42,.12)"></div>

<div class="card sales-report-table">
    <div class="sales-report-table-title">
        <div>
            <h3>{{ $isPurchaseReport ? 'Purchases List' : 'Sales List' }}</h3>
            <p>
                @if(request()->filled('from') || request()->filled('to'))
                    Showing filtered {{ $isPurchaseReport ? 'purchases' : 'sales' }} from
                    {{ request('from') ? \Carbon\Carbon::parse(request('from'))->format('M d, Y') : 'the beginning' }}
                    to
                    {{ request('to') ? \Carbon\Carbon::parse(request('to'))->format('M d, Y') : 'today' }}.
                @else
                    Showing {{ request()->filled('q') ? 'matching' : 'all' }} {{ $isPurchaseReport ? 'purchases' : 'sales' }}.
                @endif
            </p>
        </div>
    </div>

    <div class="sales-report-table-scroll">
        <table class="table sales-report-data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date / Time</th>
                    @if($isPurchaseReport)
                        <th>Supplier</th>
                    @endif
                    <th>Items</th>
                    <th>Total Amount</th>
                    @unless($isPurchaseReport)
                        <th>Delivery</th>
                    @endunless
                    <th>Recorded By</th>
                    @if($isPurchaseReport)
                        <th>Status</th>
                    @endif
                    <th class="no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td><strong>{{ $isPurchaseReport ? 'PUR-' : 'SALE-' }}{{ str_pad($record->getKey(), 4, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ ($isPurchaseReport ? $record->purchase_date : $record->sale_date)->format('m/d/Y g:i A') }}</td>
                        @if($isPurchaseReport)
                            <td>{{ $record->supplier?->supplier_name ?? '—' }}</td>
                        @endif
                        <td>{{ $record->items_count }}</td>
                        <td><strong>₱{{ number_format((float) $record->total_amount, 2) }}</strong></td>
                        @unless($isPurchaseReport)
                        <td>
                            <span class="delivery-badge {{ $record->delivery_required ? 'required' : 'walk-in' }}">
                                {{ $record->delivery_required ? 'Required' : 'Walk-in' }}
                            </span>
                        </td>
                        @endunless
                        <td>{{ $record->user?->username ?? '—' }}</td>
                        @if($isPurchaseReport)
                            <td>{{ $record->status }}</td>
                        @endif
                        <td class="no-print"><button type="button" class="btn light small" data-view-report="reportDetailsModal{{ $record->getKey() }}" aria-haspopup="dialog" aria-controls="reportDetailsModal{{ $record->getKey() }}">View</button></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isPurchaseReport ? 8 : 7 }}" class="muted" style="text-align:center;padding:30px">No {{ $isPurchaseReport ? 'purchases' : 'sales' }} found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        @php
            $currentPage = $records->currentPage();
            $lastPage = $records->lastPage();
            $startPage = max(1, min($currentPage - 1, $lastPage - 2));
            $endPage = min($lastPage, $startPage + 2);
        @endphp

        <div class="sales-report-pagination no-print">
            <div class="muted">
                Showing {{ $records->firstItem() }} to {{ $records->lastItem() }} of {{ $records->total() }} results
            </div>

            <div class="sales-report-page-links">
                @if($records->onFirstPage())
                    <span class="sales-report-page-link disabled-link">&lsaquo; Previous</span>
                @else
                    <a class="sales-report-page-link" href="{{ $records->previousPageUrl() }}">&lsaquo; Previous</a>
                @endif

                @for($page = $startPage; $page <= $endPage; $page++)
                    @if($page === $currentPage)
                        <span class="sales-report-page-link active">{{ $page }}</span>
                    @else
                        <a class="sales-report-page-link" href="{{ $records->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($records->hasMorePages())
                    <a class="sales-report-page-link" href="{{ $records->nextPageUrl() }}">Next &rsaquo;</a>
                @else
                    <span class="sales-report-page-link disabled-link">Next &rsaquo;</span>
                @endif
            </div>
        </div>
    @endif
</div>

@foreach($records as $record)
    <div class="report-modal-overlay no-print" id="reportDetailsModal{{ $record->getKey() }}" data-report-details-modal role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="reportDetailsTitle{{ $record->getKey() }}">
        <div class="report-modal">
            <div class="report-modal-header">
                <h2 id="reportDetailsTitle{{ $record->getKey() }}">{{ $isPurchaseReport ? 'Purchase Details' : 'Sale Details' }}</h2>
                <button type="button" class="btn light" data-close-report-details aria-label="Close details">&times;</button>
            </div>
            <div class="report-modal-body">
                @include('sales.report-details', ['record' => $record, 'isPurchaseReport' => $isPurchaseReport])
            </div>
            <div class="report-modal-footer"><button type="button" class="btn light" data-close-report-details>Close</button></div>
        </div>
    </div>
@endforeach
@include('sales.report-details-styles')

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

.print-report-title {
    display: none;
}

.sales-report-filter,
.sales-report-summary,
.sales-report-pagination {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.sales-report-filter {
    flex: 0 0 auto;
    display: grid !important;
    grid-template-columns: minmax(180px, 1fr) minmax(145px, 180px) minmax(190px, 210px) minmax(190px, 210px) auto !important;
    gap: 12px;
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid #edf1f6;
    border-radius: 12px;
    background: #fff;
    width: 100% !important;
    min-width: 0;
    box-sizing: border-box;
}

.sales-report-search,
.sales-report-date,
.sales-report-list {
    height: 44px;
    display: flex;
    align-items: center;
    gap: 9px;
    box-sizing: border-box;
    padding: 0 13px;
    border: 1px solid #dbe3ef;
    border-radius: 9px;
    background: #f8fafc;
}

.sales-report-list {
    width: 100%;
    min-width: 0;
    color: #0f172a;
    font: inherit;
}

.sales-report-search span,
.sales-report-date label {
    flex: 0 0 auto;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    margin: 0;
}

.sales-report-search, .sales-report-date { min-width: 0; }
.sales-report-date { gap: 6px; padding: 0 10px; }
.report-date-picker { flex: 0 0 22px; width: 22px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0; background: transparent; border: 0; color: #475569; cursor: pointer; }
.report-date-picker svg { width: 18px; height: 18px; stroke: currentColor; stroke-width: 1.7; }
.report-date-picker:focus-visible { outline: 2px solid #2468ee; border-radius: 4px; }
.sales-report-date input::-webkit-calendar-picker-indicator { display: none; }

.page-sales-report .sales-report-filter .sales-report-search input,
.page-sales-report .sales-report-filter .sales-report-date input {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    flex: 1 1 auto !important;
    padding: 0 !important;
    margin: 0 !important;
    border: 0;
    outline: 0;
    background: transparent;
    color: #0f172a;
    font: inherit;
}

.sales-report-filter-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    justify-self: end;
    min-width: max-content;
}

.sales-report-print {
    white-space: nowrap;
}

.sales-report-summary {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 14px;
}

.sales-report-stat {
    min-width: 0;
    padding: 18px 20px;
    border: 1px solid #edf1f6;
    box-shadow: none;
}

.purchase-report-summary {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.stat-label {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.sales-report-stat strong {
    display: block;
    margin-top: 8px;
    color: #0f172a;
    font-size: 25px;
    line-height: 1;
}

.sales-report-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.sales-report-table {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    padding: 0;
    overflow: hidden;
}

.sales-report-table-title {
    flex: 0 0 auto;
    padding: 17px 20px;
    border-bottom: 1px solid #edf1f6;
}

.sales-report-table-title h3 {
    margin: 0 0 4px;
    color: #0f172a;
    font-size: 17px;
}

.sales-report-table-title p {
    margin: 0;
    color: #64748b;
    font-size: 12px;
}

.sales-report-table-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.sales-report-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.sales-report-data-table td {
    vertical-align: middle;
}

.delivery-badge {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}

.delivery-badge.walk-in {
    background: #edf7ff;
    color: #1d4ed8;
}

.delivery-badge.required {
    background: #fff4dd;
    color: #b66a00;
}

.sales-report-pagination {
    flex: 0 0 auto;
    justify-content: space-between;
    margin-top: 12px;
    gap: 16px;
}

.sales-report-page-links {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    flex-wrap: wrap;
}

.sales-report-page-link {
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

.sales-report-page-link.active {
    background: #2468ee;
    color: #ffffff;
}

.disabled-link {
    pointer-events: none;
    opacity: .45;
}

@media (max-width: 1200px) {
    .sales-report-filter {
        grid-template-columns: 1fr 1fr !important;
    }

    .sales-report-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .sales-report-filter,
    .sales-report-summary {
        grid-template-columns: 1fr !important;
    }

    .sales-report-filter-actions {
        flex-wrap: wrap;
    }

    .sales-report-pagination {
        justify-content: center;
    }
}

@media print {
    html,
    body,
    .main {
        height: auto !important;
        overflow: visible !important;
    }

    .sidebar,
    .no-print,
    .sales-module-tabs {
        display: none !important;
    }

    .main {
        margin-left: 0 !important;
        padding: 0 !important;
    }

    body {
        background: white !important;
    }

    .print-report-title {
        display: block !important;
        margin-bottom: 18px;
    }

    .print-report-title h1 {
        margin: 0 0 4px;
        color: #0f172a;
    }

    .print-report-title p {
        margin: 0;
        color: #64748b;
    }

    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }

    .sales-report-table-scroll {
        height: auto !important;
        overflow: visible !important;
    }

    .sales-report-summary {
        grid-template-columns: repeat(4, 1fr) !important;
    }

    .purchase-report-summary {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}
</style>
@endsection

@push('scripts')
<script data-report-print-script>
(() => {
    const button = document.querySelector('[data-report-print-url]');
    const error = document.querySelector('[data-report-print-error]');
    if (!button) {
        return;
    }

    const label = button.textContent;
    let frame = null;
    let loadingTimer = null;
    let errorTimer = null;

    function resetButton() {
        clearTimeout(loadingTimer);
        button.disabled = false;
        button.textContent = label;
        button.removeAttribute('aria-busy');
    }

    function removeFrame() {
        frame?.remove();
        frame = null;
    }

    function showPrintError() {
        resetButton();
        removeFrame();
        button.focus();
        if (error) {
            error.textContent = 'Unable to open printing. Please try again.';
            error.style.display = 'block';
            clearTimeout(errorTimer);
            errorTimer = setTimeout(() => error.style.display = 'none', 3000);
        }
    }

    button.addEventListener('click', () => {
        if (button.disabled) {
            return;
        }
        removeFrame();
        clearTimeout(errorTimer);
        if (error) {
            error.style.display = 'none';
        }
        button.disabled = true;
        button.textContent = 'Preparing…';
        button.setAttribute('aria-busy', 'true');

        const printFrame = document.createElement('iframe');
        frame = printFrame;
        printFrame.style.display = 'none';
        printFrame.title = 'Report printing';
        printFrame.tabIndex = -1;
        printFrame.setAttribute('aria-hidden', 'true');
        printFrame.addEventListener('error', showPrintError, { once: true });
        printFrame.addEventListener('load', () => {
            if (frame !== printFrame) {
                return;
            }
            try {
                const printWindow = printFrame.contentWindow;
                if (!printWindow.document.querySelector('[data-report-print-document]')) {
                    showPrintError();
                    return;
                }
                clearTimeout(loadingTimer);
                printWindow.addEventListener('afterprint', () => {
                    if (frame === printFrame) {
                        resetButton();
                        removeFrame();
                        button.focus();
                    }
                }, { once: true });
                printWindow.focus();
                printWindow.print();
                resetButton();
            } catch {
                showPrintError();
            }
        }, { once: true });
        loadingTimer = setTimeout(showPrintError, 30000);
        printFrame.src = button.dataset.reportPrintUrl;
        document.body.appendChild(printFrame);
    });
})();
</script>
<script data-report-calendar-script>
document.querySelectorAll('[data-report-date-picker]').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.reportDatePicker);
        if (!input) {
            return;
        }
        input.focus();
        try {
            if (typeof input.showPicker === 'function') {
                input.showPicker();
            } else {
                input.click();
            }
        } catch {
            input.click();
        }
    });
});
</script>
<script data-report-details-script>
(() => {
    let activeModal = null;
    let activeTrigger = null;
    let previousOverflow = '';

    function closeReportDetails() {
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

    document.querySelectorAll('[data-view-report]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.viewReport);
            if (!modal) {
                return;
            }

            closeReportDetails();
            activeModal = modal;
            activeTrigger = button;
            previousOverflow = document.body.style.overflow;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            modal.querySelector('[data-close-report-details]').focus();
        });
    });

    document.querySelectorAll('[data-report-details-modal]').forEach(modal => {
        modal.querySelectorAll('[data-close-report-details]').forEach(button => {
            button.addEventListener('click', closeReportDetails);
        });
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                closeReportDetails();
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (!activeModal) {
            return;
        }

        if (event.key === 'Escape') {
            closeReportDetails();
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
@endpush
