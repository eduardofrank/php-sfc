# Sheet Fed Calc — standalone PHP calculator

A web-based print-pricing calculator for ten sheet-fed products. The user first
picks a product; the app then forks to that product's calculator and prices the
job server-side in PHP. It is a faithful standalone reimplementation of the
`sheet-fed-calc` WordPress/WooCommerce plugin, minus the WooCommerce, admin, and
artwork machinery.

Everything is in millimeters and USD. UI language is Spanish.

## How it works

Pricing is computed off **press-sheet counts**, not piece counts:

```
imposition → tiered per-sheet price table → lamination / die-cut / job services
           → turnaround surcharge → trade discount
```

Three pipelines, dispatched by `sfc_calculate_product_quote()`:

- **flat** — letterhead, business cards, posters, postcards, flyers, rectangular
  stickers, die-cut stickers, and the six folded-brochure variants
- **booklet** — catalogs & magazines (saddle-stitch, inner + cover runs)
- **album** — hardcover albums (duplex sheets + per-album binding fee)

The browser never prices: it POSTs the form state to `api/index.php` and renders
the returned quote. The server is the single source of truth (saving a quote
re-prices it and never trusts a client-supplied total).

## Architecture

| Path | Role |
|------|------|
| `index.php` | Landing page — the ten products as the first choice |
| `product.php` | Per-product calculator + folded-brochure hub (`?product=`, `?fold=`, `?quote=`) |
| `api/index.php` | JSON router: `sfc_calculate_product_quote`, `sfc_save_quote` |
| `bootstrap.php` | Constants + shim + engine load order |
| `wp-shims.php` | Minimal WordPress primitives (`WP_Error`, `sanitize_key`, `get_option`→defaults, …) |
| `src/includes/` | Pricing/config/steps engine, ported verbatim from the plugin |
| `src/app-helpers.php` | Quote seeding, landing list, file-based save/share, JSON envelopes |
| `assets/calculator.css` | Calculator styles (ported verbatim — `.sfc` dark theme) |
| `assets/app.css` | Page shell styles (same palette) |
| `assets/js/` | Declarative calculator front-end (jQuery, ported) |
| `data/quotes/` | Saved shareable quotes (JSON, gitignored) |

The engine is ported behind a shim rather than rewritten, so quotes are
penny-identical to the plugin. `get_option()` returns the seeded defaults
(default price tables, rates, sheet specs), so there is no database.

## Maintaining prices

All price-affecting values (price tables, lamination / die-cut / turnaround /
job-service rates, sheet specs, imposition gap, quantity tiers, fulfillment) are
read through `get_option()`, which is backed by a JSON store at
**`data/config/options.json`** (tracked in git — price changes are versioned).
Any key absent from the file falls back to the code default, so deleting the
file restores defaults.

Two ways to edit:

1. **Admin UI** — a password-protected page at **`/admin`**. Every form saves
   through the ported validators (`sfc_sanitize_price_tables()`, …), so an
   invalid entry is rejected, not stored. Set the password first:

   ```bash
   ddev exec php public/bin/set-admin-password.php 'your-strong-password'
   ```

   The hash is written to `data/config/admin-password.php` — gitignored and an
   `ABSPATH`-guarded PHP file, so it is never served as static text. (You can
   instead set `SFC_ADMIN_PASSWORD_HASH` in the web environment.)

2. **Edit the file** — change `data/config/options.json` by hand. It is
   re-validated on load. Regenerate it from the code defaults any time with:

   ```bash
   php public/bin/seed-config.php
   ```

After either, commit `data/config/options.json` to version the change.

## Development

DDEV, PHP 8.4, nginx-fpm, docroot `public/`.

```bash
ddev start
# https://php-sfc.ddev.site/
```

### Verified quotes (defaults)

| Product | Config | Total |
|---------|--------|-------|
| Business cards | 90×50, ×100, 4x0, matte laminate | $15.80 (base print $14.55) |
| Posters | 450×310, ×5, 150 g | $13.50 |
| Letterhead | carta, ×100 | $129.50 |
| Album | 215.9×279.4, ×2, 20 pp | with $25/album binding |
| Catalog | 215.9×139.7, ×10, 8 inner pp | $49.40 |
| Die-cut stickers | Ø80, ×100, lithosticker | $23.63 |
