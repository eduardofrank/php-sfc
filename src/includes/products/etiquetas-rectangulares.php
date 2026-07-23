<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Stickers y etiquetas rectangulares.
 *
 * @return array<string,mixed>
 */
function sfc_get_etiquetas_rectangulares_product_config() {
    return array(
        'slug'                   => 'etiquetas-rectangulares',
        'shortcode'              => 'etiquetas_rectangulares_calculator',
        'language'               => 'es',
        'printMode'              => '4x0',
        'minQuantity'            => 1,
        'emptyDefaultQuantity'   => true,
        'customDimensionsOnly'   => true,
        'requireSelection'       => array( 'paper' ),
        'customDimensionLimits'  => array(
            'minWidthMm'  => 50,
            'minHeightMm' => 50,
            'maxWidthMm'  => 450,
            'maxHeightMm' => 310,
        ),
        'sizes'                  => array(
            'custom' => array(
                'label'    => array(
                    'en' => 'Custom size',
                    'es' => 'Tamaño personalizado',
                ),
                'widthMm'  => 0,
                'heightMm' => 0,
            ),
        ),
        'papers'                 => array(
            'lithosticker' => array(
                'label'     => array(
                    'en' => 'Lithosticker',
                    'es' => 'Lithosticker',
                ),
                'paperType' => 'lithosticker',
            ),
            'vinyl' => array(
                'label'     => array(
                    'en' => 'Vinyl',
                    'es' => 'Vinil',
                ),
                'paperType' => 'vinyl',
            ),
        ),
        'turnaround'             => array(
            'next_day' => array(
                'label' => array(
                    'en' => 'Next day',
                    'es' => 'Siguiente día',
                ),
            ),
            'same_day' => array(
                'label'     => array(
                    'en' => 'Same day',
                    'es' => 'Mismo día',
                ),
                'localOnly' => true,
            ),
        ),
        'defaults'               => array(
            'size'       => 'custom',
            'turnaround' => 'next_day',
        ),
        'strings'                => array(
            'product_title' => array(
                'en' => 'Rectangular stickers and labels',
                'es' => 'Stickers y etiquetas rectangulares',
            ),
            'step_size' => array(
                'en' => 'Size',
                'es' => 'Dimensiones',
            ),
            'step_custom_width' => array(
                'en' => 'Dimension 1',
                'es' => 'Dimensión 1',
            ),
            'step_custom_length' => array(
                'en' => 'Dimension 2',
                'es' => 'Dimensión 2',
            ),
            'size_custom_warning' => array(
                'en' => 'Make sure to optimize sheet usage.',
                'es' => 'Intente optimizar el uso del material.',
            ),
            'step_quantity' => array(
                'en' => 'Quantity',
                'es' => 'Cantidad',
            ),
            'step_paper' => array(
                'en' => 'Material',
                'es' => 'Material',
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
                'en' => 'Labels',
                'es' => 'Etiquetas',
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
                'en' => 'Each press sheet fits {units} labels in the printable area.',
                'es' => 'En cada hoja de impresión caben {units} etiquetas en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'quantity_help' => array(
                'en' => 'Minimum {min} labels. Quantity is printed {units} up on each press sheet.',
                'es' => 'Mínimo {min} etiquetas. La cantidad se imprime {units} por hoja de impresión.',
            ),
            'selection_required' => array(
                'en' => 'Enter dimensions, quantity, and material to see your quote.',
                'es' => 'Ingrese dimensiones, cantidad y material para ver su cotización.',
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
