<?php
/**
 * Calculator page for a single product (?product=slug), and the folded-brochure
 * hub (?product=folletos-plegados[&fold=variant]). Its primary action adds the
 * configured item to the quote draft. Optionally seeds from a finalized quote
 * item (?from=<token>&item=<pos>) to inspect or re-add it.
 */

require_once __DIR__ . '/bootstrap.php';
sfc_draft_session_start();

$slug = isset( $_GET['product'] ) ? sanitize_key( str_replace( '_', '-', wp_unslash( $_GET['product'] ) ) ) : '';

$is_hub    = sfc_app_is_fold_hub_slug( $slug );
$fold_slug = '';
if ( $is_hub ) {
    $requested = isset( $_GET['fold'] ) ? sanitize_key( wp_unslash( $_GET['fold'] ) ) : '';
    $items     = sfc_get_fold_hub_items();
    $fold_slug = isset( $items[ $requested ] ) ? $requested : sfc_get_fold_hub_default_slug();
    $slug      = $fold_slug; // the calculator to render under the hub grid
}

$config = sfc_get_product_config( $slug );

// ---- Optionally seed from a finalized quote item ---------------------------
// "Abrir en calculadora" from a quote document lands here with ?from=<token>
// &item=<pos>; seed that item's config so it can be inspected or re-added.
$from_note = null;
if ( $config && isset( $_GET['from'] ) ) {
    $token = sanitize_key( wp_unslash( $_GET['from'] ) );
    $pos   = (int) ( $_GET['item'] ?? 0 );

    try {
        $record = sfc_quotes_get_by_token( $token );
    } catch ( Throwable $e ) {
        $record = null;
    }

    if ( $record && isset( $record['items'][ $pos ] )
        && ( $record['items'][ $pos ]['product_slug'] ?? '' ) === $slug ) {
        $GLOBALS['sfc_seed'] = array(
            'slug'   => $slug,
            'state'  => (array) $record['items'][ $pos ]['state'],
            'notice' => 'Basado en la cotización ' . $record['quote_number']
                . '. Ajusta y agrégalo a una nueva cotización.',
        );
        $from_note = $record['quote_number'];
    }
}

$data = $config ? sfc_build_product_js_data( $slug ) : null;
if ( $config && ! is_wp_error( $data ) ) {
    // Standalone endpoints replace admin-ajax; no nonce needed.
    $data['ajaxUrl'] = SFC_BASE_PATH . '/api/index.php';
    $data['nonce']   = '';
    // Current USD->VES rate for live Bs. display (null hides VES).
    $data['vesRate'] = sfc_current_usd_ves_rate();
}

$page_title = $config && ! is_wp_error( $data )
    ? ( $data['strings']['product_title'] ?? 'Calculadora' )
    : 'Calculadora';

$body_class  = 'page-calc';
$page_styles = array( 'quote-ui.css' );

require __DIR__ . '/src/partials/head.php';

$b = SFC_BASE_PATH; // URL prefix, '' at site root or e.g. '/php-sfc'
?>
<header class="app-header">
    <a class="app-header__brand" href="<?php echo esc_attr( $b ); ?>/">Lab&nbsp;Gráfico</a>
    <a class="app-header__back" href="<?php echo esc_attr( $b ); ?>/products.php">← Elegir otro producto</a>
</header>

<?php require __DIR__ . '/src/partials/draft-bar.php'; ?>

<main class="app-main">
<?php if ( ! $config || is_wp_error( $data ) ) : ?>
    <div class="sfc sfc--error app-notfound">
        <h1>Producto no encontrado</h1>
        <p>El producto solicitado no existe. <a href="<?php echo esc_attr( $b ); ?>/">Volver al inicio</a>.</p>
    </div>
<?php else : ?>

    <?php if ( $is_hub ) : ?>
        <?php
        $items    = sfc_get_fold_hub_items();
        $hub_copy = sfc_get_fold_hub_strings( 'es' );
        ?>
        <section class="sfc sfc-fold-hub" aria-label="Tipos de pliegue">
            <h1 class="app-title">Folletos plegables</h1>
            <p class="sfc-fold-hub__intro"><?php echo esc_html( $hub_copy['intro'] ); ?></p>
            <nav class="sfc-fold-hub__grid" aria-label="<?php echo esc_attr( $hub_copy['grid_label'] ); ?>">
                <?php foreach ( $items as $item_slug => $item ) : ?>
                    <?php
                    $active   = ( $item_slug === $fold_slug );
                    $label    = sfc_get_fold_hub_item_label( $item, 'es' );
                    $icon_url = $b . '/assets/img/' . rawurlencode( (string) ( $item['icon'] ?? '' ) );
                    $href     = $b . '/product.php?product=folletos-plegados&fold=' . rawurlencode( $item_slug );
                    ?>
                    <a class="sfc-fold-hub__item<?php echo $active ? ' is-active' : ' is-dimmed'; ?>"
                       href="<?php echo esc_attr( $href ); ?>"
                       <?php echo $active ? 'aria-current="page"' : ''; ?>>
                        <span class="sfc-fold-hub__icon">
                            <img src="<?php echo esc_attr( $icon_url ); ?>" alt="" width="120" height="80" loading="lazy" />
                        </span>
                        <span class="sfc-fold-hub__label"><?php echo esc_html( $label ); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </section>
    <?php endif; ?>

    <div class="sfc" id="sfc-root"></div>

    <script>window.__SFC_DATA = <?php echo wp_json_encode_compat( $data ); ?>;</script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/vendor/jquery.min.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/context.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/utils.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/state.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/steps.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/render.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/quote.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/events.js"></script>
    <script src="<?php echo esc_attr( $b ); ?>/assets/js/app.js"></script>
<?php endif; ?>
</main>

<?php require __DIR__ . '/src/partials/footer.php'; ?>
