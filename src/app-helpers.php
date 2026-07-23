<?php
/**
 * Standalone-app glue: quote seeding, product listing for the landing page,
 * and file-based save/share persistence. None of this is part of the ported
 * pricing engine — it replaces the WordPress/WooCommerce plumbing that the
 * plugin used for the same jobs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Seed provider consumed by public-data.php. product.php sets the global from a
 * loaded saved quote before building the JS payload; otherwise there is no seed.
 *
 * @param string $slug     Product slug.
 * @param string $language Active language.
 * @return array{state:array,notice:?string}|null
 */
function sfc_get_calculator_seed_for_product( $slug, $language ) {
    if ( empty( $GLOBALS['sfc_seed'] ) || ! is_array( $GLOBALS['sfc_seed'] ) ) {
        return null;
    }

    $seed = $GLOBALS['sfc_seed'];
    if ( ( $seed['slug'] ?? '' ) !== $slug ) {
        return null;
    }

    return array(
        'state'  => (array) ( $seed['state'] ?? array() ),
        'notice' => $seed['notice'] ?? null,
    );
}

/**
 * Slugs that route to the folded-brochure hub rather than a pricing product.
 *
 * @param string $slug Product slug.
 * @return bool
 */
function sfc_app_is_fold_hub_slug( $slug ) {
    return in_array( $slug, array( 'folletos-plegados', 'folletos-plegables' ), true );
}

/**
 * Ordered product cards for the landing page: the nine pricing products plus
 * the folded-brochure hub as the tenth choice.
 *
 * @return array<int,array<string,string>>
 */
function sfc_app_get_landing_products() {
    $order = array(
        'hojas-membretadas',
        'tarjetas-de-presentacion',
        'posters',
        'postales',
        'volantes-y-flyers',
        'catalogos-y-revistas',
        'etiquetas-rectangulares',
        'stickers-y-etiquetas',
        'albumes',
    );

    $subtitles = array(
        'hojas-membretadas'        => 'Papelería corporativa en papel bond, carta o A4.',
        'tarjetas-de-presentacion' => 'Tarjetas en papel recubierto 300 g, mate o brillante.',
        'posters'                  => 'Afiches a gran formato en una sola cara.',
        'postales'                 => 'Postales en tamaño estándar o dimensiones a medida.',
        'volantes-y-flyers'        => 'Volantes promocionales en varios tamaños y papeles.',
        'catalogos-y-revistas'     => 'Cuadernillos grapados con tripa y portada.',
        'etiquetas-rectangulares'  => 'Stickers rectangulares a medida en lithosticker o vinil.',
        'stickers-y-etiquetas'     => 'Stickers troquelados: circulares o de forma libre.',
        'albumes'                  => 'Álbumes de tapa dura impresos a doble cara.',
        'folletos-plegados'        => 'Folletos plegables: medio, tríptico, en Z, puerta y más.',
    );

    $cards = array();

    foreach ( $order as $slug ) {
        $config = sfc_get_product_config( $slug );
        if ( ! $config ) {
            continue;
        }
        $strings = sfc_get_product_strings( $config );
        $cards[] = array(
            'slug'     => $slug,
            'title'    => $strings['product_title'] ?? $slug,
            'subtitle' => $subtitles[ $slug ] ?? '',
            'href'     => 'product.php?product=' . rawurlencode( $slug ),
            'isHub'    => false,
        );
    }

    // Tenth choice: the folded-brochure hub (six fold variants live under it).
    $cards[] = array(
        'slug'     => 'folletos-plegados',
        'title'    => 'Folletos plegables',
        'subtitle' => $subtitles['folletos-plegados'],
        'href'     => 'product.php?product=folletos-plegados',
        'isHub'    => true,
    );

    return $cards;
}

/* -------------------------------------------------------------------------
 * File-based saved / shareable quotes
 * ---------------------------------------------------------------------- */

/**
 * Absolute path to the saved-quotes directory.
 *
 * @return string
 */
function sfc_app_quotes_dir() {
    return SFC_APP_DIR . '/data/quotes';
}

/**
 * Validate a quote id (defends the file path against traversal).
 *
 * @param string $id Quote id.
 * @return bool
 */
function sfc_app_is_valid_quote_id( $id ) {
    return (bool) preg_match( '/^[a-f0-9]{24}$/', (string) $id );
}

/**
 * Persist a configuration as a shareable quote.
 *
 * The price is never trusted from the client: callers re-quote server-side
 * before saving so a stored record always reflects a valid configuration.
 *
 * @param string              $slug  Product slug.
 * @param array<string,mixed> $state Calculator state.
 * @return string|WP_Error Quote id on success.
 */
function sfc_app_save_quote( $slug, $state ) {
    $dir = sfc_app_quotes_dir();
    if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, true ) && ! is_dir( $dir ) ) {
        return new WP_Error( 'save_failed', 'No se pudo guardar la cotización.' );
    }

    try {
        $id = bin2hex( random_bytes( 12 ) );
    } catch ( Exception $e ) {
        return new WP_Error( 'save_failed', 'No se pudo guardar la cotización.' );
    }

    $record = array(
        'id'    => $id,
        'slug'  => $slug,
        'state' => $state,
        'ts'    => time(),
    );

    $bytes = file_put_contents(
        $dir . '/' . $id . '.json',
        wp_json_encode_compat( $record ),
        LOCK_EX
    );

    if ( false === $bytes ) {
        return new WP_Error( 'save_failed', 'No se pudo guardar la cotización.' );
    }

    return $id;
}

/**
 * Load a saved quote record by id.
 *
 * @param string $id Quote id.
 * @return array<string,mixed>|null
 */
function sfc_app_load_quote( $id ) {
    if ( ! sfc_app_is_valid_quote_id( $id ) ) {
        return null;
    }

    $path = sfc_app_quotes_dir() . '/' . $id . '.json';
    if ( ! is_file( $path ) ) {
        return null;
    }

    $decoded = json_decode( (string) file_get_contents( $path ), true );
    return is_array( $decoded ) ? $decoded : null;
}

/**
 * JSON-encode helper (kept separate so the engine's wp_json_encode is not needed).
 *
 * @param mixed $data Data to encode.
 * @return string
 */
function wp_json_encode_compat( $data ) {
    return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/* -------------------------------------------------------------------------
 * JSON API envelopes (mirror wp_send_json_success / wp_send_json_error)
 * ---------------------------------------------------------------------- */

/**
 * Emit a WordPress-style success envelope and exit.
 *
 * @param mixed $data Payload.
 * @return void
 */
function sfc_app_send_success( $data ) {
    header( 'Content-Type: application/json; charset=utf-8' );
    echo wp_json_encode_compat( array( 'success' => true, 'data' => $data ) );
    exit;
}

/**
 * Emit a WordPress-style error envelope and exit.
 *
 * @param string $message Human-readable message.
 * @param string $code    Machine code.
 * @return void
 */
function sfc_app_send_error( $message, $code = 'error' ) {
    header( 'Content-Type: application/json; charset=utf-8' );
    echo wp_json_encode_compat(
        array(
            'success' => false,
            'data'    => array( 'message' => $message, 'code' => $code ),
        )
    );
    exit;
}
