# Changelog

## Unreleased

### Changed — installs on Magento 2.4.8 (M2MAP-687)

- **The package installs on Magento 2.4.8.** `magento/framework` was pinned at `^103.0.9` (the 2.4.9 line), so Composer refused it on 2.4.8; it is now `^103.0.8`, checked against 2.4.8-p5's `103.0.8-p5` (`hyva-themes/magento2-theme-module ^1.5` already admitted the 1.5.2 that install carries). The README says "Magento 2.4.8 or 2.4.9" instead of "2.4.x". It still needs a base `bluefairy/module-cart-products` release that installs on 2.4.8 — the next base release (base 1.0.4 and 1.0.5 require 2.4.9).

### Added — the package can now be gated from any Magento root (development only; nothing ships)

- **Gate configs that run from the package root against any `MAGENTO_ROOT`** (M2MAP-604): `Test/bootstrap.php`, and `phpunit.xml.dist` pointed at it — until now it named `../../../dev/tests/unit/framework/bootstrap.php`, which resolves only for a module under `app/code/`, and this package is only ever installed into `vendor/`, so the suite could not run anywhere. Plus `phpstan.neon.dist` (level 5, analysed at the PHP 8.3 lower bound) and `phpcs.xml.dist` (the house Magento2 ruleset), which the package never had.
- **`.githooks/pre-push`, tracked** (M2MAP-589). On a tag push it refuses unless the tag names `HEAD` with a clean tree, then runs `composer validate`, phpunit, phpcs and phpstan against `MAGENTO_ROOT` and refuses on any non-zero exit. One line per clone: `git config core.hooksPath .githooks`; `TheReleaseGateIsArmedTest` fails the suite until that is done. `.githooks/` is export-ignored.
- **First measurements, 2026-09-25, local Hyvä root (Magento 2.4.9, PHP 8.5, base 1.0.3 installed):** phpunit 21 tests / 70 assertions exit 0; phpcs 7 files, 0 errors, 0 warnings; phpstan level 5, 0 errors. Each gate was seen red on a plant first, and the hook refused a tag push over a planted red test.
- **`TailwindScansThisPackagesTemplatesTest`** (M2MAP-608), ported from module-jira-hyva. `view/frontend/tailwind/module.css` has carried the `@source` glob over this package's templates since M2MAP-229; nothing pinned it. The test strips comments before looking (module-jira-hyva shipped a file whose only `@source` was in a comment), checks that the glob reaches every shipped template, and carries controls for each of those. Seen red with the `@source` line blanked.
- **Served-stylesheet measurement, 2026-09-25, local Hyvä box** (theme `Blue/bluefairy`, compiled `web/css/styles.css`): the template uses 8 class tokens; 7 are utilities and all 7 have rules; the 8th, `cart-export-wrapper`, is a selector hook with no styling of its own. The check was proved able to fail against a stylesheet stripped of those rules (8 of 8 missing). Honest limit: every utility here (`mt-4`, `btn-primary`, `text-sm`…) is also used elsewhere in the theme, so today the glob contributes no class that only this package uses — the test above is what keeps it that way when one is added.

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
