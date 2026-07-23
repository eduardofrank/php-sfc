<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Finish keys that apply sheet lamination.
 *
 * @return string[]
 */
function sfc_get_laminated_finish_keys() {
    return array( 'matte_laminate', 'glossy_laminate' );
}

/**
 * Whether a finish selection includes lamination.
 *
 * @param string $finish_key Finish key from calculator state.
 * @return bool
 */
function sfc_is_laminated_finish( $finish_key ) {
    return in_array( sanitize_key( $finish_key ), sfc_get_laminated_finish_keys(), true );
}

/**
 * Number of press-sheet sides to laminate for a print mode.
 *
 * @param string $print_mode Print mode (4x0 or 4x4).
 * @return int
 */
function sfc_get_lamination_sides_for_print_mode( $print_mode ) {
    return '4x4' === sanitize_key( $print_mode ) ? 2 : 1;
}

/**
 * USD price for one laminated side on one press sheet.
 *
 * Uses the lamination price table tier for the job's press-sheet count.
 *
 * @param int $sheet_quantity Press sheets in the job.
 * @return float|WP_Error
 */
function sfc_get_lamination_price_per_sheet_side( $sheet_quantity ) {
    return sfc_get_unit_sheet_price( 'lamination', 'per_side', absint( $sheet_quantity ) );
}

/**
 * Apply lamination surcharges to a print pricing result.
 *
 * @param array<string,mixed> $product        Product config.
 * @param array<string,mixed> $state          Calculator state.
 * @param array<string,mixed> $pricing        Pricing result from sfc_calculate_job_price().
 * @param int                 $sheet_quantity Press sheets required.
 * @return array<string,mixed>|WP_Error
 */
function sfc_apply_lamination_pricing( $product, $state, $pricing, $sheet_quantity ) {
    if ( empty( $product['finishes'] ) || ! is_array( $pricing ) ) {
        return $pricing;
    }

    $finish = sanitize_key( $state['finish'] ?? '' );
    if ( ! sfc_is_laminated_finish( $finish ) ) {
        return $pricing;
    }

    $sheet_quantity = absint( $sheet_quantity );
    if ( $sheet_quantity <= 0 ) {
        return $pricing;
    }

    $print_mode = sanitize_key( $pricing['printMode'] ?? '4x0' );
    $sides      = sfc_get_lamination_sides_for_print_mode( $print_mode );
    $rate       = sfc_get_lamination_price_per_sheet_side( $sheet_quantity );

    if ( is_wp_error( $rate ) ) {
        return $rate;
    }

    $print_total = (float) $pricing['totalPrice'];
    $lam_amount  = round( $sheet_quantity * $sides * (float) $rate, 2 );
    $total       = round( $print_total + $lam_amount, 2 );

    $pricing['printTotalPrice']         = $print_total;
    $pricing['printUnitPrice']          = (float) $pricing['unitPrice'];
    $pricing['laminationPricePerSide']  = (float) $rate;
    $pricing['laminationSidesPerSheet'] = $sides;
    $pricing['laminationSheetQuantity'] = $sheet_quantity;
    $pricing['laminationAmount']        = $lam_amount;
    $pricing['totalPrice']              = $total;
    $pricing['unitPrice']               = round( $total / $sheet_quantity, 2 );

    return $pricing;
}
