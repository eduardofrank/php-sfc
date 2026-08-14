<?php
/**
 * Moved. The quote browser is now the public /quotes.php (out of the admin
 * realm — viewing and rate updates need no password; delete stays gated). This
 * stub redirects old links/bookmarks.
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';

header( 'Location: ' . SFC_BASE_PATH . '/quotes.php', true, 302 );
exit;
