<?php
/**
 * Product picker — the "add an item" step. Choosing a product opens its
 * calculator, whose primary action adds the configured item to the quote draft.
 */

require_once __DIR__ . '/bootstrap.php';
sfc_draft_session_start();

$products   = sfc_app_get_landing_products();
$page_title = 'Elige un producto';
$b          = SFC_BASE_PATH;

require __DIR__ . '/src/partials/head.php';
?>
<header class="app-header">
    <a class="app-header__brand" href="<?php echo esc_attr( $b ); ?>/">Lab&nbsp;Gráfico</a>
    <a class="app-header__back" href="<?php echo esc_attr( $b ); ?>/">← Cotización</a>
</header>

<?php require __DIR__ . '/src/partials/draft-bar.php'; ?>

<main class="app-main">
    <section class="app-hero">
        <h1 class="app-title">Agregar un producto</h1>
        <p class="app-lead">Elige un producto, configúralo y agrégalo a la cotización. Puedes agregar varios, del mismo o de distintos productos.</p>
    </section>

    <ul class="product-grid" role="list">
        <?php foreach ( $products as $i => $product ) : ?>
            <li class="product-card<?php echo $product['isHub'] ? ' product-card--hub' : ''; ?>">
                <a class="product-card__link" href="<?php echo esc_attr( $b . '/' . $product['href'] ); ?>">
                    <span class="product-card__index"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
                    <span class="product-card__body">
                        <span class="product-card__title"><?php echo esc_html( $product['title'] ); ?></span>
                        <span class="product-card__subtitle"><?php echo esc_html( $product['subtitle'] ); ?></span>
                    </span>
                    <span class="product-card__arrow" aria-hidden="true">→</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</main>

<?php require __DIR__ . '/src/partials/footer.php'; ?>
