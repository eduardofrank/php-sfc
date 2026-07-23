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
define( 'SFC_PLUGIN_URL', '/' );
define( 'SFC_VERSION', '1.0.0' );

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
