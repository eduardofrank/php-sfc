<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Postales (postcards).
 *
 * @return array<string,mixed>
 */
function sfc_get_postales_product_config() {
    return array(
        'slug'            => 'postales',
        'shortcode'       => 'postales_calculator',
        'language'        => 'es',
        'paperType'       => 'coated',
        'gsm'             => 300,
        'minQuantity'     => 9,
        'defaultQuantity' => 9,
        'customDimensionLimits' => array(
            'minWidthMm'  => 50,
            'minHeightMm' => 50,
            'maxWidthMm'  => 450,
            'maxHeightMm' => 310,
        ),
        'sizes'           => array(
            '140x100' => array(
                'label'    => array(
                    'en' => '140 × 100 mm',
                    'es' => '140 × 100 mm',
                ),
                'widthMm'  => 140,
                'heightMm' => 100,
            ),
            'custom'      => array(
                'label'    => array(
                    'en' => 'Custom size',
                    'es' => 'Tamaño personalizado',
                ),
                'widthMm'  => 0,
                'heightMm' => 0,
            ),
        ),
        'surfaces'        => array(
            'matte'  => array(
                'label' => array(
                    'en' => 'Matte',
                    'es' => 'Mate',
                ),
            ),
            'glossy' => array(
                'label' => array(
                    'en' => 'Glossy',
                    'es' => 'Brillante',
                ),
            ),
        ),
        'printModes'      => array(
            '4x0' => array(
                'label' => array(
                    'en' => 'Single-sided (front only)',
                    'es' => 'Una cara',
                ),
            ),
            '4x4' => array(
                'label' => array(
                    'en' => 'Double-sided (front and back)',
                    'es' => 'Dos caras',
                ),
            ),
        ),
        'turnaround'      => array(
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
        'defaults'        => array(
            'size'       => '140x100',
            'quantity'   => 9,
            'surface'    => 'matte',
            'printMode'  => '4x0',
            'turnaround' => 'next_day',
        ),
        'strings'         => array(
            'product_title' => array(
                'en' => 'Postcards',
                'es' => 'Postales',
            ),
            'step_size' => array(
                'en' => 'Size',
                'es' => 'Dimensiones',
            ),
            'size_notice' => array(
                'en' => 'These dimensions make the best use of the sheet.',
                'es' => 'Estas medidas producen mejor aprovechamiento del papel.',
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
                'en' => 'Paper',
                'es' => 'Papel',
            ),
            'step_surface' => array(
                'en' => 'Paper stock',
                'es' => 'Acabado del papel',
            ),
            'step_sides' => array(
                'en' => 'Sides to print',
                'es' => 'Caras impresas',
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
                'en' => 'Postcards',
                'es' => 'Postales',
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
                'en' => 'Each press sheet fits {units} postcards in the printable area.',
                'es' => 'Cada hoja de impresión cabe {units} postales en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'quantity_help' => array(
                'en' => 'Minimum {min} postcards. Quantity is printed {units} up on each press sheet.',
                'es' => 'Mínimo {min} postales. La cantidad se imprime {units} por hoja de impresión.',
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
        ),
    );
}
