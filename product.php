<?php
/**
 * Calculator page for a single product (?product=slug), and the folded-brochure
 * hub (?product=folletos-plegados[&fold=variant]). Optional ?quote=<id> reopens
 * a saved configuration.
 */

require_once __DIR__ . '/bootstrap.php';

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

// ---- Optional saved-quote seed ---------------------------------------------
$seed_notice = null;
if ( $config && isset( $_GET['quote'] ) ) {
    $record = sfc_app_load_quote( sanitize_key( wp_unslash( $_GET['quote'] ) ) );
    if ( $record && ( $record['slug'] ?? '' ) === $slug ) {
        $GLOBALS['sfc_seed'] = array(
            'slug'   => $slug,
            'state'  => (array) ( $record['state'] ?? array() ),
            'notice' => 'Se abrió una cotización guardada con los precios actuales.',
        );
    }
}

$data = $config ? sfc_build_product_js_data( $slug ) : null;
if ( $config && ! is_wp_error( $data ) ) {
    // Standalone endpoints replace admin-ajax; no nonce needed.
    $data['ajaxUrl'] = '/api/index.php';
    $data['nonce']   = '';
}

$page_title = $config && ! is_wp_error( $data )
    ? ( $data['strings']['product_title'] ?? 'Calculadora' )
    : 'Calculadora';

require __DIR__ . '/src/partials/head.php';
?>
<header class="app-header">
    <a class="app-header__brand" href="index.php">Sheet&nbsp;Fed&nbsp;Calc</a>
    <a class="app-header__back" href="index.php">← Todos los productos</a>
</header>

<main class="app-main">
<?php if ( ! $config || is_wp_error( $data ) ) : ?>
    <div class="sfc sfc--error app-notfound">
        <h1>Producto no encontrado</h1>
        <p>El producto solicitado no existe. <a href="index.php">Volver al inicio</a>.</p>
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
                    $icon_url = 'assets/img/' . rawurlencode( (string) ( $item['icon'] ?? '' ) );
                    $href     = 'product.php?product=folletos-plegados&fold=' . rawurlencode( $item_slug );
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
    <script src="assets/vendor/jquery.min.js"></script>
    <script src="assets/js/context.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/state.js"></script>
    <script src="assets/js/steps.js"></script>
    <script src="assets/js/render.js"></script>
    <script src="assets/js/quote.js"></script>
    <script src="assets/js/events.js"></script>
    <script src="assets/js/app.js"></script>
<?php endif; ?>
</main>

<?php require __DIR__ . '/src/partials/footer.php'; ?>
