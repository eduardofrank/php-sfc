<?php
/**
 * Home = the quote builder. Shows the current draft (line items across any
 * products), collects the client + optional title/notes, and finalizes into a
 * numbered quote. "Añadir producto" leads to the product picker.
 */

require_once __DIR__ . '/bootstrap.php';
sfc_draft_session_start();

$b     = SFC_BASE_PATH;
$error = '';
$form  = array( 'client_name' => '', 'client_email' => '', 'title' => '', 'notes' => '' );

// ---- POST handlers (Post/Redirect/Get) -------------------------------------
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
    $do = sanitize_key( $_POST['do'] ?? '' );

    if ( 'remove' === $do ) {
        sfc_draft_remove( sanitize_key( wp_unslash( $_POST['item_id'] ?? '' ) ) );
        header( 'Location: ' . $b . '/' );
        exit;
    }

    if ( 'clear' === $do ) {
        sfc_draft_clear();
        header( 'Location: ' . $b . '/' );
        exit;
    }

    if ( 'finalize' === $do ) {
        $form = array(
            'client_name'  => (string) wp_unslash( $_POST['client_name'] ?? '' ),
            'client_email' => (string) wp_unslash( $_POST['client_email'] ?? '' ),
            'title'        => (string) wp_unslash( $_POST['title'] ?? '' ),
            'notes'        => (string) wp_unslash( $_POST['notes'] ?? '' ),
        );
        $row = sfc_draft_finalize( $form['client_name'], $form['client_email'], $form['title'], $form['notes'] );
        if ( is_wp_error( $row ) ) {
            $error = $row->get_error_message();
        } else {
            header( 'Location: ' . $b . '/quote.php?token=' . rawurlencode( $row['shareToken'] ) );
            exit;
        }
    }
}

$draft      = sfc_draft_summary();
$client_names = array();
try {
    $client_names = sfc_client_names();
} catch ( Throwable $e ) {
    $client_names = array();
}

$page_title  = 'Cotización';
$body_class  = 'page-builder';
$page_styles = array( 'quote-ui.css' );
$h = static function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };
$money = static function ( $amount ) use ( $draft, $h ) {
    return $h( $draft['currency'] . ' $' . number_format( (float) $amount, 2 ) );
};
$ves_rate = sfc_current_usd_ves_rate();
$ves = static function ( $amount ) use ( $ves_rate, $h ) {
    return $h( sfc_format_ves( (float) $amount, $ves_rate ) );
};

require __DIR__ . '/src/partials/head.php';
?>
<header class="app-header">
    <a class="app-header__brand" href="<?php echo $h( $b ); ?>/">Lab&nbsp;Gráfico</a>
    <a class="app-header__back" href="<?php echo $h( $b ); ?>/admin/quotes.php">Cotizaciones guardadas</a>
</header>

<main class="app-main">
    <section class="app-hero">
        <p class="builder-kicker">Presupuesto de impresión</p>
        <h1 class="app-title">Arma tu cotización</h1>
        <p class="app-lead">Agrega uno o más productos, del mismo o de distintos tipos, y finaliza para emitir un número de cotización con precios fijos.</p>
    </section>

    <?php if ( $error ) : ?>
        <div class="builder-alert"><?php echo $h( $error ); ?></div>
    <?php endif; ?>

    <div class="builder">
        <section class="builder__items cropframe">
            <span class="cropframe__b"></span>
            <div class="builder__sheet-label">
                <span>Hoja de trabajo</span>
                <span><?php echo (int) $draft['count']; ?> ítem(s)</span>
            </div>

            <?php if ( $draft['count'] < 1 ) : ?>
                <div class="builder__empty">
                    <p>Todavía no hay productos en esta cotización.</p>
                    <a class="sfc__btn" href="<?php echo $h( $b ); ?>/products.php">Añadir el primer producto</a>
                </div>
            <?php else : ?>
                <ul class="builder__list" role="list">
                    <?php foreach ( $draft['items'] as $i => $item ) : ?>
                        <li class="builder__row">
                            <span class="builder__idx"><?php echo $h( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
                            <span class="builder__label"><?php echo $h( $item['label'] ); ?></span>
                            <span class="builder__line-total"><?php echo $money( $item['lineTotal'] ); ?><?php if ( $ves_rate ) : ?><span class="builder__line-ves"><?php echo $ves( $item['lineTotal'] ); ?></span><?php endif; ?></span>
                            <form method="post" class="builder__remove">
                                <input type="hidden" name="do" value="remove">
                                <input type="hidden" name="item_id" value="<?php echo $h( $item['itemId'] ); ?>">
                                <button type="submit" title="Quitar" aria-label="Quitar ítem">✕</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="builder__actions">
                    <a class="builder__add-row" href="<?php echo $h( $b ); ?>/products.php"><span class="plus">+</span> Añadir otro producto</a>
                    <form method="post" class="builder__clear">
                        <input type="hidden" name="do" value="clear">
                        <button type="submit" class="builder__clear-btn">Vaciar</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        <?php if ( $draft['count'] > 0 ) : ?>
        <section class="builder__finalize">
            <div class="builder__total-block">
                <span class="lbl">Total</span>
                <span class="amt"><?php echo $money( $draft['grandTotal'] ); ?><?php if ( $ves_rate ) : ?><span class="builder__total-ves"><?php echo $ves( $draft['grandTotal'] ); ?></span><?php endif; ?></span>
            </div>
            <h2>Emitir cotización</h2>
            <form method="post">
                <input type="hidden" name="do" value="finalize">
                <label class="sfc__field">
                    <span class="sfc__label">Cliente *</span>
                    <input type="text" name="client_name" list="sfc-client-list" required
                        autocomplete="off" value="<?php echo $h( $form['client_name'] ); ?>"
                        placeholder="Nombre del cliente" class="sfc__input">
                </label>
                <datalist id="sfc-client-list">
                    <?php foreach ( $client_names as $name ) : ?>
                        <option value="<?php echo $h( $name ); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <label class="sfc__field">
                    <span class="sfc__label">Correo (opcional)</span>
                    <input type="email" name="client_email" autocomplete="off"
                        value="<?php echo $h( $form['client_email'] ); ?>" class="sfc__input">
                </label>
                <label class="sfc__field">
                    <span class="sfc__label">Título (opcional)</span>
                    <input type="text" name="title" value="<?php echo $h( $form['title'] ); ?>"
                        placeholder="p. ej. Campaña de julio" class="sfc__input">
                </label>
                <label class="sfc__field">
                    <span class="sfc__label">Notas (opcional)</span>
                    <textarea name="notes" rows="3" class="sfc__input" placeholder="Términos, validez, condiciones…"><?php echo $h( $form['notes'] ); ?></textarea>
                </label>
                <button type="submit" class="sfc__btn builder__finalize-btn">Finalizar cotización</button>
            </form>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/src/partials/footer.php'; ?>
