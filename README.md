# BlueFairy_CartProductsHyva

Hyva theme companion for `BlueFairy_CartProducts` (`bluefairy/module-cart-products`). Replaces the Luma export button with a self-contained Alpine.js component that is compatible with Hyva's full-page caching and frontend architecture.

---

## Requirements

- Magento 2.4.8 or 2.4.9 (`magento/framework ~103.0.8`; the constraint also admits any later 2.4 release on framework 103.0.10 or above (2.4.10 onwards, none released yet), on which this package has not been run, and refuses any 103.1 framework). **On 2.4.8 this needs the core (`bluefairy/module-cart-products`) at 1.0.6 or later, and this package at 1.0.6 or later.** Core releases 1.0.4 and 1.0.5 require Magento 2.4.9 (`magento/framework ^103.0.9`), so a plain `composer require` of this package on 2.4.8 falls back to this package's 1.0.3 with core 1.0.3 — releases from before any Magento constraint was declared. **Do not install the 1.0.3 releases.** (Measured by resolving every released tag's `composer.json` against 2.4.8-p5's package versions with Composer 2.10.)
- What has been measured, on Magento 2.4.8-p5 (PHP 8.4, PHPUnit 10.5) and 2.4.9 (PHP 8.5, PHPUnit 12.5): this package's PHP unit suite, phpcs and phpstan pass; and with this package installed, the core's integration test `ExportButtonLayoutTest` renders the cart under a Hyvä theme (Hyva/default-csp) and finds this package's export block in place of the Luma one. This package has **no integration tests of its own**, and the Alpine.js component's tests (`Test/Jest`) are Jest unit tests, not a browser run.
- PHP 8.3 or later, within what your Magento release supports (2.4.8: 8.3–8.4; 2.4.9: 8.3–8.5). On PHP 8.3, against both 2.4.8-p5 and 2.4.9, this package's unit suite, phpcs and phpstan pass; nothing else has been run on PHP 8.3.
- `BlueFairy_CartProducts` **1.0.4 or later** on 2.4.9, **1.0.6 or later** on 2.4.8 (core module — must be installed first). Releases 1.0.4 and 1.0.5 of this package do not declare that minimum (they accept any 1.x core). If an older core is already installed, update both together with a pinned requirement, which cannot fall back to the 1.0.3 releases: `composer require bluefairy/module-cart-products-hyva:^1.0.6 -W` (the pinned requirement fails rather than installing anything older). Do not run it unpinned: on 2.4.8 an unpinned `composer require bluefairy/module-cart-products-hyva -W` resolves to the 1.0.3 releases.
- Hyva Theme

---

## Installation

```bash
composer require bluefairy/module-cart-products-hyva
php bin/magento module:enable BlueFairy_CartProductsHyva
php bin/magento setup:upgrade
php -d memory_limit=2G bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## What It Does

When this module is enabled:

1. The Luma export button block (`bluefairy.cartproducts.export`) is **removed** from the layout via `remove="true"`.
2. A new Alpine.js export button block (`bluefairy.cartproducts.hyva.export`) is **injected** into `checkout.cart.form`.

The button uses the same backend endpoint (`POST /bluefairy_cartproducts/cart/export`) and the same `ExportButton` ViewModel from the core module. Only the frontend rendering changes.

---

## Hyva-Specific Implementation

| Concern | Luma approach | Hyva approach |
|---------|---------------|---------------|
| Form key | `$block->getFormKey()` in uncached block | `window.hyva.getFormKey()` at runtime |
| JavaScript | Inline `<script>` with vanilla `fetch()` | Alpine.js `x-data` component |
| URL escaping | `$escaper->escapeUrl()` | `$escaper->escapeHtmlAttr()` (in `x-data` attribute) |
| State management | DOM manipulation | Alpine.js reactive properties (`loading`, `downloaded`, `error`) |
| Flash prevention | CSS classes | `x-cloak` directive |

---

## Configuration

This module has no separate configuration. It inherits all settings from the core module:

- **Stores > Configuration > Sales > Cart Export**

The `canExport()` ViewModel gate controls whether the button renders. If the feature is disabled or the user is a guest without permission, the template outputs nothing.

---

## Template

The Alpine.js component is defined in:

```
view/frontend/templates/cart/export-button.phtml
```

### Component API

```javascript
cartExport(url) {
    loading: false,      // true while fetch is in progress
    downloaded: false,    // true for 2s after successful download
    error: null,          // error message string on failure
    download()            // triggers the export
}
```

### Button States

- **Default:** "Export Cart" — ready to click
- **Loading:** "Exporting..." — button disabled, fetch in progress
- **Success:** "Downloaded" — shown for 2 seconds, then resets
- **Error:** Inline red error message below the button with `role="alert"`

---

## Disabling

```bash
php bin/magento module:disable BlueFairy_CartProductsHyva
php bin/magento cache:flush
```

When disabled, the Luma export button from the core module will render instead.

---

## Troubleshooting

**Export button does not appear on Hyva cart page**
- Verify both `BlueFairy_CartProducts` and `BlueFairy_CartProductsHyva` are enabled.
- Verify the feature is enabled in admin configuration.
- Flush the cache: `php bin/magento cache:flush`.

**Two export buttons appear**
- This should not happen — the layout XML removes the Luma block. If it does, check for layout XML conflicts from other modules. Run `php bin/magento dev:template-hints:enable` to identify which templates are rendering.

**Form key errors / CSRF failures**
- Ensure `window.hyva` is available on the page. The Hyva theme must be active.
- Check that the page is not being served from a stale full-page cache entry.
