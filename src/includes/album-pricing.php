<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether a product config uses the hardcover album quote engine.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_is_album_product( $product ) {
    return 'album' === ( $product['jobType'] ?? '' );
}

/**
 * Count physical album page sheets to print for one album (duplex).
 *
 * @param int $pages Total pages per album.
 * @return int
 */
function sfc_album_physical_sheets_per_album( $pages ) {
    $pages = absint( $pages );
    if ( $pages <= 0 ) {
        return 0;
    }

    return (int) ceil( $pages / 2 );
}

/**
 * Total album page units to impose for a job.
 *
 * @param int $pages    Pages per album.
 * @param int $quantity Finished album quantity.
 * @return int
 */
function sfc_album_print_unit_quantity( $pages, $quantity ) {
    return sfc_album_physical_sheets_per_album( $pages ) * absint( $quantity );
}

/**
 * Normalize album calculator state.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Raw calculator state.
 * @return array<string,mixed>
 */
function sfc_normalize_album_state( $product, $state ) {
    $normalized = array(
        'size'              => sfc_resolve_product_size_key( $product, $state['size'] ?? ( $product['defaults']['size'] ?? '' ) ),
        'paper'             => sanitize_key( $state['paper'] ?? ( $product['defaults']['paper'] ?? '' ) ),
        'surface'           => sanitize_key( $state['surface'] ?? ( $product['defaults']['surface'] ?? '' ) ),
        'hardcover_finish'   => sanitize_key( $state['hardcover_finish'] ?? ( $product['defaults']['hardcover_finish'] ?? '' ) ),
        'quantity'          => ! empty( $product['emptyDefaultQuantity'] )
            ? ( isset( $state['quantity'] ) ? absint( $state['quantity'] ) : 0 )
            : absint( $state['quantity'] ?? ( $product['defaults']['quantity'] ?? 1 ) ),
        'pages'             => isset( $state['pages'] ) ? absint( $state['pages'] ) : 0,
    );

    if ( 'custom' === $normalized['size'] ) {
        $normalized['customWidthMm']  = isset( $state['customWidthMm'] ) ? (float) $state['customWidthMm'] : 0.0;
        $normalized['customLengthMm'] = isset( $state['customLengthMm'] ) ? (float) $state['customLengthMm'] : 0.0;
    }

    return $normalized;
}

/**
 * Calculate quote for a hardcover album product.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $state Calculator state.
 * @return array<string,mixed>|WP_Error
 */
function sfc_calculate_album_quote( $slug, $state ) {
    $product = sfc_get_product_config( $slug );
    if ( ! $product || ! sfc_is_album_product( $product ) ) {
        return new WP_Error( 'invalid_product', 'Producto desconocido.' );
    }

    if ( ! is_array( $state ) ) {
        return new WP_Error( 'invalid_state', 'La configuración de la calculadora no es válida.' );
    }

    $state    = sfc_normalize_album_state( $product, $state );
    $pages    = absint( $state['pages'] ?? 0 );
    $quantity = absint( $state['quantity'] ?? 0 );
    $min_pages = absint( $product['minPages'] ?? 2 );
    $max_pages = absint( $product['maxPages'] ?? 500 );

    if ( ! isset( $product['sizes'][ $state['size'] ] ) ) {
        return new WP_Error( 'invalid_size', 'Selección de dimensiones no válida.' );
    }

    $dimensions = sfc_resolve_product_dimensions( $product, $state );
    if ( is_wp_error( $dimensions ) ) {
        return $dimensions;
    }

    if ( $pages < $min_pages || $pages > $max_pages || 0 !== ( $pages % 2 ) ) {
        if ( 0 === $pages ) {
            return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
        }

        return new WP_Error( 'invalid_page_count', 'El número de páginas debe ser un múltiplo de 2.' );
    }

    $min_quantity = absint( $product['minQuantity'] ?? 1 );
    if ( $quantity < $min_quantity ) {
        if ( ! empty( $product['emptyDefaultQuantity'] ) && 0 === $quantity ) {
            return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
        }

        return new WP_Error( 'quantity_too_low', 'La cantidad está por debajo del mínimo para este producto.' );
    }

    if ( ! empty( $product['requireSelection'] ) && is_array( $product['requireSelection'] ) ) {
        foreach ( $product['requireSelection'] as $field ) {
            $field = sanitize_key( $field );
            if ( empty( $state[ $field ] ) ) {
                return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
            }
        }
    }

    $paper_key = sanitize_key( $state['paper'] ?? '' );
    if ( '' === $paper_key || ! isset( $product['papers'][ $paper_key ] ) ) {
        return new WP_Error( 'paper_required', 'Seleccione un tipo de papel.' );
    }

    $paper = sfc_resolve_product_paper( $product, $state );
    if ( 'coated' === $paper['paperType'] && ! empty( $product['surfaces'] ) ) {
        $surface = sanitize_key( $state['surface'] ?? '' );
        if ( '' === $surface || ! isset( $product['surfaces'][ $surface ] ) ) {
            return new WP_Error( 'surface_required', 'Seleccione mate o brillante para papel recubierto.' );
        }
    }

    $hardcover = sanitize_key( $state['hardcover_finish'] ?? '' );
    if ( ! isset( $product['hardcoverFinishes'][ $hardcover ] ) ) {
        return new WP_Error( 'invalid_hardcover_finish', 'Selección de acabado de tapa dura no válida.' );
    }

    $print_units = sfc_album_print_unit_quantity( $pages, $quantity );
    if ( $print_units <= 0 ) {
        return new WP_Error( 'invalid_page_count', 'El número de páginas no es válido para este producto.' );
    }

    $size_cfg   = $product['sizes'][ $state['size'] ];
    $imposition = sfc_calculate_sheet_imposition(
        $dimensions['widthMm'],
        $dimensions['heightMm'],
        $print_units,
        sfc_get_size_imposition_options( $size_cfg )
    );
    if ( is_wp_error( $imposition ) ) {
        return $imposition;
    }

    $pricing = sfc_calculate_job_price(
        sfc_build_product_pricing_args( $product, (int) $imposition['sheetQuantity'], $state )
    );
    if ( is_wp_error( $pricing ) ) {
        return $pricing;
    }

    $pricing = sfc_apply_hardcover_pricing( $product, $state, $pricing, $quantity );
    if ( is_wp_error( $pricing ) ) {
        return $pricing;
    }

    $pricing = sfc_apply_trade_pricing( $pricing, $quantity );

    $layout = sfc_build_sheet_layout_viz( $imposition );

    return array(
        'productSlug'   => $slug,
        'jobType'       => 'album',
        'state'         => $state,
        'size'          => array(
            'key'      => $dimensions['key'],
            'widthMm'  => (float) $dimensions['widthMm'],
            'heightMm' => (float) $dimensions['heightMm'],
            'label'    => $dimensions['label'],
        ),
        'album'         => array(
            'pages'                  => $pages,
            'physicalSheetsPerAlbum' => sfc_album_physical_sheets_per_album( $pages ),
            'printUnitQuantity'      => $print_units,
        ),
        'imposition'    => $imposition,
        'layoutViz'     => $layout,
        'pricing'       => $pricing,
        'currency'      => 'USD',
        'totalPrice'    => (float) $pricing['totalPrice'],
        'unitPrice'     => (float) $pricing['unitPrice'],
        'sheetQuantity' => (int) $imposition['sheetQuantity'],
        'unitQuantity'  => $quantity,
        'printMode'     => sfc_resolve_product_print_mode( $product, $state ),
        'paperType'     => $paper['paperType'],
        'paperGsm'      => $paper['gsm'],
    );
}
