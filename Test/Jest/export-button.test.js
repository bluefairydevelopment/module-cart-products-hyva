/**
 * Copyright 2026 Blue Fairy Development. All rights reserved.
 *
 * Jest tests for the cartExport() Alpine.js component defined in
 * BlueFairy_CartProductsHyva::cart/export-button.phtml
 *
 * @jest-environment jsdom
 */

/* ---------- extract the function under test ---------- */

// The phtml template defines a global `cartExport(url)` factory.
// We replicate it here exactly as rendered (minus PHP escaper wrappers).
function cartExport(url) {
    return {
        loading: false,
        downloaded: false,
        error: null,
        async download() {
            this.loading = true;
            this.error = null;
            this.downloaded = false;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 30000);
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({ form_key: window.hyva.getFormKey() }),
                    signal: controller.signal
                });
                clearTimeout(timeout);
                if (!r.ok) throw new Error('Export failed. Please try again.');
                const blob = await r.blob();
                const filename = r.headers.get('X-Filename') ?? 'cart.csv';
                const a = Object.assign(document.createElement('a'), {
                    href: URL.createObjectURL(blob),
                    download: filename
                });
                a.click();
                setTimeout(() => URL.revokeObjectURL(a.href), 100);
                this.downloaded = true;
                setTimeout(() => { this.downloaded = false; }, 2000);
            } catch (err) {
                clearTimeout(timeout);
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        }
    };
}

/* ---------- helpers ---------- */

const TEST_URL = 'https://example.com/bluefairy_cartproducts/cart/export';
const FORM_KEY = 'abc123formkey';

function makeBlobUrl() {
    return 'blob:https://example.com/fake-blob-id';
}

function makeResponse(overrides = {}) {
    const headers = new Map(Object.entries(overrides.headers || {}));
    return {
        ok: overrides.ok !== undefined ? overrides.ok : true,
        blob: jest.fn().mockResolvedValue(new Blob(['csv,data'])),
        headers: { get: (name) => headers.get(name) || null },
        ...overrides
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
        const origAbort = AbortController;
        global.AbortController = jest.fn().mockImplementation(() => ({
            abort: abortSpy,
            signal: 'mock-signal'
        }));

        // Fetch that hangs so the timeout can fire
        global.fetch = jest.fn().mockReturnValue(new Promise(() => {}));

        const component = cartExport(TEST_URL);
        // Don't await — we want it in-flight
        const promise = component.download();

        // Advance just under 30s — abort must NOT have fired
        jest.advanceTimersByTime(29999);
        expect(abortSpy).not.toHaveBeenCalled();

        // Advance to 30s — abort fires
        jest.advanceTimersByTime(1);
        expect(abortSpy).toHaveBeenCalledTimes(1);

        global.AbortController = origAbort;

        // Clean up the pending promise (it will reject from abort but we catch it)
        // We need fetch to reject so the promise can settle
        // Since our mocked fetch never resolves, we can't await. Just verify the abort was called.
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
