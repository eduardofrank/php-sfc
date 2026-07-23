<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve quantity tier key for a job run size.
 *
 * @param int $quantity Sheet quantity.
 * @return string|WP_Error
 */
function sfc_resolve_quantity_tier( $quantity ) {
    $quantity = absint( $quantity );
    if ( $quantity <= 0 ) {
        return new WP_Error( 'invalid_quantity', 'Quantity must be at least 1.' );
    }

    foreach ( sfc_get_quantity_tiers() as $tier ) {
        $min = (int) $tier['min'];
        $max = null === $tier['max'] ? null : (int) $tier['max'];

        if ( $quantity >= $min && ( null === $max || $quantity <= $max ) ) {
            return $tier['key'];
        }
    }

    return new WP_Error( 'invalid_quantity_tier', 'No matching quantity tier found.' );
}

/**
 * Return unit sheet price for a table, print mode, and quantity.
 *
 * @param string $table_id   Price table id.
 * @param string $print_mode Print mode (4x0 or 4x4).
 * @param int    $quantity   Job quantity.
 * @return float|WP_Error
 */
function sfc_get_unit_sheet_price( $table_id, $print_mode, $quantity ) {
    $table_id   = sanitize_key( $table_id );
    $print_mode = sanitize_key( $print_mode );
    $tables     = sfc_get_price_tables();

    if ( ! isset( $tables[ $table_id ] ) ) {
        return new WP_Error( 'invalid_table', 'Unknown price table.' );
    }

    $table = $tables[ $table_id ];
    if ( ! in_array( $print_mode, $table['print_modes'], true ) ) {
        return new WP_Error( 'invalid_print_mode', 'Print mode not supported for this table.' );
    }

    $tier_key = sfc_resolve_quantity_tier( $quantity );
    if ( is_wp_error( $tier_key ) ) {
        return $tier_key;
    }

    if ( ! isset( $table['prices'][ $print_mode ][ $tier_key ] ) ) {
        return new WP_Error( 'missing_price', 'Price not configured for selected tier.' );
    }

    return (float) $table['prices'][ $print_mode ][ $tier_key ];
}

/**
 * Validate calculator/job arguments.
 *
 * @param array<string,mixed> $args Job arguments.
 * @return true|WP_Error
 */
function sfc_validate_job_args( $args ) {
    if ( ! is_array( $args ) ) {
        return new WP_Error( 'invalid_args', 'Job arguments must be an array.' );
    }

    $paper_type = sanitize_key( $args['paperType'] ?? '' );
    $print_mode = sanitize_key( $args['printMode'] ?? '' );
    $quantity   = absint( $args['quantity'] ?? 0 );
    $gsm        = isset( $args['gsm'] ) ? absint( $args['gsm'] ) : null;
    $surface    = isset( $args['surface'] ) ? sanitize_key( $args['surface'] ) : null;

    if ( '' === $paper_type ) {
        return new WP_Error( 'invalid_paper_type', 'Paper type is required.' );
    }

    if ( ! in_array( $print_mode, sfc_get_print_modes(), true ) ) {
        return new WP_Error( 'invalid_print_mode', 'Print mode must be 4x0 or 4x4.' );
    }

    if ( $quantity <= 0 ) {
        return new WP_Error( 'invalid_quantity', 'Quantity must be at least 1.' );
    }

    $catalog = sfc_get_paper_catalog();
    if ( ! isset( $catalog[ $paper_type ] ) ) {
        return new WP_Error( 'invalid_paper_type', 'Unknown paper type.' );
    }

    if ( 'coated' === $paper_type ) {
        if ( null === $gsm || $gsm <= 0 ) {
            return new WP_Error( 'invalid_gsm', 'Coated paper requires GSM.' );
        }
        if ( null === $surface || ! in_array( $surface, $catalog['coated']['surfaces'], true ) ) {
            return new WP_Error( 'invalid_surface', 'Coated paper requires matte or glossy surface.' );
        }
    }

    if ( '4x4' === $print_mode && ! sfc_paper_supports_duplex( $paper_type ) ) {
        return new WP_Error( 'duplex_not_supported', 'Duplex printing is not supported for this paper type.' );
    }

    $table_id = sfc_resolve_price_table_id( $paper_type, $gsm );
    if ( is_wp_error( $table_id ) ) {
        return $table_id;
    }

    $unit_price = sfc_get_unit_sheet_price( $table_id, $print_mode, $quantity );
    if ( is_wp_error( $unit_price ) ) {
        return $unit_price;
    }

    return true;
}

/**
 * Calculate total job price.
 *
 * @param array<string,mixed> $args Job arguments.
 * @return array<string,mixed>|WP_Error
 */
function sfc_calculate_job_price( $args ) {
    $validation = sfc_validate_job_args( $args );
    if ( is_wp_error( $validation ) ) {
        return $validation;
    }

    $paper_type = sanitize_key( $args['paperType'] );
    $print_mode = sanitize_key( $args['printMode'] );
    $quantity   = absint( $args['quantity'] );
    $gsm        = isset( $args['gsm'] ) ? absint( $args['gsm'] ) : null;
    $surface    = isset( $args['surface'] ) ? sanitize_key( $args['surface'] ) : null;

    $table_id = sfc_resolve_price_table_id( $paper_type, $gsm );
    if ( is_wp_error( $table_id ) ) {
        return $table_id;
    }

    $tier_key = sfc_resolve_quantity_tier( $quantity );
    if ( is_wp_error( $tier_key ) ) {
        return $tier_key;
    }

    $unit_price = sfc_get_unit_sheet_price( $table_id, $print_mode, $quantity );
    if ( is_wp_error( $unit_price ) ) {
        return $unit_price;
    }

    $total = round( $unit_price * $quantity, 2 );

    return array(
        'paperType'  => $paper_type,
        'printMode'  => $print_mode,
        'quantity'   => $quantity,
        'gsm'        => $gsm,
        'surface'    => $surface,
        'tableId'    => $table_id,
        'tierKey'    => $tier_key,
        'unitPrice'  => $unit_price,
        'totalPrice' => $total,
        'currency'   => 'USD',
    );
}
