<?php
/**
 * Price-maintenance admin. Password-protected. Every form saves through a
 * ported sanitizer (see src/admin/controller.php), so invalid input is rejected
 * rather than persisted.
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once SFC_APP_DIR . '/src/admin/auth.php';
require_once SFC_APP_DIR . '/src/admin/controller.php';

sfc_admin_require_login();

$flash = sfc_admin_handle_post();
$csrf  = sfc_admin_csrf_token();

$price_tables   = sfc_get_price_tables();
$die_cut_rates  = sfc_get_die_cut_rates();
$turnaround     = sfc_get_turnaround_rates();
$job_services   = sfc_get_job_service_rates();
$sheet_specs    = sfc_get_sheet_specs();
$imposition_gap = sfc_get_sheet_imposition_gap_mm();
$quantity_tiers = sfc_get_quantity_tiers();
$fulfillment    = sfc_get_fulfillment_settings();

/** Shorthand escapers. */
$h = static function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); };

/** Hidden CSRF + action fields for a form. */
$fields = static function ( $action ) use ( $csrf, $h ) {
    echo '<input type="hidden" name="csrf" value="' . $h( $csrf ) . '">';
    echo '<input type="hidden" name="admin_action" value="' . $h( $action ) . '">';
};
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mantenimiento de precios · Sheet Fed Calc</title>
    <link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="adm-body">
<header class="adm-header">
    <div>
        <strong>Sheet Fed Calc</strong> · Mantenimiento de precios
    </div>
    <nav class="adm-nav">
        <a href="/" target="_blank" rel="noopener">Ver calculadora ↗</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</header>

<main class="adm-main">
    <?php if ( $flash ) : ?>
        <div class="adm-flash <?php echo $flash['ok'] ? 'adm-flash--ok' : 'adm-flash--err'; ?>">
            <?php echo $h( $flash['message'] ); ?>
        </div>
    <?php endif; ?>

    <p class="adm-intro">
        Los precios están en USD. Los cambios se aplican de inmediato a la calculadora
        y se guardan en <code>data/config/options.json</code> (versionado en git).
    </p>

    <nav class="adm-toc">
        <a href="#tablas">Tablas de precios</a>
        <a href="#tarifas">Tarifas</a>
        <a href="#hoja">Hoja y montaje</a>
        <a href="#cantidad">Niveles de cantidad</a>
        <a href="#entrega">Entrega</a>
        <a href="#reset">Restaurar</a>
    </nav>

    <!-- ================= PRICE TABLES ================= -->
    <section id="tablas" class="adm-section">
        <h2>Tablas de precios (USD por hoja de impresión)</h2>
        <form method="post">
            <?php $fields( 'save_price_tables' ); ?>
            <?php foreach ( $price_tables as $table_id => $table ) : ?>
                <fieldset class="adm-card">
                    <legend><?php echo $h( sfc_t( $table['label_key'] ) ); ?></legend>
                    <div class="adm-grid-scroll">
                        <table class="adm-price-table">
                            <thead>
                                <tr>
                                    <th>Nivel</th>
                                    <?php foreach ( $table['print_modes'] as $mode ) : ?>
                                        <th><?php echo $h( sfc_print_mode_admin_label( $mode ) ); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $first_mode = $table['print_modes'][0];
                                foreach ( array_keys( $table['prices'][ $first_mode ] ) as $tier ) :
                                    ?>
                                    <tr>
                                        <th><?php echo $h( sfc_tier_label( $tier ) ); ?></th>
                                        <?php foreach ( $table['print_modes'] as $mode ) : ?>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                    name="prices[<?php echo $h( $table_id ); ?>][<?php echo $h( $mode ); ?>][<?php echo $h( $tier ); ?>]"
                                                    value="<?php echo $h( number_format( (float) $table['prices'][ $mode ][ $tier ], 2, '.', '' ) ); ?>">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="adm-btn">Guardar tablas de precios</button>
        </form>
    </section>

    <!-- ================= RATES ================= -->
    <section id="tarifas" class="adm-section">
        <h2>Tarifas</h2>

        <form method="post" class="adm-card">
            <?php $fields( 'save_die_cut_rates' ); ?>
            <h3>Troquelado (% del costo de impresión)</h3>
            <?php foreach ( $die_cut_rates as $key => $entry ) : ?>
                <label class="adm-row">
                    <span><?php echo $h( sfc_t( $entry['label_key'] ) ); ?></span>
                    <input type="number" step="0.01" min="0"
                        name="die_cut[<?php echo $h( $key ); ?>][percent]"
                        value="<?php echo $h( (float) $entry['percent'] ); ?>"> %
                </label>
            <?php endforeach; ?>
            <button type="submit" class="adm-btn">Guardar troquelado</button>
        </form>

        <form method="post" class="adm-card">
            <?php $fields( 'save_turnaround_rates' ); ?>
            <h3>Tiempo de entrega (% del subtotal)</h3>
            <?php foreach ( $turnaround as $key => $entry ) : ?>
                <label class="adm-row">
                    <span><?php echo $h( sfc_t( $entry['label_key'] ) ); ?></span>
                    <input type="number" step="0.01" min="0"
                        name="turnaround[<?php echo $h( $key ); ?>][percent]"
                        value="<?php echo $h( (float) $entry['percent'] ); ?>"> %
                </label>
            <?php endforeach; ?>
            <button type="submit" class="adm-btn">Guardar tiempo de entrega</button>
        </form>

        <form method="post" class="adm-card">
            <?php $fields( 'save_job_service_rates' ); ?>
            <h3>Servicios del trabajo (% del subtotal)</h3>
            <?php foreach ( $job_services as $key => $entry ) : ?>
                <label class="adm-row">
                    <span><?php echo $h( sfc_t( $entry['label_key'] ) ); ?></span>
                    <input type="number" step="0.01" min="0"
                        name="job_services[<?php echo $h( $key ); ?>][percent]"
                        value="<?php echo $h( (float) $entry['percent'] ); ?>"> %
                </label>
            <?php endforeach; ?>
            <button type="submit" class="adm-btn">Guardar servicios</button>
        </form>
    </section>

    <!-- ================= SHEET ================= -->
    <section id="hoja" class="adm-section">
        <h2>Hoja y montaje (mm)</h2>
        <form method="post" class="adm-card">
            <?php $fields( 'save_sheet' ); ?>
            <div class="adm-grid2">
                <?php
                $spec_labels = array(
                    'cutWidthMm'        => 'Ancho de hoja cortada',
                    'cutHeightMm'       => 'Alto de hoja cortada',
                    'printableWidthMm'  => 'Ancho imprimible',
                    'printableHeightMm' => 'Alto imprimible',
                );
                foreach ( $spec_labels as $key => $label ) :
                    ?>
                    <label class="adm-row">
                        <span><?php echo $h( $label ); ?></span>
                        <input type="number" step="1" min="1"
                            name="sheet_specs[<?php echo $h( $key ); ?>]"
                            value="<?php echo $h( (int) $sheet_specs[ $key ] ); ?>">
                    </label>
                <?php endforeach; ?>
                <label class="adm-row">
                    <span>Separación de montaje</span>
                    <input type="number" step="0.1" min="0" max="50"
                        name="imposition_gap"
                        value="<?php echo $h( $imposition_gap ); ?>">
                </label>
            </div>
            <button type="submit" class="adm-btn">Guardar hoja y montaje</button>
        </form>
    </section>

    <!-- ================= QUANTITY TIERS ================= -->
    <section id="cantidad" class="adm-section">
        <h2>Niveles de cantidad (hojas por trabajo)</h2>
        <form method="post" class="adm-card">
            <?php $fields( 'save_quantity_tiers' ); ?>
            <p class="adm-help">Las claves de nivel se mantienen fijas para no desincronizar las tablas de precios. Deje "máx" vacío para el último nivel (sin límite superior).</p>
            <div class="adm-grid-scroll">
                <table class="adm-price-table">
                    <thead><tr><th>Nivel</th><th>Mín</th><th>Máx</th></tr></thead>
                    <tbody>
                        <?php foreach ( $quantity_tiers as $tier ) : ?>
                            <tr>
                                <th><?php echo $h( sfc_tier_label( $tier['key'] ) ); ?></th>
                                <td><input type="number" step="1" min="1" name="tiers[<?php echo $h( $tier['key'] ); ?>][min]" value="<?php echo $h( (int) $tier['min'] ); ?>"></td>
                                <td><input type="number" step="1" min="1" name="tiers[<?php echo $h( $tier['key'] ); ?>][max]" value="<?php echo $h( null === $tier['max'] ? '' : (int) $tier['max'] ); ?>" placeholder="sin límite"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="adm-btn">Guardar niveles</button>
        </form>
    </section>

    <!-- ================= FULFILLMENT ================= -->
    <section id="entrega" class="adm-section">
        <h2>Entrega</h2>
        <form method="post" class="adm-card">
            <?php $fields( 'save_fulfillment' ); ?>
            <label class="adm-row">
                <span>Ciudad de la imprenta</span>
                <input type="text" name="fulfillment[shopCity]" value="<?php echo $h( $fulfillment['shopCity'] ); ?>">
            </label>
            <label class="adm-check">
                <input type="checkbox" name="fulfillment[sameDayShowOnCalculator]" value="1" <?php echo ! empty( $fulfillment['sameDayShowOnCalculator'] ) ? 'checked' : ''; ?>>
                <span>Ofrecer entrega el mismo día en la calculadora (recargo configurado en Tarifas → Tiempo de entrega)</span>
            </label>
            <button type="submit" class="adm-btn">Guardar entrega</button>
        </form>
    </section>

    <!-- ================= RESET ================= -->
    <section id="reset" class="adm-section">
        <h2>Restaurar valores por defecto</h2>
        <form method="post" class="adm-card" onsubmit="return confirm('¿Restaurar TODOS los precios y tarifas a los valores por defecto?');">
            <?php $fields( 'reset_defaults' ); ?>
            <p class="adm-help">Sobrescribe todas las tablas y tarifas con los valores originales del código.</p>
            <button type="submit" class="adm-btn adm-btn--danger">Restaurar todo</button>
        </form>
    </section>
</main>
</body>
</html>
