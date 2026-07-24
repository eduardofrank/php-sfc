<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$sfc_page_title = isset( $page_title ) ? $page_title : 'Sheet Fed Calc';
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo esc_html( $sfc_page_title ); ?> · Sheet Fed Calc</title>
    <link rel="stylesheet" href="<?php echo esc_attr( SFC_BASE_PATH ); ?>/assets/app.css" />
    <link rel="stylesheet" href="<?php echo esc_attr( SFC_BASE_PATH ); ?>/assets/calculator.css" />
</head>
<body class="app-body">
