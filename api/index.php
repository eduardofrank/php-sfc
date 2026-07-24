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
        $client_name = trim( (string) wp_unslash( $_POST['client_name'] ?? '' ) );
        if ( '' === $client_name ) {
            sfc_app_send_error( 'Ingrese el nombre del cliente.', 'client_required' );
        }
        $client_email = trim( (string) wp_unslash( $_POST['client_email'] ?? '' ) );
        $client_phone = trim( (string) wp_unslash( $_POST['client_phone'] ?? '' ) );

        // Re-quote to guarantee the saved configuration is valid and priceable;
        // the server is the sole price authority and this becomes the frozen snapshot.
        $quote = sfc_calculate_product_quote( $slug, $state );
        if ( is_wp_error( $quote ) ) {
            sfc_app_send_error( $quote->get_error_message(), $quote->get_error_code() );
        }

        $save_state = isset( $quote['state'] ) && is_array( $quote['state'] ) ? $quote['state'] : $state;

        try {
            $client_id = sfc_quotes_find_or_create_client( $client_name, $client_email, $client_phone );
            $row       = sfc_quotes_create( $slug, $save_state, $quote, $client_id );
        } catch ( Throwable $e ) {
            error_log( 'sfc_save_quote failed: ' . $e->getMessage() );
            sfc_app_send_error( 'No se pudo guardar la cotización.', 'save_failed' );
        }

        sfc_app_send_success(
            array(
                'id'          => $row['shareToken'],
                'url'         => sfc_app_share_url( $slug, $row['shareToken'] ),
                'quoteNumber' => $row['quoteNumber'],
                'clientName'  => $client_name,
                'total'       => $row['total'],
            )
        );
        break;

    case 'sfc_client_names':
        try {
            $names = sfc_client_names( (string) wp_unslash( $_POST['prefix'] ?? '' ) );
        } catch ( Throwable $e ) {
            $names = array();
        }
        sfc_app_send_success( array( 'names' => $names ) );
        break;

    default:
        sfc_app_send_error( 'Acción no válida.', 'invalid_action' );
}
