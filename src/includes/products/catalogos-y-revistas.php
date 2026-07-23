<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product config: Catálogos y Revistas (saddle-stitched booklets).
 *
 * Preset and custom dimensions are the finished closed booklet size.
 *
 * @return array<string,mixed>
 */
function sfc_get_catalogos_y_revistas_product_config() {
    return array(
        'slug'                   => 'catalogos-y-revistas',
        'shortcode'              => 'catalogos_y_revistas_calculator',
        'language'               => 'es',
        'jobType'                => 'booklet',
        'bookletClosedDimensions' => true,
        'minQuantity'            => 1,
        'emptyDefaultQuantity'   => true,
        'innerPrintMode'         => '4x4',
        'jobServices'            => array( 'stapling' ),
        'suppressImpositionWasteUi' => true,
        'customDimensionLimits'  => array(
            'minWidthMm'  => 50,
            'minHeightMm' => 50,
            'maxWidthMm'  => 225,
            'maxHeightMm' => 310,
        ),
        'sizes'                  => array(
            '108x139.7' => array(
                'label'    => array(
                    'en' => 'Quarter Letter (108 × 139.7 mm)',
                    'es' => 'Cuarto de Carta (108 × 139.7 mm)',
                ),
                'widthMm'  => 108,
                'heightMm' => 139.7,
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
            'bond'   => array(
                'label'     => array(
                    'en' => 'Bond',
                    'es' => 'Bond',
                ),
                'paperType' => 'bond',
            ),
            'gsm115' => array(
                'label'     => array(
                    'en' => '115 GSM',
                    'es' => '115 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 115,
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
            'gsm300' => array(
                'label'     => array(
                    'en' => '300 GSM',
                    'es' => '300 g',
                ),
                'paperType' => 'coated',
                'gsm'       => 300,
            ),
        ),
        'innerPapers'          => array( 'bond', 'gsm115', 'gsm150', 'gsm200' ),
        'surfaces'             => array(
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
        'coverPrintModes'      => array(
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
        'coverFinishes'        => array(
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
        'coverFinishSides'     => array(
            'external' => array(
                'label' => array(
                    'en' => 'External only',
                    'es' => 'Solo exterior',
                ),
            ),
            'both'     => array(
                'label' => array(
                    'en' => 'Both sides',
                    'es' => 'Ambos lados',
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
            'turnaround' => 'next_day',
        ),
        'strings'              => array(
            'product_title' => array(
                'en' => 'Catalogs & magazines',
                'es' => 'Catálogos y revistas',
            ),
            'step_size' => array(
                'en' => 'Closed size',
                'es' => 'Tamaño cerrado',
            ),
            'size_closed_help' => array(
                'en' => 'Dimensions are the finished closed booklet — width × height as the product sits in the reader’s hands.',
                'es' => 'Las dimensiones son del catálogo o revista terminado y cerrado — ancho × alto como lo ve el lector.',
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
            'quantity_help' => array(
                'en' => 'Total number of finished booklets to produce.',
                'es' => 'Cantidad total de ejemplares terminados (catálogos o revistas).',
            ),
            'step_inner_pages' => array(
                'en' => 'Inner pages',
                'es' => 'Páginas tripa',
            ),
            'inner_pages_help' => array(
                'en' => 'Body page count only (multiple of 4). Cover adds 4 pages separately. Maximum {max} inner pages (10 nested sheets including cover).',
                'es' => 'Solo páginas interiores (múltiplo de 4). La portada suma 4 páginas aparte. Máximo {max} páginas tripa (10 hojas anidadas incluyendo portada).',
            ),
            'step_inner_paper' => array(
                'en' => 'Inner paper weight',
                'es' => 'Peso del papel — tripa',
            ),
            'step_inner_surface' => array(
                'en' => 'Inner paper stock',
                'es' => 'Stock del papel — tripa',
            ),
            'step_cover_weight' => array(
                'en' => 'Cover weight',
                'es' => 'Peso de portada',
            ),
            'cover_same_as_inner' => array(
                'en' => 'Same as inner',
                'es' => 'Igual tripa',
            ),
            'step_cover_print' => array(
                'en' => 'Cover print sides',
                'es' => 'Caras impresas — portada',
            ),
            'step_cover_surface' => array(
                'en' => 'Cover paper stock',
                'es' => 'Stock del papel — portada',
            ),
            'step_cover_finish' => array(
                'en' => 'Cover finish',
                'es' => 'Acabado de portada',
            ),
            'step_cover_finish_sides' => array(
                'en' => 'Lamination sides',
                'es' => 'Lados de laminado',
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
                'en' => 'Booklets',
                'es' => 'Ejemplares',
            ),
            'sheets_label' => array(
                'en' => 'Press sheets',
                'es' => 'Hojas de impresión',
            ),
            'inner_sheets_label' => array(
                'en' => 'Inner press sheets',
                'es' => 'Hojas de impresión — tripa',
            ),
            'cover_sheets_label' => array(
                'en' => 'Cover press sheets',
                'es' => 'Hojas de impresión — portada',
            ),
            'signature_sheets_label' => array(
                'en' => 'Nested signature sheets',
                'es' => 'Pliegos de firma anidados',
            ),
            'sheets_total_label' => array(
                'en' => 'Total press sheets',
                'es' => 'Total hojas de impresión',
            ),
            'unit_price_label' => array(
                'en' => 'Avg. price per press sheet',
                'es' => 'Precio prom. por hoja de impresión',
            ),
            'total_label' => array(
                'en' => 'Total',
                'es' => 'Total',
            ),
            'layout_title' => array(
                'en' => 'Inner page layout preview',
                'es' => 'Vista previa — montaje tripa',
            ),
            'layout_inner_caption' => array(
                'en' => 'Inner pages on press sheet: {units} per sheet',
                'es' => 'Páginas tripa por hoja de impresión: {units} por hoja',
            ),
            'layout_caption' => array(
                'en' => '{units} per press sheet',
                'es' => '{units} por hoja de impresión',
            ),
            'layout_last_row' => array(
                'en' => 'Last sheet: {filled} of {total} slots used',
                'es' => 'Última hoja: {filled} de {total} posiciones usadas',
            ),
            'selection_required' => array(
                'en' => 'Complete all required options to see your quote.',
                'es' => 'Complete todas las opciones requeridas para ver su cotización.',
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
                'en' => 'Could not complete the request.',
                'es' => 'No se pudo completar la solicitud.',
            ),
        ),
    );
}
