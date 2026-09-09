<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$sfc_footer_bcv    = function_exists( 'sfc_current_usd_ves_rate' ) ? sfc_current_usd_ves_rate() : null;
$sfc_footer_usdt   = function_exists( 'sfc_current_usdt_rate' ) ? sfc_current_usdt_rate() : null;
$sfc_footer_factor = function_exists( 'sfc_ves_factor' ) ? sfc_ves_factor( $sfc_footer_bcv, $sfc_footer_usdt ) : null;
?>
<footer class="app-footer">
    <p>Lab Gráfico — calculadora de impresión con precios en USD.<?php
    if ( $sfc_footer_bcv ) {
        echo ' Cambio BCV de hoy: ' . esc_html( sfc_format_rate( $sfc_footer_bcv ) ) . '.';
    }
    if ( $sfc_footer_usdt ) {
        echo ' USDT: ' . esc_html( sfc_format_rate( $sfc_footer_usdt ) ) . '.';
    }
    if ( $sfc_footer_factor ) {
        echo ' Factor: ' . esc_html( sfc_format_factor( $sfc_footer_factor ) ) . '.';
    }
    ?></p>
</footer>
</body>
</html>
