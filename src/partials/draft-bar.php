<?php
/**
 * Persistent draft bar: shows the current in-progress quote (item count +
 * running total) with a link to the builder. Included on the picker and
 * calculator pages. Rendered server-side from the session; refreshed by JS
 * after add/remove. Hidden when the draft is empty.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sfc_draft   = sfc_draft_summary();
$sfc_bp      = SFC_BASE_PATH;
$sfc_hidden  = $sfc_draft['count'] < 1 ? ' hidden' : '';
$sfc_total   = $sfc_draft['currency'] . ' $' . number_format( $sfc_draft['grandTotal'], 2 );
?>
<div id="sfc-draft-bar" class="draft-bar<?php echo $sfc_hidden; ?>" data-base="<?php echo esc_attr( $sfc_bp ); ?>">
    <span class="draft-bar__label">Cotización actual:</span>
    <span class="draft-bar__count" id="sfc-draft-count"><?php echo (int) $sfc_draft['count']; ?></span>
    <span class="draft-bar__items">ítem(s)</span>
    <span class="draft-bar__total" id="sfc-draft-total"><?php echo esc_html( $sfc_total ); ?></span>
    <a class="draft-bar__cta" href="<?php echo esc_attr( $sfc_bp ); ?>/">Ver cotización →</a>
</div>
