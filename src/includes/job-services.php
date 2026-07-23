<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registered job service keys applied to every calculator quote.
 *
 * @return string[]
 */
function sfc_get_job_service_keys() {
    return array( 'cutting', 'creasing', 'stapling' );
}

/**
 * Apply configured job service percentages to a pricing result.
 *
 * Each service adds percent of the current subtotal (print + lamination).
 * Applied before turnaround surcharges.
 *
 * @param array<string,mixed> $pricing        Pricing result after print and lamination.
 * @param int                 $sheet_quantity Press sheets required.
 * @return array<string,mixed>
 */
function sfc_apply_job_service_pricing( $pricing, $sheet_quantity, $service_keys = null ) {
    if ( ! is_array( $pricing ) ) {
        return $pricing;
    }

    $sheet_quantity = absint( $sheet_quantity );
    $base           = (float) ( $pricing['totalPrice'] ?? 0 );
    $rates          = sfc_get_job_service_rates();
    $breakdown      = array();
    $services_total = 0.0;
    $keys           = is_array( $service_keys ) ? $service_keys : sfc_get_job_service_keys();

    foreach ( $keys as $service_key ) {
        $entry  = $rates[ $service_key ] ?? array();
        $pct    = max( 0.0, (float) ( $entry['percent'] ?? 0 ) );
        $amount = $pct > 0 ? round( $base * ( $pct / 100 ), 2 ) : 0.0;

        $breakdown[ $service_key ] = array(
            'percent' => $pct,
            'amount'  => $amount,
        );
        $services_total += $amount;
    }

    $services_total = round( $services_total, 2 );
    if ( $services_total <= 0 ) {
        return $pricing;
    }

    $total = round( $base + $services_total, 2 );

    $pricing['jobServicesBaseAmount'] = $base;
    $pricing['jobServicesBreakdown']  = $breakdown;
    $pricing['jobServicesAmount']     = $services_total;
    $pricing['totalPrice']            = $total;

    if ( $sheet_quantity > 0 ) {
        $pricing['unitPrice'] = round( $total / $sheet_quantity, 2 );
    }

    return $pricing;
}

/**
 * Apply product add-ons (lamination, job services) before turnaround pricing.
 *
 * @param array<string,mixed> $product        Product config.
 * @param array<string,mixed> $state          Calculator state.
 * @param array<string,mixed> $pricing        Base print pricing result.
 * @param int                 $sheet_quantity Press sheets required.
 * @return array<string,mixed>|WP_Error
 */
function sfc_apply_product_addon_pricing( $product, $state, $pricing, $sheet_quantity ) {
    $pricing = sfc_apply_lamination_pricing( $product, $state, $pricing, $sheet_quantity );
    if ( is_wp_error( $pricing ) ) {
        return $pricing;
    }

    $pricing = sfc_apply_die_cut_pricing( $product, $pricing, $sheet_quantity );

    return sfc_apply_job_service_pricing( $pricing, $sheet_quantity );
}
