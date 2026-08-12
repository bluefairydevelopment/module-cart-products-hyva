# Changelog

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
- Composer package `blue-fairy/module-cart-products-hyva` requiring PHP 8.3+ and `blue-fairy/module-cart-products ^1.0`
- Jest 29 unit test suite (10 tests) covering all component states, timeout, filename fallback, blob cleanup, and CSRF token injection
- `README.md` with installation instructions, architecture comparison (Luma vs. Hyva), component API, button states, and troubleshooting guide
- `FEATURES.md` user-facing feature walkthrough with layout behaviour table

[1.0.0]: https://github.com/bluefairydevelopment/module-cart-products-hyva/releases/tag/v1.0.0
