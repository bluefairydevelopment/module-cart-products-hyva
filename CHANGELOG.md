# Changelog

## 1.1.0 — 2026-10-14

### Changed
- **Installs on Magento 2.4.8 as well as 2.4.9.** The framework requirement is now `magento/framework ~103.0.8` (was `^103.0.9`), which refuses a 103.1 framework and admits later 2.4 releases from framework 103.0.10 up.
- **Requires `bluefairy/module-cart-products` 1.1.0 or later (was 1.0.4 or later).** If an older core is installed, update both together with a pinned requirement: `composer require bluefairy/module-cart-products-hyva:^1.1.0 -W`.
- README Requirements state the supported Magento releases (2.4.8, 2.4.9) and PHP versions (8.3 or later within what your Magento release supports), and what has been tested on each.
- Licence: the independent-contractor clause is reworded; the meaning is unchanged.

### Fixed
- The Jest harness for the Alpine.js component (`jest.config.js`, `package.json`, `package-lock.json`) no longer ships in the package archive.
- Development only (nothing ships): the release-gate tests run git with every `GIT_*` variable removed (except `GIT_EXEC_PATH`), so the pre-push hook's `GIT_DIR` can no longer send a scratch repository's commits to this repository, and a regression test proves it.

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
