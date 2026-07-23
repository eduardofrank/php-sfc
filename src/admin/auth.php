<?php
/**
 * Admin authentication for the price-maintenance UI.
 *
 * Single-operator, session-based login. The password hash is read from the
 * SFC_ADMIN_PASSWORD_HASH environment variable if set, otherwise from the
 * gitignored file data/config/admin-password.php (a guarded PHP file so it is
 * never served as static text). Create it with bin/set-admin-password.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Path to the local admin password file (gitignored).
 *
 * @return string
 */
function sfc_admin_password_file() {
    return SFC_APP_DIR . '/data/config/admin-password.php';
}

/**
 * Resolve the configured admin password hash, or '' when none is set.
 *
 * @return string
 */
function sfc_admin_password_hash() {
    $env = getenv( 'SFC_ADMIN_PASSWORD_HASH' );
    if ( is_string( $env ) && '' !== $env ) {
        return $env;
    }

    $file = sfc_admin_password_file();
    if ( is_file( $file ) ) {
        $hash = include $file;
        if ( is_string( $hash ) && '' !== $hash ) {
            return $hash;
        }
    }

    return '';
}

/**
 * Whether an admin password has been configured at all.
 *
 * @return bool
 */
function sfc_admin_is_configured() {
    return '' !== sfc_admin_password_hash();
}

/**
 * Start the admin session (idempotent).
 *
 * @return void
 */
function sfc_admin_session_start() {
    if ( PHP_SESSION_ACTIVE !== session_status() ) {
        session_name( 'sfc_admin_sess' );
        session_start();
    }
}

/**
 * Whether the current session is authenticated.
 *
 * @return bool
 */
function sfc_admin_is_logged_in() {
    sfc_admin_session_start();
    return ! empty( $_SESSION['sfc_admin'] );
}

/**
 * Verify a submitted password against the configured hash and log in on match.
 *
 * @param string $password Submitted password.
 * @return bool
 */
function sfc_admin_attempt_login( $password ) {
    $hash = sfc_admin_password_hash();
    if ( '' === $hash || ! password_verify( (string) $password, $hash ) ) {
        return false;
    }

    sfc_admin_session_start();
    session_regenerate_id( true );
    $_SESSION['sfc_admin'] = true;
    $_SESSION['sfc_csrf']  = bin2hex( random_bytes( 16 ) );
    return true;
}

/**
 * Log out and destroy the session.
 *
 * @return void
 */
function sfc_admin_logout() {
    sfc_admin_session_start();
    $_SESSION = array();
    session_destroy();
}

/**
 * Redirect to the login page unless authenticated. Call at the top of every
 * admin controller.
 *
 * @return void
 */
function sfc_admin_require_login() {
    if ( ! sfc_admin_is_logged_in() ) {
        header( 'Location: login.php' );
        exit;
    }
}

/**
 * CSRF token for the current admin session.
 *
 * @return string
 */
function sfc_admin_csrf_token() {
    sfc_admin_session_start();
    if ( empty( $_SESSION['sfc_csrf'] ) ) {
        $_SESSION['sfc_csrf'] = bin2hex( random_bytes( 16 ) );
    }
    return $_SESSION['sfc_csrf'];
}

/**
 * Validate a submitted CSRF token.
 *
 * @param string $token Submitted token.
 * @return bool
 */
function sfc_admin_csrf_valid( $token ) {
    sfc_admin_session_start();
    return ! empty( $_SESSION['sfc_csrf'] )
        && is_string( $token )
        && hash_equals( $_SESSION['sfc_csrf'], $token );
}
