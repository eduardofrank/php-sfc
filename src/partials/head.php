<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$sfc_page_title = isset( $page_title ) ? $page_title : 'Lab Gráfico';
$sfc_body_class = 'app-body' . ( isset( $body_class ) && $body_class ? ' ' . $body_class : '' );
$sfc_page_styles = isset( $page_styles ) && is_array( $page_styles ) ? $page_styles : array();
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo esc_html( $sfc_page_title ); ?> · Lab Gráfico</title>
    <link rel="stylesheet" href="<?php echo esc_attr( SFC_BASE_PATH ); ?>/assets/app.css" />
    <link rel="stylesheet" href="<?php echo esc_attr( SFC_BASE_PATH ); ?>/assets/calculator.css" />
    <?php foreach ( $sfc_page_styles as $sfc_style ) : ?>
    <link rel="stylesheet" href="<?php echo esc_attr( SFC_BASE_PATH . '/assets/' . $sfc_style ); ?>" />
    <?php endforeach; ?>
</head>
<body class="<?php echo esc_attr( $sfc_body_class ); ?>">

