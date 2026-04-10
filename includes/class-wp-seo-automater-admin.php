<?php
/**
 * Admin Class for WP SEO Blog Automater
 *
 * Handles all admin-side functionality including AJAX handlers,
 * menu registration, settings management, and content generation.
 *
 * @package    WP_SEO_Blog_Automater
 * @author     Codezela Technologies
 * @since      1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Main admin class for the plugin.
 *
 * @since 1.0.0
 */
class WP_SEO_Automater_Admin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Constructor intentionally left empty - initialization happens in run()
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		// Admin menu
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		
		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Frontend/public integration helpers
		add_action( 'parse_request', array( $this, 'maybe_serve_indexnow_key_file' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_wp_seo_generate_post', array( $this, 'ajax_generate_post' ) );
		add_action( 'wp_ajax_wp_seo_publish_post', array( $this, 'ajax_publish_post' ) );
		add_action( 'wp_ajax_wp_seo_refresh_image', array( $this, 'ajax_refresh_image' ) );
		add_action( 'wp_ajax_check_updates_now', array( $this, 'ajax_check_updates_now' ) );

		// Posts/pages bulk actions
		add_filter( 'bulk_actions-edit-post', array( $this, 'register_content_bulk_actions' ) );
		add_filter( 'bulk_actions-edit-page', array( $this, 'register_content_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-post', array( $this, 'handle_content_bulk_actions' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-page', array( $this, 'handle_content_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'render_bulk_action_notice' ) );
		
		// Add settings link on plugins page
		add_filter( 'plugin_action_links_' . WP_SEO_AUTOMATER_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @since 1.0.4
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=wp-seo-automater-settings' ),
			esc_html__( 'Settings', 'wp-seo-blog-automater' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Serve the IndexNow key file from the site root without requiring a
	 * manually uploaded physical file.
	 *
	 * @since 1.3.8
	 * @param WP $wp Parsed WordPress request object.
	 */
	public function maybe_serve_indexnow_key_file( $wp ) {
		$key = $this->get_indexnow_key();

		if ( empty( $key ) ) {
			return;
		}

		$expected_file = $key . '.txt';
		$request_path = '';

		if ( is_object( $wp ) && isset( $wp->request ) ) {
			$request_path = trim( (string) $wp->request, '/' );
		}

		if ( '' === $request_path && isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
			$request_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		}

		if ( $expected_file !== $request_path && $expected_file !== basename( $request_path ) ) {
			return;
		}

		if ( function_exists( 'status_header' ) ) {
			status_header( 200 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}

		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html( $key );
		exit;
	}

	/**
	 * Register plugin bulk actions on post and page list screens.
	 *
	 * @since 1.3.8
	 * @param array $actions Existing bulk actions.
	 * @return array
	 */
	public function register_content_bulk_actions( $actions ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$actions['wp_seo_automater_submit_indexnow'] = __( 'Submit to IndexNow', 'wp-seo-blog-automater' );
		$actions['wp_seo_automater_resubmit_sitemap'] = __( 'Resubmit Sitemap to Google', 'wp-seo-blog-automater' );
		$actions['wp_seo_automater_check_google_index'] = __( 'Check Google Index Status', 'wp-seo-blog-automater' );

		return $actions;
	}

	/**
	 * Handle custom bulk actions for posts and pages.
	 *
	 * @since 1.3.8
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Bulk action slug.
	 * @param array  $post_ids    Selected post IDs.
	 * @return string
	 */
	public function handle_content_bulk_actions( $redirect_to, $action, $post_ids ) {
		$supported_actions = array(
			'wp_seo_automater_submit_indexnow',
			'wp_seo_automater_resubmit_sitemap',
			'wp_seo_automater_check_google_index',
		);

		if ( ! in_array( $action, $supported_actions, true ) ) {
			return $redirect_to;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$token = $this->store_bulk_action_notice(
				array(
					'type' => 'error',
					'title' => __( 'Bulk action denied', 'wp-seo-blog-automater' ),
					'summary' => __( 'You do not have permission to use the plugin indexing actions.', 'wp-seo-blog-automater' ),
					'details' => array(),
				)
			);

			return add_query_arg( 'wp_seo_automater_notice', $token, $redirect_to );
		}

		switch ( $action ) {
			case 'wp_seo_automater_submit_indexnow':
				$notice = $this->process_indexnow_bulk_action( $post_ids );
				break;

			case 'wp_seo_automater_resubmit_sitemap':
				$notice = $this->process_google_sitemap_bulk_action( $post_ids );
				break;

			case 'wp_seo_automater_check_google_index':
				$notice = $this->process_google_index_bulk_action( $post_ids );
				break;

			default:
				return $redirect_to;
		}

		$token = $this->store_bulk_action_notice( $notice );

		return add_query_arg( 'wp_seo_automater_notice', $token, $redirect_to );
	}

	/**
	 * Render the post-list bulk action notice after redirect.
	 *
	 * @since 1.3.8
	 */
	public function render_bulk_action_notice() {
		if ( ! is_admin() || ! isset( $_GET['wp_seo_automater_notice'] ) ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['wp_seo_automater_notice'] ) );
		$notice = get_transient( 'wp_seo_automater_bulk_notice_' . $token );

		if ( ! is_array( $notice ) || empty( $notice['user_id'] ) || (int) $notice['user_id'] !== get_current_user_id() ) {
			return;
		}

		delete_transient( 'wp_seo_automater_bulk_notice_' . $token );

		$type = isset( $notice['type'] ) ? $notice['type'] : 'info';
		$class = 'notice notice-info is-dismissible';

		if ( 'success' === $type ) {
			$class = 'notice notice-success is-dismissible';
		} elseif ( 'warning' === $type ) {
			$class = 'notice notice-warning is-dismissible';
		} elseif ( 'error' === $type ) {
			$class = 'notice notice-error is-dismissible';
		}

		$title = isset( $notice['title'] ) ? $notice['title'] : __( 'WP SEO Blog Automater', 'wp-seo-blog-automater' );
		$summary = isset( $notice['summary'] ) ? $notice['summary'] : '';
		$details = isset( $notice['details'] ) && is_array( $notice['details'] ) ? $notice['details'] : array();

		echo '<div class="' . esc_attr( $class ) . '"><p><strong>' . esc_html( $title ) . '</strong> ' . esc_html( $summary ) . '</p>';

		if ( ! empty( $details ) ) {
			echo '<ul style="list-style: disc; margin-left: 1.5rem;">';
			foreach ( array_slice( $details, 0, 8 ) as $detail ) {
				echo '<li>' . esc_html( $detail ) . '</li>';
			}
			if ( count( $details ) > 8 ) {
				echo '<li>' . esc_html__( 'Additional results were omitted from this notice. See Activity Logs for the full summary.', 'wp-seo-blog-automater' ) . '</li>';
			}
			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * Persist a one-time admin notice for the current user.
	 *
	 * @since 1.3.8
	 * @param array $notice Notice data.
	 * @return string Token.
	 */
	private function store_bulk_action_notice( $notice ) {
		$token = $this->generate_random_token( 16 );
		$notice['user_id'] = get_current_user_id();
		set_transient( 'wp_seo_automater_bulk_notice_' . $token, $notice, 10 * MINUTE_IN_SECONDS );
		return $token;
	}

	/**
	 * AJAX Handler: Generate Content.
	 * 
	 * Processes content generation requests from the admin interface.
	 * Validates input, calls Gemini API, extracts metadata, fetches images,
	 * and returns structured data to the frontend.
	 *
	 * @since 1.0.0
	 */
	public function ajax_generate_post() {
		check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-seo-blog-automater' ) );
		}

		$title = sanitize_text_field( $_POST['title'] );
		$keywords = sanitize_text_field( $_POST['keywords'] );
		$result = $this->generate_preview_data( $title, $keywords );

		if ( is_wp_error( $result ) ) {
			self::log_activity( 'Generation Failed', "Title: $title - Error: " . $result->get_error_message(), 'error' );
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get the effective master prompt used for article generation.
	 *
	 * Keeps the saved/default prompt intact while appending required image
	 * keyword guidance for older installs that may still have stale prompt text.
	 *
	 * @since 1.3.0
	 * @return string Effective prompt.
	 */
	public function get_generation_master_prompt() {
		$master_prompt = get_option( 'wp_seo_automater_master_prompt', $this->get_default_master_prompt() );

		if ( stripos( $master_prompt, 'Image Search Keywords' ) === false ) {
			$master_prompt .= "\n\n[SYSTEM UPDATE]: You must also output 'Image Search Keywords: 1-2 broad visual terms (e.g. luxury glasses)' in the Phase 1 Metadata section for Unsplash integration.";
		}

		return $master_prompt;
	}

	/**
	 * Generate the exact preview payload consumed by the admin UI.
	 *
	 * This method is shared by the AJAX flow and local verification scripts so
	 * both exercise the same parsing and image-search behavior.
	 *
	 * @since 1.3.0
	 * @param string      $title         Article title/topic.
	 * @param string      $keywords      Target keywords.
	 * @param string|null $master_prompt Optional prompt override.
	 * @return array|WP_Error Preview payload or WP_Error on failure.
	 */
	public function generate_preview_data( $title, $keywords, $master_prompt = null ) {
		$handler = new Gemini_API_Handler();
		$master_prompt = null === $master_prompt ? $this->get_generation_master_prompt() : $master_prompt;

		self::log_activity( 'Generation Start', "Processing article: '{$title}' with keywords '{$keywords}'...", 'info' );
		$content = $handler->generate_article( $title, $keywords, $master_prompt );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		self::log_activity( 'Generation Success', "Generated article for: $title", 'success' );

		return $this->build_generation_result_payload( $content );
	}

	/**
	 * Parse raw AI output into the structured payload used by the editor UI.
	 *
	 * @since 1.3.0
	 * @param string $content Raw generated article content.
	 * @return array Parsed payload matching the AJAX response shape.
	 */
	public function build_generation_result_payload( $content ) {
		$content = $this->sanitize_generated_output( $content );

		$slug = '';
		if ( preg_match( '/Slug.*?(?:[:\-]|\s)[\s\*]*([^\n\r]+)/i', $content, $matches ) ) {
			$slug = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
		}

		$meta_title = '';
		if ( preg_match( '/Meta\s*Title.*?(?:[:\-]|\s)[\s\*]*([^\n\r]+)/i', $content, $matches ) ) {
			$meta_title = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
		}

		$meta_desc = '';
		if ( preg_match( '/Meta\s*Description.*?(?:[:\-]|\s)[\s\*]*([^\n\r]+)/i', $content, $matches ) ) {
			$meta_desc = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
		}

		$image_keywords = '';
		if ( preg_match( '/Image\s*Search\s*Keywords.*?(?:[:\-]|\s)[\s\*]*([^\n\r]+)/i', $content, $matches ) ) {
			$image_keywords = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );

			if ( strpos( $image_keywords, ',' ) !== false ) {
				$parts = explode( ',', $image_keywords );
				$image_keywords = trim( $parts[0] );
			}

			$words = explode( ' ', $image_keywords );
			if ( count( $words ) > 2 ) {
				$image_keywords = implode( ' ', array_slice( $words, 0, 2 ) );
			}
		}

		self::log_activity( 'Debug Extraction', "Slug: '$slug' | Title: '$meta_title' | Image Key: '$image_keywords'", 'info' );

		$image_result = $this->fetch_unsplash_image_data( $image_keywords, $meta_title, $content );

		$html_content = $this->markdown_to_html( $content );
		$extracted_schema = '';

		if ( preg_match( '/<script\s+type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html_content, $matches ) ) {
			$extracted_schema = trim( $matches[1] );
			$html_content = str_replace( $matches[0], '', $html_content );
		} elseif ( preg_match( '/```json(.*?)```/is', $content, $matches ) ) {
			if ( strpos( $matches[1], '@context' ) !== false ) {
				$extracted_schema = trim( $matches[1] );
			}
		}

		if ( ! empty( $extracted_schema ) ) {
			$decoded = json_decode( $extracted_schema );
			if ( $decoded === null ) {
				self::log_activity( 'Schema Warning', 'Extracted schema was invalid JSON. Attempting cleanup.', 'warning' );

				$start = strpos( $extracted_schema, '{' );
				$end   = strrpos( $extracted_schema, '}' );

				if ( false !== $start && false !== $end && $end > $start ) {
					$extracted_schema = substr( $extracted_schema, $start, $end - $start + 1 );
				}

				$extracted_schema = preg_replace( '/,\s*([\}\]])/s', '$1', $extracted_schema );
				$decoded = json_decode( $extracted_schema );

				if ( null !== $decoded ) {
					self::log_activity( 'Schema Fixed', 'Schema cleanup successful.', 'success' );
				} else {
					self::log_activity( 'Schema Error', 'Schema cleanup failed. JSON Error: ' . json_last_error_msg(), 'error' );
				}
			}
		}

		$h1_start_pos = stripos( $html_content, '<h1' );
		$extracted_title = '';

		if ( false !== $h1_start_pos ) {
			if ( preg_match( '/<h1.*?>(.*?)<\/h1>/is', $html_content, $matches, PREG_OFFSET_CAPTURE ) ) {
				$extracted_title = strip_tags( $matches[1][0] );
				$full_h1_string = $matches[0][0];
				$h1_end_pos = $h1_start_pos + strlen( $full_h1_string );

				$html_content = substr( $html_content, $h1_end_pos );
			} else {
				$html_content = substr( $html_content, $h1_start_pos );
			}
		} else {
			$html_content = preg_replace( '/^Phase \d+.*?(?=\n)/is', '', $html_content );
		}

		$stop_phrases = array(
			'Phase 2:',
			'Phase 3:',
			'Output Management',
			'The article, including the mandatory Call to Action',
			'There is no further content needed for this piece',
			'If you have a new topic, keyword cluster',
			'As your Lead SEO Content Strategist',
			'Please insert the following content immediately BEFORE',
			'***',
			'---',
			'___',
		);
		$cutoff_pos = strlen( $html_content );

		foreach ( $stop_phrases as $phrase ) {
			$pos = stripos( $html_content, $phrase );
			if ( false !== $pos && $pos < $cutoff_pos ) {
				$cutoff_pos = $pos;
			}
		}

		$html_content = trim( substr( $html_content, 0, $cutoff_pos ) );

		return array(
			'content'      => $html_content,
			'slug'         => $slug,
			'title'        => $extracted_title,
			'schema'       => $extracted_schema,
			'meta_title'   => $meta_title,
			'meta_desc'    => $meta_desc,
			'image_id'     => $image_result['photo_id'],
			'image_url'    => $image_result['url'],
			'image_credit' => $image_result['credit'],
			'debug_info'   => array(
				'keywords'        => $image_keywords,
				'unsplash_status' => $image_result['status'],
				'has_key'         => $image_result['has_key'],
				'image_query_source' => $image_result['query_source'],
				'image_query_used' => $image_result['query_used'],
				'image_query_attempts' => $image_result['queries_tried'],
			),
		);
	}

	/**
	 * AJAX handler to refresh the featured image without regenerating the article.
	 *
	 * @since 1.3.6
	 */
	public function ajax_refresh_image() {
		check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-seo-blog-automater' ) );
		}

		$image_keywords = isset( $_POST['image_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['image_keywords'] ) ) : '';
		$meta_title = isset( $_POST['meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_title'] ) ) : '';
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$used_image_ids = array();

		if ( isset( $_POST['used_image_ids'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['used_image_ids'] ), true );
			if ( is_array( $decoded ) ) {
				$used_image_ids = array_values(
					array_filter(
						array_map( 'sanitize_text_field', $decoded )
					)
				);
			}
		}

		$context_title = ! empty( $meta_title ) ? $meta_title : $title;
		self::log_activity( 'Image Refresh', "Refreshing image for '{$context_title}' with base keywords '{$image_keywords}'.", 'info' );

		$image_result = $this->fetch_unsplash_image_data( $image_keywords, $context_title, $content, $used_image_ids );

		if ( 0 === strpos( $image_result['status'], 'API Error:' ) ) {
			wp_send_json_error( array( 'message' => $image_result['status'] ) );
		}

		wp_send_json_success(
			array(
				'image_id' => $image_result['photo_id'],
				'image_url' => $image_result['url'],
				'image_credit' => $image_result['credit'],
				'debug_info' => array(
					'keywords' => $image_keywords,
					'unsplash_status' => $image_result['status'],
					'has_key' => $image_result['has_key'],
					'image_query_source' => $image_result['query_source'],
					'image_query_used' => $image_result['query_used'],
					'image_query_attempts' => $image_result['queries_tried'],
				),
			)
		);
	}

	/**
	 * AJAX Handler: Publish Post.
	 * 
	 * Creates a new WordPress post with all generated content,
	 * metadata, images, and SEO plugin integration.
	 *
	 * @since 1.0.0
	 */
	public function ajax_publish_post() {
		check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-seo-blog-automater' ) );
		}

		$title = sanitize_text_field( $_POST['title'] );
		$slug  = sanitize_title( $_POST['slug'] );
		$content = wp_kses_post( $_POST['content'] );
		
		// Metadata
		$schema = isset($_POST['schema']) ? trim($_POST['schema']) : '';
		$meta_title = isset($_POST['meta_title']) ? sanitize_text_field($_POST['meta_title']) : '';
		$meta_desc = isset($_POST['meta_desc']) ? sanitize_text_field($_POST['meta_desc']) : '';
		
		self::log_activity( 'Publish Start', "Attempting to publish post: '{$title}'...", 'info' );

		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'post'
		));
		
		if ( ! is_wp_error( $post_id ) ) {
			// 1. Schema
			if ( ! empty( $schema ) ) {
				// Validate JSON before saving to prevent frontend errors
				if ( json_decode( $schema ) !== null ) {
					update_post_meta( $post_id, '_wp_seo_schema_markup', $schema );
				} else {
					self::log_activity( 'Publish Warning', "Invalid JSON Schema detected. Skipped saving schema for Post ID: $post_id", 'warning' );
				}
			}

			// 2. IMAGE SIDELOAD (Unsplash)
			$image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
			self::log_activity( 'Publish Debug', "Received Image URL: " . ( empty($image_url) ? 'EMPTY' : $image_url ), 'info' );
			
			if ( ! empty( $image_url ) ) {
				// Required for media_sideload_image
				require_once(ABSPATH . 'wp-admin/includes/media.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/image.php');

				// CHECK FOR DUPLICATES (Optimization)
				// Look for an existing attachment that has this specific Image URL stored as meta.
				$existing_attachment = get_posts( array(
					'post_type'  => 'attachment',
					'meta_key'   => '_wp_seo_automater_source_url',
					'meta_value' => $image_url,
					'posts_per_page' => 1,
					'fields'     => 'ids', // efficient
				) );

				if ( ! empty( $existing_attachment ) ) {
					// Use existing ID
					$attachment_id = $existing_attachment[0];
					self::log_activity( 'Image', "Reusing existing image ID: $attachment_id for source URL", 'info' );
				} else {
					// MANUAL DOWNLOAD PIPELINE (Fixes "Invalid URL" for Unsplash links without .jpg extension)
					self::log_activity( 'Publish Debug', "Attempting manual download via wp_remote_get...", 'info' );
					
					$get_response = wp_remote_get( $image_url );
					
					if ( is_wp_error( $get_response ) ) {
						$attachment_id = $get_response; // Pass error
					} elseif ( wp_remote_retrieve_response_code( $get_response ) != 200 ) {
						$attachment_id = new WP_Error( 'http_error', 'Unsplash returned ' . wp_remote_retrieve_response_code( $get_response ) );
					} else {
						// Success: Get bits
						$image_bits = wp_remote_retrieve_body( $get_response );
						
						if ( ! empty( $image_bits ) ) {
							$upload_dir = wp_upload_dir();
							// Force a clean filename
							$filename = sanitize_title( $title ) . '-unsplash.jpg';
							if ( empty( $filename ) ) { $filename = 'image-' . time() . '.jpg'; }
							
							// Save file
							// wp_upload_bits handles unique filenames automatically if exists
							$upload = wp_upload_bits( $filename, null, $image_bits );
							
							if ( $upload['error'] ) {
								$attachment_id = new WP_Error( 'upload_error', $upload['error'] );
							} else {
								// Create Attachment
								$file_path = $upload['file'];
								$attachment = array(
									'post_mime_type' => 'image/jpeg',
									'post_title'     => $title,
									'post_content'   => '',
									'post_status'    => 'inherit'
								);
								
								$attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );
								
								// Generate Metadata (sizes)
								if ( ! is_wp_error( $attachment_id ) ) {
									require_once(ABSPATH . 'wp-admin/includes/image.php');
									$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
									wp_update_attachment_metadata( $attachment_id, $attach_data );
									
									// Save Source URL for Dedupe
									update_post_meta( $attachment_id, '_wp_seo_automater_source_url', $image_url );
								}
							}
						} else {
							$attachment_id = new WP_Error( 'empty_image', 'Downloaded image body is empty.' );
						}
					}
				}

				if ( ! is_wp_error( $attachment_id ) ) {
					// Set as Featured Image
					set_post_thumbnail( $post_id, $attachment_id );
					
					// Set SEO Alt Text (Use Title or Keyword)
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title . ' - Unsplash' );
					
					self::log_activity( 'Image', "Sideloaded image ID: $attachment_id", 'success' );
				} else {
					self::log_activity( 'Image Error', "Sideload Failed: " . $attachment_id->get_error_message(), 'error' );
				}
			}

			// 3. SEO Plugin Integration
			$seo_plugin_setting = get_option( 'wp_seo_automater_seo_plugin', 'auto' );
			$is_yoast_active = defined( 'WPSEO_VERSION' );
			$is_rank_math_active = defined( 'RANK_MATH_VERSION' );

			// Determine target system
			$use_yoast = false;
			$use_rank_math = false;

			if ( $seo_plugin_setting === 'yoast' ) {
				$use_yoast = true;
			} elseif ( $seo_plugin_setting === 'rankmath' ) {
				$use_rank_math = true;
			} else {
				// Auto-detect (Prioritize Rank Math if both? Or Yoast? Let's check definitions)
				if ( $is_rank_math_active ) {
					$use_rank_math = true;
				} elseif ( $is_yoast_active ) {
					$use_yoast = true;
				}
			}

			// Save Keys
			if ( $use_yoast ) {
				if ( ! empty( $meta_title ) ) {
					update_post_meta( $post_id, '_yoast_wpseo_title', $meta_title );
					update_post_meta( $post_id, '_yoast_wpseo_opengraph-title', $meta_title );
					update_post_meta( $post_id, '_yoast_wpseo_twitter-title', $meta_title );
				}
				if ( ! empty( $meta_desc ) ) {
					update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
					update_post_meta( $post_id, '_yoast_wpseo_opengraph-description', $meta_desc );
					update_post_meta( $post_id, '_yoast_wpseo_twitter-description', $meta_desc );
				}
				self::log_activity( 'Publish Info', "Saved metadata for Yoast SEO.", 'info' );
			} 
			elseif ( $use_rank_math ) {
				if ( ! empty( $meta_title ) ) {
					update_post_meta( $post_id, 'rank_math_title', $meta_title );
					update_post_meta( $post_id, 'rank_math_facebook_title', $meta_title );
					update_post_meta( $post_id, 'rank_math_twitter_title', $meta_title );
				}
				if ( ! empty( $meta_desc ) ) {
					update_post_meta( $post_id, 'rank_math_description', $meta_desc );
					update_post_meta( $post_id, 'rank_math_facebook_description', $meta_desc );
					update_post_meta( $post_id, 'rank_math_twitter_description', $meta_desc );
				}
				self::log_activity( 'Publish Info', "Saved metadata for Rank Math SEO.", 'info' );
			}
		}

		if ( is_wp_error( $post_id ) ) {
			self::log_activity( 'Publish Failed', "Title: $title - Error: " . $post_id->get_error_message(), 'error' );
			wp_send_json_error( $post_id->get_error_message() );
		}

		self::log_activity( 'Publish Success', "Published Post ID: $post_id", 'success' );

		wp_send_json_success( array(
			'post_id' => $post_id,
			'post_url' => get_permalink( $post_id )
		));
	}

	/**
	 * AJAX handler to check for plugin updates immediately.
	 * Clears the GitHub release cache and forces a fresh check.
	 *
	 * @since 1.0.8
	 */
	public function ajax_check_updates_now() {
		// Verify nonce
		check_ajax_referer( 'wp_seo_automater_nonce', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to check for updates.', 'wp-seo-blog-automater' )
			) );
		}

		// Clear the cached GitHub release data
		delete_transient( 'wp_seo_automater_github_release' );
		
		// Also clear WordPress update cache
		delete_site_transient( 'update_plugins' );

		// Get fresh release data
		if ( class_exists( 'WP_SEO_Automater_GitHub_Updater' ) ) {
			$updater = new WP_SEO_Automater_GitHub_Updater( WP_SEO_AUTOMATER_BASENAME, WP_SEO_AUTOMATER_VERSION );
			$release = $updater->get_github_release( true );
			
			if ( is_wp_error( $release ) ) {
				self::log_activity( 'Update Check', 'Failed to fetch update from GitHub: ' . $release->get_error_message(), 'error' );
				wp_send_json_error( array(
					'message' => sprintf(
						__( 'Failed to check for updates: %s', 'wp-seo-blog-automater' ),
						$release->get_error_message()
					)
				) );
			}

			// Compare versions
			$current_version = WP_SEO_AUTOMATER_VERSION;
			$latest_version = isset( $release->tag_name ) ? ltrim( $release->tag_name, 'v' ) : '';
			
			if ( empty( $latest_version ) ) {
				wp_send_json_error( array(
					'message' => __( 'Could not determine latest version from GitHub.', 'wp-seo-blog-automater' )
				) );
			}

			$update_available = version_compare( $latest_version, $current_version, '>' );

			// Force WordPress to rebuild the plugin update transient now that we have
			// a fresh GitHub release cached, so the Plugins page reflects the result
			// immediately after the manual check.
			if ( function_exists( 'wp_update_plugins' ) ) {
				wp_update_plugins();
			} elseif ( file_exists( ABSPATH . 'wp-admin/includes/update.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/update.php';
				if ( function_exists( 'wp_update_plugins' ) ) {
					wp_update_plugins();
				}
			}
			
			self::log_activity( 
				'Update Check', 
				sprintf( 
					'Manual update check completed. Current: %s, Latest: %s, Update Available: %s',
					$current_version,
					$latest_version,
					$update_available ? 'Yes' : 'No'
				), 
				$update_available ? 'warning' : 'info' 
			);

			wp_send_json_success( array(
				'current_version' => $current_version,
				'latest_version' => $latest_version,
				'update_available' => $update_available,
				'message' => $update_available 
					? sprintf(
						__( 'Update available! Version %s is ready to install. Go to the Plugins page to update.', 'wp-seo-blog-automater' ),
						$latest_version
					)
					: __( 'You are running the latest version!', 'wp-seo-blog-automater' )
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'GitHub Updater class not found.', 'wp-seo-blog-automater' )
			) );
		}
	}

	/**
	 * Log activity to the plugin's log system.
	 * 
	 * Stores activity logs in WordPress options for debugging and monitoring.
	 * Keeps the last 200 log entries.
	 *
	 * @since 1.0.0
	 * @param string $topic   Log topic/category.
	 * @param string $details Detailed log message.
	 * @param string $status  Log status (success, error, warning, info).
	 */
	public static function log_activity( $topic, $details, $status ) {
		$logs = get_option( 'wp_seo_automater_logs', array() );
		// Prepend new log
		array_unshift( $logs, array(
			'date'    => current_time( 'mysql' ),
			'topic'   => $topic,
			'details' => $details,
			'status'  => $status
		));
		// Keep last 200
		$logs = array_slice( $logs, 0, 200 );
		update_option( 'wp_seo_automater_logs', $logs );
	}

	/**
	 * Process the IndexNow bulk action.
	 *
	 * @since 1.3.8
	 * @param array $post_ids Selected post IDs.
	 * @return array
	 */
	private function process_indexnow_bulk_action( $post_ids ) {
		$prepared = $this->collect_public_urls_for_bulk_action( $post_ids, 100 );
		$urls = wp_list_pluck( $prepared['items'], 'url' );

		if ( empty( $urls ) ) {
			return array(
				'type' => 'error',
				'title' => __( 'IndexNow submission failed', 'wp-seo-blog-automater' ),
				'summary' => __( 'No published public URLs were available to submit.', 'wp-seo-blog-automater' ),
				'details' => $prepared['skipped'],
			);
		}

		$response = $this->submit_urls_to_indexnow( $urls );
		if ( is_wp_error( $response ) ) {
			self::log_activity( 'IndexNow', 'Submission failed: ' . $response->get_error_message(), 'error' );

			return array(
				'type' => 'error',
				'title' => __( 'IndexNow submission failed', 'wp-seo-blog-automater' ),
				'summary' => $response->get_error_message(),
				'details' => $prepared['skipped'],
			);
		}

		$details = array(
			sprintf(
				/* translators: %s: IndexNow key file URL */
				__( 'Verification file served by the plugin: %s', 'wp-seo-blog-automater' ),
				$response['key_file_url']
			),
			sprintf(
				/* translators: %d: HTTP status code */
				__( 'IndexNow endpoint accepted the batch with HTTP %d.', 'wp-seo-blog-automater' ),
				$response['status_code']
			),
		);

		$details = array_merge( $details, $prepared['skipped'] );

		self::log_activity(
			'IndexNow',
			sprintf(
				'Submitted %1$d URLs to IndexNow (HTTP %2$d).',
				count( $urls ),
				$response['status_code']
			),
			'success'
		);

		return array(
			'type' => empty( $prepared['skipped'] ) ? 'success' : 'warning',
			'title' => __( 'IndexNow submission completed', 'wp-seo-blog-automater' ),
			'summary' => sprintf(
				/* translators: %d: number of submitted URLs */
				_n(
					'Submitted %d URL to IndexNow.',
					'Submitted %d URLs to IndexNow.',
					count( $urls ),
					'wp-seo-blog-automater'
				),
				count( $urls )
			),
			'details' => $details,
		);
	}

	/**
	 * Process the Google sitemap resubmission bulk action.
	 *
	 * @since 1.3.8
	 * @param array $post_ids Selected post IDs.
	 * @return array
	 */
	private function process_google_sitemap_bulk_action( $post_ids ) {
		$sitemaps = $this->get_configured_sitemap_urls();
		if ( is_wp_error( $sitemaps ) ) {
			return array(
				'type' => 'error',
				'title' => __( 'Google sitemap submission failed', 'wp-seo-blog-automater' ),
				'summary' => $sitemaps->get_error_message(),
				'details' => array(),
			);
		}

		$result = $this->submit_sitemaps_to_google( $sitemaps );
		if ( is_wp_error( $result ) ) {
			self::log_activity( 'Google Sitemaps', 'Submission failed: ' . $result->get_error_message(), 'error' );

			return array(
				'type' => 'error',
				'title' => __( 'Google sitemap submission failed', 'wp-seo-blog-automater' ),
				'summary' => $result->get_error_message(),
				'details' => array(),
			);
		}

		$details = array(
			sprintf(
				/* translators: %d: selected post count */
				_n(
					'Selection contained %d item. Sitemap submission is site-level, so the sitemap list was resubmitted once.',
					'Selection contained %d items. Sitemap submission is site-level, so the sitemap list was resubmitted once.',
					count( $post_ids ),
					'wp-seo-blog-automater'
				),
				count( $post_ids )
			),
		);

		foreach ( $result['submitted'] as $sitemap_url ) {
			$details[] = sprintf(
				/* translators: %s: sitemap URL */
				__( 'Submitted sitemap: %s', 'wp-seo-blog-automater' ),
				$sitemap_url
			);
		}

		self::log_activity(
			'Google Sitemaps',
			sprintf(
				'Submitted %1$d sitemap URLs to Search Console for property %2$s.',
				count( $result['submitted'] ),
				$result['property']
			),
			'success'
		);

		return array(
			'type' => 'success',
			'title' => __( 'Google sitemap submission completed', 'wp-seo-blog-automater' ),
			'summary' => sprintf(
				/* translators: %d: number of submitted sitemaps */
				_n(
					'Submitted %d sitemap to Google Search Console.',
					'Submitted %d sitemaps to Google Search Console.',
					count( $result['submitted'] ),
					'wp-seo-blog-automater'
				),
				count( $result['submitted'] )
			),
			'details' => $details,
		);
	}

	/**
	 * Process the Google URL inspection bulk action.
	 *
	 * @since 1.3.8
	 * @param array $post_ids Selected post IDs.
	 * @return array
	 */
	private function process_google_index_bulk_action( $post_ids ) {
		$prepared = $this->collect_public_urls_for_bulk_action( $post_ids, 10 );
		$items = $prepared['items'];

		if ( empty( $items ) ) {
			return array(
				'type' => 'error',
				'title' => __( 'Google index check failed', 'wp-seo-blog-automater' ),
				'summary' => __( 'No published public URLs were available to inspect.', 'wp-seo-blog-automater' ),
				'details' => $prepared['skipped'],
			);
		}

		$results = array();
		$on_google = 0;
		$needs_attention = 0;
		$errors = 0;

		foreach ( $items as $item ) {
			$inspection = $this->inspect_url_in_google( $item['url'] );

			if ( is_wp_error( $inspection ) ) {
				$errors++;
				$results[] = sprintf(
					/* translators: 1: post title, 2: error message */
					__( '%1$s: API error: %2$s', 'wp-seo-blog-automater' ),
					$item['title'],
					$inspection->get_error_message()
				);
				continue;
			}

			if ( 'PASS' === $inspection['verdict'] ) {
				$on_google++;
			} else {
				$needs_attention++;
			}

			$detail = sprintf(
				/* translators: 1: post title, 2: status label, 3: coverage state */
				__( '%1$s: %2$s | %3$s', 'wp-seo-blog-automater' ),
				$item['title'],
				$inspection['status_label'],
				$inspection['coverage_state']
			);

			if ( ! empty( $inspection['last_crawl_time'] ) ) {
				$detail .= ' | ' . sprintf(
					/* translators: %s: last crawl time */
					__( 'Last crawl: %s', 'wp-seo-blog-automater' ),
					$inspection['last_crawl_time']
				);
			}

			$results[] = $detail;
		}

		self::log_activity(
			'Google Index Check',
			sprintf(
				'Inspected %1$d URLs. On Google: %2$d. Needs attention: %3$d. API errors: %4$d.',
				count( $items ),
				$on_google,
				$needs_attention,
				$errors
			),
			$errors > 0 || $needs_attention > 0 ? 'warning' : 'success'
		);

		return array(
			'type' => $errors > 0 ? 'warning' : ( $needs_attention > 0 ? 'warning' : 'success' ),
			'title' => __( 'Google index status check completed', 'wp-seo-blog-automater' ),
			'summary' => sprintf(
				/* translators: 1: total inspected, 2: on Google count, 3: needs attention count, 4: error count */
				__( 'Inspected %1$d URLs. On Google: %2$d. Needs attention: %3$d. API errors: %4$d.', 'wp-seo-blog-automater' ),
				count( $items ),
				$on_google,
				$needs_attention,
				$errors
			),
			'details' => array_merge( $results, $prepared['skipped'] ),
		);
	}

	/**
	 * Collect eligible public URLs for bulk actions.
	 *
	 * @since 1.3.8
	 * @param array $post_ids Selected post IDs.
	 * @param int   $limit    Maximum URLs to process.
	 * @return array
	 */
	private function collect_public_urls_for_bulk_action( $post_ids, $limit ) {
		$items = array();
		$skipped = array();
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$post_ids = array_values( array_unique( array_map( 'absint', (array) $post_ids ) ) );

		foreach ( $post_ids as $index => $post_id ) {
			if ( count( $items ) >= $limit ) {
				$remaining = count( $post_ids ) - $index;
				if ( $remaining > 0 ) {
					$skipped[] = sprintf(
						/* translators: 1: remaining item count, 2: processing limit */
						__( 'Skipped %1$d additional items because this action processes up to %2$d URLs per run.', 'wp-seo-blog-automater' ),
						$remaining,
						$limit
					);
				}
				break;
			}

			$post = get_post( $post_id );
			if ( ! $post ) {
				$skipped[] = sprintf(
					/* translators: %d: post ID */
					__( 'Skipped ID %d because the post could not be loaded.', 'wp-seo-blog-automater' ),
					$post_id
				);
				continue;
			}

			if ( 'publish' !== $post->post_status ) {
				$skipped[] = sprintf(
					/* translators: 1: post title, 2: post status */
					__( 'Skipped "%1$s" because it is %2$s, not published.', 'wp-seo-blog-automater' ),
					get_the_title( $post ),
					$post->post_status
				);
				continue;
			}

			if ( function_exists( 'is_post_type_viewable' ) && ! is_post_type_viewable( $post->post_type ) ) {
				$skipped[] = sprintf(
					/* translators: %s: post title */
					__( 'Skipped "%s" because its post type is not publicly viewable.', 'wp-seo-blog-automater' ),
					get_the_title( $post )
				);
				continue;
			}

			$url = get_permalink( $post );
			if ( empty( $url ) || is_wp_error( $url ) ) {
				$skipped[] = sprintf(
					/* translators: %s: post title */
					__( 'Skipped "%s" because a public permalink was not available.', 'wp-seo-blog-automater' ),
					get_the_title( $post )
				);
				continue;
			}

			$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( ! empty( $home_host ) && ! empty( $url_host ) && $home_host !== $url_host ) {
				$skipped[] = sprintf(
					/* translators: %s: post title */
					__( 'Skipped "%s" because its URL host does not match the site host.', 'wp-seo-blog-automater' ),
					get_the_title( $post )
				);
				continue;
			}

			$items[] = array(
				'id'    => $post_id,
				'title' => get_the_title( $post ),
				'url'   => $url,
			);
		}

		return array(
			'items'   => $items,
			'skipped' => $skipped,
		);
	}

	/**
	 * Get the saved IndexNow key.
	 *
	 * @since 1.3.8
	 * @return string
	 */
	private function get_indexnow_key() {
		return trim( (string) get_option( 'wp_seo_automater_indexnow_key', '' ) );
	}

	/**
	 * Build the public IndexNow key file URL served by the plugin.
	 *
	 * @since 1.3.8
	 * @return string
	 */
	private function get_indexnow_key_file_url() {
		$key = $this->get_indexnow_key();

		if ( empty( $key ) ) {
			return '';
		}

		return home_url( '/' . rawurlencode( $key ) . '.txt' );
	}

	/**
	 * Submit a batch of URLs to IndexNow.
	 *
	 * @since 1.3.8
	 * @param array $urls URLs to submit.
	 * @return array|WP_Error
	 */
	private function submit_urls_to_indexnow( $urls ) {
		$key = $this->get_indexnow_key();
		if ( empty( $key ) ) {
			return new WP_Error(
				'missing_indexnow_key',
				__( 'IndexNow key is not configured. Save or generate one in Settings first.', 'wp-seo-blog-automater' )
			);
		}

		$key_file_url = $this->get_indexnow_key_file_url();
		$home_host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( empty( $home_host ) ) {
			return new WP_Error(
				'indexnow_host_error',
				__( 'Could not determine the site host for IndexNow submission.', 'wp-seo-blog-automater' )
			);
		}

		$payload = array(
			'host'        => $home_host,
			'key'         => $key,
			'keyLocation' => $key_file_url,
			'urlList'     => array_values( array_unique( array_map( 'esc_url_raw', $urls ) ) ),
		);

		$response = $this->perform_http_request(
			'POST',
			'https://api.indexnow.org/indexnow',
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'indexnow_http_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: API response message */
					__( 'IndexNow returned HTTP %1$d. %2$s', 'wp-seo-blog-automater' ),
					$status_code,
					$this->extract_api_error_message( wp_remote_retrieve_body( $response ) )
				)
			);
		}

		return array(
			'status_code'  => $status_code,
			'key_file_url' => $key_file_url,
		);
	}

	/**
	 * Return the configured Search Console property or the site home URL.
	 *
	 * @since 1.3.8
	 * @return string
	 */
	private function get_search_console_property() {
		$property = trim( (string) get_option( 'wp_seo_automater_google_property', '' ) );

		if ( empty( $property ) ) {
			$property = home_url( '/' );
		}

		if ( 0 === strpos( $property, 'sc-domain:' ) ) {
			return $property;
		}

		return trailingslashit( esc_url_raw( $property ) );
	}

	/**
	 * Parse the saved Google service account JSON credentials.
	 *
	 * @since 1.3.8
	 * @return array|WP_Error
	 */
	private function get_google_service_account_credentials() {
		$raw = trim( (string) get_option( 'wp_seo_automater_google_service_account_json', '' ) );

		if ( '' === $raw ) {
			return new WP_Error(
				'missing_google_credentials',
				__( 'Google service account JSON is not configured in Settings.', 'wp-seo-blog-automater' )
			);
		}

		$credentials = json_decode( $raw, true );
		if ( ! is_array( $credentials ) || empty( $credentials['client_email'] ) || empty( $credentials['private_key'] ) ) {
			return new WP_Error(
				'invalid_google_credentials',
				__( 'Google service account JSON is invalid. It must include client_email and private_key.', 'wp-seo-blog-automater' )
			);
		}

		if ( empty( $credentials['token_uri'] ) ) {
			$credentials['token_uri'] = 'https://oauth2.googleapis.com/token';
		}

		return $credentials;
	}

	/**
	 * Get the configured service account email for display.
	 *
	 * @since 1.3.8
	 * @return string
	 */
	private function get_google_service_account_email() {
		$credentials = $this->get_google_service_account_credentials();

		if ( is_wp_error( $credentials ) ) {
			return '';
		}

		return (string) $credentials['client_email'];
	}

	/**
	 * Fetch or mint a Google access token using the stored service account.
	 *
	 * @since 1.3.8
	 * @return string|WP_Error
	 */
	private function get_google_access_token() {
		$credentials = $this->get_google_service_account_credentials();
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$cache_key = 'wp_seo_automater_google_token_' . md5( $credentials['client_email'] );
		$cached = get_transient( $cache_key );

		if ( is_array( $cached ) && ! empty( $cached['access_token'] ) && ! empty( $cached['expires_at'] ) && (int) $cached['expires_at'] > time() + 60 ) {
			return $cached['access_token'];
		}

		if ( ! function_exists( 'openssl_sign' ) ) {
			return new WP_Error(
				'missing_openssl',
				__( 'OpenSSL is required to authenticate with Google Search Console.', 'wp-seo-blog-automater' )
			);
		}

		$now = time();
		$header = array(
			'alg' => 'RS256',
			'typ' => 'JWT',
		);
		$claims = array(
			'iss'   => $credentials['client_email'],
			'scope' => 'https://www.googleapis.com/auth/webmasters',
			'aud'   => $credentials['token_uri'],
			'iat'   => $now,
			'exp'   => $now + HOUR_IN_SECONDS,
		);

		$segments = $this->base64_url_encode( wp_json_encode( $header ) ) . '.' . $this->base64_url_encode( wp_json_encode( $claims ) );
		$signature = '';
		$private_key = openssl_pkey_get_private( $credentials['private_key'] );

		if ( false === $private_key || ! openssl_sign( $segments, $signature, $private_key, 'sha256WithRSAEncryption' ) ) {
			return new WP_Error(
				'google_signature_error',
				__( 'Failed to sign the Google service account assertion. Check the private key JSON.', 'wp-seo-blog-automater' )
			);
		}

		$assertion = $segments . '.' . $this->base64_url_encode( $signature );
		$response = $this->perform_http_request(
			'POST',
			$credentials['token_uri'],
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
				),
				'body'    => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $assertion,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code < 200 || $status_code >= 300 || empty( $body['access_token'] ) ) {
			return new WP_Error(
				'google_token_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: API response message */
					__( 'Google token request failed with HTTP %1$d. %2$s', 'wp-seo-blog-automater' ),
					$status_code,
					$this->extract_api_error_message( wp_remote_retrieve_body( $response ) )
				)
			);
		}

		$expires_in = isset( $body['expires_in'] ) ? max( 60, (int) $body['expires_in'] ) : HOUR_IN_SECONDS;
		set_transient(
			$cache_key,
			array(
				'access_token' => $body['access_token'],
				'expires_at'   => time() + $expires_in,
			),
			$expires_in
		);

		return $body['access_token'];
	}

	/**
	 * Submit sitemap URLs to Google Search Console.
	 *
	 * @since 1.3.8
	 * @param array $sitemap_urls Sitemap URLs to submit.
	 * @return array|WP_Error
	 */
	private function submit_sitemaps_to_google( $sitemap_urls ) {
		$property = $this->get_search_console_property();
		$submitted = array();

		foreach ( $sitemap_urls as $sitemap_url ) {
			$endpoint = sprintf(
				'https://www.googleapis.com/webmasters/v3/sites/%1$s/sitemaps/%2$s',
				rawurlencode( $property ),
				rawurlencode( $sitemap_url )
			);

			$response = $this->call_search_console_api( 'PUT', $endpoint );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$submitted[] = $sitemap_url;
		}

		return array(
			'property'  => $property,
			'submitted' => $submitted,
		);
	}

	/**
	 * Determine which sitemap URLs should be resubmitted.
	 *
	 * @since 1.3.8
	 * @return array|WP_Error
	 */
	private function get_configured_sitemap_urls() {
		$custom = trim( (string) get_option( 'wp_seo_automater_google_sitemap_urls', '' ) );

		if ( '' !== $custom ) {
			$urls = preg_split( '/\r\n|\r|\n/', $custom );
			$urls = array_values(
				array_unique(
					array_filter(
						array_map( 'esc_url_raw', array_map( 'trim', $urls ) )
					)
				)
			);

			if ( ! empty( $urls ) ) {
				return $urls;
			}
		}

		$candidates = array(
			home_url( '/sitemap_index.xml' ),
			home_url( '/wp-sitemap.xml' ),
			home_url( '/sitemap.xml' ),
		);

		$reachable = array();
		foreach ( array_unique( $candidates ) as $candidate ) {
			$response = $this->perform_http_request(
				'GET',
				$candidate,
				array(
					'timeout' => 10,
				)
			);

			if ( is_wp_error( $response ) ) {
				continue;
			}

			$status_code = (int) wp_remote_retrieve_response_code( $response );
			if ( $status_code >= 200 && $status_code < 300 ) {
				$reachable[] = $candidate;
			}
		}

		if ( empty( $reachable ) ) {
			return new WP_Error(
				'missing_sitemap_urls',
				__( 'No sitemap URLs were configured and no default sitemap endpoint was reachable. Add sitemap URLs in Settings first.', 'wp-seo-blog-automater' )
			);
		}

		return $reachable;
	}

	/**
	 * Inspect a URL in Google Search Console and normalize the result.
	 *
	 * @since 1.3.8
	 * @param string $url URL to inspect.
	 * @return array|WP_Error
	 */
	private function inspect_url_in_google( $url ) {
		$response = $this->call_search_console_api(
			'POST',
			'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',
			array(
				'inspectionUrl' => $url,
				'siteUrl'       => $this->get_search_console_property(),
				'languageCode'  => 'en-US',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$index_status = isset( $body['inspectionResult']['indexStatusResult'] ) && is_array( $body['inspectionResult']['indexStatusResult'] )
			? $body['inspectionResult']['indexStatusResult']
			: array();

		if ( empty( $index_status ) ) {
			return new WP_Error(
				'google_inspection_parse_error',
				__( 'Google did not return index status data for this URL.', 'wp-seo-blog-automater' )
			);
		}

		$verdict = isset( $index_status['verdict'] ) ? (string) $index_status['verdict'] : 'VERDICT_UNSPECIFIED';
		$status_label = __( 'Unknown status', 'wp-seo-blog-automater' );

		if ( 'PASS' === $verdict ) {
			$status_label = __( 'URL is on Google', 'wp-seo-blog-automater' );
		} elseif ( 'NEUTRAL' === $verdict ) {
			$status_label = __( 'URL is excluded from Google', 'wp-seo-blog-automater' );
		} elseif ( 'FAIL' === $verdict ) {
			$status_label = __( 'URL has indexing errors', 'wp-seo-blog-automater' );
		}

		$last_crawl_time = '';
		if ( ! empty( $index_status['lastCrawlTime'] ) ) {
			$timestamp = strtotime( $index_status['lastCrawlTime'] );
			$last_crawl_time = $timestamp ? wp_date( 'Y-m-d H:i', $timestamp ) : $index_status['lastCrawlTime'];
		}

		return array(
			'verdict'         => $verdict,
			'status_label'    => $status_label,
			'coverage_state'  => ! empty( $index_status['coverageState'] ) ? $index_status['coverageState'] : __( 'Coverage state unavailable', 'wp-seo-blog-automater' ),
			'indexing_state'  => ! empty( $index_status['indexingState'] ) ? $index_status['indexingState'] : '',
			'page_fetch_state'=> ! empty( $index_status['pageFetchState'] ) ? $index_status['pageFetchState'] : '',
			'last_crawl_time' => $last_crawl_time,
		);
	}

	/**
	 * Call a Google Search Console API endpoint with service-account auth.
	 *
	 * @since 1.3.8
	 * @param string     $method HTTP method.
	 * @param string     $endpoint Endpoint URL.
	 * @param array|null $body Request body.
	 * @return array|WP_Error
	 */
	private function call_search_console_api( $method, $endpoint, $body = null ) {
		$token = $this->get_google_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json; charset=utf-8';
			$args['body'] = wp_json_encode( $body );
		}

		$response = $this->perform_http_request( $method, $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'google_api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: API response message */
					__( 'Google Search Console returned HTTP %1$d. %2$s', 'wp-seo-blog-automater' ),
					$status_code,
					$this->extract_api_error_message( wp_remote_retrieve_body( $response ) )
				)
			);
		}

		return $response;
	}

	/**
	 * Perform an HTTP request using the best available transport in runtime or tests.
	 *
	 * @since 1.3.8
	 * @param string $method HTTP method.
	 * @param string $url Request URL.
	 * @param array  $args Request arguments.
	 * @return array|WP_Error
	 */
	private function perform_http_request( $method, $url, $args = array() ) {
		$method = strtoupper( $method );
		$args = wp_parse_args(
			$args,
			array(
				'timeout' => 20,
				'headers' => array(),
				'body'    => null,
			)
		);

		if ( is_array( $args['body'] ) ) {
			$content_type = isset( $args['headers']['Content-Type'] ) ? (string) $args['headers']['Content-Type'] : '';
			$args['body'] = false !== stripos( $content_type, 'application/json' )
				? wp_json_encode( $args['body'] )
				: http_build_query( $args['body'], '', '&' );
		}

		if ( function_exists( 'wp_remote_request' ) ) {
			$args['method'] = $method;
			return wp_remote_request( $url, $args );
		}

		if ( function_exists( 'wp_seo_automater_test_http_request' ) ) {
			return wp_seo_automater_test_http_request( $method, $url, $args );
		}

		if ( 'GET' === $method ) {
			return wp_remote_get( $url, $args );
		}

		if ( 'POST' === $method ) {
			return wp_remote_post( $url, $args );
		}

		return new WP_Error(
			'unsupported_http_method',
			sprintf(
				/* translators: %s: HTTP method */
				__( 'HTTP method %s is not supported in this environment.', 'wp-seo-blog-automater' ),
				$method
			)
		);
	}

	/**
	 * Extract a readable API error message from a JSON or plain-text response body.
	 *
	 * @since 1.3.8
	 * @param string $body Raw response body.
	 * @return string
	 */
	private function extract_api_error_message( $body ) {
		$decoded = json_decode( (string) $body, true );

		if ( is_array( $decoded ) ) {
			if ( ! empty( $decoded['error']['message'] ) ) {
				return $decoded['error']['message'];
			}

			if ( ! empty( $decoded['message'] ) ) {
				return $decoded['message'];
			}
		}

		$body = trim( wp_strip_all_tags( (string) $body ) );
		return '' !== $body ? $body : __( 'No additional error details were returned.', 'wp-seo-blog-automater' );
	}

	/**
	 * Base64 URL-safe encoding helper for JWT creation.
	 *
	 * @since 1.3.8
	 * @param string $value Raw input.
	 * @return string
	 */
	private function base64_url_encode( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	/**
	 * Generate a random ASCII token.
	 *
	 * @since 1.3.8
	 * @param int $length Token length.
	 * @return string
	 */
	private function generate_random_token( $length = 32 ) {
		$length = max( 8, (int) $length );
		$token = '';

		if ( function_exists( 'random_bytes' ) ) {
			$token = bin2hex( random_bytes( (int) ceil( $length / 2 ) ) );
		} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$token = bin2hex( openssl_random_pseudo_bytes( (int) ceil( $length / 2 ) ) );
		} else {
			$token = md5( uniqid( (string) mt_rand(), true ) ) . md5( (string) microtime( true ) );
		}

		return substr( preg_replace( '/[^A-Za-z0-9-]+/', '', $token ), 0, $length );
	}

	/**
	 * Fetch Unsplash image data for the generated image keywords.
	 *
	 * @since 1.3.0
	 * @param string $image_keywords Extracted image keywords.
	 * @return array URL, credit, and debug status.
	 */
	private function fetch_unsplash_image_data( $image_keywords, $context_title = '', $content = '', $excluded_photo_ids = array() ) {
		$unsplash_key = get_option( 'wp_seo_automater_unsplash_key', '' );
		$result = array(
			'url'           => '',
			'credit'        => '',
			'status'        => 'Not Attempted',
			'has_key'       => ! empty( $unsplash_key ),
			'photo_id'      => '',
			'query_source'  => '',
			'query_used'    => '',
			'queries_tried' => array(),
		);

		if ( empty( $unsplash_key ) ) {
			$result['status'] = 'Missing API Key';
			return $result;
		}

		if ( empty( $image_keywords ) ) {
			$result['status'] = 'No Keywords Extracted from AI';
			return $result;
		}

		$try_queries = function ( $queries, $source ) use ( &$result, $unsplash_key, $excluded_photo_ids ) {
			foreach ( $queries as $query ) {
				foreach ( array( 'landscape', '' ) as $orientation ) {
					$attempt_label = $query . ( '' !== $orientation ? ' [' . $orientation . ']' : ' [any]' );
					if ( in_array( $attempt_label, $result['queries_tried'], true ) ) {
						continue;
					}

					$result['queries_tried'][] = $attempt_label;
					$response = $this->search_unsplash_image( $query, $unsplash_key, $orientation );

					if ( is_wp_error( $response ) ) {
						$result['status'] = 'API Error: ' . $response->get_error_message();
						self::log_activity( 'Unsplash Error', $response->get_error_message(), 'error' );
						return $result;
					}

					if ( ! empty( $response['results'] ) && is_array( $response['results'] ) ) {
						foreach ( $response['results'] as $photo ) {
							if ( empty( $photo['id'] ) || in_array( $photo['id'], $excluded_photo_ids, true ) ) {
								continue;
							}

							$result['photo_id'] = $photo['id'];
							$result['url'] = $photo['urls']['regular'];
							$result['credit'] = 'Photo by ' . $photo['user']['name'] . ' on Unsplash';
							$result['status'] = 'Success';
							$result['query_source'] = $source;
							$result['query_used'] = $query;
							self::log_activity( 'Unsplash', "Found image for '$query' from {$source} using " . ( '' !== $orientation ? $orientation : 'any' ) . " orientation: {$result['url']}", 'success' );
							return $result;
						}
					}
				}
			}

			return null;
		};

		$stage_one = $this->build_unsplash_query_candidates( $image_keywords, '', false );
		$attempt_result = $try_queries( $stage_one, 'article_keywords' );
		if ( is_array( $attempt_result ) && ! empty( $attempt_result['url'] ) ) {
			return $attempt_result;
		}
		if ( is_array( $attempt_result ) && 0 === strpos( $attempt_result['status'], 'API Error:' ) ) {
			return $attempt_result;
		}

		$handler = new Gemini_API_Handler();
		$generated_queries = $handler->generate_image_search_keywords( $context_title, $image_keywords, $content );
		if ( is_wp_error( $generated_queries ) ) {
			self::log_activity( 'Unsplash', 'Gemini fallback image keyword generation failed: ' . $generated_queries->get_error_message(), 'warning' );
		} elseif ( ! empty( $generated_queries ) ) {
			$attempt_result = $try_queries( $generated_queries, 'gemini_fallback' );
			if ( is_array( $attempt_result ) && ! empty( $attempt_result['url'] ) ) {
				return $attempt_result;
			}
			if ( is_array( $attempt_result ) && 0 === strpos( $attempt_result['status'], 'API Error:' ) ) {
				return $attempt_result;
			}
		}

		$stage_three = $this->build_unsplash_query_candidates( $image_keywords, $context_title, true );
		$attempt_result = $try_queries( $stage_three, 'title_fallback' );
		if ( is_array( $attempt_result ) && ! empty( $attempt_result['url'] ) ) {
			return $attempt_result;
		}
		if ( is_array( $attempt_result ) && 0 === strpos( $attempt_result['status'], 'API Error:' ) ) {
			return $attempt_result;
		}

		$result['status'] = 'No Results from API';
		self::log_activity( 'Unsplash', "No images found after trying: " . implode( ', ', $result['queries_tried'] ), 'warning' );

		return $result;
	}

	/**
	 * Build progressively broader Unsplash search queries.
	 *
	 * @since 1.3.0
	 * @param string $image_keywords Extracted image keywords.
	 * @param string $context_title  Meta/title context from the generated article.
	 * @param bool   $include_generic Whether to include broad generic fallbacks.
	 * @return array Candidate queries ordered from specific to broad.
	 */
	private function build_unsplash_query_candidates( $image_keywords, $context_title = '', $include_generic = true ) {
		$normalized = strtolower( trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9\s-]+/i', ' ', $image_keywords ) ) ) );
		$normalized_context = strtolower( trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9\s-]+/i', ' ', $context_title ) ) ) );
		$queries = array();
		$eyewear_terms = array( 'glasses', 'eyeglasses', 'eyewear', 'frames', 'spectacles' );

		$add_query = static function ( $query ) use ( &$queries ) {
			$query = trim( preg_replace( '/\s+/', ' ', $query ) );
			if ( '' !== $query && ! in_array( $query, $queries, true ) ) {
				$queries[] = $query;
			}
		};

		$add_query( $normalized );

		$synonym_map = array(
			'glasses'    => 'eyeglasses',
			'eyeglasses' => 'glasses',
			'frames'     => 'eyeglasses',
			'eyewear'    => 'eyeglasses',
			'spectacles' => 'eyeglasses',
		);

		foreach ( $synonym_map as $from => $to ) {
			if ( preg_match( '/\b' . preg_quote( $from, '/' ) . '\b/i', $normalized ) ) {
				$add_query( preg_replace( '/\b' . preg_quote( $from, '/' ) . '\b/i', $to, $normalized ) );
			}
		}

		$words = preg_split( '/\s+/', $normalized );
		if ( is_array( $words ) && count( $words ) > 1 ) {
			$add_query( end( $words ) );
			$add_query( implode( ' ', array_slice( $words, -2 ) ) );
		}

		$is_eyewear_query = false;
		foreach ( $eyewear_terms as $term ) {
			if ( preg_match( '/\b' . preg_quote( $term, '/' ) . '\b/i', $normalized ) ) {
				$is_eyewear_query = true;
				break;
			}
		}

		if ( $is_eyewear_query && $include_generic ) {
			$add_query( 'luxury eyeglasses' );
			$add_query( 'designer eyewear' );
			$add_query( 'eyeglasses' );
		} elseif ( '' !== $normalized_context && $include_generic ) {
			$context_words = preg_split( '/\s+/', $normalized_context );
			if ( is_array( $context_words ) ) {
				foreach ( $context_words as $index => $word ) {
					if ( ! in_array( $word, $eyewear_terms, true ) ) {
						continue;
					}

					$start = max( 0, $index - 2 );
					$context_phrase = implode( ' ', array_slice( $context_words, $start, $index - $start + 1 ) );
					$add_query( $context_phrase );

					$start = max( 0, $index - 1 );
					$context_phrase = implode( ' ', array_slice( $context_words, $start, $index - $start + 1 ) );
					$add_query( $context_phrase );

					$add_query( $word );
				}
			}

			$add_query( 'luxury eyeglasses' );
			$add_query( 'designer eyewear' );
		}

		if ( $include_generic && ! in_array( 'eyeglasses', $queries, true ) ) {
			$add_query( 'eyeglasses' );
		}

		return array_slice( $queries, 0, 6 );
	}

	/**
	 * Search Unsplash for a single query/orientation combination.
	 *
	 * @since 1.3.0
	 * @param string $query Search query.
	 * @param string $unsplash_key Unsplash API key.
	 * @param string $orientation Orientation filter.
	 * @return array|WP_Error
	 */
	private function search_unsplash_image( $query, $unsplash_key, $orientation = 'landscape' ) {
		$endpoint = 'https://api.unsplash.com/search/photos';
		$params = array(
			'client_id' => $unsplash_key,
			'query'     => $query,
			'page'      => 1,
			'per_page'  => 8,
		);

		if ( '' !== $orientation ) {
			$params['orientation'] = $orientation;
		}

		$api_url = add_query_arg( $params, $endpoint );
		$response = wp_remote_get( $api_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Remove assistant-style epilogues that sometimes leak after the article/schema.
	 *
	 * The prompt requires the schema to be the final deliverable. If the model
	 * appends recap text, CMS instructions, or "new task" chatter afterward,
	 * strip it before metadata extraction and preview rendering.
	 *
	 * @since 1.2.5
	 * @param string $content Raw generated content from Gemini.
	 * @return string Sanitized generated content.
	 */
	private function sanitize_generated_output( $content ) {
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		$original_content = $content;

		$schema_patterns = array(
			'/<script\s+type="application\/ld\+json"[^>]*>.*?<\/script>/is',
			'/```json\s*.*?"@context".*?```/is',
		);

		foreach ( $schema_patterns as $pattern ) {
			if ( preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
				$match = $matches[0][0];
				$offset = $matches[0][1];
				$end_pos = $offset + strlen( $match );
				$trailing_content = trim( substr( $content, $end_pos ) );

				if ( '' !== $trailing_content ) {
					$content = substr( $content, 0, $end_pos );
					self::log_activity( 'Generation Cleanup', 'Removed trailing content found after the final schema block.', 'warning' );
				}

				break;
			}
		}

		$epilogue_patterns = array(
			'/\n+\s*The article, including the mandatory Call to Action\b/i',
			'/\n+\s*There is no further content needed for this piece\b/i',
			'/\n+\s*If you have a new topic, keyword cluster\b/i',
			'/\n+\s*\*?\s*As your Lead SEO Content Strategist\b/i',
			'/\n+\s*Please insert the following content immediately BEFORE\b/i',
		);

		$cutoff_pos = null;
		foreach ( $epilogue_patterns as $pattern ) {
			if ( preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
				$match_pos = $matches[0][1];
				if ( null === $cutoff_pos || $match_pos < $cutoff_pos ) {
					$cutoff_pos = $match_pos;
				}
			}
		}

		if ( null !== $cutoff_pos ) {
			$content = rtrim( substr( $content, 0, $cutoff_pos ) );
			self::log_activity( 'Generation Cleanup', 'Removed assistant commentary appended after the article body.', 'warning' );
		}

		if ( $content !== $original_content ) {
			$content = trim( $content );
		}

		return $content;
	}

	/**
	 * Simple Markdown to HTML converter.
	 * 
	 * Converts basic Markdown syntax to HTML for content display.
	 * Handles headers (H1-H3) and bold text.
	 *
	 * @since 1.0.0
	 * @param string $text Markdown text.
	 * @return string HTML text.
	 */
	private function markdown_to_html( $text ) {
		// Convert Headers
		$text = preg_replace( '/^# (.*?)$/m', '<h1>$1</h1>', $text );
		$text = preg_replace( '/^## (.*?)$/m', '<h2>$1</h2>', $text );
		$text = preg_replace( '/^### (.*?)$/m', '<h3>$1</h3>', $text );
		
		// Bold
		$text = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text );
		
		return $text;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_admin_menu() {
		// Use a simple Dashicon for the admin menu icon (no logo image)
		$icon_url = 'dashicons-chart-area';
		
		add_menu_page(
			__( 'WP SEO Blog Automater', 'wp-seo-blog-automater' ),
			__( 'Blog Automater', 'wp-seo-blog-automater' ),
			'manage_options',
			'wp-seo-automater',
			array( $this, 'display_generator_page' ),
			$icon_url,
			6
		);

		add_submenu_page(
			'wp-seo-automater',
			__( 'Generator', 'wp-seo-blog-automater' ),
			__( 'Generator', 'wp-seo-blog-automater' ),
			'manage_options',
			'wp-seo-automater',
			array( $this, 'display_generator_page' )
		);

		add_submenu_page(
			'wp-seo-automater',
			__( 'Settings', 'wp-seo-blog-automater' ),
			__( 'Settings', 'wp-seo-blog-automater' ),
			'manage_options',
			'wp-seo-automater-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'wp-seo-automater',
			__( 'Activity Logs', 'wp-seo-blog-automater' ),
			__( 'Logs', 'wp-seo-blog-automater' ),
			'manage_options',
			'wp-seo-automater-logs',
			array( $this, 'display_logs_page' )
		);

		add_submenu_page(
			'wp-seo-automater',
			__( 'System Info', 'wp-seo-blog-automater' ),
			__( 'System Info', 'wp-seo-blog-automater' ),
			'manage_options',
			'wp-seo-automater-system-info',
			array( $this, 'display_system_info_page' )
		);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'wp-seo-automater' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'wp-seo-automater-admin',
			WP_SEO_AUTOMATER_URL . 'admin/css/style.css',
			array(),
			WP_SEO_AUTOMATER_VERSION,
			'all'
		);
		
		// Enqueue Google Fonts (Inter)
		wp_enqueue_style(
			'google-fonts-inter',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
			array(),
			null
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'wp-seo-automater' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'wp-seo-automater-admin-js',
			WP_SEO_AUTOMATER_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			WP_SEO_AUTOMATER_VERSION,
			true
		);

		wp_localize_script( 'wp-seo-automater-admin-js', 'wpSeoAutomater', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wp_seo_automater_nonce' ),
			'admin_url' => admin_url(),
		));
	}

	/**
	 * Render the Settings Page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		$settings_notices = array();

		// Reset Prompt
		if ( isset( $_POST['wp_seo_automater_reset_prompt'] ) && check_admin_referer( 'wp_seo_automater_settings_save' ) ) {
			delete_option( 'wp_seo_automater_master_prompt' );
			self::log_activity( 'Settings', 'Master Prompt reset to default.', 'info' );
			$settings_notices[] = array(
				'type' => 'info',
				'message' => __( 'Master Prompt reset to default.', 'wp-seo-blog-automater' ),
			);
		}
		// Save settings if posted
		elseif ( ( isset( $_POST['wp_seo_automater_save_settings'] ) || isset( $_POST['wp_seo_automater_generate_indexnow_key'] ) ) && check_admin_referer( 'wp_seo_automater_settings_save' ) ) {
			$generated_indexnow_key = false;
			$old_service_account = $this->get_google_service_account_credentials();
			$old_service_account_email = is_wp_error( $old_service_account ) ? '' : (string) $old_service_account['client_email'];

			update_option( 'wp_seo_automater_gemini_key', sanitize_text_field( wp_unslash( $_POST['gemini_api_key'] ) ) );
			update_option( 'wp_seo_automater_gemini_model', sanitize_text_field( wp_unslash( $_POST['gemini_model_id'] ) ) );
			update_option( 'wp_seo_automater_unsplash_key', sanitize_text_field( wp_unslash( $_POST['unsplash_key'] ) ) );
			update_option( 'wp_seo_automater_seo_plugin', sanitize_text_field( wp_unslash( $_POST['seo_plugin'] ) ) );
			update_option( 'wp_seo_automater_master_prompt', wp_kses_post( wp_unslash( $_POST['master_prompt'] ) ) );

			$indexnow_key = isset( $_POST['indexnow_key'] ) ? sanitize_text_field( wp_unslash( $_POST['indexnow_key'] ) ) : '';
			if ( isset( $_POST['wp_seo_automater_generate_indexnow_key'] ) ) {
				$indexnow_key = $this->generate_random_token( 32 );
				$generated_indexnow_key = true;
			}

			if ( '' === $indexnow_key ) {
				update_option( 'wp_seo_automater_indexnow_key', '' );
			} elseif ( preg_match( '/^[A-Za-z0-9-]{8,128}$/', $indexnow_key ) ) {
				update_option( 'wp_seo_automater_indexnow_key', $indexnow_key );
			} else {
				$settings_notices[] = array(
					'type' => 'error',
					'message' => __( 'IndexNow key was not saved. Use 8-128 characters containing letters, numbers, or hyphens only.', 'wp-seo-blog-automater' ),
				);
			}

			$google_property = isset( $_POST['google_property'] ) ? sanitize_text_field( wp_unslash( $_POST['google_property'] ) ) : '';
			$google_property = trim( $google_property );

			if ( '' === $google_property ) {
				update_option( 'wp_seo_automater_google_property', '' );
			} elseif ( 0 === strpos( $google_property, 'sc-domain:' ) ) {
				if ( preg_match( '/^sc-domain:[A-Za-z0-9.-]+$/', $google_property ) ) {
					update_option( 'wp_seo_automater_google_property', $google_property );
				} else {
					$settings_notices[] = array(
						'type' => 'error',
						'message' => __( 'Google Search Console property was not saved. Domain properties must look like sc-domain:example.com.', 'wp-seo-blog-automater' ),
					);
				}
			} else {
				$property_url = esc_url_raw( $google_property );
				if ( ! empty( $property_url ) ) {
					update_option( 'wp_seo_automater_google_property', trailingslashit( $property_url ) );
				} else {
					$settings_notices[] = array(
						'type' => 'error',
						'message' => __( 'Google Search Console property was not saved. Use a full URL prefix such as https://example.com/ or a sc-domain property.', 'wp-seo-blog-automater' ),
					);
				}
			}

			$service_account_json = isset( $_POST['google_service_account_json'] ) ? trim( wp_unslash( $_POST['google_service_account_json'] ) ) : '';
			if ( '' === $service_account_json ) {
				update_option( 'wp_seo_automater_google_service_account_json', '' );
			} else {
				$decoded_service_account = json_decode( $service_account_json, true );
				if ( ! is_array( $decoded_service_account ) || empty( $decoded_service_account['client_email'] ) || empty( $decoded_service_account['private_key'] ) ) {
					$settings_notices[] = array(
						'type' => 'error',
						'message' => __( 'Google service account JSON was not saved. It must be valid JSON and include client_email plus private_key.', 'wp-seo-blog-automater' ),
					);
				} else {
					update_option(
						'wp_seo_automater_google_service_account_json',
						wp_json_encode( $decoded_service_account, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
					);
				}
			}

			$sitemap_input = isset( $_POST['google_sitemap_urls'] ) ? trim( wp_unslash( $_POST['google_sitemap_urls'] ) ) : '';
			$valid_sitemaps = array();
			$invalid_sitemaps = array();

			if ( '' !== $sitemap_input ) {
				$lines = preg_split( '/\r\n|\r|\n/', $sitemap_input );
				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( '' === $line ) {
						continue;
					}

					$sanitized_url = esc_url_raw( $line );
					if ( ! empty( $sanitized_url ) ) {
						$valid_sitemaps[] = $sanitized_url;
					} else {
						$invalid_sitemaps[] = $line;
					}
				}
			}

			update_option( 'wp_seo_automater_google_sitemap_urls', implode( "\n", array_unique( $valid_sitemaps ) ) );

			if ( ! empty( $invalid_sitemaps ) ) {
				$settings_notices[] = array(
					'type' => 'warning',
					'message' => sprintf(
						/* translators: %s: invalid sitemap URLs */
						__( 'Some sitemap URLs were ignored because they were invalid: %s', 'wp-seo-blog-automater' ),
						implode( ', ', array_slice( $invalid_sitemaps, 0, 5 ) )
					),
				);
			}

			$new_service_account = $this->get_google_service_account_credentials();
			$new_service_account_email = is_wp_error( $new_service_account ) ? '' : (string) $new_service_account['client_email'];

			if ( ! empty( $old_service_account_email ) ) {
				delete_transient( 'wp_seo_automater_google_token_' . md5( $old_service_account_email ) );
			}
			if ( ! empty( $new_service_account_email ) ) {
				delete_transient( 'wp_seo_automater_google_token_' . md5( $new_service_account_email ) );
			}

			self::log_activity( 'Settings', 'Plugin settings updated.', 'success' );
			$settings_notices[] = array(
				'type' => 'success',
				'message' => __( 'Settings saved successfully.', 'wp-seo-blog-automater' ),
			);

			if ( $generated_indexnow_key && get_option( 'wp_seo_automater_indexnow_key', '' ) === $indexnow_key ) {
				$settings_notices[] = array(
					'type' => 'info',
					'message' => __( 'A new IndexNow key was generated and saved. The plugin now serves the verification file automatically.', 'wp-seo-blog-automater' ),
				);
			}
		}

		$api_key = get_option( 'wp_seo_automater_gemini_key', '' );
		$unsplash_key = get_option( 'wp_seo_automater_unsplash_key', '' );
		$model_id = get_option( 'wp_seo_automater_gemini_model', 'gemini-pro-latest' );
		$seo_plugin = get_option( 'wp_seo_automater_seo_plugin', 'auto' );
		$master_prompt = get_option( 'wp_seo_automater_master_prompt', $this->get_default_master_prompt() );
		$indexnow_key = get_option( 'wp_seo_automater_indexnow_key', '' );
		$indexnow_key_file_url = $this->get_indexnow_key_file_url();
		$google_property = get_option( 'wp_seo_automater_google_property', '' );
		if ( '' === $google_property ) {
			$google_property = home_url( '/' );
		}
		$google_service_account_json = get_option( 'wp_seo_automater_google_service_account_json', '' );
		$google_sitemap_urls = get_option( 'wp_seo_automater_google_sitemap_urls', '' );
		$google_service_account_email = $this->get_google_service_account_email();

		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/settings-display.php';
	}

	/**
	 * Render the Generator Page.
	 *
	 * @since 1.0.0
	 */
	public function display_generator_page() {
		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/generator-display.php';
	}

	/**
	 * Render the Logs Page.
	 *
	 * @since 1.0.0
	 */
	public function display_logs_page() {
		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/logs-display.php';
	}

	/**
	 * Render the System Info Page.
	 *
	 * @since 1.0.6
	 */
	public function display_system_info_page() {
		include_once WP_SEO_AUTOMATER_PATH . 'admin/partials/system-info-display.php';
	}

	/**
	 * Get the default Master Prompt.
	 * 
	 * Returns the default system prompt for content generation.
	 * Can be customized by users through the settings page.
	 *
	 * @since 1.0.0
	 * @return string Default master prompt.
	 */
	private function get_default_master_prompt() {
		return "Role & Persona:
You are the Lead SEO Content Strategist & Senior Medical Copywriter for Lumiere Optique. Your goal is to dominate the #1 Google ranking position for targeted keywords in Scottsdale, AZ. You are writing for high-net-worth individuals who value both medical precision and luxury fashion.

Voice: Professional, sophisticated, knowledgeable, and reassuring.

Perspective: You write as the Lumiere Optique Brand (we/our/the team). You do not write as a specific doctor and never make first-person medical claims (\"I recommend\").

Core Directives (Non-Negotiable)
1. Website Grounding (Mandatory Start)

Action: You MUST visit and analyze https://lumiereoptique.com/ before writing.

Purpose: Absorb the exact tone, service details, and brand philosophy. Use only this site as your source of truth for services and brand identity.

2. Content Depth & \"No-Fluff\" Strategy (Target: 2,300+ Words)

The Goal: Create the most comprehensive resource on the internet.

The \"Anti-Fluff\" Rule: Do not repeat points to hit the word count. Instead, go deeper:

Technical Depth: Explain why a frame material fails (e.g., \"titanium micro-fractures\") or how a diagnostic tool works.

Featured Snippet Optimization: You MUST include distinct bulleted or numbered lists within the body content (e.g., \"5 Common DIY Mistakes,\" \"Symptoms of X,\" \"Benefits of Y\"). Google loves these for \"Position 0\" rankings.

Commercial Intent: If the keyword is commercial, prioritize conversion (booking) but justify the article length by explaining the process and experience in detail.

3. Medical Responsibility (YMYL)

Safety First: Do not diagnose, promise results, or guarantee cures.

Language: Use qualifiers like \"may help,\" \"often improves,\" or \"clinical studies suggest.\"

Urgency: Always include guidance on when to seek urgent medical care.

4. E-E-A-T & Local SEO Integration

Expertise: Showcase deep knowledge of luxury brands (Cartier, Chanel, Tom Ford, Lindberg) and specific medical services verified on the website.

Hyper-Local Context: Do not just say \"Scottsdale.\" Mention specific environmental factors relevant to eye health, such as:

Arizona's intense UV index/glare.

Dryness from air conditioning or desert heat.

Dust during monsoon season.

Lifestyle needs: Golfing, driving, or resort living.

Content Creation Checklist
Phase 1: Meta Data (Output First)

Meta Title: <60 chars. Front-load keyword. Compelling & Scottsdale-specific.

Meta Description: ~155-160 chars. Keyword + Value Prop + Phone Number in text.

Slug: lowercase-hyphenated-keyword.

Image Search Keywords: 2-3 visual nouns describing the ideal hero image (e.g., \"titanium glasses luxury table\").

Phase 2: The Article (Structure & Formatting)

H1 Title: Catchy, includes primary keyword. Only one H1.

Introduction: Hook the reader immediately. Define the problem and position Lumiere Optique as the solution.

Keyword Strategy:

Primary: Natural placement in H1, first paragraph, and at least one H2.

LSI/Contextual: Use terms like 'eye doctor Scottsdale', 'optometrist', 'bespoke eyewear', 'comprehensive eye exam'.

Brand Mentions: Naturally reference 'Lumiere Optique' and specific verified brands.

Body Content:

Hierarchy: Logical H2 and H3 headers.

Readability: Short, elegant paragraphs. Single line spacing only.

Bolding: Use bold text sparingly for key takeaways/lists only. Do not bold full sentences.

Internal Linking: Provide 3-6 internal link suggestions (anchor text only) to relevant service pages.

Mandatory FAQ Section: Place at the end of the body (H2: \"Frequently Asked Questions\"). Include 5 distinct Q&As.

Phase 3: The Close

Tone Check: Ensure the voice is professional yet warm.

Mandatory CTA: The final paragraph must exactly match this format:

[Transition text encouraging health/style priority...] Book Your Appointment: (480) 699-1885 | Visit Us in Scottsdale, AZ

Phase 4: Technical Deliverables

Output Purity: No conversational filler. Just the content.

Final Boundary Rule: The FAQPage JSON-LD schema is the final output. After the schema closes, output nothing else.

Forbidden Post-Article Notes: Do not add recap text, \"the article was completed above\" notices, \"if you have a new topic\" offers, or CMS insertion instructions.

Schema Markup: Immediately after the CTA, provide a valid JSON-LD FAQPage Schema script block.

CRITICAL: The Schema Question/Answer text must match the on-page FAQ text word-for-word. Do not rewrite it.

Output Management Safety Rule
If you reach your output limit before completing the article
Stop at a natural paragraph break and write
[PAUSING FOR CONTINUATION]
Wait for the user to say Continue.
Do not rush the ending.
Do not compress the FAQ.
Do not truncate the schema.

Commercial Intent Handling Clarity
For commercial keywords, you must include
What happens during the visit
Who the service is best for
What outcomes can realistically be expected
When to book
Do not mention pricing unless verified on the website.

Bold Usage Control
Bold text is for key takeaways only.
Do not bold full sentences repeatedly.
Do not bold for visual noise.";
	}
}
