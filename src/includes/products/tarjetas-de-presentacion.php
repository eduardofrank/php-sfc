<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Tarjetas de Presentación (business cards).
 *
 * @return array<string,mixed>
 */
function sfc_get_tarjetas_de_presentacion_product_config() {
    return array(
        'slug'            => 'tarjetas-de-presentacion',
        'shortcode'       => 'tarjetas_de_presentacion_calculator',
        'language'        => 'es',
        'paperType'       => 'coated',
        'gsm'             => 300,
        'minQuantity'     => 100,
        'defaultQuantity' => 100,
        'sizes'           => array(
            '90x50' => array(
                'label'          => array(
                    'en' => '90 × 50 mm',
                    'es' => '90 × 50 mm',
                ),
                'widthMm'        => 90,
                'heightMm'       => 50,
                'unitsPerSheet'  => 20,
                'impositionCols' => 4,
                'impositionRows' => 5,
                // 20-up is intentional: max auto fit is 24-up, but 100 cards at 24-up
                // needs five sheets with 20 wasted slots; 20-up uses five sheets with zero waste.
            ),
            '80x50' => array(
                'label'          => array(
                    'en' => '80 × 50 mm',
                    'es' => '80 × 50 mm',
                ),
                'widthMm'        => 80,
                'heightMm'       => 50,
                'unitsPerSheet'  => 25,
                'impositionCols' => 5,
                'impositionRows' => 5,
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
                    'es' => 'Solo frente',
                ),
            ),
            '4x4' => array(
                'label' => array(
                    'en' => 'Double-sided (front and back)',
                    'es' => 'Frente y reverso',
                ),
            ),
        ),
        'finishes'        => array(
            'matte_laminate'  => array(
                'label' => array(
                    'en' => 'Matte laminate',
                    'es' => 'Laminado mate',
                ),
            ),
            'glossy_laminate' => array(
                'label' => array(
                    'en' => 'Glossy laminate',
                    'es' => 'Laminado brillante',
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
            'size'       => '90x50',
            'quantity'   => 100,
            'surface'    => 'matte',
            'printMode'  => '4x0',
            'finish'     => 'matte_laminate',
            'turnaround' => 'next_day',
        ),
        'strings'         => array(
            'product_title' => array(
                'en' => 'Business cards',
                'es' => 'Tarjetas de presentación',
            ),
            'step_size' => array(
                'en' => 'Size',
                'es' => 'Dimensiones',
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
            'step_finish' => array(
                'en' => 'Finish',
                'es' => 'Laminado',
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
                'en' => 'Business cards',
                'es' => 'Tarjetas',
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
                'en' => 'Each press sheet fits {units} business cards in the printable area.',
                'es' => 'Cada hoja de impresión cabe {units} tarjetas en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'quantity_help' => array(
                'en' => 'Minimum {min} cards. Quantity is printed {units} up on each press sheet.',
                'es' => 'Mínimo {min} tarjetas. La cantidad se imprime {units} por hoja de impresión.',
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
