<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Centralized EN/ES string map for setting-driven admin localization.
 *
 * @return array<string,array<string,string>>
 */
function sfc_get_string_map() {
    return array(
        'menu_title' => array(
            'en' => 'Sheet Fed Calc',
            'es' => 'Sheet Fed Calc',
        ),
        'settings_title' => array(
            'en' => 'Settings',
            'es' => 'Configuración',
        ),
        'pricing_title' => array(
            'en' => 'Price Table',
            'es' => 'Tabla de precios',
        ),
        'language_label' => array(
            'en' => 'Language',
            'es' => 'Idioma',
        ),
        'language_en' => array(
            'en' => 'English',
            'es' => 'Inglés',
        ),
        'language_es' => array(
            'en' => 'Spanish',
            'es' => 'Español',
        ),
        'units_label' => array(
            'en' => 'Units',
            'es' => 'Unidades',
        ),
        'units_metric' => array(
            'en' => 'Metric (mm)',
            'es' => 'Métrico (mm)',
        ),
        'units_imperial' => array(
            'en' => 'Imperial (in)',
            'es' => 'Imperial (in)',
        ),
        'sheet_specs_title' => array(
            'en' => 'Sheet dimensions',
            'es' => 'Dimensiones de la hoja',
        ),
        'sheet_cut_size' => array(
            'en' => 'Cut sheet size',
            'es' => 'Tamaño de hoja cortada',
        ),
        'sheet_printable_area' => array(
            'en' => 'Printable area',
            'es' => 'Área imprimible',
        ),
        'imposition_gap_label' => array(
            'en' => 'Imposition gap',
            'es' => 'Separación de montaje',
        ),
        'imposition_gap_note' => array(
            'en' => 'Space between imposed units on the press sheet, in millimeters.',
            'es' => 'Espacio entre piezas montadas en la hoja de impresión, en milímetros.',
        ),
        'fulfillment_title' => array(
            'en' => 'Fulfillment and same-day turnaround',
            'es' => 'Entrega y tiempo mismo día',
        ),
        'fulfillment_note' => array(
            'en' => 'Same-day surcharge percentage is edited under Price Table → Turnaround rates.',
            'es' => 'El recargo de mismo día se edita en Tabla de precios → Tiempos de entrega.',
        ),
        'fulfillment_shop_city_label' => array(
            'en' => 'Shop city',
            'es' => 'Ciudad de la imprenta',
        ),
        'fulfillment_shop_city_note' => array(
            'en' => 'Used to match local delivery addresses at checkout and in customer-facing messages.',
            'es' => 'Se usa para comparar direcciones de entrega local en el checkout y en mensajes al cliente.',
        ),
        'fulfillment_same_day_calculator_label' => array(
            'en' => 'Offer same-day on calculator product pages',
            'es' => 'Ofrecer mismo día en páginas de producto con calculadora',
        ),
        'fulfillment_same_day_calculator_note' => array(
            'en' => 'When disabled, same-day is hidden in the calculator unless a developer filter overrides it.',
            'es' => 'Si se desactiva, mismo día se oculta en la calculadora salvo que un filtro del desarrollador lo habilite.',
        ),
        'fulfillment_same_day_pickup_label' => array(
            'en' => 'Allow same-day checkout with local pickup',
            'es' => 'Permitir mismo día en checkout con retiro en tienda',
        ),
        'fulfillment_same_day_pickup_note' => array(
            'en' => 'Treat WooCommerce local pickup shipping methods as local fulfillment.',
            'es' => 'Considerar métodos de retiro en tienda de WooCommerce como entrega local.',
        ),
        'fulfillment_same_day_city_label' => array(
            'en' => 'Allow same-day checkout for shop-city delivery',
            'es' => 'Permitir mismo día en checkout con entrega en la ciudad de la imprenta',
        ),
        'fulfillment_same_day_city_note' => array(
            'en' => 'When the shipping city matches the shop city above, same-day checkout is allowed.',
            'es' => 'Cuando la ciudad de envío coincide con la ciudad de la imprenta, se permite mismo día en checkout.',
        ),
        'options_portability_title' => array(
            'en' => 'Backup and restore',
            'es' => 'Respaldo y restauración',
        ),
        'options_portability_note' => array(
            'en' => 'Export or import all Sheet Fed Calc settings, price tables, and calculator mappings as JSON.',
            'es' => 'Exporte o importe toda la configuración de Sheet Fed Calc, tablas de precios y mapeos de calculadoras en JSON.',
        ),
        'export_options' => array(
            'en' => 'Export settings',
            'es' => 'Exportar configuración',
        ),
        'import_options' => array(
            'en' => 'Import settings',
            'es' => 'Importar configuración',
        ),
        'import_options_file' => array(
            'en' => 'Settings JSON file',
            'es' => 'Archivo JSON de configuración',
        ),
        'import_confirm' => array(
            'en' => 'Importing will overwrite existing Sheet Fed Calc settings on this site. Continue?',
            'es' => 'La importación sobrescribirá la configuración existente de Sheet Fed Calc en este sitio. ¿Continuar?',
        ),
        'export_ready_success' => array(
            'en' => 'Settings export is ready.',
            'es' => 'La exportación de configuración está lista.',
        ),
        'import_success' => array(
            'en' => 'Settings imported successfully.',
            'es' => 'Configuración importada correctamente.',
        ),
        'import_error' => array(
            'en' => 'Could not import settings.',
            'es' => 'No se pudo importar la configuración.',
        ),
        'ops_digest_label' => array(
            'en' => 'Daily ops digest',
            'es' => 'Resumen diario de operaciones',
        ),
        'ops_digest_note' => array(
            'en' => 'Email a morning summary: orders awaiting artwork, preflight warnings, yesterday\'s errors.',
            'es' => 'Enviar un resumen matutino: pedidos esperando arte, advertencias de preflight, errores de ayer.',
        ),
        'ops_digest_recipient_label' => array(
            'en' => 'Digest recipient',
            'es' => 'Destinatario del resumen',
        ),
        'ops_digest_recipient_note' => array(
            'en' => 'Email address for the daily digest. Defaults to the site admin email.',
            'es' => 'Correo para el resumen diario. Por defecto, el correo del administrador del sitio.',
        ),
        'retention_quote_label' => array(
            'en' => 'Saved quote retention (days)',
            'es' => 'Retención de cotizaciones guardadas (días)',
        ),
        'retention_quote_note' => array(
            'en' => 'Days a saved/shareable quote stays available before the daily cleanup removes it. 7–3650.',
            'es' => 'Días que una cotización guardada/compartible permanece disponible antes de que la limpieza diaria la elimine. 7–3650.',
        ),
        'retention_artwork_label' => array(
            'en' => 'Unclaimed artwork retention (days)',
            'es' => 'Retención de arte sin reclamar (días)',
        ),
        'retention_artwork_note' => array(
            'en' => 'Days an uploaded file not attached to any order is kept. Artwork on orders is never removed. 1–365.',
            'es' => 'Días que se conserva un archivo subido que no pertenece a ningún pedido. El arte de los pedidos nunca se elimina. 1–365.',
        ),
        'save_settings' => array(
            'en' => 'Save settings',
            'es' => 'Guardar configuración',
        ),
        'save_pricing' => array(
            'en' => 'Save price table',
            'es' => 'Guardar tabla de precios',
        ),
        'saving' => array(
            'en' => 'Saving…',
            'es' => 'Guardando…',
        ),
        'saved_success' => array(
            'en' => 'Settings saved successfully.',
            'es' => 'Configuración guardada correctamente.',
        ),
        'pricing_saved_success' => array(
            'en' => 'Price table saved successfully.',
            'es' => 'Tabla de precios guardada correctamente.',
        ),
        'save_error' => array(
            'en' => 'Could not save changes.',
            'es' => 'No se pudieron guardar los cambios.',
        ),
        'network_error' => array(
            'en' => 'Network error while saving.',
            'es' => 'Error de red al guardar.',
        ),
        'quantity_tiers' => array(
            'en' => 'Quantity tiers (sheets per job)',
            'es' => 'Niveles de cantidad (hojas por trabajo)',
        ),
        'print_mode' => array(
            'en' => 'Print mode',
            'es' => 'Modo de impresión',
        ),
        'print_mode_4x0' => array(
            'en' => '4x0 — single-sided',
            'es' => '4x0 — una cara',
        ),
        'print_mode_4x4' => array(
            'en' => '4x4 — duplex (two-sided)',
            'es' => '4x4 — doble cara',
        ),
        'print_mode_per_side' => array(
            'en' => 'Per sheet side',
            'es' => 'Por cara de hoja',
        ),
        'price_usd' => array(
            'en' => 'Price (USD per sheet)',
            'es' => 'Precio (USD por hoja)',
        ),
        'price_usd_per_side' => array(
            'en' => 'Price (USD per sheet side)',
            'es' => 'Precio (USD por cara de hoja)',
        ),
        'table_coated_115_150' => array(
            'en' => 'Coated paper 115–150 GSM',
            'es' => 'Papel recubierto 115–150 GSM',
        ),
        'table_coated_200_300' => array(
            'en' => 'Coated paper 200–300 GSM',
            'es' => 'Papel recubierto 200–300 GSM',
        ),
        'table_lithosticker' => array(
            'en' => 'Lithosticker (self-adhering)',
            'es' => 'Lithosticker (autoadherible)',
        ),
        'table_vinyl' => array(
            'en' => 'Vinyl sticker',
            'es' => 'Vinil adhesivo',
        ),
        'table_lamination' => array(
            'en' => 'Lamination (per press sheet side)',
            'es' => 'Laminado (por cara de hoja de impresión)',
        ),
        'table_hardcover_binding' => array(
            'en' => 'Hardcover binding (per album)',
            'es' => 'Tapa dura (por álbum)',
        ),
        'bond_uses_coated' => array(
            'en' => 'Bond paper uses the Coated 115–150 GSM price table.',
            'es' => 'El papel bond usa la tabla de precios de Papel recubierto 115–150 GSM.',
        ),
        'lamination_pricing_note' => array(
            'en' => 'Lamination is charged per press sheet side (one side for 4x0 jobs, two sides for 4x4). Matte and gloss laminate share the same rate.',
            'es' => 'El laminado se cobra por cara de hoja de impresión (una cara en 4x0, dos caras en 4x4). Laminado mate y brillante usan la misma tarifa.',
        ),
        'artwork_pdfx_label' => array(
            'en' => 'Artwork uploads',
            'es' => 'Carga de arte',
        ),
        'artwork_pdfx_note' => array(
            'en' => 'Require PDF/X (recommended). Unchecked, any valid PDF is accepted.',
            'es' => 'Exigir PDF/X (recomendado). Desmarcado, se acepta cualquier PDF válido.',
        ),
        'funnel_title' => array(
            'en' => 'Statistics',
            'es' => 'Estadísticas',
        ),
        'funnel_note' => array(
            'en' => 'Quote funnel per product. "Quotes" counts server calculations (one customer adjusting options generates several); conversion rates are comparable across products. Aggregates only — no customer data is stored.',
            'es' => 'Embudo de cotizaciones por producto. "Cotizaciones" cuenta cálculos del servidor (un cliente ajustando opciones genera varios); las tasas de conversión son comparables entre productos. Solo agregados — no se guardan datos de clientes.',
        ),
        'funnel_last_7_days' => array(
            'en' => 'Last 7 days',
            'es' => 'Últimos 7 días',
        ),
        'funnel_last_30_days' => array(
            'en' => 'Last 30 days',
            'es' => 'Últimos 30 días',
        ),
        'funnel_last_90_days' => array(
            'en' => 'Last 90 days',
            'es' => 'Últimos 90 días',
        ),
        'funnel_no_data' => array(
            'en' => 'No data yet for this period.',
            'es' => 'Aún no hay datos para este período.',
        ),
        'funnel_product' => array(
            'en' => 'Product',
            'es' => 'Producto',
        ),
        'funnel_quotes' => array(
            'en' => 'Quotes',
            'es' => 'Cotizaciones',
        ),
        'funnel_carts' => array(
            'en' => 'Added to cart',
            'es' => 'Al carrito',
        ),
        'funnel_orders' => array(
            'en' => 'Ordered',
            'es' => 'Pedidos',
        ),
        'funnel_cart_rate' => array(
            'en' => 'Quote → cart',
            'es' => 'Cotización → carrito',
        ),
        'funnel_order_rate' => array(
            'en' => 'Cart → order',
            'es' => 'Carrito → pedido',
        ),
        'funnel_order_value' => array(
            'en' => 'Ordered value',
            'es' => 'Valor pedido',
        ),
        'trade_pricing_title' => array(
            'en' => 'Trade pricing (per-role discount)',
            'es' => 'Precios mayoristas (descuento por rol)',
        ),
        'trade_pricing_note' => array(
            'en' => 'Discount applied to the final quote total for logged-in customers with the role. A customer with several roles gets the highest discount. 0 disables the role.',
            'es' => 'Descuento aplicado al total final de la cotización para clientes registrados con el rol. Un cliente con varios roles recibe el descuento mayor. 0 desactiva el rol.',
        ),
        'trade_role_label' => array(
            'en' => 'Role',
            'es' => 'Rol',
        ),
        'trade_percent_label' => array(
            'en' => 'Discount %',
            'es' => 'Descuento %',
        ),
        'job_services_title' => array(
            'en' => 'Job services (% of subtotal)',
            'es' => 'Servicios del trabajo (% del subtotal)',
        ),
        'job_services_note' => array(
            'en' => 'Each service adds its percentage to the job subtotal after print and lamination. Applied to every calculator; hidden from the public UI except in the total.',
            'es' => 'Cada servicio suma su porcentaje al subtotal del trabajo después de impresión y laminado. Se aplica en todas las calculadoras; no se muestra en la interfaz pública excepto en el total.',
        ),
        'service_percent_label' => array(
            'en' => 'Rate (%)',
            'es' => 'Tarifa (%)',
        ),
        'service_cutting' => array(
            'en' => 'Cutting',
            'es' => 'Corte',
        ),
        'service_creasing' => array(
            'en' => 'Creasing',
            'es' => 'Doblado',
        ),
        'service_stapling' => array(
            'en' => 'Stapling',
            'es' => 'Grapado',
        ),
        'die_cut_rates_title' => array(
            'en' => 'Die-cutting (% of print cost)',
            'es' => 'Troquelado (% del costo de impresión)',
        ),
        'die_cut_rates_note' => array(
            'en' => 'Applied to die-cut sticker jobs by total press sheet count. The percentage is calculated from the print cost only.',
            'es' => 'Se aplica a stickers troquelados según el total de hojas de impresión. El porcentaje se calcula solo sobre el costo de impresión.',
        ),
        'die_cut_tier_label' => array(
            'en' => 'Press sheets',
            'es' => 'Hojas de impresión',
        ),
        'die_cut_tier_up_to_50' => array(
            'en' => 'Up to 50 sheets',
            'es' => 'Hasta 50 hojas',
        ),
        'die_cut_tier_up_to_100' => array(
            'en' => '51 to 100 sheets',
            'es' => 'De 51 a 100 hojas',
        ),
        'die_cut_tier_above_100' => array(
            'en' => 'More than 100 sheets',
            'es' => 'Más de 100 hojas',
        ),
        'turnaround_rates_title' => array(
            'en' => 'Turnaround (% of subtotal)',
            'es' => 'Tiempo de entrega (% del subtotal)',
        ),
        'turnaround_rates_note' => array(
            'en' => 'Markup applied to the job subtotal after print, lamination, and job services for same-day turnaround.',
            'es' => 'Recargo aplicado al subtotal del trabajo después de impresión, laminado y servicios para entrega el mismo día.',
        ),
        'turnaround_same_day' => array(
            'en' => 'Same day',
            'es' => 'Mismo día',
        ),
        'paper_coated' => array(
            'en' => 'Coated paper',
            'es' => 'Papel recubierto',
        ),
        'paper_lithosticker' => array(
            'en' => 'Lithosticker',
            'es' => 'Lithosticker',
        ),
        'paper_vinyl' => array(
            'en' => 'Vinyl sticker',
            'es' => 'Vinil',
        ),
        'paper_bond' => array(
            'en' => 'Bond paper',
            'es' => 'Papel bond',
        ),
        'surface_matte' => array(
            'en' => 'Matte',
            'es' => 'Mate',
        ),
        'surface_glossy' => array(
            'en' => 'Glossy',
            'es' => 'Brillante',
        ),
        'phase1_note' => array(
            'en' => 'Use Settings for language, units, imposition gap, fulfillment rules, and JSON backup/restore. Use Price Table for bracketed print prices, lamination, job services, and turnaround rates.',
            'es' => 'Use Configuración para idioma, unidades, separación de montaje, reglas de entrega y respaldo/restauración JSON. Use Tabla de precios para tarifas de impresión, laminado, servicios y tiempos de entrega.',
        ),
        'tier_1_10' => array(
            'en' => '1–10',
            'es' => '1–10',
        ),
        'tier_11_25' => array(
            'en' => '11–25',
            'es' => '11–25',
        ),
        'tier_26_50' => array(
            'en' => '26–50',
            'es' => '26–50',
        ),
        'tier_51_100' => array(
            'en' => '51–100',
            'es' => '51–100',
        ),
        'tier_101_200' => array(
            'en' => '101–200',
            'es' => '101–200',
        ),
        'tier_201_300' => array(
            'en' => '201–300',
            'es' => '201–300',
        ),
        'tier_301_plus' => array(
            'en' => '301+',
            'es' => '301+',
        ),
    );
}

/**
 * Resolve a localized string for the active plugin language setting.
 *
 * @param string $key String map key.
 * @return string
 */
function sfc_t( $key ) {
    $map      = sfc_get_string_map();
    $language = function_exists( 'sfc_get_language' ) ? sfc_get_language() : sfc_get_default_language();

    if ( isset( $map[ $key ][ $language ] ) ) {
        return $map[ $key ][ $language ];
    }

    if ( isset( $map[ $key ]['en'] ) ) {
        return $map[ $key ]['en'];
    }

    return (string) $key;
}

/**
 * Return localized label for a quantity tier key.
 *
 * @param string $tier_key Tier key.
 * @return string
 */
function sfc_tier_label( $tier_key ) {
    $label_key = 'tier_' . $tier_key;
    return sfc_t( $label_key );
}

/**
 * Admin label for a price-table print mode row.
 *
 * @param string $mode Print mode key.
 * @return string
 */
function sfc_print_mode_admin_label( $mode ) {
    if ( '4x4' === $mode ) {
        return sfc_t( 'print_mode_4x4' );
    }
    if ( 'per_side' === $mode ) {
        return sfc_t( 'print_mode_per_side' );
    }
    return sfc_t( 'print_mode_4x0' );
}

/**
 * Format millimeters for display according to units setting.
 *
 * @param int|float $mm Value in millimeters.
 * @param int       $precision Decimal precision for imperial output.
 * @return string
 */
function sfc_format_length( $mm, $precision = 2 ) {
    $units = function_exists( 'sfc_get_units' ) ? sfc_get_units() : sfc_get_default_units();

    if ( 'imperial' === $units ) {
        $inches = (float) $mm / 25.4;
        return number_format( $inches, $precision ) . ' in';
    }

    return (int) $mm . ' mm';
}

/**
 * Format a width x height pair for display.
 *
 * @param int|float $width_mm  Width in millimeters.
 * @param int|float $height_mm Height in millimeters.
 * @return string
 */
function sfc_format_dimensions( $width_mm, $height_mm ) {
    return sfc_format_length( $width_mm ) . ' × ' . sfc_format_length( $height_mm );
}

/**
 * Export string map for admin JavaScript.
 *
 * @return array<string,string>
 */
function sfc_get_admin_strings() {
    $map      = sfc_get_string_map();
    $language = sfc_get_language();
    $strings  = array();

    foreach ( $map as $key => $translations ) {
        $strings[ $key ] = $translations[ $language ] ?? $translations['en'] ?? $key;
    }

    return $strings;
}
