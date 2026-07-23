<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Default fulfillment settings.
 *
 * @return array<string,mixed>
 */
function sfc_get_default_fulfillment_settings() {
    return array(
        'shopCity'                    => 'Caracas',
        'sameDayShowOnCalculator'     => true,
        'sameDayAllowLocalPickup'     => true,
        'sameDayAllowShopCityDelivery' => true,
    );
}

/**
 * Get fulfillment settings merged with defaults.
 *
 * @return array<string,mixed>
 */
function sfc_get_fulfillment_settings() {
    $defaults = sfc_get_default_fulfillment_settings();
    $stored   = get_option( 'sfc_fulfillment_settings', array() );
    $stored   = is_array( $stored ) ? $stored : array();

    return array(
        'shopCity'                     => sanitize_text_field( $stored['shopCity'] ?? $defaults['shopCity'] ),
        'sameDayShowOnCalculator'      => sfc_sanitize_bool_setting(
            $stored['sameDayShowOnCalculator'] ?? null,
            $defaults['sameDayShowOnCalculator']
        ),
        'sameDayAllowLocalPickup'      => sfc_sanitize_bool_setting(
            $stored['sameDayAllowLocalPickup'] ?? null,
            $defaults['sameDayAllowLocalPickup']
        ),
        'sameDayAllowShopCityDelivery' => sfc_sanitize_bool_setting(
            $stored['sameDayAllowShopCityDelivery'] ?? null,
            $defaults['sameDayAllowShopCityDelivery']
        ),
    );
}

/**
 * Normalize a stored boolean fulfillment flag.
 *
 * @param mixed $value   Raw stored value.
 * @param bool  $default Default when value is null.
 * @return bool
 */
function sfc_sanitize_bool_setting( $value, $default ) {
    if ( null === $value ) {
        return (bool) $default;
    }

    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return (int) $value === 1;
    }

    if ( is_string( $value ) ) {
        $normalized = strtolower( trim( $value ) );
        if ( in_array( $normalized, array( '1', 'true', 'yes', 'on' ), true ) ) {
            return true;
        }
        if ( in_array( $normalized, array( '0', 'false', 'no', 'off', '' ), true ) ) {
            return false;
        }
    }

    return (bool) $value;
}

/**
 * Sanitize fulfillment settings for import/storage.
 *
 * @param mixed $value Raw value.
 * @return array<string,string>|WP_Error
 */
function sfc_sanitize_fulfillment_settings( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_fulfillment_settings', 'Fulfillment settings must be an array.' );
    }

    $defaults = sfc_get_default_fulfillment_settings();
    $shop_city = sanitize_text_field( $value['shopCity'] ?? $defaults['shopCity'] );

    if ( '' === $shop_city ) {
        return new WP_Error( 'invalid_fulfillment_settings', 'Shop city cannot be empty.' );
    }

    return array(
        'shopCity'                     => $shop_city,
        'sameDayShowOnCalculator'      => sfc_sanitize_bool_setting(
            $value['sameDayShowOnCalculator'] ?? null,
            $defaults['sameDayShowOnCalculator']
        ),
        'sameDayAllowLocalPickup'      => sfc_sanitize_bool_setting(
            $value['sameDayAllowLocalPickup'] ?? null,
            $defaults['sameDayAllowLocalPickup']
        ),
        'sameDayAllowShopCityDelivery' => sfc_sanitize_bool_setting(
            $value['sameDayAllowShopCityDelivery'] ?? null,
            $defaults['sameDayAllowShopCityDelivery']
        ),
    );
}

/**
 * Shop city used to decide whether same-day turnaround is offered.
 *
 * @return string
 */
function sfc_get_shop_city() {
    $settings = sfc_get_fulfillment_settings();
    return $settings['shopCity'];
}

/**
 * Whether local fulfillment (same-day) is available for the current request.
 *
 * Defaults to true whenever a configured calculator product is being rendered
 * or quoted. Quote AJAX requests run outside the product-page main query, so
 * callers that already resolved a product config must pass it here; otherwise
 * is_product() is false during admin-ajax and same-day would be rejected even
 * though the calculator offered it. Shipping enforcement happens separately at
 * checkout via sfc_checkout_is_local_fulfillment(). Use the
 * sfc_local_fulfillment_available filter to tie availability to shipping
 * zones, local pickup, or checkout city.
 *
 * @param array<string,mixed>|null $product Optional resolved calculator product config.
 * @return bool
 */
function sfc_is_local_fulfillment_available( $product = null ) {
    $settings  = sfc_get_fulfillment_settings();
    $available = false;

    if ( empty( $settings['sameDayShowOnCalculator'] ) ) {
        return false;
    }

    if ( is_array( $product ) && ! empty( $product ) ) {
        $available = true;
    } elseif ( function_exists( 'is_product' ) && is_product() && function_exists( 'sfc_get_current_product_calculator_config' ) ) {
        $available = (bool) sfc_get_current_product_calculator_config();
    }

    /**
     * Filter whether same-day turnaround can be offered.
     *
     * @param bool $available Whether local fulfillment is available.
     */
    return (bool) apply_filters( 'sfc_local_fulfillment_available', $available );
}

/**
 * Whether a turnaround option requires local fulfillment.
 *
 * @param array<string,mixed> $option Turnaround option config.
 * @return bool
 */
function sfc_turnaround_requires_local_fulfillment( $option ) {
    return ! empty( $option['localOnly'] );
}

/**
 * Resolve surcharge percent for a turnaround option from the price table rates.
 *
 * @param string $turnaround_key Turnaround option key (e.g. same_day, next_day).
 * @return float
 */
function sfc_get_turnaround_surcharge_pct( $turnaround_key ) {
    $turnaround_key = sanitize_key( (string) $turnaround_key );
    $rates          = sfc_get_turnaround_rates();

    if ( ! isset( $rates[ $turnaround_key ] ) ) {
        return 0.0;
    }

    return max( 0.0, (float) ( $rates[ $turnaround_key ]['percent'] ?? 0 ) );
}

/**
 * Return turnaround options available for calculator display.
 *
 * @param array<string,mixed> $product Product config.
 * @return array<string,array<string,mixed>>
 */
function sfc_get_product_turnaround_for_display( $product ) {
    $turnaround = (array) ( $product['turnaround'] ?? array() );
    $available  = array();

    foreach ( $turnaround as $key => $option ) {
        if ( sfc_turnaround_requires_local_fulfillment( $option ) && ! sfc_is_local_fulfillment_available( $product ) ) {
            continue;
        }

        $available[ $key ] = $option;
    }

    return $available;
}

/**
 * Apply turnaround surcharge to a pricing result.
 *
 * @param array<string,mixed> $product Product config.
 * @param array<string,mixed> $state   Calculator state.
 * @param array<string,mixed> $pricing Pricing result from sfc_calculate_job_price().
 * @return array<string,mixed>
 */
function sfc_apply_turnaround_surcharge( $product, $state, $pricing ) {
    $turnaround_key = sanitize_key( $state['turnaround'] ?? '' );
    $option         = $product['turnaround'][ $turnaround_key ] ?? null;

    if ( ! is_array( $option ) ) {
        return $pricing;
    }

    $surcharge_pct = sfc_get_turnaround_surcharge_pct( $turnaround_key );
    if ( $surcharge_pct <= 0 ) {
        return $pricing;
    }

    $base_total = (float) $pricing['totalPrice'];
    $amount     = round( $base_total * ( $surcharge_pct / 100 ), 2 );

    $pricing['baseTotalPrice']           = $base_total;
    $pricing['turnaround']               = $turnaround_key;
    $pricing['turnaroundSurchargePct']   = $surcharge_pct;
    $pricing['turnaroundSurchargeAmount'] = $amount;
    $pricing['totalPrice']               = round( $base_total + $amount, 2 );

    return $pricing;
}

/**
 * Normalize shipping city strings for comparison.
 *
 * @param string $city City name.
 * @return string
 */
function sfc_normalize_city_name( $city ) {
    $city = remove_accents( wp_strip_all_tags( (string) $city ) );
    $city = strtolower( trim( $city ) );
    return preg_replace( '/\s+/', ' ', $city );
}

/**
 * Whether checkout shipping qualifies as local fulfillment for same-day jobs.
 *
 * @return bool
 */
function sfc_checkout_is_local_fulfillment() {
    $settings = sfc_get_fulfillment_settings();

    if ( ! function_exists( 'WC' ) || ! WC()->session ) {
        return false;
    }

    if ( ! empty( $settings['sameDayAllowLocalPickup'] ) ) {
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
        foreach ( (array) $chosen_methods as $method ) {
            if ( is_string( $method ) && false !== stripos( $method, 'local_pickup' ) ) {
                /**
                 * Filter checkout local-fulfillment detection.
                 *
                 * @param bool $is_local Whether checkout qualifies for same-day turnaround.
                 */
                return (bool) apply_filters( 'sfc_checkout_is_local_fulfillment', true );
            }
        }
    }

    if ( ! empty( $settings['sameDayAllowShopCityDelivery'] ) && function_exists( 'WC' ) && WC()->customer ) {
        $city = sfc_normalize_city_name( WC()->customer->get_shipping_city() );
        $shop = sfc_normalize_city_name( sfc_get_shop_city() );
        if ( '' !== $city && $city === $shop ) {
            /**
             * Filter checkout local-fulfillment detection.
             *
             * @param bool $is_local Whether checkout qualifies for same-day turnaround.
             */
            return (bool) apply_filters( 'sfc_checkout_is_local_fulfillment', true );
        }
    }

    /**
     * Filter checkout local-fulfillment detection.
     *
     * @param bool $is_local Whether checkout qualifies for same-day turnaround.
     */
    return (bool) apply_filters( 'sfc_checkout_is_local_fulfillment', false );
}

add_action( 'woocommerce_check_cart_items', 'sfc_validate_cart_same_day_fulfillment' );

/**
 * Block checkout when a cart line requests same-day turnaround without local fulfillment.
 *
 * @return void
 */
function sfc_validate_cart_same_day_fulfillment() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart || sfc_checkout_is_local_fulfillment() ) {
        return;
    }

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( empty( $cart_item['sfc_calculator_data'] ) || ! is_array( $cart_item['sfc_calculator_data'] ) ) {
            continue;
        }

        $payload = $cart_item['sfc_calculator_data'];
        $slug    = sanitize_key( str_replace( '_', '-', $payload['productSlug'] ?? '' ) );
        $product = sfc_get_product_config( $slug );
        $state   = (array) ( $payload['state'] ?? array() );

        if ( ! $product || empty( $state['turnaround'] ) ) {
            continue;
        }

        $option = $product['turnaround'][ $state['turnaround'] ] ?? null;
        if ( is_array( $option ) && sfc_turnaround_requires_local_fulfillment( $option ) ) {
            wc_add_notice(
                sprintf(
                    'El tiempo "%s" solo está disponible para retiro en %s o entrega local sin envío.',
                    $option['label']['es'] ?? $state['turnaround'],
                    sfc_get_shop_city()
                ),
                'error'
            );
            return;
        }
    }
}
