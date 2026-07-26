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

$page_title = 'Cotización';
$h = static function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };

require __DIR__ . '/src/partials/head.php';
?>
<header class="app-header">
    <a class="app-header__brand" href="<?php echo $h( $b ); ?>/">Sheet&nbsp;Fed&nbsp;Calc</a>
    <a class="app-header__back" href="<?php echo $h( $b ); ?>/admin/quotes.php">Cotizaciones guardadas</a>
</header>

<main class="app-main">
    <section class="app-hero">
        <h1 class="app-title">Tu cotización</h1>
        <p class="app-lead">Agrega uno o más productos, del mismo o de distintos tipos, y finaliza para generar un número de cotización.</p>
    </section>

    <?php if ( $error ) : ?>
        <div class="sfc__alert builder-alert"><?php echo $h( $error ); ?></div>
    <?php endif; ?>

    <div class="builder">
        <section class="builder__items">
            <div class="builder__items-head">
                <h2>Ítems</h2>
                <a class="sfc__btn builder__add" href="<?php echo $h( $b ); ?>/products.php">+ Añadir producto</a>
            </div>

            <?php if ( $draft['count'] < 1 ) : ?>
                <div class="builder__empty">
                    <p>Aún no has agregado productos a esta cotización.</p>
                    <a class="sfc__btn" href="<?php echo $h( $b ); ?>/products.php">Añadir el primer producto</a>
                </div>
            <?php else : ?>
                <ul class="builder__list" role="list">
                    <?php foreach ( $draft['items'] as $item ) : ?>
                        <li class="builder__row">
                            <span class="builder__label"><?php echo $h( $item['label'] ); ?></span>
                            <span class="builder__line-total"><?php echo $h( $draft['currency'] . ' $' . number_format( $item['lineTotal'], 2 ) ); ?></span>
                            <form method="post" class="builder__remove">
                                <input type="hidden" name="do" value="remove">
                                <input type="hidden" name="item_id" value="<?php echo $h( $item['itemId'] ); ?>">
                                <button type="submit" class="adm-link-danger" title="Quitar">Quitar</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="builder__grand">
                    <span>Total</span>
                    <strong><?php echo $h( $draft['currency'] . ' $' . number_format( $draft['grandTotal'], 2 ) ); ?></strong>
                </div>
                <form method="post" class="builder__clear">
                    <input type="hidden" name="do" value="clear">
                    <button type="submit" class="builder__clear-btn">Vaciar cotización</button>
                </form>
            <?php endif; ?>
        </section>

        <?php if ( $draft['count'] > 0 ) : ?>
        <section class="builder__finalize">
            <h2>Finalizar</h2>
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
