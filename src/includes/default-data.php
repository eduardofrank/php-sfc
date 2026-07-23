<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Default plugin language.
 *
 * @return string
 */
function sfc_get_default_language() {
    return 'es';
}

/**
 * Default display units.
 *
 * @return string
 */
function sfc_get_default_units() {
    return 'metric';
}

/**
 * Gap between imposed units on a press sheet (millimeters).
 *
 * @return float
 */
function sfc_get_default_sheet_imposition_gap_mm() {
    return 4.0;
}

/**
 * Default sheet cut and printable dimensions (millimeters).
 *
 * @return array<string,int>
 */
function sfc_get_default_sheet_specs() {
    return array(
        'cutWidthMm'         => 475,
        'cutHeightMm'        => 320,
        'printableWidthMm'   => 450,
        'printableHeightMm'  => 310,
    );
}

/**
 * Default quantity tier brackets.
 *
 * @return array<int,array<string,mixed>>
 */
function sfc_get_default_quantity_tiers() {
    return array(
        array( 'key' => '1_10', 'min' => 1, 'max' => 10 ),
        array( 'key' => '11_25', 'min' => 11, 'max' => 25 ),
        array( 'key' => '26_50', 'min' => 26, 'max' => 50 ),
        array( 'key' => '51_100', 'min' => 51, 'max' => 100 ),
        array( 'key' => '101_200', 'min' => 101, 'max' => 200 ),
        array( 'key' => '201_300', 'min' => 201, 'max' => 300 ),
        array( 'key' => '301_plus', 'min' => 301, 'max' => null ),
    );
}

/**
 * Supported print modes.
 *
 * @return string[]
 */
function sfc_get_print_modes() {
    return array( '4x0', '4x4' );
}

/**
 * Default per-sheet price tables seeded from Price table.xlsx (USD).
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_default_price_tables() {
    $tier_keys = array( '1_10', '11_25', '26_50', '51_100', '101_200', '201_300', '301_plus' );

    $coated_115_150_4x0 = array(
        '1_10'     => 2.70,
        '11_25'    => 2.63,
        '26_50'    => 2.59,
        '51_100'   => 2.56,
        '101_200'  => 2.49,
        '201_300'  => 2.45,
        '301_plus' => 2.45,
    );

    $coated_115_150_4x4 = array(
        '1_10'     => 3.26,
        '11_25'    => 3.15,
        '26_50'    => 3.05,
        '51_100'   => 2.98,
        '101_200'  => 2.91,
        '201_300'  => 2.84,
        '301_plus' => 2.84,
    );

    $coated_200_300_4x0 = array(
        '1_10'     => 2.91,
        '11_25'    => 2.84,
        '26_50'    => 2.77,
        '51_100'   => 2.70,
        '101_200'  => 2.63,
        '201_300'  => 2.59,
        '301_plus' => 2.59,
    );

    $coated_200_300_4x4 = array(
        '1_10'     => 3.36,
        '11_25'    => 3.29,
        '26_50'    => 3.19,
        '51_100'   => 3.12,
        '101_200'  => 3.05,
        '201_300'  => 2.99,
        '301_plus' => 2.99,
    );

    $lithosticker_4x0 = $coated_115_150_4x0;

    $vinyl_4x0 = array(
        '1_10'     => 3.55,
        '11_25'    => 3.27,
        '26_50'    => 3.24,
        '51_100'   => 3.20,
        '101_200'  => 3.14,
        '201_300'  => 3.11,
        '301_plus' => 3.11,
    );

    $lamination_per_side = array(
        '1_10'     => 0.25,
        '11_25'    => 0.25,
        '26_50'    => 0.25,
        '51_100'   => 0.25,
        '101_200'  => 0.25,
        '201_300'  => 0.25,
        '301_plus' => 0.25,
    );

    $hardcover_per_unit = array(
        '1_10'     => 25.00,
        '11_25'    => 25.00,
        '26_50'    => 25.00,
        '51_100'   => 25.00,
        '101_200'  => 25.00,
        '201_300'  => 25.00,
        '301_plus' => 25.00,
    );

    return array(
        'coated_115_150' => array(
            'label_key'    => 'table_coated_115_150',
            'print_modes'  => array( '4x0', '4x4' ),
            'prices'       => array(
                '4x0' => $coated_115_150_4x0,
                '4x4' => $coated_115_150_4x4,
            ),
        ),
        'coated_200_300' => array(
            'label_key'    => 'table_coated_200_300',
            'print_modes'  => array( '4x0', '4x4' ),
            'prices'       => array(
                '4x0' => $coated_200_300_4x0,
                '4x4' => $coated_200_300_4x4,
            ),
        ),
        'lithosticker' => array(
            'label_key'    => 'table_lithosticker',
            'print_modes'  => array( '4x0' ),
            'prices'       => array(
                '4x0' => $lithosticker_4x0,
            ),
        ),
        'vinyl' => array(
            'label_key'    => 'table_vinyl',
            'print_modes'  => array( '4x0' ),
            'prices'       => array(
                '4x0' => $vinyl_4x0,
            ),
        ),
        'lamination' => array(
            'label_key'    => 'table_lamination',
            'print_modes'  => array( 'per_side' ),
            'prices'       => array(
                'per_side' => $lamination_per_side,
            ),
        ),
        'hardcover_binding' => array(
            'label_key'    => 'table_hardcover_binding',
            'print_modes'  => array( 'per_unit' ),
            'prices'       => array(
                'per_unit' => $hardcover_per_unit,
            ),
        ),
    );
}

/**
 * Default paper catalog metadata.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_default_paper_catalog() {
    return array(
        'coated' => array(
            'label_key'          => 'paper_coated',
            'duplex'             => true,
            'surfaces'           => array( 'matte', 'glossy' ),
            'weight_groups'      => array(
                array(
                    'gsm'       => array( 115, 150 ),
                    'table_id'  => 'coated_115_150',
                ),
                array(
                    'gsm'       => array( 200, 250, 300 ),
                    'table_id'  => 'coated_200_300',
                ),
            ),
        ),
        'lithosticker' => array(
            'label_key' => 'paper_lithosticker',
            'duplex'    => false,
            'table_id'  => 'lithosticker',
        ),
        'vinyl' => array(
            'label_key' => 'paper_vinyl',
            'duplex'    => false,
            'table_id'  => 'vinyl',
        ),
        'bond' => array(
            'label_key' => 'paper_bond',
            'duplex'    => true,
            'table_id'  => 'coated_115_150',
        ),
    );
}

/**
 * Default job service surcharges (% of print + lamination subtotal).
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_default_job_service_rates() {
    return array(
        'cutting'  => array(
            'label_key' => 'service_cutting',
            'percent'   => 0.0,
        ),
        'creasing' => array(
            'label_key' => 'service_creasing',
            'percent'   => 0.0,
        ),
        'stapling' => array(
            'label_key' => 'service_stapling',
            'percent'   => 0.0,
        ),
    );
}

/**
 * Default die-cut surcharges (% of print cost) by press sheet count tier.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_default_die_cut_rates() {
    return array(
        'up_to_50'  => array(
            'label_key'  => 'die_cut_tier_up_to_50',
            'min_sheets' => 1,
            'max_sheets' => 50,
            'percent'    => 25.0,
        ),
        'up_to_100' => array(
            'label_key'  => 'die_cut_tier_up_to_100',
            'min_sheets' => 51,
            'max_sheets' => 100,
            'percent'    => 20.0,
        ),
        'above_100' => array(
            'label_key'  => 'die_cut_tier_above_100',
            'min_sheets' => 101,
            'max_sheets' => null,
            'percent'    => 15.0,
        ),
    );
}

/**
 * Default turnaround surcharges (% of subtotal after print, lamination, and job services).
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_default_turnaround_rates() {
    return array(
        'same_day' => array(
            'label_key' => 'turnaround_same_day',
            'percent'   => 20.0,
        ),
    );
}
