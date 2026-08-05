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
 * Persist a compound quote from a set of draft items. The number and every line
 * price are frozen at this moment.
 *
 * @param int                             $client_id Client id.
 * @param array<int,array<string,mixed>>  $items     Draft items (see src/quote-draft.php).
 * @param string                          $title     Optional quote title.
 * @param string                          $notes     Optional quote notes.
 * @return array{id:int,quoteNumber:string,shareToken:string,total:float,currency:string}
 */
function sfc_quotes_create_from_draft( $client_id, $items, $title = '', $notes = '' ) {
    if ( empty( $items ) ) {
        throw new RuntimeException( 'Cannot create a quote with no items.' );
    }

    $pdo   = sfc_db();
    $token = bin2hex( random_bytes( 12 ) );
    $total = 0.0;
    foreach ( $items as $item ) {
        $total += (float) $item['lineTotal'];
    }
    $total = round( $total, 2 );
    $curr  = (string) ( $items[0]['currency'] ?? 'USD' );

    // Freeze the issue-time VES rate (null when no rate is available yet).
    $ves_rate  = sfc_current_usd_ves_rate();
    $total_ves = null !== $ves_rate ? round( $total * $ves_rate, 2 ) : null;

    $pdo->beginTransaction();
    try {
        $number = sfc_quotes_next_number();

        $head = $pdo->prepare(
            'INSERT INTO sfc_quotes
                (quote_number, share_token, client_id, total_price, currency, title, notes, ves_rate, total_ves)
             VALUES
                (:number, :token, :client, :total, :currency, :title, :notes, :ves_rate, :total_ves)
             RETURNING id'
        );
        $head->execute(
            array(
                ':number'    => $number,
                ':token'     => $token,
                ':client'    => $client_id,
                ':total'     => number_format( $total, 2, '.', '' ),
                ':currency'  => $curr,
                ':title'     => '' === $title ? null : $title,
                ':notes'     => '' === $notes ? null : $notes,
                ':ves_rate'  => null !== $ves_rate ? number_format( $ves_rate, 4, '.', '' ) : null,
                ':total_ves' => null !== $total_ves ? number_format( $total_ves, 2, '.', '' ) : null,
            )
        );
        $quote_id = (int) $head->fetchColumn();

        $line = $pdo->prepare(
            'INSERT INTO sfc_quote_items
                (quote_id, position, product_slug, state, snapshot, line_total, label)
             VALUES
                (:quote, :pos, :slug, :state, :snapshot, :total, :label)'
        );
        foreach ( array_values( $items ) as $pos => $item ) {
            $line->execute(
                array(
                    ':quote'    => $quote_id,
                    ':pos'      => $pos,
                    ':slug'     => $item['productSlug'],
                    ':state'    => wp_json_encode_compat( $item['state'] ),
                    ':snapshot' => wp_json_encode_compat( $item['snapshot'] ),
                    ':total'    => number_format( (float) $item['lineTotal'], 2, '.', '' ),
                    ':label'    => $item['label'],
                )
            );
        }

        $pdo->commit();
    } catch ( Throwable $e ) {
        $pdo->rollBack();
        throw $e;
    }

    return array(
        'id'          => $quote_id,
        'quoteNumber' => $number,
        'shareToken'  => $token,
        'total'       => $total,
        'currency'    => $curr,
    );
}

/**
 * Re-stamp a finalized quote to a new VES rate in place — same quote number and
 * USD prices, only ves_rate / total_ves recomputed. Defaults to the current rate.
 *
 * @param int        $quote_id Quote id.
 * @param float|null $rate     Bs. per USD; null uses sfc_current_usd_ves_rate().
 * @return array{quoteNumber:string,vesRate:float,totalVes:float}|null Null if no rate.
 */
function sfc_quotes_update_rate( $quote_id, $rate = null ) {
    $rate = null !== $rate ? (float) $rate : sfc_current_usd_ves_rate();
    if ( null === $rate || $rate <= 0 ) {
        return null;
    }

    $pdo  = sfc_db();
    $stmt = $pdo->prepare(
        'UPDATE sfc_quotes
            SET ves_rate = :rate,
                total_ves = round(total_price * :rate2, 2)
          WHERE id = :id
        RETURNING quote_number, ves_rate, total_ves'
    );
    $stmt->execute(
        array(
            ':rate'  => number_format( $rate, 4, '.', '' ),
            ':rate2' => number_format( $rate, 4, '.', '' ),
            ':id'    => (int) $quote_id,
        )
    );
    $row = $stmt->fetch();
    if ( ! $row ) {
        return null;
    }

    return array(
        'quoteNumber' => $row['quote_number'],
        'vesRate'     => (float) $row['ves_rate'],
        'totalVes'    => (float) $row['total_ves'],
    );
}

/**
 * Load a quote header (with client) and its line items by public share token.
 *
 * @param string $token Share token.
 * @return array<string,mixed>|null Header fields plus 'items' => [ ... ].
 */
function sfc_quotes_get_by_token( $token ) {
    if ( ! preg_match( '/^[a-f0-9]{24}$/', (string) $token ) ) {
        return null;
    }

    $pdo  = sfc_db();
    $stmt = $pdo->prepare(
        'SELECT q.*, c.name AS client_name, c.email AS client_email
         FROM sfc_quotes q JOIN sfc_clients c ON c.id = q.client_id
         WHERE q.share_token = :token LIMIT 1'
    );
    $stmt->execute( array( ':token' => $token ) );
    $head = $stmt->fetch();
    if ( ! $head ) {
        return null;
    }

    $items_stmt = $pdo->prepare(
        'SELECT id, position, product_slug, state, snapshot, line_total, label
         FROM sfc_quote_items WHERE quote_id = :qid ORDER BY position'
    );
    $items_stmt->execute( array( ':qid' => $head['id'] ) );

    $items = array();
    foreach ( $items_stmt->fetchAll() as $row ) {
        $row['state']    = json_decode( (string) $row['state'], true ) ?: array();
        $row['snapshot'] = json_decode( (string) $row['snapshot'], true ) ?: array();
        $items[]         = $row;
    }
    $head['items'] = $items;

    return $head;
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

    $sql = 'SELECT q.id, q.quote_number, q.share_token, q.title, q.total_price,
                   q.currency, q.status, q.created_at, q.ves_rate, q.total_ves,
                   c.name AS client_name,
                   (SELECT COUNT(*) FROM sfc_quote_items i WHERE i.quote_id = q.id) AS item_count
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
