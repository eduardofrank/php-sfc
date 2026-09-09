#!/usr/bin/env python3
"""Fetch the P2P USDT->VES rate from usdt.com.ve and append a row to
sfc_usdt_rates. Intended to run from cron every hour: unlike the BCV rate,
which is fixed for the business day, P2P rates move continuously.

We take the HIGHEST USDT figure the site displays (the "Comparar Tasas" table
lists a buy and a sell reference per platform). The BCV reference row in that
same table is excluded — it is not a USDT price, and mixing it in would silently
drag the maximum down if the P2P feed ever went empty.

The app reads the most recent row and divides it by the BCV rate to get the
factor applied to bolivar totals. A failed run leaves the previous row in place
(stale but present) and exits non-zero so cron can alert; the admin can override
manually from /admin.

Config: DB connection from environment variables
    SFC_DB_HOST  SFC_DB_PORT  SFC_DB_NAME  SFC_DB_USER  SFC_DB_PASS

Deps: pip install requests beautifulsoup4 psycopg2-binary

Usage:
    SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc \
    SFC_DB_PASS=... python3 bin/fetch-usdt-rate.py
"""

import os
import re
import sys

import requests
from bs4 import BeautifulSoup
import psycopg2

SOURCE_URL = "https://www.usdt.com.ve/"
TIMEOUT = 20
USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0 Safari/537.36"
)

# A VES-per-USDT price. Anything outside this band is a parse error, not a rate:
# it catches percentages, spreads and stray page numbers picked up by mistake.
MIN_PLAUSIBLE = 50.0
MAX_PLAUSIBLE = 100000.0

NUM_RE = re.compile(r"^\d{1,3}(?:\.\d{3})*,\d{1,4}$|^\d+(?:[.,]\d{1,4})?$")


def parse_ves(text):
    """'1.234,56' / '965,00' -> float. Returns None when not a plain number."""
    t = text.strip().replace("\xa0", " ").replace("Bs.", "").replace("Bs", "").strip()
    if not t or not NUM_RE.match(t):
        return None
    if "," in t:
        t = t.replace(".", "").replace(",", ".")
    try:
        return float(t)
    except ValueError:
        return None


def candidates_from_table(soup):
    """USDT prices from the 'Comparar Tasas' table, skipping the BCV row."""
    out = []
    for table in soup.find_all("table"):
        for row in table.select("tbody tr"):
            cells = row.find_all("td")
            if len(cells) < 2:
                continue
            platform = cells[0].get_text(" ", strip=True)
            if "bcv" in platform.lower():
                continue
            for cell in cells[1:]:
                value = parse_ves(cell.get_text(" ", strip=True))
                if value is not None and MIN_PLAUSIBLE <= value <= MAX_PLAUSIBLE:
                    out.append((value, platform))
    return out


def candidates_from_cards(soup):
    """Fallback: the 'Mejor compra' / 'Mejor venta' headline cards.

    Deliberately narrow. A loose scan of every number on the page could latch
    onto the BCV reference or a spread and misprice quotes silently, which is
    worse than failing loudly and letting cron alert.
    """
    text = re.sub(r"\s+", " ", soup.get_text(" ", strip=True))
    out = []
    for raw in re.findall(r"Mejor\s+(?:compra|venta)\s*Bs\.?\s*([\d.]+,\d{2})", text):
        value = parse_ves(raw)
        if value is not None and MIN_PLAUSIBLE <= value <= MAX_PLAUSIBLE:
            out.append((value, "card"))
    return out


def fetch_rate():
    resp = requests.get(
        SOURCE_URL, timeout=TIMEOUT, headers={"User-Agent": USER_AGENT}
    )
    resp.raise_for_status()
    soup = BeautifulSoup(resp.text, "html.parser")

    found = candidates_from_table(soup) or candidates_from_cards(soup)
    if not found:
        raise ValueError("usdt.com.ve: no USDT price found (page layout changed?)")

    rate, platform = max(found, key=lambda pair: pair[0])
    return rate, f"usdt.com.ve/{platform}"


def db_connect():
    return psycopg2.connect(
        host=os.environ.get("SFC_DB_HOST", "127.0.0.1"),
        port=os.environ.get("SFC_DB_PORT", "5432"),
        dbname=os.environ.get("SFC_DB_NAME", "sheetfedcalc"),
        user=os.environ.get("SFC_DB_USER", "sheetfedcalc"),
        password=os.environ.get("SFC_DB_PASS", ""),
    )


def insert(rate, source):
    conn = db_connect()
    try:
        with conn, conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO sfc_usdt_rates (ves_per_usdt, source, fetched_at)
                VALUES (%s, %s, now())
                RETURNING fetched_at
                """,
                (round(rate, 4), source),
            )
            return cur.fetchone()[0]
    finally:
        conn.close()


def main():
    try:
        rate, source = fetch_rate()
        stamp = insert(rate, source)
    except Exception as exc:  # noqa: BLE001
        print(f"fetch-usdt-rate: FAILED: {exc}", file=sys.stderr)
        return 1
    print(f"fetch-usdt-rate: {stamp:%Y-%m-%d %H:%M} = Bs. {rate:.4f}/USDT ({source})")
    return 0


if __name__ == "__main__":
    sys.exit(main())
