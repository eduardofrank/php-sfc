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

Clone somewhere temporary, then copy the files into the project directory.
Excluding `.git` keeps your repository history off the web server.

```bash
git clone https://github.com/eduardofrank/php-sfc.git /tmp/php-sfc-build

mkdir -p /var/www/localhost/htdocs/php-sfc
rsync -a --exclude='.git' /tmp/php-sfc-build/ /var/www/localhost/htdocs/php-sfc/
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
php bin/db-migrate.php        # -> "Tables: sfc_clients, sfc_quote_counters, sfc_quote_items, sfc_quotes"
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

## 6. HTTPS (recommended)

Saved-quote share links and the admin session cookie should travel over TLS. On
a shared host this is usually managed for you; otherwise `certbot --apache`.

---

## Updating a live install

Re-run the sync, but **protect the runtime data** so an update does not wipe
saved quotes or the admin password:

```bash
git -C /tmp/php-sfc-build pull

rsync -a --delete \
  --exclude='.git' \
  --exclude='data/quotes/' \
  --exclude='data/config/admin-password.php' \
  --exclude='data/config/db.php' \
  /tmp/php-sfc-build/ /var/www/localhost/htdocs/php-sfc/

# Apply any new schema (idempotent).
php /var/www/localhost/htdocs/php-sfc/bin/db-migrate.php
```

**Prices and updates:** `data/config/options.json` is tracked in git. If you
maintain prices *in git* (edit + commit), a redeploy correctly carries them. If
instead you edit prices through the **admin UI on the live server**, add
`--exclude='data/config/options.json'` to the rsync above (or commit the
server's copy back to git) so an update does not revert your live prices.

## Base path

The app derives its URL prefix from the request, so `/php-sfc/` works with no
configuration. For unusual setups (a reverse proxy or Apache `Alias` that
rewrites the path so it can't be inferred), set it explicitly in the environment:

```apache
SetEnv SFC_BASE_PATH /php-sfc
```

Use an empty value (`SetEnv SFC_BASE_PATH ""`) or `/` to force site-root mode.
