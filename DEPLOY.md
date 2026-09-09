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
`/var/www/localhost/htdocs/php-sfc`, served at `http://your-host/php-sfc/`. The
reference server runs **Gentoo with systemd**; §6 and §7 assume `systemctl` and a
Python virtualenv, so adapt those two if you deploy elsewhere.

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

This host runs PHP as **`apache`**. The server writes saved quotes and the
price config under `data/`, so that tree must be owned by `apache` (a `644`
file owned by root still cannot be saved from `/admin`):

```bash
chown -R apache:apache /var/www/localhost/htdocs/php-sfc/data
find /var/www/localhost/htdocs/php-sfc/data -type d -exec chmod 775 {} \;
```

Re-run the `chown` after every deploy — `rsync` as root resets `data/`
ownership even when `options.json` is excluded. Everything outside `data/`
can stay read-only to the web user.

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
php bin/db-migrate.php        # -> "Tables: sfc_clients, sfc_exchange_rates, sfc_quote_counters, sfc_quote_items, sfc_quotes, sfc_usdt_rates"
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

## 6. Exchange rates (VES) — cron

Quotes show the bolívar amount alongside USD, computed as **USD × tasa BCV × factor**,
where the factor is **tasa USDT ÷ tasa BCV**. That needs two fetchers:

| Rate | Table | Script | Cadence |
|---|---|---|---|
| BCV (official) | `sfc_exchange_rates` (one row/day) | `bin/fetch-bcv-rate.py` | daily, before business hours |
| USDT (P2P) | `sfc_usdt_rates` (time series) | `bin/fetch-usdt-rate.py` | hourly |

If **either** rate is missing the app shows USD only — nothing breaks, but no bolívares
appear until both are present. Both scripts exit non-zero on failure and leave the
previous value in place.

### 6a. Python environment (do this first)

Both fetchers need `requests`, `beautifulsoup4` and `psycopg2`. Install them into a
dedicated virtualenv rather than system-wide — current distros mark the system Python
as externally managed (PEP 668) and a stray `pip install` there can fight the package
manager:

```bash
python3 -m venv /opt/sfc-venv
/opt/sfc-venv/bin/pip install requests beautifulsoup4 psycopg2-binary
/opt/sfc-venv/bin/python3 -c 'import requests, bs4, psycopg2, urllib3; print("deps OK")'
```

**Everything below — both cron entries and the systemd unit in §7b — must invoke
`/opt/sfc-venv/bin/python3`, never `/usr/bin/python3`.** The system interpreter does
not have these packages.

> **Upgrading the system Python breaks this venv, and the failure is quiet.** A venv
> is bound to the minor version that created it, so a distro upgrade (e.g. 3.13 → 3.14)
> leaves it pointing at an interpreter that is gone. The fetches then stop while the
> app happily keeps showing the **last stored rate** — no error page, no visibly broken
> quote, just prices drifting further from reality every day. This exact failure went
> unnoticed for four weeks on the reference server. After any Python upgrade:
>
> ```bash
> /opt/sfc-venv/bin/python3 -c 'import requests, bs4, psycopg2' || {
>   python3 -m venv --clear /opt/sfc-venv
>   /opt/sfc-venv/bin/pip install requests beautifulsoup4 psycopg2-binary
> }
> ```
>
> Better, monitor the data rather than the interpreter — see "Rate staleness check"
> at the end of §6.

### 6b. Daily BCV rate

```bash
# test it once (deps from §6a; DB creds via env, same names PHP uses):
SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc SFC_DB_PASS='...' \
  /opt/sfc-venv/bin/python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-bcv-rate.py
# -> "fetch-bcv-rate: 2026-08-05 = Bs. 40.2500/USD (bcv-scrape)"
```

Add a cron entry to run it before business hours (America/Caracas). It carries its own env, since
cron does not inherit Apache's:

```cron
# min hour dom mon dow  (server clock; adjust to hit ~07:00 Caracas)
0 7 * * *  SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc SFC_DB_PASS='...' /opt/sfc-venv/bin/python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-bcv-rate.py >> /var/log/sfc-bcv.log 2>&1
```

The script tries the BCV site first, falls back to a maintained JSON API, and exits non-zero on
failure (leaving the previous day's rate in place). If a morning run fails, set the rate manually in
**/admin → Tasas de cambio**. Staff can also re-stamp a saved quote to current rates from
`/admin/quotes.php` ("Actualizar tasa") without rebuilding it.

> **On unreliable power, use the systemd timer in §7 instead of cron.** A plain cron
> job never runs if the machine was off at its scheduled time, so a morning outage
> would leave the daily BCV rate stale all day. The timer catches up missed runs and
> refreshes shortly after every boot. The hourly USDT job in §6c needs no equivalent:
> a missed run self-corrects at the top of the next hour.

### 6c. Hourly USDT rate

The P2P rate moves through the day, so it is sampled hourly. `bin/fetch-usdt-rate.py`
scrapes usdt.com.ve and stores the **highest** USDT price published there; the BCV
reference row on that page is excluded so it can never drag the maximum down. Each run
appends a row — the table is a history, and readers take the newest.

```bash
# same deps as the BCV fetcher; test it once:
SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc SFC_DB_PASS='...' \
  /opt/sfc-venv/bin/python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-usdt-rate.py
# -> "fetch-usdt-rate: 2026-09-09 17:47 = Bs. 965.5000/USDT (usdt.com.ve/Binance P2P)"
```

```cron
# every hour, on the hour
0 * * * *  SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc SFC_DB_PASS='...' /opt/sfc-venv/bin/python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-usdt-rate.py >> /var/log/sfc-usdt.log 2>&1
```

If the site's layout changes the script fails loudly rather than guessing — a wrong-but-
plausible rate would silently misprice every quote. On failure set the rate manually in
**/admin → Tasas de cambio → Tasa P2P (USDT)**; the newest sample wins.

Prune the history occasionally if you like (it is small — 24 rows/day):

```sql
DELETE FROM sfc_usdt_rates WHERE fetched_at < now() - interval '1 year';
```

### Rate staleness check

Both fetchers fail loudly, but nothing *reads* their exit code once cron has moved
on, and the app keeps serving the last stored rate indefinitely. Watch the data
instead — this prints a line only when something is actually stale, so it is safe to
run from cron and mail on output:

```bash
psql -U sheetfedcalc -h 127.0.0.1 -d sheetfedcalc -tA -c "
SELECT 'BCV rate is '  || (current_date - max(rate_date)) || ' day(s) old'
  FROM sfc_exchange_rates HAVING max(rate_date) < current_date - 1
UNION ALL
SELECT 'USDT rate is ' || round(extract(epoch FROM now() - max(fetched_at))/3600) || ' hour(s) old'
  FROM sfc_usdt_rates HAVING max(fetched_at) < now() - interval '6 hours';"
```

```cron
# 08:30 daily — mails you only if a rate has gone stale
30 8 * * *  psql -U sheetfedcalc -h 127.0.0.1 -d sheetfedcalc -tA -c "SELECT 'BCV rate is ' || (current_date - max(rate_date)) || ' day(s) old' FROM sfc_exchange_rates HAVING max(rate_date) < current_date - 1 UNION ALL SELECT 'USDT rate is ' || round(extract(epoch FROM now() - max(fetched_at))/3600) || ' hour(s) old' FROM sfc_usdt_rates HAVING max(fetched_at) < now() - interval '6 hours';"
```

## 7. Power-loss resilience (auto-recovery after reboot)

Frequent grid outages mean the server reboots often and uncleanly. The goal: when
power returns, the **site and the daily rate fetch come back with no human
intervention**. Three pieces — enable the services on boot, run the fetch from a
resilient timer, then verify.

### 7a. Auto-start the core services on boot

The site is down after every outage until Apache and PostgreSQL run. Enable them so
systemd starts them on boot (PostgreSQL's unit is versioned on Gentoo — find it
first):

```bash
sudo systemctl enable --now apache2
systemctl list-unit-files | grep -i postgres        # find the versioned unit(s)
sudo systemctl enable --now postgresql-18            # use the live unit you found

systemctl is-enabled apache2 postgresql-18           # -> enabled / enabled
```

PostgreSQL is **crash-safe** (it replays its write-ahead log on start), so an
unclean shutdown recovers on its own — enabling it just guarantees it starts. The
app also degrades gracefully: if the DB is briefly unavailable after boot, the
calculator still prices from `options.json` and the footer simply omits the rate —
never an error.

> **Retire any stale PostgreSQL cluster — this is a real reboot hazard.** After a
> major-version upgrade (e.g. 17 → 18) the old versioned unit is often left
> **`enabled` but broken** (its data dir was migrated to the new cluster, so it has
> nothing to serve). Two clusters can't both own port 5432, so on boot they *race*
> for it; if the dead one wins, the app connects to an empty cluster and quotes
> break until someone intervenes. Find out which cluster is real, then disable **and
> mask** the other so it can never contend:
>
> ```bash
> # which versions are installed/enabled, which is active, which owns 5432
> systemctl list-unit-files | grep -i postgres
> systemctl is-active postgresql-17 postgresql-18          # e.g. failed / active
> sudo ss -ltnp | grep :5432                               # the live cluster's pid
> # which cluster actually has the app database
> for p in 5432 5433; do echo -n "port $p: "; sudo -u postgres psql -p "$p" \
>   -tAc "SELECT datname FROM pg_database WHERE datname='sheetfedcalc'" 2>/dev/null; done
>
> # retire the stale one (example: 17 is the leftover; keep 18)
> sudo systemctl disable --now postgresql-17.service
> sudo systemctl mask postgresql-17.service                # -> symlinked to /dev/null
> sudo systemctl reset-failed postgresql-17.service
> ```
>
> Keep **only** the cluster that holds `sheetfedcalc` (the one your §4 connection
> settings target — `SetEnv SFC_DB_*` in the vhost, or `data/config/db.php` if you
> chose that route) enabled. `systemctl --failed` should be empty afterward.
>
> A related trap even with one cluster: PHP and the Python fetchers are configured
> **separately**, so they can end up on different ports. `SFC_DB_PORT` defaults to
> 5432 in the fetchers; if PHP is pointed somewhere else, migrations land in one
> database and rate rows in another. The symptom is a fetcher failing with
> `relation "sfc_usdt_rates" does not exist` right after a successful migration:
>
> ```bash
> for p in 5432 5433; do echo -n "port $p: "; psql -U sheetfedcalc -h 127.0.0.1 -p $p \
>   -d sheetfedcalc -tAc "SELECT count(*) FROM pg_tables WHERE tablename='sfc_usdt_rates'" 2>&1 | tail -1; done
> ```

### 7b. Fetch the rate from a systemd timer (survives missed runs)

(The unit below runs `/opt/sfc-venv/bin/python3` — the virtualenv from §6a. If you
skipped that, create it before enabling the timer.)

Replace the cron entry from §6. Remove the old line non-interactively (backs up the
crontab automatically):

```bash
sudo crontab -l | grep -v 'fetch-bcv-rate' | sudo crontab -
```

Then: 

Put the DB credentials in a root-only env file (keeps the password out of the unit):

```bash
sudo tee /etc/sfc-bcv.env >/dev/null <<'ENV'
SFC_DB_HOST=127.0.0.1
SFC_DB_PORT=5432
SFC_DB_NAME=sheetfedcalc
SFC_DB_USER=sheetfedcalc
SFC_DB_PASS=your-db-password
ENV
sudo chmod 600 /etc/sfc-bcv.env
```

Create the service — `/etc/systemd/system/sfc-bcv-rate.service`:

```ini
[Unit]
Description=Fetch daily BCV USD->VES rate
# Order after the network and the LIVE PostgreSQL unit — use the versioned name you
# kept in §7a (e.g. postgresql-18.service). No Wants= on network-online.target: that
# needs a wait-online unit to be meaningful, and the retry loop below already covers
# "network not up yet," so requiring it would only risk a boot delay.
After=network-online.target postgresql-18.service

[Service]
Type=oneshot
EnvironmentFile=/etc/sfc-bcv.env
# Retry up to 5×, 60s apart, so a flaky link right after an outage still lands the rate.
ExecStart=/bin/sh -c 'for i in 1 2 3 4 5; do /opt/sfc-venv/bin/python3 /var/www/localhost/htdocs/php-sfc/bin/fetch-bcv-rate.py && exit 0; sleep 60; done; exit 1'
```

Create the timer — `/etc/systemd/system/sfc-bcv-rate.timer`:

```ini
[Unit]
Description=Daily BCV rate fetch (persistent + on boot)

[Timer]
# 07:00 Caracas daily. The trailing timezone needs systemd >= 247; if yours is
# older, drop it and use the system-clock equivalent (e.g. 11:00:00 when on UTC).
OnCalendar=*-*-* 07:00:00 America/Caracas
# Refresh a couple minutes after every boot — covers power returning mid-morning.
OnBootSec=2min
# Run a job that was missed while the machine was off, as soon as it is back.
Persistent=true
Unit=sfc-bcv-rate.service

[Install]
WantedBy=timers.target
```

Enable and test:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now sfc-bcv-rate.timer
sudo systemctl start sfc-bcv-rate.service            # run once now
journalctl -u sfc-bcv-rate.service --no-pager -n 20  # -> "fetch-bcv-rate: … = Bs. …/USD"
systemctl list-timers sfc-bcv-rate.timer --no-pager  # shows next run + last run
```

Why a timer beats cron here: `Persistent=true` runs a missed daily fetch the moment
the machine is back, `OnBootSec` refreshes shortly after every reboot, and the
retry loop rides out a link that is briefly flaky after the power returns. A failed
run leaves the previous rate in place (stale but present), and you can always set it
by hand in **/admin → Tasa de cambio**.

### 7c. Verify recovery

After a test reboot (`sudo reboot`) — or the next real outage — nothing should need
touching:

```bash
curl -s -o /dev/null -w 'site %{http_code}\n' http://printanet.ddns.net/php-sfc/
curl -s "http://printanet.ddns.net/php-sfc/product.php?product=posters" | grep -A2 'class="app-footer"'
```

The site should answer **200** and the footer's "Cambio BCV de hoy" should show the
current rate.

> **Hardware complement:** a small UPS that lets the box shut down cleanly (or just
> ride out short dips) spares the filesystem and database repeated unclean
> power-offs. The software above recovers regardless, but a UPS reduces wear and the
> chance of a longer fsck on boot.

## 8. HTTPS (recommended)

Saved-quote share links and the admin session cookie should travel over TLS. On
a shared host this is usually managed for you; otherwise `certbot --apache`.

---

## 9. Going live from trial (reset to an empty database)

After a trial period the quotes, clients and numbering are usually throwaway. This
resets them so the first real quote is numbered `YYYY-0001`, without touching prices,
credentials or the schema.

**Order matters:** deploy the code and migrate *first*, so the schema is current
before you wipe.

```bash
# 0. Back up — this is the only way back.
pg_dump -U sheetfedcalc -h 127.0.0.1 sheetfedcalc > ~/sfc-trial-backup-$(date +%F).sql

# 1. Deploy the code (the "Updating a live install" rsync below, whose excludes
#    protect prices, admin password and db.php), then:
php /var/www/localhost/htdocs/php-sfc/bin/db-migrate.php

# 2. Wipe quote data and restart numbering.
psql -U sheetfedcalc -h 127.0.0.1 -d sheetfedcalc -v ON_ERROR_STOP=1 <<'SQL'
TRUNCATE sfc_quote_items, sfc_quotes, sfc_clients, sfc_quote_counters
  RESTART IDENTITY CASCADE;
SQL

# 3. Remove dead file-store leftovers (superseded by the database; nothing reads them).
rm -f /var/www/localhost/htdocs/php-sfc/data/quotes/*.json
```

`sfc_quote_counters` is the one that matters for numbering — quote numbers come from
that table, not from row ids, so clearing it is what restarts the sequence at `0001`.
`RESTART IDENTITY` resets the id sequences too, so the database looks genuinely new.

Leave `sfc_exchange_rates` and `sfc_usdt_rates` alone unless the trial rates are
junk; wiping them means re-running both fetchers (§6) before any bolívar amount
appears. Then reload PHP and verify:

```bash
systemctl restart apache2

psql -U sheetfedcalc -h 127.0.0.1 -d sheetfedcalc -c "
SELECT 'quotes' t, count(*) FROM sfc_quotes
UNION ALL SELECT 'clients', count(*) FROM sfc_clients
UNION ALL SELECT 'counters', count(*) FROM sfc_quote_counters;"
```

All three must be `0`. In the browser: `/quotes.php` is empty, the landing footer
shows both rates and the factor, and your first saved quote comes out `YYYY-0001`.

> **Prices are not part of this reset.** They live in `data/config/options.json` on
> the server and both the update rsync and the TRUNCATE leave them untouched. But if
> you ever re-clone onto a *fresh* server, the §1 first-install rsync has no
> `options.json` exclude, so the git seed would overwrite your live prices — fold
> them back into git first, per "Capturing the live prices back into git" below.

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

# rsync as root resets data/ to root:root; PHP cannot save prices until this runs.
chown -R apache:apache /var/www/localhost/htdocs/php-sfc/data

# Apply any new schema (idempotent; a no-op when nothing changed).
php /var/www/localhost/htdocs/php-sfc/bin/db-migrate.php

# Reload PHP so opcache drops the old bytecode (see below).
systemctl restart apache2        # mod_php / mod_fcgid: PHP runs inside Apache
# stand-alone php-fpm instead:   systemctl restart php-fpm
```

### Code-only deploys (no migration)

Most changes — new products, calculator steps, labels/copy, pricing logic, JS/CSS
— touch **no database schema**, so `db-migrate.php` is a **no-op** and the deploy
is just: **pull → rsync → chown data → reload PHP**. The migrate step is always safe to run
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

- after every `rsync`, run
  `chown -R apache:apache /var/www/localhost/htdocs/php-sfc/data`
  (see step 2). Without that, `/admin` cannot write `options.json` and the
  value silently reverts. The admin shows a "no se pudo guardar" error when
  it cannot write;
- values are **per field**: a stray number in one row (e.g. cutting = 15 instead
  of 10) only affects that service. Business cards are only *cut*; folded
  brochures are *cut + creased*; booklets *cut + creased + stapled*.

To instead manage pricing **in git**, drop the `options.json` exclude and edit +
commit the file in the repo; then don't change prices on the server.

#### Capturing the live prices back into git

Because the docroot has no `.git` and the update sync skips the file, **git never
learns the prices you set in /admin**. The committed copy stays frozen at the
last commit and drifts from the live one. That copy still matters: it is what a
**fresh install** gets (step 1 has no `options.json` exclude), and the update
sync will *not* restore a live file that goes missing — the app would fall back
to the code defaults.

So after a round of price changes, push the live file back as the new seed:

```bash
cp /var/www/localhost/htdocs/php-sfc/data/config/options.json \
   /var/tmp/php-sfc-build/data/config/options.json
git -C /var/tmp/php-sfc-build diff --stat            # sanity-check before committing
git -C /var/tmp/php-sfc-build commit -am 'Update seed prices from live'
git -C /var/tmp/php-sfc-build push
```

Do this **from the build clone**, and pull before the next deploy as usual. It is
a backup and a seed refresh, not a deploy: the live file is already the file the
site is using.

## Base path

The app derives its URL prefix from the request, so `/php-sfc/` works with no
configuration. For unusual setups (a reverse proxy or Apache `Alias` that
rewrites the path so it can't be inferred), set it explicitly in the environment:

```apache
SetEnv SFC_BASE_PATH /php-sfc
```

Use an empty value (`SetEnv SFC_BASE_PATH ""`) or `/` to force site-root mode.
