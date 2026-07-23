<?php
/**
 * Seed data/config/options.json with the current code defaults for every
 * price-affecting option. Run once to materialize the tracked config file;
 * safe to re-run (it overwrites each seeded key with the code default).
 *
 *   php bin/seed-config.php
 *
 * After running, review and commit data/config/options.json.
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';

if ( PHP_SAPI !== 'cli' ) {
    http_response_code( 403 );
    exit( "CLI only.\n" );
}

$seed = array(
    'sfc_price_tables'              => sfc_get_default_price_tables(),
    'sfc_quantity_tiers'            => sfc_get_default_quantity_tiers(),
    'sfc_sheet_specs'               => sfc_get_default_sheet_specs(),
    'sfc_sheet_imposition_gap_mm'   => sfc_get_default_sheet_imposition_gap_mm(),
    'sfc_job_service_rates'         => sfc_get_default_job_service_rates(),
    'sfc_die_cut_rates'             => sfc_get_default_die_cut_rates(),
    'sfc_turnaround_rates'          => sfc_get_default_turnaround_rates(),
    'sfc_paper_catalog'             => sfc_get_default_paper_catalog(),
    'sfc_fulfillment_settings'      => sfc_get_default_fulfillment_settings(),
);

foreach ( $seed as $key => $value ) {
    update_option( $key, $value );
    echo "seeded: {$key}\n";
}

echo "\nWrote " . sfc_options_store_file() . "\n";
