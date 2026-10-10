const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/product-sizes.blade.php'), 'utf8');
const script = view.match(/<script[^>]*>([\s\S]*?)<\/script>/)[1]
    .replace(/const managedProductId = .*?;/, 'const managedProductId = managedId;');

function setup(managedId = null) {
    const events = () => ({
        handlers: {},
        addEventListener(type, callback) { this.handlers[type] = callback; },
        dispatch(type) { this.handlers[type]?.(); },
    });
    const makeGroup = (prefix, labels) => {
        const variants = labels.map((label, index) => ({
            dataset: { sizeProduct: `${prefix}${index}`, search: label.toLowerCase() },
            hidden: index > 0,
        }));
        const selectors = labels.map((_, index) => ({ ...events(), value: `${prefix}${index}` }));
        return {
            variants, selectors,
            querySelectorAll(selector) { return selector === '[data-size-product]' ? variants : selectors; },
        };
    };
    const lumber = makeGroup('l', ['2x2 Lumber', '2x4 Lumber']);
    const nails = makeGroup('n', ['Common Nail 1"', 'Common Nail 2"']);
    const plain = makeGroup('p', ['Portland Cement']);
    plain.selectors = [];
    const search = { ...events(), value: '' };
    const document = {
        ...events(), querySelectorAll: () => [lumber, nails, plain],
        getElementById: () => search,
    };
    vm.runInNewContext(script, { document, managedId });
    document.dispatch('DOMContentLoaded');
    return { lumber, nails, plain, search };
}

test('choosing a size switches only its group and synchronizes the size controls', () => {
    const context = setup();
    context.lumber.selectors[0].value = 'l1';

    context.lumber.selectors[0].dispatch('change');

    assert.equal(context.lumber.variants[0].hidden, true);
    assert.equal(context.lumber.variants[1].hidden, false);
    assert.equal(context.lumber.selectors[1].value, 'l1');
    assert.equal(context.nails.variants[0].hidden, false);
    assert.equal(context.nails.variants[1].hidden, true);
    assert.equal(context.plain.variants[0].hidden, false);
});

test('an invalid size selection cannot hide all product sizes', () => {
    const context = setup();
    context.lumber.selectors[0].value = 'missing';

    context.lumber.selectors[0].dispatch('change');

    assert.equal(context.lumber.variants[0].hidden, false);
    assert.equal(context.lumber.variants[1].hidden, true);
});

test('a size-specific cashier search selects the matching size', () => {
    const context = setup();
    context.search.value = '2x4';

    context.search.dispatch('input');

    assert.equal(context.lumber.variants[0].hidden, true);
    assert.equal(context.lumber.variants[1].hidden, false);
    assert.equal(context.lumber.selectors[0].value, 'l1');
});

test('generic product searches keep the currently selected size', () => {
    const context = setup();
    context.lumber.selectors[0].value = 'l1';
    context.lumber.selectors[0].dispatch('change');
    context.search.value = 'lumber';

    context.search.dispatch('input');

    assert.equal(context.lumber.variants[1].hidden, false);
});

test('returning from unit status changes restores that product size without affecting other groups', () => {
    const context = setup('n1');

    assert.equal(context.nails.variants[1].hidden, false);
    assert.equal(context.nails.selectors[0].value, 'n1');
    assert.equal(context.lumber.variants[0].hidden, false);
});
