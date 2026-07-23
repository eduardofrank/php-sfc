<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build frontend bootstrap data for a product calculator.
 *
 * @param string $slug Product slug.
 * @return array<string,mixed>|WP_Error
 */
function sfc_build_product_js_data( $slug ) {
    $product = sfc_get_product_config( $slug );
    if ( ! $product ) {
        return new WP_Error( 'invalid_product', 'Unknown product.' );
    }

    if ( sfc_is_booklet_product( $product ) ) {
        return sfc_build_booklet_js_data( $slug, $product );
    }

    if ( sfc_is_album_product( $product ) ) {
        return sfc_build_album_js_data( $slug, $product );
    }

    $language = sfc_get_product_language( $product );
    $strings  = array_merge(
        sfc_get_calculator_ui_strings( $language ),
        sfc_get_product_strings( $product )
    );
    $sizes    = array();
    $seed     = sfc_get_calculator_seed_for_product( $slug, $language );

    $sheet_specs = sfc_get_sheet_specs();

    foreach ( $product['sizes'] as $key => $size ) {
        if ( 'custom' === $key ) {
            $units_per_sheet = 0;
        } elseif ( isset( $size['unitsPerSheet'] ) ) {
            $units_per_sheet = (int) $size['unitsPerSheet'];
        } else {
            $units_per_sheet = sfc_max_units_per_sheet(
                (float) $size['widthMm'],
                (float) $size['heightMm'],
                (int) $sheet_specs['printableWidthMm'],
                (int) $sheet_specs['printableHeightMm']
            );
        }

        $sizes[ $key ] = array(
            'key'           => $key,
            'label'         => $size['label'][ $language ] ?? $key,
            'widthMm'       => (float) $size['widthMm'],
            'heightMm'      => (float) $size['heightMm'],
            'unitsPerSheet' => $units_per_sheet,
        );
    }

    $finishes = array();
    foreach ( (array) ( $product['finishes'] ?? array() ) as $key => $finish ) {
        $finishes[ $key ] = array(
            'key'   => $key,
            'label' => $finish['label'][ $language ] ?? $key,
        );
    }

    $turnaround = array();
    foreach ( sfc_get_product_turnaround_for_display( $product ) as $key => $option ) {
        $turnaround[ $key ] = array(
            'key'   => $key,
            'label' => $option['label'][ $language ] ?? $key,
        );
    }

    $surfaces = array();
    foreach ( (array) ( $product['surfaces'] ?? array() ) as $key => $surface ) {
        $surfaces[ $key ] = array(
            'key'   => $key,
            'label' => $surface['label'][ $language ] ?? $key,
        );
    }

    $print_modes = array();
    foreach ( (array) ( $product['printModes'] ?? array() ) as $key => $mode ) {
        $print_modes[ $key ] = array(
            'key'   => $key,
            'label' => $mode['label'][ $language ] ?? $key,
        );
    }

    $papers = array();
    foreach ( (array) ( $product['papers'] ?? array() ) as $key => $paper ) {
        $papers[ $key ] = array(
            'key'       => $key,
            'label'     => $paper['label'][ $language ] ?? $key,
            'paperType' => sanitize_key( $paper['paperType'] ?? '' ),
        );
    }

    $die_cut_shapes = array();
    foreach ( (array) ( $product['dieCutShapes'] ?? array() ) as $key => $shape ) {
        $die_cut_shapes[ $key ] = array(
            'key'   => $key,
            'label' => $shape['label'][ $language ] ?? $key,
        );
    }

    return array(
        'productSlug'   => $slug,
        'language'      => $language,
        'units'         => sfc_get_units(),
        'strings'       => $strings,
        'defaults'      => $product['defaults'],
        'minQuantity'   => (int) $product['minQuantity'],
        'emptyDefaultQuantity' => ! empty( $product['emptyDefaultQuantity'] ),
        'printMode'     => sfc_get_product_print_mode( $product ),
        'paperLabel'    => sfc_get_product_paper_label( $product ),
        'sizes'         => $sizes,
        'surfaces'      => $surfaces,
        'printModes'    => $print_modes,
        'papers'        => $papers,
        'dieCutShapes'  => $die_cut_shapes,
        'finishes'      => $finishes,
        'requiredSelections' => array_values( (array) ( $product['requireSelection'] ?? array() ) ),
        'steps'         => sfc_build_product_steps( $product ),
        'seedState'     => $seed ? $seed['state'] : null,
        'seedNotice'    => $seed ? $seed['notice'] : null,
        'turnaround'    => $turnaround,
        'customDimensionLimits' => (array) ( $product['customDimensionLimits'] ?? array() ),
        'circularDimensionLimits' => (array) ( $product['circularDimensionLimits'] ?? array() ),
        'suppressImpositionWasteUi' => sfc_product_suppresses_imposition_waste_ui( $product ),
        'sheetSpecs'    => sfc_get_sheet_specs(),
        'localFulfillmentAvailable' => sfc_is_local_fulfillment_available( $product ),
        'shopCity'      => sfc_get_shop_city(),
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'sfc_public_nonce' ),
        'wooProductId'  => sfc_get_product_woo_id( $slug ),
    );
}

/**
 * Build frontend bootstrap data for an album calculator.
 *
 * @param string              $slug    Product slug.
 * @param array<string,mixed> $product Product config.
 * @return array<string,mixed>
 */
function sfc_build_album_js_data( $slug, $product ) {
    $language = sfc_get_product_language( $product );
    $strings  = array_merge(
        sfc_get_calculator_ui_strings( $language ),
        sfc_get_product_strings( $product )
    );
    $strings['pages_help'] = str_replace(
        '{min}',
        (string) absint( $product['minPages'] ?? 2 ),
        $strings['pages_help'] ?? ''
    );
    $seed        = sfc_get_calculator_seed_for_product( $slug, $language );
    $sheet_specs = sfc_get_sheet_specs();
    $sizes       = array();

    foreach ( $product['sizes'] as $key => $size ) {
        if ( 'custom' === $key ) {
            $units_per_sheet = 0;
        } elseif ( isset( $size['unitsPerSheet'] ) ) {
            $units_per_sheet = (int) $size['unitsPerSheet'];
        } else {
            $units_per_sheet = sfc_max_units_per_sheet(
                (float) $size['widthMm'],
                (float) $size['heightMm'],
                (int) $sheet_specs['printableWidthMm'],
                (int) $sheet_specs['printableHeightMm']
            );
        }

        $sizes[ $key ] = array(
            'key'           => $key,
            'label'         => $size['label'][ $language ] ?? $key,
            'widthMm'       => (float) $size['widthMm'],
            'heightMm'      => (float) $size['heightMm'],
            'unitsPerSheet' => $units_per_sheet,
        );
    }

    $surfaces = array();
    foreach ( (array) ( $product['surfaces'] ?? array() ) as $key => $surface ) {
        $surfaces[ $key ] = array(
            'key'   => $key,
            'label' => $surface['label'][ $language ] ?? $key,
        );
    }

    $papers = array();
    foreach ( (array) ( $product['papers'] ?? array() ) as $key => $paper ) {
        $papers[ $key ] = array(
            'key'       => $key,
            'label'     => $paper['label'][ $language ] ?? $key,
            'paperType' => sanitize_key( $paper['paperType'] ?? '' ),
        );
    }

    $hardcover_finishes = array();
    foreach ( (array) ( $product['hardcoverFinishes'] ?? array() ) as $key => $finish ) {
        $hardcover_finishes[ $key ] = array(
            'key'   => $key,
            'label' => $finish['label'][ $language ] ?? $key,
        );
    }

    return array(
        'productSlug'               => $slug,
        'jobType'                   => 'album',
        'language'                  => $language,
        'units'                     => sfc_get_units(),
        'strings'                   => $strings,
        'defaults'                  => $product['defaults'],
        'minQuantity'               => (int) $product['minQuantity'],
        'minPages'                  => absint( $product['minPages'] ?? 2 ),
        'maxPages'                  => absint( $product['maxPages'] ?? 500 ),
        'emptyDefaultQuantity'      => ! empty( $product['emptyDefaultQuantity'] ),
        'printMode'                 => sfc_get_product_print_mode( $product ),
        'sizes'                     => $sizes,
        'surfaces'                  => $surfaces,
        'papers'                    => $papers,
        'hardcoverFinishes'         => $hardcover_finishes,
        'finishes'                  => array(),
        'printModes'                => array(),
        'turnaround'                => array(),
        'requiredSelections'        => array_values( (array) ( $product['requireSelection'] ?? array() ) ),
        'steps'                     => sfc_build_product_steps( $product ),
        'seedState'                 => $seed ? $seed['state'] : null,
        'seedNotice'                => $seed ? $seed['notice'] : null,
        'customDimensionLimits'     => (array) ( $product['customDimensionLimits'] ?? array() ),
        'suppressImpositionWasteUi' => sfc_product_suppresses_imposition_waste_ui( $product ),
        'sheetSpecs'                => sfc_get_sheet_specs(),
        'localFulfillmentAvailable' => sfc_is_local_fulfillment_available( $product ),
        'shopCity'                  => sfc_get_shop_city(),
        'ajaxUrl'                   => admin_url( 'admin-ajax.php' ),
        'nonce'                     => wp_create_nonce( 'sfc_public_nonce' ),
        'wooProductId'              => sfc_get_product_woo_id( $slug ),
    );
}

/**
 * Shared calculator UI strings merged into every product bootstrap payload.
 *
 * @param string $language Active language code.
 * @return array<string,string>
 */
function sfc_get_calculator_ui_strings( $language ) {
    $language = in_array( $language, array( 'en', 'es' ), true ) ? $language : 'es';
    $catalog  = array(
        'imposition_warnings_title' => array(
            'en' => 'Layout notices',
            'es' => 'Avisos de montaje',
        ),
        'size_custom_dimensions_help' => array(
            'en' => 'Enter both dimensions in millimeters. Dimension 1 and Dimension 2 can be entered in either order; the layout adjusts automatically on press.',
            'es' => 'Ingrese ambas dimensiones en milímetros. Dimensión 1 y Dimensión 2 pueden ingresarse en cualquier orden; el montaje se ajusta automáticamente en la hoja.',
        ),
        'step_custom_dimension_mm_hint' => array(
            'en' => 'In millimeters',
            'es' => 'En milímetros',
        ),
        'layout_press_orientation' => array(
            'en' => 'On-press layout: {width} × {length} mm',
            'es' => 'Montaje en hoja: {width} × {length} mm',
        ),
        'summary_press_orientation_label' => array(
            'en' => 'On-press layout',
            'es' => 'Montaje en hoja',
        ),
        'save_quote' => array(
            'en' => 'Save quote',
            'es' => 'Guardar cotización',
        ),
        'saving_quote' => array(
            'en' => 'Saving…',
            'es' => 'Guardando…',
        ),
        'quote_saved_share' => array(
            'en' => 'Share this link — it reopens this configuration at current prices:',
            'es' => 'Comparta este enlace — abre esta configuración con precios actuales:',
        ),
        'quote_saved_copied' => array(
            'en' => 'Link copied to clipboard.',
            'es' => 'Enlace copiado al portapapeles.',
        ),
        'quote_save_error' => array(
            'en' => 'Could not save the quote.',
            'es' => 'No se pudo guardar la cotización.',
        ),
        'trade_list_price_label' => array(
            'en' => 'List price',
            'es' => 'Precio de lista',
        ),
        'trade_discount_label' => array(
            'en' => 'Trade discount',
            'es' => 'Descuento mayorista',
        ),
        'artwork_label' => array(
            'en' => 'Artwork file (optional)',
            'es' => 'Arte / archivo de diseño (opcional)',
        ),
        'artwork_select' => array(
            'en' => 'Select PDF/X file…',
            'es' => 'Seleccionar archivo PDF/X…',
        ),
        'artwork_help' => array(
            'en' => 'PDF/X only (export with the PDF/X-1a or PDF/X-4 preset from your design software), up to 50 MB. You can also send it after ordering.',
            'es' => 'Solo PDF/X (exporte con el preajuste PDF/X-1a o PDF/X-4 desde su programa de diseño), hasta 50 MB. También puede enviarlo después de ordenar.',
        ),
        'artwork_uploading' => array(
            'en' => 'Uploading…',
            'es' => 'Subiendo…',
        ),
        'artwork_remove' => array(
            'en' => 'Remove',
            'es' => 'Quitar',
        ),
        'artwork_upload_error' => array(
            'en' => 'Could not upload the file.',
            'es' => 'No se pudo subir el archivo.',
        ),
        'artwork_type_error' => array(
            'en' => 'Format not allowed. Upload a PDF/X file.',
            'es' => 'Formato no permitido. Suba un archivo PDF/X.',
        ),
        'artwork_cart_hint' => array(
            'en' => 'Add to cart first — you can upload your print-ready file on the cart page.',
            'es' => 'Primero agregue al carrito — podrá subir su archivo listo para imprimir en la página del carrito.',
        ),
    );
    // When the PDF/X requirement is switched off, the artwork copy must ask
    // for a plain PDF instead.
    if ( function_exists( 'sfc_artwork_requires_pdfx' ) && ! sfc_artwork_requires_pdfx() ) {
        $catalog['artwork_select'] = array(
            'en' => 'Select PDF file…',
            'es' => 'Seleccionar archivo PDF…',
        );
        $catalog['artwork_help'] = array(
            'en' => 'PDF only, up to 50 MB. You can also send it after ordering.',
            'es' => 'Solo PDF, hasta 50 MB. También puede enviarlo después de ordenar.',
        );
        $catalog['artwork_type_error'] = array(
            'en' => 'Format not allowed. Upload a PDF file.',
            'es' => 'Formato no permitido. Suba un archivo PDF.',
        );
    }

    $strings = array();

    foreach ( $catalog as $key => $translations ) {
        $strings[ $key ] = $translations[ $language ] ?? $translations['en'] ?? $key;
    }

    return $strings;
}

/**
 * Build frontend bootstrap data for a booklet product calculator.
 *
 * @param string              $slug    Product slug.
 * @param array<string,mixed> $product Product config.
 * @return array<string,mixed>
 */
function sfc_build_booklet_js_data( $slug, $product ) {
    $language = sfc_get_product_language( $product );
    $seed     = sfc_get_calculator_seed_for_product( $slug, $language );
    $strings  = array_merge(
        sfc_get_calculator_ui_strings( $language ),
        sfc_get_product_strings( $product )
    );
    $strings['inner_pages_help'] = str_replace(
        '{max}',
        (string) sfc_booklet_max_inner_pages(),
        $strings['inner_pages_help'] ?? ''
    );

    $sheet_specs = sfc_get_sheet_specs();
    $sizes       = array();

    foreach ( $product['sizes'] as $key => $size ) {
        $units_per_sheet = 0;
        if ( 'custom' !== $key ) {
            $units_per_sheet = sfc_max_units_per_sheet(
                (float) $size['widthMm'],
                (float) $size['heightMm'],
                (int) $sheet_specs['printableWidthMm'],
                (int) $sheet_specs['printableHeightMm']
            );
        }

        $sizes[ $key ] = array(
            'key'           => $key,
            'label'         => $size['label'][ $language ] ?? $key,
            'widthMm'       => (float) $size['widthMm'],
            'heightMm'      => (float) $size['heightMm'],
            'unitsPerSheet' => $units_per_sheet,
        );
    }

    $papers = array();
    foreach ( (array) ( $product['papers'] ?? array() ) as $key => $paper ) {
        $papers[ $key ] = array(
            'key'       => $key,
            'label'     => $paper['label'][ $language ] ?? $key,
            'paperType' => sanitize_key( $paper['paperType'] ?? '' ),
            'gsm'       => isset( $paper['gsm'] ) ? absint( $paper['gsm'] ) : null,
        );
    }

    $inner_papers = array();
    foreach ( (array) ( $product['innerPapers'] ?? array() ) as $paper_key ) {
        if ( isset( $papers[ $paper_key ] ) ) {
            $inner_papers[ $paper_key ] = $papers[ $paper_key ];
        }
    }

    $surfaces = array();
    foreach ( (array) ( $product['surfaces'] ?? array() ) as $key => $surface ) {
        $surfaces[ $key ] = array(
            'key'   => $key,
            'label' => $surface['label'][ $language ] ?? $key,
        );
    }

    $cover_print_modes = array();
    foreach ( (array) ( $product['coverPrintModes'] ?? array() ) as $key => $mode ) {
        $cover_print_modes[ $key ] = array(
            'key'   => $key,
            'label' => $mode['label'][ $language ] ?? $key,
        );
    }

    $cover_finishes = array();
    foreach ( (array) ( $product['coverFinishes'] ?? array() ) as $key => $finish ) {
        $cover_finishes[ $key ] = array(
            'key'   => $key,
            'label' => $finish['label'][ $language ] ?? $key,
        );
    }

    $cover_finish_sides = array();
    foreach ( (array) ( $product['coverFinishSides'] ?? array() ) as $key => $side ) {
        $cover_finish_sides[ $key ] = array(
            'key'   => $key,
            'label' => $side['label'][ $language ] ?? $key,
        );
    }

    $turnaround = array();
    foreach ( sfc_get_product_turnaround_for_display( $product ) as $key => $option ) {
        $turnaround[ $key ] = array(
            'key'   => $key,
            'label' => $option['label'][ $language ] ?? $key,
        );
    }

    return array(
        'productSlug'            => $slug,
        'jobType'                => 'booklet',
        'language'               => $language,
        'units'                  => sfc_get_units(),
        'strings'                => $strings,
        'defaults'               => $product['defaults'],
        'minQuantity'            => (int) $product['minQuantity'],
        'emptyDefaultQuantity'   => ! empty( $product['emptyDefaultQuantity'] ),
        'sizes'                  => $sizes,
        'papers'                 => $papers,
        'innerPapers'            => $inner_papers,
        'surfaces'               => $surfaces,
        'coverPrintModes'        => $cover_print_modes,
        'coverFinishes'          => $cover_finishes,
        'coverFinishSides'       => $cover_finish_sides,
        'coverWeights'           => sfc_build_booklet_cover_weight_options( $product, $language ),
        'steps'                  => sfc_build_product_steps( $product ),
        'seedState'              => $seed ? $seed['state'] : null,
        'seedNotice'             => $seed ? $seed['notice'] : null,
        'turnaround'             => $turnaround,
        'bookletLimits'          => array(
            'maxInnerPages'      => sfc_booklet_max_inner_pages(),
            'coverPages'         => sfc_booklet_cover_page_count(),
            'pageStep'           => 4,
            'maxSignatureSheets' => sfc_booklet_max_signature_sheets(),
        ),
        'customDimensionLimits'  => (array) ( $product['customDimensionLimits'] ?? array() ),
        'suppressImpositionWasteUi' => sfc_product_suppresses_imposition_waste_ui( $product ),
        'coverSameAsInnerLabel'  => $strings['cover_same_as_inner'] ?? 'Igual tripa',
        'sheetSpecs'             => sfc_get_sheet_specs(),
        'localFulfillmentAvailable' => sfc_is_local_fulfillment_available( $product ),
        'shopCity'               => sfc_get_shop_city(),
        'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
        'nonce'                  => wp_create_nonce( 'sfc_public_nonce' ),
        'wooProductId'           => sfc_get_product_woo_id( $slug ),
    );
}
