const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/products/index.blade.php'), 'utf8');
const renderer = view.slice(view.indexOf('    function renderProductUnits('), view.indexOf('    function selectedUnitName('));

function renderUnit(overrides = {}) {
    const target = { innerHTML: '' };
    const unit = {
        product_unit_id: 21, name: 'Box', selling_price: 300, purchase_cost: 225,
        conversion_factor: 1.666667, is_base_unit: false, is_active: true,
        toggle_url: '/products/6/units/21/toggle', ...overrides,
    };
    vm.runInNewContext(`${renderer}\nrenderProductUnits([unit]);`, {
        unit, productUnitViewList: target,
        unitsForm: { querySelector: () => ({ value: 'csrf-token' }) },
        escapeHtml: (value) => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('"', '&quot;'),
        peso: (value) => `₱${value.toFixed(2)}`,
    });
    return target.innerHTML;
}

test('active selling units show Edit followed by a protected Disable form', () => {
    const html = renderUnit();

    assert.match(html, /Edit[\s\S]+unit-toggle-form[\s\S]+Disable/);
    assert.match(html, /method="POST" action="\/products\/6\/units\/21\/toggle"/);
    assert.match(html, /name="_token" value="csrf-token"/);
    assert.match(html, /name="_method" value="PATCH"/);
    assert.match(html, /1\.666667x • Active/);
});

test('disabled selling units show Enable instead of Disable', () => {
    const html = renderUnit({ is_active: false });

    assert.match(html, /Enable/);
    assert.doesNotMatch(html, />\s*Disable\s*</);
    assert.match(html, /1\.666667x • Disabled/);
});

test('the base inventory unit has no disable form', () => {
    const html = renderUnit({ is_base_unit: true });

    assert.doesNotMatch(html, /unit-toggle-form|unit-edit-btn|Disable|Enable/);
    assert.match(html, /Base/);
});

test('unit labels and status URLs are escaped in generated HTML', () => {
    const html = renderUnit({ name: '<script>bad</script>', toggle_url: '/toggle?test="bad"' });

    assert.doesNotMatch(html, /<script>/);
    assert.match(html, /&lt;script>/);
    assert.match(html, /action="\/toggle\?test=&quot;bad&quot;"/);
});
