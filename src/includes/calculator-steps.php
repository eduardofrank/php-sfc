<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Declarative calculator steps (Phase 2).
 *
 * Each product's UI is described as an ordered list of step descriptors built
 * from the product config. The generic renderer in assets/calculator/steps.js
 * consumes them; no per-product JavaScript is needed.
 *
 * Step descriptor shape:
 *
 *   key            Unique step key.
 *   type           'options' | 'number' | 'custom-dimensions' | 'static-option'.
 *   field          Calculator state field the step edits (options/number).
 *   labelKey       Product string key for the step label.
 *   labelFallback  Fallback label when the string is missing.
 *   optionsFrom    Bootstrap data collection holding the options map.
 *   optionsByField Resolve options as data[optionsFrom][state[optionsByField]].
 *   required       Step must hold a valid value before quoting.
 *   quoteImmediate Fetch a quote immediately on change (skip debounce).
 *   helpKeys       Product string keys rendered as help paragraphs.
 *   noticeKey      Product string key rendered as a notice paragraph.
 *   noticeWhen     AND-group of conditions gating the notice.
 *   visibleWhen    AND-group of conditions gating the step.
 *   visibleWhenAny OR-list of AND-groups gating the step.
 *   inputId        Fixed DOM id for number inputs (event handlers bind to it).
 *   min/max/step   Number input attributes.
 *   multipleOf     Number readiness requires value % multipleOf === 0.
 *   textFrom       Bootstrap data key shown by static-option steps.
 *
 * A condition is array( 'field' => <state field>, 'in' => string[] ) or
 * array( 'field' => <state field>, 'notIn' => string[] ). Value lists are
 * precomputed here so the client evaluates set membership only — no product
 * logic ships to the browser.
 */

/**
 * Build the ordered step list for a product.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<int,array<string,mixed>>
 */
function sfc_build_product_steps( $product ) {
    if ( sfc_is_booklet_product( $product ) ) {
        $steps = sfc_build_booklet_product_steps( $product );
    } elseif ( sfc_is_album_product( $product ) ) {
        $steps = sfc_build_album_product_steps( $product );
    } else {
        $steps = sfc_build_flat_product_steps( $product );
    }

    return sfc_apply_product_step_order( $product, $steps );
}

/**
 * Reorder steps by an optional per-product stepOrder key list.
 *
 * Listed keys come first in the given order; unlisted steps keep their
 * default relative order and follow.
 *
 * @param array<string,mixed>              $product Product config.
 * @param array<int,array<string,mixed>>   $steps   Default-ordered steps.
 * @return array<int,array<string,mixed>>
 */
function sfc_apply_product_step_order( $product, $steps ) {
    $order = array_values( array_map( 'strval', (array) ( $product['stepOrder'] ?? array() ) ) );
    if ( empty( $order ) ) {
        return $steps;
    }

    $by_key = array();
    foreach ( $steps as $step ) {
        $by_key[ $step['key'] ] = $step;
    }

    $sorted = array();
    foreach ( $order as $key ) {
        if ( isset( $by_key[ $key ] ) ) {
            $sorted[] = $by_key[ $key ];
            unset( $by_key[ $key ] );
        }
    }

    foreach ( $by_key as $step ) {
        $sorted[] = $step;
    }

    return $sorted;
}

/**
 * Paper keys that require a surface selection (coated without preset surface).
 *
 * @param array<string,mixed> $papers Product papers config.
 * @return string[]
 */
function sfc_get_surface_dependent_paper_keys( $papers ) {
    $keys = array();

    foreach ( (array) $papers as $key => $cfg ) {
        if ( ! is_array( $cfg ) ) {
            continue;
        }
        if ( 'coated' === ( $cfg['paperType'] ?? '' ) && empty( $cfg['surface'] ) ) {
            $keys[] = (string) $key;
        }
    }

    return $keys;
}

/**
 * Finish keys that are laminated (require a sides selection on booklets).
 *
 * @param array<string,mixed> $finishes Finishes config map.
 * @return string[]
 */
function sfc_get_laminated_keys_from_finishes( $finishes ) {
    $keys = array();

    foreach ( array_keys( (array) $finishes ) as $key ) {
        if ( sfc_is_laminated_finish( (string) $key ) ) {
            $keys[] = (string) $key;
        }
    }

    return $keys;
}

/**
 * Build steps for a flat product.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<int,array<string,mixed>>
 */
function sfc_build_flat_product_steps( $product ) {
    $steps       = array();
    $custom_only = function_exists( 'sfc_product_uses_custom_dimensions_only' )
        && sfc_product_uses_custom_dimensions_only( $product );
    $die_cut     = function_exists( 'sfc_product_has_die_cut_shape_choice' )
        && sfc_product_has_die_cut_shape_choice( $product );

    if ( $die_cut ) {
        $steps[] = array(
            'key'            => 'die_cut_shape',
            'type'           => 'options',
            'field'          => 'die_cut_shape',
            'labelKey'       => 'step_die_cut_shape',
            'labelFallback'  => 'Forma',
            'optionsFrom'    => 'dieCutShapes',
            'required'       => true,
            'quoteImmediate' => true,
        );

        $circular_limits = (array) ( $product['circularDimensionLimits'] ?? array() );
        $steps[]         = array(
            'key'           => 'diameter',
            'type'          => 'number',
            'field'         => 'diameterMm',
            'labelKey'      => 'step_diameter',
            'labelFallback' => 'Diámetro',
            'inputId'       => 'sfc-diameter',
            'min'           => (float) ( $circular_limits['minDiameterMm'] ?? 50 ),
            'max'           => (float) ( $circular_limits['maxDiameterMm'] ?? 310 ),
            'step'          => 0.1,
            'required'      => true,
            'helpKeys'      => array( 'circular_diameter_help' ),
            'visibleWhen'   => array(
                array(
                    'field' => 'die_cut_shape',
                    'in'    => array( 'circular' ),
                ),
            ),
        );

        $steps[] = array(
            'key'           => 'customDimensions',
            'type'          => 'custom-dimensions',
            'labelKey'      => 'step_size',
            'labelFallback' => 'Caja delimitadora',
            'required'      => true,
            'helpKeys'      => array( 'die_cut_bounding_box_help' ),
            'visibleWhen'   => array(
                array(
                    'field' => 'die_cut_shape',
                    'in'    => array( 'freeform' ),
                ),
            ),
        );
    }

    if ( ! $die_cut && ! $custom_only ) {
        $steps[] = array(
            'key'            => 'size',
            'type'           => 'options',
            'field'          => 'size',
            'labelKey'       => 'step_size',
            'labelFallback'  => 'Dimensiones',
            'optionsFrom'    => 'sizes',
            'required'       => true,
            'quoteImmediate' => true,
            'noticeKey'      => 'size_notice',
            'noticeWhen'     => array(
                array(
                    'field' => 'size',
                    'notIn' => array( 'custom' ),
                ),
            ),
        );
    }

    if ( ! $die_cut && ( $custom_only || sfc_product_has_custom_size( $product ) ) ) {
        $custom_step = array(
            'key'      => 'customDimensions',
            'type'     => 'custom-dimensions',
            'required' => true,
        );

        if ( $custom_only ) {
            $custom_step['labelKey']      = 'step_size';
            $custom_step['labelFallback'] = 'Dimensiones';
        } else {
            $custom_step['visibleWhen'] = array(
                array(
                    'field' => 'size',
                    'in'    => array( 'custom' ),
                ),
            );
        }

        $steps[] = $custom_step;
    }

    $steps[] = array(
        'key'           => 'quantity',
        'type'          => 'number',
        'field'         => 'quantity',
        'labelKey'      => 'step_quantity',
        'labelFallback' => 'Cantidad',
        'inputId'       => 'sfc-quantity',
        'min'           => absint( $product['minQuantity'] ?? 1 ),
        'step'          => 1,
        'required'      => true,
        'helpKeys'      => array( 'quantity_help' ),
    );

    if ( ! empty( $product['papers'] ) && is_array( $product['papers'] ) ) {
        $steps[] = array(
            'key'           => 'paper',
            'type'          => 'options',
            'field'         => 'paper',
            'labelKey'      => 'step_paper',
            'labelFallback' => 'Papel',
            'optionsFrom'   => 'papers',
            'required'      => true,
        );

        if ( ! empty( $product['surfaces'] ) ) {
            $surface_papers = sfc_get_surface_dependent_paper_keys( $product['papers'] );
            if ( ! empty( $surface_papers ) ) {
                $steps[] = array(
                    'key'           => 'surface',
                    'type'          => 'options',
                    'field'         => 'surface',
                    'labelKey'      => 'step_surface',
                    'labelFallback' => 'Acabado del papel',
                    'optionsFrom'   => 'surfaces',
                    'required'      => true,
                    'visibleWhen'   => array(
                        array(
                            'field' => 'paper',
                            'in'    => $surface_papers,
                        ),
                    ),
                );
            }
        }
    } else {
        $steps[] = array(
            'key'           => 'paper',
            'type'          => 'static-option',
            'labelKey'      => 'step_paper',
            'labelFallback' => 'Papel',
            'textFrom'      => 'paperLabel',
            'required'      => false,
        );

        if ( ! empty( $product['surfaces'] ) && 'coated' === ( $product['paperType'] ?? '' ) ) {
            $steps[] = array(
                'key'           => 'surface',
                'type'          => 'options',
                'field'         => 'surface',
                'labelKey'      => 'step_surface',
                'labelFallback' => 'Acabado del papel',
                'optionsFrom'   => 'surfaces',
                'required'      => true,
            );
        }
    }

    if ( ! empty( $product['printModes'] ) ) {
        $steps[] = array(
            'key'           => 'printMode',
            'type'          => 'options',
            'field'         => 'printMode',
            'labelKey'      => 'step_sides',
            'labelFallback' => 'Caras impresas',
            'optionsFrom'   => 'printModes',
            'required'      => true,
        );
    }

    if ( ! empty( $product['finishes'] ) ) {
        $steps[] = array(
            'key'           => 'finish',
            'type'          => 'options',
            'field'         => 'finish',
            'labelKey'      => 'step_finish',
            'labelFallback' => 'Acabado',
            'optionsFrom'   => 'finishes',
            'required'      => true,
        );
    }

    if ( ! empty( $product['turnaround'] ) ) {
        $steps[] = array(
            'key'           => 'turnaround',
            'type'          => 'options',
            'field'         => 'turnaround',
            'labelKey'      => 'step_turnaround',
            'labelFallback' => 'Tiempo de entrega',
            'optionsFrom'   => 'turnaround',
            'required'      => true,
        );
    }

    return $steps;
}

/**
 * Build steps for a saddle-stitched booklet product.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<int,array<string,mixed>>
 */
function sfc_build_booklet_product_steps( $product ) {
    $papers        = (array) ( $product['papers'] ?? array() );
    $inner_keys    = array_values( array_map( 'strval', (array) ( $product['innerPapers'] ?? array_keys( $papers ) ) ) );
    $inner_coated  = array_values( array_intersect( sfc_get_surface_dependent_paper_keys( $papers ), $inner_keys ) );
    $cover_coated  = sfc_get_surface_dependent_paper_keys( $papers );
    $laminated     = sfc_get_laminated_keys_from_finishes( $product['coverFinishes'] ?? array() );

    $steps = array();

    $steps[] = array(
        'key'            => 'size',
        'type'           => 'options',
        'field'          => 'size',
        'labelKey'       => 'step_size',
        'labelFallback'  => 'Tamaño plano (abierto)',
        'optionsFrom'    => 'sizes',
        'required'       => true,
        'quoteImmediate' => true,
        'helpKeys'       => array(
            sfc_product_uses_closed_booklet_dimensions( $product ) ? 'size_closed_help' : 'size_open_help',
        ),
        'noticeKey'      => 'size_notice',
        'noticeWhen'     => array(
            array(
                'field' => 'size',
                'notIn' => array( 'custom' ),
            ),
        ),
    );

    if ( sfc_product_has_custom_size( $product ) ) {
        $steps[] = array(
            'key'         => 'customDimensions',
            'type'        => 'custom-dimensions',
            'required'    => true,
            'visibleWhen' => array(
                array(
                    'field' => 'size',
                    'in'    => array( 'custom' ),
                ),
            ),
        );
    }

    $steps[] = array(
        'key'           => 'quantity',
        'type'          => 'number',
        'field'         => 'quantity',
        'labelKey'      => 'step_quantity',
        'labelFallback' => 'Cantidad',
        'inputId'       => 'sfc-quantity',
        'min'           => absint( $product['minQuantity'] ?? 1 ),
        'step'          => 1,
        'required'      => true,
        'helpKeys'      => array( 'quantity_help' ),
    );

    $steps[] = array(
        'key'           => 'innerPages',
        'type'          => 'number',
        'field'         => 'innerPages',
        'labelKey'      => 'step_inner_pages',
        'labelFallback' => 'Páginas tripa',
        'inputId'       => 'sfc-inner-pages',
        'min'           => 4,
        'max'           => sfc_booklet_max_inner_pages(),
        'step'          => 4,
        'multipleOf'    => 4,
        'required'      => true,
        'helpKeys'      => array( 'inner_pages_help' ),
    );

    $steps[] = array(
        'key'            => 'innerPaper',
        'type'           => 'options',
        'field'          => 'innerPaper',
        'labelKey'       => 'step_inner_paper',
        'labelFallback'  => 'Peso del papel — tripa',
        'optionsFrom'    => 'innerPapers',
        'required'       => true,
        'quoteImmediate' => true,
    );

    if ( ! empty( $product['surfaces'] ) && ! empty( $inner_coated ) ) {
        $steps[] = array(
            'key'           => 'innerSurface',
            'type'          => 'options',
            'field'         => 'innerSurface',
            'labelKey'      => 'step_inner_surface',
            'labelFallback' => 'Stock del papel — tripa',
            'optionsFrom'   => 'surfaces',
            'required'      => true,
            'visibleWhen'   => array(
                array(
                    'field' => 'innerPaper',
                    'in'    => $inner_coated,
                ),
            ),
        );
    }

    $steps[] = array(
        'key'            => 'coverWeight',
        'type'           => 'options',
        'field'          => 'coverWeight',
        'labelKey'       => 'step_cover_weight',
        'labelFallback'  => 'Peso de portada',
        'optionsFrom'    => 'coverWeights',
        'optionsByField' => 'innerPaper',
        'required'       => true,
        'quoteImmediate' => true,
        'visibleWhen'    => array(
            array(
                'field' => 'innerPaper',
                'in'    => $inner_keys,
            ),
        ),
    );

    if ( ! empty( $product['surfaces'] ) ) {
        $cover_surface_groups = array();
        if ( ! empty( $cover_coated ) ) {
            $cover_surface_groups[] = array(
                array(
                    'field' => 'coverWeight',
                    'in'    => $cover_coated,
                ),
            );
        }
        if ( ! empty( $inner_coated ) ) {
            $cover_surface_groups[] = array(
                array(
                    'field' => 'coverWeight',
                    'in'    => array( 'same_as_inner' ),
                ),
                array(
                    'field' => 'innerPaper',
                    'in'    => $inner_coated,
                ),
            );
        }

        if ( ! empty( $cover_surface_groups ) ) {
            $steps[] = array(
                'key'            => 'coverSurface',
                'type'           => 'options',
                'field'          => 'coverSurface',
                'labelKey'       => 'step_cover_surface',
                'labelFallback'  => 'Stock del papel — portada',
                'optionsFrom'    => 'surfaces',
                'required'       => true,
                'visibleWhenAny' => $cover_surface_groups,
            );
        }
    }

    if ( ! empty( $product['coverPrintModes'] ) ) {
        $steps[] = array(
            'key'           => 'coverPrintMode',
            'type'          => 'options',
            'field'         => 'coverPrintMode',
            'labelKey'      => 'step_cover_print',
            'labelFallback' => 'Caras impresas — portada',
            'optionsFrom'   => 'coverPrintModes',
            'required'      => true,
        );
    }

    if ( ! empty( $product['coverFinishes'] ) ) {
        $steps[] = array(
            'key'           => 'coverFinish',
            'type'          => 'options',
            'field'         => 'coverFinish',
            'labelKey'      => 'step_cover_finish',
            'labelFallback' => 'Acabado de portada',
            'optionsFrom'   => 'coverFinishes',
            'required'      => true,
        );
    }

    if ( ! empty( $product['coverFinishSides'] ) && ! empty( $laminated ) ) {
        $steps[] = array(
            'key'           => 'coverFinishSides',
            'type'          => 'options',
            'field'         => 'coverFinishSides',
            'labelKey'      => 'step_cover_finish_sides',
            'labelFallback' => 'Lados de laminado',
            'optionsFrom'   => 'coverFinishSides',
            'required'      => true,
            'visibleWhen'   => array(
                array(
                    'field' => 'coverFinish',
                    'in'    => $laminated,
                ),
            ),
        );
    }

    if ( ! empty( $product['turnaround'] ) ) {
        $steps[] = array(
            'key'           => 'turnaround',
            'type'          => 'options',
            'field'         => 'turnaround',
            'labelKey'      => 'step_turnaround',
            'labelFallback' => 'Tiempo de entrega',
            'optionsFrom'   => 'turnaround',
            'required'      => true,
        );
    }

    return $steps;
}

/**
 * Build steps for a hardcover album product.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<int,array<string,mixed>>
 */
function sfc_build_album_product_steps( $product ) {
    $steps = array();

    $steps[] = array(
        'key'            => 'size',
        'type'           => 'options',
        'field'          => 'size',
        'labelKey'       => 'step_size',
        'labelFallback'  => 'Tamaño de página',
        'optionsFrom'    => 'sizes',
        'required'       => true,
        'quoteImmediate' => true,
    );

    if ( sfc_product_has_custom_size( $product ) ) {
        $steps[] = array(
            'key'         => 'customDimensions',
            'type'        => 'custom-dimensions',
            'required'    => true,
            'visibleWhen' => array(
                array(
                    'field' => 'size',
                    'in'    => array( 'custom' ),
                ),
            ),
        );
    }

    $steps[] = array(
        'key'           => 'pages',
        'type'          => 'number',
        'field'         => 'pages',
        'labelKey'      => 'step_pages',
        'labelFallback' => 'Número de páginas',
        'inputId'       => 'sfc-pages',
        'min'           => absint( $product['minPages'] ?? 2 ),
        'max'           => absint( $product['maxPages'] ?? 500 ),
        'step'          => 2,
        'multipleOf'    => 2,
        'required'      => true,
        'helpKeys'      => array( 'pages_help' ),
    );

    $steps[] = array(
        'key'           => 'quantity',
        'type'          => 'number',
        'field'         => 'quantity',
        'labelKey'      => 'step_quantity',
        'labelFallback' => 'Cantidad',
        'inputId'       => 'sfc-quantity',
        'min'           => absint( $product['minQuantity'] ?? 1 ),
        'step'          => 1,
        'required'      => true,
        'helpKeys'      => array( 'quantity_help' ),
    );

    $steps[] = array(
        'key'           => 'paper',
        'type'          => 'options',
        'field'         => 'paper',
        'labelKey'      => 'step_paper',
        'labelFallback' => 'Gramaje del papel',
        'optionsFrom'   => 'papers',
        'required'      => true,
    );

    if ( ! empty( $product['surfaces'] ) ) {
        $surface_papers = sfc_get_surface_dependent_paper_keys( $product['papers'] ?? array() );
        if ( ! empty( $surface_papers ) ) {
            $steps[] = array(
                'key'           => 'surface',
                'type'          => 'options',
                'field'         => 'surface',
                'labelKey'      => 'step_surface',
                'labelFallback' => 'Acabado del papel',
                'optionsFrom'   => 'surfaces',
                'required'      => true,
                'visibleWhen'   => array(
                    array(
                        'field' => 'paper',
                        'in'    => $surface_papers,
                    ),
                ),
            );
        }
    }

    $steps[] = array(
        'key'           => 'hardcoverFinish',
        'type'          => 'options',
        'field'         => 'hardcover_finish',
        'labelKey'      => 'step_hardcover_finish',
        'labelFallback' => 'Acabado tapa dura',
        'optionsFrom'   => 'hardcoverFinishes',
        'required'      => true,
    );

    return $steps;
}

/**
 * Precomputed cover-weight option maps keyed by inner paper.
 *
 * The client resolves the coverWeight step's options from this map instead of
 * mirroring the paper sort-weight logic.
 *
 * @param array<string,mixed> $product  Product config.
 * @param string              $language Language code.
 * @return array<string,array<string,array<string,mixed>>>
 */
function sfc_build_booklet_cover_weight_options( $product, $language ) {
    $papers     = (array) ( $product['papers'] ?? array() );
    $inner_keys = (array) ( $product['innerPapers'] ?? array_keys( $papers ) );
    $strings    = sfc_get_product_strings( $product );
    $same_label = $strings['cover_same_as_inner'] ?? 'Igual tripa';
    $map        = array();

    foreach ( $inner_keys as $inner_key ) {
        $inner_key = (string) $inner_key;
        $options   = array();

        foreach ( sfc_get_booklet_cover_weight_keys( $inner_key, $papers ) as $key ) {
            if ( 'same_as_inner' === $key ) {
                $options[ $key ] = array(
                    'key'   => $key,
                    'label' => $same_label,
                );
                continue;
            }

            $cfg             = is_array( $papers[ $key ] ?? null ) ? $papers[ $key ] : array();
            $options[ $key ] = array(
                'key'       => $key,
                'label'     => $cfg['label'][ $language ] ?? $key,
                'paperType' => sanitize_key( $cfg['paperType'] ?? '' ),
                'gsm'       => isset( $cfg['gsm'] ) ? absint( $cfg['gsm'] ) : null,
            );
        }

        $map[ $inner_key ] = $options;
    }

    return $map;
}
