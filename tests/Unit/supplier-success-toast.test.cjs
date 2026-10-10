const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/layouts/app.blade.php'), 'utf8');
const start = view.indexOf('        function autoDismissSuccessToast()');
const end = view.indexOf("        document.addEventListener('DOMContentLoaded'", start);
assert.ok(start >= 0 && end > start, 'The toast initializer must exist in the layout.');
const initializer = view.slice(start, end);

test('shared success toast stays visible until the one-second timer removes it', () => {
    let removed = false;
    let scheduled;
    vm.runInNewContext(`${initializer}\nautoDismissSuccessToast();`, {
        document: {
            querySelector(selector) {
                assert.equal(selector, '[data-success-toast]');
                return { remove() { removed = true; } };
            },
        },
        window: { setTimeout(callback, delay) { scheduled = { callback, delay }; } },
    });

    assert.equal(removed, false);
    assert.equal(scheduled.delay, 1000);
    scheduled.callback();
    assert.equal(removed, true);
});

test('pages without a success toast do not schedule removals or hide errors', () => {
    vm.runInNewContext(`${initializer}\nautoDismissSuccessToast();`, {
        document: {
            querySelector(selector) {
                assert.equal(selector, '[data-success-toast]');
                return null;
            },
        },
        window: { setTimeout() { assert.fail('No timer should be scheduled without a toast.'); } },
    });
});

test('the toast timer starts when the page is ready', () => {
    assert.match(view.slice(end), /document\.addEventListener\('DOMContentLoaded', \(\) => \{\s*autoDismissSuccessToast\(\);/);
});

test('the shared success popup remains centered and fits narrow screens', () => {
    const style = view.match(/\.success-toast,\s*\.error-toast\s*\{([^}]+)\}/)[1];

    assert.match(style, /position:\s*fixed;/);
    assert.match(style, /top:\s*50%;/);
    assert.match(style, /left:\s*50%;/);
    assert.match(style, /transform:\s*translate\(-50%,\s*-50%\);/);
    assert.match(style, /max-width:\s*min\(360px,\s*calc\(100vw - 32px\)\);/);
});
