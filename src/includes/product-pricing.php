<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build imposition options from a product size config.
 *
 * @param array<string,mixed> $size_cfg Size config.
 * @return array<string,mixed>
 */
function sfc_get_size_imposition_options( $size_cfg ) {
    $options = array();

    if ( ! empty( $size_cfg['unitsPerSheet'] ) ) {
        $options['unitsPerSheet'] = (int) $size_cfg['unitsPerSheet'];
    }
    if ( ! empty( $size_cfg['impositionCols'] ) ) {
        $options['impositionCols'] = (int) $size_cfg['impositionCols'];
    }
    if ( ! empty( $size_cfg['impositionRows'] ) ) {
        $options['impositionRows'] = (int) $size_cfg['impositionRows'];
    }

    return $options;
}

/**
 * Resolve the imposition for a size, minimizing press sheets.
 *
 * A size may declare 'impositionCandidates' (a list of imposition option sets).
 * Each is evaluated for the ordered quantity and the one needing the fewest
 * press sheets wins; ties are broken toward the earliest-listed candidate (the
 * tidier layout). A denser candidate never costs more sheets, so it only wins
 * when it actually saves paper — and the whole job uses that single imposition.
 * Sizes without candidates fall back to their single imposition options.
 *
 * @param float               $width_mm  Unit width in millimeters.
 * @param float               $height_mm Unit height in millimeters.
 * @param int                 $quantity  Ordered unit quantity.
 * @param array<string,mixed> $size_cfg  Product size config.
 * @return array<string,mixed>|WP_Error
 */
function sfc_resolve_size_imposition( $width_mm, $height_mm, $quantity, $size_cfg ) {
    $candidates = ( isset( $size_cfg['impositionCandidates'] ) && is_array( $size_cfg['impositionCandidates'] ) )
        ? $size_cfg['impositionCandidates']
        : array( $size_cfg );

    $best = null;
    foreach ( $candidates as $candidate ) {
        $imposition = sfc_calculate_sheet_imposition(
            $width_mm,
            $height_mm,
            $quantity,
            sfc_get_size_imposition_options( (array) $candidate )
        );
        if ( is_wp_error( $imposition ) ) {
            continue;
        }
        // Strictly fewer sheets wins; an equal count keeps the earlier candidate.
        if ( null === $best || (int) $imposition['sheetQuantity'] < (int) $best['sheetQuantity'] ) {
            $best = $imposition;
        }
    }

    if ( null !== $best ) {
        return $best;
    }

    return sfc_calculate_sheet_imposition(
        $width_mm,
        $height_mm,
        $quantity,
        sfc_get_size_imposition_options( (array) $size_cfg )
    );
}

/**
 * Resolve print mode from calculator state.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @return string
 */
function sfc_resolve_product_print_mode( $product, $state ) {
    $print_modes = ( isset( $product['printModes'] ) && is_array( $product['printModes'] ) )
        ? $product['printModes']
        : array();

    $allowed = $print_modes;
    if ( ! empty( $product['printModesByPaper'] ) && is_array( $product['printModesByPaper'] ) ) {
        $paper_key = sanitize_key( $state['paper'] ?? '' );
        $mode_keys = ( isset( $product['printModesByPaper'][ $paper_key ] ) && is_array( $product['printModesByPaper'][ $paper_key ] ) )
            ? $product['printModesByPaper'][ $paper_key ]
            : array();
        $subset    = array();
        foreach ( $mode_keys as $mode_key ) {
            $mode_key = sanitize_key( $mode_key );
            if ( isset( $print_modes[ $mode_key ] ) ) {
                $subset[ $mode_key ] = $print_modes[ $mode_key ];
            }
        }
        if ( $subset ) {
            $allowed = $subset;
        }
    }

    if ( $allowed ) {
        $mode = sanitize_key( $state['printMode'] ?? '' );
        if ( isset( $allowed[ $mode ] ) ) {
            return $mode;
        }

        if ( 1 === count( $allowed ) ) {
            return (string) array_key_first( $allowed );
        }

        $default = sanitize_key( $product['defaults']['printMode'] ?? '4x0' );
        return isset( $allowed[ $default ] ) ? $default : (string) array_key_first( $allowed );
    }

    return sfc_get_product_print_mode( $product );
}

/**
 * Resolve paper pricing fields from product config and calculator state.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @return array<string,mixed>
 */
function sfc_resolve_product_paper( $product, $state ) {
    if ( ! empty( $product['papers'] ) && is_array( $product['papers'] ) ) {
        $paper_key = sanitize_key( $state['paper'] ?? '' );
        $paper_cfg = $product['papers'][ $paper_key ] ?? null;

        if ( ! is_array( $paper_cfg ) ) {
            return array(
                'paperType' => '',
                'gsm'       => null,
                'surface'   => null,
            );
        }

        $resolved = array(
            'paperType' => sanitize_key( $paper_cfg['paperType'] ?? '' ),
            'gsm'       => isset( $paper_cfg['gsm'] ) ? absint( $paper_cfg['gsm'] ) : null,
            'surface'   => isset( $paper_cfg['surface'] ) ? sanitize_key( $paper_cfg['surface'] ) : null,
        );

        if ( 'coated' === $resolved['paperType'] && null === $resolved['surface'] && ! empty( $product['surfaces'] ) ) {
            $resolved['surface'] = sanitize_key( $state['surface'] ?? '' );
        }

        return $resolved;
    }

    $resolved = array(
        'paperType' => sanitize_key( $product['paperType'] ?? '' ),
        'gsm'       => ! empty( $product['gsm'] ) ? absint( $product['gsm'] ) : null,
        'surface'   => null,
    );

    if ( 'coated' === $resolved['paperType'] ) {
        $resolved['surface'] = sanitize_key( $state['surface'] ?? ( $product['defaults']['surface'] ?? 'matte' ) );
    }

    return $resolved;
}

/**
 * Build pricing arguments for a configured product job.
 *
 * @param array<string,mixed> $product        Product config.
 * @param int                 $sheet_quantity Press sheets required.
 * @param array<string,mixed> $state          Calculator state.
 * @return array<string,mixed>
 */
function sfc_build_product_pricing_args( $product, $sheet_quantity, $state ) {
    $paper = sfc_resolve_product_paper( $product, $state );

    $args = array(
        'paperType' => $paper['paperType'],
        'printMode' => sfc_resolve_product_print_mode( $product, $state ),
        'quantity'  => absint( $sheet_quantity ),
    );

    if ( 'coated' === $args['paperType'] ) {
        $args['gsm']     = absint( $paper['gsm'] ?? 0 );
        $args['surface'] = sanitize_key( $paper['surface'] ?? 'matte' );
    }

    return $args;
}

/**
 * Resolve a product size key against the configured size map.
 *
 * Size keys may contain characters that sanitize_key() strips (e.g. decimal points).
 *
 * @param array<string,mixed> $product  Product config.
 * @param mixed               $raw_size Raw size key from calculator state.
 * @return string
 */
function sfc_resolve_product_size_key( $product, $raw_size ) {
    if ( empty( $product['sizes'] ) || ! is_array( $product['sizes'] ) ) {
        return sanitize_key( (string) ( $raw_size ?? '' ) );
    }

    $raw = is_scalar( $raw_size ) ? (string) $raw_size : '';
    if ( '' !== $raw && isset( $product['sizes'][ $raw ] ) ) {
        return $raw;
    }

    $sanitized = sanitize_key( $raw );
    foreach ( array_keys( $product['sizes'] ) as $key ) {
        if ( sanitize_key( $key ) === $sanitized ) {
            return (string) $key;
        }
    }

    return $sanitized;
}

/**
 * Whether a product config offers a custom size entry.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_product_has_custom_size( $product ) {
    return ! empty( $product['sizes']['custom'] );
}

/**
 * Minimum finished-unit quantity for a quote.
 *
 * Custom size can drop a preset floor (e.g. Postales' 9) so any count is
 * allowed; imposition still warns about unused slots on the sheet.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @return int
 */
function sfc_product_min_quantity( $product, $state = array() ) {
    $min = absint( $product['minQuantity'] ?? 1 );
    if ( $min < 1 ) {
        $min = 1;
    }

    $size = is_array( $state ) ? sanitize_key( str_replace( '_', '-', (string) ( $state['size'] ?? '' ) ) ) : '';
    if ( 'custom' === $size && ! empty( $product['emptyQuantityOnCustomSize'] ) ) {
        return 1;
    }

    return $min;
}

/**
 * Whether custom dimensions fit configured min/max bounds in either orientation.
 *
 * @param float               $width_mm  First edge in millimeters.
 * @param float               $height_mm Second edge in millimeters.
 * @param array<string,mixed> $limits    Product customDimensionLimits.
 * @return bool
 */
function sfc_custom_dimensions_fit_limits( $width_mm, $height_mm, $limits ) {
    $min_w = (float) ( $limits['minWidthMm'] ?? 50 );
    $min_h = (float) ( $limits['minHeightMm'] ?? 50 );
    $max_w = (float) ( $limits['maxWidthMm'] ?? 450 );
    $max_h = (float) ( $limits['maxHeightMm'] ?? 310 );

    $meets_min = static function ( $w, $h ) use ( $min_w, $min_h ) {
        return $w >= $min_w && $h >= $min_h;
    };

    $meets_max = static function ( $w, $h ) use ( $max_w, $max_h ) {
        return $w <= $max_w && $h <= $max_h;
    };

    $normal  = $meets_min( $width_mm, $height_mm ) && $meets_max( $width_mm, $height_mm );
    $rotated = $meets_min( $height_mm, $width_mm ) && $meets_max( $height_mm, $width_mm );

    return $normal || $rotated;
}

/**
 * Validate custom width and height against product limits and printable area.
 *
 * @param array<string,mixed> $product  Product config.
 * @param float               $width_mm Width in millimeters.
 * @param float               $height_mm Height in millimeters.
 * @return true|WP_Error
 */
function sfc_validate_custom_dimensions( $product, $width_mm, $height_mm ) {
    $limits = (array) ( $product['customDimensionLimits'] ?? array() );
    $specs  = sfc_get_sheet_specs();
    $min_w  = (float) ( $limits['minWidthMm'] ?? 50 );
    $min_h  = (float) ( $limits['minHeightMm'] ?? 50 );

    $meets_min = static function ( $w, $h ) use ( $min_w, $min_h ) {
        return $w >= $min_w && $h >= $min_h;
    };

    if ( ! $meets_min( $width_mm, $height_mm ) && ! $meets_min( $height_mm, $width_mm ) ) {
        return new WP_Error( 'invalid_custom_size', 'Las dimensiones personalizadas son demasiado pequeñas.' );
    }

    if ( ! sfc_custom_dimensions_fit_limits( $width_mm, $height_mm, $limits ) ) {
        return new WP_Error( 'invalid_custom_size', 'Las dimensiones personalizadas exceden el área imprimible.' );
    }

    if ( sfc_max_units_per_sheet(
        $width_mm,
        $height_mm,
        (int) $specs['printableWidthMm'],
        (int) $specs['printableHeightMm']
    ) <= 0 ) {
        return new WP_Error( 'does_not_fit', 'Las dimensiones seleccionadas no caben en el área imprimible.' );
    }

    return true;
}

/**
 * Validate circular die-cut diameter against product limits and sheet fit.
 *
 * @param array<string,mixed> $product     Product config.
 * @param float               $diameter_mm Diameter in millimeters.
 * @return true|WP_Error
 */
function sfc_validate_circular_diameter( $product, $diameter_mm ) {
    $limits = (array) ( $product['circularDimensionLimits'] ?? array() );
    $specs  = sfc_get_sheet_specs();
    $min    = (float) ( $limits['minDiameterMm'] ?? 50 );
    $max    = (float) ( $limits['maxDiameterMm'] ?? 310 );

    if ( $diameter_mm < $min ) {
        return new WP_Error( 'invalid_custom_size', 'El diámetro es demasiado pequeño.' );
    }

    if ( $diameter_mm > $max ) {
        return new WP_Error( 'invalid_custom_size', 'El diámetro excede el área imprimible.' );
    }

    if ( sfc_max_units_per_sheet(
        $diameter_mm,
        $diameter_mm,
        (int) $specs['printableWidthMm'],
        (int) $specs['printableHeightMm']
    ) <= 0 ) {
        return new WP_Error( 'does_not_fit', 'El diámetro seleccionado no cabe en el área imprimible.' );
    }

    return true;
}

/**
 * Resolve flat unit dimensions from calculator state.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @return array<string,mixed>|WP_Error
 */
function sfc_resolve_product_dimensions( $product, $state ) {
    $size_key = (string) ( $state['size'] ?? '' );
    $language = sfc_get_product_language( $product );

    if ( ! empty( $product['dieCutShapes'] ) && is_array( $product['dieCutShapes'] ) ) {
        $shape = sanitize_key( $state['die_cut_shape'] ?? '' );
        if ( ! isset( $product['dieCutShapes'][ $shape ] ) ) {
            return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
        }

        if ( 'circular' === $shape ) {
            $diameter = isset( $state['diameterMm'] ) ? (float) $state['diameterMm'] : 0.0;
            if ( $diameter <= 0 ) {
                return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
            }

            $validation = sfc_validate_circular_diameter( $product, $diameter );
            if ( is_wp_error( $validation ) ) {
                return $validation;
            }

            return array(
                'key'         => 'circular',
                'widthMm'     => $diameter,
                'heightMm'    => $diameter,
                'label'       => sprintf(
                    'Ø %s mm',
                    number_format_i18n( $diameter, 1 )
                ),
                'die_cut_shape' => 'circular',
            );
        }

        $size_key = 'custom';
    }

    if ( 'custom' === $size_key ) {
        if ( ! sfc_product_has_custom_size( $product ) ) {
            return new WP_Error( 'invalid_size', 'Selección de dimensiones no válida.' );
        }

        $width  = isset( $state['customWidthMm'] ) ? (float) $state['customWidthMm'] : 0.0;
        $height = isset( $state['customLengthMm'] ) ? (float) $state['customLengthMm'] : 0.0;

        if ( $width <= 0 || $height <= 0 ) {
            return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
        }

        $validation = sfc_validate_custom_dimensions( $product, $width, $height );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $label = sprintf(
            '%s × %s mm',
            number_format_i18n( $width, 1 ),
            number_format_i18n( $height, 1 )
        );

        return array(
            'key'      => 'custom',
            'widthMm'  => $width,
            'heightMm' => $height,
            'label'    => $label,
        );
    }

    if ( empty( $product['sizes'][ $size_key ] ) ) {
        return new WP_Error( 'invalid_size', 'Selección de dimensiones no válida.' );
    }

    $size_cfg = $product['sizes'][ $size_key ];

    return array(
        'key'      => $size_key,
        'widthMm'  => (float) $size_cfg['widthMm'],
        'heightMm' => (float) $size_cfg['heightMm'],
        'label'    => $size_cfg['label'][ $language ] ?? $size_key,
    );
}

/**
 * Normalize calculator state persisted on quotes and cart lines.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Raw calculator state.
 * @return array<string,mixed>
 */
function sfc_normalize_product_state( $product, $state ) {
    if ( ! empty( $product['dieCutShapes'] ) && is_array( $product['dieCutShapes'] ) ) {
        $shape = sanitize_key( $state['die_cut_shape'] ?? '' );
        $size  = isset( $product['dieCutShapes'][ $shape ] )
            ? ( 'circular' === $shape ? 'circular' : 'custom' )
            : 'custom';
    } else {
        $size = sfc_product_uses_custom_dimensions_only( $product )
            ? 'custom'
            : sfc_resolve_product_size_key( $product, $state['size'] ?? ( $product['defaults']['size'] ?? '' ) );
    }

    $normalized = array(
        'size'       => $size,
        'turnaround' => sanitize_key( $state['turnaround'] ?? ( $product['defaults']['turnaround'] ?? '' ) ),
    );

    if ( ! empty( $product['dieCutShapes'] ) && is_array( $product['dieCutShapes'] ) ) {
        $shape = sanitize_key( $state['die_cut_shape'] ?? '' );
        if ( isset( $product['dieCutShapes'][ $shape ] ) ) {
            $normalized['die_cut_shape'] = $shape;
        }
    }

    if ( ! empty( $product['emptyDefaultQuantity'] ) ) {
        $normalized['quantity'] = isset( $state['quantity'] ) ? absint( $state['quantity'] ) : 0;
    } else {
        $normalized['quantity'] = absint( $state['quantity'] ?? ( $product['defaults']['quantity'] ?? 1 ) );
    }

    if ( ! empty( $product['finishes'] ) ) {
        $normalized['finish'] = sanitize_key( $state['finish'] ?? ( $product['defaults']['finish'] ?? '' ) );
    }

    if ( ! empty( $product['papers'] ) ) {
        $normalized['paper'] = sanitize_key( $state['paper'] ?? ( $product['defaults']['paper'] ?? '' ) );
    }

    if ( ! empty( $product['surfaces'] ) ) {
        $normalized['surface'] = sanitize_key( $state['surface'] ?? ( $product['defaults']['surface'] ?? '' ) );
    }

    if ( ! empty( $product['printModes'] ) ) {
        $normalized['printMode'] = sfc_resolve_product_print_mode( $product, $state );
    }

    if ( ! empty( $product['turnaround'] ) ) {
        $available = sfc_get_product_turnaround_for_display( $product );
        if ( ! isset( $available[ $normalized['turnaround'] ] ) ) {
            $normalized['turnaround'] = (string) array_key_first( $available );
        }
    }

    if ( ! empty( $product['services'] ) && is_array( $product['services'] ) ) {
        $selected = array();
        foreach ( (array) ( $state['services'] ?? array() ) as $service ) {
            $service = sanitize_key( $service );
            if ( isset( $product['services'][ $service ] ) && ! in_array( $service, $selected, true ) ) {
                $selected[] = $service;
            }
        }
        $normalized['services'] = $selected;
    }

    if ( 'custom' === $normalized['size'] ) {
        $normalized['customWidthMm']  = isset( $state['customWidthMm'] ) ? (float) $state['customWidthMm'] : 0.0;
        $normalized['customLengthMm'] = isset( $state['customLengthMm'] ) ? (float) $state['customLengthMm'] : 0.0;
    }

    if ( 'circular' === ( $normalized['die_cut_shape'] ?? '' ) ) {
        $normalized['diameterMm'] = isset( $state['diameterMm'] ) ? (float) $state['diameterMm'] : 0.0;
    }

    return $normalized;
}

/**
 * Validate a product calculator state array.
 *
 * Thin wrapper over the quote resolver so validation and pricing cannot drift.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $state Calculator state.
 * @return true|WP_Error
 */
function sfc_validate_product_state( $slug, $state ) {
    $quote = sfc_calculate_product_quote( $slug, $state );
    return is_wp_error( $quote ) ? $quote : true;
}

/**
 * Calculate quote for a configured product.
 *
 * Validation checks and quote assembly run in a single pass.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $state Calculator state.
 * @return array<string,mixed>|WP_Error
 */
function sfc_calculate_product_quote( $slug, $state ) {
    $product = sfc_get_product_config( $slug );
    if ( ! $product ) {
        return new WP_Error( 'invalid_product', 'Producto desconocido.' );
    }

    if ( sfc_is_booklet_product( $product ) ) {
        return sfc_calculate_booklet_quote( $slug, $state );
    }

    if ( sfc_is_album_product( $product ) ) {
        return sfc_calculate_album_quote( $slug, $state );
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

    $state      = sfc_normalize_product_state( $product, $state );
    $size       = $state['size'];
    $turnaround = $state['turnaround'];
    $quantity   = $state['quantity'];

    if ( ! isset( $product['sizes'][ $size ] ) ) {
        return new WP_Error( 'invalid_size', 'Selección de dimensiones no válida.' );
    }

    $dimensions = sfc_resolve_product_dimensions( $product, $state );
    if ( is_wp_error( $dimensions ) ) {
        return $dimensions;
    }

    if ( ! empty( $product['requireSelection'] ) && is_array( $product['requireSelection'] ) ) {
        foreach ( $product['requireSelection'] as $field ) {
            $field = sanitize_key( $field );
            if ( empty( $state[ $field ] ) ) {
                return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
            }
        }
    }

    if ( ! empty( $product['papers'] ) ) {
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
    }

    if ( ! empty( $product['finishes'] ) && ! isset( $product['finishes'][ $state['finish'] ?? '' ] ) ) {
        return new WP_Error( 'invalid_finish', 'Selección de acabado no válida.' );
    }

    if ( ! empty( $product['turnaround'] ) && ! isset( $product['turnaround'][ $turnaround ] ) ) {
        return new WP_Error( 'invalid_turnaround', 'Selección de tiempo de entrega no válida.' );
    }

    if ( empty( $product['papers'] ) && ! empty( $product['surfaces'] ) && ! isset( $product['surfaces'][ $state['surface'] ?? '' ] ) ) {
        return new WP_Error( 'invalid_surface', 'Selección de acabado del papel no válida.' );
    }

    if ( ! empty( $product['printModes'] ) ) {
        $print_mode = sanitize_key( $state['printMode'] ?? '' );
        if ( ! isset( $product['printModes'][ $print_mode ] ) ) {
            return new WP_Error( 'invalid_print_mode', 'Selección de caras impresas no válida.' );
        }

        if ( ! empty( $product['printModesByPaper'] ) && is_array( $product['printModesByPaper'] ) ) {
            $paper_key    = sanitize_key( $state['paper'] ?? '' );
            $allowed_keys = ( isset( $product['printModesByPaper'][ $paper_key ] ) && is_array( $product['printModesByPaper'][ $paper_key ] ) )
                ? array_map( 'sanitize_key', $product['printModesByPaper'][ $paper_key ] )
                : array();
            if ( $allowed_keys && ! in_array( $print_mode, $allowed_keys, true ) ) {
                return new WP_Error( 'invalid_print_mode', 'Selección de caras impresas no válida.' );
            }
        }
    }

    $min_quantity = sfc_product_min_quantity( $product, $state );
    if ( $quantity < $min_quantity ) {
        if ( 0 === $quantity && ( ! empty( $product['emptyDefaultQuantity'] ) || ( 'custom' === $size && ! empty( $product['emptyQuantityOnCustomSize'] ) ) ) ) {
            return new WP_Error( 'selection_required', 'Complete todas las opciones requeridas.' );
        }

        return new WP_Error( 'quantity_too_low', 'La cantidad está por debajo del mínimo para este producto.' );
    }

    $size_cfg   = $product['sizes'][ $size ];
    $imposition = sfc_resolve_size_imposition(
        $dimensions['widthMm'],
        $dimensions['heightMm'],
        $quantity,
        $size_cfg
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

    $pricing = sfc_apply_product_addon_pricing(
        $product,
        $state,
        $pricing,
        (int) $imposition['sheetQuantity']
    );

    if ( is_wp_error( $pricing ) ) {
        return $pricing;
    }

    if ( ! empty( $product['turnaround'] ) ) {
        $pricing = sfc_apply_turnaround_surcharge( $product, $state, $pricing );
    }
    $pricing = sfc_apply_trade_pricing( $pricing, (int) $imposition['sheetQuantity'] );

    $layout = sfc_build_sheet_layout_viz( $imposition );
    $paper  = sfc_resolve_product_paper( $product, $state );

    return array(
        'productSlug'   => $slug,
        'state'         => $state,
        'size'          => array(
            'key'      => $dimensions['key'],
            'widthMm'  => (float) $dimensions['widthMm'],
            'heightMm' => (float) $dimensions['heightMm'],
            'label'    => $dimensions['label'],
        ),
        'imposition'    => $imposition,
        'layoutViz'     => $layout,
        'pricing'       => $pricing,
        'currency'      => 'USD',
        'totalPrice'    => (float) $pricing['totalPrice'],
        'unitPrice'     => (float) $pricing['unitPrice'],
        'sheetQuantity' => (int) $imposition['sheetQuantity'],
        'unitQuantity'  => (int) $quantity,
        'printMode'     => sfc_resolve_product_print_mode( $product, $state ),
        'paperType'     => $paper['paperType'],
        'paperGsm'      => $paper['gsm'],
    );
}

/**
 * Build a human-readable cart summary line.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $quote Quote payload.
 * @return string
 */
function sfc_build_product_cart_summary( $slug, $quote ) {
    $product = sfc_get_product_config( $slug );
    if ( $product && sfc_is_booklet_product( $product ) ) {
        return sfc_build_booklet_cart_summary( $slug, $quote );
    }

    $strings   = sfc_get_product_strings( $product );
    $language  = sfc_get_product_language( $product );
    $state     = $quote['state'];
    $size_label = $quote['size']['label'] ?? ( $product['sizes'][ $state['size'] ?? '' ]['label'][ $language ] ?? '' );

    return sprintf(
        '%s × %s — %s: $%s',
        number_format_i18n( (int) $quote['unitQuantity'] ),
        $size_label,
        $strings['total_label'],
        number_format( (float) $quote['totalPrice'], 2, '.', '' )
    );
}

/**
 * Return localized paper label for a product.
 *
 * @param array<string,mixed> $product Product config.
 * @return string
 */
function sfc_get_product_paper_label( $product, $state = array() ) {
    $language = sfc_get_product_language( $product );
    $state    = is_array( $state ) ? $state : array();

    if ( ! empty( $product['papers'] ) && ! empty( $state['paper'] ) ) {
        $paper_key = sanitize_key( $state['paper'] );
        $paper_cfg = $product['papers'][ $paper_key ] ?? null;
        if ( is_array( $paper_cfg ) ) {
            return $paper_cfg['label'][ $language ] ?? $paper_cfg['label']['en'] ?? $paper_key;
        }
    }

    if ( ! empty( $product['paperLabel'] ) && is_array( $product['paperLabel'] ) ) {
        return $product['paperLabel'][ $language ] ?? $product['paperLabel']['en'] ?? '';
    }

    if ( 'coated' === ( $product['paperType'] ?? '' ) && ! empty( $product['gsm'] ) ) {
        return 'es' === $language
            ? sprintf( 'Papel recubierto %d g/m²', (int) $product['gsm'] )
            : sprintf( 'Coated paper %d GSM', (int) $product['gsm'] );
    }

    return 'es' === $language ? 'Papel bond' : sfc_t( 'paper_bond' );
}
