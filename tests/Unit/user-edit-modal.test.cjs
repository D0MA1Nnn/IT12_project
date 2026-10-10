const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/users/index.blade.php'), 'utf8');
const script = view.match(/<script data-user-edit-script>\s*([\s\S]*?)<\/script>/)[1];

function mountUsers(overflow = '', initiallyOpenId = null) {
    const elements = {};
    const events = {};
    const buttons = [];
    const modals = [];
    const document = {
        body: { style: { overflow } },
        getElementById(id) { return elements[id]; },
        addEventListener(name, handler) { events[name] = handler; },
        querySelectorAll(selector) {
            if (selector === '[data-edit-user]') return buttons;
            if (selector === '[data-user-edit-modal]') return modals;
            throw new Error(`Unexpected selector: ${selector}`);
        },
    };

    function element(id) {
        const handlers = {};
        const classes = new Set();
        const attributes = { 'aria-hidden': 'true' };
        const node = {
            id,
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
        const modal = element(`editUserModal${id}`);
        modal.username = element(`username${id}`);
        modal.save = element(`save${id}`);
        if (id === initiallyOpenId) modal.classList.add('show');
        modal.close = element(`close${id}`);
        modal.footerClose = element(`footerClose${id}`);
        modal.querySelector = () => modal.username;
        modal.querySelectorAll = selector => selector === '[data-close-user-edit]' ? [modal.close, modal.footerClose] : [modal.close, modal.username, modal.footerClose, modal.save];
        modals.push(modal);
        const button = element(`view${id}`);
        button.dataset.editUser = `editUserModal${id}`;
        buttons.push(button);
    }
    const recordModal = element('unrelatedModal');
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

test('Edit opens only its user edit and focuses Username without opening an unrelated modal', () => {
    const page = mountUsers();
    page.buttons[1].fire('click');
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.shown(page.modals[1]), true);
    assert.equal(page.shown(page.recordModal), false);
    assert.equal(page.modals[1].getAttribute('aria-hidden'), 'false');
    assert.equal(page.document.body.style.overflow, 'hidden');
    assert.equal(page.document.activeElement, page.modals[1].username);
});

for (const action of ['header close', 'footer close', 'escape', 'backdrop']) {
    test(`${action} dismisses user edit and restores focus and scrolling`, () => {
        const page = mountUsers('auto');
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
    const page = mountUsers();
    page.buttons[0].fire('click');
    page.modals[0].fire('click', { target: page.modals[0].close });
    page.key('Enter');
    assert.equal(page.shown(page.modals[0]), true);
});

test('Tab and Shift Tab keep keyboard focus inside the details modal', () => {
    const page = mountUsers();
    const modal = page.modals[0];
    page.buttons[0].fire('click');
    modal.close.focus();
    assert.equal(page.key('Tab', true).prevented, true);
    assert.equal(page.document.activeElement, modal.save);
    assert.equal(page.key('Tab').prevented, true);
    assert.equal(page.document.activeElement, modal.close);
});

test('switching user edit hides the previous modal and restores scrolling on close', () => {
    const page = mountUsers();
    page.buttons[0].fire('click');
    page.buttons[1].fire('click');
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.shown(page.modals[1]), true);
    page.modals[1].footerClose.fire('click');
    assert.equal(page.document.activeElement, page.buttons[1]);
    assert.equal(page.document.body.style.overflow, '');
});

test('a missing details modal and Escape with no open details do not disturb the page', () => {
    const page = mountUsers();
    page.buttons[0].dataset.editUser = 'missingModal';
    page.buttons[0].fire('click');
    page.key('Escape');
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.document.body.style.overflow, '');
});



test('validation failures reopen the indicated user and focus Username', () => {
    const page = mountUsers('auto', 14);
    assert.equal(page.shown(page.modals[0]), false);
    assert.equal(page.shown(page.modals[1]), true);
    assert.equal(page.document.activeElement, page.modals[1].username);
    assert.equal(page.document.body.style.overflow, 'hidden');
    page.modals[1].footerClose.fire('click');
    assert.equal(page.document.activeElement, page.buttons[1]);
    assert.equal(page.document.body.style.overflow, 'auto');
});
