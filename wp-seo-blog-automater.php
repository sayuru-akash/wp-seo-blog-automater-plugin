<?php
/**
 * Plugin Name:       WP SEO Blog Automater
 * Plugin URI:        https://codezela.com/wp-seo-blog-automater/
 * Description:       Professional AI-powered content automation tool. Automatically generates high-quality, SEO-optimized blog posts with integrated images, schema markup, and complete meta data using Google Gemini AI.
 * Version:           1.0.5
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Codezela Technologies
 * Author URI:        https://codezela.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-seo-blog-automater
 * Domain Path:       /languages
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.0
 * @copyright  2025 Codezela Technologies
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Define Plugin Constants
define( 'WP_SEO_AUTOMATER_VERSION', '1.0.5' );
define( 'WP_SEO_AUTOMATER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_SEO_AUTOMATER_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_SEO_AUTOMATER_BASENAME', plugin_basename( __FILE__ ) );
define( 'WP_SEO_AUTOMATER_FILE', __FILE__ );

// Load plugin text domain for translations
add_action( 'plugins_loaded', 'wp_seo_automater_load_textdomain' );
if ( ! function_exists( 'wp_seo_automater_load_textdomain' ) ) {
	function wp_seo_automater_load_textdomain() {
		load_plugin_textdomain(
			'wp-seo-blog-automater',
			false,
			dirname( WP_SEO_AUTOMATER_BASENAME ) . '/languages'
		);
	}
}

// Autoloader or Includes
require_once WP_SEO_AUTOMATER_PATH . 'includes/class-wp-seo-automater-admin.php';
require_once WP_SEO_AUTOMATER_PATH . 'includes/class-gemini-api-handler.php';
require_once WP_SEO_AUTOMATER_PATH . 'includes/class-github-updater.php';

// Initialize GitHub Updater for automatic updates
if ( ! function_exists( 'wp_seo_automater_init_github_updater' ) ) {
	function wp_seo_automater_init_github_updater() {
		new WP_SEO_Automater_GitHub_Updater( WP_SEO_AUTOMATER_BASENAME, WP_SEO_AUTOMATER_VERSION );
	}
	add_action( 'init', 'wp_seo_automater_init_github_updater' );
}

/**
 * Plugin activation hook.
 * Sets up default options and creates necessary database entries.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'wp_seo_automater_activate' ) ) {
	function wp_seo_automater_activate() {
		// Set default options if they don't exist
		if ( false === get_option( 'wp_seo_automater_gemini_model' ) ) {
			add_option( 'wp_seo_automater_gemini_model', 'gemini-pro-latest' );
		}
		if ( false === get_option( 'wp_seo_automater_seo_plugin' ) ) {
			add_option( 'wp_seo_automater_seo_plugin', 'auto' );
		}
		if ( false === get_option( 'wp_seo_automater_logs' ) ) {
			add_option( 'wp_seo_automater_logs', array() );
		}
		
		// Log activation
		WP_SEO_Automater_Admin::log_activity(
			'Plugin Activated',
			'WP SEO Blog Automater v' . WP_SEO_AUTOMATER_VERSION . ' activated successfully.',
			'success'
		);
	}
}
register_activation_hook( WP_SEO_AUTOMATER_FILE, 'wp_seo_automater_activate' );

/**
 * Plugin deactivation hook.
 * Cleans up temporary data and logs deactivation.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'wp_seo_automater_deactivate' ) ) {
	function wp_seo_automater_deactivate() {
		// Log deactivation
		WP_SEO_Automater_Admin::log_activity(
			'Plugin Deactivated',
			'WP SEO Blog Automater deactivated.',
			'info'
		);
		
		// Clear any scheduled events if we add cron jobs in the future
		// wp_clear_scheduled_hook( 'wp_seo_automater_cron_hook' );
	}
}
register_deactivation_hook( WP_SEO_AUTOMATER_FILE, 'wp_seo_automater_deactivate' );

/**
 * Initialize the plugin.
 * Loads core functionality after WordPress is fully loaded.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'run_wp_seo_automater' ) ) {
	function run_wp_seo_automater() {
		$plugin_admin = new WP_SEO_Automater_Admin();
		$plugin_admin->run();
	}
	add_action( 'plugins_loaded', 'run_wp_seo_automater' );
}

/**
 * Frontend: Inject Schema Markup into head.
 * Adds structured data (JSON-LD) for better SEO and rich snippets.
 *
 * @since 1.0.0
 */
function wp_seo_automater_inject_schema() {
	if ( is_single() ) {
		$post_id = get_the_ID();
		$schema = get_post_meta( $post_id, '_wp_seo_schema_markup', true );
		
		if ( ! empty( $schema ) ) {
			// Validate JSON before output
			$decoded = json_decode( $schema );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				echo "\n<!-- WP SEO Blog Automater - Schema Markup by Codezela Technologies -->\n";
				echo '<script type="application/ld+json">';
				echo $schema; // Already JSON, no need to escape
				echo '</script>';
				echo "\n<!-- /WP SEO Blog Automater Schema -->\n";
			}
		}
	}
}
add_action( 'wp_head', 'wp_seo_automater_inject_schema', 1 );
