const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/purchases/index.blade.php'), 'utf8');
const script = view.match(/<script data-purchase-details-script>\s*([\s\S]*?)<\/script>/)[1];

function mountPurchases(overflow = '') {
    const elements = {};
    const events = {};
    const buttons = [];
    const modals = [];
    const document = {
        body: { style: { overflow } },
        getElementById(id) { return elements[id]; },
        addEventListener(name, handler) { events[name] = handler; },
        querySelectorAll(selector) {
            if (selector === '[data-view-purchase]') return buttons;
            if (selector === '[data-purchase-details-modal]') return modals;
            throw new Error(`Unexpected selector: ${selector}`);
        },
    };

    function element(id) {
        const handlers = {};
        const classes = new Set();
        const attributes = { 'aria-hidden': 'true' };
        const node = {
            dataset: {},
            classList: {
                add(value) { classes.add(value); },
                remove(value) { classes.delete(value); },
                contains(value) { return classes.has(value); },
            },
            setAttribute(name, value) { attributes[name] = value; },
            getAttribute(name) { return attributes[name]; },
            addEventListener(name, handler) { handlers[name] = handler; },
            focus() { document.activeElement = node; },
            fire(name, extra = {}) { handlers[name]?.({ target: node, ...extra }); },
        };
        elements[id] = node;
        return node;
    }

    for (const id of [13, 14]) {
        const modal = element(`purchaseDetailsModal${id}`);
        modal.close = element(`close${id}`);
        modal.footerClose = element(`footerClose${id}`);
        modal.querySelector = () => modal.close;
        modal.querySelectorAll = () => [modal.close, modal.footerClose];
        modals.push(modal);
        const button = element(`view${id}`);
        button.dataset.viewPurchase = `purchaseDetailsModal${id}`;
        buttons.push(button);
    }
    const recordModal = element('purchaseModal');
    vm.runInNewContext(script, { document });
    return {
        document, buttons, modals, recordModal,
        shown(modal) { return modal.classList.contains('show'); },
        key(key, shiftKey = false) {
            const event = { key, shiftKey, prevented: false, preventDefault() { this.prevented = true; } };
            events.keydown(event);
            return event;
        },
    };
}

test('View opens only its purchase details and focuses Close without opening Record Purchase', () => {
    const page = mountPurchases();
    page.buttons[1].fire('click');
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.shown(page.modals[1]), true);
    assert.equal(page.shown(page.recordModal), false);
    assert.equal(page.modals[1].getAttribute('aria-hidden'), 'false');
    assert.equal(page.document.body.style.overflow, 'hidden');
    assert.equal(page.document.activeElement, page.modals[1].close);
});

for (const action of ['header close', 'footer close', 'escape', 'backdrop']) {
    test(`${action} dismisses purchase details and restores focus and scrolling`, () => {
        const page = mountPurchases('auto');
        const modal = page.modals[0];
        page.buttons[0].fire('click');
        if (action === 'header close') modal.close.fire('click');
        if (action === 'footer close') modal.footerClose.fire('click');
        if (action === 'escape') page.key('Escape');
        if (action === 'backdrop') modal.fire('click');
        assert.equal(page.shown(modal), false);
        assert.equal(modal.getAttribute('aria-hidden'), 'true');
        assert.equal(page.document.activeElement, page.buttons[0]);
        assert.equal(page.document.body.style.overflow, 'auto');
    });
}

test('clicking contents or pressing another key leaves details open', () => {
    const page = mountPurchases();
    page.buttons[0].fire('click');
    page.modals[0].fire('click', { target: page.modals[0].close });
    page.key('Enter');
    assert.equal(page.shown(page.modals[0]), true);
});

test('Tab and Shift Tab keep keyboard focus inside the details modal', () => {
    const page = mountPurchases();
    const modal = page.modals[0];
    page.buttons[0].fire('click');
    assert.equal(page.key('Tab', true).prevented, true);
    assert.equal(page.document.activeElement, modal.footerClose);
    assert.equal(page.key('Tab').prevented, true);
    assert.equal(page.document.activeElement, modal.close);
});

test('switching purchase details hides the previous modal and restores scrolling on close', () => {
    const page = mountPurchases();
    page.buttons[0].fire('click');
    page.buttons[1].fire('click');
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.shown(page.modals[1]), true);
    page.modals[1].footerClose.fire('click');
    assert.equal(page.document.activeElement, page.buttons[1]);
    assert.equal(page.document.body.style.overflow, '');
});

test('a missing details modal and Escape with no open details do not disturb the page', () => {
    const page = mountPurchases();
    page.buttons[0].dataset.viewPurchase = 'missingModal';
    page.buttons[0].fire('click');
    page.key('Escape');
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.document.body.style.overflow, '');
});
