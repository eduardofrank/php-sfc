<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared option blocks for folded brochure calculators.
 *
 * @return array<string,mixed>
 */
function sfc_get_folded_product_shared_options() {
    return array(
        'language'             => 'es',
        'minQuantity'          => 1,
        'emptyDefaultQuantity' => true,
        'requireSelection'     => array( 'paper', 'finish' ),
        'customDimensionLimits' => array(
            'minWidthMm'  => 50,
            'minHeightMm' => 50,
            'maxWidthMm'  => 450,
            'maxHeightMm' => 310,
        ),
        'papers'               => array(
            'bond' => array(
                'label'     => array(
                    'en' => 'Bond',
                    'es' => 'Bond',
                ),
                'paperType' => 'bond',
            ),
            'gsm150' => array(
                'label'     => array(
                    'en' => '150 GSM',
                    'es' => '150 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 150,
            ),
            'gsm200' => array(
                'label'     => array(
                    'en' => '200 GSM',
                    'es' => '200 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 200,
            ),
            'gsm250' => array(
                'label'     => array(
                    'en' => '250 GSM',
                    'es' => '250 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 250,
            ),
            'gsm300' => array(
                'label'     => array(
                    'en' => '300 GSM',
                    'es' => '300 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 300,
            ),
        ),
        'surfaces'             => array(
            'matte'  => array(
                'label' => array(
                    'en' => 'Matte',
                    'es' => 'Mate',
                ),
            ),
            'glossy' => array(
                'label' => array(
                    'en' => 'Gloss',
                    'es' => 'Brillante',
                ),
            ),
        ),
        'printModes'           => array(
            '4x0' => array(
                'label' => array(
                    'en' => 'One side',
                    'es' => 'Una cara',
                ),
            ),
            '4x4' => array(
                'label' => array(
                    'en' => 'Two sides',
                    'es' => 'Dos caras',
                ),
            ),
        ),
        'finishes'             => array(
            'none' => array(
                'label' => array(
                    'en' => 'None',
                    'es' => 'Ninguno',
                ),
            ),
            'matte_laminate'  => array(
                'label' => array(
                    'en' => 'Matte lamination',
                    'es' => 'Laminado mate',
                ),
            ),
            'glossy_laminate' => array(
                'label' => array(
                    'en' => 'Gloss lamination',
                    'es' => 'Laminado brillante',
                ),
            ),
        ),
        'turnaround'           => array(
            'next_day' => array(
                'label' => array(
                    'en' => 'Next day',
                    'es' => 'Siguiente día',
                ),
            ),
            'same_day' => array(
                'label' => array(
                    'en' => 'Same day',
                    'es' => 'Mismo día',
                ),
                'localOnly' => true,
            ),
        ),
        'defaults'             => array(
            'printMode'  => '4x4',
            'turnaround' => 'next_day',
        ),
    );
}

/**
 * Custom size option shared by all folded brochure calculators.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_folded_custom_size_option() {
    return array(
        'custom' => array(
            'label'    => array(
                'en' => 'Custom size',
                'es' => 'Tamaño personalizado',
            ),
            'widthMm'  => 0,
            'heightMm' => 0,
        ),
    );
}

/**
 * Flat size presets for pliegue al medio, plegado en tres, and plegado en Z.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_folded_tri_panel_sizes() {
    return array_merge(
        array(
            '215.9x139.7' => array(
                'label'    => array(
                    'en' => 'Half Letter (215.9 × 139.7 mm)',
                    'es' => 'Media Carta (215.9 × 139.7 mm)',
                ),
                'widthMm'  => 215.9,
                'heightMm' => 139.7,
            ),
            '215.9x279.4' => array(
                'label'    => array(
                    'en' => 'Letter (215.9 × 279.4 mm)',
                    'es' => 'Carta (215.9 × 279.4 mm)',
                ),
                'widthMm'  => 215.9,
                'heightMm' => 279.4,
            ),
            '431.8x279.4' => array(
                'label'    => array(
                    'en' => 'Tabloid (431.8 × 279.4 mm)',
                    'es' => 'Tabloide (431.8 × 279.4 mm)',
                ),
                'widthMm'  => 431.8,
                'heightMm' => 279.4,
            ),
        ),
        sfc_get_folded_custom_size_option()
    );
}

/**
 * Flat size presets for gate, French, and accordion folds.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_folded_panel_sizes() {
    return array_merge(
        array(
            '431.8x279.4' => array(
                'label'    => array(
                    'en' => 'Tabloid (431.8 × 279.4 mm)',
                    'es' => 'Tabloide (431.8 × 279.4 mm)',
                ),
                'widthMm'  => 431.8,
                'heightMm' => 279.4,
            ),
        ),
        sfc_get_folded_custom_size_option()
    );
}

/**
 * Resolve flat size presets for a folded brochure slug.
 *
 * @param string $slug Product slug.
 * @return array<string,array<string,mixed>>
 */
function sfc_get_folded_product_sizes( $slug ) {
    $slug = sanitize_key( str_replace( '_', '-', $slug ) );

    if ( in_array( $slug, array( 'half-fold', 'tri-fold', 'z-fold' ), true ) ) {
        return sfc_get_folded_tri_panel_sizes();
    }

    return sfc_get_folded_panel_sizes();
}

/**
 * Build strings for a folded brochure calculator.
 *
 * @param array<string,array<string,string>> $title Titles keyed by language.
 * @return array<string,array<string,string>>
 */
function sfc_get_folded_product_strings( $title ) {
    return array(
        'product_title' => $title,
        'step_size' => array(
            'en' => 'Flat size (before folding)',
            'es' => 'Tamaño plano (antes del pliegue)',
        ),
        'size_notice' => array(
            'en' => 'These flat dimensions make the best use of the sheet.',
            'es' => 'Estas medidas planas producen mejor aprovechamiento del papel.',
        ),
        'size_custom_warning' => array(
            'en' => 'Make sure to optimize sheet usage.',
            'es' => 'Intente optimizar el uso del papel',
        ),
        'step_custom_width' => array(
            'en' => 'Dimension 1',
            'es' => 'Dimensión 1',
        ),
        'step_custom_length' => array(
            'en' => 'Dimension 2',
            'es' => 'Dimensión 2',
        ),
        'step_quantity' => array(
            'en' => 'Quantity',
            'es' => 'Cantidad',
        ),
        'step_paper' => array(
            'en' => 'Paper weight',
            'es' => 'Peso del papel',
        ),
        'step_surface' => array(
            'en' => 'Paper stock',
            'es' => 'Stock del papel',
        ),
        'step_sides' => array(
            'en' => 'Sides to print',
            'es' => 'Caras impresas',
        ),
        'step_finish' => array(
            'en' => 'Finish',
            'es' => 'Acabado',
        ),
        'step_turnaround' => array(
            'en' => 'Turnaround',
            'es' => 'Tiempo de entrega',
        ),
        'summary_title' => array(
            'en' => 'Your quote',
            'es' => 'Tu cotización',
        ),
        'units_label' => array(
            'en' => 'Brochures',
            'es' => 'Folletos',
        ),
        'sheets_label' => array(
            'en' => 'Press sheets',
            'es' => 'Hojas de impresión',
        ),
        'unit_price_label' => array(
            'en' => 'Price per press sheet',
            'es' => 'Precio por hoja de impresión',
        ),
        'total_label' => array(
            'en' => 'Total',
            'es' => 'Total',
        ),
        'layout_title' => array(
            'en' => 'Sheet layout preview',
            'es' => 'Vista previa del montaje',
        ),
        'layout_caption' => array(
            'en' => 'Each press sheet fits {units} brochures in the printable area.',
            'es' => 'Cada hoja de impresión cabe {units} folletos en el área imprimible.',
        ),
        'layout_last_row' => array(
            'en' => 'The last sheet uses {filled} of {total} available slots.',
            'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
        ),
        'quantity_help' => array(
            'en' => 'Minimum {min} brochures. Quantity is printed {units} up on each press sheet.',
            'es' => 'Mínimo {min} folletos. La cantidad se imprime {units} por hoja de impresión.',
        ),
        'selection_required' => array(
            'en' => 'Enter quantity and select paper, stock (when coated), and finish to see your quote.',
            'es' => 'Ingrese cantidad y seleccione peso, stock (si es recubierto) y acabado para ver su cotización.',
        ),
        'add_to_cart' => array(
            'en' => 'Add to cart',
            'es' => 'Agregar al carrito',
        ),
        'adding_to_cart' => array(
            'en' => 'Adding…',
            'es' => 'Agregando…',
        ),
        'cart_error' => array(
            'en' => 'Could not add this configuration to the cart.',
            'es' => 'No se pudo agregar esta configuración al carrito.',
        ),
        'config_error' => array(
            'en' => 'Calculator configuration is unavailable.',
            'es' => 'La configuración de la calculadora no está disponible.',
        ),
    );
}

/**
 * Build one folded brochure product config.
 *
 * @param string               $slug      Product slug.
 * @param string               $shortcode Dedicated shortcode tag.
 * @param array<string,string> $title     Localized calculator title.
 * @return array<string,mixed>
 */
function sfc_build_folded_product_config( $slug, $shortcode, $title ) {
    return array_merge(
        sfc_get_folded_product_shared_options(),
        array(
            'slug'      => $slug,
            'shortcode' => $shortcode,
            'sizes'     => sfc_get_folded_product_sizes( $slug ),
            'strings'   => sfc_get_folded_product_strings( $title ),
        )
    );
}

/**
 * Folded brochure calculator configs keyed by slug.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_folded_product_registry() {
    $defs = array(
        'half-fold' => array(
            'shortcode' => 'half_fold_calculator',
            'title'     => array(
                'en' => 'Half-fold brochure',
                'es' => 'Folleto — pliegue al medio',
            ),
        ),
        'tri-fold' => array(
            'shortcode' => 'tri_fold_calculator',
            'title'     => array(
                'en' => 'Tri-fold brochure',
                'es' => 'Folleto — plegado en tres',
            ),
        ),
        'z-fold' => array(
            'shortcode' => 'z_fold_calculator',
            'title'     => array(
                'en' => 'Z-fold brochure',
                'es' => 'Folleto — plegado en Z',
            ),
        ),
        'gate-fold' => array(
            'shortcode' => 'gate_fold_calculator',
            'title'     => array(
                'en' => 'Gate-fold brochure',
                'es' => 'Folleto — plegado en puerta',
            ),
        ),
        'french-fold' => array(
            'shortcode' => 'french_fold_calculator',
            'title'     => array(
                'en' => 'French-fold brochure',
                'es' => 'Folleto — plegado francés',
            ),
        ),
        'accordion-4-panel' => array(
            'shortcode' => 'accordion_4_panel_calculator',
            'title'     => array(
                'en' => '4-panel accordion brochure',
                'es' => 'Folleto — acordeón 4 paneles',
            ),
        ),
    );

    $registry = array();
    foreach ( $defs as $slug => $def ) {
        $registry[ $slug ] = sfc_build_folded_product_config( $slug, $def['shortcode'], $def['title'] );
    }

    return $registry;
}
