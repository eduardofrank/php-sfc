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
 * Whether a product opts into die-cutting.
 *
 * Die-cutting is configured like a job service — listed in the product's
 * `jobServices` — but priced through the tiered die-cut rate system
 * (sfc_apply_die_cut_pricing), not the flat per-service percentage. Any product
 * can enable it by adding 'die_cutting' to its jobServices config.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_product_uses_die_cutting( $product ) {
    $services = (array) ( $product['jobServices'] ?? array() );
    return in_array( 'die_cutting', $services, true );
}

/**
 * Apply configured job service percentages to a pricing result.
 *
 * Each service is a percentage of the **print cost** (not of lamination). The
 * base defaults to the current running total, but callers pass the print-only
 * amount so lamination is billed as a separate line, consistent with die-cut.
 * Applied before turnaround surcharges.
 *
 * @param array<string,mixed> $pricing        Pricing result after print and lamination.
 * @param int                 $sheet_quantity Press sheets required.
 * @param string[]|null       $service_keys   Services to apply (null = all).
 * @param float|null          $base_amount    Amount to charge the % against (print cost).
 * @return array<string,mixed>
 */
function sfc_apply_job_service_pricing( $pricing, $sheet_quantity, $service_keys = null, $base_amount = null ) {
    if ( ! is_array( $pricing ) ) {
        return $pricing;
    }

    $sheet_quantity = absint( $sheet_quantity );
    $running        = (float) ( $pricing['totalPrice'] ?? 0 );
    $pct_base       = null !== $base_amount ? (float) $base_amount : $running;
    $rates          = sfc_get_job_service_rates();
    $breakdown      = array();
    $services_total = 0.0;
    $keys           = is_array( $service_keys ) ? $service_keys : sfc_get_job_service_keys();

    foreach ( $keys as $service_key ) {
        $entry  = $rates[ $service_key ] ?? array();
        $pct    = max( 0.0, (float) ( $entry['percent'] ?? 0 ) );
        $amount = $pct > 0 ? round( $pct_base * ( $pct / 100 ), 2 ) : 0.0;

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

    $total = round( $running + $services_total, 2 );

    $pricing['jobServicesBaseAmount'] = $pct_base;
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
    // Print cost before any add-on; job-service percentages are charged on this,
    // so lamination is billed separately (like die-cut).
    $print_base = (float) ( $pricing['totalPrice'] ?? 0 );

    $pricing = sfc_apply_lamination_pricing( $product, $state, $pricing, $sheet_quantity );
    if ( is_wp_error( $pricing ) ) {
        return $pricing;
    }

    // Effective services: user-selected (state) for products that expose the
    // services checkbox, else the fixed per-product jobServices config.
    $services = sfc_resolve_effective_job_services( $product, $state );

    // 'die_cutting' is priced by the tiered die-cut system; pass the effective
    // list so the gate honours the user's selection, not just config.
    $pricing = sfc_apply_die_cut_pricing( $product, $pricing, $sheet_quantity, $services );

    // Flat percentage services (cutting/creasing/stapling); die_cutting is billed
    // by the tiered system above, so keep it out of the job-service loop.
    $flat_services = array_values( array_diff( $services, array( 'die_cutting' ) ) );

    return sfc_apply_job_service_pricing( $pricing, $sheet_quantity, $flat_services, $print_base );
}

/**
 * Resolve the effective job-service list for a quote.
 *
 * Products that expose a `services` map let the user choose (read from the
 * validated state); all others use the fixed per-product `jobServices` config
 * (default: cutting only). Returns canonical service keys.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Normalized calculator state.
 * @return string[]
 */
function sfc_resolve_effective_job_services( $product, $state ) {
    if ( ! empty( $product['services'] ) && is_array( $product['services'] ) ) {
        $selected = ( isset( $state['services'] ) && is_array( $state['services'] ) )
            ? $state['services']
            : array();
        return array_values( array_intersect( $selected, array_keys( $product['services'] ) ) );
    }

    return ( isset( $product['jobServices'] ) && is_array( $product['jobServices'] ) )
        ? $product['jobServices']
        : array( 'cutting' );
}
