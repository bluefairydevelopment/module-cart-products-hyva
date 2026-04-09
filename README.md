# BlueFairy_CartProductsHyva

Hyva theme companion for [BlueFairy_CartProducts](../CartProducts/README.md). Replaces the Luma export button with a self-contained Alpine.js component that is compatible with Hyva's full-page caching and frontend architecture.

---

## Requirements

- Magento 2.4.x
- PHP 8.3+
- `BlueFairy_CartProducts` (core module — must be installed first)
- Hyva Theme

---

## Installation

```bash
composer require blue-fairy/module-cart-products-hyva
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
