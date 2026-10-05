<?php $__env->startSection('title', 'Sales Report'); ?>

<?php $__env->startSection('content'); ?>
<div class="print-report-title">
    <h1>Sales Report</h1>
    <p>
        Generated <?php echo e(now()->format('M d, Y g:i A')); ?>

    </p>
</div>

<div class="sales-report-summary">
    <div class="card sales-report-stat">
        <span class="stat-label">Completed Sales</span>
        <strong><?php echo e($totalSales); ?></strong>
    </div>

    <div class="card sales-report-stat">
        <span class="stat-label">Total Sales Amount</span>
        <strong>₱<?php echo e(number_format($totalAmount, 2)); ?></strong>
    </div>

    <div class="card sales-report-stat">
        <span class="stat-label">Average Sale</span>
        <strong>₱<?php echo e(number_format($averageSale, 2)); ?></strong>
    </div>

    <div class="card sales-report-stat sales-report-split">
        <div>
            <span class="stat-label">Walk-in</span>
            <strong><?php echo e($walkInSales); ?></strong>
        </div>

        <div>
            <span class="stat-label">Delivery</span>
            <strong><?php echo e($deliverySales); ?></strong>
        </div>
    </div>
</div>

<form method="GET" action="<?php echo e(route('sales.report')); ?>" class="toolbar sales-report-filter no-print">
    <div class="sales-report-search">
        <span>⌕</span>
        <input
            name="q"
            value="<?php echo e(old('q', request('q'))); ?>"
            placeholder="Search sale reference or user..."
        >
    </div>

    <label class="sales-report-date">
        <span>From</span>
        <input
            type="date"
            name="from"
            value="<?php echo e(old('from', request('from'))); ?>"
            max="<?php echo e(now()->toDateString()); ?>"
            title="Use a valid date only."
        >
    </label>

    <label class="sales-report-date">
        <span>To</span>
        <input
            type="date"
            name="to"
            value="<?php echo e(old('to', request('to'))); ?>"
            max="<?php echo e(now()->toDateString()); ?>"
            title="Use a valid date only."
        >
    </label>

    <div class="sales-report-filter-actions">
        <button class="btn primary">Filter</button>

        <?php if(request()->hasAny(['q', 'from', 'to'])): ?>
            <a class="btn light" href="<?php echo e(route('sales.report')); ?>">Clear</a>
        <?php endif; ?>

        <button type="button" class="btn primary sales-report-print" onclick="window.print()">
            Print Report
        </button>
    </div>
</form>

<div class="card sales-report-table">
    <div class="sales-report-table-title">
        <div>
            <h3>Sales List</h3>
            <p>
                <?php if(request()->filled('from') || request()->filled('to')): ?>
                    Showing filtered sales from
                    <?php echo e(request('from') ? \Carbon\Carbon::parse(request('from'))->format('M d, Y') : 'the beginning'); ?>

                    to
                    <?php echo e(request('to') ? \Carbon\Carbon::parse(request('to'))->format('M d, Y') : 'today'); ?>.
                <?php else: ?>
                    Showing all sales.
                <?php endif; ?>
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
                <?php $__empty_1 = true; $__currentLoopData = $sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong>SALE-<?php echo e(str_pad($sale->sale_id, 4, '0', STR_PAD_LEFT)); ?></strong></td>
                        <td><?php echo e($sale->sale_date->format('m/d/Y g:i A')); ?></td>
                        <td><?php echo e($sale->items->count()); ?></td>
                        <td><strong>₱<?php echo e(number_format((float) $sale->total_amount, 2)); ?></strong></td>
                        <td>
                            <span class="delivery-badge <?php echo e($sale->delivery_required ? 'required' : 'walk-in'); ?>">
                                <?php echo e($sale->delivery_required ? 'Required' : 'Walk-in'); ?>

                            </span>
                        </td>
                        <td><?php echo e($sale->user?->username ?? '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="muted" style="text-align:center;padding:30px">No sales found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($sales->hasPages()): ?>
        <?php
            $currentPage = $sales->currentPage();
            $lastPage = $sales->lastPage();
            $startPage = max(1, min($currentPage - 1, $lastPage - 2));
            $endPage = min($lastPage, $startPage + 2);
        ?>

        <div class="sales-report-pagination no-print">
            <div class="muted">
                Showing <?php echo e($sales->firstItem()); ?> to <?php echo e($sales->lastItem()); ?> of <?php echo e($sales->total()); ?> results
            </div>

            <div class="sales-report-page-links">
                <?php if($sales->onFirstPage()): ?>
                    <span class="sales-report-page-link disabled-link">&lsaquo; Previous</span>
                <?php else: ?>
                    <a class="sales-report-page-link" href="<?php echo e($sales->previousPageUrl()); ?>">&lsaquo; Previous</a>
                <?php endif; ?>

                <?php for($page = $startPage; $page <= $endPage; $page++): ?>
                    <?php if($page === $currentPage): ?>
                        <span class="sales-report-page-link active"><?php echo e($page); ?></span>
                    <?php else: ?>
                        <a class="sales-report-page-link" href="<?php echo e($sales->url($page)); ?>"><?php echo e($page); ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if($sales->hasMorePages()): ?>
                    <a class="sales-report-page-link" href="<?php echo e($sales->nextPageUrl()); ?>">Next &rsaquo;</a>
                <?php else: ?>
                    <span class="sales-report-page-link disabled-link">Next &rsaquo;</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

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
    grid-template-columns: minmax(260px, 1fr) minmax(170px, 220px) minmax(170px, 220px) auto !important;
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
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    flex: 1 1 auto !important;
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
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/sales/report.blade.php ENDPATH**/ ?>