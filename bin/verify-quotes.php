<?php
/**
 * Re-price the README's "Verified quotes (defaults)" table against the engine.
 *
 *   php bin/verify-quotes.php          (with DDEV:  ddev exec php public/bin/verify-quotes.php)
 *   php bin/verify-quotes.php --rows   (print the table rows, to paste into README.md)
 *
 * Exits non-zero if any total has drifted, so it can be wired into a check.
 *
 * Totals come from whatever get_option() resolves, i.e. data/config/options.json
 * on the host it runs on. Against the committed seed it verifies the README;
 * run on the server and it instead reports the live prices, which are expected
 * to differ — the README documents a fresh install. See "Maintaining prices".
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';

if ( PHP_SAPI !== 'cli' ) {
    http_response_code( 403 );
    exit( "CLI only.\n" );
}

/**
 * The README table, one entry per row: the config column, the state the
 * calculator would POST, and the total the README claims.
 */
$cases = array(
    'Business cards'   => array(
        'config' => '90×50, ×100, 4x0, matte laminate',
        'slug'   => 'tarjetas-de-presentacion',
        'state'  => array(
            'size'       => '90x50',
            'quantity'   => 100,
            'surface'    => 'matte',
            'printMode'  => '4x0',
            'finish'     => 'matte_laminate',
            'turnaround' => 'next_day',
        ),
        'total'  => 17.26,
    ),
    'Posters'          => array(
        'config' => '450×310, ×5, 150 g',
        'slug'   => 'posters',
        'state'  => array(
            'size'       => '450x310',
            'quantity'   => 5,
            'paper'      => 'gsm150',
            'surface'    => 'matte',
            'finish'     => 'none',
            'printMode'  => '4x0',
            'turnaround' => 'next_day',
        ),
        'total'  => 14.85,
    ),
    'Letterhead'       => array(
        'config' => 'carta, ×100',
        'slug'   => 'hojas-membretadas',
        'state'  => array(
            'size'       => 'carta',
            'quantity'   => 100,
            'turnaround' => 'next_day',
        ),
        'total'  => 142.45,
    ),
    'Album'            => array(
        'config' => '215.9×279.4, ×2, 20 pp, 150 g',
        'slug'   => 'albumes',
        'state'  => array(
            'size'             => '215.9x279.4',
            'quantity'         => 2,
            'pages'            => 20,
            'paper'            => 'gsm150',
            'surface'          => 'matte',
            'hardcover_finish' => 'matte',
            'turnaround'       => 'next_day',
        ),
        'total'  => 85.86,
    ),
    'Catalog'          => array(
        'config' => '215.9×139.7, ×10, 8 inner pp, bond inner, 300 g cover',
        'slug'   => 'catalogos-y-revistas',
        'state'  => array(
            'size'             => '215.9x139.7',
            'quantity'         => 10,
            'innerPages'       => 8,
            'innerPaper'       => 'bond',
            'coverWeight'      => 'gsm300',
            'coverSurface'     => 'matte',
            'coverPrintMode'   => '4x0',
            'coverFinish'      => 'none',
            'coverFinishSides' => 'external',
            'turnaround'       => 'next_day',
        ),
        'total'  => 54.23,
    ),
    'Die-cut stickers' => array(
        'config' => 'Ø80, ×100, lithosticker',
        'slug'   => 'stickers-y-etiquetas',
        'state'  => array(
            'die_cut_shape'  => 'circular',
            'size'           => 'circular',
            'diameterMm'     => 80,
            'customWidthMm'  => 80,
            'customLengthMm' => 80,
            'quantity'       => 100,
            'paper'          => 'lithosticker',
            'turnaround'     => 'next_day',
        ),
        'total'  => 25.81,
    ),
);

/**
 * Short labels for the job services, matching the README's breakdown wording.
 */
$service_labels = array(
    'cutting'     => 'cut',
    'creasing'    => 'crease',
    'stapling'    => 'staple',
    'die_cutting' => 'die-cut',
);

/**
 * Format a rate for prose: whole dollars lose the ".00" ($25/album, not $25.00).
 *
 * @param float $amount Amount to format.
 * @return string
 */
function sfc_verify_money( $amount ) {
    $amount = (float) $amount;
    return number_format( $amount, ( round( $amount, 2 ) === round( $amount ) ) ? 0 : 2 );
}

/**
 * Compose the README's parenthetical breakdown from a quote's pricing array.
 *
 * The bare print cost is whichever base the engine billed the extras against
 * (album's printTotalPrice already folds job services in, so it cannot be it).
 * Returns null when the parts do not sum to the total, which means this
 * function is missing a component rather than the total being wrong.
 *
 * @param array<string,mixed> $pricing Quote pricing array.
 * @param float               $total   Quote total, for the sum check.
 * @return string|null Breakdown text, or null if the parts do not add up.
 */
function sfc_verify_breakdown( $pricing, $total ) {
    global $service_labels;

    $print = $pricing['jobServicesBaseAmount']
        ?? $pricing['dieCutBaseAmount']
        ?? $pricing['printTotalPrice']
        ?? $total;

    $parts = array( sprintf( 'print $%s', number_format( $print, 2 ) ) );
    $sum   = round( (float) $print, 2 );

    $extras = array();
    foreach ( $pricing['jobServicesBreakdown'] ?? array() as $service => $line ) {
        $extras[ $service_labels[ $service ] ?? $service ] = (float) ( $line['amount'] ?? 0 );
    }
    $extras['die-cut']   = (float) ( $pricing['dieCutAmount'] ?? 0 );
    $extras['laminate']  = (float) ( $pricing['laminationAmount'] ?? 0 );
    $extras['binding']   = (float) ( $pricing['hardcoverAmount'] ?? 0 );

    foreach ( $extras as $label => $amount ) {
        if ( $amount <= 0 ) {
            continue;
        }
        $note = ( 'binding' === $label && ! empty( $pricing['hardcoverPricePerAlbum'] ) )
            ? sprintf( ' at $%s/album', sfc_verify_money( $pricing['hardcoverPricePerAlbum'] ) )
            : '';
        $parts[] = sprintf( '%s $%s%s', $label, number_format( $amount, 2 ), $note );
        $sum    += round( $amount, 2 );
    }

    if ( round( $sum, 2 ) !== round( (float) $total, 2 ) ) {
        return null;
    }

    return implode( ' + ', $parts );
}

$show_rows = in_array( '--rows', array_slice( $argv, 1 ), true );
$failed    = 0;
$rows      = array();

foreach ( $cases as $name => $case ) {
    $quote = sfc_calculate_product_quote( $case['slug'], $case['state'] );

    if ( is_wp_error( $quote ) ) {
        printf( "FAIL  %-17s %s\n", $name, $quote->get_error_message() );
        $failed++;
        continue;
    }

    $total     = (float) $quote['totalPrice'];
    $expected  = (float) $case['total'];
    $breakdown = sfc_verify_breakdown( $quote['pricing'], $total );
    $drifted   = round( $total, 2 ) !== round( $expected, 2 );

    printf(
        "%s  %-17s README $%-8s engine $%s%s\n",
        $drifted ? 'FAIL' : 'ok  ',
        $name,
        number_format( $expected, 2 ),
        number_format( $total, 2 ),
        null === $breakdown ? '  (breakdown incomplete — see sfc_verify_breakdown())' : ''
    );

    if ( $drifted ) {
        $failed++;
    }

    $rows[] = sprintf(
        '| %s | %s | $%s%s |',
        $name,
        $case['config'],
        number_format( $total, 2 ),
        null === $breakdown ? '' : " ({$breakdown})"
    );
}

if ( $show_rows ) {
    echo "\n| Product | Config | Total |\n|---------|--------|-------|\n";
    echo implode( "\n", $rows ) . "\n";
}

if ( $failed ) {
    printf( "\n%d row(s) drifted. Re-run with --rows and update README.md.\n", $failed );
    exit( 1 );
}

echo "\nAll " . count( $cases ) . " rows match README.md.\n";
