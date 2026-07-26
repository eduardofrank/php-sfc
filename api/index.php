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

    case 'sfc_quote_add_item':
        // Price the current config (server authority) and append it to the draft.
        $quote = sfc_calculate_product_quote( $slug, $state );
        if ( is_wp_error( $quote ) ) {
            sfc_app_send_error( $quote->get_error_message(), $quote->get_error_code() );
        }
        $save_state = isset( $quote['state'] ) && is_array( $quote['state'] ) ? $quote['state'] : $state;
        sfc_draft_add( $slug, $save_state, $quote );
        sfc_app_send_success( sfc_draft_summary() );
        break;

    case 'sfc_quote_remove_item':
        sfc_draft_remove( sanitize_key( wp_unslash( $_POST['item_id'] ?? '' ) ) );
        sfc_app_send_success( sfc_draft_summary() );
        break;

    case 'sfc_quote_clear':
        sfc_draft_clear();
        sfc_app_send_success( sfc_draft_summary() );
        break;

    case 'sfc_quote_draft':
        sfc_app_send_success( sfc_draft_summary() );
        break;

    case 'sfc_finalize_quote':
        $row = sfc_draft_finalize(
            (string) wp_unslash( $_POST['client_name'] ?? '' ),
            (string) wp_unslash( $_POST['client_email'] ?? '' ),
            (string) wp_unslash( $_POST['title'] ?? '' ),
            (string) wp_unslash( $_POST['notes'] ?? '' )
        );
        if ( is_wp_error( $row ) ) {
            sfc_app_send_error( $row->get_error_message(), $row->get_error_code() );
        }
        sfc_app_send_success(
            array(
                'quoteNumber' => $row['quoteNumber'],
                'url'         => sfc_app_quote_url( $row['shareToken'] ),
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
