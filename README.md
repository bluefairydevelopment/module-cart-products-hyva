# BlueFairy_CartProductsHyva

Hyva theme companion for `BlueFairy_CartProducts` (`bluefairy/module-cart-products`). Replaces the Luma export button with a self-contained Alpine.js component that is compatible with Hyva's full-page caching and frontend architecture.

---

## Requirements

- Magento 2.4.8 or 2.4.9 (`magento/framework ~103.0.8`).
- PHP 8.3 or later, within what your Magento release supports (2.4.8: 8.3–8.4; 2.4.9: 8.3–8.5).
- `BlueFairy_CartProducts` **1.1.0 or later** (core module — must be installed first). Releases 1.0.4 and 1.0.5 of this package do not declare that minimum (they accept any 1.x core). If an older core is installed, update both together with a pinned requirement: `composer require bluefairy/module-cart-products-hyva:^1.1.0 -W`.
- On Magento 2.4.8, install this package and the core at 1.1.0 or later and pin the version as above. An unpinned `composer require` on 2.4.8 falls back to the 1.0.3 releases, which predate any Magento constraint. **Do not install the 1.0.3 releases.**
- Hyva Theme (`hyva-themes/magento2-theme-module` `^1.5`)
- The Alpine.js component's tests are Jest unit tests; this package has no integration tests of its own.

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
2. A new Alpine.js export button block (`bluefairy.cartproducts.hyva.export`) is **injected** into `checkout.cart.order.actions`, the cart's order-actions row. If a custom theme has no such block, add an equivalent `referenceBlock` for the theme's cart action area.

The button uses the same backend endpoint (`POST /bluefairy_cartproducts/cart/export`) and the same `ExportButton` ViewModel from the core module. Only the frontend rendering changes.

---

## Hyva-Specific Implementation

| Concern | Luma approach | Hyva approach |
|---------|---------------|---------------|
| Form key | `formkey` block inside an uncached block | `window.hyva.getFormKey()` at runtime |
| JavaScript | Inline `<script>` with vanilla `fetch()` | Alpine.js `x-data` component |
| URL escaping | `$escaper->escapeUrl()` | `$escaper->escapeHtmlAttr()` (in `x-data` attribute) |
| State management | DOM manipulation | Alpine.js reactive properties (`loading`, `downloaded`, `error`) |
| Flash prevention | CSS (the error message is hidden by default) | `x-cloak` directive |

---

## Configuration

This module has no separate configuration. It inherits all settings from the core module:

- **Stores → Configuration → Sales → Cart Export**

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
