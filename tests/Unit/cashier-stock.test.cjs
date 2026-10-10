const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname,
    '../../resources/views/sales/create.blade.php'), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

function element() {
    const listeners = new Map();
    const classes = new Set();
    return {
        dataset: {}, style: {}, value: '', checked: false, disabled: false, textContent: '', innerHTML: '',
        classList: {
            add(name) { classes.add(name); },
            remove(name) { classes.delete(name); },
            toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); },
            contains(name) { return classes.has(name); },
        },
        addEventListener(type, listener) {
            const handlers = listeners.get(type) || [];
            handlers.push(listener);
            listeners.set(type, handlers);
        },
        dispatch(type, target = this) {
            for (const handler of listeners.get(type) || []) {
                handler({ target, preventDefault() {} });
            }
        },
        async dispatchAsync(type, target = this) {
            for (const handler of listeners.get(type) || []) {
                await handler({ target, preventDefault() {} });
            }
        },
        click() { if (!this.disabled) { this.dispatch('click'); } },
        setAttribute() {}, focus() {}, reportValidity() { return true; },
    };
}

function setup(stock = 4080, { approvalStatus = 200 } = {}) {
    const ids = new Map();
    for (const match of script.matchAll(/byId\(\s*'([^']+)'\s*\)/g)) {
        ids.set(match[1], element());
    }
    ids.get('paymentMethod').value = 'PAY_NOW';
    const requests = [];
    if (ids.has('alterationForm')) {
        ids.get('alterationForm').dataset.approvalUrl = '/sales/alterations';
        ids.get('alterationForm').reset = () => {
            for (const [id, field] of ids) {
                if (id.startsWith('alteration')) { field.value = ''; }
            }
        };
        ids.get('alterationForm').querySelector = () => ({ value: 'csrf-token' });
    }
    ids.get('order').querySelector = () => element();
    const makeProduct = (productId, baseId, baseStock, name, hasBag) => {
        const row = element();
        row.dataset = { productId: String(productId), baseStock: String(baseStock), search: name.toLowerCase(), category: 'materials' };
        const available = element();
        const badge = element();
        const makeButton = (id, factor, disabled = false) => {
            const button = element();
            button.disabled = disabled;
            button.dataset.unit = JSON.stringify({
                id, base_id: baseId, product_id: productId, base_stock: baseStock,
                factor, name, unit: factor === 40 ? 'Bag' : 'Kilogram', price: 8 * factor,
                base_unit: 'Kilogram', base_unit_id: 100, base_price: 8,
            });
            return button;
        };
        const buttons = [makeButton(baseId, 1)];
        if (hasBag) {
            buttons.push(makeButton(baseId + 1, 40));
        }
        const locked = makeButton(baseId + 100, 0, true);
        buttons.push(locked);
        row.querySelector = (selector) => selector === '[data-available-stock]' ? available : badge;
        row.querySelectorAll = () => buttons;
        return { row, available, badge, buttons, locked };
    };
    const cement = makeProduct(10, 1, stock, 'Portland Cement', true);
    const other = makeProduct(20, 3, 10, 'Other Product', false);
    const rows = [cement.row, other.row];
    const buttons = [...cement.buttons, ...other.buttons];
    const document = element();
    document.getElementById = (id) => ids.get(id);
    document.querySelectorAll = (selector) => selector === '.cashier-unit-button' ? buttons : rows;
    document.createElement = () => {
        const div = element();
        Object.defineProperty(div, 'innerHTML', { get: () => div.textContent });
        return div;
    };

    vm.runInNewContext(script, {
        document,
        async fetch(url, options) {
            requests.push({ url, options, payload: JSON.parse(options.body) });
            return {
                ok: approvalStatus === 200,
                async json() {
                    return approvalStatus === 200 ? {
                        token: 'a'.repeat(64),
                        alteration: { quantity: 5, base_quantity: 0.02, unit_price: 2, subtotal: 10, unit: 'Piece', selling_unit_id: 200 },
                    } : { errors: { password: ['The admin username or password is incorrect.'] } };
                },
            };
        },
    });
    document.dispatch('DOMContentLoaded');

    return {
        cement, other, ids, requests,
        quantity(id, value) {
            const input = { value: String(value), dataset: { orderQuantity: String(id) } };
            input.closest = (selector) => selector === '[data-order-quantity]' ? input : null;
            ids.get('order').dispatch('change', input);
        },
        action(attribute, id) {
            const button = { dataset: { [attribute]: String(id) } };
            const selector = attribute.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);
            button.closest = (requested) => requested === `[data-${selector}]` ? button : null;
            ids.get('order').dispatch('click', button);
        },
    };
}

test('adding 360 kilograms previews 3720 available without changing the original stock', () => {
    const context = setup();
    context.cement.buttons[0].click();

    context.quantity(1, 360);

    assert.equal(context.cement.available.textContent, '3720');
    assert.equal(context.cement.row.dataset.baseStock, '4080');
    assert.equal(JSON.parse(context.cement.buttons[0].dataset.unit).base_stock, 4080);
});

test('switching from a bag to half a kilogram replaces the stock preview', () => {
    const context = setup();
    context.cement.buttons[1].click();
    assert.equal(context.cement.available.textContent, '4040');

    context.cement.buttons[0].click();
    context.quantity(1, 0.5);

    assert.equal(context.cement.available.textContent, '4079.5');
    assert.equal((context.ids.get('order').innerHTML.match(/class="cashier-order-line"/g) || []).length, 1);
});

test('plus and minus update the stock preview in both directions', () => {
    const context = setup();
    context.cement.buttons[1].click();

    context.action('orderIncrease', 1);
    assert.equal(context.cement.available.textContent, '4000');
    context.action('orderDecrease', 1);

    assert.equal(context.cement.available.textContent, '4040');
});

test('removing a product restores its full available stock', () => {
    const context = setup();
    context.cement.buttons[1].click();

    context.action('remove', 1);

    assert.equal(context.cement.available.textContent, '4080');
    assert.match(context.ids.get('order').innerHTML, /No items added/);
});

test('using all stock disables adding more but still allows switching selling units', () => {
    const context = setup(40);
    context.cement.buttons[1].click();
    assert.equal(context.cement.available.textContent, '0');
    assert.equal(context.cement.badge.textContent, 'Out of Stock');
    assert.equal(context.cement.badge.classList.contains('out-stock'), true);
    assert.equal(context.cement.buttons[0].disabled, false);
    assert.equal(context.cement.buttons[1].disabled, true);

    context.action('remove', 1);

    assert.equal(context.cement.available.textContent, '40');
    assert.equal(context.cement.badge.textContent, 'In Stock');
    assert.equal(context.cement.badge.classList.contains('in-stock'), true);
    assert.equal(context.cement.buttons[0].disabled, false);
    assert.equal(context.cement.buttons[1].disabled, false);
    assert.equal(context.cement.locked.disabled, true);
});

test('changing one product never reduces another products available stock', () => {
    const context = setup();
    context.cement.buttons[1].click();
    assert.equal(context.other.available.textContent, '10');

    context.other.buttons[0].click();
    context.quantity(3, 5);

    assert.equal(context.cement.available.textContent, '4040');
    assert.equal(context.other.available.textContent, '5');
});

test('an excessive quantity is rejected without changing the preview', () => {
    const context = setup(40);
    context.cement.buttons[0].click();

    context.quantity(1, 41);

    assert.equal(context.cement.available.textContent, '39');
    assert.match(context.ids.get('cashierNoticeMessage').textContent, /Only 40 Kilogram available/);
});

test('six decimal place stock previews do not accumulate floating point errors', () => {
    const context = setup(1.000001);
    context.cement.buttons[0].click();

    context.quantity(1, 0.000001);

    assert.equal(context.cement.available.textContent, '1');
});

function enterPieceAlteration(context) {
    context.action('orderAlter', 1);
    context.ids.get('alterationUnit').value = '200';
    context.ids.get('alterationUnit').dispatch('change');
    context.ids.get('alterationQuantity').value = '5';
    context.ids.get('alterationStock').value = '0.020';
    context.ids.get('alterationPrice').value = '2.00';
    context.ids.get('alterationReason').value = 'Customer wants five pieces.';
    context.ids.get('alterationAdmin').value = 'owner';
    context.ids.get('alterationPassword').value = 'owner-secret';
}

test('admin-approved pieces show the selling quantity and price but preview only their measured weight', { skip: 'Alter workflow retired.' }, async () => {
    const context = setup();
    context.cement.buttons[0].click();
    enterPieceAlteration(context);

    await context.ids.get('alterationForm').dispatchAsync('submit');

    assert.equal(context.cement.available.textContent, '4079.98');
    assert.match(context.ids.get('order').innerHTML, /Piece/);
    assert.match(context.ids.get('order').innerHTML, /value="5"\s+disabled/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[quantity\]"\s+value="0.02"/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[alteration_token\]"/);
    assert.equal(context.ids.get('total').textContent, '₱10.00');
    assert.equal(context.ids.get('alterationPassword').value, '');
    assert.equal(context.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.equal(context.requests[0].payload.base_quantity, '0.020');
});

test('incorrect admin password leaves the order and stock preview unchanged and clears the password', { skip: 'Alter workflow retired.' }, async () => {
    const context = setup(4080, { approvalStatus: 422 });
    context.cement.buttons[0].click();
    enterPieceAlteration(context);

    await context.ids.get('alterationForm').dispatchAsync('submit');

    assert.equal(context.cement.available.textContent, '4079');
    assert.equal(context.ids.get('total').textContent, '₱8.00');
    assert.equal(context.ids.get('alterationPassword').value, '');
    assert.equal(context.ids.get('approveAlteration').disabled, false);
    assert.match(context.ids.get('alterationError').textContent, /incorrect/);
    assert.equal(context.ids.get('alterationModal').classList.contains('open'), true);
});

test('an approved alteration cannot be increased by normal cart controls or unit buttons', { skip: 'Alter workflow retired.' }, async () => {
    const context = setup();
    context.cement.buttons[0].click();
    enterPieceAlteration(context);
    await context.ids.get('alterationForm').dispatchAsync('submit');

    context.action('orderIncrease', 1);
    context.cement.buttons[1].click();

    assert.equal(context.cement.available.textContent, '4079.98');
    assert.equal(context.ids.get('total').textContent, '₱10.00');
    assert.match(context.ids.get('cashierNoticeMessage').textContent, /ask the admin/);
});

test('removing an altered line restores its measured stock deduction', { skip: 'Alter workflow retired.' }, async () => {
    const context = setup();
    context.cement.buttons[0].click();
    enterPieceAlteration(context);
    await context.ids.get('alterationForm').dispatchAsync('submit');

    context.action('remove', 1);

    assert.equal(context.cement.available.textContent, '4080');
    assert.equal(context.ids.get('total').textContent, '₱0.00');
});

test('base-unit alterations deduct their selling quantity but another unit requires a measured deduction', { skip: 'Alter workflow retired.' }, () => {
    const context = setup();
    context.cement.buttons[0].click();
    context.action('orderAlter', 1);
    assert.equal(context.ids.get('alterationStock').disabled, true);

    context.ids.get('alterationQuantity').value = '2';
    context.ids.get('alterationQuantity').dispatch('input');
    assert.equal(context.ids.get('alterationStock').value, '2');
    context.ids.get('alterationUnit').value = '200';
    context.ids.get('alterationUnit').dispatch('change');

    assert.equal(context.ids.get('alterationStock').disabled, false);
    assert.equal(context.ids.get('alterationStock').value, '');
});

test('checkout offers normal quantity controls without an Alter action or approval requests', () => {
    const context = setup();
    context.cement.buttons[0].click();

    assert.doesNotMatch(context.ids.get('order').innerHTML, /data-order-alter|alteration_token|Admin approved/);
    assert.doesNotMatch(view, /id="alterationModal"|id="alterationForm"/);
    context.action('orderAlter', 1);
    assert.equal(context.requests.length, 0);
    context.action('orderIncrease', 1);
    assert.equal(context.cement.available.textContent, '4078');
    assert.equal(context.ids.get('total').textContent, '₱16.00');
});

test('one bag displays one bag and submits its selling unit while previewing kilograms', () => {
    const context = setup();
    context.cement.buttons[1].click();

    assert.match(context.ids.get('order').innerHTML, /Bag\s+•\s+₱320\.00 each/);
    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="1"/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[product_unit_id\]"\s+value="2"/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[quantity\]"\s+value="1"/);
    assert.equal(context.cement.available.textContent, '4040');
    assert.equal(context.ids.get('total').textContent, '₱320.00');
});

test('bag quantity editing and repeated bag clicks stay in bags with correct kilogram stock', () => {
    const context = setup();
    context.cement.buttons[1].click();
    context.quantity(1, 1.5);
    assert.equal(context.cement.available.textContent, '4020');
    assert.equal(context.ids.get('total').textContent, '₱480.00');
    context.cement.buttons[1].click();
    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="2\.5"/);
    assert.equal(context.cement.available.textContent, '3980');
    assert.equal(context.ids.get('total').textContent, '₱800.00');
});

test('switching from bag to kilogram resets to one kilogram without a duplicate row', () => {
    const context = setup();
    context.cement.buttons[1].click();
    context.cement.buttons[0].click();

    assert.match(context.ids.get('order').innerHTML, /Kilogram\s+•\s+₱8\.00 each/);
    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="1"/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[product_unit_id\]"\s+value="1"/);
    assert.equal(context.cement.available.textContent, '4079');
    assert.equal(context.ids.get('total').textContent, '₱8.00');
    context.cement.buttons[1].click();
    assert.match(context.ids.get('order').innerHTML, /Bag\s+•\s+₱320\.00 each/);
    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="1"/);
    assert.equal(context.ids.get('total').textContent, '₱320.00');
    assert.equal(context.cement.available.textContent, '4040');
    assert.equal((context.ids.get('order').innerHTML.match(/class="cashier-order-line"/g) || []).length, 1);
});

test('switching from a fractional kilogram to bag resets to one bag', () => {
    const context = setup();
    context.cement.buttons[0].click();
    context.quantity(1, 0.5);
    context.cement.buttons[1].click();

    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="1"/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[product_unit_id\]"\s+value="2"/);
    assert.equal(context.cement.available.textContent, '4040');
    assert.equal(context.ids.get('total').textContent, '₱320.00');
});

test('bag quantity above stock is rejected without changing the unit or total', () => {
    const context = setup(80);
    context.cement.buttons[1].click();
    context.quantity(1, 3);

    assert.equal(context.cement.available.textContent, '40');
    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="1"/);
    assert.equal(context.ids.get('total').textContent, '₱320.00');
    assert.match(context.ids.get('cashierNoticeMessage').textContent, /Only 80 Kilogram available/);
});

test('switching units resets an edited quantity and repeated clicks then add in the new unit', () => {
    const context = setup();
    context.cement.buttons[1].click();
    context.quantity(1, 2.5);

    context.cement.buttons[0].click();
    context.cement.buttons[0].click();
    context.action('orderIncrease', 1);

    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="3"/);
    assert.match(context.ids.get('order').innerHTML, /name="items\[0\]\[quantity\]"\s+value="3"/);
    assert.equal(context.cement.available.textContent, '4077');
    assert.equal(context.ids.get('total').textContent, '₱24.00');
});

test('switching away from a bag using all stock restores all but one kilogram', () => {
    const context = setup(40);
    context.cement.buttons[1].click();

    context.cement.buttons[0].click();

    assert.equal(context.cement.available.textContent, '39');
    assert.equal(context.cement.badge.textContent, 'In Stock');
    assert.equal(context.ids.get('total').textContent, '₱8.00');
    assert.match(context.ids.get('order').innerHTML, /Kilogram\s+•\s+₱8\.00 each/);
});

test('an unavailable replacement unit leaves the existing quantity and stock unchanged', () => {
    const context = setup(20);
    context.cement.buttons[0].click();
    context.quantity(1, 5);

    context.cement.buttons[1].click();

    assert.equal(context.cement.available.textContent, '15');
    assert.equal(context.ids.get('total').textContent, '₱40.00');
    assert.match(context.ids.get('order').innerHTML, /data-order-quantity="1"\s+value="5"/);
    assert.match(context.ids.get('cashierNoticeMessage').textContent, /Only 20 Kilogram available/);
});
