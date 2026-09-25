<?php $__env->startSection('title', 'Purchases'); ?>

<?php $__env->startSection('content'); ?>
<div class="top">
    <div>
        <h1>Supplier & Purchasing</h1>
        <div class="muted">
            Manage supplier records and accepted supplier deliveries
        </div>
    </div>
    <div class="who">Owner</div>
</div>

<div class="tabs">
    <a href="<?php echo e(route('suppliers.index')); ?>">Suppliers</a>
    <a class="active" href="<?php echo e(route('purchases.index')); ?>">Purchases</a>
</div>

<div class="toolbar">
    <a class="btn primary" href="<?php echo e(route('purchases.create')); ?>">
        + Record Purchase
    </a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
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
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $purchase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong>PUR-<?php echo e(str_pad($purchase->purchase_id, 4, '0', STR_PAD_LEFT)); ?></strong></td>
                        <td><?php echo e($purchase->purchase_date->format('m/d/Y g:i A')); ?></td>
                        <td><?php echo e($purchase->supplier?->supplier_name ?? '—'); ?></td>
                        <td><?php echo e($purchase->items->count()); ?></td>
                        <td>₱<?php echo e(number_format((float) $purchase->total_amount, 2)); ?></td>
                        <td><?php echo e($purchase->user?->username ?? '—'); ?></td>
                        <td><?php echo e($purchase->status); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="muted" style="text-align:center;padding:30px;">No purchase records yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\IT12_project\resources\views/purchases/index.blade.php ENDPATH**/ ?>