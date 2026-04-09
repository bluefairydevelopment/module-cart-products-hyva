# BlueFairy_CartProductsHyva — Features Guide

Visual walkthrough of the Hyva Alpine.js cart export button.

---

## Table of Contents

1. [Export Button on Hyva Cart Page](#1-export-button-on-hyva-cart-page)
2. [Button States](#2-button-states)
3. [Layout Behaviour](#3-layout-behaviour)

---

## 1. Export Button on Hyva Cart Page

The Alpine.js export button appears on the shopping cart page within the `checkout.cart.form` container, replacing the Luma button.

The button inherits all configuration from the core module — no separate admin screens are needed.

---

## 2. Button States

### Default

The button displays "Export Cart" and is ready to click.

### Loading

While the CSV is being generated and downloaded, the button text changes to "Exporting..." and the button is disabled. The `x-cloak` directive ensures this state is hidden until Alpine.js initialises.

### Success

After the download completes, the button text changes to "Downloaded" for 2 seconds, then automatically reverts to "Export Cart".

### Error

If the export fails (network error, server error, or 30-second timeout), an inline error message appears below the button in red text with `role="alert"` for screen reader accessibility.

---

## 3. Layout Behaviour

### With BlueFairy_CartProductsHyva enabled

- The Luma export block (`bluefairy.cartproducts.export`) is **removed** via `remove="true"`.
- The Hyva export block (`bluefairy.cartproducts.hyva.export`) is **injected** into `checkout.cart.form`.
- Only one export button renders on the page.

### With BlueFairy_CartProductsHyva disabled

- The Luma export block renders as the fallback.
- No Alpine.js component is loaded.

### ViewModel gate

The `ExportButton::canExport()` method controls rendering:

| Condition | Result |
|-----------|--------|
| Feature enabled + logged-in customer | Button renders |
| Feature enabled + guest + guest export allowed | Button renders |
| Feature enabled + guest + guest export denied | Nothing renders |
| Feature disabled | Nothing renders |
