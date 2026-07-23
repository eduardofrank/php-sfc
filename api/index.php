<?php
/**
 * JSON API router.
 *
 * Mirrors the plugin's admin-ajax contract so the ported front-end JS works
 * unchanged: requests carry an `action` field and receive a WordPress-style
 * { success, data } envelope. The server is the sole pricing authority — a
 * client-supplied price is never trusted (save re-quotes before persisting).
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';

header( 'X-Content-Type-Options: nosniff' );

$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
$slug   = isset( $_POST['product_slug'] ) ? sanitize_key( str_replace( '_', '-', wp_unslash( $_POST['product_slug'] ) ) ) : '';

$raw_state = isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : '';
$state     = is_string( $raw_state ) ? json_decode( $raw_state, true ) : $raw_state;
if ( ! is_array( $state ) ) {
    $state = array();
}

switch ( $action ) {
    case 'sfc_calculate_product_quote':
        $quote = sfc_calculate_product_quote( $slug, $state );
        if ( is_wp_error( $quote ) ) {
            sfc_app_send_error( $quote->get_error_message(), $quote->get_error_code() );
        }
        sfc_app_send_success( $quote );
        break;

    case 'sfc_save_quote':
        // Re-quote to guarantee the saved configuration is valid and priceable.
        $quote = sfc_calculate_product_quote( $slug, $state );
        if ( is_wp_error( $quote ) ) {
            sfc_app_send_error( $quote->get_error_message(), $quote->get_error_code() );
        }

        // Persist the normalized state the engine actually used.
        $save_state = isset( $quote['state'] ) && is_array( $quote['state'] ) ? $quote['state'] : $state;
        $id         = sfc_app_save_quote( $slug, $save_state );
        if ( is_wp_error( $id ) ) {
            sfc_app_send_error( $id->get_error_message(), $id->get_error_code() );
        }

        $url = sfc_app_share_url( $slug, $id );
        sfc_app_send_success( array( 'id' => $id, 'url' => $url ) );
        break;

    default:
        sfc_app_send_error( 'Acción no válida.', 'invalid_action' );
}
