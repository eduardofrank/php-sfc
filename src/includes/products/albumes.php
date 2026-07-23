<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Álbumes (hardcover photo albums).
 *
 * @return array<string,mixed>
 */
function sfc_get_albumes_product_config() {
    return array(
        'slug'                   => 'albumes',
        'shortcode'              => 'albumes_calculator',
        'language'               => 'es',
        'jobType'                => 'album',
        'printMode'              => '4x4',
        'minQuantity'            => 1,
        'emptyDefaultQuantity'   => true,
        'minPages'               => 2,
        'maxPages'               => 500,
        'requireSelection'       => array( 'paper', 'surface', 'hardcover_finish' ),
        'customDimensionLimits'  => array(
            'minWidthMm'  => 50,
            'minHeightMm' => 50,
            'maxWidthMm'  => 450,
            'maxHeightMm' => 310,
        ),
        'stepOrder'              => array(
            'size',
            'customDimensions',
            'pages',
            'quantity',
            'paper',
            'surface',
            'hardcoverFinish',
        ),
        'sizes'                  => array(
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
                'widthMm'        => 431.8,
                'heightMm'       => 279.4,
                'unitsPerSheet'  => 1,
                'impositionCols' => 1,
                'impositionRows' => 1,
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
        'papers'                 => array(
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
            'gsm300' => array(
                'label'     => array(
                    'en' => '300 GSM',
                    'es' => '300 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 300,
            ),
        ),
        'surfaces'               => array(
            'matte' => array(
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
        'hardcoverFinishes'      => array(
            'matte' => array(
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
        'defaults'               => array(
            'size'             => '215.9x279.4',
            'paper'            => 'gsm150',
            'surface'          => 'matte',
            'hardcover_finish'  => 'matte',
        ),
        'strings'                => array(
            'product_title' => array(
                'en' => 'Hardcover albums',
                'es' => 'Álbumes',
            ),
            'step_size' => array(
                'en' => 'Page size',
                'es' => 'Tamaño de página',
            ),
            'step_pages' => array(
                'en' => 'Number of pages',
                'es' => 'Número de páginas',
            ),
            'step_quantity' => array(
                'en' => 'Quantity',
                'es' => 'Cantidad',
            ),
            'step_paper' => array(
                'en' => 'Paper weight',
                'es' => 'Gramaje del papel',
            ),
            'step_surface' => array(
                'en' => 'Paper stock',
                'es' => 'Acabado del papel',
            ),
            'step_hardcover_finish' => array(
                'en' => 'Hardcover finish',
                'es' => 'Acabado tapa dura',
            ),
            'pages_help' => array(
                'en' => 'Total pages per album (both sides count). Must be a multiple of 2. Minimum {min} pages.',
                'es' => 'Total de páginas por álbum (cuentan ambas caras). Debe ser múltiplo de 2. Mínimo {min} páginas.',
            ),
            'quantity_help' => array(
                'en' => 'Number of finished albums.',
                'es' => 'Cantidad de álbumes terminados.',
            ),
            'summary_title' => array(
                'en' => 'Your quote',
                'es' => 'Tu cotización',
            ),
            'units_label' => array(
                'en' => 'Albums',
                'es' => 'Álbumes',
            ),
            'pages_label' => array(
                'en' => 'Pages per album',
                'es' => 'Páginas por álbum',
            ),
            'sheets_label' => array(
                'en' => 'Press sheets',
                'es' => 'Hojas de impresión',
            ),
            'unit_price_label' => array(
                'en' => 'Price per press sheet',
                'es' => 'Precio por hoja de impresión',
            ),
            'hardcover_label' => array(
                'en' => 'Hardcover binding',
                'es' => 'Tapa dura',
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
                'en' => 'Each press sheet fits {units} album pages in the printable area.',
                'es' => 'En cada hoja de impresión caben {units} páginas de álbum en el área imprimible.',
            ),
            'layout_last_row' => array(
                'en' => 'The last sheet uses {filled} of {total} available slots.',
                'es' => 'La última hoja usa {filled} de {total} espacios disponibles.',
            ),
            'selection_required' => array(
                'en' => 'Select paper, stock, and hardcover finish to see your quote.',
                'es' => 'Seleccione gramaje, acabado del papel y tapa dura para ver su cotización.',
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
