<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get active plugin language.
 *
 * @return string
 */
function sfc_get_language() {
    $language = get_option( 'sfc_language', sfc_get_default_language() );
    return in_array( $language, array( 'en', 'es' ), true ) ? $language : sfc_get_default_language();
}

/**
 * Get active display units.
 *
 * @return string
 */
function sfc_get_units() {
    $units = get_option( 'sfc_units', sfc_get_default_units() );
    return in_array( $units, array( 'metric', 'imperial' ), true ) ? $units : sfc_get_default_units();
}

/**
 * Gap between imposed units on a press sheet (millimeters).
 *
 * @return float
 */
function sfc_get_sheet_imposition_gap_mm() {
    $gap = get_option( 'sfc_sheet_imposition_gap_mm', sfc_get_default_sheet_imposition_gap_mm() );
    $clean = sfc_sanitize_sheet_imposition_gap_mm( $gap );
    return is_wp_error( $clean ) ? sfc_get_default_sheet_imposition_gap_mm() : $clean;
}

/**
 * Get sheet cut and printable dimensions.
 *
 * @return array<string,int>
 */
function sfc_get_sheet_specs() {
    $specs = get_option( 'sfc_sheet_specs', sfc_get_default_sheet_specs() );
    $clean = sfc_sanitize_sheet_specs( $specs );
    return is_wp_error( $clean ) ? sfc_get_default_sheet_specs() : $clean;
}

/**
 * Get quantity tier definitions.
 *
 * @return array<int,array<string,mixed>>
 */
function sfc_get_quantity_tiers() {
    $tiers = get_option( 'sfc_quantity_tiers', sfc_get_default_quantity_tiers() );
    $clean = sfc_sanitize_quantity_tiers( $tiers );
    return is_wp_error( $clean ) ? sfc_get_default_quantity_tiers() : $clean;
}

/**
 * Get editable price tables.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_price_tables() {
    $stored   = get_option( 'sfc_price_tables', array() );
    $stored   = is_array( $stored ) ? $stored : array();
    $defaults = sfc_get_default_price_tables();

    foreach ( $defaults as $table_id => $default_table ) {
        if ( ! isset( $stored[ $table_id ] ) ) {
            $stored[ $table_id ] = $default_table;
        }
    }

    $clean = sfc_sanitize_price_tables( $stored );
    return is_wp_error( $clean ) ? $defaults : $clean;
}

/**
 * Get editable job service surcharge rates.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_job_service_rates() {
    $stored   = get_option( 'sfc_job_service_rates', array() );
    $stored   = is_array( $stored ) ? $stored : array();
    $defaults = sfc_get_default_job_service_rates();
    $merged   = array();

    foreach ( $defaults as $service_key => $default_entry ) {
        $merged[ $service_key ] = isset( $stored[ $service_key ] ) && is_array( $stored[ $service_key ] )
            ? array_merge( $default_entry, $stored[ $service_key ] )
            : $default_entry;
    }

    $clean = sfc_sanitize_job_service_rates( $merged );
    return is_wp_error( $clean ) ? $defaults : $clean;
}

/**
 * Get editable die-cut surcharge rates by press sheet count tier.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_die_cut_rates() {
    $stored   = get_option( 'sfc_die_cut_rates', array() );
    $stored   = is_array( $stored ) ? $stored : array();
    $defaults = sfc_get_default_die_cut_rates();
    $merged   = array();

    foreach ( $defaults as $tier_key => $default_entry ) {
        $merged[ $tier_key ] = isset( $stored[ $tier_key ] ) && is_array( $stored[ $tier_key ] )
            ? array_merge( $default_entry, $stored[ $tier_key ] )
            : $default_entry;
    }

    $clean = sfc_sanitize_die_cut_rates( $merged );
    return is_wp_error( $clean ) ? $defaults : $clean;
}

/**
 * Get editable turnaround surcharge rates.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_turnaround_rates() {
    $defaults = sfc_get_default_turnaround_rates();
    $stored   = get_option( 'sfc_turnaround_rates', false );

    if ( false === $stored ) {
        $legacy = get_option( 'sfc_fulfillment_settings', array() );
        if ( is_array( $legacy ) && isset( $legacy['sameDaySurchargePct'] ) ) {
            $defaults['same_day']['percent'] = max( 0.0, (float) $legacy['sameDaySurchargePct'] );
        }
    } else {
        $stored = is_array( $stored ) ? $stored : array();
        foreach ( $defaults as $rate_key => $default_entry ) {
            if ( isset( $stored[ $rate_key ] ) && is_array( $stored[ $rate_key ] ) ) {
                $defaults[ $rate_key ] = array_merge( $default_entry, $stored[ $rate_key ] );
            }
        }
    }

    $clean = sfc_sanitize_turnaround_rates( $defaults );
    return is_wp_error( $clean ) ? sfc_get_default_turnaround_rates() : $clean;
}

/**
 * Get paper catalog metadata.
 *
 * @return array<string,array<string,mixed>>
 */
function sfc_get_paper_catalog() {
    $catalog = get_option( 'sfc_paper_catalog', sfc_get_default_paper_catalog() );
    $clean   = sfc_sanitize_paper_catalog( $catalog );
    return is_wp_error( $clean ) ? sfc_get_default_paper_catalog() : $clean;
}

/**
 * Data retention settings (days) with defaults applied.
 *
 * quoteDays: saved quotes / shareable quote records (min 7).
 * artworkDays: unclaimed artwork uploads (min 1); artwork attached to an
 * order is never cleaned up.
 *
 * @return array{quoteDays:int,artworkDays:int}
 */
function sfc_get_retention_settings() {
    $stored = get_option( 'sfc_retention', array() );
    $clean  = sfc_sanitize_retention_settings( $stored );

    return is_wp_error( $clean ) ? array( 'quoteDays' => 180, 'artworkDays' => 7 ) : $clean;
}

/**
 * Sanitize retention settings.
 *
 * @param mixed $value Raw settings array.
 * @return array{quoteDays:int,artworkDays:int}|WP_Error
 */
function sfc_sanitize_retention_settings( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_retention', 'Retention settings must be an array.' );
    }

    $quote   = isset( $value['quoteDays'] ) ? (int) $value['quoteDays'] : 180;
    $artwork = isset( $value['artworkDays'] ) ? (int) $value['artworkDays'] : 7;

    if ( $quote < 7 || $quote > 3650 || $artwork < 1 || $artwork > 365 ) {
        return new WP_Error(
            'invalid_retention',
            'Quote retention must be 7-3650 days; artwork retention 1-365 days.'
        );
    }

    return array(
        'quoteDays'   => $quote,
        'artworkDays' => $artwork,
    );
}

/**
 * Return ordered tier keys.
 *
 * @return string[]
 */
function sfc_get_tier_keys() {
    return array_map(
        static function ( $tier ) {
            return $tier['key'];
        },
        sfc_get_quantity_tiers()
    );
}

/**
 * Sanitize language setting.
 *
 * @param mixed $value Raw value.
 * @return string|WP_Error
 */
function sfc_sanitize_language( $value ) {
    $value = sanitize_key( (string) $value );
    if ( ! in_array( $value, array( 'en', 'es' ), true ) ) {
        return new WP_Error( 'invalid_language', 'Language must be en or es.' );
    }
    return $value;
}

/**
 * Sanitize units setting.
 *
 * @param mixed $value Raw value.
 * @return string|WP_Error
 */
function sfc_sanitize_units( $value ) {
    $value = sanitize_key( (string) $value );
    if ( ! in_array( $value, array( 'metric', 'imperial' ), true ) ) {
        return new WP_Error( 'invalid_units', 'Units must be metric or imperial.' );
    }
    return $value;
}

/**
 * Sanitize imposition gap setting (millimeters).
 *
 * @param mixed $value Raw value.
 * @return float|WP_Error
 */
function sfc_sanitize_sheet_imposition_gap_mm( $value ) {
    if ( ! is_numeric( $value ) ) {
        return new WP_Error( 'invalid_imposition_gap', 'Imposition gap must be numeric.' );
    }

    $gap = round( (float) $value, 2 );
    if ( $gap < 0 || $gap > 50 ) {
        return new WP_Error( 'invalid_imposition_gap', 'Imposition gap must be between 0 and 50 mm.' );
    }

    return $gap;
}

/**
 * Sanitize sheet dimension specs.
 *
 * @param mixed $value Raw value.
 * @return array<string,int>|WP_Error
 */
function sfc_sanitize_sheet_specs( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_sheet_specs', 'Sheet specs must be an array.' );
    }

    $defaults = sfc_get_default_sheet_specs();
    $clean    = array();

    foreach ( $defaults as $key => $default ) {
        if ( ! isset( $value[ $key ] ) ) {
            return new WP_Error( 'invalid_sheet_specs', 'Missing sheet spec key: ' . $key );
        }
        $num = absint( $value[ $key ] );
        if ( $num <= 0 ) {
            return new WP_Error( 'invalid_sheet_specs', 'Sheet dimensions must be positive.' );
        }
        $clean[ $key ] = $num;
    }

    if ( $clean['printableWidthMm'] > $clean['cutWidthMm'] || $clean['printableHeightMm'] > $clean['cutHeightMm'] ) {
        return new WP_Error( 'invalid_sheet_specs', 'Printable area cannot exceed cut sheet size.' );
    }

    return $clean;
}

/**
 * Sanitize quantity tier definitions.
 *
 * @param mixed $value Raw value.
 * @return array<int,array<string,mixed>>|WP_Error
 */
function sfc_sanitize_quantity_tiers( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_quantity_tiers', 'Quantity tiers must be an array.' );
    }

    $clean = array();
    foreach ( $value as $tier ) {
        if ( ! is_array( $tier ) ) {
            return new WP_Error( 'invalid_quantity_tiers', 'Each tier must be an array.' );
        }

        $key = sanitize_key( $tier['key'] ?? '' );
        $min = absint( $tier['min'] ?? 0 );
        $max = array_key_exists( 'max', $tier ) && null !== $tier['max'] ? absint( $tier['max'] ) : null;

        if ( '' === $key || $min <= 0 ) {
            return new WP_Error( 'invalid_quantity_tiers', 'Tier key and min must be valid.' );
        }

        if ( null !== $max && $max < $min ) {
            return new WP_Error( 'invalid_quantity_tiers', 'Tier max cannot be less than min.' );
        }

        $clean[] = array(
            'key' => $key,
            'min' => $min,
            'max' => $max,
        );
    }

    if ( empty( $clean ) ) {
        return new WP_Error( 'invalid_quantity_tiers', 'At least one quantity tier is required.' );
    }

    return $clean;
}

/**
 * Sanitize a single price value.
 *
 * @param mixed $value Raw price.
 * @return float|WP_Error
 */
function sfc_sanitize_price_value( $value ) {
    if ( ! is_numeric( $value ) ) {
        return new WP_Error( 'invalid_price', 'Price must be numeric.' );
    }

    $price = round( (float) $value, 2 );
    if ( $price < 0 ) {
        return new WP_Error( 'invalid_price', 'Price cannot be negative.' );
    }

    return $price;
}

/**
 * Sanitize editable price tables.
 *
 * @param mixed $value Raw value.
 * @return array<string,array<string,mixed>>|WP_Error
 */
function sfc_sanitize_price_tables( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_price_tables', 'Price tables must be an array.' );
    }

    $defaults  = sfc_get_default_price_tables();
    $tier_keys = array_map(
        static function ( $tier ) {
            return $tier['key'];
        },
        sfc_get_default_quantity_tiers()
    );
    $clean     = array();

    foreach ( $defaults as $table_id => $default_table ) {
        if ( ! isset( $value[ $table_id ] ) || ! is_array( $value[ $table_id ] ) ) {
            return new WP_Error( 'invalid_price_tables', 'Missing price table: ' . $table_id );
        }

        $incoming = $value[ $table_id ];
        $modes    = $default_table['print_modes'];
        $prices   = array();

        if ( ! isset( $incoming['prices'] ) || ! is_array( $incoming['prices'] ) ) {
            return new WP_Error( 'invalid_price_tables', 'Missing prices for table: ' . $table_id );
        }

        foreach ( $modes as $mode ) {
            if ( ! isset( $incoming['prices'][ $mode ] ) || ! is_array( $incoming['prices'][ $mode ] ) ) {
                return new WP_Error( 'invalid_price_tables', 'Missing print mode prices: ' . $table_id . ' / ' . $mode );
            }

            $prices[ $mode ] = array();
            foreach ( $tier_keys as $tier_key ) {
                if ( ! array_key_exists( $tier_key, $incoming['prices'][ $mode ] ) ) {
                    return new WP_Error( 'invalid_price_tables', 'Missing tier price: ' . $table_id . ' / ' . $mode . ' / ' . $tier_key );
                }

                $price = sfc_sanitize_price_value( $incoming['prices'][ $mode ][ $tier_key ] );
                if ( is_wp_error( $price ) ) {
                    return $price;
                }
                $prices[ $mode ][ $tier_key ] = $price;
            }
        }

        $clean[ $table_id ] = array(
            'label_key'   => $default_table['label_key'],
            'print_modes' => $modes,
            'prices'      => $prices,
        );
    }

    return $clean;
}

/**
 * Sanitize job service percentage rates.
 *
 * @param mixed $value Raw value.
 * @return array<string,array<string,mixed>>|WP_Error
 */
function sfc_sanitize_job_service_rates( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_job_service_rates', 'Job service rates must be an array.' );
    }

    $defaults = sfc_get_default_job_service_rates();
    $clean    = array();

    foreach ( $defaults as $service_key => $default_entry ) {
        $entry = isset( $value[ $service_key ] ) && is_array( $value[ $service_key ] )
            ? $value[ $service_key ]
            : array();

        $percent = isset( $entry['percent'] ) ? (float) $entry['percent'] : (float) $default_entry['percent'];
        if ( $percent < 0 ) {
            return new WP_Error( 'invalid_job_service_rates', 'Service percentages cannot be negative.' );
        }

        $clean[ $service_key ] = array(
            'label_key' => $default_entry['label_key'],
            'percent'   => round( $percent, 4 ),
        );
    }

    return $clean;
}

/**
 * Sanitize die-cut percentage rates by press sheet count tier.
 *
 * @param mixed $value Raw value.
 * @return array<string,array<string,mixed>>|WP_Error
 */
function sfc_sanitize_die_cut_rates( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_die_cut_rates', 'Die-cut rates must be an array.' );
    }

    $defaults = sfc_get_default_die_cut_rates();
    $clean    = array();

    foreach ( $defaults as $tier_key => $default_entry ) {
        $entry = isset( $value[ $tier_key ] ) && is_array( $value[ $tier_key ] )
            ? $value[ $tier_key ]
            : array();

        $percent = isset( $entry['percent'] ) ? (float) $entry['percent'] : (float) $default_entry['percent'];
        if ( $percent < 0 ) {
            return new WP_Error( 'invalid_die_cut_rates', 'Die-cut percentages cannot be negative.' );
        }

        $clean[ $tier_key ] = array(
            'label_key'  => $default_entry['label_key'],
            'min_sheets' => (int) $default_entry['min_sheets'],
            'max_sheets' => $default_entry['max_sheets'],
            'percent'    => round( $percent, 4 ),
        );
    }

    return $clean;
}

/**
 * Sanitize turnaround percentage rates.
 *
 * @param mixed $value Raw value.
 * @return array<string,array<string,mixed>>|WP_Error
 */
function sfc_sanitize_turnaround_rates( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_turnaround_rates', 'Turnaround rates must be an array.' );
    }

    $defaults = sfc_get_default_turnaround_rates();
    $clean    = array();

    foreach ( $defaults as $rate_key => $default_entry ) {
        $entry = isset( $value[ $rate_key ] ) && is_array( $value[ $rate_key ] )
            ? $value[ $rate_key ]
            : array();

        $percent = isset( $entry['percent'] ) ? (float) $entry['percent'] : (float) $default_entry['percent'];
        if ( $percent < 0 ) {
            return new WP_Error( 'invalid_turnaround_rates', 'Turnaround percentages cannot be negative.' );
        }

        $clean[ $rate_key ] = array(
            'label_key' => $default_entry['label_key'],
            'percent'   => round( $percent, 4 ),
        );
    }

    return $clean;
}

/**
 * Sanitize paper catalog metadata.
 *
 * @param mixed $value Raw value.
 * @return array<string,array<string,mixed>>|WP_Error
 */
function sfc_sanitize_paper_catalog( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_paper_catalog', 'Paper catalog must be an array.' );
    }

    $defaults = sfc_get_default_paper_catalog();
    $clean    = array();

    foreach ( $defaults as $paper_type => $default_entry ) {
        if ( ! isset( $value[ $paper_type ] ) || ! is_array( $value[ $paper_type ] ) ) {
            $clean[ $paper_type ] = $default_entry;
            continue;
        }

        $entry = $value[ $paper_type ];
        $clean[ $paper_type ] = array(
            'label_key' => sanitize_key( $entry['label_key'] ?? $default_entry['label_key'] ),
            'duplex'    => ! empty( $entry['duplex'] ),
        );

        if ( 'coated' === $paper_type ) {
            $clean['coated']['surfaces'] = array();
            foreach ( (array) ( $entry['surfaces'] ?? $default_entry['surfaces'] ) as $surface ) {
                $surface = sanitize_key( (string) $surface );
                if ( in_array( $surface, array( 'matte', 'glossy' ), true ) ) {
                    $clean['coated']['surfaces'][] = $surface;
                }
            }
            if ( empty( $clean['coated']['surfaces'] ) ) {
                $clean['coated']['surfaces'] = $default_entry['surfaces'];
            }

            $clean['coated']['weight_groups'] = $default_entry['weight_groups'];
        } else {
            $table_id = sanitize_key( $entry['table_id'] ?? $default_entry['table_id'] );
            if ( ! isset( sfc_get_default_price_tables()[ $table_id ] ) ) {
                return new WP_Error( 'invalid_paper_catalog', 'Unknown table id for paper type: ' . $paper_type );
            }
            $clean[ $paper_type ]['table_id'] = $table_id;
        }
    }

    return $clean;
}

/**
 * Resolve price table id for a paper selection.
 *
 * @param string   $paper_type Paper type key.
 * @param int|null $gsm        GSM for coated paper.
 * @return string|WP_Error
 */
function sfc_resolve_price_table_id( $paper_type, $gsm = null ) {
    $catalog = sfc_get_paper_catalog();
    $paper_type = sanitize_key( $paper_type );

    if ( ! isset( $catalog[ $paper_type ] ) ) {
        return new WP_Error( 'invalid_paper_type', 'Unknown paper type.' );
    }

    $entry = $catalog[ $paper_type ];

    if ( 'coated' === $paper_type ) {
        $gsm = absint( $gsm );
        if ( $gsm <= 0 ) {
            return new WP_Error( 'invalid_gsm', 'Coated paper requires a valid GSM.' );
        }

        foreach ( $entry['weight_groups'] as $group ) {
            if ( in_array( $gsm, $group['gsm'], true ) ) {
                return $group['table_id'];
            }
        }

        return new WP_Error( 'invalid_gsm', 'Unsupported coated paper GSM.' );
    }

    return $entry['table_id'];
}

/**
 * Return whether a paper type supports duplex printing.
 *
 * @param string $paper_type Paper type key.
 * @return bool
 */
function sfc_paper_supports_duplex( $paper_type ) {
    $catalog = sfc_get_paper_catalog();
    $paper_type = sanitize_key( $paper_type );
    return ! empty( $catalog[ $paper_type ]['duplex'] );
}
