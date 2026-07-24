<?php
/**
 * Standalone app bootstrap.
 *
 * Defines the constants the ported engine expects, loads the WordPress shim
 * layer, then requires the ported Sheet Fed Calc engine files in dependency
 * order (mirroring the original plugin's load order). Every entry point
 * (index.php, product.php, api/index.php) includes this single file.
 */

define( 'SFC_APP_DIR', __DIR__ );
// SFC_PLUGIN_DIR must point at the directory that contains includes/ so the
// ported products.php require_once( SFC_PLUGIN_DIR . 'includes/products/...' )
// paths resolve unchanged.
define( 'SFC_PLUGIN_DIR', __DIR__ . '/src/' );
define( 'SFC_VERSION', '1.0.0' );

/**
 * Derive the URL path prefix the app is served under (no trailing slash).
 *
 * Works for a real subdirectory of the docroot and for an Apache Alias, by
 * lining up the executing script's URL path (SCRIPT_NAME) with its filesystem
 * path (SCRIPT_FILENAME) relative to the app root. Honors an explicit
 * SFC_BASE_PATH environment override first, for setups it cannot infer.
 *
 * @param string $app_dir Filesystem path of the app root (this directory).
 * @return string '' at the site root, e.g. '/php-sfc' in a subdirectory.
 */
function sfc_compute_base_path( $app_dir ) {
    $env = getenv( 'SFC_BASE_PATH' );
    if ( false !== $env ) {
        return '/' === $env ? '' : rtrim( $env, '/' );
    }

    if ( 'cli' === PHP_SAPI ) {
        return '';
    }

    $app         = rtrim( str_replace( '\\', '/', $app_dir ), '/' );
    $script_name = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] ?? '' );
    $script_file = $_SERVER['SCRIPT_FILENAME'] ?? '';
    $script_file = str_replace( '\\', '/', ( $script_file ? ( realpath( $script_file ) ?: $script_file ) : '' ) );

    // Preferred: strip the script's app-relative path off the end of its URL.
    if ( '' !== $script_name && '' !== $script_file && 0 === strpos( $script_file, $app . '/' ) ) {
        $rel = substr( $script_file, strlen( $app ) ); // e.g. /api/index.php
        if ( '' !== $rel && $rel === substr( $script_name, -strlen( $rel ) ) ) {
            return rtrim( substr( $script_name, 0, strlen( $script_name ) - strlen( $rel ) ), '/' );
        }
    }

    // Fallback: diff the app root against DOCUMENT_ROOT.
    $docroot = isset( $_SERVER['DOCUMENT_ROOT'] ) ? str_replace( '\\', '/', $_SERVER['DOCUMENT_ROOT'] ) : '';
    $docroot = $docroot ? rtrim( ( realpath( $docroot ) ?: $docroot ), '/' ) : '';
    if ( '' !== $docroot && 0 === strpos( $app, $docroot ) ) {
        return rtrim( substr( $app, strlen( $docroot ) ), '/' );
    }

    return '';
}

/**
 * URL path prefix the app is served under, with no trailing slash.
 *
 * '' when served at the site root (https://host/), '/php-sfc' when served from a
 * subdirectory (https://host/php-sfc/). Every asset, API, and link URL is built
 * from this so the app works in either location. Override with the SFC_BASE_PATH
 * environment variable for alias/proxy setups where it cannot be derived.
 */
if ( ! defined( 'SFC_BASE_PATH' ) ) {
    define( 'SFC_BASE_PATH', sfc_compute_base_path( __DIR__ ) );
}
define( 'SFC_PLUGIN_URL', SFC_BASE_PATH . '/' );

require_once __DIR__ . '/wp-shims.php';

$sfc_engine_files = array(
    'default-data.php',
    'settings.php',
    'i18n.php',
    'fulfillment.php',
    'trade-pricing.php',
    'pricing.php',
    'imposition.php',
    'lamination.php',
    'die-cut-pricing.php',
    'hardcover-pricing.php',
    'job-services.php',
    'product-pricing.php',
    'booklet-pricing.php',
    'album-pricing.php',
    'products.php', // pulls in includes/products/*.php
    'calculator-steps.php',
    'public-data.php',
    'fold-hub.php',
);

foreach ( $sfc_engine_files as $sfc_file ) {
    require_once SFC_PLUGIN_DIR . 'includes/' . $sfc_file;
}

require_once __DIR__ . '/src/app-helpers.php';
