<?php
/**
 * Shared bootstrap for CLI verification scripts.
 */

$repo_root = dirname( __DIR__, 2 );

require_once __DIR__ . '/env-loader.php';
wp_seo_automater_load_env( $repo_root . '/.env' );

if ( ! function_exists( 'get_option' ) ) {
	require_once __DIR__ . '/wp-shim.php';
}

defined( 'WP_SEO_AUTOMATER_PATH' ) || define( 'WP_SEO_AUTOMATER_PATH', $repo_root . '/' );
defined( 'WP_SEO_AUTOMATER_URL' ) || define( 'WP_SEO_AUTOMATER_URL', '' );
defined( 'WP_SEO_AUTOMATER_BASENAME' ) || define( 'WP_SEO_AUTOMATER_BASENAME', 'wp-seo-blog-automater/wp-seo-blog-automater.php' );
defined( 'WP_SEO_AUTOMATER_FILE' ) || define( 'WP_SEO_AUTOMATER_FILE', $repo_root . '/wp-seo-blog-automater.php' );

update_option( 'wp_seo_automater_gemini_key', wp_seo_automater_env( 'WP_SEO_AUTOMATER_GEMINI_KEY', '' ) );
update_option( 'wp_seo_automater_gemini_model', wp_seo_automater_env( 'WP_SEO_AUTOMATER_GEMINI_MODEL', 'gemini-3.6-flash' ) );
update_option( 'wp_seo_automater_image_alt_model', wp_seo_automater_env( 'WP_SEO_AUTOMATER_IMAGE_ALT_MODEL', 'gemini-3.6-flash' ) );
update_option( 'wp_seo_automater_unsplash_key', wp_seo_automater_env( 'WP_SEO_AUTOMATER_UNSPLASH_KEY', '' ) );
update_option( 'wp_seo_automater_logs', array() );

require_once $repo_root . '/includes/class-wp-seo-automater-admin.php';
require_once $repo_root . '/includes/class-gemini-api-handler.php';

if ( ! function_exists( 'wp_seo_automater_test_fail' ) ) {
	function wp_seo_automater_test_fail( $message ) {
		fwrite( STDERR, "[FAIL] $message\n" );
		exit( 1 );
	}
}

if ( ! function_exists( 'wp_seo_automater_test_assert' ) ) {
	function wp_seo_automater_test_assert( $condition, $message ) {
		if ( ! $condition ) {
			wp_seo_automater_test_fail( $message );
		}
	}
}

if ( ! function_exists( 'wp_seo_automater_test_admin' ) ) {
	function wp_seo_automater_test_admin() {
		return new WP_SEO_Automater_Admin();
	}
}

if ( ! function_exists( 'wp_seo_automater_test_forbidden_phrases' ) ) {
	function wp_seo_automater_test_forbidden_phrases() {
		return array(
			'The article, including the mandatory Call to Action',
			'There is no further content needed for this piece',
			'If you have a new topic, keyword cluster',
			'As your Lead SEO Content Strategist',
			'Please insert the following content immediately BEFORE',
		);
	}
}
