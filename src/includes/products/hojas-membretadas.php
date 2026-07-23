<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Hojas Membretadas (letterhead).
 *
 * @return array<string,mixed>
 */
function sfc_get_hojas_membretadas_product_config() {
    return array(
        'slug'              => 'hojas-membretadas',
        'shortcode'         => 'hojas_membretadas_calculator',
        'language'          => 'es',
        'paperType'         => 'bond',
        'printMode'         => '4x0',
        'minQuantity'       => 100,
        'defaultQuantity'   => 100,
        'sizes'             => array(
            'carta' => array(
                'label' => array(
                    'en' => 'Letter (Carta)',
                    'es' => 'Carta',
                ),
                'widthMm'  => 215.6,
                'heightMm' => 279.4,
            ),
            'a4' => array(
                'label' => array(
                    'en' => 'A4',
                    'es' => 'A4',
                ),
                'widthMm'  => 210,
                'heightMm' => 297,
            ),
        ),
        'turnaround'        => array(
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
        'defaults'          => array(
            'size'       => 'carta',
            'quantity'   => 100,
            'turnaround' => 'next_day',
        ),
        'strings'           => array(
            'product_title' => array(
                'en' => 'Letterhead',
                'es' => 'Hojas membretadas',
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
            'step_turnaround' => array(
                'en' => 'Turnaround',
                'es' => 'Tiempo de entrega',
            ),
            'summary_title' => array(
                'en' => 'Your quote',
                'es' => 'Tu cotización',
            ),
            'units_label' => array(
                'en' => 'Letterheads',
                'es' => 'Hojas membretadas',
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
                'en' => 'Each press sheet fits {units} letterheads in the printable area.',
                'es' => 'Cada hoja de impresión cabe {units} hojas membretadas en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'quantity_help' => array(
                'en' => 'Minimum {min} letterheads. Quantity is printed {units} up on each press sheet.',
                'es' => 'Mínimo {min} hojas membretadas. La cantidad se imprime {units} por hoja de impresión.',
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
