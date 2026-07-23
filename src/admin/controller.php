<?php
/**
 * Admin POST controller. Each save action reconstructs an option array from the
 * submitted form and runs it through the ported plugin sanitizer before
 * persisting, so the maintenance UI cannot write an invalid price config.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle an admin POST, if any. Returns a flash result or null.
 *
 * @return array{ok:bool,message:string}|null
 */
function sfc_admin_handle_post() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
        return null;
    }

    if ( ! sfc_admin_csrf_valid( $_POST['csrf'] ?? '' ) ) {
        return array( 'ok' => false, 'message' => 'Sesión expirada. Vuelva a intentar.' );
    }

    $action = sanitize_key( $_POST['admin_action'] ?? '' );

    switch ( $action ) {
        case 'save_price_tables':
            return sfc_admin_save_price_tables();
        case 'save_die_cut_rates':
            return sfc_admin_save_generic(
                'sfc_die_cut_rates',
                sfc_admin_rates_from_post( 'die_cut', sfc_get_default_die_cut_rates() ),
                'sfc_sanitize_die_cut_rates',
                'Tarifas de troquelado guardadas.'
            );
        case 'save_turnaround_rates':
            return sfc_admin_save_generic(
                'sfc_turnaround_rates',
                sfc_admin_rates_from_post( 'turnaround', sfc_get_default_turnaround_rates() ),
                'sfc_sanitize_turnaround_rates',
                'Tarifas de tiempo de entrega guardadas.'
            );
        case 'save_job_service_rates':
            return sfc_admin_save_generic(
                'sfc_job_service_rates',
                sfc_admin_rates_from_post( 'job_services', sfc_get_default_job_service_rates() ),
                'sfc_sanitize_job_service_rates',
                'Tarifas de servicios guardadas.'
            );
        case 'save_sheet':
            return sfc_admin_save_sheet();
        case 'save_quantity_tiers':
            return sfc_admin_save_quantity_tiers();
        case 'save_fulfillment':
            return sfc_admin_save_fulfillment();
        case 'reset_defaults':
            return sfc_admin_reset_defaults();
    }

    return array( 'ok' => false, 'message' => 'Acción no reconocida.' );
}

/**
 * Persist a sanitized option, returning a flash result.
 *
 * @param string          $option    Option key.
 * @param array           $raw       Raw array to sanitize.
 * @param callable        $sanitizer Sanitizer function name.
 * @param string          $success   Success message.
 * @return array{ok:bool,message:string}
 */
function sfc_admin_save_generic( $option, $raw, $sanitizer, $success ) {
    $clean = call_user_func( $sanitizer, $raw );
    if ( is_wp_error( $clean ) ) {
        return array( 'ok' => false, 'message' => $clean->get_error_message() );
    }
    update_option( $option, $clean );
    return array( 'ok' => true, 'message' => $success );
}

/**
 * Read a { key => percent } rate form back into the sanitizer's expected shape.
 *
 * @param string $field    POST field name.
 * @param array  $defaults Default rate structure (defines the keys).
 * @return array<string,array<string,mixed>>
 */
function sfc_admin_rates_from_post( $field, $defaults ) {
    $submitted = (array) ( $_POST[ $field ] ?? array() );
    $out       = array();
    foreach ( $defaults as $key => $entry ) {
        $out[ $key ] = array( 'percent' => (float) ( $submitted[ $key ]['percent'] ?? ( $entry['percent'] ?? 0 ) ) );
    }
    return $out;
}

/**
 * Rebuild and save the full price-table set from POST.
 *
 * @return array{ok:bool,message:string}
 */
function sfc_admin_save_price_tables() {
    $defaults  = sfc_get_default_price_tables();
    $submitted = (array) ( $_POST['prices'] ?? array() );
    $rebuilt   = array();

    foreach ( $defaults as $table_id => $table ) {
        $prices = array();
        foreach ( $table['print_modes'] as $mode ) {
            foreach ( array_keys( $table['prices'][ $mode ] ) as $tier ) {
                $prices[ $mode ][ $tier ] = $submitted[ $table_id ][ $mode ][ $tier ]
                    ?? $table['prices'][ $mode ][ $tier ];
            }
        }
        $rebuilt[ $table_id ] = array(
            'label_key'   => $table['label_key'],
            'print_modes' => $table['print_modes'],
            'prices'      => $prices,
        );
    }

    return sfc_admin_save_generic(
        'sfc_price_tables',
        $rebuilt,
        'sfc_sanitize_price_tables',
        'Tabla de precios guardada.'
    );
}

/**
 * Save sheet specs + imposition gap together.
 *
 * @return array{ok:bool,message:string}
 */
function sfc_admin_save_sheet() {
    $specs = sfc_sanitize_sheet_specs( (array) ( $_POST['sheet_specs'] ?? array() ) );
    if ( is_wp_error( $specs ) ) {
        return array( 'ok' => false, 'message' => $specs->get_error_message() );
    }

    $gap = sfc_sanitize_sheet_imposition_gap_mm( $_POST['imposition_gap'] ?? '' );
    if ( is_wp_error( $gap ) ) {
        return array( 'ok' => false, 'message' => $gap->get_error_message() );
    }

    update_option( 'sfc_sheet_specs', $specs );
    update_option( 'sfc_sheet_imposition_gap_mm', $gap );
    return array( 'ok' => true, 'message' => 'Dimensiones de hoja guardadas.' );
}

/**
 * Save quantity-tier min/max bounds (keys are preserved to stay in sync with
 * the price tables).
 *
 * @return array{ok:bool,message:string}
 */
function sfc_admin_save_quantity_tiers() {
    $current   = sfc_get_quantity_tiers();
    $submitted = (array) ( $_POST['tiers'] ?? array() );
    $rebuilt   = array();

    foreach ( $current as $tier ) {
        $key       = $tier['key'];
        $in        = (array) ( $submitted[ $key ] ?? array() );
        $max_raw   = trim( (string) ( $in['max'] ?? '' ) );
        $rebuilt[] = array(
            'key' => $key,
            'min' => (int) ( $in['min'] ?? $tier['min'] ),
            'max' => '' === $max_raw ? null : (int) $max_raw,
        );
    }

    return sfc_admin_save_generic(
        'sfc_quantity_tiers',
        $rebuilt,
        'sfc_sanitize_quantity_tiers',
        'Niveles de cantidad guardados.'
    );
}

/**
 * Save fulfillment settings (shop city + same-day availability).
 *
 * @return array{ok:bool,message:string}
 */
function sfc_admin_save_fulfillment() {
    $in  = (array) ( $_POST['fulfillment'] ?? array() );
    $raw = array(
        'shopCity'                     => $in['shopCity'] ?? '',
        'sameDayShowOnCalculator'      => ! empty( $in['sameDayShowOnCalculator'] ),
        'sameDayAllowLocalPickup'      => true,
        'sameDayAllowShopCityDelivery' => true,
    );
    return sfc_admin_save_generic(
        'sfc_fulfillment_settings',
        $raw,
        'sfc_sanitize_fulfillment_settings',
        'Configuración de entrega guardada.'
    );
}

/**
 * Reset every price-affecting option back to the code defaults.
 *
 * @return array{ok:bool,message:string}
 */
function sfc_admin_reset_defaults() {
    update_option( 'sfc_price_tables', sfc_get_default_price_tables() );
    update_option( 'sfc_quantity_tiers', sfc_get_default_quantity_tiers() );
    update_option( 'sfc_sheet_specs', sfc_get_default_sheet_specs() );
    update_option( 'sfc_sheet_imposition_gap_mm', sfc_get_default_sheet_imposition_gap_mm() );
    update_option( 'sfc_job_service_rates', sfc_get_default_job_service_rates() );
    update_option( 'sfc_die_cut_rates', sfc_get_default_die_cut_rates() );
    update_option( 'sfc_turnaround_rates', sfc_get_default_turnaround_rates() );
    update_option( 'sfc_paper_catalog', sfc_get_default_paper_catalog() );
    update_option( 'sfc_fulfillment_settings', sfc_get_default_fulfillment_settings() );
    return array( 'ok' => true, 'message' => 'Se restauraron todos los valores por defecto.' );
}
