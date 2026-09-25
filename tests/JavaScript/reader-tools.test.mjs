import assert from 'node:assert/strict';
import test from 'node:test';
import readerTools from '../../resources/js/reader-tools.js';

const markdown = '# A quieter world — পৃথিবী 🌿\n\nBy Elena Rossi\n\nSource: http://localhost:8000/@elena/story\n\nA complete paragraph, including & + ? # and café.\n\nThe final line belongs in the prompt.';
const response = (body = markdown, type = 'text/markdown', status = 200) => new Response(body, { status, headers: { 'content-type': type } });

function deferred() {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
}

// Only browser APIs are substituted. Every action below runs the production Alpine factory.
function harness(t, { fetcher = async () => response(), clipboardDenied = false, clipboardItem = false, writeText = true } = {}) {
    const saved = new Map();
    const calls = { fetch: [], clipboard: [], clipboardMethods: [], focus: [], select: [] };
    const replace = (key, value) => {
        saved.set(key, Object.getOwnPropertyDescriptor(globalThis, key));
        Object.defineProperty(globalThis, key, { configurable: true, writable: true, value });
    };
    const clipboard = {
        async writeText(value) {
            calls.clipboardMethods.push('writeText');
            if (clipboardDenied) throw new Error('Permission denied');
            calls.clipboard.push(value);
        },
    };
    if (!writeText) delete clipboard.writeText;
    const browser = { innerWidth: 1280, innerHeight: 900, dispatchEvent() {} };
    if (clipboardItem) {
        class ClipboardItem {
            constructor(data) { this.data = data; }
        }
        browser.ClipboardItem = ClipboardItem;
        replace('ClipboardItem', ClipboardItem);
        clipboard.write = async items => {
            calls.clipboardMethods.push('write');
            if (clipboardDenied) throw new Error('Permission denied');
            for (const item of items) calls.clipboard.push(await (await item.data['text/plain']).text());
        };
    }
    replace('window', browser);
    replace('navigator', { clipboard });
    replace('fetch', async (...args) => { calls.fetch.push(args); return fetcher(...args); });
    const component = readerTools('/read/stories/1.md');
    const ref = name => ({ focus: () => calls.focus.push(name), select: () => calls.select.push(name) });
    component.$refs = {
        root: { getBoundingClientRect: () => ({ left: 900, right: 1100, top: 100, bottom: 144, height: 44 }), querySelector: () => ref('first-option') },
        buttons: { getBoundingClientRect: () => component.$refs.root.getBoundingClientRect() },
        panel: { offsetWidth: 330, scrollHeight: 450 },
        toggle: ref('toggle'), manualText: ref('markdown'),
    };
    component.$nextTick = callback => callback();
    t.after(() => {
        component.destroy();
        for (const [key, descriptor] of saved) {
            if (descriptor) Object.defineProperty(globalThis, key, descriptor);
            else delete globalThis[key];
        }
    });
    return { component, calls };
}

test('initialization prepares Copy page without using the clipboard, and opening the menu adds no request', async t => {
    const waiting = deferred();
    const { component, calls } = harness(t, { fetcher: () => waiting.promise });
    component.init();
    component.show(true);
    assert.equal(component.open, true);
    assert.equal(component.markdown, '');
    assert.equal(calls.fetch.length, 1);
    assert.deepEqual(calls.clipboard, []);
    assert.ok(calls.focus.includes('first-option'));
    waiting.resolve(response());
    await component.load();
    assert.equal(component.markdown, markdown);
    assert.equal(calls.fetch.length, 1);
    assert.deepEqual(calls.clipboardMethods, []);
});

test('duplicate Copy page actions share one request and cached copies write synchronously during the click', async t => {
    const waiting = deferred();
    const { component, calls } = harness(t, { fetcher: () => waiting.promise });
    const first = component.copy();
    await component.copy();
    assert.equal(component.busy, true);
    assert.equal(calls.fetch.length, 1);
    assert.deepEqual(calls.clipboard, []);
    waiting.resolve(response());
    await first;
    const cachedCopy = component.copy();
    // Check before any await: yielding here would lose browser clipboard activation.
    assert.deepEqual(calls.clipboardMethods, ['writeText', 'writeText']);
    assert.deepEqual(calls.clipboard, [markdown, markdown]);
    await cachedCopy;
    assert.equal(component.busy, false);
    assert.equal(calls.fetch.length, 1);
    assert.equal(calls.fetch[0][0], '/read/stories/1.md');
    assert.equal(calls.fetch[0][1].credentials, 'same-origin');
    assert.equal(calls.fetch[0][1].headers.Accept, 'text/markdown');
    assert.deepEqual(calls.clipboard, [markdown, markdown]);
});

test('Copy page prefers writeText when both clipboard APIs are available', async t => {
    const { component, calls } = harness(t, { clipboardItem: true });
    await component.copy();
    assert.deepEqual(calls.clipboardMethods, ['writeText']);
    assert.deepEqual(calls.clipboard, [markdown]);
});

test('Copy page supports ClipboardItem when writeText is unavailable', async t => {
    const { component, calls } = harness(t, { clipboardItem: true, writeText: false });
    await component.copy();
    assert.deepEqual(calls.clipboardMethods, ['write']);
    assert.deepEqual(calls.clipboard, [markdown]);
    assert.equal(component.copied, true);
});

for (const [name, fetcher] of [
    ['a network failure', async () => { throw new TypeError('Offline'); }],
    ['an error response', async () => response('Not found', 'text/markdown', 404)],
    ['an HTML login response', async () => response('<h1>Sign in</h1>', 'text/html')],
    ['an empty Markdown response', async () => response(' \n ')],
]) {
    test(`${name} shows a recoverable copy error without copying invalid content`, async t => {
        let fail = true;
        const { component, calls } = harness(t, { fetcher: (...args) => fail ? fetcher(...args) : response() });
        await component.copy();
        assert.equal(component.markdown, '');
        assert.equal(component.copied, false);
        assert.equal(component.failed, true);
        assert.equal(component.manual, false);
        assert.equal(component.open, true);
        assert.equal(component.busy, false);
        assert.match(component.message, /could not be copied/i);
        assert.deepEqual(calls.clipboard, []);
        assert.equal(calls.fetch.length, 1);
        fail = false;
        await component.copy();
        assert.deepEqual(calls.clipboard, [markdown]);
        assert.equal(component.failed, false);
        assert.equal(component.copied, true);
        assert.equal(calls.fetch.length, 2);
    });
}

test('a mobile reader panel fits near viewport edges and closes when its button leaves the screen', t => {
    const { component } = harness(t);
    window.innerWidth = 320;
    window.innerHeight = 600;
    component.$refs.panel.offsetWidth = 276;
    component.$refs.panel.scrollHeight = 900;
    for (const top of [5, 540]) {
        const anchor = { left: 22, right: 222, top, bottom: top + 44, height: 44 };
        component.$refs.root.getBoundingClientRect = () => anchor;
        component.place();
        const panelTop = anchor.top + component.panelTop;
        const panelLeft = anchor.left + component.panelLeft;
        assert.ok(component.panelMaxHeight > 0, `anchor at ${top}: panel retains usable height`);
        assert.ok(panelTop >= 22, `anchor at ${top}: top ${panelTop} should respect the 22px gutter`);
        assert.ok(panelTop + component.panelMaxHeight <= window.innerHeight - 22, `anchor at ${top}: panel bottom should respect the 22px gutter`);
        assert.ok(panelLeft >= 22, `anchor at ${top}: panel left should respect the 22px gutter`);
        assert.ok(panelLeft + component.$refs.panel.offsetWidth <= window.innerWidth - 22, `anchor at ${top}: panel right should respect the 22px gutter`);
    }
    for (const top of [-74, 640]) {
        component.open = true;
        component.$refs.root.getBoundingClientRect = () => ({ left: 22, right: 222, top, bottom: top + 44, height: 44 });
        component.place();
        assert.equal(component.open, false, `offscreen anchor at ${top} should close the panel`);
    }
});

test('feedback cannot move the reader panel away from its split-button anchor', t => {
    const { component } = harness(t);
    const root = { left: 650, right: 1100, top: 100, bottom: 260, height: 160 };
    const buttons = { left: 650, right: 810, top: 100, bottom: 146, height: 46 };
    component.$refs.root.getBoundingClientRect = () => root;
    component.$refs.buttons.getBoundingClientRect = () => buttons;
    component.place();
    assert.equal(root.left + component.panelLeft + component.$refs.panel.offsetWidth, buttons.right);
    assert.equal(root.top + component.panelTop, buttons.bottom + 8);
});

test('Copy page still copies raw Markdown, closes the menu and restores keyboard focus', async t => {
    const { component, calls } = harness(t);
    component.open = true;
    await component.copy();
    assert.deepEqual(calls.clipboard, [markdown]);
    assert.equal(component.copied, true);
    assert.equal(component.message, 'Page copied as Markdown.');
    assert.equal(component.open, false);
    assert.equal(component.busy, false);
    assert.ok(calls.focus.includes('toggle'));
});

test('Copy page still offers manual Markdown copying when clipboard access is denied', async t => {
    const { component, calls } = harness(t, { clipboardDenied: true });
    await component.copy();
    assert.equal(component.markdown, markdown);
    assert.equal(component.manual, true);
    assert.equal(component.open, true);
    assert.match(component.message, /Select the text below/i);
    assert.ok(calls.select.includes('markdown'));
    assert.equal(calls.fetch.length, 1);
});
