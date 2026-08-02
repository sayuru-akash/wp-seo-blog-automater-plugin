<?php
/**
 * Minimal .env loader for local CLI verification scripts.
 */

if ( ! function_exists( 'wp_seo_automater_load_env' ) ) {
	/**
	 * Load key=value pairs from a local .env file.
	 *
	 * @param string $path Absolute path to the env file.
	 * @return array<string, string>
	 */
	function wp_seo_automater_load_env( $path ) {
		$values = array();

		if ( ! is_readable( $path ) ) {
			return $values;
		}

		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( false === $lines ) {
			return $values;
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || 0 === strpos( $line, '#' ) ) {
				continue;
			}

			$parts = explode( '=', $line, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$key = trim( $parts[0] );
			$value = trim( $parts[1] );
			$value = trim( $value, "\"'" );

			$existing_value = getenv( $key );
			if ( false !== $existing_value && '' !== $existing_value ) {
				$values[ $key ] = $existing_value;
				continue;
			}

			$values[ $key ] = $value;
			$_ENV[ $key ] = $value;
			$_SERVER[ $key ] = $value;
			putenv( $key . '=' . $value );
		}

		return $values;
	}
}

if ( ! function_exists( 'wp_seo_automater_env' ) ) {
	/**
	 * Read an env var with a default fallback.
	 *
	 * @param string $key Env key.
	 * @param string $default Default value.
	 * @return string
	 */
	function wp_seo_automater_env( $key, $default = '' ) {
		$value = getenv( $key );

		if ( false === $value || '' === $value ) {
			return $default;
		}

		return $value;
	}
}
