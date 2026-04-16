<?php
/**
 * Tiny WordPress compatibility layer for CLI-only verification scripts.
 */

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['wp_seo_automater_test_options'] = isset( $GLOBALS['wp_seo_automater_test_options'] ) ? $GLOBALS['wp_seo_automater_test_options'] : array();

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code = $code;
			$this->message = $message;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		$url = filter_var( trim( (string) $url ), FILTER_SANITIZE_URL );

		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		return $url;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		return array_key_exists( $key, $GLOBALS['wp_seo_automater_test_options'] ) ? $GLOBALS['wp_seo_automater_test_options'][ $key ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value ) {
		$GLOBALS['wp_seo_automater_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $params, $url ) {
		$query = http_build_query( $params );
		return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return isset( $response['body'] ) ? $response['body'] : '';
	}
}

if ( ! function_exists( 'wp_seo_automater_test_http_request' ) ) {
	/**
	 * Minimal HTTP client that mimics the subset of the WP HTTP API used here.
	 *
	 * @param string $method GET or POST.
	 * @param string $url Request URL.
	 * @param array  $args Request arguments.
	 * @return array|WP_Error
	 */
	function wp_seo_automater_test_http_request( $method, $url, $args = array() ) {
		$timeout = isset( $args['timeout'] ) ? (int) $args['timeout'] : 30;
		$headers = isset( $args['headers'] ) ? $args['headers'] : array();
		$body = isset( $args['body'] ) ? $args['body'] : null;

		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init( $url );

			if ( false === $ch ) {
				return new WP_Error( 'http_error', 'Unable to initialize cURL.' );
			}

			$header_lines = array();
			foreach ( $headers as $name => $value ) {
				$header_lines[] = $name . ': ' . $value;
			}

			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

			if ( ! empty( $header_lines ) ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_lines );
			}

			if ( null !== $body ) {
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
			}

			$response_body = curl_exec( $ch );
			if ( false === $response_body ) {
				$error = curl_error( $ch );
				return new WP_Error( 'http_error', $error );
			}

			$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			return array(
				'response' => array( 'code' => $status ),
				'body'     => $response_body,
			);
		}

		$context_args = array(
			'http' => array(
				'method'        => $method,
				'timeout'       => $timeout,
				'ignore_errors' => true,
				'header'        => '',
			),
		);

		foreach ( $headers as $name => $value ) {
			$context_args['http']['header'] .= $name . ': ' . $value . "\r\n";
		}

		if ( null !== $body ) {
			$context_args['http']['content'] = $body;
		}

		$context = stream_context_create( $context_args );
		$response_body = @file_get_contents( $url, false, $context );

		if ( false === $response_body ) {
			return new WP_Error( 'http_error', 'HTTP request failed.' );
		}

		$status = 0;
		$response_headers = function_exists( 'http_get_last_response_headers' ) ? http_get_last_response_headers() : array();
		if ( ! empty( $response_headers[0] ) && preg_match( '#\s(\d{3})\s#', $response_headers[0], $matches ) ) {
			$status = (int) $matches[1];
		}

		return array(
			'response' => array( 'code' => $status ),
			'body'     => $response_body,
		);
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		return wp_seo_automater_test_http_request( 'GET', $url, $args );
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		return wp_seo_automater_test_http_request( 'POST', $url, $args );
	}
}
