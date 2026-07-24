<?php
/**
 * PostgreSQL connection layer.
 *
 * Quotes and clients are persisted in PostgreSQL. Connection parameters are
 * resolved with this precedence:
 *   1. SFC_DB_* environment variables (set on the server, e.g. Apache SetEnv);
 *   2. data/config/db.php  — a gitignored, ABSPATH-guarded PHP file returning
 *      an array (same pattern as data/config/admin-password.php);
 *   3. DDEV defaults (host "db", db/user/password all "db") for local dev.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Path to the local DB credentials file (gitignored).
 *
 * @return string
 */
function sfc_db_config_file() {
    return SFC_APP_DIR . '/data/config/db.php';
}

/**
 * Resolve database connection parameters.
 *
 * @return array{host:string,port:string,name:string,user:string,password:string}
 */
function sfc_db_config() {
    $defaults = array(
        'host'     => '127.0.0.1',
        'port'     => '5432',
        'name'     => 'sheetfedcalc',
        'user'     => 'sheetfedcalc',
        'password' => '',
    );

    // Local dev inside DDEV: the bundled Postgres is reachable as "db".
    if ( getenv( 'IS_DDEV_PROJECT' ) ) {
        $defaults = array(
            'host'     => 'db',
            'port'     => '5432',
            'name'     => 'db',
            'user'     => 'db',
            'password' => 'db',
        );
    }

    // File override (never committed).
    $file = sfc_db_config_file();
    if ( is_file( $file ) ) {
        $from_file = include $file;
        if ( is_array( $from_file ) ) {
            $defaults = array_merge( $defaults, array_intersect_key( $from_file, $defaults ) );
        }
    }

    // Environment override (highest precedence).
    $env = array(
        'host'     => getenv( 'SFC_DB_HOST' ),
        'port'     => getenv( 'SFC_DB_PORT' ),
        'name'     => getenv( 'SFC_DB_NAME' ),
        'user'     => getenv( 'SFC_DB_USER' ),
        'password' => getenv( 'SFC_DB_PASS' ),
    );
    foreach ( $env as $key => $value ) {
        if ( false !== $value && '' !== $value ) {
            $defaults[ $key ] = $value;
        }
    }

    return $defaults;
}

/**
 * Return the shared PDO connection, creating it on first use.
 *
 * @return PDO
 * @throws PDOException When the connection cannot be established.
 */
function sfc_db() {
    static $pdo = null;
    if ( $pdo instanceof PDO ) {
        return $pdo;
    }

    $cfg = sfc_db_config();
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['name']
    );

    $pdo = new PDO(
        $dsn,
        $cfg['user'],
        $cfg['password'],
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        )
    );

    return $pdo;
}

/**
 * Whether the database is reachable (for graceful degradation / diagnostics).
 *
 * @return bool
 */
function sfc_db_available() {
    try {
        sfc_db()->query( 'SELECT 1' );
        return true;
    } catch ( Throwable $e ) {
        return false;
    }
}
