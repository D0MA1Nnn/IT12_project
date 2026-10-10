const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/sales/index.blade.php'), 'utf8');
const script = view.match(/<script>\s*([\s\S]*?)<\/script>/)[1];

function mountDeliveries() {
    const elements = {};
    const handlers = {};
    const modals = [];
    const triggers = [];
    const closeButtons = [];
    const cancelForms = [];
    const confirmationButtons = [];
    const deliveryForms = [];
    const document = {
        getElementById(id) { return elements[id]; },
        addEventListener(name, handler) { handlers[name] = handler; },
        querySelector(selector) { return document.querySelectorAll(selector)[0] ?? null; },
        querySelectorAll(selector) {
            if (selector === '.delivery-modal') return modals;
            if (selector === '.delivery-modal.open') return modals.filter(modal => modal.classList.contains('open'));
            if (selector === '[data-open-modal]') return triggers;
            if (selector === '[data-close-modal]') return closeButtons;
            if (selector === '[data-cancel-confirm-modal]') return cancelForms;
            if (selector === '[data-confirm-cancellation]') return confirmationButtons;
            if (selector === '[data-delivery-confirm-form]') return deliveryForms;
            throw new Error(`Unexpected document selector: ${selector}`);
        },
    };

    function element(id) {
        const events = {};
        const classes = new Set();
        const attributes = {};
        const node = {
            dataset: {}, disabled: false, textContent: '', value: '', valid: true,
            submits: 0, reports: 0,
            classList: {
                add(value) { classes.add(value); },
                remove(value) { classes.delete(value); },
                contains(value) { return classes.has(value); },
            },
            setAttribute(name, value) { attributes[name] = value; },
            getAttribute(name) { return attributes[name]; },
            addEventListener(name, handler) { events[name] = handler; },
            focus() { document.activeElement = node; },
            closest() { return node.modal; },
            checkValidity() { return node.valid; },
            reportValidity() { node.reports += 1; return node.valid; },
            requestSubmit() {
                if (node.valid && !node.fire('submit').prevented) node.submits += 1;
            },
            fire(name, extra = {}) {
                const event = { target: node, prevented: false, preventDefault() { this.prevented = true; }, ...extra };
                events[name]?.(event);
                return event;
            },
        };
        elements[id] = node;
        return node;
    }

    function modal(id) {
        const node = element(id);
        node.controls = [];
        node.setAttribute('aria-hidden', 'true');
        node.querySelectorAll = () => node.controls.filter(control => !control.disabled);
        node.querySelector = selector => {
            if (selector === '[data-initial-focus]') return node.initialFocus;
            if (selector === '[data-close-modal]') return node.close;
            if (selector === '[data-confirm-cancellation]') return node.confirm;
            if (selector === 'form[data-submitting="true"]') return node.form?.dataset.submitting === 'true' ? node.form : null;
            throw new Error(`Unexpected modal selector: ${selector}`);
        };
        modals.push(node);
        return node;
    }

    function closeButton(id, parent) {
        const button = element(id);
        button.modal = parent;
        closeButtons.push(button);
        return button;
    }

    function cancellation(id) {
        const reasonModal = modal(`cancelDeliveryModal${id}`);
        const confirmation = modal(`confirmCancellationModal${id}`);
        confirmation.dataset.returnModal = `cancelDeliveryModal${id}`;
        const trigger = element(`cancelTrigger${id}`);
        trigger.dataset.openModal = `cancelDeliveryModal${id}`;
        triggers.push(trigger);
        const form = element(`cancelDeliveryForm${id}`);
        form.dataset.cancelConfirmModal = `confirmCancellationModal${id}`;
        form.modal = reasonModal;
        cancelForms.push(form);
        const reason = element(`reason${id}`);
        reason.value = `Customer cancelled order ${id}`;
        const submit = element(`submit${id}`);
        form.querySelector = () => submit;
        reasonModal.form = form;
        reasonModal.close = closeButton(`closeReason${id}`, reasonModal);
        const back = closeButton(`backReason${id}`, reasonModal);
        reasonModal.initialFocus = reason;
        reasonModal.controls = [reasonModal.close, reason, back, submit];
        confirmation.close = closeButton(`closeConfirmation${id}`, confirmation);
        const goBack = closeButton(`goBack${id}`, confirmation);
        confirmation.initialFocus = goBack;
        const confirm = element(`confirm${id}`);
        confirm.dataset.confirmCancellation = `cancelDeliveryForm${id}`;
        confirmationButtons.push(confirm);
        confirmation.confirm = confirm;
        confirmation.controls = [confirmation.close, goBack, confirm];
        return { reasonModal, confirmation, trigger, form, reason, submit, goBack, confirm, back };
    }

    const orders = [cancellation(43), cancellation(44)];
    const deliveryModal = modal('confirmDeliveryModal45');
    const deliveryTrigger = element('deliveryTrigger');
    deliveryTrigger.dataset.openModal = 'confirmDeliveryModal45';
    triggers.push(deliveryTrigger);
    const deliveryForm = element('deliveryForm');
    const deliverySubmit = element('deliverySubmit');
    deliveryForm.querySelector = () => deliverySubmit;
    deliveryModal.form = deliveryForm;
    deliveryModal.close = closeButton('closeDelivery', deliveryModal);
    deliveryModal.initialFocus = deliveryModal.close;
    deliveryModal.controls = [deliveryModal.close, deliverySubmit];
    deliveryForms.push(deliveryForm);

    vm.runInNewContext(script, { document, confirm() { throw new Error('Browser confirm must not be used'); } });
    handlers.DOMContentLoaded();
    return {
        orders, document, deliveryModal, deliveryTrigger, deliveryForm, deliverySubmit,
        key(key, shiftKey = false) {
            const event = { key, shiftKey, prevented: false, preventDefault() { this.prevented = true; } };
            handlers.keydown(event);
            return event;
        },
        shown(node) { return node.classList.contains('open'); },
    };
}

test('submitting a reason opens the custom confirmation without cancelling yet', () => {
    const page = mountDeliveries();
    const order = page.orders[0];
    order.trigger.fire('click');
    assert.equal(page.document.activeElement, order.reason);
    assert.equal(order.form.fire('submit').prevented, true);
    assert.equal(page.shown(order.reasonModal), false);
    assert.equal(order.reasonModal.getAttribute('aria-hidden'), 'true');
    assert.equal(page.shown(order.confirmation), true);
    assert.equal(order.confirmation.getAttribute('aria-hidden'), 'false');
    assert.equal(page.document.activeElement, order.goBack);
    assert.equal(order.form.submits, 0);
});

for (const action of ['go back', 'close', 'escape', 'backdrop']) {
    test(`${action} returns to the reason without cancelling or losing input`, () => {
        const page = mountDeliveries();
        const order = page.orders[0];
        order.trigger.fire('click');
        order.form.fire('submit');
        if (action === 'go back') order.goBack.fire('click');
        if (action === 'close') order.confirmation.close.fire('click');
        if (action === 'escape') page.key('Escape');
        if (action === 'backdrop') order.confirmation.fire('click');
        assert.equal(page.shown(order.confirmation), false);
        assert.equal(page.shown(order.reasonModal), true);
        assert.equal(page.document.activeElement, order.reason);
        assert.equal(order.reason.value, 'Customer cancelled order 43');
        assert.equal(order.form.submits, 0);
        order.form.fire('submit');
        assert.equal(page.shown(order.confirmation), true);
        assert.equal(order.form.submits, 0);
    });
}

test('invalid reasons stay in the reason form without opening confirmation', () => {
    const page = mountDeliveries();
    const order = page.orders[0];
    order.trigger.fire('click');
    order.form.valid = false;
    assert.equal(order.form.fire('submit').prevented, true);
    assert.equal(page.shown(order.reasonModal), true);
    assert.equal(page.shown(order.confirmation), false);
    assert.equal(order.form.reports, 1);
    assert.equal(order.form.submits, 0);
});

test('confirmation rechecks validity and returns to invalid input instead of cancelling', () => {
    const page = mountDeliveries();
    const order = page.orders[0];
    order.trigger.fire('click');
    order.form.fire('submit');
    order.form.valid = false;
    order.confirm.fire('click');
    assert.equal(page.shown(order.confirmation), false);
    assert.equal(page.shown(order.reasonModal), true);
    assert.equal(order.form.reports, 1);
    assert.equal(order.form.submits, 0);
    assert.equal(order.form.dataset.cancellationConfirmed, undefined);
});

test('confirmation submits once and blocks duplicate submission and dismissal while saving', () => {
    const page = mountDeliveries();
    const order = page.orders[0];
    order.trigger.fire('click');
    order.form.fire('submit');
    order.confirm.fire('click');
    order.confirm.fire('click');
    assert.equal(order.form.fire('submit').prevented, true);
    order.goBack.fire('click');
    page.key('Escape');
    order.confirmation.fire('click');
    assert.equal(order.form.submits, 1);
    assert.equal(order.confirm.disabled, true);
    assert.equal(order.submit.disabled, true);
    assert.equal(order.confirm.textContent, 'Cancelling...');
    assert.equal(page.shown(order.confirmation), true);
    assert.equal(order.form.dataset.cancellationConfirmed, undefined);
});

test('each confirmation targets its own order and reason', () => {
    const page = mountDeliveries();
    const [first, second] = page.orders;
    first.trigger.fire('click');
    first.form.fire('submit');
    first.goBack.fire('click');
    first.back.fire('click');
    assert.equal(page.document.activeElement, first.trigger);
    second.trigger.fire('click');
    second.form.fire('submit');
    second.confirm.fire('click');
    assert.equal(first.form.submits, 0);
    assert.equal(second.form.submits, 1);
    assert.equal(second.reason.value, 'Customer cancelled order 44');
    assert.equal(page.shown(first.reasonModal), false);
    assert.equal(page.shown(first.confirmation), false);
});

test('keyboard focus stays inside confirmation and clicking its contents does not dismiss it', () => {
    const page = mountDeliveries();
    const order = page.orders[0];
    order.trigger.fire('click');
    order.form.fire('submit');
    order.confirmation.fire('click', { target: order.goBack });
    assert.equal(page.shown(order.confirmation), true);
    order.confirm.focus();
    assert.equal(page.key('Tab').prevented, true);
    assert.equal(page.document.activeElement, order.confirmation.close);
    assert.equal(page.key('Tab', true).prevented, true);
    assert.equal(page.document.activeElement, order.confirm);
});

test('existing delivered and paid confirmation still submits once and blocks dismissal', () => {
    const page = mountDeliveries();
    page.deliveryTrigger.fire('click');
    assert.equal(page.shown(page.deliveryModal), true);
    page.deliveryForm.requestSubmit();
    page.deliveryForm.requestSubmit();
    page.deliveryModal.close.fire('click');
    page.key('Escape');
    assert.equal(page.deliveryForm.submits, 1);
    assert.equal(page.deliverySubmit.disabled, true);
    assert.equal(page.deliverySubmit.textContent, 'Completing...');
    assert.equal(page.shown(page.deliveryModal), true);
});
