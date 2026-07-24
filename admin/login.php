<?php
/**
 * Admin login. Posts a password, verifies against the configured hash, and on
 * success starts an authenticated session.
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once SFC_APP_DIR . '/src/admin/auth.php';

if ( sfc_admin_is_logged_in() ) {
    header( 'Location: index.php' );
    exit;
}

$error      = '';
$configured = sfc_admin_is_configured();

if ( $configured && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
    if ( sfc_admin_attempt_login( $_POST['password'] ?? '' ) ) {
        header( 'Location: index.php' );
        exit;
    }
    $error = 'Contraseña incorrecta.';
    usleep( 400000 ); // small throttle against guessing
}

$h = static function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso · Mantenimiento de precios</title>
    <link rel="stylesheet" href="<?php echo $h( SFC_BASE_PATH ); ?>/assets/admin.css">
</head>
<body class="adm-body adm-body--login">
<main class="adm-login">
    <h1>Mantenimiento de precios</h1>
    <?php if ( ! $configured ) : ?>
        <div class="adm-flash adm-flash--err">
            No se ha configurado una contraseña de administrador. Ejecute:
            <br><code>ddev exec php bin/set-admin-password.php 'su-contraseña'</code>
        </div>
    <?php else : ?>
        <?php if ( $error ) : ?>
            <div class="adm-flash adm-flash--err"><?php echo $h( $error ); ?></div>
        <?php endif; ?>
        <form method="post" class="adm-card">
            <label class="adm-row adm-row--stack">
                <span>Contraseña</span>
                <input type="password" name="password" autofocus autocomplete="current-password" required>
            </label>
            <button type="submit" class="adm-btn">Ingresar</button>
        </form>
    <?php endif; ?>
    <p class="adm-login__back"><a href="<?php echo $h( SFC_BASE_PATH ); ?>/">← Volver a la calculadora</a></p>
</main>
</body>
</html>
