<?php
/**
 * Read-only quote document. Shows a finalized quote (header + line items) by its
 * public share token: number, client, date, title, notes, per-item and grand
 * totals. Printable and shareable; each item links back into the calculator.
 */

require_once __DIR__ . '/bootstrap.php';

$b     = SFC_BASE_PATH;
$token = isset( $_GET['token'] ) ? sanitize_key( wp_unslash( $_GET['token'] ) ) : '';

$quote = null;
try {
    $quote = sfc_quotes_get_by_token( $token );
} catch ( Throwable $e ) {
    $quote = null;
}

$h = static function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };
$money = static function ( $amount, $currency ) {
    return $currency . ' $' . number_format( (float) $amount, 2 );
};

// Frozen VES rate on this quote (0 when the quote predates dual-currency).
$ves_rate = $quote && isset( $quote['ves_rate'] ) ? (float) $quote['ves_rate'] : 0.0;
$has_ves  = $ves_rate > 0;
$ves = static function ( $usd ) use ( $ves_rate, $h ) {
    return $h( sfc_format_ves( (float) $usd, $ves_rate > 0 ? $ves_rate : null ) );
};

$page_title  = $quote ? ( 'Cotización ' . $quote['quote_number'] ) : 'Cotización';
$body_class  = 'page-quote';
$page_styles = array( 'quote-ui.css' );

require __DIR__ . '/src/partials/head.php';
?>
<header class="app-header app-header--print-hide">
    <a class="app-header__brand" href="<?php echo $h( $b ); ?>/">Lab&nbsp;Gráfico</a>
    <?php if ( $quote ) : ?>
        <div class="app-header__actions">
            <button type="button" class="app-header__back" onclick="window.print()">Imprimir</button>
            <a class="app-header__back" href="<?php echo $h( $b ); ?>/">Nueva cotización</a>
        </div>
    <?php endif; ?>
</header>

<main class="app-main">
<?php if ( ! $quote ) : ?>
    <div class="sfc sfc--error app-notfound">
        <h1>Cotización no encontrada</h1>
        <p>El enlace no corresponde a ninguna cotización. <a href="<?php echo $h( $b ); ?>/">Crear una nueva</a>.</p>
    </div>
<?php else : ?>
    <?php $curr = $quote['currency']; ?>
    <article class="quote-doc cropframe">
        <span class="cropframe__b"></span>

        <div class="quote-doc__masthead">
            <div class="quote-doc__brand">Lab&nbsp;Gr<span>á</span>fico</div>
            <div class="quote-doc__stamp">
                <span class="k">Cotización</span>
                <span class="num"><?php echo $h( $quote['quote_number'] ); ?></span>
            </div>
        </div>

        <?php if ( ! empty( $quote['title'] ) ) : ?>
            <p class="quote-doc__title"><?php echo $h( $quote['title'] ); ?></p>
        <?php endif; ?>

        <dl class="quote-doc__meta">
            <div><dt>Cliente</dt><dd><?php echo $h( $quote['client_name'] ); ?></dd></div>
            <?php if ( ! empty( $quote['client_email'] ) ) : ?>
                <div><dt>Correo</dt><dd><?php echo $h( $quote['client_email'] ); ?></dd></div>
            <?php endif; ?>
            <div><dt>Fecha</dt><dd><?php echo $h( substr( (string) $quote['created_at'], 0, 10 ) ); ?></dd></div>
            <div><dt>Moneda</dt><dd><?php echo $h( $curr ); ?></dd></div>
        </dl>

        <table class="quote-doc__items">
            <thead>
                <tr><th class="col-idx">#</th><th>Descripción</th><th class="quote-doc__amount">Importe</th></tr>
            </thead>
            <tbody>
                <?php foreach ( $quote['items'] as $idx => $item ) : ?>
                    <?php $calc = sfc_app_item_calc_url( $item['product_slug'], $quote['share_token'], (int) $item['position'] ); ?>
                    <tr>
                        <td class="col-idx"><span><?php echo $h( str_pad( (string) ( $idx + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span></td>
                        <td>
                            <span class="quote-doc__item-label"><?php echo $h( $item['label'] ); ?></span>
                            <a class="quote-doc__item-open" href="<?php echo $h( $calc ); ?>">Abrir en calculadora</a>
                        </td>
                        <td class="quote-doc__amount"><?php echo $h( $money( $item['line_total'], $curr ) ); ?><?php if ( $has_ves ) : ?><span class="quote-doc__amount-ves"><?php echo $ves( $item['line_total'] ); ?></span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="col-idx"></td>
                    <td class="quote-doc__total-label">Total</td>
                    <td class="quote-doc__grand"><?php echo $h( $money( $quote['total_price'], $curr ) ); ?><?php if ( $has_ves ) : ?><span class="quote-doc__grand-ves"><?php echo $ves( $quote['total_price'] ); ?></span><?php endif; ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if ( ! empty( $quote['notes'] ) ) : ?>
            <div class="quote-doc__notes">
                <h2>Notas</h2>
                <p><?php echo nl2br( $h( $quote['notes'] ) ); ?></p>
            </div>
        <?php endif; ?>

        <p class="quote-doc__foot">Cotización emitida el <?php echo $h( substr( (string) $quote['created_at'], 0, 10 ) ); ?> · Precios fijos en <?php echo $h( $curr ); ?><?php if ( $has_ves ) : ?> · Tasa BCV: <?php echo $h( sfc_format_rate( $ves_rate ) ); ?> / USD<?php endif; ?>.</p>
    </article>
<?php endif; ?>
</main>

<?php require __DIR__ . '/src/partials/footer.php'; ?>
