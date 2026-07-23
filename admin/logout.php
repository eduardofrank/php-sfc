<?php
require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once SFC_APP_DIR . '/src/admin/auth.php';

sfc_admin_logout();
header( 'Location: login.php' );
exit;
