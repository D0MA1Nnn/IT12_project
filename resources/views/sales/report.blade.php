@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="print-report-title">
    <h1>Sales Report</h1>
    <p>
        Generated {{ now()->format('M d, Y g:i A') }}
    </p>
</div>

<div class="sales-report-summary">
    <div class="card sales-report-stat">
        <span class="stat-label">Completed Sales</span>
        <strong>{{ $totalSales }}</strong>
    </div>

    <div class="card sales-report-stat">
        <span class="stat-label">Total Sales Amount</span>
        <strong>₱{{ number_format($totalAmount, 2) }}</strong>
    </div>

    <div class="card sales-report-stat">
        <span class="stat-label">Average Sale</span>
        <strong>₱{{ number_format($averageSale, 2) }}</strong>
    </div>

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
</div>

<form method="GET" action="{{ route('sales.report') }}" class="toolbar sales-report-filter no-print">
    <div class="sales-report-search">
        <span>⌕</span>
        <input
            name="q"
            value="{{ old('q', request('q')) }}"
            placeholder="Search sale reference or user..."
        >
    </div>

    <label class="sales-report-date">
        <span>From</span>
        <input
            type="date"
            name="from"
            value="{{ old('from', request('from')) }}"
        >
    </label>

    <label class="sales-report-date">
        <span>To</span>
        <input
            type="date"
            name="to"
            value="{{ old('to', request('to')) }}"
        >
    </label>

    <div class="sales-report-filter-actions">
        <button class="btn primary">Filter</button>

        @if(request()->hasAny(['q', 'from', 'to']))
            <a class="btn light" href="{{ route('sales.report') }}">Clear</a>
        @endif

        <button type="button" class="btn primary sales-report-print" onclick="window.print()">
            Print Report
        </button>
    </div>
</form>

<div class="card sales-report-table">
    <div class="sales-report-table-title">
        <div>
            <h3>Completed Sales List</h3>
            <p>
                @if(request()->filled('from') || request()->filled('to'))
                    Showing filtered sales from
                    {{ request('from') ? \Carbon\Carbon::parse(request('from'))->format('M d, Y') : 'the beginning' }}
                    to
                    {{ request('to') ? \Carbon\Carbon::parse(request('to'))->format('M d, Y') : 'today' }}.
                @else
                    Showing all completed sales.
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
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Delivery</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td><strong>SALE-{{ str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $sale->sale_date->format('m/d/Y g:i A') }}</td>
                        <td>{{ $sale->items->count() }}</td>
                        <td><strong>₱{{ number_format((float) $sale->total_amount, 2) }}</strong></td>
                        <td>
                            <span class="delivery-badge {{ $sale->delivery_required ? 'required' : 'walk-in' }}">
                                {{ $sale->delivery_required ? 'Required' : 'Walk-in' }}
                            </span>
                        </td>
                        <td>{{ $sale->user?->username ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted" style="text-align:center;padding:30px">No completed sales found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($sales->hasPages())
    <div class="sales-report-pagination no-print">
        <a class="btn light small {{ $sales->onFirstPage() ? 'disabled-link' : '' }}" href="{{ $sales->previousPageUrl() ?: '#' }}">Previous</a>
        <span class="muted">Page {{ $sales->currentPage() }} of {{ $sales->lastPage() }}</span>
        <a class="btn light small {{ $sales->hasMorePages() ? '' : 'disabled-link' }}" href="{{ $sales->nextPageUrl() ?: '#' }}">Next</a>
    </div>
@endif

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
    display: grid;
    grid-template-columns: minmax(330px, 1fr) 175px 175px auto;
    gap: 12px;
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid #edf1f6;
    border-radius: 12px;
    background: #fff;
}

.sales-report-search,
.sales-report-date {
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

.sales-report-search span,
.sales-report-date span {
    flex: 0 0 auto;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.sales-report-search input,
.sales-report-date input {
    width: 100%;
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
    justify-content: center;
    margin-top: 12px;
}

.disabled-link {
    pointer-events: none;
    opacity: .45;
}

@media (max-width: 1100px) {
    .sales-report-filter {
        grid-template-columns: 1fr 1fr;
    }

    .sales-report-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .sales-report-filter,
    .sales-report-summary {
        grid-template-columns: 1fr;
    }

    .sales-report-filter-actions {
        flex-wrap: wrap;
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
}
</style>
@endsection
