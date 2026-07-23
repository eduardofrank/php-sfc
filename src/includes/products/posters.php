<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Posters (full-sheet 450 × 310 mm).
 *
 * @return array<string,mixed>
 */
function sfc_get_posters_product_config() {
    return array(
        'slug'              => 'posters',
        'shortcode'         => 'posters_calculator',
        'language'          => 'es',
        'printMode'         => '4x0',
        'minQuantity'       => 1,
        'defaultQuantity'   => 5,
        'requireSelection'  => array( 'paper', 'finish' ),
        'sizes'             => array(
            '450x310' => array(
                'label'          => array(
                    'en' => '450 × 310 mm',
                    'es' => '450 × 310 mm',
                ),
                'widthMm'        => 450,
                'heightMm'       => 310,
                'unitsPerSheet'  => 1,
                'impositionCols' => 1,
                'impositionRows' => 1,
            ),
        ),
        'papers'            => array(
            'bond' => array(
                'label'     => array(
                    'en' => 'Bond',
                    'es' => 'Bond',
                ),
                'paperType' => 'bond',
            ),
            'gsm150' => array(
                'label'     => array(
                    'en' => '150 GSM coated',
                    'es' => '150 g/m² recubierto',
                ),
                'paperType' => 'coated',
                'gsm'       => 150,
                'surface'   => 'matte',
            ),
            'gsm200' => array(
                'label'     => array(
                    'en' => '200 GSM coated',
                    'es' => '200 g/m² recubierto',
                ),
                'paperType' => 'coated',
                'gsm'       => 200,
                'surface'   => 'matte',
            ),
            'gsm300' => array(
                'label'     => array(
                    'en' => '300 GSM coated',
                    'es' => '300 g/m² recubierto',
                ),
                'paperType' => 'coated',
                'gsm'       => 300,
                'surface'   => 'matte',
            ),
        ),
        'printModes'        => array(
            '4x0' => array(
                'label' => array(
                    'en' => 'Single-sided (front only)',
                    'es' => 'Solo frente',
                ),
            ),
        ),
        'finishes'          => array(
            'none' => array(
                'label' => array(
                    'en' => 'No finish',
                    'es' => 'Sin acabado',
                ),
            ),
            'glossy_laminate' => array(
                'label' => array(
                    'en' => 'Glossy laminate',
                    'es' => 'Laminado brillante',
                ),
            ),
            'matte_laminate'  => array(
                'label' => array(
                    'en' => 'Matte laminate',
                    'es' => 'Laminado mate',
                ),
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
            'size'       => '450x310',
            'quantity'   => 5,
            'printMode'  => '4x0',
            'turnaround' => 'next_day',
        ),
        'strings'           => array(
            'product_title' => array(
                'en' => 'Posters',
                'es' => 'Afiches',
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
                'en' => 'Posters',
                'es' => 'Afiches',
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
                'en' => 'Each press sheet fits {units} poster in the printable area.',
                'es' => 'Cada hoja de impresión cabe {units} póster en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'quantity_help' => array(
                'en' => 'Minimum {min} posters. Quantity is printed {units} up on each press sheet.',
                'es' => 'Mínimo {min} afiches. La cantidad se imprime {units} por hoja de impresión.',
            ),
            'selection_required' => array(
                'en' => 'Select paper and finish to see your quote.',
                'es' => 'Seleccione papel y acabado para ver su cotización.',
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
