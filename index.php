<?php
/**
 * Landing page: the ten products presented as the first choice. Picking one
 * forks to product.php, which loads that product's calculator logic.
 */

require_once __DIR__ . '/bootstrap.php';

$products   = sfc_app_get_landing_products();
$page_title = 'Elige un producto';

require __DIR__ . '/src/partials/head.php';
?>
<header class="app-header">
    <span class="app-header__brand">Sheet&nbsp;Fed&nbsp;Calc</span>
</header>

<main class="app-main">
    <section class="app-hero">
        <h1 class="app-title">Calculadora de impresión</h1>
        <p class="app-lead">Elige un producto para cotizar. Cada uno abre su propia calculadora con las opciones y precios que le corresponden.</p>
    </section>

    <ul class="product-grid" role="list">
        <?php foreach ( $products as $i => $product ) : ?>
            <li class="product-card<?php echo $product['isHub'] ? ' product-card--hub' : ''; ?>">
                <a class="product-card__link" href="<?php echo esc_attr( $product['href'] ); ?>">
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
