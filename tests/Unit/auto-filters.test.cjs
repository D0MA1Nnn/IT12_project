const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname,
    '../../resources/views/layouts/auto-filters.blade.php'), 'utf8')
    .replace(/^<script[^>]*>/, '').replace(/<\/script>\s*$/, '');

class EventTarget {
    listeners = new Map();

    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) || [];
        listeners.push(listener);
        this.listeners.set(type, listeners);
    }

    dispatch(type, properties = {}) {
        const event = { ...properties, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        for (const listener of this.listeners.get(type) || []) {
            listener(event);
        }
        return event;
    }
}

function setup({ method = 'get', savedFocus = null, storageBlocked = false } = {}) {
    const document = new EventTarget();
    const window = new EventTarget();
    window.location = { pathname: '/products' };
    const storage = new Map(savedFocus ? [['auto-filter-focus:/products', JSON.stringify(savedFocus)]] : []);
    const timers = new Map();
    let nextTimer = 0;
    const form = new EventTarget();
    form.method = method;
    form.valid = true;
    form.submissions = 0;
    form.checkValidity = () => form.valid;
    form.requestSubmit = () => {
        if (!form.dispatch('submit').defaultPrevented) {
            form.submissions++;
        }
    };
    const makeControl = (type, name, value = '') => {
        const control = Object.assign(new EventTarget(), {
            type, name, value, tagName: type === 'select-one' ? 'SELECT' : 'INPUT',
            selectionStart: 2, selectionEnd: 2, disabled: false,
        });
        control.focus = () => { document.activeElement = control; };
        control.setSelectionRange = (start, end) => {
            control.selectionStart = start;
            control.selectionEnd = end;
        };
        return control;
    };
    const search = makeControl('text', 'search', 'Cement');
    const dropdown = makeControl('select-one', 'category', '1');
    const date = makeControl('date', 'from', '2026-10-01');
    const page = makeControl('hidden', 'page', '4');
    const controls = [search, dropdown, date, page];
    form.querySelectorAll = () => controls;
    document.querySelectorAll = () => [form];
    const sessionStorage = {
        getItem(key) {
            if (storageBlocked) { throw new Error('Storage unavailable'); }
            return storage.get(key) || null;
        },
        setItem(key, value) {
            if (storageBlocked) { throw new Error('Storage unavailable'); }
            storage.set(key, value);
        },
        removeItem(key) { storage.delete(key); },
    };

    vm.runInNewContext(source, {
        document, window, sessionStorage,
        setTimeout(callback, delay) {
            assert.equal(delay, 500);
            timers.set(++nextTimer, callback);
            return nextTimer;
        },
        clearTimeout(id) { timers.delete(id); },
    });
    document.dispatch('DOMContentLoaded');

    return {
        form, search, dropdown, date, page, document, window, storage, timers,
        flush() {
            const callbacks = Array.from(timers.values());
            timers.clear();
            callbacks.forEach((callback) => callback());
        },
    };
}

test('typing waits for a pause and submits only the latest search', () => {
    const context = setup();
    context.search.focus();
    context.search.dispatch('input');
    context.search.value = 'Cement bag';
    context.search.dispatch('input');
    assert.equal(context.form.submissions, 0);
    assert.equal(context.timers.size, 1);

    context.flush();

    assert.equal(context.form.submissions, 1);
    assert.equal(context.page.disabled, true);
    assert.equal(JSON.parse(context.storage.get('auto-filter-focus:/products')).value, 'Cement bag');
});

for (const controlName of ['dropdown', 'date']) {
    test(`${controlName} applies immediately and cancels any pending search`, () => {
        const context = setup();
        context.search.dispatch('input');

        context[controlName].dispatch('change');
        context.flush();

        assert.equal(context.form.submissions, 1);
        assert.equal(context.page.disabled, true);
    });
}

test('native Enter submission cancels the timer and prevents duplicate requests', () => {
    const context = setup();
    context.search.dispatch('input');

    context.form.requestSubmit();
    context.form.requestSubmit();
    context.flush();

    assert.equal(context.form.submissions, 1);
});

test('Enter in a search field applies immediately even without a submit button', () => {
    const context = setup();
    context.search.dispatch('input');

    const event = context.search.dispatch('keydown', { key: 'Enter' });
    context.flush();

    assert.equal(event.defaultPrevented, true);
    assert.equal(context.form.submissions, 1);
});

test('Enter used to confirm an input composition does not submit filters', () => {
    const context = setup();

    context.search.dispatch('keydown', { key: 'Enter', isComposing: true });

    assert.equal(context.form.submissions, 0);
});

test('input composition does not submit unfinished text', () => {
    const context = setup();
    context.search.dispatch('compositionstart');
    context.search.dispatch('input');
    context.flush();
    assert.equal(context.form.submissions, 0);

    context.search.dispatch('compositionend');
    context.flush();

    assert.equal(context.form.submissions, 1);
});

test('invalid filters wait until the user supplies a valid value', () => {
    const context = setup();
    context.form.valid = false;
    context.date.dispatch('change');
    assert.equal(context.form.submissions, 0);

    context.form.valid = true;
    context.date.dispatch('change');

    assert.equal(context.form.submissions, 1);
});

test('POST actions never receive automatic filtering listeners', () => {
    const context = setup({ method: 'post' });

    context.search.dispatch('input');
    context.dropdown.dispatch('change');
    context.flush();

    assert.equal(context.form.submissions, 0);
    assert.equal(context.search.listeners.size, 0);
});

test('matching search focus and cursor are restored after a filtered navigation', () => {
    const context = setup({ savedFocus: {
        formIndex: 0, name: 'search', value: 'Cement', start: 3, end: 5,
    } });

    assert.equal(context.document.activeElement, context.search);
    assert.equal(context.search.selectionStart, 3);
    assert.equal(context.search.selectionEnd, 5);
    assert.equal(context.storage.size, 0);
});

test('stale focus does not move the cursor when search values differ', () => {
    const context = setup({ savedFocus: {
        formIndex: 0, name: 'search', value: 'Lumber', start: 3, end: 5,
    } });

    assert.equal(context.document.activeElement, undefined);
});

test('filtering still submits when browser storage is unavailable', () => {
    const context = setup({ storageBlocked: true });
    context.search.focus();

    context.search.dispatch('input');
    context.flush();

    assert.equal(context.form.submissions, 1);
});

test('filters can submit again after navigating back', () => {
    const context = setup();
    context.dropdown.dispatch('change');

    context.window.dispatch('pageshow');
    context.dropdown.dispatch('change');

    assert.equal(context.form.submissions, 2);
});
