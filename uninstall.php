<?php
/**
 * Uninstall script for WP SEO Blog Automater
 *
 * Fires when the plugin is uninstalled via WordPress admin.
 * Cleans up all plugin data from the database.
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin options
 */
delete_option( 'wp_seo_automater_gemini_key' );
delete_option( 'wp_seo_automater_gemini_model' );
delete_option( 'wp_seo_automater_unsplash_key' );
delete_option( 'wp_seo_automater_seo_plugin' );
delete_option( 'wp_seo_automater_master_prompt' );
delete_option( 'wp_seo_automater_logs' );

// For multisite
if ( is_multisite() ) {
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

	$options_to_delete = array(
		'wp_seo_automater_gemini_key',
		'wp_seo_automater_gemini_model',
		'wp_seo_automater_unsplash_key',
		'wp_seo_automater_seo_plugin',
		'wp_seo_automater_master_prompt',
		'wp_seo_automater_logs',
	);

	foreach ( $blog_ids as $blog_id ) {
		$prefix = $wpdb->get_blog_prefix( $blog_id );
		$table  = $prefix . 'options';
		$wpdb->query( "DELETE FROM $table WHERE option_name IN ('" . implode( "', '", $options_to_delete ) . "')" );
	}
}

/**
 * Optional: Remove custom post meta added by the plugin
 * Uncomment the following lines if you want to remove all schema markup
 * and Unsplash source URLs from posts when uninstalling.
 */
// global $wpdb;
// $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_wp_seo_schema_markup'" );
// $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_wp_seo_automater_source_url'" );
