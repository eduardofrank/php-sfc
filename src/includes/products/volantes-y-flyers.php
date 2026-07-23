<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Volantes y Flyers.
 *
 * @return array<string,mixed>
 */
function sfc_get_volantes_y_flyers_product_config() {
    return array(
        'slug'                 => 'volantes-y-flyers',
        'shortcode'            => 'volantes_y_flyers_calculator',
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
        'sizes'                => array(
            '140x100' => array(
                'label'    => array(
                    'en' => '140 × 100 mm',
                    'es' => '140 × 100 mm',
                ),
                'widthMm'  => 140,
                'heightMm' => 100,
            ),
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
            'custom'      => array(
                'label'    => array(
                    'en' => 'Custom size',
                    'es' => 'Tamaño personalizado',
                ),
                'widthMm'  => 0,
                'heightMm' => 0,
            ),
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
            'printMode'  => '4x0',
            'turnaround' => 'next_day',
        ),
        'strings'              => array(
            'product_title' => array(
                'en' => 'Flyers',
                'es' => 'Volantes y flyers',
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
                'en' => 'Flyers',
                'es' => 'Volantes',
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
                'en' => 'Each press sheet fits {units} flyers in the printable area.',
                'es' => 'Cada hoja de impresión cabe {units} volantes en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'quantity_help' => array(
                'en' => 'Minimum {min} flyers. Quantity is printed {units} up on each press sheet.',
                'es' => 'Mínimo {min} volantes. La cantidad se imprime {units} por hoja de impresión.',
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
        ),
    );
}
