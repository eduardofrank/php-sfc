<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Count units across/down on a sheet span, including inter-unit gap.
 *
 * @param float $span_mm Printable span (width or height).
 * @param float $unit_mm Unit span in the same direction.
 * @param float $gap_mm  Gap between adjacent units.
 * @return int
 */
function sfc_count_units_on_sheet_span( $span_mm, $unit_mm, $gap_mm ) {
    $span_mm = (float) $span_mm;
    $unit_mm = (float) $unit_mm;
    $gap_mm  = max( 0.0, (float) $gap_mm );

    if ( $unit_mm <= 0 || $unit_mm > $span_mm ) {
        return 0;
    }

    if ( $gap_mm <= 0 ) {
        return (int) floor( $span_mm / $unit_mm );
    }

    return max( 0, (int) floor( ( $span_mm + $gap_mm ) / ( $unit_mm + $gap_mm ) ) );
}

/**
 * Compute centered layout bounds for a cols × rows imposition grid.
 *
 * @param int        $cols             Columns on the sheet.
 * @param int        $rows             Rows on the sheet.
 * @param float      $unit_width_mm    Oriented unit width.
 * @param float      $unit_height_mm   Oriented unit height.
 * @param int        $sheet_width_mm   Printable width.
 * @param int        $sheet_height_mm  Printable height.
 * @param float|null $gap_mm           Optional gap override.
 * @return array<string,float|int>
 */
function sfc_compute_imposition_layout_metrics( $cols, $rows, $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm, $gap_mm = null ) {
    $gap_mm  = null === $gap_mm ? sfc_get_sheet_imposition_gap_mm() : max( 0.0, (float) $gap_mm );
    $cols    = max( 1, (int) $cols );
    $rows    = max( 1, (int) $rows );
    $unit_w  = (float) $unit_width_mm;
    $unit_h  = (float) $unit_height_mm;
    $sheet_w = (int) $sheet_width_mm;
    $sheet_h = (int) $sheet_height_mm;

    $layout_width  = ( $cols * $unit_w ) + ( max( 0, $cols - 1 ) * $gap_mm );
    $layout_height = ( $rows * $unit_h ) + ( max( 0, $rows - 1 ) * $gap_mm );

    return array(
        'gapMm'              => $gap_mm,
        'layoutWidthMm'      => $layout_width,
        'layoutHeightMm'     => $layout_height,
        'layoutOffsetLeftMm' => ( $sheet_w - $layout_width ) / 2,
        'layoutOffsetTopMm'  => ( $sheet_h - $layout_height ) / 2,
    );
}

/**
 * Calculate maximum units that fit on one printable sheet.
 *
 * @param float $unit_width_mm  Unit width in millimeters.
 * @param float $unit_height_mm Unit height in millimeters.
 * @param int   $sheet_width_mm Printable width.
 * @param int   $sheet_height_mm Printable height.
 * @return int
 */
function sfc_max_units_per_sheet( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm ) {
    $unit_width_mm  = (float) $unit_width_mm;
    $unit_height_mm = (float) $unit_height_mm;
    $sheet_width_mm = (int) $sheet_width_mm;
    $sheet_height_mm = (int) $sheet_height_mm;

    if ( $unit_width_mm <= 0 || $unit_height_mm <= 0 ) {
        return 0;
    }

    $normal = sfc_units_per_sheet_orientation( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm );
    $rotated = sfc_units_per_sheet_orientation( $unit_height_mm, $unit_width_mm, $sheet_width_mm, $sheet_height_mm );

    return max( $normal, $rotated );
}

/**
 * Units per sheet for a fixed orientation.
 *
 * @param float $unit_width_mm  Oriented unit width.
 * @param float $unit_height_mm Oriented unit height.
 * @param int   $sheet_width_mm Printable width.
 * @param int   $sheet_height_mm Printable height.
 * @return int
 */
function sfc_units_per_sheet_orientation( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm ) {
    $gap_mm = sfc_get_sheet_imposition_gap_mm();
    $across = sfc_count_units_on_sheet_span( $sheet_width_mm, $unit_width_mm, $gap_mm );
    $down   = sfc_count_units_on_sheet_span( $sheet_height_mm, $unit_height_mm, $gap_mm );

    return max( 0, $across * $down );
}

/**
 * Build imposition data for a product size and unit quantity.
 *
 * @param float              $unit_width_mm  Unit width in millimeters.
 * @param float              $unit_height_mm Unit height in millimeters.
 * @param int                $unit_quantity  Ordered unit quantity.
 * @param array<string,mixed> $options       Optional overrides: unitsPerSheet, impositionCols, impositionRows.
 * @return array<string,mixed>|WP_Error
 */
function sfc_calculate_sheet_imposition( $unit_width_mm, $unit_height_mm, $unit_quantity, $options = array() ) {
    $specs           = sfc_get_sheet_specs();
    $sheet_width_mm  = (int) $specs['printableWidthMm'];
    $sheet_height_mm = (int) $specs['printableHeightMm'];
    $unit_quantity   = absint( $unit_quantity );
    $options         = is_array( $options ) ? $options : array();

    if ( $unit_quantity <= 0 ) {
        return new WP_Error( 'invalid_quantity', 'Quantity must be at least 1.' );
    }

    $units_per_sheet_override = isset( $options['unitsPerSheet'] ) ? absint( $options['unitsPerSheet'] ) : 0;
    $oriented                 = sfc_best_sheet_orientation( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm );

    if ( $units_per_sheet_override > 0 ) {
        $units_per_sheet = $units_per_sheet_override;
        if ( ! empty( $options['impositionCols'] ) && ! empty( $options['impositionRows'] ) ) {
            $cols = max( 1, (int) $options['impositionCols'] );
            $rows = max( 1, (int) $options['impositionRows'] );
            // Fixed grid uses product dimensions as given; do not auto-rotate.
            $oriented['unitWidthMm']    = (float) $unit_width_mm;
            $oriented['unitHeightMm']   = (float) $unit_height_mm;
            $oriented['cols']           = $cols;
            $oriented['rows']           = $rows;
            $oriented['blockWidthPct']  = ( (float) $unit_width_mm / $sheet_width_mm ) * 100;
            $oriented['blockHeightPct'] = ( (float) $unit_height_mm / $sheet_height_mm ) * 100;
        }
    } else {
        $units_per_sheet = sfc_max_units_per_sheet( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm );
    }

    if ( $units_per_sheet <= 0 ) {
        return new WP_Error( 'does_not_fit', 'Selected size does not fit on the printable sheet.' );
    }

    $sheets_needed       = (int) ceil( $unit_quantity / $units_per_sheet );
    $units_on_last_sheet = $unit_quantity - ( ( $sheets_needed - 1 ) * $units_per_sheet );
    $layout              = sfc_compute_imposition_layout_metrics(
        (int) $oriented['cols'],
        (int) $oriented['rows'],
        (float) $oriented['unitWidthMm'],
        (float) $oriented['unitHeightMm'],
        $sheet_width_mm,
        $sheet_height_mm
    );
    $max_units_per_sheet = sfc_max_units_per_sheet( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm );

    $result = array_merge(
        array(
            'printableWidthMm'   => $sheet_width_mm,
            'printableHeightMm'  => $sheet_height_mm,
            'unitWidthMm'        => (float) $oriented['unitWidthMm'],
            'unitHeightMm'       => (float) $oriented['unitHeightMm'],
            'unitsPerSheet'      => $units_per_sheet,
            'maxUnitsPerSheet'   => $max_units_per_sheet,
            'sheetQuantity'      => $sheets_needed,
            'unitQuantity'       => $unit_quantity,
            'unitsOnLastSheet'   => $units_on_last_sheet,
            'cols'               => (int) $oriented['cols'],
            'rows'               => (int) $oriented['rows'],
            'blockWidthPct'      => (float) $oriented['blockWidthPct'],
            'blockHeightPct'     => (float) $oriented['blockHeightPct'],
        ),
        $layout
    );

    $result['waste']    = sfc_calculate_imposition_waste_stats( $result );
    $result['warnings'] = sfc_build_imposition_warnings( $result, $unit_width_mm, $unit_height_mm );

    return $result;
}

/**
 * Pick the best orientation for sheet imposition.
 *
 * @param float $unit_width_mm  Unit width.
 * @param float $unit_height_mm Unit height.
 * @param int   $sheet_width_mm Printable width.
 * @param int   $sheet_height_mm Printable height.
 * @return array<string,float|int>
 */
function sfc_best_sheet_orientation( $unit_width_mm, $unit_height_mm, $sheet_width_mm, $sheet_height_mm ) {
    $gap_mm     = sfc_get_sheet_imposition_gap_mm();
    $candidates = array();

    foreach ( array( false, true ) as $rotated ) {
        $w = $rotated ? (float) $unit_height_mm : (float) $unit_width_mm;
        $h = $rotated ? (float) $unit_width_mm : (float) $unit_height_mm;
        $count = sfc_units_per_sheet_orientation( $w, $h, $sheet_width_mm, $sheet_height_mm );
        if ( $count <= 0 ) {
            continue;
        }

        $cols = sfc_count_units_on_sheet_span( $sheet_width_mm, $w, $gap_mm );
        $rows = sfc_count_units_on_sheet_span( $sheet_height_mm, $h, $gap_mm );
        $candidates[] = array(
            'unitWidthMm'    => $w,
            'unitHeightMm'   => $h,
            'cols'           => max( 1, $cols ),
            'rows'           => max( 1, $rows ),
            'count'          => $count,
            'blockWidthPct'  => ( $w / $sheet_width_mm ) * 100,
            'blockHeightPct' => ( $h / $sheet_height_mm ) * 100,
        );
    }

    usort(
        $candidates,
        static function ( $a, $b ) {
            return $b['count'] <=> $a['count'];
        }
    );

    return $candidates[0] ?? array(
        'unitWidthMm'    => (float) $unit_width_mm,
        'unitHeightMm'   => (float) $unit_height_mm,
        'cols'           => 1,
        'rows'           => 1,
        'blockWidthPct'  => ( (float) $unit_width_mm / $sheet_width_mm ) * 100,
        'blockHeightPct' => ( (float) $unit_height_mm / $sheet_height_mm ) * 100,
    );
}

/**
 * Build row/slot visualization data for the sheet layout UI.
 *
 * Blocks are positioned from mm dimensions with a fixed inter-unit gap; the
 * full group is centered in the printable area and unused margins stay visible.
 *
 * @param array<string,mixed> $imposition Imposition result.
 * @return array<string,mixed>
 */
function sfc_build_sheet_layout_viz( $imposition ) {
    $rows = array();
    $full_rows = (int) floor( $imposition['unitQuantity'] / $imposition['unitsPerSheet'] );
    $remainder = (int) ( $imposition['unitQuantity'] % $imposition['unitsPerSheet'] );

    for ( $i = 0; $i < $full_rows; $i++ ) {
        $rows[] = array(
            'slotCount'   => (int) $imposition['unitsPerSheet'],
            'filledCount' => (int) $imposition['unitsPerSheet'],
            'isLastRow'   => false,
        );
    }

    if ( $remainder > 0 ) {
        $rows[] = array(
            'slotCount'   => (int) $imposition['unitsPerSheet'],
            'filledCount' => $remainder,
            'isLastRow'   => true,
        );
    } elseif ( ! empty( $rows ) ) {
        $rows[ count( $rows ) - 1 ]['isLastRow'] = true;
    } else {
        $rows[] = array(
            'slotCount'   => (int) $imposition['unitsPerSheet'],
            'filledCount' => (int) $imposition['unitQuantity'],
            'isLastRow'   => true,
        );
    }

    return array(
        'rows'               => $rows,
        'cols'               => (int) $imposition['cols'],
        'rowsPerSheet'       => (int) $imposition['rows'],
        'blockWidthPct'      => (float) $imposition['blockWidthPct'],
        'blockHeightPct'     => (float) $imposition['blockHeightPct'],
        'unitsPerSheet'      => (int) $imposition['unitsPerSheet'],
        'sheetQuantity'      => (int) $imposition['sheetQuantity'],
        'printableWidthMm'   => (int) $imposition['printableWidthMm'],
        'printableHeightMm'  => (int) $imposition['printableHeightMm'],
        'unitWidthMm'        => (float) $imposition['unitWidthMm'],
        'unitHeightMm'       => (float) $imposition['unitHeightMm'],
        'gapMm'              => (float) ( $imposition['gapMm'] ?? sfc_get_sheet_imposition_gap_mm() ),
        'layoutWidthMm'      => (float) ( $imposition['layoutWidthMm'] ?? 0 ),
        'layoutHeightMm'     => (float) ( $imposition['layoutHeightMm'] ?? 0 ),
        'layoutOffsetLeftMm' => (float) ( $imposition['layoutOffsetLeftMm'] ?? 0 ),
        'layoutOffsetTopMm'  => (float) ( $imposition['layoutOffsetTopMm'] ?? 0 ),
        'warnings'           => (array) ( $imposition['warnings'] ?? array() ),
    );
}

/**
 * Calculate wasted unit slots for a job at a given units-per-sheet density.
 *
 * @param int $units_per_sheet Units imposed on each press sheet.
 * @param int $unit_quantity   Ordered unit quantity.
 * @return int
 */
function sfc_calculate_imposition_job_waste( $units_per_sheet, $unit_quantity ) {
    $units_per_sheet = max( 1, (int) $units_per_sheet );
    $unit_quantity   = max( 0, (int) $unit_quantity );
    $sheet_quantity  = (int) ceil( $unit_quantity / $units_per_sheet );

    return max( 0, ( $sheet_quantity * $units_per_sheet ) - $unit_quantity );
}

/**
 * Build waste statistics for an imposition result.
 *
 * @param array<string,mixed> $imposition Imposition result.
 * @return array<string,int>
 */
function sfc_calculate_imposition_waste_stats( $imposition ) {
    $units_per_sheet    = (int) ( $imposition['unitsPerSheet'] ?? 0 );
    $unit_quantity      = (int) ( $imposition['unitQuantity'] ?? 0 );
    $units_on_last      = (int) ( $imposition['unitsOnLastSheet'] ?? 0 );
    $job_waste          = sfc_calculate_imposition_job_waste( $units_per_sheet, $unit_quantity );
    $last_sheet_waste   = max( 0, $units_per_sheet - $units_on_last );
    $max_units_per_sheet = (int) ( $imposition['maxUnitsPerSheet'] ?? 0 );
    $max_density_waste  = $max_units_per_sheet > 0
        ? sfc_calculate_imposition_job_waste( $max_units_per_sheet, $unit_quantity )
        : 0;

    return array(
        'jobWasteUnits'        => $job_waste,
        'lastSheetWasteUnits'  => $last_sheet_waste,
        'maxDensityWasteUnits' => $max_density_waste,
    );
}

/**
 * Imposition warnings that only apply to flat copy-count products (flyers, cards, etc.).
 *
 * Booklet quotes impose inner/cover page faces, so copy-quantity waste hints are misleading.
 *
 * @return string[]
 */
function sfc_get_flat_product_imposition_warning_codes() {
    return array( 'job_waste', 'denser_grid_reduces_waste' );
}

/**
 * Remove flat-product waste warnings from a warning list.
 *
 * @param array<int,array<string,mixed>> $warnings Warning payloads.
 * @return array<int,array<string,mixed>>
 */
function sfc_strip_flat_product_imposition_warnings( $warnings ) {
    if ( ! is_array( $warnings ) ) {
        return array();
    }

    $skip = array_flip( sfc_get_flat_product_imposition_warning_codes() );
    $kept = array();

    foreach ( $warnings as $warning ) {
        if ( ! is_array( $warning ) ) {
            continue;
        }

        if ( isset( $skip[ $warning['code'] ?? '' ] ) ) {
            continue;
        }

        $kept[] = $warning;
    }

    return $kept;
}

/**
 * Build imposition warnings for inefficient paper use or layout issues.
 *
 * @param array<string,mixed> $imposition       Imposition result.
 * @param float               $unit_width_mm    Native unit width.
 * @param float               $unit_height_mm   Native unit height.
 * @return array<int,array<string,mixed>>
 */
function sfc_build_imposition_warnings( $imposition, $unit_width_mm, $unit_height_mm ) {
    $warnings            = array();
    $sheet_width_mm      = (int) ( $imposition['printableWidthMm'] ?? 0 );
    $sheet_height_mm     = (int) ( $imposition['printableHeightMm'] ?? 0 );
    $units_per_sheet     = (int) ( $imposition['unitsPerSheet'] ?? 0 );
    $max_units_per_sheet = (int) ( $imposition['maxUnitsPerSheet'] ?? 0 );
    $unit_quantity       = (int) ( $imposition['unitQuantity'] ?? 0 );
    $waste               = sfc_calculate_imposition_waste_stats( $imposition );

    if ( (float) ( $imposition['layoutWidthMm'] ?? 0 ) > $sheet_width_mm
        || (float) ( $imposition['layoutHeightMm'] ?? 0 ) > $sheet_height_mm ) {
        $warnings[] = array(
            'code'     => 'layout_overflow',
            'severity' => 'warning',
            'message'  => 'El grupo de montaje excede el área imprimible.',
        );
    }

    if ( $waste['jobWasteUnits'] > 0 ) {
        $warnings[] = array(
            'code'       => 'job_waste',
            'severity'   => 'warning',
            'message'    => sprintf(
                'Este pedido deja %1$d unidades sin usar en la última hoja o en el total del trabajo. Agregue %1$d unidades a la cantidad a imprimir para optimizar el uso del papel sin elevar el precio.',
                $waste['jobWasteUnits']
            ),
            'wasteUnits' => $waste['jobWasteUnits'],
        );
    }

    if ( $max_units_per_sheet > $units_per_sheet
        && $waste['maxDensityWasteUnits'] < $waste['jobWasteUnits'] ) {
        $warnings[] = array(
            'code'                => 'denser_grid_reduces_waste',
            'severity'            => 'info',
            'message'             => sprintf(
                'Un montaje de %d por hoja dejaría %d unidades sin usar frente a %d con %d por hoja.',
                $max_units_per_sheet,
                $waste['maxDensityWasteUnits'],
                $waste['jobWasteUnits'],
                $units_per_sheet
            ),
            'maxUnitsPerSheet'    => $max_units_per_sheet,
            'chosenUnitsPerSheet' => $units_per_sheet,
            'wasteWithMaxDensity' => $waste['maxDensityWasteUnits'],
            'wasteWithChosen'     => $waste['jobWasteUnits'],
        );
    }

    return $warnings;
}
