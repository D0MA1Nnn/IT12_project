const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../resources/views/sales/report.blade.php'), 'utf8');
const script = view.match(/<script data-report-print-script>\s*([\s\S]*?)<\/script>/)[1];

function mountPrinting(options = {}) {
    const frames = [];
    const timers = new Map();
    let nextTimer = 0;
    const button = {
        dataset: { reportPrintUrl: '/sales/report?report_type=purchases&q=cement&print=1' },
        textContent: 'Print Report', disabled: false, attributes: {}, focusCount: 0,
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        addEventListener(name, handler) { this.handler = handler; },
        focus() { this.focusCount++; },
        click() { this.handler(); },
    };
    const error = { textContent: '', style: { display: 'none' } };
    function createFrame() {
        const events = {};
        const printEvents = {};
        const frame = {
            style: {}, attributes: {}, removed: false,
            setAttribute(name, value) { this.attributes[name] = value; },
            addEventListener(name, handler, config) { events[name] = { handler, config }; },
            fire(name) {
                const event = events[name];
                if (!event) return;
                if (event.config?.once) delete events[name];
                event.handler();
            },
            remove() { this.removed = true; },
            contentWindow: {
                focusCount: 0, printCount: 0,
                document: { querySelector(selector) {
                    assert.equal(selector, '[data-report-print-document]');
                    if (options.inaccessible) throw new Error('SecurityError');
                    return options.invalidDocument ? null : {};
                } },
                addEventListener(name, handler) { printEvents[name] = handler; },
                focus() { this.focusCount++; },
                print() {
                    this.printCount++;
                    if (options.printThrows) throw new Error('Printing failed');
                    if (options.synchronousAfterPrint) printEvents.afterprint();
                },
                finish() { printEvents.afterprint(); },
            },
        };
        return frame;
    }
    const document = {
        querySelector(selector) {
            return selector === '[data-report-print-url]' ? (options.missingButton ? null : button) : error;
        },
        createElement(tag) { assert.equal(tag, 'iframe'); return createFrame(); },
        body: { appendChild(frame) { frames.push(frame); } },
    };
    vm.runInNewContext(script, {
        document,
        setTimeout(handler, delay) { const id = ++nextTimer; timers.set(id, { handler, delay }); return id; },
        clearTimeout(id) { timers.delete(id); },
    });
    function runTimer(delay) {
        for (const [id, timer] of timers) {
            if (timer.delay === delay) { timers.delete(id); timer.handler(); return; }
        }
        assert.fail(`Missing ${delay} ms timer`);
    }
    return { button, error, frames, timers, runTimer };
}

test('Print Report loads the filtered full document invisibly without opening another page', () => {
    const page = mountPrinting();
    page.button.click();
    const frame = page.frames[0];
    assert.equal(frame.src, page.button.dataset.reportPrintUrl);
    assert.equal(frame.style.display, 'none');
    assert.equal(frame.attributes['aria-hidden'], 'true');
    assert.equal(frame.tabIndex, -1);
    assert.equal(frame.contentWindow.printCount, 0);
    assert.equal(page.button.disabled, true);
    assert.equal(page.button.attributes['aria-busy'], 'true');
    assert.doesNotMatch(script, /window\.open|location\s*=/);
    assert.doesNotMatch(view, /target="_blank"/);
});

test('printing begins only after the report has loaded and opens exactly once', () => {
    const page = mountPrinting();
    page.button.click();
    const frame = page.frames[0];
    frame.fire('load');
    frame.fire('load');
    assert.equal(frame.contentWindow.printCount, 1);
    assert.equal(frame.contentWindow.focusCount, 1);
    assert.equal(page.button.disabled, false);
    assert.equal(page.button.textContent, 'Print Report');
    assert.equal(page.button.attributes['aria-busy'], undefined);
    assert.equal(page.timers.size, 0);
});

test('completing or cancelling printing removes the hidden document and restores button focus', () => {
    const page = mountPrinting();
    page.button.click();
    const frame = page.frames[0];
    frame.fire('load');
    frame.contentWindow.finish();
    assert.equal(frame.removed, true);
    assert.equal(page.button.focusCount, 1);
    assert.equal(page.button.disabled, false);
    assert.equal(page.error.style.display, 'none');
});

test('a browser that closes the print dialog before print returns still cleans up safely', () => {
    const page = mountPrinting({ synchronousAfterPrint: true });
    page.button.click();
    page.frames[0].fire('load');
    assert.equal(page.frames[0].removed, true);
    assert.equal(page.button.disabled, false);
});

test('repeated clicks while loading cannot open duplicate print dialogs', () => {
    const page = mountPrinting();
    page.button.click();
    page.button.click();
    assert.equal(page.frames.length, 1);
});

test('another print works even when the browser did not dispatch afterprint', () => {
    const page = mountPrinting();
    page.button.click();
    const previous = page.frames[0];
    previous.fire('load');
    page.button.click();
    assert.equal(previous.removed, true);
    previous.contentWindow.finish();
    assert.equal(page.button.disabled, true);
    assert.equal(page.frames[1].removed, false);
    page.frames[1].fire('load');
    assert.equal(page.frames[1].contentWindow.printCount, 1);
});

for (const options of [{ invalidDocument: true }, { inaccessible: true }, { printThrows: true }]) {
    test(`${Object.keys(options)[0]} shows a temporary error and allows retrying`, () => {
        const page = mountPrinting(options);
        page.button.click();
        const frame = page.frames[0];
        frame.fire('load');
        assert.equal(frame.removed, true);
        assert.equal(page.button.disabled, false);
        assert.equal(page.error.style.display, 'block');
        assert.match(page.error.textContent, /Please try again/);
        if (!options.printThrows) assert.equal(frame.contentWindow.printCount, 0);
        page.runTimer(3000);
        assert.equal(page.error.style.display, 'none');
    });
}

for (const event of ['error', 'timeout']) {
    test(`a report load ${event} restores the button without printing`, () => {
        const page = mountPrinting();
        page.button.click();
        const frame = page.frames[0];
        if (event === 'error') frame.fire('error'); else page.runTimer(30000);
        assert.equal(frame.removed, true);
        assert.equal(page.button.disabled, false);
        assert.equal(page.error.style.display, 'block');
        frame.fire('load');
        assert.equal(frame.contentWindow.printCount, 0);
        page.button.click();
        assert.equal(page.error.style.display, 'none');
        assert.equal(page.frames.length, 2);
    });
}

test('pages without a Print Report button remain unaffected', () => {
    const page = mountPrinting({ missingButton: true });
    assert.equal(page.frames.length, 0);
    assert.equal(page.timers.size, 0);
});
