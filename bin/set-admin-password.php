<?php
/**
 * Set (or reset) the admin password for the price-maintenance UI.
 *
 *   php bin/set-admin-password.php 'your-strong-password'
 *
 * Writes a bcrypt hash to data/config/admin-password.php (gitignored, and a
 * guarded PHP file so it is never served as static text). With DDEV:
 *
 *   ddev exec php bin/set-admin-password.php 'your-strong-password'
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once SFC_APP_DIR . '/src/admin/auth.php';

if ( PHP_SAPI !== 'cli' ) {
    http_response_code( 403 );
    exit( "CLI only.\n" );
}

$password = $argv[1] ?? '';
if ( strlen( $password ) < 8 ) {
    fwrite( STDERR, "Usage: php bin/set-admin-password.php '<password>'  (min 8 chars)\n" );
    exit( 1 );
}

$hash = password_hash( $password, PASSWORD_DEFAULT );
$file = sfc_admin_password_file();
$dir  = dirname( $file );
if ( ! is_dir( $dir ) ) {
    mkdir( $dir, 0775, true );
}

$contents = "<?php\n"
    . "// Admin password hash for Sheet Fed Calc. Gitignored. Do not serve.\n"
    . "if ( ! defined( 'ABSPATH' ) ) { exit; }\n"
    . 'return ' . var_export( $hash, true ) . ";\n";

if ( false === file_put_contents( $file, $contents, LOCK_EX ) ) {
    fwrite( STDERR, "Failed to write {$file}\n" );
    exit( 1 );
}

echo "Admin password set. Login at /admin/login.php\n";
