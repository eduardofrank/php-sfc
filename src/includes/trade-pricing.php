<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trade (wholesale) pricing.
 *
 * Per-role discount percentages stored in sfc_trade_pricing and applied as an
 * explicit final-total discount after the turnaround surcharge. A logged-in
 * customer gets the highest discount among their roles; guests get none. The
 * sfc_trade_discount_percent filter allows per-customer overrides. Because
 * discounts resolve at quote time, cart re-quoting and add-to-cart price
 * verification stay consistent automatically, and shared quote links always
 * re-price for the viewer.
 */

/**
 * Default trade pricing map (no discounts).
 *
 * @return array<string,array<string,float>>
 */
function sfc_get_default_trade_pricing() {
    return array();
}

/**
 * Sanitize the trade pricing map.
 *
 * @param mixed $value Raw value: array of role => array( 'percent' => float ).
 * @return array<string,array<string,float>>|WP_Error
 */
function sfc_sanitize_trade_pricing( $value ) {
    if ( ! is_array( $value ) ) {
        return new WP_Error( 'invalid_trade_pricing', 'Trade pricing must be an array.' );
    }

    $clean = array();

    foreach ( $value as $role => $entry ) {
        $role = sanitize_key( $role );
        if ( '' === $role ) {
            continue;
        }

        $percent = is_array( $entry ) ? ( $entry['percent'] ?? 0 ) : $entry;
        if ( ! is_numeric( $percent ) ) {
            return new WP_Error( 'invalid_trade_pricing', 'Trade discount must be numeric for role: ' . $role );
        }

        $percent = round( (float) $percent, 4 );
        if ( $percent < 0 || $percent > 100 ) {
            return new WP_Error( 'invalid_trade_pricing', 'Trade discount must be between 0 and 100 for role: ' . $role );
        }

        $clean[ $role ] = array( 'percent' => $percent );
    }

    return $clean;
}

/**
 * Stored trade pricing map.
 *
 * @return array<string,array<string,float>>
 */
function sfc_get_trade_pricing() {
    $stored = get_option( 'sfc_trade_pricing', sfc_get_default_trade_pricing() );
    $clean  = sfc_sanitize_trade_pricing( $stored );

    return is_wp_error( $clean ) ? sfc_get_default_trade_pricing() : $clean;
}

/**
 * Roles of the current user (empty for guests).
 *
 * @return string[]
 */
function sfc_get_current_user_roles() {
    if ( ! function_exists( 'wp_get_current_user' ) || ! is_user_logged_in() ) {
        return array();
    }

    $user = wp_get_current_user();

    return is_object( $user ) && ! empty( $user->roles ) ? array_map( 'strval', (array) $user->roles ) : array();
}

/**
 * Trade discount percent for the current customer.
 *
 * Takes the highest discount among the user's roles, then lets the
 * sfc_trade_discount_percent filter adjust it (e.g. per-customer deals).
 *
 * @return float 0–100.
 */
function sfc_get_trade_discount_percent() {
    $pricing = sfc_get_trade_pricing();
    $percent = 0.0;

    foreach ( sfc_get_current_user_roles() as $role ) {
        $role_pct = (float) ( $pricing[ sanitize_key( $role ) ]['percent'] ?? 0 );
        if ( $role_pct > $percent ) {
            $percent = $role_pct;
        }
    }

    /**
     * Filter the trade discount percent applied to the current customer.
     *
     * @param float $percent Discount percent (0–100).
     * @param int   $user_id Current user id (0 for guests).
     */
    $percent = (float) apply_filters(
        'sfc_trade_discount_percent',
        $percent,
        function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0
    );

    return min( 100.0, max( 0.0, $percent ) );
}

/**
 * Apply the customer's trade discount to a pricing result.
 *
 * Runs after the turnaround surcharge so the discount covers the whole job
 * (print, lamination, job services, surcharge) as one predictable percentage
 * of the public price.
 *
 * @param array<string,mixed> $pricing        Pricing result.
 * @param int                 $sheet_quantity Press sheets for unit-price recalc.
 * @return array<string,mixed>
 */
function sfc_apply_trade_pricing( $pricing, $sheet_quantity = 0 ) {
    if ( ! is_array( $pricing ) ) {
        return $pricing;
    }

    $percent = sfc_get_trade_discount_percent();
    if ( $percent <= 0 ) {
        return $pricing;
    }

    $list_total = (float) $pricing['totalPrice'];
    $amount     = round( $list_total * ( $percent / 100 ), 2 );
    $total      = round( $list_total - $amount, 2 );

    $pricing['listTotalPrice']      = $list_total;
    $pricing['tradeDiscountPct']    = $percent;
    $pricing['tradeDiscountAmount'] = $amount;
    $pricing['totalPrice']          = $total;

    $sheet_quantity = absint( $sheet_quantity );
    if ( $sheet_quantity > 0 ) {
        $pricing['unitPrice'] = round( $total / $sheet_quantity, 2 );
    }

    return $pricing;
}

/**
 * Cart/order display row for an applied trade discount.
 *
 * @param array<string,mixed> $quote    Quote payload.
 * @param string              $language Language code.
 * @return array<string,string>|null
 */
function sfc_build_trade_discount_cart_row( $quote, $language ) {
    $pricing = (array) ( $quote['pricing'] ?? array() );
    $amount  = (float) ( $pricing['tradeDiscountAmount'] ?? 0 );

    if ( $amount <= 0 ) {
        return null;
    }

    $percent = (float) ( $pricing['tradeDiscountPct'] ?? 0 );

    return array(
        'key'   => 'es' === $language ? 'Descuento mayorista' : 'Trade discount',
        'value' => sprintf(
            '−$%s (%s%%)',
            number_format( $amount, 2, '.', '' ),
            rtrim( rtrim( number_format( $percent, 2, '.', '' ), '0' ), '.' )
        ),
    );
}

/**
 * Editable roles for the trade pricing admin card.
 *
 * @return array<string,string> role slug => display name.
 */
function sfc_get_editable_trade_roles() {
    if ( ! function_exists( 'wp_roles' ) ) {
        return array();
    }

    $roles = array();
    foreach ( (array) wp_roles()->roles as $slug => $role ) {
        $roles[ (string) $slug ] = (string) ( $role['name'] ?? $slug );
    }

    return $roles;
}
