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

if ( ! function_exists( 'get_option' ) ) {
    /**
     * No options store: always fall back to the seeded default.
     */
    function get_option( $option, $default = false ) {
        return $default;
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
