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
        $client_name = trim( (string) wp_unslash( $_POST['client_name'] ?? '' ) );
        if ( '' === $client_name ) {
            sfc_app_send_error( 'Ingrese el nombre del cliente.', 'client_required' );
        }
        $client_email = trim( (string) wp_unslash( $_POST['client_email'] ?? '' ) );
        $title        = trim( (string) wp_unslash( $_POST['title'] ?? '' ) );
        $notes        = trim( (string) wp_unslash( $_POST['notes'] ?? '' ) );

        $draft = sfc_draft_get();
        if ( empty( $draft ) ) {
            sfc_app_send_error( 'La cotización no tiene ítems.', 'empty_draft' );
        }

        // Re-price every item now, so the frozen snapshot reflects issue-time prices.
        $items = array();
        foreach ( $draft as $draft_item ) {
            $i_slug  = $draft_item['productSlug'];
            $i_state = $draft_item['state'];
            $quote   = sfc_calculate_product_quote( $i_slug, $i_state );
            if ( is_wp_error( $quote ) ) {
                sfc_app_send_error(
                    'Un ítem ya no es válido: ' . $quote->get_error_message(),
                    'item_invalid'
                );
            }
            $items[] = array(
                'productSlug' => $i_slug,
                'state'       => isset( $quote['state'] ) && is_array( $quote['state'] ) ? $quote['state'] : $i_state,
                'snapshot'    => $quote,
                'lineTotal'   => (float) $quote['totalPrice'],
                'currency'    => (string) ( $quote['currency'] ?? 'USD' ),
                'label'       => sfc_draft_item_label( $i_slug, $i_state, $quote ),
            );
        }

        try {
            $client_id = sfc_quotes_find_or_create_client( $client_name, $client_email );
            $row       = sfc_quotes_create_from_draft( $client_id, $items, $title, $notes );
        } catch ( Throwable $e ) {
            error_log( 'sfc_finalize_quote failed: ' . $e->getMessage() );
            sfc_app_send_error( 'No se pudo generar la cotización.', 'finalize_failed' );
        }

        sfc_draft_clear();

        sfc_app_send_success(
            array(
                'quoteNumber' => $row['quoteNumber'],
                'url'         => sfc_app_quote_url( $row['shareToken'] ),
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
