<?php
/**
 * Create (or update) the quote-tracking schema. Idempotent — safe to re-run and
 * to run on every deploy.
 *
 *   php bin/db-migrate.php          (with DDEV:  ddev exec php public/bin/db-migrate.php)
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';

if ( PHP_SAPI !== 'cli' ) {
    http_response_code( 403 );
    exit( "CLI only.\n" );
}

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS sfc_clients (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       TEXT NOT NULL,
    email      TEXT,
    phone      TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS sfc_clients_name_lower ON sfc_clients (lower(name));

CREATE TABLE IF NOT EXISTS sfc_quotes (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    quote_number TEXT NOT NULL UNIQUE,
    share_token  TEXT NOT NULL UNIQUE,
    client_id    BIGINT NOT NULL REFERENCES sfc_clients(id),
    product_slug TEXT NOT NULL,
    state        JSONB NOT NULL,
    snapshot     JSONB NOT NULL,
    total_price  NUMERIC(12,2) NOT NULL,
    currency     TEXT NOT NULL DEFAULT 'USD',
    status       TEXT NOT NULL DEFAULT 'saved',
    priced_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS sfc_quotes_client  ON sfc_quotes (client_id);
CREATE INDEX IF NOT EXISTS sfc_quotes_created ON sfc_quotes (created_at DESC);

CREATE TABLE IF NOT EXISTS sfc_quote_counters (
    year     INT PRIMARY KEY,
    last_seq INT NOT NULL
);
SQL;

try {
    $pdo = sfc_db();
    $pdo->exec( $sql );
    echo "Schema is up to date.\n";
    $tables = $pdo->query(
        "SELECT tablename FROM pg_tables WHERE tablename LIKE 'sfc\\_%' ORDER BY tablename"
    )->fetchAll( PDO::FETCH_COLUMN );
    echo 'Tables: ' . implode( ', ', $tables ) . "\n";
} catch ( Throwable $e ) {
    fwrite( STDERR, 'Migration failed: ' . $e->getMessage() . "\n" );
    exit( 1 );
}
