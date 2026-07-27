# Lab Gráfico — standalone PHP calculator

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

The app is **quote-centric**: the primary object is a Quote (client, number, line
items, totals); products are how you add items to it.

| Path | Role |
|------|------|
| `index.php` | **Quote builder** (home) — the current draft, client + title/notes, Finalize |
| `products.php` | Product picker — "add an item" (the ten-product grid) |
| `product.php` | Per-product calculator; primary action **Add to quote** (`?from=&item=` seeds a saved item) |
| `quote.php` | Read-only, printable **quote document** (`?token=`) |
| `api/index.php` | JSON router: `sfc_calculate_product_quote`, `sfc_quote_add_item`/`remove_item`/`clear`/`draft`, `sfc_finalize_quote`, `sfc_client_names` |
| `src/quote-draft.php` | Server-session draft basket + `sfc_draft_finalize()` |
| `src/quotes-repo.php` | Clients + quote header/items data access |
| `src/db.php` | PostgreSQL PDO connection (env → `data/config/db.php` → DDEV defaults) |
| `src/includes/` | Pricing/config/steps engine, ported verbatim from the plugin |
| `wp-shims.php` | Minimal WordPress primitives (`WP_Error`, `sanitize_key`, `get_option`→store, …) |
| `admin/` | Password-protected price maintenance + quote browser |
| `assets/js/` | Declarative calculator front-end (jQuery, ported) |

The engine is ported behind a shim rather than rewritten, so quotes are
penny-identical to the plugin. `get_option()` reads the JSON options store
(default price tables, rates, sheet specs), so pricing needs no database.

## Compound quotes (PostgreSQL)

A quote is a **document with one or more line items** — the same product more than
once and/or different products — under one client, one number, one grand total.

Flow: **Add to quote** on any calculator collects items into a server-session
**draft** (the persistent draft bar shows the running count + total); the **builder
home** captures the **client** (reusable, case-insensitive) plus optional **title +
notes** and **Finalizes** into a numbered quote. Each item's price is **frozen at
finalize** (`YYYY-NNNN`, per-year number), so the document is a fixed, dated quote.

- Schema: header `sfc_quotes` + `sfc_quote_items` + `sfc_clients` +
  `sfc_quote_counters`, created by `bin/db-migrate.php` (idempotent; backfills any
  legacy single quotes to one item).
- Connection resolves from `SFC_DB_*` env → gitignored `data/config/db.php` →
  DDEV defaults (`src/db.php`). Local dev uses DDEV's bundled Postgres 15.
- `quote.php?token=` is the shareable/printable document; `/admin/quotes.php`
  browses, searches, and deletes quotes. The server re-prices on add and finalize
  and never trusts a client-supplied total.

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

DDEV, PHP 8.4, nginx-fpm, docroot `public/`, PostgreSQL 15.

```bash
ddev start
ddev exec php public/bin/db-migrate.php    # create the quote tables
# https://php-sfc.ddev.site/
```

The DDEV project uses PostgreSQL (`database.type: postgres` in `.ddev/config.yaml`);
the app connects with DDEV's default credentials automatically (`src/db.php`).

### Verified quotes (defaults)

| Product | Config | Total |
|---------|--------|-------|
| Business cards | 90×50, ×100, 4x0, matte laminate | $15.80 (base print $14.55) |
| Posters | 450×310, ×5, 150 g | $13.50 |
| Letterhead | carta, ×100 | $129.50 |
| Album | 215.9×279.4, ×2, 20 pp | with $25/album binding |
| Catalog | 215.9×139.7, ×10, 8 inner pp | $49.40 |
| Die-cut stickers | Ø80, ×100, lithosticker | $23.63 |
