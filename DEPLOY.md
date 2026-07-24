# Deploying to Apache (`/var/www/localhost/htdocs`)

This app **is** its own document root: the repository contents (`index.php`,
`product.php`, `admin/`, `api/`, `assets/`, plus `bootstrap.php`, `wp-shims.php`,
`src/`, `data/`) go directly into the docroot and are served at the **site root**.
No database, no Composer, no non-core PHP extensions — just PHP 8.x.

The target docroot here is `/var/www/localhost/htdocs`.

---

## 1. Put the code in the docroot (without the `.git` folder)

Clone somewhere temporary, then copy the files into the docroot. Excluding
`.git` keeps your repository history off the web server.

```bash
git clone https://github.com/eduardofrank/php-sfc.git /tmp/php-sfc-build

sudo rsync -a --exclude='.git' /tmp/php-sfc-build/ /var/www/localhost/htdocs/
```

The tracked `data/config/options.json` (your prices) ships with the clone, so
the calculator has its price tables immediately.

## 2. Make the runtime directories writable by the web server user

On Alpine the Apache user is **`apache`**; on Debian/Ubuntu it is **`www-data`**
(check with `ps -o user= -C httpd` or `ps -o user= -C apache2`). The web server
writes saved quotes and the price config here:

```bash
sudo chown -R apache:apache /var/www/localhost/htdocs/data
sudo find /var/www/localhost/htdocs/data -type d -exec chmod 775 {} \;
```

Everything outside `data/` can stay read-only to the web user.

## 3. Set the admin password

The password hash is intentionally **not** in the repo. Create it on the server:

```bash
cd /var/www/localhost/htdocs
php bin/set-admin-password.php 'your-strong-password'
```

This writes `data/config/admin-password.php` (git-ignored, and an ABSPATH-guarded
PHP file that is never served as text). Log in at `/admin/login.php`.
Alternatively, set `SFC_ADMIN_PASSWORD_HASH` in the Apache/PHP environment.

## 4. Apache: allow the `.htaccess` files to take effect

The shipped `.htaccess` files harden the site (no directory listings, block
hidden files, and deny web access to `data/`, `src/`, `bin/`). They only work if
the docroot allows overrides. In your vhost or server config:

```apache
DocumentRoot /var/www/localhost/htdocs
DirectoryIndex index.php

<Directory /var/www/localhost/htdocs>
    AllowOverride All
    Require all granted
</Directory>
```

Then reload Apache (`sudo rc-service apache2 reload` on Alpine, or
`sudo systemctl reload apache2`). Make sure `mod_php` (or PHP-FPM) is active so
`.php` files execute; `mod_rewrite` is optional (only used for extra hardening).

Verify the denies work — these should all return **403** (or empty), not the file
contents:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://your-host/data/config/options.json
curl -s -o /dev/null -w '%{http_code}\n' http://your-host/src/app-helpers.php
curl -s -o /dev/null -w '%{http_code}\n' http://your-host/bin/seed-config.php
```

## 5. HTTPS (recommended)

Saved-quote share links and the admin session cookie should travel over TLS:

```bash
sudo certbot --apache -d your-host
```

---

## Serve at the site root, not a subdirectory

Some URLs are absolute (`/assets/...`, `/api/index.php`, the admin links), so the
app must be reachable at the **root** of a host or subdomain
(`https://calc.example.com/`), which the `/var/www/localhost/htdocs` docroot
gives you. Running it under a path like `/calc/` would break those absolute
paths.

## Updating a live install

Re-run the rsync from step 1, but **protect the runtime data** so an update does
not wipe saved quotes or the admin password:

```bash
git -C /tmp/php-sfc-build pull

sudo rsync -a --delete \
  --exclude='.git' \
  --exclude='data/quotes/' \
  --exclude='data/config/admin-password.php' \
  /tmp/php-sfc-build/ /var/www/localhost/htdocs/
```

**Prices and updates:** `data/config/options.json` is tracked in git. If you keep
maintaining prices *in git* (edit + commit, or edit the file and commit), a
redeploy correctly carries them. If instead you edit prices through the **admin
UI on the live server**, add `--exclude='data/config/options.json'` to the rsync
above (or commit the server's copy back to git) so an update does not revert your
live prices.
