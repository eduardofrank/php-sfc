<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once SFC_PLUGIN_DIR . 'includes/products/hojas-membretadas.php';
require_once SFC_PLUGIN_DIR . 'includes/products/tarjetas-de-presentacion.php';
require_once SFC_PLUGIN_DIR . 'includes/products/posters.php';
require_once SFC_PLUGIN_DIR . 'includes/products/postales.php';
require_once SFC_PLUGIN_DIR . 'includes/products/volantes-y-flyers.php';
require_once SFC_PLUGIN_DIR . 'includes/products/catalogos-y-revistas.php';
require_once SFC_PLUGIN_DIR . 'includes/products/folletos-plegados.php';
require_once SFC_PLUGIN_DIR . 'includes/products/etiquetas-rectangulares.php';
require_once SFC_PLUGIN_DIR . 'includes/products/stickers-y-etiquetas.php';
require_once SFC_PLUGIN_DIR . 'includes/products/albumes.php';

/**
 * Registered sheet-fed product definitions.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_product_registry() {
    // Configs are pure data (no option reads), so a per-request cache is safe.
    static $registry = null;

    if ( null === $registry ) {
        $registry = array_merge(
            array(
                'hojas-membretadas'        => sfc_get_hojas_membretadas_product_config(),
                'tarjetas-de-presentacion' => sfc_get_tarjetas_de_presentacion_product_config(),
                'posters'                  => sfc_get_posters_product_config(),
                'postales'                 => sfc_get_postales_product_config(),
                'volantes-y-flyers'        => sfc_get_volantes_y_flyers_product_config(),
                'catalogos-y-revistas'     => sfc_get_catalogos_y_revistas_product_config(),
                'etiquetas-rectangulares'  => sfc_get_etiquetas_rectangulares_product_config(),
                'stickers-y-etiquetas'     => sfc_get_stickers_y_etiquetas_product_config(),
                'albumes'                  => sfc_get_albumes_product_config(),
            ),
            sfc_get_folded_product_registry()
        );
    }

    return $registry;
}

/**
 * Return a product config by slug.
 *
 * @param string $slug Product slug.
 * @return array<string,mixed>|null
 */
function sfc_get_product_config( $slug ) {
    $registry = sfc_get_product_registry();
    $slug     = sanitize_key( str_replace( '_', '-', $slug ) );
    return $registry[ $slug ] ?? null;
}

/**
 * Resolve WooCommerce product ID for a calculator product.
 *
 * @param string $slug Product slug.
 * @return int
 */
function sfc_get_product_woo_id( $slug ) {
    $option_key = 'sfc_product_' . sanitize_key( str_replace( '-', '_', $slug ) ) . '_product_id';
    $product_id = absint( get_option( $option_key, 0 ) );

    if ( $product_id ) {
        return $product_id;
    }

    if ( function_exists( 'is_product' ) && is_product() ) {
        global $post;
        if ( $post instanceof WP_Post && $post->post_name === str_replace( '_', '-', $slug ) ) {
            return (int) $post->ID;
        }
    }

    return 0;
}

/**
 * Return localized product strings for the active language.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<string,string>
 */
function sfc_get_product_language( $product ) {
    $language = $product['language'] ?? sfc_get_language();
    return in_array( $language, array( 'en', 'es' ), true ) ? $language : sfc_get_language();
}

/**
 * Return fixed print mode for a product calculator.
 *
 * @param array<string,mixed> $product Product config.
 * @return string
 */
function sfc_get_product_print_mode( $product ) {
    $print_mode = sanitize_key( $product['printMode'] ?? '4x0' );
    return in_array( $print_mode, sfc_get_print_modes(), true ) ? $print_mode : '4x0';
}

/**
 * Return localized product strings for the active language.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<string,string>
 */
function sfc_get_product_strings( $product ) {
    $language = sfc_get_product_language( $product );
    $strings  = array();

    foreach ( (array) ( $product['strings'] ?? array() ) as $key => $translations ) {
        if ( is_array( $translations ) ) {
            $strings[ $key ] = $translations[ $language ] ?? $translations['en'] ?? $key;
        } else {
            $strings[ $key ] = (string) $translations;
        }
    }

    return $strings;
}

/**
 * Whether a product hides flat-style imposition waste warnings and last-sheet captions.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_product_suppresses_imposition_waste_ui( $product ) {
    return ! empty( $product['suppressImpositionWasteUi'] );
}

/**
 * Whether the calculator shows custom dimension fields only (no size presets).
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_product_uses_custom_dimensions_only( $product ) {
    return ! empty( $product['customDimensionsOnly'] );
}

/**
 * Whether the calculator offers circular vs free-form die-cut shape choices.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_product_has_die_cut_shape_choice( $product ) {
    return ! empty( $product['dieCutShapes'] ) && is_array( $product['dieCutShapes'] );
}

/**
 * Whether the calculator collects finished closed booklet dimensions.
 *
 * @param array<string,mixed> $product Product config.
 * @return bool
 */
function sfc_product_uses_closed_booklet_dimensions( $product ) {
    return ! empty( $product['bookletClosedDimensions'] );
}
