const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/products/index.blade.php'), 'utf8');
const start = view.indexOf('    function updateConversionFactor()');
const end = view.indexOf('    function refreshUnitDropdown(', start);
assert.ok(start >= 0 && end > start);
const conversionScript = view.slice(start, end);

function convert(selected, base) {
    const factorInput = { value: '' };
    let error = '';
    let priceFactor;
    vm.runInNewContext(`${conversionScript}\nupdateConversionFactor();`, {
        unitOptionSelect: { value: '2' },
        conversionSelectedQty: { value: String(selected) },
        conversionBaseQty: { value: String(base) },
        conversionSelectedUnitName: {}, conversionBaseUnitName: {},
        currentBaseUnitName: 'Kilogram', selectedUnitName: () => 'Piece',
        unitConversionFactor: factorInput,
        setConversionError: (message = '') => { error = message; },
        setAutomaticUnitPrices: (factor = 0) => { priceFactor = factor; },
    });
    return { factor: factorInput.value, error, priceFactor };
}

test('conversion builder preserves screenshot and eight-decimal values', () => {
    assert.equal(convert(1, 0.00605).factor, '0.00605000');
    assert.equal(convert(1, 0.00605001).factor, '0.00605001');
    assert.equal(convert(1, 0.00000001).factor, '0.00000001');
    assert.equal(convert(0.00000001, 1).factor, '100000000.00000000');
});

test('calculated ratios and automatic prices use the same eight-decimal factor', () => {
    assert.deepEqual(convert(3, 1), { factor: '0.33333333', error: '', priceFactor: 0.33333333 });
});

test('invalid and too-small conversion ratios cannot be submitted as zero', () => {
    assert.equal(convert(1, 0).factor, '');
    assert.equal(convert(0, 1).factor, '');
    assert.deepEqual(convert(100000000, 0.00000001), { factor: '', error: 'Conversion must be at least 0.00000001.', priceFactor: 0 });
});
