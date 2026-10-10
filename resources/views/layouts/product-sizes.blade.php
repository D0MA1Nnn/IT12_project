<script data-product-sizes-script>
document.addEventListener('DOMContentLoaded', function () {
    const groups = Array.from(document.querySelectorAll('[data-size-group]'));

    function selectSize(group, productId) {
        const variants = Array.from(group.querySelectorAll('[data-size-product]'));
        if (!variants.some(variant => variant.dataset.sizeProduct === String(productId))) {
            return;
        }

        variants.forEach(variant => {
            variant.hidden = variant.dataset.sizeProduct !== String(productId);
        });
        group.querySelectorAll('[data-size-selector]').forEach(selector => {
            selector.value = String(productId);
        });
    }

    const managedProductId = {{ Illuminate\Support\Js::from(session('manage_product_units', session('selected_product_size'))) }};
    groups.forEach(group => {
        group.querySelectorAll('[data-size-selector]').forEach(selector => {
            selector.addEventListener('change', function () {
                selectSize(group, selector.value);
            });
        });

        if (managedProductId !== null) {
            selectSize(group, managedProductId);
        }
    });

    const search = document.getElementById('productSearch');
    search?.addEventListener('input', function () {
        const term = search.value.trim().toLowerCase();
        if (!term) {
            return;
        }

        groups.forEach(group => {
            const variants = Array.from(group.querySelectorAll('[data-size-product]'));
            const matching = variants.filter(variant => (variant.dataset.search || '').includes(term));
            if (matching.length > 0 && matching.length < variants.length && matching.every(variant => variant.hidden)) {
                selectSize(group, matching[0].dataset.sizeProduct);
            }
        });
    });
});
</script>
