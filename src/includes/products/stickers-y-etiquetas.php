<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Stickers y etiquetas troqueladas.
 *
 * @return array<string,mixed>
 */
function sfc_get_stickers_y_etiquetas_product_config() {
    return array(
        'slug'                   => 'stickers-y-etiquetas',
        'shortcode'              => 'stickers_y_etiquetas_calculator',
        'language'               => 'es',
        'printMode'              => '4x0',
        'minQuantity'            => 1,
        'emptyDefaultQuantity'   => true,
        'requireSelection'       => array( 'die_cut_shape', 'paper' ),
        'customDimensionLimits'  => array(
            'minWidthMm'  => 50,
            'minHeightMm' => 50,
            'maxWidthMm'  => 450,
            'maxHeightMm' => 310,
        ),
        'circularDimensionLimits' => array(
            'minDiameterMm' => 50,
            'maxDiameterMm' => 310,
        ),
        'dieCutShapes'           => array(
            'circular' => array(
                'label' => array(
                    'en' => 'Circular',
                    'es' => 'Circular',
                ),
            ),
            'freeform' => array(
                'label' => array(
                    'en' => 'Free-form',
                    'es' => 'Forma libre',
                ),
            ),
        ),
        'sizes'                  => array(
            'circular' => array(
                'label'    => array(
                    'en' => 'Circular',
                    'es' => 'Circular',
                ),
                'widthMm'  => 0,
                'heightMm' => 0,
            ),
            'custom'   => array(
                'label'    => array(
                    'en' => 'Custom bounding box',
                    'es' => 'Caja delimitadora personalizada',
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
            'vinyl'        => array(
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
            'turnaround' => 'next_day',
        ),
        'strings'                => array(
            'product_title' => array(
                'en' => 'Die-cut stickers and labels',
                'es' => 'Stickers y etiquetas troqueladas',
            ),
            'step_die_cut_shape' => array(
                'en' => 'Shape',
                'es' => 'Forma',
            ),
            'step_diameter' => array(
                'en' => 'Diameter',
                'es' => 'Diámetro',
            ),
            'circular_diameter_help' => array(
                'en' => 'Enter the sticker diameter in millimeters.',
                'es' => 'Ingrese el diámetro del sticker en milímetros.',
            ),
            'step_size' => array(
                'en' => 'Bounding box',
                'es' => 'Caja delimitadora',
            ),
            'step_custom_width' => array(
                'en' => 'Dimension 1',
                'es' => 'Dimensión 1',
            ),
            'step_custom_length' => array(
                'en' => 'Dimension 2',
                'es' => 'Dimensión 2',
            ),
            'die_cut_bounding_box_help' => array(
                'en' => 'Enter the width and height of the rectangular (or square) box that encloses the die-cut artwork — not the irregular outline itself. Dimensions are in millimeters and can be entered in either order.',
                'es' => 'Ingrese el ancho y alto de la caja rectangular (o cuadrada) que encierra el contorno troquelado — no el contorno irregular. Las dimensiones son en milímetros y pueden ingresarse en cualquier orden.',
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
            'die_cut_label' => array(
                'en' => 'Die-cutting',
                'es' => 'Troquelado',
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
                'en' => 'Select shape, dimensions, quantity, and material to see your quote.',
                'es' => 'Seleccione forma, dimensiones, cantidad y material para ver su cotización.',
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
