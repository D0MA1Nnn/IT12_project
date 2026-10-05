<?php
    $activeSalesTab = $activeSalesTab ?? '';
?>

<div class="sales-module-tabs no-print">
    <a class="sales-module-tab <?php echo e($activeSalesTab === 'cashiering' ? 'active' : ''); ?>" href="<?php echo e(route('sales.create')); ?>">
        Cashiering
    </a>

    <a class="sales-module-tab <?php echo e($activeSalesTab === 'pending' ? 'active' : ''); ?>" href="<?php echo e(route('sales.index')); ?>">
        Delivery
    </a>

</div>

<style>
.sales-module-tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 4px;
    margin-bottom: 18px;
    border-bottom: 1px solid #dbe4f0;
}

.sales-module-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0 18px;
    border: 1px solid #e1e7ef;
    border-bottom: 0;
    border-radius: 10px 10px 0 0;
    background: #eef3f9;
    color: #64748b;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
}

.sales-module-tab.active {
    background: #ffffff;
    color: #2563eb;
    border-color: #dbe4f0;
    box-shadow: 0 -1px 0 rgba(15, 23, 42, .03);
}
</style>
<?php /**PATH C:\Projects\IT12_project\resources\views/sales/partials/module-tabs.blade.php ENDPATH**/ ?>