# Changelog

## 1.0.5 — 2026-09-18

### Fixed
- The package shipped the GNU General Public License v3 while `composer.json` declared `"license": ["proprietary"]`. The two statements contradicted each other and the open one was the file in the merchant's `vendor/` directory. `LICENSE` is replaced by `LICENSE.txt` carrying the Blue Fairy Development end user licence agreement (M2MAP-482).
- Added `Test/Unit/Package/TheLicenceAMerchantReceivesTest.php`: the shipped licence must carry the house markers, must not carry any open-source grant, must match `composer.json`, and its `Version X.Y.Z` line must equal the newest changelog heading — that line is the only version string a merchant receives, and nothing was comparing it.

## 1.0.4 — 2026-09-11

### Fixed
- M2MAP-257: layout file renamed `checkout_cart_index.xml` → `hyva_checkout_cart_index.xml` — the unprefixed handle applied on every theme, including Luma, injecting an Alpine.js component onto a page with no Alpine loaded
- M2MAP-229: module was never registered with Hyvä's Tailwind build (`view/frontend/tailwind/module.css` + `Observer/RegisterModuleForHyvaConfig.php` observing `hyva_config_generate_before`), so its utility classes were silently dropped from the compiled CSS
- Added PHP unit test coverage (phpunit.xml.dist + Test/Unit/Observer/RegisterModuleForHyvaConfigTest.php) - this package had none, Jest only
- M2MAP-479: README opened with a link to `../CartProducts/README.md`, which doesn't exist - these are separate repos (module-cart-products, not CartProducts), so the link 404s for anyone browsing the repo

### Changed
- Gitignore docs/screenshots/

## 1.0.3 — 2026-08-12

### Changed
- Fix Jest makeResponse helper that overwrote computed headers object

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-09

### Added

- Alpine.js export button component for Hyva theme (`export-button.phtml`) with reactive loading, success, and error states
- Layout XML (`checkout_cart_index.xml`) that removes the Luma export block and injects the Hyva Alpine.js block into `checkout.cart.order.actions`
- Full-page cache compatibility via runtime form key retrieval using `window.hyva.getFormKey()`
- 30-second `AbortController` timeout on the export fetch request
- Inline error display with `role="alert"` for accessibility on network or server failures
- `canExport()` ViewModel gate — renders nothing when export is disabled or guest export is not permitted
- `X-Filename` response header support for dynamic CSV filename, with `cart.csv` fallback
- Module declaration (`etc/module.xml`) with sequence dependencies on `BlueFairy_CartProducts` and `Hyva_Theme`
- Composer package `bluefairy/module-cart-products-hyva` requiring PHP 8.3+ and `bluefairy/module-cart-products ^1.0`
- Jest 29 unit test suite (10 tests) covering all component states, timeout, filename fallback, blob cleanup, and CSRF token injection
- `README.md` with installation instructions, architecture comparison (Luma vs. Hyva), component API, button states, and troubleshooting guide
- `FEATURES.md` user-facing feature walkthrough with layout behaviour table

[1.0.0]: https://github.com/bluefairydevelopment/module-cart-products-hyva/releases/tag/v1.0.0
