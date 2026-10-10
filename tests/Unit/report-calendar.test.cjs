const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/sales/report.blade.php'), 'utf8');
const script = view.match(/<script data-report-calendar-script>\s*([\s\S]*?)<\/script>/)[1];

function mountCalendars(mode = 'supported') {
    const inputs = {};
    const buttons = [];
    for (const name of ['from', 'to']) {
        const input = { focusCount: 0, clickCount: 0, pickerCount: 0,
            focus() { this.focusCount++; }, click() { this.clickCount++; } };
        if (mode !== 'unsupported') {
            input.showPicker = function () {
                this.pickerCount++;
                if (mode === 'restricted') throw new Error('SecurityError');
            };
        }
        inputs[`report-${name}`] = input;
        const button = { dataset: { reportDatePicker: `report-${name}` },
            addEventListener(name, handler) { this.handler = handler; }, click() { this.handler(); } };
        buttons.push(button);
    }
    const document = { querySelectorAll() { return buttons; }, getElementById(id) { return inputs[id]; } };
    vm.runInNewContext(script, { document });
    return { inputs, buttons };
}

for (const [index, name] of ['from', 'to'].entries()) {
    test(`${name} calendar button opens only its corresponding date picker`, () => {
        const page = mountCalendars();
        page.buttons[index].click();
        assert.equal(page.inputs[`report-${name}`].focusCount, 1);
        assert.equal(page.inputs[`report-${name}`].pickerCount, 1);
        assert.equal(page.inputs[`report-${name}`].clickCount, 0);
        assert.equal(page.inputs[index === 0 ? 'report-to' : 'report-from'].pickerCount, 0);
    });
}

for (const mode of ['unsupported', 'restricted']) {
    test(`${mode} browsers fall back to focusing and selecting the date field`, () => {
        const page = mountCalendars(mode);
        page.buttons[0].click();
        assert.equal(page.inputs['report-from'].focusCount, 1);
        assert.equal(page.inputs['report-from'].clickCount, 1);
    });
}

test('a missing date field is ignored without affecting another date', () => {
    const page = mountCalendars();
    page.buttons[0].dataset.reportDatePicker = 'missing';
    assert.doesNotThrow(() => page.buttons[0].click());
    assert.equal(page.inputs['report-to'].focusCount, 0);
});

test('calendar icons have a nonshrinking width separate from the date input', () => {
    assert.match(view, /\.report-date-picker\s*\{[^}]*flex:\s*0 0 22px/);
    assert.match(view, /\.report-date-picker svg\s*\{[^}]*width:\s*18px;[^}]*height:\s*18px/);
    assert.equal((view.match(/aria-label="Choose (From|To) date"/g) || []).length, 2);
});
