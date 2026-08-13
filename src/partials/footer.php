<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$sfc_footer_rate = function_exists( 'sfc_current_usd_ves_rate' ) ? sfc_current_usd_ves_rate() : null;
?>
<footer class="app-footer">
    <p>Lab Gráfico — calculadora de impresión con precios en USD.<?php
    if ( $sfc_footer_rate ) {
        echo ' Cambio BCV de hoy: ' . esc_html( sfc_format_rate( $sfc_footer_rate ) ) . '.';
    }
    ?></p>
</footer>
</body>
</html>
