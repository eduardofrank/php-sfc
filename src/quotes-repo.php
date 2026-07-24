<?php
/**
 * Data access for clients and quotes (PostgreSQL).
 *
 * A saved quote is a first-class, durable record: a client, a human-friendly
 * quote number (YYYY-NNNN), the calculator state (for reopening), and a frozen
 * snapshot of the priced result (so the number represents a fixed, dated price).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Find a client by name (case-insensitive) or create one. Optional email/phone
 * are filled in when the client is first created.
 *
 * @param string $name  Client name (required, trimmed).
 * @param string $email Optional email.
 * @param string $phone Optional phone.
 * @return int Client id.
 */
function sfc_quotes_find_or_create_client( $name, $email = '', $phone = '' ) {
    $name  = trim( (string) $name );
    $email = trim( (string) $email );
    $phone = trim( (string) $phone );
    $pdo   = sfc_db();

    $find = $pdo->prepare( 'SELECT id FROM sfc_clients WHERE lower(name) = lower(:name) LIMIT 1' );
    $find->execute( array( ':name' => $name ) );
    $id = $find->fetchColumn();
    if ( false !== $id ) {
        return (int) $id;
    }

    $insert = $pdo->prepare(
        'INSERT INTO sfc_clients (name, email, phone) VALUES (:name, :email, :phone)
         ON CONFLICT (lower(name)) DO UPDATE SET name = EXCLUDED.name
         RETURNING id'
    );
    $insert->execute(
        array(
            ':name'  => $name,
            ':email' => '' === $email ? null : $email,
            ':phone' => '' === $phone ? null : $phone,
        )
    );

    return (int) $insert->fetchColumn();
}

/**
 * Allocate the next quote number for the current year (race-safe).
 *
 * @return string e.g. "2026-0001".
 */
function sfc_quotes_next_number() {
    $year = (int) gmdate( 'Y' );
    $stmt = sfc_db()->prepare(
        'INSERT INTO sfc_quote_counters (year, last_seq) VALUES (:y, 1)
         ON CONFLICT (year) DO UPDATE SET last_seq = sfc_quote_counters.last_seq + 1
         RETURNING last_seq'
    );
    $stmt->execute( array( ':y' => $year ) );
    $seq = (int) $stmt->fetchColumn();

    return sprintf( '%d-%04d', $year, $seq );
}

/**
 * Persist a priced quote. The number and price are frozen at this moment.
 *
 * @param string              $slug         Product slug.
 * @param array<string,mixed> $state        Normalized calculator state.
 * @param array<string,mixed> $quote_result Full result from sfc_calculate_product_quote().
 * @param int                 $client_id    Client id.
 * @return array{id:int,quoteNumber:string,shareToken:string,total:float,currency:string}
 */
function sfc_quotes_create( $slug, $state, $quote_result, $client_id ) {
    $pdo   = sfc_db();
    $token = bin2hex( random_bytes( 12 ) );
    $total = (float) ( $quote_result['totalPrice'] ?? 0 );
    $curr  = (string) ( $quote_result['currency'] ?? 'USD' );

    $pdo->beginTransaction();
    try {
        $number = sfc_quotes_next_number();
        $stmt   = $pdo->prepare(
            'INSERT INTO sfc_quotes
                (quote_number, share_token, client_id, product_slug, state, snapshot, total_price, currency)
             VALUES
                (:number, :token, :client, :slug, :state, :snapshot, :total, :currency)
             RETURNING id'
        );
        $stmt->execute(
            array(
                ':number'   => $number,
                ':token'    => $token,
                ':client'   => $client_id,
                ':slug'     => $slug,
                ':state'    => wp_json_encode_compat( $state ),
                ':snapshot' => wp_json_encode_compat( $quote_result ),
                ':total'    => number_format( $total, 2, '.', '' ),
                ':currency' => $curr,
            )
        );
        $id = (int) $stmt->fetchColumn();
        $pdo->commit();
    } catch ( Throwable $e ) {
        $pdo->rollBack();
        throw $e;
    }

    return array(
        'id'          => $id,
        'quoteNumber' => $number,
        'shareToken'  => $token,
        'total'       => $total,
        'currency'    => $curr,
    );
}

/**
 * Load a quote by its public share token, with client fields joined.
 *
 * @param string $token Share token.
 * @return array<string,mixed>|null
 */
function sfc_quotes_get_by_token( $token ) {
    if ( ! preg_match( '/^[a-f0-9]{24}$/', (string) $token ) ) {
        return null;
    }

    $stmt = sfc_db()->prepare(
        'SELECT q.*, c.name AS client_name, c.email AS client_email
         FROM sfc_quotes q JOIN sfc_clients c ON c.id = q.client_id
         WHERE q.share_token = :token LIMIT 1'
    );
    $stmt->execute( array( ':token' => $token ) );
    $row = $stmt->fetch();
    if ( ! $row ) {
        return null;
    }

    $row['state']    = json_decode( (string) $row['state'], true ) ?: array();
    $row['snapshot'] = json_decode( (string) $row['snapshot'], true ) ?: array();
    return $row;
}

/**
 * Build a WHERE clause + params from admin browser filters.
 *
 * @param array<string,mixed> $filters { search?:string, product?:string }
 * @return array{0:string,1:array<string,mixed>}
 */
function sfc_quotes_filter_sql( $filters ) {
    $where  = array();
    $params = array();

    $search = trim( (string) ( $filters['search'] ?? '' ) );
    if ( '' !== $search ) {
        $where[]           = '(q.quote_number ILIKE :search OR c.name ILIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $product = trim( (string) ( $filters['product'] ?? '' ) );
    if ( '' !== $product ) {
        $where[]            = 'q.product_slug = :product';
        $params[':product'] = $product;
    }

    $sql = $where ? ( ' WHERE ' . implode( ' AND ', $where ) ) : '';
    return array( $sql, $params );
}

/**
 * List quotes for the admin browser (newest first).
 *
 * @param array<string,mixed> $filters See sfc_quotes_filter_sql().
 * @param int                 $limit   Page size.
 * @param int                 $offset  Row offset.
 * @return array<int,array<string,mixed>>
 */
function sfc_quotes_list( $filters = array(), $limit = 25, $offset = 0 ) {
    list( $where, $params ) = sfc_quotes_filter_sql( $filters );

    $sql = 'SELECT q.id, q.quote_number, q.share_token, q.product_slug, q.total_price,
                   q.currency, q.status, q.created_at, c.name AS client_name
            FROM sfc_quotes q JOIN sfc_clients c ON c.id = q.client_id'
        . $where
        . ' ORDER BY q.created_at DESC LIMIT :limit OFFSET :offset';

    $stmt = sfc_db()->prepare( $sql );
    foreach ( $params as $k => $v ) {
        $stmt->bindValue( $k, $v );
    }
    $stmt->bindValue( ':limit', max( 1, (int) $limit ), PDO::PARAM_INT );
    $stmt->bindValue( ':offset', max( 0, (int) $offset ), PDO::PARAM_INT );
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Count quotes matching the given filters.
 *
 * @param array<string,mixed> $filters See sfc_quotes_filter_sql().
 * @return int
 */
function sfc_quotes_count( $filters = array() ) {
    list( $where, $params ) = sfc_quotes_filter_sql( $filters );
    $stmt = sfc_db()->prepare(
        'SELECT COUNT(*) FROM sfc_quotes q JOIN sfc_clients c ON c.id = q.client_id' . $where
    );
    $stmt->execute( $params );
    return (int) $stmt->fetchColumn();
}

/**
 * Delete a quote by id.
 *
 * @param int $id Quote id.
 * @return bool
 */
function sfc_quotes_delete( $id ) {
    $stmt = sfc_db()->prepare( 'DELETE FROM sfc_quotes WHERE id = :id' );
    $stmt->execute( array( ':id' => (int) $id ) );
    return $stmt->rowCount() > 0;
}

/**
 * Distinct client names for the save-form datalist (optionally prefix-filtered).
 *
 * @param string $prefix Optional name prefix.
 * @param int    $limit  Max names.
 * @return string[]
 */
function sfc_client_names( $prefix = '', $limit = 200 ) {
    $prefix = trim( (string) $prefix );
    if ( '' !== $prefix ) {
        $stmt = sfc_db()->prepare(
            'SELECT name FROM sfc_clients WHERE name ILIKE :p ORDER BY name LIMIT :lim'
        );
        $stmt->bindValue( ':p', $prefix . '%' );
    } else {
        $stmt = sfc_db()->prepare( 'SELECT name FROM sfc_clients ORDER BY name LIMIT :lim' );
    }
    $stmt->bindValue( ':lim', max( 1, (int) $limit ), PDO::PARAM_INT );
    $stmt->execute();

    return $stmt->fetchAll( PDO::FETCH_COLUMN );
}
