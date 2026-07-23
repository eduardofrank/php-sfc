<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * USD price for one hardcover album from the price table.
 *
 * @param int $album_quantity Finished album count (tier lookup).
 * @return float|WP_Error
 */
function sfc_get_hardcover_price_per_album( $album_quantity ) {
    return sfc_get_unit_sheet_price( 'hardcover_binding', 'per_unit', absint( $album_quantity ) );
}

/**
 * Apply hardcover binding surcharges to a pricing result.
 *
 * @param array<string,mixed> $product        Product config.
 * @param array<string,mixed> $state          Calculator state.
 * @param array<string,mixed> $pricing        Pricing result after print pricing.
 * @param int                 $album_quantity Finished album count.
 * @return array<string,mixed>|WP_Error
 */
function sfc_apply_hardcover_pricing( $product, $state, $pricing, $album_quantity ) {
    if ( empty( $product['hardcoverFinishes'] ) || ! is_array( $pricing ) ) {
        return $pricing;
    }

    $finish = sanitize_key( $state['hardcover_finish'] ?? '' );
    if ( ! isset( $product['hardcoverFinishes'][ $finish ] ) ) {
        return $pricing;
    }

    $album_quantity = absint( $album_quantity );
    if ( $album_quantity <= 0 ) {
        return $pricing;
    }

    $rate = sfc_get_hardcover_price_per_album( $album_quantity );
    if ( is_wp_error( $rate ) ) {
        return $rate;
    }

    $print_total = (float) ( $pricing['totalPrice'] ?? 0 );
    $amount      = round( $album_quantity * (float) $rate, 2 );
    $total       = round( $print_total + $amount, 2 );

    $pricing['printTotalPrice']       = $print_total;
    $pricing['hardcoverPricePerAlbum'] = (float) $rate;
    $pricing['hardcoverAlbumQuantity'] = $album_quantity;
    $pricing['hardcoverAmount']        = $amount;
    $pricing['totalPrice']             = $total;

    if ( $album_quantity > 0 ) {
        $pricing['unitPrice'] = round( $total / $album_quantity, 2 );
    }

    return $pricing;
}

/**
 * Build a cart row for hardcover binding when present on a quote.
 *
 * @param array<string,mixed> $quote    Quote payload.
 * @param array<string,mixed> $product  Product config.
 * @param string              $language Active language code.
 * @return array{key:string,value:string}|null
 */
function sfc_build_hardcover_cart_row( $quote, $product, $language ) {
    $pricing = (array) ( $quote['pricing'] ?? array() );
    $amount  = (float) ( $pricing['hardcoverAmount'] ?? 0 );
    if ( $amount <= 0 ) {
        return null;
    }

    $strings = sfc_get_product_strings( $product );

    return array(
        'key'   => $strings['hardcover_label'] ?? 'Tapa dura',
        'value' => sprintf(
            '$%s',
            number_format_i18n( $amount, 2 )
        ),
    );
}
