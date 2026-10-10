const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/units/index.blade.php'), 'utf8');
const script = view.match(/<script>\s*([\s\S]*?)<\/script>/)[1];

function mountUnitsPage({ validationError = false } = {}) {
    const elements = {};
    const documentHandlers = {};
    const document = {
        body: { style: {} },
        getElementById(id) { return elements[id]; },
        querySelectorAll(selector) {
            return selector === '.unit-edit-button' ? [editButton] : [toggleButton];
        },
        addEventListener(name, handler) { documentHandlers[name] = handler; },
    };

    function makeElement(id) {
        const handlers = {};
        const classes = new Set();
        const element = {
            value: '', textContent: '', disabled: false, dataset: {}, options: [], selectedIndex: 0,
            submits: 0, valid: true,
            classList: {
                add(value) { classes.add(value); },
                remove(value) { classes.delete(value); },
                contains(value) { return classes.has(value); },
            },
            addEventListener(name, handler) { handlers[name] = handler; },
            focus() { document.activeElement = element; },
            reportValidity() { return element.valid; },
            submit() { element.submits += 1; },
            fire(name, target = element) {
                const event = { target, prevented: false, preventDefault() { this.prevented = true; } };
                handlers[name]?.call(element, event);
                return event;
            },
        };
        elements[id] = element;
        return element;
    }

    for (const id of [
        'addUnitForm', 'addUnitModal', 'addUnitFormModal', 'openAddUnitFormModal', 'cancelAddUnitForm',
        'cancelAddUnit', 'confirmAddUnit', 'unit_name', 'unit_symbol', 'unit_type',
        'confirmUnitName', 'confirmUnitSymbol', 'confirmUnitType', 'editUnitModal', 'editUnitForm',
        'cancelEditUnit', 'saveEditUnit', 'edit_unit_name', 'edit_unit_symbol', 'edit_unit_type',
        'toggleUnitModal', 'toggleModalTitle', 'toggleModalMessage', 'toggleUnitName', 'toggleModalIcon',
        'cancelToggleUnit', 'confirmToggleUnit',
    ]) {
        makeElement(id);
    }

    const editButton = makeElement('editButton');
    editButton.dataset = { name: 'Kilogram', symbol: 'kg', type: 'WEIGHT', updateUrl: '/units/7' };
    const toggleButton = makeElement('toggleButton');
    const toggleForm = makeElement('toggleForm');
    toggleForm.dataset = { name: 'Kilogram', action: 'archive' };
    toggleButton.closest = () => toggleForm;
    elements.unit_name.value = ' Meter ';
    elements.unit_symbol.value = ' m ';
    elements.unit_type.options = [{ text: 'Length' }];
    if (validationError) {
        elements.addUnitFormModal.classList.add('show');
    }

    vm.runInNewContext(script, { document });
    documentHandlers.DOMContentLoaded();
    return {
        elements, document,
        key(key) { documentHandlers.keydown({ key }); },
        shown(id) { return elements[id].classList.contains('show'); },
    };
}

test('Add Unit opens the input popup and focuses the unit name', () => {
    const page = mountUnitsPage();
    assert.equal(page.shown('addUnitFormModal'), false);
    page.elements.openAddUnitFormModal.fire('click');
    assert.equal(page.shown('addUnitFormModal'), true);
    assert.equal(page.document.body.style.overflow, 'hidden');
    assert.equal(page.document.activeElement, page.elements.unit_name);
    assert.equal(page.elements.addUnitForm.submits, 0);
});

for (const closeAction of ['cancel', 'escape', 'backdrop']) {
    test(`${closeAction} closes the form without saving or losing entered values`, () => {
        const page = mountUnitsPage();
        page.elements.openAddUnitFormModal.fire('click');
        if (closeAction === 'cancel') page.elements.cancelAddUnitForm.fire('click');
        if (closeAction === 'escape') page.key('Escape');
        if (closeAction === 'backdrop') page.elements.addUnitFormModal.fire('click');
        assert.equal(page.shown('addUnitFormModal'), false);
        assert.equal(page.document.body.style.overflow, '');
        assert.equal(page.document.activeElement, page.elements.openAddUnitFormModal);
        assert.equal(page.elements.unit_name.value, ' Meter ');
        assert.equal(page.elements.addUnitForm.submits, 0);
    });
}

test('clicking popup content or another key does not dismiss the form', () => {
    const page = mountUnitsPage();
    page.elements.openAddUnitFormModal.fire('click');
    page.elements.addUnitFormModal.fire('click', page.elements.unit_name);
    page.key('Enter');
    assert.equal(page.shown('addUnitFormModal'), true);
});

test('submitting valid input shows a single confirmation and saves only on confirmation', () => {
    const page = mountUnitsPage();
    const elements = page.elements;
    elements.openAddUnitFormModal.fire('click');
    const event = elements.addUnitForm.fire('submit');
    assert.equal(event.prevented, true);
    assert.equal(page.shown('addUnitFormModal'), false);
    assert.equal(page.shown('addUnitModal'), true);
    assert.equal(elements.confirmUnitName.textContent, 'Meter');
    assert.equal(elements.confirmUnitSymbol.textContent, 'm');
    assert.equal(elements.confirmUnitType.textContent, 'Length');
    assert.equal(page.document.activeElement, elements.confirmAddUnit);
    assert.equal(elements.addUnitForm.submits, 0);
    elements.confirmAddUnit.fire('click');
    elements.confirmAddUnit.fire('click');
    assert.equal(elements.addUnitForm.submits, 1);
    assert.equal(elements.confirmAddUnit.disabled, true);
    assert.equal(elements.confirmAddUnit.textContent, 'Adding...');
});

test('invalid input stays in the input popup without showing confirmation or saving', () => {
    const page = mountUnitsPage();
    page.elements.openAddUnitFormModal.fire('click');
    page.elements.addUnitForm.valid = false;
    page.elements.addUnitForm.fire('submit');
    assert.equal(page.shown('addUnitFormModal'), true);
    assert.equal(page.shown('addUnitModal'), false);
    assert.equal(page.elements.addUnitForm.submits, 0);
});

for (const cancelAction of ['cancel', 'escape', 'backdrop']) {
    test(`${cancelAction} on confirmation returns to the entered form for corrections`, () => {
        const page = mountUnitsPage();
        page.elements.openAddUnitFormModal.fire('click');
        page.elements.addUnitForm.fire('submit');
        if (cancelAction === 'cancel') page.elements.cancelAddUnit.fire('click');
        if (cancelAction === 'escape') page.key('Escape');
        if (cancelAction === 'backdrop') page.elements.addUnitModal.fire('click');
        assert.equal(page.shown('addUnitModal'), false);
        assert.equal(page.shown('addUnitFormModal'), true);
        assert.equal(page.document.body.style.overflow, 'hidden');
        assert.equal(page.elements.unit_name.value, ' Meter ');
        assert.equal(page.elements.addUnitForm.submits, 0);
    });
}

test('a server validation failure opens and focuses the recovered form', () => {
    const page = mountUnitsPage({ validationError: true });
    assert.equal(page.shown('addUnitFormModal'), true);
    assert.equal(page.document.body.style.overflow, 'hidden');
    assert.equal(page.document.activeElement, page.elements.unit_name);
});

test('existing edit and archive popups still operate independently of the add form', () => {
    const page = mountUnitsPage();
    const elements = page.elements;
    elements.editButton.fire('click');
    assert.equal(page.shown('editUnitModal'), true);
    assert.equal(elements.editUnitForm.action, '/units/7');
    assert.equal(elements.edit_unit_name.value, 'Kilogram');
    assert.equal(elements.edit_unit_type.value, 'WEIGHT');
    elements.cancelEditUnit.fire('click');
    assert.equal(page.shown('editUnitModal'), false);
    elements.toggleButton.fire('click');
    assert.equal(page.shown('toggleUnitModal'), true);
    assert.equal(elements.toggleModalTitle.textContent, 'Archive Unit?');
    elements.confirmToggleUnit.fire('click');
    assert.equal(elements.toggleForm.submits, 1);
    elements.cancelToggleUnit.fire('click');
    assert.equal(page.shown('toggleUnitModal'), false);
    assert.equal(page.shown('addUnitFormModal'), false);
});
