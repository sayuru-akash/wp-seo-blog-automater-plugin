<?php
/**
 * Plugin Name: WP SEO Blog Automater
 * Plugin URI:  https://codezela.com/
 * Description: Automates high-quality SEO blog post creation using Gemini API with a specific Master Prompt.
 * Version:     1.0.0
 * Author:      CodeZela Technologies
 * Author URI:  https://codezela.com/
 * License:     GPL-2.0+
 * Text Domain: wp-seo-blog-automater
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'WP_SEO_AUTOMATER_VERSION', '1.0.1' );
define( 'WP_SEO_AUTOMATER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_SEO_AUTOMATER_URL', plugin_dir_url( __FILE__ ) );

// Autoloader or Includes
require_once WP_SEO_AUTOMATER_PATH . 'includes/class-wp-seo-automater-admin.php';
require_once WP_SEO_AUTOMATER_PATH . 'includes/class-gemini-api-handler.php';

/**
 * Initialize the plugin.
 */
function run_wp_seo_automater() {
	$plugin_admin = new WP_SEO_Automater_Admin();
	$plugin_admin->run();
}
add_action( 'plugins_loaded', 'run_wp_seo_automater' );
