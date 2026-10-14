# Changelog

## Unreleased

### Changed (M2MAP-650 review round 7)
- **phpcs reads the tests** (M2MAP-650 review round 7, small-rig LOW-3), as in `bluefairy/module-cart-products`: the `*/Test/*` exclusion is gone, the findings are fixed in code or carry a per-line `phpcs:ignore <sniff> -- <reason>`, and the run is 0 errors and 0 warnings (`~/Sites/.worktrees/rig-gate-runs/20261013-s8/phpcs/green-*.json`).

### Verified (M2MAP-650 review round 8)
- **Review round 8 final runs** (build round 9, S9) are in `~/Sites/.worktrees/rig-gate-runs/20261013-s9-final/`, on committed clean package HEADs (rig `~/Sites/magento-rig` main 6fda311; the first driver's `verdicts` opens with `rig 6fda311 dirty=1`, so one rig file was uncommitted at 17:33 - which one was not recorded, and the rig working tree is clean now - and the gates that driver ran, `gate-small`, `gate-jira` and `gate-cart`, were rerun on a clean rig in review round 9, see that entry). Its three verdicts files record each tree's HEAD and dirt, each step's exit code and VERDICT line, and the served installs' 15-item fingerprint taken before and after EVERY step, with the diff of every step that changed it: `verdicts` (the first driver), `verdicts-rerun` and `verdicts-rerun2` (reruns, below). A step that writes the served `composer.json`, `composer.lock` and `vendor/composer/installed.json` (every `bin/rig gate` does: composer reinstalls the package on the served install) shows those files in its diff; this entry does not call such a fingerprint EQUAL, it quotes the difference. In these runs `gate-small` and `gate-jira` changed those three files of each install and `gate-cart` changed `composer.json` and `composer.lock` only (4 of the 15 items, not `installed.json`), and nothing else; `recipes-gate`, `oc-gate`, `php83`, `it-small`, `it-cart` and `it-oc` left all 15 items UNCHANGED; "fingerprint steps CHANGED outside the gate-written files: 0" (`app/etc/env.php`, `app/etc/config.php`, the database checksums and the crontab never moved in any step). Reruns, and why: the first driver's `recipes-gate`, `oc-gate`, `php83` and `it-small` failed because the box disk was full (`No space left on device`; dirs `*-diskfull` kept) and were rerun; the rerun's `recipes-gate` then reported 9 real `phpstan` errors in Recipes (a dead second catch in the three uploaders, cured in 7418ce7; dir `recipes-gate-phpstan-red`), so `recipes-gate`, `php83` and `it-small` ran a third time on the cured HEAD (`verdicts-rerun2`); the rerun's `oc-gate` (24 commands, 0 nonzero) stands, and the rerun's own `php83` was stopped by the operator at that point (its box process group killed; `verdicts-rerun` ends there) and is not counted. For this package (HEAD 8f54e2f, unchanged) with base 1.0.6 and graphql 1.0.7: `gate-cart` rc=0 VERDICT GREEN (`rig-gate-runs/20261013-175550-30629`), `php83` (31 tests, rc=0 on both roots), `it-cart` rc=0 VERDICT GREEN (`rig-integration-runs/20261013-183058-65476`). PHPUnit 12 on 2.4.9 prints notices that `rc=0` hides; measured on PHP 8.3 / 2.4.9 in `php83/summary`: recipes 665, recipes-hyva 31, jira 162, cart-products 122, cart-products-graphql 23, cart-products-hyva 0, grid-export 53, tabs 48, mailchimp 95 (PHPUnit 10.5 on 2.4.8 prints none).  A release needs every one of those lines at rc=0 / VERDICT GREEN.

### Verified (M2MAP-650 review round 7)
- **Review round 7 final runs** are on the commit that adds this entry, all in `~/Sites/.worktrees/rig-gate-runs/20261013-s8-final/`, whose `verdicts` file records each tree's HEAD and dirt, each step's exit code and VERDICT line, and the served installs' 15-item fingerprint (rig `~/Sites/magento-rig` main c11020f) taken before and after EVERY step, with the diff of every step that changed it. A step that writes the served `composer.json`, `composer.lock` and `vendor/composer/installed.json` (every `bin/rig gate` does - composer reinstalls the package on the served install - and the rig's `GATE_WRITES` in `tests/mutate.py` names exactly those three) shows those files in its diff; the entry does not call such a fingerprint EQUAL, it quotes the difference. No step is allowed to change `app/etc/env.php`, `app/etc/config.php`, the two database checksums or the crontab, and the verdicts file says whether any did. For this package: `gate-cart`, `php83`, `it-cart` as for the core package (this package has no integration tests of its own). A release needs every one of those lines at rc=0 / VERDICT GREEN.

### Verified (M2MAP-650 review round 6)
- **Review round 6 final runs** are on the commit that adds this entry, all in `~/Sites/.worktrees/rig-gate-runs/20261008-s7-cart-final/` (the cart trio was re-run there after its tags v1.0.0-v1.0.2 were deleted; the small repos, jira, Recipes and Order Comments are in `20261008-s6-final/`), whose `verdicts` file records each tree's HEAD and dirt, each run's exit code and VERDICT line, and the served installs' fingerprint before and after (driver.sh there; rig ~/Sites/magento-rig main 8ec1fa3). For this package: `gate-cart`, `php83` (unit suite, phpcs and phpstan on PHP 8.3 against 2.4.8-p5 and 2.4.9 roots; `php83/summary`), `it-cart` (installed beside the core; no integration tests of its own). The served installs' 9-line fingerprint (rig 8ec1fa3) was taken before and after that driver and DID differ, by design and in four lines: `/var/www/m249/composer.json` (47530da9... -> f01c1e27...), `/var/www/m249/composer.lock` (90ea5406... -> 32424fd9...), `/var/www/m248/composer.json` (61b56282... -> 0be82378...) and `/var/www/m248/composer.lock` (e4a7e734... -> 934ca3f7...), the files the driver's own `bin/rig gate` steps write (the rig's `GATE_WRITES` tolerance names them); `app/etc/env.php`, `app/etc/config.php` and the crontab line were identical (`fingerprint-before.txt` and `fingerprint-after.txt` beside `verdicts`). A release needs every one of those lines at rc=0 / VERDICT GREEN; a fingerprint that differs only in those gate-written composer files is reported with this diff, not called EQUAL.

### Changed (M2MAP-650 review round 6)
- **`magento/framework` is required at `~103.0.8` (was `^103.0.8`), and the README's Requirements name the
  releases it still admits that nobody has run** (M2MAP-650 review round 6, S6 item 5; the small repos' LOW-7
  cure carried here). `^103.0.8` also admitted a 103.1 framework, and nothing pinned the constraint at all.
  `~103.0.8` (>= 103.0.8, < 103.1.0) refuses 103.1; it still admits 103.0.10 and up (a future 2.4.10), and is
  not capped below it, because that would make Composer refuse a merchant's Magento upgrade to 2.4.10 until this
  package is released again (the parent's decision of 2026-10-08). So the README says so. Pinned by the new
  `Test/Unit/Package/TheManifestAdmitsOnlyTheMeasuredMagentoReleasesTest` (the small repos' test): measured
  releases admitted, 2.4.7-p5 / 2.4.6 / 2.4.0 / 103.1 / 104 refused, the README quoting the constraint and naming
  2.4.10. Red first, logs in `~/Sites/.worktrees/rig-gate-runs/20261008-s7-manifest-cart-oc/`: `red-1-tests-only-*`
  (old manifest and README: 2 failures), `green-*`, `mutant-widen-103.1-*` (`~103.0.8 || ^103.1` planted: red).
- **The PHP lines say what was measured, PHP 8.3 included** (M2MAP-650 review round 6, S6 item 1). They said PHP 8.3 was admitted but not run. It has now been run: this package's PHP unit suite, phpcs and phpstan on PHP 8.3.35 against private 8.3-resolved copies of 2.4.8-p5 (PHPUnit 10.5) and 2.4.9 (PHPUnit 12.5), every line rc=0 (`~/Sites/.worktrees/rig-gate-runs/20261008-s6-php83/s7-rerun-154809/summary`; the exploratory `explore-run-152846/` beside it was red only because its clones had no `core.hooksPath` and jira had no sibling checkouts, both fixed in `s6-php83.sh`). The integration suite has not been run on PHP 8.3, and the text says so. Changed: README Requirements.
- Tags v1.0.0-v1.0.2 deleted 2026-10-08 (superseded; never install). Jennifer's decision; v1.0.3 stays until the estate moves off it. The companions' `TheBaseRequirementExcludesUnconstrainedBaseReleasesTest` no longer records them (seen red first with the old record against the pruned history: `~/Sites/.worktrees/rig-gate-runs/20261008-s7-cart-tags/red-old-record-pruned-tags-*.log`; green: `green-new-record-pruned-tags-*.log`).

### Changed (licence)
- Licence: the independent-contractor clause is reworded; it now reads "Nothing in this Agreement creates a joint venture, agency, franchise or employment relationship". The meaning is unchanged (each party remains an independent contractor). (M2MAP-650)

### Fixed — the README no longer offers 2.4.8 installs that land on the 1.0.3 releases (M2MAP-650 review round 3, M1)

- The requirements said the package "cannot be installed" on 2.4.8 until the next core release. Measured, it can — on the wrong releases: with every released tag's `composer.json` resolved against 2.4.8-p5's package versions (Composer 2.10, `~/Sites/.worktrees/rig-gate-runs/20261007-194842-cart-products-composer-resolution/`), a plain `composer require bluefairy/module-cart-products-hyva` on 2.4.8 picks this package's **1.0.3 with core 1.0.3**, releases from before any Magento constraint was declared; on 2.4.9 it picks the released 1.0.4+ line. The README now says: on 2.4.8 this needs core 1.0.6+ and this package's next release, neither released yet, and the 1.0.3 releases must not be installed.
- The older-core advice was an unpinned `composer require bluefairy/module-cart-products-hyva -W`, which on 2.4.8 resolves to the 1.0.3 releases. It is now pinned to `:^1.0.6 -W`: measured on 2.4.8 against the released tags only, the pinned require fails (exit 2) instead of falling back, and the README says not to run it unpinned. With the HEAD manifests added as the next releases (core 1.0.6, this package 1.0.6) the same resolution picks those on both 2.4.8 and 2.4.9, and with core 1.0.3 already locked the pinned `-W` command updates both to the new releases (`w-advice.log` there).
- PHP 8.3 is admitted by `php ^8.3` but has never been run for this package (review round 3, L2): the README now says so beside the PHP line instead of leaving "8.3 or later" to read as tested.

### Fixed — the base tag-history test reads unprefixed release tags too (development only; nothing ships) (M2MAP-650 review round 3, L1)

- The test's docblock no longer says the rig's gate uploads "HEAD-only clones without tags" (review round 4, LOW-11). Since rig c2a1b94 the gate uploads each package as a bundle carrying every tag, and in cart gate run `20261007-215732-36460` the git-history case passed on 2.4.9 and 2.4.8 rather than skipping; the docblock now says that, and that a clone of this package alone still skips that one case. Comment only.
- `TheBaseRequirementExcludesUnconstrainedBaseReleasesTest`'s git half listed the base's tags with `git tag -l 'v*'`. Composer offers every tag its `VersionParser` can normalise as a release, prefixed or not, so an unprefixed `1.0.6` tag on an unconstrained `composer.json` would have been a release Composer could pick and this test could not see. It now lists every tag (`git tag -l`) and keeps exactly the ones `VersionParser` normalises; versions are compared through the same parser. Seen red: against a throwaway base clone carrying a planted unprefixed `1.0.9` tag on 1.0.3's `magento/framework: "*"` (`CART_PRODUCTS_GIT` pointed at it), the old test passed and the new one fails naming `1.0.9` (`~/Sites/.worktrees/rig-gate-runs/20261007-195105-cart-products-L1-tag-read/`).
- A new control, `testTheGitReadSeesUnprefixedReleaseTagsAndSkipsNonVersionTags`, builds a throwaway repository with `v1.0.5`, `1.0.6` and `nightly` tags and requires the read to return the first two only; with the listing put back to `'v*'` it fails (`MUTANT-v-star-listing-control.log` there). Measured with PHPUnit 12.5.37 / PHP 8.5.4 on the Mac's vanilla 2.4.9.

### Fixed — the package archive no longer ships the Jest harness (M2MAP-650 review round 3, R3-8)

- `jest.config.js`, `package.json` and `package-lock.json` — the Alpine.js component's Jest harness, which no Magento install runs — were in every release archive. They are now `export-ignore` in this package's `.gitattributes`. **`TheArchiveShipsNoDevelopmentToolingTest`** reads what a merchant actually receives — `git archive --worktree-attributes HEAD`, parsed — and fails on any development file (Test/, node_modules/, .githooks/, the Jest files, the phpunit/phpstan/phpcs configs, .gitattributes), after first requiring `composer.json`, `registration.php`, `etc/module.xml` and the template to be present so an empty read cannot pass. Seen red before the `.gitattributes` change, naming exactly the three files, and green after (`~/Sites/.worktrees/rig-gate-runs/20261007-195105-cart-products-R3-8-archive/`, with the archive listing).

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
