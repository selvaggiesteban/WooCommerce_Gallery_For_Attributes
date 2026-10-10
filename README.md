# WooCommerce Gallery For Attributes

Adds a **custom featured image gallery to each product variation** in WooCommerce. Natively, WooCommerce only lets you set a single variation image — this plugin lets you attach an entire gallery (multiple images) per variation and swaps the frontend product gallery automatically when the customer selects that variation.

## Features

- Per-variation image gallery managed with the WordPress Media Library.
- Frontend gallery swap on `found_variation` (and restore on `reset_data`).
- Compatible with the WooCommerce default gallery (flexslider + zoom + photoswipe).
- New variations added on the fly are handled (variation accordion clone).
- Lightweight: no external dependencies, vanilla jQuery + WP Media.

## Installation

1. Copy the `WooCommerce_Gallery_For_Attributes/` folder to `wp-content/plugins/`.
2. Activate **WooCommerce Gallery For Attributes** in *Plugins*.
3. Edit a variable product → **Variations** → open each variation → **Add gallery images**.

## Usage

1. In each variation, click **Add gallery images**, pick one or more images from the Media Library.
2. Save the product.
3. On the product page, selecting that variation swaps the main gallery to the chosen images. Clearing the variation restores the parent gallery.

## Technical notes

| Hook / meta | Purpose |
|---|---|
| `woocommerce_product_after_variable_attributes` | Renders the gallery field in each variation |
| `woocommerce_save_product_variation` | Saves IDs to `_wc_gfa_gallery` meta (comma-separated) |
| `woocommerce_available_variation` | Exposes `wc_gfa_gallery` (full-size URLs) to frontend JS |

## Requirements

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+

## License

GPL-2.0-or-later
