const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/app.blade.php'), 'utf8');
const start = view.indexOf('        function autoDismissErrorToast()');
const end = view.indexOf("        document.addEventListener('DOMContentLoaded'", start);
assert.ok(start >= 0 && end > start);
const initializer = view.slice(start, end);

test('errors stay visible until the three-second timer removes them', () => {
    let removed = false;
    let scheduled;
    vm.runInNewContext(`${initializer}\nautoDismissErrorToast();`, {
        document: {
            querySelector(selector) {
                assert.equal(selector, '[data-error-toast]');
                return {
                    remove() { removed = true; },
                };
            },
        },
        window: { setTimeout(callback, delay) { scheduled = { callback, delay }; } },
    });

    assert.equal(removed, false);
    assert.equal(scheduled.delay, 3000);
    scheduled.callback();
    assert.equal(removed, true);
});

test('initializing a page without an error popup is safe', () => {
    vm.runInNewContext(`${initializer}\nautoDismissErrorToast();`, {
        document: { querySelector() { return null; } },
        window: { setTimeout() { assert.fail('No timer should be scheduled without an error popup.'); } },
    });
});

test('the error popup is centered visible above edit modals and scrollable', () => {
    const sharedStyle = view.match(/\.success-toast,\s*\.error-toast\s*\{([^}]+)\}/)[1];
    const errorStyle = view.match(/\.error-toast\s*\{([^}]+)\}/g)[1];

    assert.match(sharedStyle, /position:\s*fixed;/);
    assert.match(sharedStyle, /top:\s*50%;/);
    assert.match(sharedStyle, /left:\s*50%;/);
    assert.match(sharedStyle, /transform:\s*translate\(-50%,\s*-50%\);/);
    assert.match(errorStyle, /z-index:\s*120000;/);
    assert.match(errorStyle, /max-height:\s*calc\(100vh - 32px\);/);
    assert.match(errorStyle, /overflow-y:\s*auto;/);
});

test('the error popup timer starts when the page is ready', () => {
    assert.match(view.slice(end), /document\.addEventListener\('DOMContentLoaded', \(\) => \{\s*autoDismissSuccessToast\(\);\s*autoDismissErrorToast\(\);/);
    assert.match(view.slice(end), /autoDismissErrorToast\(\);\s*initializeFieldValidationWarnings\(\);/);
});

function mountValidationPopups() {
    const listeners = {};
    const timers = new Map();
    let now = 0;
    let timerSequence = 0;
    let focusedField = null;

    function makeElement(tagName) {
        return {
            tagName, children: [], attributes: {}, textContent: '', className: '',
            setAttribute(name, value) { this.attributes[name] = value; },
            getAttribute(name) { return this.attributes[name] ?? null; },
            removeAttribute(name) { delete this.attributes[name]; },
            appendChild(child) { child.parent = this; this.children.push(child); },
            insertAdjacentElement(position, child) {
                assert.equal(position, 'afterend');
                child.parent = this.parent;
                this.parent.children.splice(this.parent.children.indexOf(this) + 1, 0, child);
            },
            remove() { this.parent.children = this.parent.children.filter((child) => child !== this); },
            set innerHTML(value) { assert.fail('Warning text must never be inserted as HTML.'); },
        };
    }

    const body = makeElement('body');
    function descendants(element) {
        return element.children.flatMap((child) => [child, ...descendants(child)]);
    }
    const warnings = () => descendants(body).filter((child) => child.className === 'field-validation-warning no-print');
    const popups = () => descendants(body).filter((child) => 'data-error-toast' in child.attributes);
    const document = {
        body, createElement: makeElement,
        querySelector() { return popups()[0] || null; },
        querySelectorAll() { return popups(); },
        addEventListener(name, callback, capture) { listeners[name] = { callback, capture }; },
    };
    const window = {
        setTimeout(callback, delay) {
            const id = ++timerSequence;
            timers.set(id, { callback, at: now + delay });
            return id;
        },
        clearTimeout(id) { timers.delete(id); },
    };
    vm.runInNewContext(initializer + '\ninitializeFieldValidationWarnings();', { document, window });

    function invalidEvent(field) {
        const event = { target: field, prevented: false, preventDefault() { this.prevented = true; } };
        listeners.invalid.callback(event);
        return event;
    }

    return {
        body, listeners, warnings, popups,
        revalidate: invalidEvent,
        focusedField() { return focusedField; },
        messages() { return warnings().map((warning) => warning.textContent); },
        warning(field) {
            const id = field.getAttribute('aria-describedby')?.split(' ').at(-1);
            return warnings().find((warning) => warning.id === id);
        },
        change(field, eventName = 'input') { listeners[eventName].callback({ target: field }); },
        invalid(label, validity = { valueMissing: true }, options = {}) {
            const wrapper = makeElement('div');
            const group = options.group ? makeElement('div') : null;
            body.appendChild(wrapper);
            if (group) wrapper.appendChild(group);
            const field = Object.assign(makeElement('input'), {
                labels: label ? [{ textContent: label }] : [], name: options.name || '',
                validity: { valid: false, ...validity }, willValidate: true,
                validationMessage: options.validationMessage || 'Please fill out this field.',
                title: options.title || '', type: options.type || 'text',
                closest(selector) {
                    if (selector === '.field') {
                        return options.nearbyLabel ? { querySelector() { return { textContent: options.nearbyLabel }; } } : null;
                    }
                    return group;
                },
                matches() { return true; },
                focus() { focusedField = field; },
            });
            (group || wrapper).appendChild(field);
            if (options.ariaLabel) field.setAttribute('aria-label', options.ariaLabel);
            if (options.describedBy) field.setAttribute('aria-describedby', options.describedBy);
            if (options.originalInvalid !== undefined) field.setAttribute('aria-invalid', options.originalInvalid);
            return invalidEvent(field);
        },
        advance(milliseconds) {
            const until = now + milliseconds;
            while ([...timers.values()].some((timer) => timer.at <= until)) {
                const [id, timer] = [...timers].sort((left, right) => left[1].at - right[1].at)[0];
                timers.delete(id);
                now = timer.at;
                timer.callback();
            }
            now = until;
        },
    };
}

test('missing fields suppress native tooltips and show warnings directly below each input for three seconds', () => {
    const page = mountValidationPopups();
    const firstEvent = page.invalid('Delivery Fee');
    const secondEvent = page.invalid('Customer Name');
    page.advance(0);

    assert.equal(page.listeners.invalid.capture, true);
    assert.equal(firstEvent.prevented, true);
    assert.equal(secondEvent.prevented, true);
    assert.equal(page.popups().length, 0);
    assert.deepEqual(page.messages(), ['Delivery Fee is required.', 'Customer Name is required.']);
    for (const field of [firstEvent.target, secondEvent.target]) {
        const warning = page.warning(field);
        assert.equal(field.parent.children[field.parent.children.indexOf(field) + 1], warning);
        assert.equal(warning.attributes.role, 'alert');
        assert.equal(field.getAttribute('aria-invalid'), 'true');
        assert.equal(warning.children.length, 0);
    }
    assert.equal(page.focusedField(), firstEvent.target);
    page.advance(2999);
    assert.equal(page.warnings().length, 2);
    page.advance(1);
    assert.equal(page.warnings().length, 0);
    assert.equal(firstEvent.target.getAttribute('aria-invalid'), null);
});

test('repeated invalid checks update the same warning and restart only its timer', () => {
    const page = mountValidationPopups();
    const field = page.invalid('Unit Name').target;
    page.advance(1000);
    page.revalidate(field);
    assert.equal(page.warnings().length, 1);
    assert.deepEqual(page.messages(), ['Unit Name is required.']);
    page.advance(2000);
    assert.equal(page.warnings().length, 1);
    page.advance(1000);
    assert.equal(page.warnings().length, 0);
});

test('phone and allowed email patterns show their existing helpful instructions below their fields', () => {
    const page = mountValidationPopups();
    page.invalid('Phone', { patternMismatch: true }, {
        title: 'Contact number must start with 09 and be exactly 11 digits.',
    });
    page.invalid('Email', { patternMismatch: true }, {
        title: 'Use an email ending with @gmail.com, @yahoo.com, or @outlook.com.',
    });

    assert.deepEqual(page.messages(), [
        'Contact number must start with 09 and be exactly 11 digits.',
        'Use an email ending with @gmail.com, @yahoo.com, or @outlook.com.',
    ]);
});

test('email format custom errors and numeric errors retain the appropriate message', () => {
    const page = mountValidationPopups();
    page.invalid('Email', { typeMismatch: true }, { type: 'email' });
    page.invalid('Quantity', { customError: true }, { validationMessage: 'Quantity exceeds available stock.' });
    page.invalid('Conversion', { stepMismatch: true }, { validationMessage: 'Please enter a multiple of 0.00000001.' });

    assert.deepEqual(page.messages(), [
        'Email must be a valid email address.', 'Quantity exceeds available stock.',
        'Please enter a multiple of 0.00000001.',
    ]);
});

test('fields without associated labels use accessible names or a readable field name', () => {
    const page = mountValidationPopups();
    page.invalid('', { valueMissing: true }, { ariaLabel: 'Purchase Quantity' });
    page.invalid('', { valueMissing: true }, { name: 'unit_symbol' });
    page.invalid('');

    assert.deepEqual(page.messages(), ['Purchase Quantity is required.', 'Unit Symbol is required.', 'This field is required.']);
});

test('fields use their displayed labels even without a label for attribute', () => {
    const page = mountValidationPopups();
    page.invalid('', { valueMissing: true }, { name: 'delivery_fee', nearbyLabel: ' Delivery Fee ' });
    page.invalid('', { valueMissing: true }, { name: 'reorder_level', nearbyLabel: ' Reorder Level ' });

    assert.deepEqual(page.messages(), ['Delivery Fee is required.', 'Reorder Level is required.']);
});

test('warning messages are inserted as text rather than executable markup', () => {
    const page = mountValidationPopups();
    const maliciousMessage = '<img src=x onerror=alert(1)>';
    page.invalid('Name', { customError: true }, { validationMessage: maliciousMessage });

    assert.deepEqual(page.messages(), [maliciousMessage]);
    assert.equal(page.warnings()[0].children.length, 0);
});

test('valid pages with no invalid event show no warning or timer', () => {
    const page = mountValidationPopups();
    page.advance(5000);
    assert.equal(page.warnings().length, 0);
    assert.equal(page.focusedField(), null);
});

test('invalid events from non-input elements are not intercepted', () => {
    const page = mountValidationPopups();
    page.listeners.invalid.callback({
        target: { matches() { return false; } },
        preventDefault() { assert.fail('Unrelated events must keep their default behavior.'); },
    });
    page.advance(0);
    assert.equal(page.warnings().length, 0);
});

test('correcting an input removes its warning but leaves other invalid fields unchanged', () => {
    const page = mountValidationPopups();
    const fee = page.invalid('Delivery Fee').target;
    page.invalid('Customer Name');
    fee.validity.valid = true;
    page.change(fee);

    assert.deepEqual(page.messages(), ['Customer Name is required.']);
    assert.equal(fee.getAttribute('aria-invalid'), null);
    assert.equal(fee.getAttribute('aria-describedby'), null);
});

test('changing a select to a valid option removes its warning immediately', () => {
    const page = mountValidationPopups();
    const field = page.invalid('Category').target;
    field.validity.valid = true;
    page.change(field, 'change');

    assert.equal(page.warnings().length, 0);
});

test('existing accessibility descriptions and invalid state are preserved after warning removal', () => {
    const page = mountValidationPopups();
    const field = page.invalid('Phone', { valueMissing: true }, { describedBy: 'phone-help', originalInvalid: 'false' }).target;
    assert.match(field.getAttribute('aria-describedby'), /^phone-help field-validation-warning-\d+$/);
    page.advance(3000);

    assert.equal(field.getAttribute('aria-describedby'), 'phone-help');
    assert.equal(field.getAttribute('aria-invalid'), 'false');
});

test('quantity and conversion warnings appear below the grouped controls rather than inside their row', () => {
    const page = mountValidationPopups();
    for (const label of ['Quantity', 'Conversion']) {
        const field = page.invalid(label, { valueMissing: true }, { group: true }).target;
        const group = field.parent;
        assert.equal(group.parent.children[1], page.warning(field));
        assert.equal(group.children.length, 1);
    }
});

test('resetting a form removes its warnings and cancels their timers', () => {
    const page = mountValidationPopups();
    const field = page.invalid('Customer Name').target;
    page.listeners.reset.callback({ target: { querySelectorAll() { return [field]; } } });
    page.advance(5000);

    assert.equal(page.warnings().length, 0);
    assert.equal(field.getAttribute('aria-describedby'), null);
});

test('typing another invalid value keeps the existing warning and saving remains blocked', () => {
    const page = mountValidationPopups();
    const field = page.invalid('Phone', { patternMismatch: true }).target;
    page.change(field);
    assert.equal(page.warnings().length, 1);
    assert.equal(field.validity.valid, false);
});

test('warnings remain in normal document flow and fit narrow inputs', () => {
    const styles = view.match(/\.field-validation-warning\s*\{([^}]+)\}/)[1];
    assert.match(styles, /display:\s*block;/);
    assert.match(styles, /width:\s*100%;/);
    assert.match(styles, /margin-top:\s*8px;/);
    assert.doesNotMatch(styles, /position:\s*fixed;/);
});
