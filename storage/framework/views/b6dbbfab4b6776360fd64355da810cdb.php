<?php $__env->startSection('title','Activity Logs'); ?>
<?php $__env->startSection('content'); ?>
<div class="card activity-log-card">
    <form method="GET" class="toolbar activity-log-filter">
        <select name="module" class="activity-log-select">
            <option value="">All Modules</option>
            <?php $__currentLoopData = ['AUTH','PRODUCT','CATEGORY','SUPPLIER','PURCHASE','SALE','USER','INVENTORY','BACKUP','UNIT']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($m); ?>" <?php if(request('module')===$m): echo 'selected'; endif; ?>><?php echo e($m); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <input class="input activity-log-date" type="date" name="date" value="<?php echo e(request('date')); ?>">

        <button class="btn primary">Filter</button>
        <a class="btn light" href="<?php echo e(route('activity.index')); ?>">Clear</a>
    </form>

    <div class="activity-log-table-scroll">
        <table class="table activity-log-table">
            <thead>
                <tr>
                    <th>DATE & TIME</th>
                    <th>USER</th>
                    <th>MODULE</th>
                    <th>ACTION</th>
                    <th>DESCRIPTION</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($l->created_at?->format('M d, Y h:i A')); ?></td>
                        <td><?php echo e($l->user->username ?? 'Unknown'); ?></td>
                        <td><span class="badge"><?php echo e($l->module); ?></span></td>
                        <td><?php echo e($l->action); ?></td>
                        <td><?php echo e($l->description); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5">No activity found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php if($logs->hasPages()): ?>
<?php
    $currentPage = $logs->currentPage();
    $lastPage = $logs->lastPage();
    $startPage = max(1, min($currentPage - 1, $lastPage - 2));
    $endPage = min($lastPage, $startPage + 2);
?>
<div class="activity-log-pagination">
    <div class="muted">
        Showing <?php echo e($logs->firstItem()); ?> to <?php echo e($logs->lastItem()); ?> of <?php echo e($logs->total()); ?> results
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
        <?php if($logs->onFirstPage()): ?>
            <span class="btn light" style="opacity:.55; cursor:not-allowed;">&lsaquo; Previous</span>
        <?php else: ?>
            <a class="btn light" href="<?php echo e($logs->previousPageUrl()); ?>">&lsaquo; Previous</a>
        <?php endif; ?>

        <?php for($page = $startPage; $page <= $endPage; $page++): ?>
            <?php if($page === $currentPage): ?>
                <span class="btn primary"><?php echo e($page); ?></span>
            <?php else: ?>
                <a class="btn light" href="<?php echo e($logs->url($page)); ?>"><?php echo e($page); ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($logs->hasMorePages()): ?>
            <a class="btn light" href="<?php echo e($logs->nextPageUrl()); ?>">Next &rsaquo;</a>
        <?php else: ?>
            <span class="btn light" style="opacity:.55; cursor:not-allowed;">Next &rsaquo;</span>
        <?php endif; ?>
    </div>
</div>
<?php elseif($logs->total() > 0): ?>
<div class="muted activity-log-results">
    Showing <?php echo e($logs->firstItem()); ?> to <?php echo e($logs->lastItem()); ?> of <?php echo e($logs->total()); ?> results
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

.activity-log-card {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    max-height: none !important;
    overflow: hidden;
}

.activity-log-filter {
    flex: 0 0 auto;
    width: fit-content;
    max-width: 100%;
    justify-content: flex-start;
    margin-bottom: 22px;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}

.activity-log-select,
.activity-log-date {
    max-width: 180px;
}

.activity-log-table-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.activity-log-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.activity-log-pagination,
.activity-log-results {
    flex: 0 0 auto;
    margin-top: 16px;
}

.activity-log-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/activity/index.blade.php ENDPATH**/ ?>