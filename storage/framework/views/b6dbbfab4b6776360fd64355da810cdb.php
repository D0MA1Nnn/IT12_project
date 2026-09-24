<?php $__env->startSection('title','Activity Logs'); ?>
<?php $__env->startSection('content'); ?>
<div class="top"><div><h1>Activity Logs</h1><div class="muted">Review important user and transaction activities recorded by the system.</div></div><div class="who">Owner</div></div>
<div class="card"><form method="GET" class="toolbar" style="justify-content:flex-start"><select name="module" style="max-width:180px"><option value="">All Modules</option><?php $__currentLoopData = ['AUTH','PRODUCT','CATEGORY','SUPPLIER','PURCHASE','SALE','USER','INVENTORY','BACKUP','UNIT']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m); ?>" <?php if(request('module')===$m): echo 'selected'; endif; ?>><?php echo e($m); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><input class="input" style="max-width:180px" type="date" name="date" value="<?php echo e(request('date')); ?>"><button class="btn primary">Filter</button><a class="btn light" href="<?php echo e(route('activity.index')); ?>">Clear</a></form><table class="table"><thead><tr><th>DATE & TIME</th><th>USER</th><th>MODULE</th><th>ACTION</th><th>DESCRIPTION</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($l->created_at?->format('M d, Y h:i A')); ?></td><td><?php echo e($l->user->username ?? 'Unknown'); ?></td><td><span class="badge"><?php echo e($l->module); ?></span></td><td><?php echo e($l->action); ?></td><td><?php echo e($l->description); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">No activity found.</td></tr><?php endif; ?></tbody></table><?php if($logs->hasPages()): ?>
<div style="margin-top:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div class="muted">
        Showing <?php echo e($logs->firstItem()); ?> to <?php echo e($logs->lastItem()); ?> of <?php echo e($logs->total()); ?> results
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
        <?php if($logs->onFirstPage()): ?>
            <span class="btn light" style="opacity:.55; cursor:not-allowed;">&lsaquo; Previous</span>
        <?php else: ?>
            <a class="btn light" href="<?php echo e($logs->previousPageUrl()); ?>">&lsaquo; Previous</a>
        <?php endif; ?>

        <?php $__currentLoopData = $logs->getUrlRange(1, $logs->lastPage()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($page == $logs->currentPage()): ?>
                <span class="btn primary"><?php echo e($page); ?></span>
            <?php else: ?>
                <a class="btn light" href="<?php echo e($url); ?>"><?php echo e($page); ?></a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($logs->hasMorePages()): ?>
            <a class="btn light" href="<?php echo e($logs->nextPageUrl()); ?>">Next &rsaquo;</a>
        <?php else: ?>
            <span class="btn light" style="opacity:.55; cursor:not-allowed;">Next &rsaquo;</span>
        <?php endif; ?>
    </div>
</div>
<?php elseif($logs->total() > 0): ?>
<div class="muted" style="margin-top:16px;">
    Showing <?php echo e($logs->firstItem()); ?> to <?php echo e($logs->lastItem()); ?> of <?php echo e($logs->total()); ?> results
</div>
<?php endif; ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/activity/index.blade.php ENDPATH**/ ?>