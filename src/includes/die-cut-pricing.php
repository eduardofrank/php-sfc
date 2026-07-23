<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve the die-cut rate tier for a press sheet count.
 *
 * @param int $sheet_quantity Total press sheets in the job.
 * @return array<string,mixed>
 */
function sfc_resolve_die_cut_rate_tier( $sheet_quantity ) {
    $rates = sfc_get_die_cut_rates();
    $q     = absint( $sheet_quantity );

    foreach ( $rates as $tier_key => $entry ) {
        $min = max( 1, (int) ( $entry['min_sheets'] ?? 1 ) );
        $max = array_key_exists( 'max_sheets', $entry ) && null !== $entry['max_sheets']
            ? (int) $entry['max_sheets']
            : null;

        if ( $q < $min ) {
            continue;
        }

        if ( null === $max || $q <= $max ) {
            return array_merge(
                $entry,
                array(
                    'key' => (string) $tier_key,
                )
            );
        }
    }

    $fallback_key = 'above_100';
    $fallback     = $rates[ $fallback_key ] ?? array(
        'percent' => 15.0,
    );

    return array_merge(
        $fallback,
        array(
            'key' => $fallback_key,
        )
    );
}

/**
 * Apply die-cut surcharges to a print pricing result.
 *
 * Percentage is taken from the print cost only (before lamination), keyed by
 * total press sheet count tiers configured in admin.
 *
 * @param array<string,mixed> $product        Product config.
 * @param array<string,mixed> $pricing        Pricing result after print and lamination.
 * @param int                 $sheet_quantity Press sheets required.
 * @return array<string,mixed>
 */
function sfc_apply_die_cut_pricing( $product, $pricing, $sheet_quantity ) {
    if ( empty( $product['dieCutShapes'] ) || ! is_array( $pricing ) ) {
        return $pricing;
    }

    $sheet_quantity = absint( $sheet_quantity );
    if ( $sheet_quantity <= 0 ) {
        return $pricing;
    }

    $print_base        = (float) ( $pricing['printTotalPrice'] ?? $pricing['totalPrice'] );
    $current_total     = (float) ( $pricing['totalPrice'] ?? $print_base );
    $pre_die_cut_addons = round( max( 0, $current_total - $print_base ), 2 );
    $tier              = sfc_resolve_die_cut_rate_tier( $sheet_quantity );
    $pct               = max( 0.0, (float) ( $tier['percent'] ?? 0 ) );

    if ( $pct <= 0 ) {
        return $pricing;
    }

    $amount = round( $print_base * ( $pct / 100 ), 2 );
    if ( $amount <= 0 ) {
        return $pricing;
    }

    $total = round( $print_base + $pre_die_cut_addons + $amount, 2 );

    $pricing['printTotalPrice']  = $print_base;
    $pricing['dieCutTierKey']    = (string) ( $tier['key'] ?? '' );
    $pricing['dieCutPercent']    = $pct;
    $pricing['dieCutBaseAmount'] = $print_base;
    $pricing['dieCutAmount']     = $amount;
    $pricing['totalPrice']       = $total;
    $pricing['unitPrice']        = round( $total / $sheet_quantity, 2 );

    return $pricing;
}

/**
 * Cart/order display row for die-cutting cost.
 *
 * @param array<string,mixed> $quote    Quote payload.
 * @param array<string,mixed> $product Product config.
 * @param string              $language Language code.
 * @return array<string,string>|null
 */
function sfc_build_die_cut_cart_row( $quote, $product, $language ) {
    if ( empty( $product['dieCutShapes'] ) ) {
        return null;
    }

    $pricing = (array) ( $quote['pricing'] ?? array() );
    $amount  = (float) ( $pricing['dieCutAmount'] ?? 0 );

    if ( $amount <= 0 ) {
        return null;
    }

    $strings = sfc_get_product_strings( $product );

    return array(
        'key'   => $strings['die_cut_label'] ?? ( 'es' === $language ? 'Troquelado' : 'Die-cutting' ),
        'value' => '$' . number_format( $amount, 2, '.', '' ),
    );
}
