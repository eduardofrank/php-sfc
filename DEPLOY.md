# Deploying to Apache (shared server, project subdirectory)

The whole app lives in one directory (`index.php`, `products.php`, `product.php`,
`quote.php`, `admin/`, `api/`, `assets/`, plus `bootstrap.php`, `wp-shims.php`,
`src/`, `data/`). No Composer, just PHP 8.x with the `pdo_pgsql` extension. Saved quotes are stored in
**PostgreSQL** (step 4); the calculator and pricing work without it — only the
Save/quote-tracking feature needs the database.

It works served **at the site root** *or* **from a subdirectory** — the base
path (e.g. `/php-sfc`) is detected automatically, so every asset, API call, and
share link resolves correctly either way. See "Base path" at the end if you need
to override it.

These instructions use the real target: the code in
`/var/www/localhost/htdocs/php-sfc`, served at `http://your-host/php-sfc/`.

---

## 1. Put the code in its subdirectory (without the `.git` folder)

Clone to `/var/tmp` (a durable scratch dir), then copy the files into the project
directory. Excluding `.git` keeps your repository history off the web server. Use
`/var/tmp`, **not** `/tmp` — on many systems `/tmp` is a tmpfs that is wiped on
every reboot, which silently deletes the build clone between deploys.

```bash
git clone https://github.com/eduardofrank/php-sfc.git /var/tmp/php-sfc-build

mkdir -p /var/www/localhost/htdocs/php-sfc
rsync -a --exclude='.git' /var/tmp/php-sfc-build/ /var/www/localhost/htdocs/php-sfc/
```

The tracked `data/config/options.json` (your prices) ships with the clone, so
the calculator has its price tables immediately.

## 2. Make the runtime directories writable by the web server user

On Alpine the Apache user is **`apache`**; on Debian/Ubuntu it is **`www-data`**
(check with `ps -o user= -C httpd` or `ps -o user= -C apache2`). The server
writes saved quotes and the price config under `data/`:

```bash
chown -R apache:apache /var/www/localhost/htdocs/php-sfc/data
find /var/www/localhost/htdocs/php-sfc/data -type d -exec chmod 775 {} \;
```

Everything outside `data/` can stay read-only to the web user.

## 3. Set the admin password

The password hash is intentionally **not** in the repo. Create it on the server:

```bash
cd /var/www/localhost/htdocs/php-sfc
php bin/set-admin-password.php 'your-strong-password'
```

This writes `data/config/admin-password.php` (git-ignored, and an ABSPATH-guarded
PHP file that is never served as text). Log in at
`http://your-host/php-sfc/admin/login.php`. Alternatively, set
`SFC_ADMIN_PASSWORD_HASH` in the Apache/PHP environment.

## 4. PostgreSQL (quote database)

Saved quotes, clients, and quote numbers live in PostgreSQL. Ensure PHP has
`pdo_pgsql` (`php -m | grep pdo_pgsql`), then create a database and role:

```bash
sudo -u postgres psql -c "CREATE ROLE sheetfedcalc LOGIN PASSWORD 'a-strong-db-password';"
sudo -u postgres psql -c "CREATE DATABASE sheetfedcalc OWNER sheetfedcalc;"
```

Tell the app how to connect — either environment variables (preferred) in the
vhost/PHP env:

```apache
SetEnv SFC_DB_HOST 127.0.0.1
SetEnv SFC_DB_PORT 5432
SetEnv SFC_DB_NAME sheetfedcalc
SetEnv SFC_DB_USER sheetfedcalc
SetEnv SFC_DB_PASS a-strong-db-password
```

…or a gitignored, ABSPATH-guarded file `data/config/db.php`:

```php
<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
    'host' => '127.0.0.1', 'port' => '5432', 'name' => 'sheetfedcalc',
    'user' => 'sheetfedcalc', 'password' => 'a-strong-db-password',
);
```

Create the schema (idempotent — safe to re-run on every deploy):

```bash
cd /var/www/localhost/htdocs/php-sfc
php bin/db-migrate.php        # -> "Tables: sfc_clients, sfc_exchange_rates, sfc_quote_counters, sfc_quote_items, sfc_quotes"
```

## 5. Make sure `.htaccess` overrides are allowed

The shipped `.htaccess` files harden the site (no directory listings, block
hidden files like `.git`, and deny web access to `data/`, `src/`, `bin/`). They
only take effect if the server permits overrides for the docroot — typically
`AllowOverride All`, which most shared Apache hosts already set. You usually
don't control the vhost on a shared box; if in doubt, ask the host whether
`AllowOverride All` is on for `/var/www/localhost/htdocs`.

Verify the denies work — these should return **403** (or empty), never the file
contents:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://your-host/php-sfc/data/config/options.json
curl -s -o /dev/null -w '%{http_code}\n' http://your-host/php-sfc/src/app-helpers.php
curl -s -o /dev/null -w '%{http_code}\n' http://your-host/php-sfc/bin/seed-config.php
```

If any returns **200 with contents**, `.htaccess` is being ignored (overrides
off). If the whole site returns **500** right after deploy, the host allows only
*some* overrides and rejects a directive — remove the root `.htaccess` (the
per-directory `data/.htaccess`, `src/.htaccess`, `bin/.htaccess` deny files use
only `Require`, which is the most widely allowed).

## 6. Daily BCV exchange rate (VES) — cron

Quotes show the bolívar amount alongside USD, using the daily **BCV** rate stored in
`sfc_exchange_rates`. A Python script fetches it each morning; PHP reads the latest row.
(Without a rate, the app simply shows USD only — nothing breaks.)

```bash
# one-time: install the fetcher's deps (use your distro's python)
pip install requests beautifulsoup4 psycopg2-binary

# test it once (DB creds via env, same names PHP uses):
SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc SFC_DB_PASS='...' \
  python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-bcv-rate.py
# -> "fetch-bcv-rate: 2026-08-05 = Bs. 40.2500/USD (bcv-scrape)"
```

Add a cron entry to run it before business hours (America/Caracas). It carries its own env, since
cron does not inherit Apache's:

```cron
# min hour dom mon dow  (server clock; adjust to hit ~07:00 Caracas)
0 7 * * *  SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc SFC_DB_PASS='...' /usr/bin/python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-bcv-rate.py >> /var/log/sfc-bcv.log 2>&1
```

The script tries the BCV site first, falls back to a maintained JSON API, and exits non-zero on
failure (leaving the previous day's rate in place). If a morning run fails, set the rate manually in
**/admin → Tasa de cambio**. Staff can also re-stamp a saved quote to the current rate from
`/admin/quotes.php` ("Actualizar tasa") without rebuilding it.

## 7. HTTPS (recommended)

Saved-quote share links and the admin session cookie should travel over TLS. On
a shared host this is usually managed for you; otherwise `certbot --apache`.

---

## Updating a live install

Sync the code (protecting runtime data), apply any schema, then **reload PHP** so
the new code actually runs:

```bash
git -C /var/tmp/php-sfc-build pull

rsync -a --delete \
  --exclude='.git' \
  --exclude='data/quotes/' \
  --exclude='data/config/admin-password.php' \
  --exclude='data/config/db.php' \
  --exclude='data/config/options.json' \
  /var/tmp/php-sfc-build/ /var/www/localhost/htdocs/php-sfc/

# Apply any new schema (idempotent; a no-op when nothing changed).
php /var/www/localhost/htdocs/php-sfc/bin/db-migrate.php

# Reload PHP so opcache drops the old bytecode (see below).
systemctl restart apache2        # mod_php / mod_fcgid: PHP runs inside Apache
# stand-alone php-fpm instead:   systemctl restart php-fpm
```

### Code-only deploys (no migration)

Most changes — new products, calculator steps, labels/copy, pricing logic, JS/CSS
— touch **no database schema**, so `db-migrate.php` is a **no-op** and the deploy
is just: **pull → rsync → reload PHP**. The migrate step is always safe to run
(idempotent), so leaving it in the routine costs nothing; only skip it if you want
the shortest path and know the change added no `CREATE TABLE`/`ALTER TABLE` in
`bin/db-migrate.php`.

Run the migrate step when a change **does** alter the schema — the schema block in
`bin/db-migrate.php` changed (a new table or column), which so far means the saved-
quote tables and the `sfc_exchange_rates` / `ves_rate` / `total_ves` additions.
When in doubt, run it: it never harms an up-to-date database.

Either way the **opcache reload is mandatory** — a code-only deploy that skips the
Apache restart will keep serving the old bytecode.

### Reload PHP after every deploy (opcache)

PHP caches compiled bytecode in **opcache**; copying new files does not refresh
it, so after an `rsync` the server keeps running the *old* code until PHP is
reloaded. This is the #1 cause of "the deploy didn't take" (old prices/behavior
even though the files on disk are new). Two ways to handle it:

- **Reload on each deploy** (above). With `mod_php` or `mod_fcgid` PHP lives
  inside Apache, so `systemctl restart apache2` clears it; only a stand-alone
  `php-fpm` needs its own restart. Find the unit with
  `systemctl list-unit-files | grep -iE 'php|fpm'` (note: `list-units` only shows
  *running* units, so a stopped/absent match there does not mean it isn't there).
  If PHP is `mod_php`/`mod_fcgid` there is no `php-fpm` service at all — restart
  Apache. Confirm which you have with `apache2ctl -M | grep -Ei 'php|fcgid|proxy_fcgi'`.
- **Auto-detect changes** (no restart step): in the *web* `php.ini`
  (Gentoo: `/etc/php/apache2-php8.x/php.ini` or `.../fpm-php8.x/php.ini`) set
  ```ini
  opcache.validate_timestamps = 1
  opcache.revalidate_freq = 0
  ```
  and restart PHP once. After that every deploy is picked up automatically.

To flush opcache without any service (any SAPI), hit `opcache_reset()` through the
web once, then delete the file:
```bash
printf '<?php opcache_reset();' > /var/www/localhost/htdocs/php-sfc/_oc.php
curl -s http://your-host/php-sfc/_oc.php && rm -f /var/www/localhost/htdocs/php-sfc/_oc.php
```

### Prices and rates ownership

`data/config/options.json` holds **all price tables and the service rates**
(cutting/creasing/stapling %, turnaround, etc.). The update sync above
**excludes** it, so deploys never overwrite what you set in the live **/admin** —
i.e. you manage pricing on the server. For that to work:

- keep `data/config/` writable by the web user (see step 2), or admin saves fail
  and the value silently reverts. The admin now shows a "no se pudo guardar"
  error when it cannot write — if you see that, fix the permissions;
- values are **per field**: a stray number in one row (e.g. cutting = 15 instead
  of 10) only affects that service. Business cards are only *cut*; folded
  brochures are *cut + creased*; booklets *stapled*.

To instead manage pricing **in git**, drop the `options.json` exclude and edit +
commit the file in the repo; then don't change prices on the server.

## Base path

The app derives its URL prefix from the request, so `/php-sfc/` works with no
configuration. For unusual setups (a reverse proxy or Apache `Alias` that
rewrites the path so it can't be inferred), set it explicitly in the environment:

```apache
SetEnv SFC_BASE_PATH /php-sfc
```

Use an empty value (`SetEnv SFC_BASE_PATH ""`) or `/` to force site-root mode.
