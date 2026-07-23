<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether a product config uses the saddle-stitched booklet quote engine.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_is_booklet_product( $product ) {
    return 'booklet' === ( $product['jobType'] ?? '' );
}

/**
 * Cover page count (always four for this product).
 *
 * @return int
 */
function sfc_booklet_cover_page_count() {
    return 4;
}

/**
 * Pages on one flat/open signature sheet (inner or cover).
 *
 * @return int
 */
function sfc_booklet_pages_per_flat_sheet() {
    return sfc_booklet_cover_page_count();
}

/**
 * Count inner flat signature sheets to impose for a booklet job.
 *
 * Each flat sheet carries four inner pages; quantity is finished booklets.
 *
 * @param int $inner_pages Inner (tripa) page count per booklet.
 * @param int $quantity    Finished booklet quantity.
 * @return int
 */
function sfc_booklet_inner_flat_sheet_count( $inner_pages, $quantity ) {
    $inner_pages = absint( $inner_pages );
    $quantity    = absint( $quantity );

    if ( $inner_pages <= 0 || $quantity <= 0 ) {
        return 0;
    }

    return (int) ( ( $inner_pages / sfc_booklet_pages_per_flat_sheet() ) * $quantity );
}

/**
 * Count cover flat sheets to impose for a booklet job.
 *
 * One four-page cover flat per finished booklet.
 *
 * @param int $quantity Finished booklet quantity.
 * @return int
 */
function sfc_booklet_cover_flat_sheet_count( $quantity ) {
    return absint( $quantity );
}

/**
 * Press-sheet imposition for a booklet print run counted in flat signature sheets.
 *
 * @param float $width_mm         Flat unit width in millimeters.
 * @param float $height_mm        Flat unit height in millimeters.
 * @param int   $flat_sheet_count Flat signature sheets to print.
 * @return array<string,mixed>|WP_Error
 */
function sfc_calculate_booklet_run_imposition( $width_mm, $height_mm, $flat_sheet_count ) {
    return sfc_calculate_sheet_imposition( $width_mm, $height_mm, absint( $flat_sheet_count ) );
}

/**
 * Maximum nested signature sheets allowed for stapling.
 *
 * @return int
 */
function sfc_booklet_max_signature_sheets() {
    return 10;
}

/**
 * Maximum inner (body) page count given a separate four-page cover.
 *
 * @return int
 */
function sfc_booklet_max_inner_pages() {
    return ( sfc_booklet_max_signature_sheets() * 4 ) - sfc_booklet_cover_page_count();
}

/**
 * Numeric sort weight for cover-weight comparisons (bond = 0).
 *
 * @param array<string,mixed>|null $paper_cfg Paper config entry.
 * @return int
 */
function sfc_booklet_paper_sort_weight( $paper_cfg ) {
    if ( ! is_array( $paper_cfg ) ) {
        return -1;
    }

    if ( 'bond' === ( $paper_cfg['paperType'] ?? '' ) ) {
        return 0;
    }

    return absint( $paper_cfg['gsm'] ?? 0 );
}

/**
 * Allowed cover-weight keys for a selected inner paper.
 *
 * @param string              $inner_paper_key Inner paper key.
 * @param array<string,mixed> $papers          Product paper catalog.
 * @return string[]
 */
function sfc_get_booklet_cover_weight_keys( $inner_paper_key, $papers ) {
    $inner_key = sanitize_key( $inner_paper_key );
    $inner_cfg = $papers[ $inner_key ] ?? null;

    if ( ! is_array( $inner_cfg ) ) {
        return array();
    }

    $options     = array( 'same_as_inner' );
    $inner_weight = sfc_booklet_paper_sort_weight( $inner_cfg );

    foreach ( $papers as $key => $paper_cfg ) {
        if ( ! is_array( $paper_cfg ) || 'same_as_inner' === $key ) {
            continue;
        }

        $weight = sfc_booklet_paper_sort_weight( $paper_cfg );
        if ( $weight > $inner_weight && $weight <= 300 ) {
            $options[] = (string) $key;
        }
    }

    return array_values( array_unique( $options ) );
}

/**
 * Convert closed booklet dimensions to flat/open spread dimensions for imposition.
 *
 * @param float $width_mm  Closed width in millimeters.
 * @param float $height_mm Closed height in millimeters.
 * @return array{widthMm:float,heightMm:float}
 */
function sfc_booklet_closed_to_open_dimensions( $width_mm, $height_mm ) {
    return array(
        'widthMm'  => (float) $width_mm * 2,
        'heightMm' => (float) $height_mm,
    );
}

/**
 * Resolve flat/open booklet page dimensions from calculator state.
 *
 * When the product uses closed dimensions, customer-facing sizes are converted
 * to open spreads (width doubled) for imposition and pricing.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @return array<string,mixed>|WP_Error
 */
function sfc_resolve_booklet_dimensions( $product, $state ) {
    $dimensions = sfc_resolve_product_dimensions( $product, $state );
    if ( is_wp_error( $dimensions ) ) {
        return $dimensions;
    }

    if ( ! sfc_product_uses_closed_booklet_dimensions( $product ) ) {
        return $dimensions;
    }

    $closed_width  = (float) $dimensions['widthMm'];
    $closed_height = (float) $dimensions['heightMm'];
    $open          = sfc_booklet_closed_to_open_dimensions( $closed_width, $closed_height );
    $specs         = sfc_get_sheet_specs();

    if ( sfc_max_units_per_sheet(
        $open['widthMm'],
        $open['heightMm'],
        (int) $specs['printableWidthMm'],
        (int) $specs['printableHeightMm']
    ) <= 0 ) {
        return new WP_Error( 'does_not_fit', 'Las dimensiones seleccionadas no caben en el área imprimible.' );
    }

    $language = sfc_get_product_language( $product );

    if ( 'custom' === $dimensions['key'] ) {
        $label = sprintf(
            '%s × %s mm',
            number_format_i18n( $closed_width, 1 ),
            number_format_i18n( $closed_height, 1 )
        );
    } else {
        $label = $dimensions['label'];
    }

    return array(
        'key'            => $dimensions['key'],
        'widthMm'        => $open['widthMm'],
        'heightMm'       => $open['heightMm'],
        'closedWidthMm'  => $closed_width,
        'closedHeightMm' => $closed_height,
        'label'          => $label,
    );
}

/**
 * Resolve inner paper fields from calculator state.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @return array<string,mixed>
 */
function sfc_resolve_booklet_inner_paper( $product, $state ) {
    $paper_key = sanitize_key( $state['innerPaper'] ?? '' );
    $paper_cfg = $product['papers'][ $paper_key ] ?? null;

    if ( ! is_array( $paper_cfg ) ) {
        return array(
            'key'       => '',
            'paperType' => '',
            'gsm'       => null,
            'surface'   => null,
        );
    }

    $resolved = array(
        'key'       => $paper_key,
        'paperType' => sanitize_key( $paper_cfg['paperType'] ?? '' ),
        'gsm'       => isset( $paper_cfg['gsm'] ) ? absint( $paper_cfg['gsm'] ) : null,
        'surface'   => null,
    );

    if ( 'coated' === $resolved['paperType'] && ! empty( $product['surfaces'] ) ) {
        $resolved['surface'] = sanitize_key( $state['innerSurface'] ?? '' );
    }

    return $resolved;
}

/**
 * Resolve cover paper fields from calculator state and inner paper.
 *
 * @param array<string,mixed> $product     Product config.
 * @param array<string,mixed> $state       Calculator state.
 * @param array<string,mixed> $inner_paper Resolved inner paper.
 * @return array<string,mixed>
 */
function sfc_resolve_booklet_cover_paper( $product, $state, $inner_paper ) {
    $cover_weight = sanitize_key( $state['coverWeight'] ?? '' );

    if ( 'same_as_inner' === $cover_weight ) {
        return array(
            'key'       => $inner_paper['key'],
            'paperType' => $inner_paper['paperType'],
            'gsm'       => $inner_paper['gsm'],
            'surface'   => $inner_paper['surface'],
            'sameAsInner' => true,
        );
    }

    $paper_cfg = $product['papers'][ $cover_weight ] ?? null;
    if ( ! is_array( $paper_cfg ) ) {
        return array(
            'key'         => '',
            'paperType'   => '',
            'gsm'         => null,
            'surface'     => null,
            'sameAsInner' => false,
        );
    }

    $resolved = array(
        'key'         => $cover_weight,
        'paperType'   => sanitize_key( $paper_cfg['paperType'] ?? '' ),
        'gsm'         => isset( $paper_cfg['gsm'] ) ? absint( $paper_cfg['gsm'] ) : null,
        'surface'     => null,
        'sameAsInner' => false,
    );

    if ( 'coated' === $resolved['paperType'] && ! empty( $product['surfaces'] ) ) {
        $resolved['surface'] = sanitize_key( $state['coverSurface'] ?? '' );
    }

    return $resolved;
}

/**
 * Build pricing args for a booklet print run.
 *
 * @param array<string,mixed> $paper          Resolved paper fields.
 * @param int                 $sheet_quantity Press sheets.
 * @param string              $print_mode     Print mode key.
 * @return array<string,mixed>
 */
function sfc_build_booklet_pricing_args( $paper, $sheet_quantity, $print_mode ) {
    $args = array(
        'paperType' => $paper['paperType'] ?? '',
        'printMode' => sanitize_key( $print_mode ),
        'quantity'  => absint( $sheet_quantity ),
    );

    if ( 'coated' === $args['paperType'] ) {
        $args['gsm']     = absint( $paper['gsm'] ?? 0 );
        $args['surface'] = sanitize_key( $paper['surface'] ?? 'matte' );
    }

    return $args;
}

/**
 * Apply cover lamination based on cover finish and side selection.
 *
 * @param array<string,mixed> $state          Calculator state.
 * @param array<string,mixed> $pricing        Print pricing result.
 * @param int                 $sheet_quantity Cover press sheets.
 * @param string              $print_mode     Cover print mode.
 * @return array<string,mixed>|WP_Error
 */
function sfc_apply_booklet_cover_lamination( $state, $pricing, $sheet_quantity, $print_mode ) {
    if ( ! is_array( $pricing ) ) {
        return $pricing;
    }

    $finish = sanitize_key( $state['coverFinish'] ?? 'none' );
    if ( ! sfc_is_laminated_finish( $finish ) ) {
        return $pricing;
    }

    $sheet_quantity = absint( $sheet_quantity );
    if ( $sheet_quantity <= 0 ) {
        return $pricing;
    }

    $finish_sides = sanitize_key( $state['coverFinishSides'] ?? 'external' );
    $sides        = 'both' === $finish_sides
        ? sfc_get_lamination_sides_for_print_mode( $print_mode )
        : 1;

    $rate = sfc_get_lamination_price_per_sheet_side( $sheet_quantity );
    if ( is_wp_error( $rate ) ) {
        return $rate;
    }

    $print_total = (float) $pricing['totalPrice'];
    $lam_amount  = round( $sheet_quantity * $sides * (float) $rate, 2 );
    $total       = round( $print_total + $lam_amount, 2 );

    $pricing['printTotalPrice']         = $print_total;
    $pricing['laminationPricePerSide']  = (float) $rate;
    $pricing['laminationSidesPerSheet'] = $sides;
    $pricing['laminationSheetQuantity'] = $sheet_quantity;
    $pricing['laminationAmount']        = $lam_amount;
    $pricing['totalPrice']              = $total;
    $pricing['unitPrice']               = round( $total / $sheet_quantity, 2 );

    return $pricing;
}

/**
 * Merge inner and cover pricing results.
 *
 * @param array<string,mixed> $inner_pricing Inner run pricing.
 * @param array<string,mixed> $cover_pricing Cover run pricing.
 * @param int                 $total_sheets  Combined press sheets.
 * @return array<string,mixed>
 */
function sfc_merge_booklet_pricing( $inner_pricing, $cover_pricing, $total_sheets ) {
    $inner_total = (float) ( $inner_pricing['totalPrice'] ?? 0 );
    $cover_total = (float) ( $cover_pricing['totalPrice'] ?? 0 );
    $total       = round( $inner_total + $cover_total, 2 );

    return array(
        'totalPrice'      => $total,
        'unitPrice'       => $total_sheets > 0 ? round( $total / $total_sheets, 2 ) : 0.0,
        'innerPricing'    => $inner_pricing,
        'coverPricing'    => $cover_pricing,
        'innerTotalPrice' => $inner_total,
        'coverTotalPrice' => $cover_total,
        'printMode'       => $inner_pricing['printMode'] ?? '4x4',
    );
}

/**
 * Build booklet-specific warnings merged with imposition warnings.
 *
 * @param array<string,mixed> $product          Product config.
 * @param array<string,mixed> $inner_imposition Inner page imposition.
 * @param int                 $inner_pages      Inner page count.
 * @param int                 $signature_sheets Total nested signature sheets.
 * @return array<int,array<string,mixed>>
 */
function sfc_build_booklet_warnings( $product, $inner_imposition, $inner_pages, $signature_sheets ) {
    $warnings = is_array( $inner_imposition['warnings'] ?? null ) ? $inner_imposition['warnings'] : array();

    if ( sfc_product_suppresses_imposition_waste_ui( $product ) ) {
        $warnings = sfc_strip_flat_product_imposition_warnings( $warnings );
    }

    if ( $inner_pages % 4 !== 0 ) {
        $warnings[] = array(
            'code'     => 'inner_pages_not_multiple_of_four',
            'severity' => 'warning',
            'message'  => 'Las páginas de tripa deben ser múltiplo de cuatro para la encuadernación grapada.',
        );
    }

    if ( $signature_sheets > sfc_booklet_max_signature_sheets() ) {
        $warnings[] = array(
            'code'     => 'staple_limit_exceeded',
            'severity' => 'warning',
            'message'  => sprintf(
                'Este trabajo requiere %d hojas anidadas; el máximo recomendado para grapado es %d (40 páginas totales incluyendo portada).',
                $signature_sheets,
                sfc_booklet_max_signature_sheets()
            ),
        );
    }

    if ( (int) ( $inner_imposition['unitsPerSheet'] ?? 0 ) <= 0 ) {
        $warnings[] = array(
            'code'     => 'layout_impossible',
            'severity' => 'warning',
            'message'  => 'Las dimensiones seleccionadas no caben en el área imprimible.',
        );
    }

    return $warnings;
}

/**
 * Normalize booklet calculator state.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Raw state.
 * @return array<string,mixed>
 */
function sfc_normalize_booklet_state( $product, $state ) {
    $normalized = array(
        'size'             => sfc_resolve_product_size_key( $product, $state['size'] ?? '' ),
        'turnaround'       => sanitize_key( $state['turnaround'] ?? ( $product['defaults']['turnaround'] ?? '' ) ),
        'innerPaper'       => sanitize_key( $state['innerPaper'] ?? '' ),
        'coverWeight'      => sanitize_key( $state['coverWeight'] ?? '' ),
        'coverPrintMode'   => sanitize_key( $state['coverPrintMode'] ?? '4x0' ),
        'coverFinish'      => sanitize_key( $state['coverFinish'] ?? 'none' ),
        'coverFinishSides' => sanitize_key( $state['coverFinishSides'] ?? 'external' ),
    );

    $normalized['quantity']   = isset( $state['quantity'] ) ? absint( $state['quantity'] ) : 0;
    $normalized['innerPages'] = isset( $state['innerPages'] ) ? absint( $state['innerPages'] ) : 0;

    if ( 'custom' === $normalized['size'] ) {
        $normalized['customWidthMm']  = isset( $state['customWidthMm'] ) ? (float) $state['customWidthMm'] : 0.0;
        $normalized['customLengthMm'] = isset( $state['customLengthMm'] ) ? (float) $state['customLengthMm'] : 0.0;
    }

    if ( ! empty( $product['surfaces'] ) ) {
        $normalized['innerSurface'] = sanitize_key( $state['innerSurface'] ?? '' );
        $normalized['coverSurface'] = sanitize_key( $state['coverSurface'] ?? '' );
    }

    if ( ! empty( $product['turnaround'] ) ) {
        $available = sfc_get_product_turnaround_for_display( $product );
        if ( ! isset( $available[ $normalized['turnaround'] ] ) ) {
            $normalized['turnaround'] = (string) array_key_first( $available );
        }
    }

    return $normalized;
}

/**
 * Validate booklet calculator state.
 *
 * Thin wrapper over the quote resolver so validation and pricing cannot drift.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $state Calculator state.
 * @return true|WP_Error
 */
function sfc_validate_booklet_state( $slug, $state ) {
    $quote = sfc_calculate_booklet_quote( $slug, $state );
    return is_wp_error( $quote ) ? $quote : true;
}

/**
 * Calculate saddle-stitched booklet quote.
 *
 * Validation checks and quote assembly run in a single pass.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $state Calculator state.
 * @return array<string,mixed>|WP_Error
 */
function sfc_calculate_booklet_quote( $slug, $state ) {
    $product = sfc_get_product_config( $slug );
    if ( ! $product || ! sfc_is_booklet_product( $product ) ) {
        return new WP_Error( 'invalid_product', 'Producto desconocido.' );
    }

    if ( ! is_array( $state ) ) {
        return new WP_Error( 'invalid_state', 'La configuración de la calculadora no es válida.' );
    }

    $requested_turnaround = sanitize_key( $state['turnaround'] ?? '' );
    if ( isset( $product['turnaround'][ $requested_turnaround ] ) ) {
        $requested_option = $product['turnaround'][ $requested_turnaround ];
        if ( sfc_turnaround_requires_local_fulfillment( $requested_option ) && ! sfc_is_local_fulfillment_available( $product ) ) {
            return new WP_Error(
                'same_day_not_available',
                sprintf(
                    'El tiempo de entrega "%s" solo está disponible para retiro o entrega local en %s.',
                    $requested_option['label']['es'] ?? $requested_turnaround,
                    sfc_get_shop_city()
                )
            );
        }
    }

    $state    = sfc_normalize_booklet_state( $product, $state );
    $language = sfc_get_product_language( $product );

    if ( '' === $state['size'] ) {
        return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
    }

    $dimensions = sfc_resolve_booklet_dimensions( $product, $state );
    if ( is_wp_error( $dimensions ) ) {
        return $dimensions;
    }

    $min_quantity = absint( $product['minQuantity'] ?? 1 );
    if ( $state['quantity'] < $min_quantity ) {
        return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
    }

    $inner_pages = $state['innerPages'];
    if ( $inner_pages <= 0 ) {
        return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
    }

    if ( $inner_pages % 4 !== 0 ) {
        return new WP_Error( 'invalid_inner_pages', 'Las páginas de tripa deben ser múltiplo de cuatro.' );
    }

    if ( $inner_pages > sfc_booklet_max_inner_pages() ) {
        return new WP_Error(
            'inner_pages_too_high',
            sprintf(
                'Las páginas de tripa no pueden superar %d (máximo %d hojas grapadas incluyendo portada de 4 páginas).',
                sfc_booklet_max_inner_pages(),
                sfc_booklet_max_signature_sheets()
            )
        );
    }

    $inner_paper = sfc_resolve_booklet_inner_paper( $product, $state );
    if ( '' === $inner_paper['key'] || ! isset( $product['papers'][ $inner_paper['key'] ] ) ) {
        return new WP_Error( 'paper_required', 'Seleccione un peso de papel para la tripa.' );
    }

    if ( 'coated' === $inner_paper['paperType'] && ! empty( $product['surfaces'] ) ) {
        if ( '' === $inner_paper['surface'] || ! isset( $product['surfaces'][ $inner_paper['surface'] ] ) ) {
            return new WP_Error( 'surface_required', 'Seleccione mate o brillante para la tripa.' );
        }
    }

    $allowed_cover_weights = sfc_get_booklet_cover_weight_keys( $inner_paper['key'], $product['papers'] );
    if ( '' === $state['coverWeight'] || ! in_array( $state['coverWeight'], $allowed_cover_weights, true ) ) {
        return new WP_Error( 'invalid_cover_weight', 'Selección de peso de portada no válida.' );
    }

    $cover_paper = sfc_resolve_booklet_cover_paper( $product, $state, $inner_paper );
    if ( 'coated' === $cover_paper['paperType'] && ! empty( $product['surfaces'] ) ) {
        if ( '' === $cover_paper['surface'] || ! isset( $product['surfaces'][ $cover_paper['surface'] ] ) ) {
            return new WP_Error( 'surface_required', 'Seleccione mate o brillante para la portada.' );
        }
    }

    if ( ! isset( $product['coverPrintModes'][ $state['coverPrintMode'] ] ) ) {
        return new WP_Error( 'invalid_print_mode', 'Selección de caras de portada no válida.' );
    }

    if ( ! isset( $product['coverFinishes'][ $state['coverFinish'] ] ) ) {
        return new WP_Error( 'invalid_finish', 'Selección de acabado de portada no válida.' );
    }

    if ( sfc_is_laminated_finish( $state['coverFinish'] ) && ! empty( $product['coverFinishSides'] )
        && ! isset( $product['coverFinishSides'][ $state['coverFinishSides'] ] ) ) {
        return new WP_Error( 'invalid_finish_sides', 'Selección de lados de laminado no válida.' );
    }

    if ( ! isset( $product['turnaround'][ $state['turnaround'] ] ) ) {
        return new WP_Error( 'invalid_turnaround', 'Selección de tiempo de entrega no válida.' );
    }

    $signature_sheets = (int) ( $inner_pages / 4 ) + 1;
    if ( $signature_sheets > sfc_booklet_max_signature_sheets() ) {
        return new WP_Error(
            'staple_limit_exceeded',
            sprintf(
                'Este trabajo requiere %d hojas anidadas; el máximo es %d (40 páginas totales incluyendo portada).',
                $signature_sheets,
                sfc_booklet_max_signature_sheets()
            )
        );
    }

    $quantity   = $state['quantity'];
    $cover_mode = sanitize_key( $state['coverPrintMode'] );

    $inner_flat_sheets = sfc_booklet_inner_flat_sheet_count( $inner_pages, $quantity );
    $cover_flat_sheets = sfc_booklet_cover_flat_sheet_count( $quantity );

    $inner_imposition = sfc_calculate_booklet_run_imposition(
        $dimensions['widthMm'],
        $dimensions['heightMm'],
        $inner_flat_sheets
    );

    if ( is_wp_error( $inner_imposition ) ) {
        return $inner_imposition;
    }

    $cover_imposition = sfc_calculate_booklet_run_imposition(
        $dimensions['widthMm'],
        $dimensions['heightMm'],
        $cover_flat_sheets
    );

    if ( is_wp_error( $cover_imposition ) ) {
        return $cover_imposition;
    }

    $inner_print_mode = sanitize_key( $product['innerPrintMode'] ?? '4x4' );
    $inner_pricing    = sfc_calculate_job_price(
        sfc_build_booklet_pricing_args( $inner_paper, (int) $inner_imposition['sheetQuantity'], $inner_print_mode )
    );

    if ( is_wp_error( $inner_pricing ) ) {
        return $inner_pricing;
    }

    $cover_pricing = sfc_calculate_job_price(
        sfc_build_booklet_pricing_args( $cover_paper, (int) $cover_imposition['sheetQuantity'], $cover_mode )
    );

    if ( is_wp_error( $cover_pricing ) ) {
        return $cover_pricing;
    }

    $cover_pricing = sfc_apply_booklet_cover_lamination(
        $state,
        $cover_pricing,
        (int) $cover_imposition['sheetQuantity'],
        $cover_mode
    );

    if ( is_wp_error( $cover_pricing ) ) {
        return $cover_pricing;
    }

    $total_sheets = (int) $inner_imposition['sheetQuantity'] + (int) $cover_imposition['sheetQuantity'];
    $pricing      = sfc_merge_booklet_pricing( $inner_pricing, $cover_pricing, $total_sheets );

    $job_services = (array) ( $product['jobServices'] ?? array( 'stapling' ) );
    $pricing      = sfc_apply_job_service_pricing( $pricing, $total_sheets, $job_services );

    $pricing = sfc_apply_turnaround_surcharge( $product, $state, $pricing );
    $pricing = sfc_apply_trade_pricing( $pricing, $total_sheets );

    if ( sfc_product_suppresses_imposition_waste_ui( $product ) ) {
        $inner_imposition['warnings'] = sfc_strip_flat_product_imposition_warnings( $inner_imposition['warnings'] ?? array() );
        $cover_imposition['warnings'] = sfc_strip_flat_product_imposition_warnings( $cover_imposition['warnings'] ?? array() );
    }

    $layout_inner = sfc_build_sheet_layout_viz( $inner_imposition );
    $warnings     = sfc_build_booklet_warnings(
        $product,
        $inner_imposition,
        $inner_pages,
        $signature_sheets
    );

    if ( ! empty( $cover_imposition['warnings'] ) ) {
        $cover_warnings = $cover_imposition['warnings'];
        if ( sfc_product_suppresses_imposition_waste_ui( $product ) ) {
            $cover_warnings = sfc_strip_flat_product_imposition_warnings( $cover_warnings );
        }
        $warnings = array_merge( $warnings, $cover_warnings );
    }

    $layout_inner['warnings'] = $warnings;
    $layout_inner['captionKey'] = 'layout_inner_caption';

    return array(
        'productSlug'      => $slug,
        'jobType'          => 'booklet',
        'state'            => $state,
        'size'             => $dimensions,
        'booklet'          => array(
            'innerPages'         => $inner_pages,
            'coverPages'         => sfc_booklet_cover_page_count(),
            'totalPages'         => $inner_pages + sfc_booklet_cover_page_count(),
            'signatureSheets'    => $signature_sheets,
            'innerFlatSheets'    => $inner_flat_sheets,
            'coverFlatSheets'    => $cover_flat_sheets,
            'innerPrintMode'     => $inner_print_mode,
            'coverPrintMode'     => $cover_mode,
        ),
        'imposition'       => $inner_imposition,
        'coverImposition'  => $cover_imposition,
        'layoutViz'        => $layout_inner,
        'pricing'          => $pricing,
        'currency'         => 'USD',
        'totalPrice'       => (float) $pricing['totalPrice'],
        'unitPrice'        => (float) $pricing['unitPrice'],
        'sheetQuantity'    => $total_sheets,
        'innerSheetQuantity' => (int) $inner_imposition['sheetQuantity'],
        'coverSheetQuantity' => (int) $cover_imposition['sheetQuantity'],
        'unitQuantity'     => $quantity,
        'innerPaperLabel'  => sfc_get_booklet_paper_label( $product, $inner_paper, $language ),
        'coverPaperLabel'  => sfc_get_booklet_paper_label( $product, $cover_paper, $language ),
    );
}

/**
 * Localized label for a resolved booklet paper.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $paper   Resolved paper fields.
 * @param string              $language Language code.
 * @return string
 */
function sfc_get_booklet_paper_label( $product, $paper, $language ) {
    if ( ! empty( $paper['sameAsInner'] ) ) {
        $strings = sfc_get_product_strings( $product );
        return $strings['cover_same_as_inner'] ?? 'Igual tripa';
    }

    $key = $paper['key'] ?? '';
    if ( isset( $product['papers'][ $key ]['label'][ $language ] ) ) {
        return $product['papers'][ $key ]['label'][ $language ];
    }

    return $key;
}

/**
 * Build cart summary for a booklet quote.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $quote Quote payload.
 * @return string
 */
function sfc_build_booklet_cart_summary( $slug, $quote ) {
    $product   = sfc_get_product_config( $slug );
    $strings   = sfc_get_product_strings( $product );
    $size_label = $quote['size']['label'] ?? '';

    return sprintf(
        '%s × %s — %s: $%s',
        number_format_i18n( (int) $quote['unitQuantity'] ),
        $size_label,
        $strings['total_label'] ?? 'Total',
        number_format( (float) $quote['totalPrice'], 2, '.', '' )
    );
}
