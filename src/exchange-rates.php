<?php
/**
 * USD -> VES exchange-rate access and Bs. formatting.
 *
 * Two rates feed the bolivar figure:
 *   - BCV: the official daily rate, in sfc_exchange_rates, one row per business
 *     day (bin/fetch-bcv-rate.py or the admin manual override).
 *   - USDT: the P2P market rate, in sfc_usdt_rates, sampled hourly by
 *     bin/fetch-usdt-rate.py because it moves through the day.
 *
 * Published bolivares are  USD * bcv_rate * factor,  where factor is
 * usdt_rate / bcv_rate (typically 1.15-1.5). Carried at full precision that is
 * arithmetically USD * usdt_rate; the two legs and the factor are kept apart so
 * a quote can show how its total was reached. sfc_effective_ves_rate() is the
 * single value callers multiply by.
 *
 * Pricing stays in USD; VES is a display conversion. Finalized quotes freeze
 * both legs and the factor they were issued at.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Most recent stored rate row, or null if none / DB unavailable.
 *
 * @return array{rate_date:string,ves_per_usd:float,source:?string}|null
 */
function sfc_current_rate_row() {
    if ( ! empty( $GLOBALS['__sfc_rate_loaded'] ) ) {
        return $GLOBALS['__sfc_rate_row'];
    }
    $GLOBALS['__sfc_rate_loaded'] = true;
    $GLOBALS['__sfc_rate_row']    = null;

    try {
        $stmt = sfc_db()->query(
            'SELECT rate_date, ves_per_usd, source FROM sfc_exchange_rates ORDER BY rate_date DESC LIMIT 1'
        );
        $r = $stmt->fetch();
        if ( $r ) {
            $r['ves_per_usd']          = (float) $r['ves_per_usd'];
            $GLOBALS['__sfc_rate_row'] = $r;
        }
    } catch ( Throwable $e ) {
        $GLOBALS['__sfc_rate_row'] = null;
    }

    return $GLOBALS['__sfc_rate_row'];
}

/**
 * Drop the per-request rate cache (call after a write so a later read in the
 * same request sees the new value).
 *
 * @return void
 */
function sfc_reset_rate_cache() {
    unset(
        $GLOBALS['__sfc_rate_loaded'],
        $GLOBALS['__sfc_rate_row'],
        $GLOBALS['__sfc_usdt_loaded'],
        $GLOBALS['__sfc_usdt_row']
    );
}

/**
 * Current USD->VES rate (Bs. per 1 USD), or null when unavailable.
 *
 * @return float|null
 */
function sfc_current_usd_ves_rate() {
    $row = sfc_current_rate_row();
    return $row ? (float) $row['ves_per_usd'] : null;
}

/**
 * Most recent stored USDT rate row, or null if none / DB unavailable.
 *
 * sfc_usdt_rates keeps every hourly sample, so this takes the newest by
 * fetched_at rather than a per-day key.
 *
 * @return array{ves_per_usdt:float,source:?string,fetched_at:string}|null
 */
function sfc_current_usdt_rate_row() {
    if ( ! empty( $GLOBALS['__sfc_usdt_loaded'] ) ) {
        return $GLOBALS['__sfc_usdt_row'];
    }
    $GLOBALS['__sfc_usdt_loaded'] = true;
    $GLOBALS['__sfc_usdt_row']    = null;

    try {
        $stmt = sfc_db()->query(
            'SELECT ves_per_usdt, source, fetched_at FROM sfc_usdt_rates ORDER BY fetched_at DESC LIMIT 1'
        );
        $r = $stmt->fetch();
        if ( $r ) {
            $r['ves_per_usdt']         = (float) $r['ves_per_usdt'];
            $GLOBALS['__sfc_usdt_row'] = $r;
        }
    } catch ( Throwable $e ) {
        $GLOBALS['__sfc_usdt_row'] = null;
    }

    return $GLOBALS['__sfc_usdt_row'];
}

/**
 * Current USDT->VES rate (Bs. per 1 USDT), or null when unavailable.
 *
 * @return float|null
 */
function sfc_current_usdt_rate() {
    $row = sfc_current_usdt_rate_row();
    return $row ? (float) $row['ves_per_usdt'] : null;
}

/**
 * Market factor for a pair of rates: how far the P2P rate sits above the
 * official one. Returns null unless both legs are usable.
 *
 * @param float|null $bcv  Bs. per USD (official).
 * @param float|null $usdt Bs. per USDT (P2P).
 * @return float|null Typically ~1.15-1.5.
 */
function sfc_ves_factor( $bcv, $usdt ) {
    $bcv  = (float) $bcv;
    $usdt = (float) $usdt;
    if ( $bcv <= 0 || $usdt <= 0 ) {
        return null;
    }
    return $usdt / $bcv;
}

/**
 * Current market factor (USDT / BCV), or null when either leg is missing.
 *
 * @return float|null
 */
function sfc_current_ves_factor() {
    return sfc_ves_factor( sfc_current_usd_ves_rate(), sfc_current_usdt_rate() );
}

/**
 * The rate callers actually multiply USD by: BCV * factor.
 *
 * Null when either leg is missing, which hides Bs. entirely rather than
 * publishing an official-rate figure roughly 20% under the intended price.
 *
 * @return float|null
 */
function sfc_effective_ves_rate() {
    $bcv    = sfc_current_usd_ves_rate();
    $factor = sfc_current_ves_factor();
    if ( null === $bcv || null === $factor ) {
        return null;
    }
    // Rounded to the precision the rates are stored at, so the value is exact
    // rather than carrying binary-float noise into JSON and totals.
    return round( (float) $bcv * $factor, 4 );
}

/**
 * Format a factor for display, e.g. "1,1767".
 *
 * @param float|null $factor
 * @return string
 */
function sfc_format_factor( $factor ) {
    if ( null === $factor || $factor <= 0 ) {
        return '';
    }
    return number_format( (float) $factor, 4, ',', '.' );
}

/**
 * Format a USD amount as bolívares (es-VE: "Bs. 3.460,00").
 *
 * @param float      $usd  Amount in USD.
 * @param float|null $rate Bs. per USD; null returns an empty string (VES hidden).
 * @return string
 */
function sfc_format_ves( $usd, $rate ) {
    if ( null === $rate || $rate <= 0 ) {
        return '';
    }
    return 'Bs. ' . number_format( (float) $usd * (float) $rate, 2, ',', '.' );
}

/**
 * Format a rate value itself, e.g. "Bs. 40,25".
 *
 * @param float|null $rate Bs. per USD.
 * @return string
 */
function sfc_format_rate( $rate ) {
    if ( null === $rate || $rate <= 0 ) {
        return '';
    }
    return 'Bs. ' . number_format( (float) $rate, 2, ',', '.' );
}

/**
 * Upsert today's rate (America/Caracas) with the given value.
 *
 * @param float  $rate   Bs. per USD (> 0).
 * @param string $source Origin tag ('manual', 'bcv-scrape', …).
 * @return bool
 */
function sfc_set_manual_rate( $rate, $source = 'manual' ) {
    $rate = (float) $rate;
    if ( $rate <= 0 ) {
        return false;
    }
    try {
        $today = new DateTimeImmutable( 'now', new DateTimeZone( 'America/Caracas' ) );
        $stmt  = sfc_db()->prepare(
            'INSERT INTO sfc_exchange_rates (rate_date, ves_per_usd, source, fetched_at)
             VALUES (:d, :r, :s, now())
             ON CONFLICT (rate_date) DO UPDATE
               SET ves_per_usd = EXCLUDED.ves_per_usd, source = EXCLUDED.source, fetched_at = now()'
        );
        $stmt->execute(
            array(
                ':d' => $today->format( 'Y-m-d' ),
                ':r' => number_format( $rate, 4, '.', '' ),
                ':s' => (string) $source,
            )
        );
        sfc_reset_rate_cache();
        return true;
    } catch ( Throwable $e ) {
        error_log( 'sfc_set_manual_rate failed: ' . $e->getMessage() );
        return false;
    }
}

/**
 * Append a USDT rate sample with the given value.
 *
 * Unlike the BCV rate this inserts rather than upserts: the table is a time
 * series and a manual entry is just the newest sample.
 *
 * @param float  $rate   Bs. per USDT (> 0).
 * @param string $source Origin tag ('manual', 'usdt.com.ve/...', ...).
 * @return bool
 */
function sfc_set_manual_usdt_rate( $rate, $source = 'manual' ) {
    $rate = (float) $rate;
    if ( $rate <= 0 ) {
        return false;
    }
    try {
        $stmt = sfc_db()->prepare(
            'INSERT INTO sfc_usdt_rates (ves_per_usdt, source, fetched_at)
             VALUES (:r, :s, now())'
        );
        $stmt->execute(
            array(
                ':r' => number_format( $rate, 4, '.', '' ),
                ':s' => (string) $source,
            )
        );
        sfc_reset_rate_cache();
        return true;
    } catch ( Throwable $e ) {
        error_log( 'sfc_set_manual_usdt_rate failed: ' . $e->getMessage() );
        return false;
    }
}
