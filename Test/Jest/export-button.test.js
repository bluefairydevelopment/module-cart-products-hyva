/**
 * Copyright 2026 Blue Fairy Development. All rights reserved.
 *
 * Jest tests for the cartExport Alpine.js component registered by
 * BlueFairy_CartProductsHyva::cart/export-button.phtml
 *
 * @jest-environment jsdom
 */

/* ---------- load the component from the template ---------- */

// The component is the template's one inline script. It is read from the shipped
// phtml and run as-is, so these tests exercise the code the storefront runs, not a copy.
const fs = require('fs');
const path = require('path');

const TEMPLATE = path.join(__dirname, '../../view/frontend/templates/cart/export-button.phtml');
const ERROR_MESSAGE = 'Export failed. Please try again.';

function templateScript() {
    const html = fs.readFileSync(TEMPLATE, 'utf8');
    const scripts = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)];
    if (scripts.length !== 1) {
        throw new Error(`expected one inline script in the template, found ${scripts.length}`);
    }
    if (scripts[0][1].includes('<?')) {
        throw new Error('the inline script must not contain PHP');
    }
    return scripts[0][1];
}

// Run the script, fire alpine:init, and return the factory it registered under the name.
function registeredFactory(name) {
    const registered = {};
    global.Alpine = { data: jest.fn((n, fn) => { registered[n] = fn; }) };
    // eslint-disable-next-line no-new-func
    new Function(templateScript())();
    window.dispatchEvent(new Event('alpine:init'));
    delete global.Alpine;
    return registered[name];
}

// What Alpine does: build the data object, give it $el (the x-data root), call init().
function cartExport(url, dataset = { url, errorMessage: ERROR_MESSAGE }) {
    const factory = registeredFactory('cartExport');
    if (typeof factory !== 'function') {
        throw new Error('the template did not register cartExport with Alpine.data');
    }
    const component = factory();
    component.$el = { dataset };
    component.init();
    return component;
}

/* ---------- helpers ---------- */

const TEST_URL = 'https://example.com/bluefairy_cartproducts/cart/export';
const FORM_KEY = 'abc123formkey';

function makeBlobUrl() {
    return 'blob:https://example.com/fake-blob-id';
}

function makeResponse(overrides = {}) {
    const { headers: headersObj = {}, ...restOverrides } = overrides;
    const headers = new Map(Object.entries(headersObj));
    return {
        ok: restOverrides.ok !== undefined ? restOverrides.ok : true,
        blob: jest.fn().mockResolvedValue(new Blob(['csv,data'])),
        headers: { get: (name) => headers.get(name) ?? null },
        ...restOverrides
    };
}

/* ---------- setup / teardown ---------- */

beforeEach(() => {
    jest.useFakeTimers();

    // window.hyva.getFormKey()
    window.hyva = { getFormKey: jest.fn().mockReturnValue(FORM_KEY) };

    // URL.createObjectURL / revokeObjectURL
    URL.createObjectURL = jest.fn().mockReturnValue(makeBlobUrl());
    URL.revokeObjectURL = jest.fn();

    // global fetch
    global.fetch = jest.fn().mockResolvedValue(makeResponse());

    // Spy on document.createElement to capture the anchor click
    jest.spyOn(document, 'createElement');
});

afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
    delete window.hyva;
});

/* ---------- tests ---------- */

describe('cartExport()', () => {

    test('1 — initial state: loading false, downloaded false, error null', () => {
        const component = cartExport(TEST_URL);
        expect(component.loading).toBe(false);
        expect(component.downloaded).toBe(false);
        expect(component.error).toBeNull();
    });

    test('2 — loading becomes true while fetch is in progress', async () => {
        // fetch that never resolves during our check
        let resolveFetch;
        global.fetch = jest.fn().mockReturnValue(new Promise((resolve) => { resolveFetch = resolve; }));

        const component = cartExport(TEST_URL);
        const promise = component.download();

        // loading should be true before fetch resolves
        expect(component.loading).toBe(true);

        // resolve to avoid dangling promise
        resolveFetch(makeResponse());
        await promise;
    });

    test('3 — r.ok failure sets error message', async () => {
        global.fetch = jest.fn().mockResolvedValue(makeResponse({ ok: false }));

        const component = cartExport(TEST_URL);
        await component.download();

        expect(component.error).toBe('Export failed. Please try again.');
        expect(component.loading).toBe(false);
        expect(component.downloaded).toBe(false);
    });

    test('4 — success: creates blob URL, clicks anchor, triggers download', async () => {
        const clickSpy = jest.fn();
        const origCreate = document.createElement.bind(document);
        document.createElement = jest.fn().mockImplementation((tag) => {
            const el = origCreate(tag);
            if (tag === 'a') {
                el.click = clickSpy;
            }
            return el;
        });

        const component = cartExport(TEST_URL);
        await component.download();

        expect(URL.createObjectURL).toHaveBeenCalledTimes(1);
        expect(clickSpy).toHaveBeenCalledTimes(1);
        expect(component.downloaded).toBe(true);
        expect(component.loading).toBe(false);
        expect(component.error).toBeNull();
    });

    test('5 — X-Filename header value is used as download filename', async () => {
        global.fetch = jest.fn().mockResolvedValue(
            makeResponse({ headers: { 'X-Filename': 'my-custom-cart.csv' } })
        );

        let capturedAnchor;
        const origCreate = document.createElement.bind(document);
        document.createElement = jest.fn().mockImplementation((tag) => {
            const el = origCreate(tag);
            if (tag === 'a') {
                capturedAnchor = el;
                el.click = jest.fn();
            }
            return el;
        });

        const component = cartExport(TEST_URL);
        await component.download();

        expect(capturedAnchor.download).toBe('my-custom-cart.csv');
    });

    test('6 — fallback filename "cart.csv" when X-Filename header is absent', async () => {
        global.fetch = jest.fn().mockResolvedValue(makeResponse({ headers: {} }));

        let capturedAnchor;
        const origCreate = document.createElement.bind(document);
        document.createElement = jest.fn().mockImplementation((tag) => {
            const el = origCreate(tag);
            if (tag === 'a') {
                capturedAnchor = el;
                el.click = jest.fn();
            }
            return el;
        });

        const component = cartExport(TEST_URL);
        await component.download();

        expect(capturedAnchor.download).toBe('cart.csv');
    });

    test('7 — AbortController abort is scheduled at 30 000 ms', async () => {
        const abortSpy = jest.fn();
        let rejectFetch;

        global.AbortController = jest.fn().mockImplementation(() => ({
            abort: abortSpy,
            signal: 'mock-signal'
        }));

        global.fetch = jest.fn().mockReturnValue(
            new Promise((_resolve, reject) => { rejectFetch = reject; })
        );

        const component = cartExport(TEST_URL);
        const promise = component.download();

        // Advance just under 30s — abort must NOT have fired
        jest.advanceTimersByTime(29999);
        expect(abortSpy).not.toHaveBeenCalled();

        // Advance to 30s — abort fires
        jest.advanceTimersByTime(1);
        expect(abortSpy).toHaveBeenCalledTimes(1);

        // Settle the promise so the async function completes cleanly
        rejectFetch(new DOMException('The user aborted a request.', 'AbortError'));
        await promise;

        expect(component.loading).toBe(false);
        expect(component.error).not.toBeNull();
    });

    test('8 — downloaded resets to false after 2 000 ms', async () => {
        const origCreate = document.createElement.bind(document);
        document.createElement = jest.fn().mockImplementation((tag) => {
            const el = origCreate(tag);
            if (tag === 'a') el.click = jest.fn();
            return el;
        });

        const component = cartExport(TEST_URL);
        await component.download();

        expect(component.downloaded).toBe(true);

        // Advance 1999ms — still true
        jest.advanceTimersByTime(1999);
        expect(component.downloaded).toBe(true);

        // Advance to 2000ms — resets
        jest.advanceTimersByTime(1);
        expect(component.downloaded).toBe(false);
    });

    test('9 — form_key from window.hyva.getFormKey() is included in POST body', async () => {
        const origCreate = document.createElement.bind(document);
        document.createElement = jest.fn().mockImplementation((tag) => {
            const el = origCreate(tag);
            if (tag === 'a') el.click = jest.fn();
            return el;
        });

        const component = cartExport(TEST_URL);
        await component.download();

        expect(window.hyva.getFormKey).toHaveBeenCalledTimes(1);

        const fetchCall = global.fetch.mock.calls[0];
        expect(fetchCall[0]).toBe(TEST_URL);

        const options = fetchCall[1];
        expect(options.method).toBe('POST');
        expect(options.headers['X-Requested-With']).toBe('XMLHttpRequest');

        // body is URLSearchParams — extract form_key
        const body = options.body;
        expect(body).toBeInstanceOf(URLSearchParams);
        expect(body.get('form_key')).toBe(FORM_KEY);
    });

    test('10 — URL.revokeObjectURL is called after 100 ms delay', async () => {
        const origCreate = document.createElement.bind(document);
        document.createElement = jest.fn().mockImplementation((tag) => {
            const el = origCreate(tag);
            if (tag === 'a') el.click = jest.fn();
            return el;
        });

        const component = cartExport(TEST_URL);
        await component.download();

        // Immediately after download — not yet revoked
        expect(URL.revokeObjectURL).not.toHaveBeenCalled();

        // Advance 99ms — still not revoked
        jest.advanceTimersByTime(99);
        expect(URL.revokeObjectURL).not.toHaveBeenCalled();

        // Advance to 100ms — revoked
        jest.advanceTimersByTime(1);
        expect(URL.revokeObjectURL).toHaveBeenCalledWith(makeBlobUrl());
    });
});

describe('strict CSP registration and template bindings', () => {

    test('11 — the script registers cartExport with Alpine.data on alpine:init, once', () => {
        const calls = [];
        global.Alpine = { data: jest.fn((n) => calls.push(n)) };
        new Function(templateScript())();
        expect(calls).toEqual([]);
        window.dispatchEvent(new Event('alpine:init'));
        window.dispatchEvent(new Event('alpine:init'));
        expect(calls).toEqual(['cartExport']);
        delete global.Alpine;
    });

    test('12 — the script defines no global cartExport function', () => {
        delete window.cartExport;
        global.Alpine = { data: jest.fn() };
        new Function(templateScript())();
        delete global.Alpine;
        expect(window.cartExport).toBeUndefined();
    });

    test('13 — init reads the export URL and error message from the data attributes', async () => {
        global.fetch = jest.fn().mockResolvedValue(makeResponse({ ok: false }));
        const component = cartExport(null, { url: TEST_URL, errorMessage: 'Planted message' });
        await component.download();
        expect(global.fetch.mock.calls[0][0]).toBe(TEST_URL);
        expect(component.error).toBe('Planted message');
    });

    test('14 — idle() is true only when neither loading nor downloaded', () => {
        const component = cartExport(TEST_URL);
        expect(component.idle()).toBe(true);
        component.loading = true;
        expect(component.idle()).toBe(false);
        component.loading = false;
        component.downloaded = true;
        expect(component.idle()).toBe(false);
    });

    test('15 — busy() gives the aria-busy string for the loading state', () => {
        const component = cartExport(TEST_URL);
        expect(component.busy()).toBe('false');
        component.loading = true;
        expect(component.busy()).toBe('true');
    });
});
