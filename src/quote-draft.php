<?php
/**
 * In-progress quote (the "draft basket"), held in a public server session so it
 * persists as the user configures products across pages. Each item stores the
 * server-priced snapshot; the draft is finalized into a numbered DB quote.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Start the public draft session (idempotent). Separate cookie/name from the
 * admin session so the two never collide.
 *
 * @return void
 */
function sfc_draft_session_start() {
    if ( PHP_SESSION_ACTIVE === session_status() ) {
        return;
    }
    session_name( 'sfc_quote_draft' );
    session_start();
    if ( ! isset( $_SESSION['sfc_draft'] ) || ! is_array( $_SESSION['sfc_draft'] ) ) {
        $_SESSION['sfc_draft'] = array( 'items' => array() );
    }
}

/**
 * Current draft items (raw).
 *
 * @return array<int,array<string,mixed>>
 */
function sfc_draft_get() {
    sfc_draft_session_start();
    return $_SESSION['sfc_draft']['items'];
}

/**
 * Human label for a draft/quote line item, e.g.
 * "Tarjetas de presentación · 90 × 50 mm · 100 u".
 *
 * @param string              $slug         Product slug.
 * @param array<string,mixed> $state        Calculator state.
 * @param array<string,mixed> $quote_result Result from sfc_calculate_product_quote().
 * @return string
 */
function sfc_draft_item_label( $slug, $state, $quote_result ) {
    $config  = sfc_get_product_config( $slug );
    $title   = $config ? ( sfc_get_product_strings( $config )['product_title'] ?? $slug ) : $slug;
    $parts   = array( $title );

    $size_label = $quote_result['size']['label'] ?? '';
    if ( '' !== $size_label ) {
        $parts[] = $size_label;
    }

    $qty = (int) ( $quote_result['unitQuantity'] ?? 0 );
    if ( $qty > 0 ) {
        $parts[] = $qty . ' u';
    }

    return implode( ' · ', $parts );
}

/**
 * Append a server-priced item to the draft.
 *
 * @param string              $slug         Product slug.
 * @param array<string,mixed> $state        Normalized calculator state.
 * @param array<string,mixed> $quote_result Result from sfc_calculate_product_quote().
 * @return array<string,mixed> The stored item.
 */
function sfc_draft_add( $slug, $state, $quote_result ) {
    sfc_draft_session_start();

    $item = array(
        'itemId'      => bin2hex( random_bytes( 6 ) ),
        'productSlug' => $slug,
        'state'       => $state,
        'snapshot'    => $quote_result,
        'lineTotal'   => (float) ( $quote_result['totalPrice'] ?? 0 ),
        'currency'    => (string) ( $quote_result['currency'] ?? 'USD' ),
        'label'       => sfc_draft_item_label( $slug, $state, $quote_result ),
    );

    $_SESSION['sfc_draft']['items'][] = $item;
    return $item;
}

/**
 * Remove an item from the draft by its id.
 *
 * @param string $item_id Item id.
 * @return bool Whether an item was removed.
 */
function sfc_draft_remove( $item_id ) {
    sfc_draft_session_start();
    $before = count( $_SESSION['sfc_draft']['items'] );

    $_SESSION['sfc_draft']['items'] = array_values(
        array_filter(
            $_SESSION['sfc_draft']['items'],
            static function ( $item ) use ( $item_id ) {
                return ( $item['itemId'] ?? '' ) !== $item_id;
            }
        )
    );

    return count( $_SESSION['sfc_draft']['items'] ) < $before;
}

/**
 * Empty the draft.
 *
 * @return void
 */
function sfc_draft_clear() {
    sfc_draft_session_start();
    $_SESSION['sfc_draft']['items'] = array();
}

/**
 * Compact summary for the builder, draft bar, and API responses.
 *
 * @return array{count:int,grandTotal:float,currency:string,items:array<int,array<string,mixed>>}
 */
function sfc_draft_summary() {
    $items = sfc_draft_get();
    $total = 0.0;
    $curr  = 'USD';
    $out   = array();

    foreach ( $items as $item ) {
        $total += (float) $item['lineTotal'];
        $curr   = (string) ( $item['currency'] ?? $curr );
        $out[]  = array(
            'itemId'      => $item['itemId'],
            'productSlug' => $item['productSlug'],
            'label'       => $item['label'],
            'lineTotal'   => (float) $item['lineTotal'],
        );
    }

    return array(
        'count'      => count( $items ),
        'grandTotal' => round( $total, 2 ),
        'currency'   => $curr,
        'items'      => $out,
    );
}
