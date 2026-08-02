<?php
/**
 * Media alt-text generation service for WP SEO Blog Automater.
 *
 * @package WP_SEO_Blog_Automater
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Generates accessible, SEO-safe image text and applies it to attachments.
 *
 * The original upload is never changed. Large or unsupported source files are
 * converted to a temporary JPEG only for the Gemini analysis request.
 */
class WP_SEO_Automater_Media_Alt_Text {

	/**
	 * The stable multimodal model used for image-to-text work.
	 *
	 * @var string
	 */
	const DEFAULT_MODEL = 'gemini-2.5-flash';

	/**
	 * Keep the base64 request comfortably below Gemini's inline-data limit.
	 *
	 * @var int
	 */
	const MAX_INLINE_IMAGE_BYTES = 12000000;

	/**
	 * Bound streamed downloads for media stored outside the local uploads path.
	 *
	 * @var int
	 */
	const MAX_REMOTE_DOWNLOAD_BYTES = 25000000;

	/**
	 * Limit temporary analysis copies to a useful, cost-conscious resolution.
	 *
	 * @var int
	 */
	const MAX_ANALYSIS_DIMENSION = 1600;

	/**
	 * Avoid concurrent duplicate analysis of the same attachment.
	 *
	 * @var int
	 */
	const LOCK_TTL = 600;

	/**
	 * Generate and persist text for an image attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|WP_Error Updated metadata or a meaningful failure.
	 */
	public function generate_and_apply( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$attachment    = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new WP_Error( 'invalid_attachment', __( 'The selected media item no longer exists.', 'wp-seo-blog-automater' ) );
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new WP_Error( 'not_an_image', __( 'Only image attachments can receive AI image text.', 'wp-seo-blog-automater' ) );
		}

		$lock_key = 'wp_seo_automater_image_alt_lock_' . $attachment_id;
		if ( false !== get_transient( $lock_key ) ) {
			return new WP_Error( 'image_generation_in_progress', __( 'This image is already being analyzed. Wait for that request to finish, then try again.', 'wp-seo-blog-automater' ) );
		}

		set_transient( $lock_key, 1, self::LOCK_TTL );
		$temporary_files = array();

		try {
			$prepared = $this->prepare_image_for_analysis( $attachment_id, $temporary_files );
			if ( is_wp_error( $prepared ) ) {
				return $prepared;
			}

			$model   = $this->get_model_id();
			$handler = new Gemini_API_Handler( null, $model );
			$alt_text = $handler->generate_image_alt_text(
				$prepared['path'],
				$prepared['mime_type'],
				$this->build_image_context( $attachment )
			);

			if ( is_wp_error( $alt_text ) ) {
				return $alt_text;
			}

			$updated = wp_update_post(
				array(
					'ID'           => $attachment_id,
					'post_excerpt' => $alt_text,
					'post_content' => $alt_text,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
			update_post_meta( $attachment_id, '_wp_seo_automater_ai_image_text_model', $model );
			update_post_meta( $attachment_id, '_wp_seo_automater_ai_image_text_generated_at', current_time( 'mysql' ) );

			return array(
				'attachment_id' => $attachment_id,
				'alt_text'      => $alt_text,
				'caption'       => $alt_text,
				'description'   => $alt_text,
				'model'         => $model,
			);
		} finally {
			foreach ( array_unique( $temporary_files ) as $temporary_file ) {
				if ( is_string( $temporary_file ) && is_file( $temporary_file ) ) {
					$this->delete_temporary_file( $temporary_file );
				}
			}

			delete_transient( $lock_key );
		}
	}

	/**
	 * Get the configured image-analysis model, with a safe stable fallback.
	 *
	 * @return string
	 */
	public function get_model_id() {
		$model = trim( (string) get_option( 'wp_seo_automater_image_alt_model', self::DEFAULT_MODEL ) );

		if ( ! preg_match( '/^[A-Za-z0-9._-]{1,100}$/', $model ) ) {
			$model = self::DEFAULT_MODEL;
		}

		$model = trim( (string) apply_filters( 'wp_seo_automater_image_alt_model', $model ) );

		return preg_match( '/^[A-Za-z0-9._-]{1,100}$/', $model ) ? $model : self::DEFAULT_MODEL;
	}

	/**
	 * Build the non-image context supplied to Gemini.
	 *
	 * @param WP_Post $attachment Attachment post.
	 * @return array
	 */
	private function build_image_context( $attachment ) {
		$context = array(
			'site_name'     => get_bloginfo( 'name' ),
			'site_tagline'  => get_bloginfo( 'description' ),
			'site_url'      => home_url( '/' ),
			'brand_context' => $this->truncate_text( get_option( 'wp_seo_automater_image_alt_site_context', '' ), 1200 ),
			'attachment_title' => get_the_title( $attachment ),
		);

		if ( ! empty( $attachment->post_parent ) ) {
			$parent = get_post( $attachment->post_parent );
			if ( $parent ) {
				$parent_context = ! empty( $parent->post_excerpt ) ? $parent->post_excerpt : $parent->post_content;
				$context['parent_title']   = get_the_title( $parent );
				$context['parent_context'] = $this->truncate_text( $parent_context, 900 );
			}
		}

		return $context;
	}

	/**
	 * Resolve the image and create a compact analysis derivative if necessary.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $temporary_files Mutable list of temp files for cleanup.
	 * @return array|WP_Error
	 */
	private function prepare_image_for_analysis( $attachment_id, &$temporary_files ) {
		$source = $this->resolve_attachment_file( $attachment_id, $temporary_files );
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		$source_path = $source['path'];
		$source_size = filesize( $source_path );
		$image_size  = @getimagesize( $source_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$mime_type   = is_array( $image_size ) && ! empty( $image_size['mime'] ) ? $image_size['mime'] : wp_get_image_mime( $source_path );
		$width       = is_array( $image_size ) && isset( $image_size[0] ) ? (int) $image_size[0] : 0;
		$height      = is_array( $image_size ) && isset( $image_size[1] ) ? (int) $image_size[1] : 0;

		if ( empty( $mime_type ) || 0 !== strpos( $mime_type, 'image/' ) || false === $source_size ) {
			return new WP_Error( 'invalid_image_file', __( 'WordPress could not read this attachment as a valid image.', 'wp-seo-blog-automater' ) );
		}

		$supported_mimes = array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' );
		$requires_derivative = ! in_array( $mime_type, $supported_mimes, true )
			|| $source_size > self::MAX_INLINE_IMAGE_BYTES
			|| max( $width, $height ) > self::MAX_ANALYSIS_DIMENSION;

		if ( ! $requires_derivative ) {
			return array(
				'path'      => $source_path,
				'mime_type' => $mime_type,
			);
		}

		$derivative = $this->create_analysis_derivative( $source_path, $temporary_files );
		if ( is_wp_error( $derivative ) ) {
			return $derivative;
		}

		return $derivative;
	}

	/**
	 * Resolve a local attachment path, or safely retrieve an offloaded image.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $temporary_files Mutable list of temp files for cleanup.
	 * @return array|WP_Error
	 */
	private function resolve_attachment_file( $attachment_id, &$temporary_files ) {
		$file_path = get_attached_file( $attachment_id );
		if ( is_string( $file_path ) && is_readable( $file_path ) ) {
			return array( 'path' => $file_path );
		}

		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( empty( $attachment_url ) || ! $this->is_safe_remote_image_url( $attachment_url ) ) {
			return new WP_Error( 'missing_attachment_file', __( 'The image file is not available locally and could not be retrieved safely from its media URL.', 'wp-seo-blog-automater' ) );
		}

		$temp_file = wp_tempnam( 'wp-seo-automater-image' );
		if ( ! $temp_file ) {
			return new WP_Error( 'attachment_temp_file_failed', __( 'WordPress could not create a temporary image file for analysis.', 'wp-seo-blog-automater' ) );
		}

		$temporary_files[] = $temp_file;
		$response          = wp_safe_remote_get(
			$attachment_url,
			array(
				'timeout'             => 30,
				'redirection'         => 3,
				'limit_response_size' => self::MAX_REMOTE_DOWNLOAD_BYTES,
				'stream'              => true,
				'filename'            => $temp_file,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'attachment_download_failed', $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$content_length = absint( wp_remote_retrieve_header( $response, 'content-length' ) );
		$file_size      = is_file( $temp_file ) ? filesize( $temp_file ) : false;
		if ( $status_code < 200 || $status_code >= 300 || false === $file_size || 0 === $file_size ) {
			return new WP_Error( 'attachment_download_failed', __( 'The offloaded media file could not be downloaded for analysis.', 'wp-seo-blog-automater' ) );
		}

		if ( $content_length > self::MAX_REMOTE_DOWNLOAD_BYTES || $file_size >= self::MAX_REMOTE_DOWNLOAD_BYTES ) {
			return new WP_Error( 'offloaded_image_too_large', __( 'This offloaded image is too large to download safely for Gemini analysis. Optimize the source image first.', 'wp-seo-blog-automater' ) );
		}

		return array( 'path' => $temp_file );
	}

	/**
	 * Create a temporary JPEG small enough for safe inline Gemini input.
	 *
	 * @param string $source_path Path to the attachment source image.
	 * @param array  $temporary_files Mutable list of temp files for cleanup.
	 * @return array|WP_Error
	 */
	private function create_analysis_derivative( $source_path, &$temporary_files ) {
		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$targets = array(
			array( 'dimension' => self::MAX_ANALYSIS_DIMENSION, 'quality' => 82 ),
			array( 'dimension' => 1200, 'quality' => 76 ),
			array( 'dimension' => 900, 'quality' => 70 ),
		);

		foreach ( $targets as $target ) {
			$editor = wp_get_image_editor( $source_path );
			if ( is_wp_error( $editor ) ) {
				return new WP_Error( 'image_editor_unavailable', __( 'WordPress could not prepare this image for Gemini analysis. Install or enable an image editor such as Imagick or GD.', 'wp-seo-blog-automater' ) );
			}

			$size = $editor->get_size();
			if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) && max( $size['width'], $size['height'] ) > $target['dimension'] ) {
				$resized = $editor->resize( $target['dimension'], $target['dimension'], false );
				if ( is_wp_error( $resized ) ) {
					continue;
				}
			}

			$editor->set_quality( $target['quality'] );
			$temp_file = wp_tempnam( 'wp-seo-automater-image-analysis.jpg' );
			$saved     = $editor->save( $temp_file, 'image/jpeg' );

			if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_readable( $saved['path'] ) ) {
				if ( $temp_file && is_file( $temp_file ) ) {
					$this->delete_temporary_file( $temp_file );
				}
				continue;
			}

			$temporary_files[] = $saved['path'];
			if ( filesize( $saved['path'] ) <= self::MAX_INLINE_IMAGE_BYTES ) {
				return array(
					'path'      => $saved['path'],
					'mime_type' => 'image/jpeg',
				);
			}
		}

		return new WP_Error( 'image_too_large', __( 'This image could not be reduced to a safe size for Gemini analysis. Try optimizing the original upload first.', 'wp-seo-blog-automater' ) );
	}

	/**
	 * Validate a remote fallback URL without allowing non-web protocols.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private function is_safe_remote_image_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		return ! function_exists( 'wp_http_validate_url' ) || (bool) wp_http_validate_url( $url );
	}

	/**
	 * Normalize context without sending unbounded post body content to Gemini.
	 *
	 * @param string $text Source text.
	 * @param int    $limit Maximum characters.
	 * @return string
	 */
	private function truncate_text( $text, $limit ) {
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $limit );
		}

		return substr( $text, 0, $limit );
	}

	/**
	 * Remove a temporary analysis file without depending on admin file helpers.
	 *
	 * @param string $file_path Temporary file path.
	 * @return void
	 */
	private function delete_temporary_file( $file_path ) {
		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $file_path );
			return;
		}

		unlink( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}
}
