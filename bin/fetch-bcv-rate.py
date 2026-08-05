#!/usr/bin/env python3
"""Fetch the BCV official USD->VES rate and upsert today's row into
sfc_exchange_rates. Intended to run from cron each morning (America/Caracas)
before business hours.

The app reads the latest row; PHP prices stay in USD and VES is a display
conversion. A failed run leaves the previous day's rate in place (stale but
present) and exits non-zero so cron can alert; the admin can override manually.

Config: DB connection from environment variables
    SFC_DB_HOST  SFC_DB_PORT  SFC_DB_NAME  SFC_DB_USER  SFC_DB_PASS

Deps: pip install requests beautifulsoup4 psycopg2-binary

Usage:
    SFC_DB_HOST=127.0.0.1 SFC_DB_NAME=sheetfedcalc SFC_DB_USER=sheetfedcalc \
    SFC_DB_PASS=... python3 bin/fetch-bcv-rate.py
"""

import os
import sys
import urllib3
from datetime import datetime
from zoneinfo import ZoneInfo

import requests
from bs4 import BeautifulSoup
import psycopg2

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

CARACAS = ZoneInfo("America/Caracas")
TIMEOUT = 20


def fetch_from_bcv():
    """Scrape the official rate from bcv.org.ve (self-signed cert -> verify=False)."""
    resp = requests.get("https://www.bcv.org.ve/", timeout=TIMEOUT, verify=False)
    resp.raise_for_status()
    soup = BeautifulSoup(resp.text, "html.parser")
    node = soup.select_one("#dolar strong") or soup.select_one("#dolar")
    if not node:
        raise ValueError("BCV: USD node not found")
    raw = node.get_text(strip=True).replace(".", "").replace(",", ".")
    rate = float(raw)
    if rate <= 0:
        raise ValueError(f"BCV: implausible rate {rate}")
    return rate, "bcv-scrape"


def fetch_from_dolarapi():
    """Fallback: a maintained JSON API that mirrors the BCV oficial rate."""
    resp = requests.get(
        "https://ve.dolarapi.com/v1/dolares/oficial", timeout=TIMEOUT
    )
    resp.raise_for_status()
    data = resp.json()
    rate = float(data.get("promedio") or data.get("precio") or 0)
    if rate <= 0:
        raise ValueError("dolarapi: no usable rate")
    return rate, "dolarapi"


def fetch_rate():
    errors = []
    for fetch in (fetch_from_bcv, fetch_from_dolarapi):
        try:
            return fetch()
        except Exception as exc:  # noqa: BLE001
            errors.append(f"{fetch.__name__}: {exc}")
    raise RuntimeError("all rate sources failed -> " + " | ".join(errors))


def db_connect():
    return psycopg2.connect(
        host=os.environ.get("SFC_DB_HOST", "127.0.0.1"),
        port=os.environ.get("SFC_DB_PORT", "5432"),
        dbname=os.environ.get("SFC_DB_NAME", "sheetfedcalc"),
        user=os.environ.get("SFC_DB_USER", "sheetfedcalc"),
        password=os.environ.get("SFC_DB_PASS", ""),
    )


def upsert(rate, source):
    today = datetime.now(CARACAS).date()
    conn = db_connect()
    try:
        with conn, conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO sfc_exchange_rates (rate_date, ves_per_usd, source, fetched_at)
                VALUES (%s, %s, %s, now())
                ON CONFLICT (rate_date) DO UPDATE
                  SET ves_per_usd = EXCLUDED.ves_per_usd,
                      source = EXCLUDED.source,
                      fetched_at = now()
                """,
                (today, round(rate, 4), source),
            )
    finally:
        conn.close()
    return today


def main():
    try:
        rate, source = fetch_rate()
        day = upsert(rate, source)
    except Exception as exc:  # noqa: BLE001
        print(f"fetch-bcv-rate: FAILED: {exc}", file=sys.stderr)
        return 1
    print(f"fetch-bcv-rate: {day} = Bs. {rate:.4f}/USD ({source})")
    return 0


if __name__ == "__main__":
    sys.exit(main())
