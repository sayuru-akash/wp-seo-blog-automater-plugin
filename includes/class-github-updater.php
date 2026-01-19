<?php
/**
 * GitHub Updater for WP SEO Blog Automater
 * 
 * Enables automatic plugin updates from GitHub releases.
 * Checks GitHub repository for new releases and provides one-click updates in WordPress admin.
 *
 * @package    WP_SEO_Blog_Automater
 * @subpackage WP_SEO_Blog_Automater/includes
 * @author     Codezela Technologies
 * @since      1.0.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * GitHub Updater Class
 *
 * Handles automatic updates from GitHub releases.
 *
 * @since 1.0.4
 */
class WP_SEO_Automater_GitHub_Updater {

	/**
	 * GitHub repository owner/username.
	 *
	 * @since 1.0.4
	 * @var string
	 */
	private $github_user = 'sayuru-akash';

	/**
	 * GitHub repository name.
	 *
	 * @since 1.0.4
	 * @var string
	 */
	private $github_repo = 'wp-seo-blog-automater-plugin';

	/**
	 * Plugin basename (e.g., wp-seo-blog-automater/wp-seo-blog-automater.php).
	 *
	 * @since 1.0.4
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Current plugin version.
	 *
	 * @since 1.0.4
	 * @var string
	 */
	private $plugin_version;

	/**
	 * Cache key for transient storage.
	 *
	 * @since 1.0.4
	 * @var string
	 */
	private $cache_key;

	/**
	 * Constructor.
	 *
	 * @since 1.0.4
	 * @param string $plugin_basename Plugin basename.
	 * @param string $plugin_version  Current plugin version.
	 */
	public function __construct( $plugin_basename, $plugin_version ) {
		$this->plugin_basename = $plugin_basename;
		$this->plugin_version  = $plugin_version;
		$this->cache_key       = 'wp_seo_automater_github_release';

		// Hook into WordPress update checks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
	}

	/**
	 * Get latest release information from GitHub.
	 *
	 * @since 1.0.4
	 * @return object|false Release data or false on failure.
	 */
	private function get_github_release() {
		// Check cache first
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$api_url  = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( empty( $data ) || isset( $data->message ) ) {
			return false;
		}

		// Cache for 12 hours
		set_transient( $this->cache_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Check for plugin updates.
	 *
	 * @since 1.0.4
	 * @param object $transient Update transient data.
	 * @return object Modified transient data.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_github_release();

		if ( ! $release ) {
			return $transient;
		}

		// Get version from tag (remove 'v' prefix if present)
		$remote_version = ltrim( $release->tag_name, 'v' );

		// Compare versions
		if ( version_compare( $this->plugin_version, $remote_version, '<' ) ) {
			// Find the ZIP asset
			$download_url = null;
			if ( ! empty( $release->assets ) ) {
				foreach ( $release->assets as $asset ) {
					if ( strpos( $asset->name, '.zip' ) !== false ) {
						$download_url = $asset->browser_download_url;
						break;
					}
				}
			}

			// Fallback to zipball if no ZIP asset found
			if ( ! $download_url ) {
				$download_url = $release->zipball_url;
			}

			$plugin_data = array(
				'slug'        => dirname( $this->plugin_basename ),
				'new_version' => $remote_version,
				'url'         => "https://github.com/{$this->github_user}/{$this->github_repo}",
				'package'     => $download_url,
				'tested'      => '6.4',
				'icons'       => array(),
			);

			$transient->response[ $this->plugin_basename ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Provide plugin information for update screen.
	 *
	 * @since 1.0.4
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The type of information being requested.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Modified result.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( dirname( $this->plugin_basename ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_github_release();

		if ( ! $release ) {
			return $result;
		}

		$remote_version = ltrim( $release->tag_name, 'v' );

		$result = (object) array(
			'name'          => 'WP SEO Blog Automater',
			'slug'          => dirname( $this->plugin_basename ),
			'version'       => $remote_version,
			'author'        => '<a href="https://codezela.com">Codezela Technologies</a>',
			'homepage'      => "https://github.com/{$this->github_user}/{$this->github_repo}",
			'download_link' => ! empty( $release->assets[0]->browser_download_url ) ? $release->assets[0]->browser_download_url : $release->zipball_url,
			'requires'      => '5.8',
			'tested'        => '6.4',
			'requires_php'  => '7.4',
			'sections'      => array(
				'description' => 'Professional AI-powered content automation tool. Automatically generates high-quality, SEO-optimized blog posts.',
				'changelog'   => $this->parse_changelog( $release->body ),
			),
		);

		return $result;
	}

	/**
	 * Parse changelog from GitHub release notes.
	 *
	 * @since 1.0.4
	 * @param string $body Release body/notes.
	 * @return string Formatted changelog.
	 */
	private function parse_changelog( $body ) {
		if ( empty( $body ) ) {
			return '<p>See <a href="https://github.com/' . $this->github_user . '/' . $this->github_repo . '/releases" target="_blank">GitHub releases</a> for details.</p>';
		}

		// Convert markdown to basic HTML
		$changelog = wp_kses_post( $body );
		$changelog = wpautop( $changelog );

		return $changelog;
	}

	/**
	 * Clean up after installation.
	 *
	 * @since 1.0.4
	 * @param bool  $response   Installation response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result     Installation result data.
	 * @return array Modified result.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		$install_directory = plugin_dir_path( WP_PLUGIN_DIR . '/' . $this->plugin_basename );
		$wp_filesystem->move( $result['destination'], $install_directory );
		$result['destination'] = $install_directory;

		// Clear cache after update
		delete_transient( $this->cache_key );

		// Activate plugin
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_basename ) {
			activate_plugin( $this->plugin_basename );
		}

		return $result;
	}
}
