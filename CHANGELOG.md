# Changelog

## Unreleased

### Changed — installs on Magento 2.4.8 (M2MAP-687)

- **The package installs on Magento 2.4.8.** `magento/framework` was pinned at `^103.0.9` (the 2.4.9 line), so Composer refused it on 2.4.8; it is now `^103.0.8`, checked against 2.4.8-p5's `103.0.8-p5` (`hyva-themes/magento2-theme-module ^1.5` already admitted the 1.5.2 that install carries). The README says "Magento 2.4.8 or 2.4.9" instead of "2.4.x". On 2.4.8 it installs only once the next base `bluefairy/module-cart-products` release is published: it now requires the base at `^1.0.4` (below), and the released base 1.0.4 and 1.0.5 require the 2.4.9 line (`magento/framework ^103.0.9`).

### Fixed — the base requirement admitted base releases that install on any Magento (M2MAP-650 review, R2-1)

- **`bluefairy/module-cart-products` is now required at `^1.0.4`, not `^1.0`.** Read from each base tag's `composer.json` via git: base 1.0.0 – 1.0.3 declare `magento/framework: "*"`, and 1.0.4 / 1.0.5 require `^103.0.9`. With `^1.0`, on Magento 2.4.8 Composer would pair this package with base 1.0.3 (the newest of the 1.0.1 – 1.0.3 releases that admit any framework) — releases from before M2MAP-151, which carry no `.gitattributes`, so their archives still ship `Test/`. The sentence this entry replaced ("it still needs a base release … base 1.0.4 and 1.0.5 require 2.4.9") missed that: the old requirement did not need a new base on 2.4.8, it fell back to 1.0.3. Our integration rig could not show it, because it installs the base from a path repository at a forced version. The published 1.0.4 and 1.0.5 of this package still declare `^1.0` (published releases cannot be amended); on a store whose base is older than 1.0.4, update both together with `composer require bluefairy/module-cart-products-hyva -W`.
- **`TheBaseRequirementExcludesUnconstrainedBaseReleasesTest`** holds it: the base requirement must admit no base release whose `composer.json` leaves `magento/framework` unconstrained (missing, or admitting Magento 2.0.0's framework `100.0.0`, as `*` does), read with Composer's own `Composer\Semver`. It checks a record of every base tag's requirement on every run, and — when the base repository's git history is reachable (`$CART_PRODUCTS_GIT`, `$CART_PRODUCTS_SRC`, or a `module-cart-products` clone beside this repository's main checkout or beside the package root) — reads every `v*` tag with `git tag` / `git show <tag>:composer.json`, applies the same rule to all of them and fails if the record differs from git. Where no history is reachable (the rig's gate uploads HEAD-only clones without tags) the git test is skipped, naming where it looked; the record check still runs. Seen red against `^1.0` (both the record test and the git test named v1.0.0 – v1.0.3) and against a planted wrong record for v1.0.3; a `$CART_PRODUCTS_GIT` that names no tagged repository fails rather than skips.

### Fixed — the README says what was measured (M2MAP-650 review, R2-2)

- The requirements said "integration-tested on 2.4.8-p5 and 2.4.9". This package has no integration tests of its own. They now say what was measured on both installs — this package's PHP unit suite, phpcs and phpstan, and the core's `ExportButtonLayoutTest` run with this package installed under a Hyvä theme — that the Alpine.js tests are Jest unit tests, that 1.0.4 is the core minimum, and that on 2.4.8 the package cannot be installed until the next core release.

### Changed — tests (development only; nothing ships)

- `RegisterModuleForHyvaConfigTest`'s docblock `@covers` is now a `#[CoversClass]` attribute (M2MAP-650 review, R2-9): PHPUnit 12 ignores the docblock form, so the class declared no coverage target there — run with `requireCoverageMetadata="true"` on PHPUnit 12.5 all 5 tests were risky (exit 1); with the attribute, 0. `testStripsLeadingSlashFromRelativePath` now expects `setData()` exactly once, which also clears the PHPUnit 12 notice "No expectations were configured for the mock object".

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
