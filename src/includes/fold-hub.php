<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fold types shown in the visual hub (Option A).
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_fold_hub_items() {
    return array(
        'half-fold' => array(
            'label'   => array(
                'en' => 'Half fold',
                'es' => 'Pliegue al medio',
            ),
            'icon'    => 'Half-Fold.png',
            'default' => true,
        ),
        'tri-fold' => array(
            'label' => array(
                'en' => 'Tri-fold',
                'es' => 'Plegado en tres',
            ),
            'icon'  => 'Tri-Fold.png',
        ),
        'z-fold' => array(
            'label' => array(
                'en' => 'Z-fold',
                'es' => 'Plegado en Z',
            ),
            'icon'  => 'Z-Fold.png',
        ),
        'gate-fold' => array(
            'label' => array(
                'en' => 'Gate fold',
                'es' => 'Plegado en puerta',
            ),
            'icon'  => '3-panel-gate-fold.png',
        ),
        'french-fold' => array(
            'label' => array(
                'en' => 'French fold',
                'es' => 'Plegado francés',
            ),
            'icon'  => 'French-Fold.png',
        ),
        'accordion-4-panel' => array(
            'label' => array(
                'en' => '4-panel accordion',
                'es' => 'Acordeón 4 paneles',
            ),
            'icon'  => '4-panel-accordion.png',
        ),
    );
}

/**
 * WooCommerce product slugs that host the fold hub.
 *
 * @return string[]
 */
function sfc_get_fold_hub_page_slugs() {
    $slugs = array( 'folletos-plegables' );
    return apply_filters( 'sfc_fold_hub_page_slugs', $slugs );
}

/**
 * Default fold slug when none is selected.
 *
 * @return string
 */
function sfc_get_fold_hub_default_slug() {
    foreach ( sfc_get_fold_hub_items() as $slug => $item ) {
        if ( ! empty( $item['default'] ) ) {
            return $slug;
        }
    }

    $items = sfc_get_fold_hub_items();
    return (string) array_key_first( $items );
}

/**
 * Resolve active fold slug from query string or fallback.
 *
 * @param string $fallback Optional slug when ?fold= is absent or invalid.
 * @return string
 */
function sfc_get_fold_hub_active_slug( $fallback = '' ) {
    $items     = sfc_get_fold_hub_items();
    $requested = isset( $_GET['fold'] ) ? sanitize_key( wp_unslash( (string) $_GET['fold'] ) ) : '';

    if ( $requested && isset( $items[ $requested ] ) ) {
        return $requested;
    }

    $fallback = sanitize_key( str_replace( '_', '-', $fallback ) );
    if ( $fallback && isset( $items[ $fallback ] ) ) {
        return $fallback;
    }

    return sfc_get_fold_hub_default_slug();
}

/**
 * Whether the current request is a WooCommerce fold hub product page.
 *
 * @return bool
 */
function sfc_is_fold_hub_page() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return false;
    }

    global $post;
    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    return in_array( $post->post_name, sfc_get_fold_hub_page_slugs(), true );
}

/**
 * Localized hub copy for the active site language.
 *
 * @return array<string,string>
 */
function sfc_get_fold_hub_strings( $language = '' ) {
    if ( ! in_array( $language, array( 'en', 'es' ), true ) ) {
        $language = sfc_get_language();
    }
    $copy     = array(
        'grid_label' => array(
            'en' => 'Fold styles',
            'es' => 'Tipos de pliegue',
        ),
        'intro' => array(
            'en' => 'Choose a fold style, then configure size, paper, and finishing below.',
            'es' => 'Elige un tipo de pliegue y configura tamaño, papel y acabado abajo.',
        ),
    );

    $strings = array();
    foreach ( $copy as $key => $translations ) {
        $strings[ $key ] = $translations[ $language ] ?? $translations['en'] ?? $key;
    }

    return $strings;
}

/**
 * Localized label for a fold hub item.
 *
 * @param array<string,mixed> $item Fold hub item config.
 * @return string
 */
function sfc_get_fold_hub_item_label( $item, $language = '' ) {
    if ( ! in_array( $language, array( 'en', 'es' ), true ) ) {
        $language = sfc_get_language();
    }
    $label    = $item['label'] ?? array();

    if ( is_array( $label ) ) {
        return (string) ( $label[ $language ] ?? $label['en'] ?? '' );
    }

    return (string) $label;
}

/**
 * Base URL for fold hub icon links.
 *
 * @return string
 */
function sfc_get_fold_hub_base_url() {
    if ( function_exists( 'is_singular' ) && is_singular() ) {
        $permalink = get_permalink();
        if ( $permalink ) {
            return $permalink;
        }
    }

    return home_url( add_query_arg( array() ) );
}

/**
 * Render fold hub markup: icon grid + active calculator.
 *
 * @param string $default_slug Optional default fold when ?fold= is missing.
 * @return string
 */
function sfc_render_fold_hub( $default_slug = '' ) {
    $items       = sfc_get_fold_hub_items();
    $active_slug = sfc_get_fold_hub_active_slug( $default_slug );
    $active_cfg  = sfc_get_product_config( $active_slug );
    $language    = $active_cfg ? sfc_get_product_language( $active_cfg ) : sfc_get_language();
    $strings     = sfc_get_fold_hub_strings( $language );
    $base_url    = sfc_get_fold_hub_base_url();

    if ( ! sfc_get_product_config( $active_slug ) ) {
        return '<div class="sfc sfc--error"><p>La configuración de la calculadora no está disponible.</p></div>';
    }

    wp_enqueue_style(
        'sfc-calculator-css',
        SFC_PLUGIN_URL . 'assets/calculator.css',
        array(),
        SFC_VERSION
    );

    ob_start();
    ?>
    <div class="sfc sfc-fold-hub" data-active-fold="<?php echo esc_attr( $active_slug ); ?>">
        <p class="sfc-fold-hub__intro"><?php echo esc_html( $strings['intro'] ); ?></p>
        <nav class="sfc-fold-hub__grid" aria-label="<?php echo esc_attr( $strings['grid_label'] ); ?>">
            <?php foreach ( $items as $slug => $item ) : ?>
                <?php
                $is_active = ( $slug === $active_slug );
                $item_url  = esc_url( add_query_arg( 'fold', $slug, $base_url ) );
                $label     = sfc_get_fold_hub_item_label( $item, $language );
                $icon_url  = esc_url( SFC_PLUGIN_URL . 'assets/' . ltrim( (string) ( $item['icon'] ?? '' ), '/' ) );
                $classes   = 'sfc-fold-hub__item' . ( $is_active ? ' is-active' : ' is-dimmed' );
                ?>
                <a
                    class="<?php echo esc_attr( $classes ); ?>"
                    href="<?php echo $item_url; ?>"
                    <?php echo $is_active ? 'aria-current="page"' : ''; ?>
                >
                    <span class="sfc-fold-hub__icon">
                        <img src="<?php echo $icon_url; ?>" alt="" width="120" height="80" loading="lazy" decoding="async" />
                    </span>
                    <span class="sfc-fold-hub__label"><?php echo esc_html( $label ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sfc-fold-hub__calculator">
            <?php echo sfc_render_product_calculator( $active_slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
