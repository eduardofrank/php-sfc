<?php
/**
 * Minimal WordPress compatibility shims.
 *
 * The Sheet Fed Calc pricing engine was written as a WordPress plugin. This
 * standalone app ports those engine files verbatim and satisfies the small set
 * of WordPress primitives they touch with the lightweight implementations below.
 * There is no database: get_option() always returns the seeded default, so the
 * default price tables / rates / specs apply exactly as shipped.
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Ported engine files guard on ABSPATH; define it so they load.
    define( 'ABSPATH', __DIR__ . '/' );
}

/**
 * WordPress error object stand-in.
 */
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        /** @var string */
        protected $code;
        /** @var string */
        protected $message;
        /** @var mixed */
        protected $data;

        public function __construct( $code = '', $message = '', $data = null ) {
            $this->code    = (string) $code;
            $this->message = (string) $message;
            $this->data    = $data;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    /**
     * Mirror of WordPress sanitize_key(): lowercase, keep a-z0-9_- only.
     */
    function sanitize_key( $key ) {
        $key = strtolower( (string) $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ) {
        return abs( (int) $maybeint );
    }
}

if ( ! function_exists( 'number_format_i18n' ) ) {
    function number_format_i18n( $number, $decimals = 0 ) {
        return number_format( (float) $number, (int) $decimals );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        $str = (string) $str;
        $str = strip_tags( $str );
        $str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
        return trim( $str );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        if ( is_array( $value ) ) {
            return array_map( 'wp_unslash', $value );
        }
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    /**
     * No filter subscribers exist here; return the value unchanged.
     */
    function apply_filters( $tag, $value = null ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action() {
        // No hook system in the standalone app.
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {
        // No hook system in the standalone app.
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action() {
        // No hook system in the standalone app.
    }
}

/* -------------------------------------------------------------------------
 * Persistent options store
 *
 * The pricing engine reads every editable value (price tables, lamination /
 * die-cut / turnaround / job-service rates, sheet specs, quantity tiers,
 * paper catalog, fulfillment) through get_option(). Instead of the DB the
 * plugin used, this app persists overrides to a JSON file
 * (data/config/options.json). Any key absent from the file falls back to the
 * code default passed by the caller, so deleting the file restores defaults.
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'sfc_options_store_file' ) ) {
    function sfc_options_store_file() {
        return SFC_APP_DIR . '/data/config/options.json';
    }
}

if ( ! function_exists( 'sfc_options_store' ) ) {
    /**
     * Load (once) and return the full options store.
     *
     * @return array<string,mixed>
     */
    function sfc_options_store() {
        if ( ! isset( $GLOBALS['__sfc_options'] ) ) {
            $file    = sfc_options_store_file();
            $decoded = is_file( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
            $GLOBALS['__sfc_options'] = is_array( $decoded ) ? $decoded : array();
        }
        return $GLOBALS['__sfc_options'];
    }
}

if ( ! function_exists( 'sfc_options_persist' ) ) {
    /**
     * Write the in-memory store to disk (atomic, pretty-printed).
     *
     * @return bool
     */
    function sfc_options_persist() {
        $file = sfc_options_store_file();
        $dir  = dirname( $file );
        if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, true ) && ! is_dir( $dir ) ) {
            return false;
        }
        $json = json_encode(
            $GLOBALS['__sfc_options'] ?? array(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $tmp = $file . '.' . getmypid() . '.tmp';
        if ( false === file_put_contents( $tmp, $json . "\n", LOCK_EX ) ) {
            return false;
        }
        return rename( $tmp, $file );
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        $store = sfc_options_store();
        return array_key_exists( $option, $store ) ? $store[ $option ] : $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value ) {
        sfc_options_store();
        $GLOBALS['__sfc_options'][ $option ] = $value;
        return sfc_options_persist();
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) {
        sfc_options_store();
        unset( $GLOBALS['__sfc_options'][ $option ] );
        return sfc_options_persist();
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        $url = (string) $url;
        $url = str_replace( array( '"', "'", '<', '>', ' ' ), array( '%22', '%27', '%3C', '%3E', '%20' ), $url );
        return $url;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text ) {
        return esc_html( $text );
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    // Public calculator; no CSRF token needed. Endpoints are same-origin, read-only quotes.
    function wp_create_nonce( $action = -1 ) {
        return '';
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    // ajaxUrl is overridden by the page renderer to point at /api/index.php.
    function admin_url( $path = '' ) {
        return '/api/index.php';
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '' ) {
        return '/' . ltrim( (string) $path, '/' );
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg() {
        return '';
    }
}
