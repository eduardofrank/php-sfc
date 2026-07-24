<?php
/**
 * Admin quote browser. Searchable, paginated list of saved quotes with reopen
 * (share link) and delete. Password-protected, CSRF-guarded delete.
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once SFC_APP_DIR . '/src/admin/auth.php';

sfc_admin_require_login();

$h    = static function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };
$csrf = sfc_admin_csrf_token();
$flash = null;

// ---- Delete (POST) ----------------------------------------------------------
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
    if ( ! sfc_admin_csrf_valid( $_POST['csrf'] ?? '' ) ) {
        $flash = array( 'ok' => false, 'message' => 'Sesión expirada. Vuelva a intentar.' );
    } elseif ( 'delete' === ( $_POST['admin_action'] ?? '' ) ) {
        try {
            $flash = sfc_quotes_delete( (int) ( $_POST['id'] ?? 0 ) )
                ? array( 'ok' => true, 'message' => 'Cotización eliminada.' )
                : array( 'ok' => false, 'message' => 'No se encontró la cotización.' );
        } catch ( Throwable $e ) {
            $flash = array( 'ok' => false, 'message' => 'No se pudo eliminar.' );
        }
    }
}

// ---- Query / pagination -----------------------------------------------------
$search   = trim( (string) ( $_GET['q'] ?? '' ) );
$per_page = 25;
$page     = max( 1, (int) ( $_GET['p'] ?? 1 ) );
$filters  = array( 'search' => $search );

$db_error = false;
$total    = 0;
$rows     = array();
try {
    $total = sfc_quotes_count( $filters );
    $rows  = sfc_quotes_list( $filters, $per_page, ( $page - 1 ) * $per_page );
} catch ( Throwable $e ) {
    $db_error = true;
}
$pages = max( 1, (int) ceil( $total / $per_page ) );

$page_link = static function ( $p ) use ( $search, $h ) {
    $qs = 'p=' . (int) $p . ( '' !== $search ? '&q=' . rawurlencode( $search ) : '' );
    return 'quotes.php?' . $h( $qs );
};
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cotizaciones · Sheet Fed Calc</title>
    <link rel="stylesheet" href="<?php echo $h( SFC_BASE_PATH ); ?>/assets/admin.css">
</head>
<body class="adm-body">
<header class="adm-header">
    <div><strong>Sheet Fed Calc</strong> · Cotizaciones</div>
    <nav class="adm-nav">
        <a href="index.php">Precios</a>
        <a href="<?php echo $h( SFC_BASE_PATH ); ?>/" target="_blank" rel="noopener">Ver calculadora ↗</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</header>

<main class="adm-main">
    <?php if ( $flash ) : ?>
        <div class="adm-flash <?php echo $flash['ok'] ? 'adm-flash--ok' : 'adm-flash--err'; ?>">
            <?php echo $h( $flash['message'] ); ?>
        </div>
    <?php endif; ?>

    <?php if ( $db_error ) : ?>
        <div class="adm-flash adm-flash--err">
            No se pudo conectar a la base de datos. Verifique la configuración
            (<code>data/config/db.php</code> o variables <code>SFC_DB_*</code>) y que la migración
            se haya ejecutado (<code>bin/db-migrate.php</code>).
        </div>
    <?php else : ?>

    <form method="get" class="adm-search">
        <input type="text" name="q" value="<?php echo $h( $search ); ?>"
            placeholder="Buscar por cliente o número de cotización">
        <button type="submit" class="adm-btn">Buscar</button>
        <?php if ( '' !== $search ) : ?>
            <a class="adm-search__clear" href="quotes.php">Limpiar</a>
        <?php endif; ?>
    </form>

    <p class="adm-help"><?php echo (int) $total; ?> cotización(es).</p>

    <div class="adm-grid-scroll">
        <table class="adm-price-table adm-quotes">
            <thead>
                <tr>
                    <th>Número</th><th>Cliente</th><th>Producto</th>
                    <th>Total</th><th>Fecha</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! $rows ) : ?>
                    <tr><td colspan="6">No hay cotizaciones.</td></tr>
                <?php else : ?>
                    <?php foreach ( $rows as $r ) : ?>
                        <?php $share = SFC_BASE_PATH . '/product.php?product=' . rawurlencode( $r['product_slug'] ) . '&quote=' . rawurlencode( $r['share_token'] ); ?>
                        <tr>
                            <td><strong><?php echo $h( $r['quote_number'] ); ?></strong></td>
                            <td><?php echo $h( $r['client_name'] ); ?></td>
                            <td><?php echo $h( $r['product_slug'] ); ?></td>
                            <td><?php echo $h( $r['currency'] . ' $' . number_format( (float) $r['total_price'], 2 ) ); ?></td>
                            <td><?php echo $h( substr( (string) $r['created_at'], 0, 10 ) ); ?></td>
                            <td class="adm-quotes__actions">
                                <a href="<?php echo $h( $share ); ?>" target="_blank" rel="noopener">Abrir</a>
                                <form method="post" onsubmit="return confirm('¿Eliminar la cotización <?php echo $h( $r['quote_number'] ); ?>?');">
                                    <input type="hidden" name="csrf" value="<?php echo $h( $csrf ); ?>">
                                    <input type="hidden" name="admin_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                                    <button type="submit" class="adm-link-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ( $pages > 1 ) : ?>
        <nav class="adm-pager">
            <?php if ( $page > 1 ) : ?>
                <a href="<?php echo $page_link( $page - 1 ); ?>">← Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo (int) $page; ?> de <?php echo (int) $pages; ?></span>
            <?php if ( $page < $pages ) : ?>
                <a href="<?php echo $page_link( $page + 1 ); ?>">Siguiente →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <?php endif; ?>
</main>
</body>
</html>
