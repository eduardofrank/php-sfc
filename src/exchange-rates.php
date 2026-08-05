<?php
/**
 * USD -> VES exchange-rate access and Bs. formatting.
 *
 * The daily BCV rate is written to sfc_exchange_rates (by bin/fetch-bcv-rate.py
 * or the admin manual override). Pricing stays in USD; VES is a display
 * conversion. Finalized quotes freeze the rate they were issued at.
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
    unset( $GLOBALS['__sfc_rate_loaded'], $GLOBALS['__sfc_rate_row'] );
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
