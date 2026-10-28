# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.2 — 2026-10-28

### Changed

- The extension is now called **BlueFairy Cart Products** everywhere, matching its package names; its configuration is under **Stores → Configuration → Sales → Cart Products**. Nothing on the storefront changes.

### Tests

- The checks that run before a release now run against a Magento install built from the tree being released, with this module installed and compiled. No change to the module.

## 1.1.1 — 2026-10-15

### Changed
- **Button position:** in the Hyvä default theme (`hyva-themes/magento2-default-theme` 1.5.2) the `checkout.cart.order.actions` block is rendered inside the cart form's actions toolbar, after the Update Shopping Cart button (and the clear-cart button when enabled); custom themes may differ. The default theme's `row-reverse` toolbar shows the button to the left of Update Shopping Cart. The loading label is "Exporting…".
- **Injection point:** the layout injects the Alpine.js button into `checkout.cart.order.actions`, a block of the cart items form (`checkout.cart.form` is where the Luma button from the core module sits). The README says what to do when a custom theme has no such block.
- The Requirements section states the supported Magento and PHP releases, the core version this package needs, and the Hyvä theme module the package requires (`hyva-themes/magento2-theme-module` `^1.5`). The PHP requirement is the `php ^8.3` constraint in `composer.json`.
- The README Installation section says the Hyvä theme CSS must be rebuilt (`npm run build`) after installing.
- The export button block is declared not cacheable.

## 1.1.0 — 2026-10-14

### Changed
- **Installs on Magento 2.4.8 as well as 2.4.9.** The framework requirement is now `magento/framework ~103.0.8` (was `^103.0.9`), which refuses a 103.1 framework.
- **Requires `bluefairy/module-cart-products` 1.1.0 or later (was 1.0.4 or later).** If an older core is installed, update both together with a pinned requirement: `composer require bluefairy/module-cart-products-hyva:^1.1.0 -W`.
- README Requirements state the supported Magento releases (2.4.8, 2.4.9) and PHP versions (8.3 or later within what your Magento release supports).
- Licence: the independent-contractor clause is reworded; the meaning is unchanged.

### Fixed
- The Jest harness for the Alpine.js component (`jest.config.js`, `package.json`, `package-lock.json`) no longer ships in the package archive.

## 1.0.5 — 2026-09-18

### Fixed
- The package shipped the GNU General Public License v3 while `composer.json` declared `"license": ["proprietary"]`. The two statements contradicted each other. `LICENSE` is replaced by `LICENSE.txt` carrying the Blue Fairy Development end user licence agreement.

## 1.0.4 — 2026-09-11

### Fixed
- The layout file is renamed from `checkout_cart_index.xml` to `hyva_checkout_cart_index.xml`. The unprefixed handle applied on every theme, including Luma, and injected an Alpine.js component onto a page with no Alpine loaded.
- The module was never registered with Hyvä's Tailwind build (`view/frontend/tailwind/module.css` plus `Observer/RegisterModuleForHyvaConfig.php`, observing `hyva_config_generate_before`), so its utility classes were silently dropped from the compiled CSS. It is registered now.
- The README has no link to `../CartProducts/README.md`.

### Changed
- Added `docs/screenshots/` to `.gitignore`.

## 1.0.3 — 2026-08-12

### Changed
- No change for merchants.

## 1.0.0 — 2026-04-09

### Added
- Alpine.js export button component for Hyva theme (`export-button.phtml`) with reactive loading, success, and error states
- Layout XML (`checkout_cart_index.xml`) that removes the Luma export block and injects the Hyva Alpine.js block into `checkout.cart.order.actions`
- Form key retrieved at runtime using `window.hyva.getFormKey()`
- 30-second `AbortController` timeout on the export fetch request
- Inline error display with `role="alert"` for accessibility on network or server failures
- `canExport()` ViewModel gate — renders nothing when export is disabled or guest export is not permitted
- `X-Filename` response header support for dynamic CSV filename, with `cart.csv` fallback
- Module declaration (`etc/module.xml`) with sequence dependencies on `BlueFairy_CartProducts` and `Hyva_Theme`
- Composer package `bluefairy/module-cart-products-hyva` requiring PHP 8.3+ and `bluefairy/module-cart-products ^1.0`
- `README.md` with installation instructions, architecture comparison (Luma vs. Hyva), component API, button states, and troubleshooting guide
- `FEATURES.md` user-facing feature walkthrough with layout behaviour table
