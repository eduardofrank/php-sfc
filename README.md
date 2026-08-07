# Lab Gráfico — standalone PHP calculator

A web-based print-pricing tool for ten sheet-fed products. You build a **quote**
by adding one or more products — each configured in its own calculator — then
finalize it into a numbered, client-ready document. Pricing runs server-side in
PHP, ported faithfully from the `sheet-fed-calc` WordPress/WooCommerce plugin
(minus the WooCommerce cart/checkout and artwork; the price/quote admin here is
this app's own).

Everything is in millimeters and USD. UI language is Spanish; the interface is
branded **Lab Gráfico**.

## How it works

Pricing is computed off **press-sheet counts**, not piece counts:

```
imposition → tiered per-sheet price table → lamination / die-cut / job services
           → turnaround surcharge → trade discount
```

Lamination is billed per press-sheet side; **job services are a percentage of the
print cost** (lamination is a separate line). Job services are **per-product**,
set via each product's `jobServices`: business cards are only cut, folded
brochures are cut + creased, booklets stapled.

**Die-cutting** is configured the same way — add `die_cutting` to a product's
`jobServices` and any product gets it — but it is priced through the **tiered
die-cut rate system** (a % of print cost that steps down by total press-sheet
count: 25% ≤50 / 20% 51–100 / 15% >100, editable in `/admin`), not a flat
per-service rate. The shape picker on die-cut stickers (`dieCutShapes`) is a
separate, UI-only concern.

Three pipelines, dispatched by `sfc_calculate_product_quote()`:

- **flat** — letterhead, business cards, posters, postcards, flyers, rectangular
  stickers, die-cut stickers, the six folded-brochure variants, and **Producto
  Avanzado**
- **booklet** — catalogs & magazines (saddle-stitch, inner + cover runs)
- **album** — hardcover albums (duplex sheets + per-album binding fee)

The browser never prices: it POSTs the form state to `api/index.php` and renders
the returned quote. The server is the single source of truth (saving a quote
re-prices it and never trusts a client-supplied total).

### Producto Avanzado

A non-boxed calculator (`producto-avanzado`) for pricing *any* product. The page
opens with a **Tipo de proyecto** radio: **Plano** or **Editorial**.

**What it does**

- **Plano** — a superset flat calculator with everything a flat product offers:
  - **Dimensiones**: 140×100, Media Carta, Carta, Tabloide, Tamaño Personalizado
    (with custom W/H)
  - **Cantidad** entry field
  - **Peso del Papel**: Bond, 115/150/200/250/300 g, Lithosticker, Vinil
  - **Acabado del Papel** (Mate/Brillante) — shown *only* for the coated weights,
    hidden for Bond/Lithosticker/Vinil (reuses the existing surface-gating)
  - **Caras Impresas** — one/two sides, with **two-sided automatically removed for
    Lithosticker/Vinil**
  - **Servicios** — real checkboxes (Corte/Signado/Grapado/Troquel), check all that
    apply
  - **Acabado** (Ninguno / Laminado Mate / Brillante) and **Tiempo de entrega**
- **Editorial** — routes to the existing **Catálogos y revistas** and **Álbum**
  calculators (different pipelines), so nothing is duplicated.

**How it's kept low-risk**

- Pricing rides the **existing engine unmodified**: paper weights map onto existing
  tables; services are read from state via `sfc_resolve_effective_job_services()`;
  die-cutting (Troquel) still uses the tiered rate system.
- The one genuinely new control — a checkbox step (`type: checkboxes`, array-valued
  state `services`, keys `cutting/creasing/stapling/die_cutting`) plus per-paper
  sides (`printModesByPaper` + `optionsByField`) — is guarded so **every existing
  product is byte-for-byte unchanged** (verified by regression on posters and
  die-cut stickers).

**Verification (DDEV, headless DevTools)**

Selecting Carta + 200 g revealed **Acabado del papel** and **Caras impresas**;
switching to Vinil collapsed sides to one option and hid surface; checking Corte +
Troquel produced a live **$186.98** (cutting $13.85 + tiered die-cut $34.63) with
the **Bs. 141.489,32** VES line; the Editorial toggle hid Plano and linked out.

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
| `src/app-helpers.php` | Landing/picker list, share + item URLs, JSON envelopes |
| `admin/` | Password-protected price maintenance + quote browser |
| `assets/js/` | Declarative calculator front-end (jQuery, ported) |
| `assets/quote-ui.css` + `assets/fonts/` | Lab Gráfico visual layer (self-hosted Space Grotesk/Mono) for builder, picker, calculator, quote document |

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

## Dual currency (USD + VES)

Every subtotal is shown in **bolívares** next to USD, using the daily **BCV** rate.
Pricing stays in USD (the source of truth); VES is a display conversion.

- The rate lives in `sfc_exchange_rates` (one row/day), fetched each morning by
  `bin/fetch-bcv-rate.py` (cron, BCV scrape + JSON-API fallback) or set manually in
  `/admin → Tasa de cambio`. `src/exchange-rates.php` reads the latest rate and
  formats Bs. (es-VE, `Bs. 3.481,63`).
- A finalized quote **freezes** its issue-time rate (`sfc_quotes.ves_rate`/
  `total_ves`), so a sent quote's Bs. total is fixed — mirroring the frozen USD
  prices. Staff can **re-stamp a quote to the current rate in place** (same number,
  same USD) from `/admin/quotes.php` or the document (when logged in) via
  `sfc_quotes_update_rate()`.
- No rate yet → the app shows USD only (VES hidden), never an error.

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

Totals below use the shipped default rates, including the **cutting** job service
(10% of print) that applies to flat products — so `Total` is above the base print.

| Product | Config | Total |
|---------|--------|-------|
| Business cards | 90×50, ×100, 4x0, matte laminate | $17.26 (print $14.55 + cut $1.46 + laminate $1.25) |
| Posters | 450×310, ×5, 150 g | $14.85 (print $13.50 + cut $1.35) |
| Letterhead | carta, ×100 | $142.45 (print $129.50 + cut $12.95) |
| Album | 215.9×279.4, ×2, 20 pp | with $25/album binding |
| Catalog | 215.9×139.7, ×10, 8 inner pp | $49.40 |
| Die-cut stickers | Ø80, ×100, lithosticker | $25.52 (print $18.90 + die-cut $4.73 + cut $1.89) |
