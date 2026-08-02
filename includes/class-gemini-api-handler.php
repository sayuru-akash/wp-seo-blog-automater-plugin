<?php
/**
 * Gemini API Handler Class
 *
 * Handles all communication with Google's Gemini AI API.
 * Manages article generation, continuation handling, and API requests.
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
 * Handles Gemini API interactions.
 *
 * @since 1.0.0
 */
class Gemini_API_Handler {

	/**
	 * Stable model used for multimodal image-to-text analysis.
	 *
	 * @since 1.4.0
	 * @var string
	 */
	const DEFAULT_IMAGE_ALT_MODEL = 'gemini-2.5-flash';

	/**
	 * Default timeout for article generation requests, in seconds.
	 *
	 * @since 1.3.15
	 * @var int
	 */
	const DEFAULT_GENERATION_TIMEOUT = 300;

	/**
	 * Gemini API Key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_key;

	/**
	 * Gemini API base URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * Gemini model ID to use.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $model_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string|null $api_key  Optional. API key to use.
	 * @param string|null $model_id Optional. Model ID to use.
	 */
	public function __construct( $api_key = null, $model_id = null ) {
		$this->api_key = $api_key ? $api_key : get_option( 'wp_seo_automater_gemini_key', '' );
		$this->model_id = $model_id ? $model_id : get_option( 'wp_seo_automater_gemini_model', self::DEFAULT_IMAGE_ALT_MODEL );
	}

	/**
	 * Generate concise, factual image text from a local image file.
	 *
	 * @since 1.4.0
	 * @param string $image_path Local path to a compact analysis image.
	 * @param string $mime_type Image MIME type.
	 * @param array  $context Website and attachment context.
	 * @return string|WP_Error
	 */
	public function generate_image_alt_text( $image_path, $mime_type, $context = array() ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_key', __( 'Gemini API Key is missing. Please configure it in settings.', 'wp-seo-blog-automater' ) );
		}

		if ( ! is_string( $image_path ) || ! is_readable( $image_path ) ) {
			return new WP_Error( 'missing_image_file', __( 'The image file is no longer available for analysis.', 'wp-seo-blog-automater' ) );
		}

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif' );
		$mime_type     = strtolower( trim( (string) $mime_type ) );
		if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
			return new WP_Error( 'unsupported_image_type', __( 'This image type cannot be sent to Gemini for analysis.', 'wp-seo-blog-automater' ) );
		}

		$image_data = file_get_contents( $image_path );
		if ( false === $image_data || '' === $image_data ) {
			return new WP_Error( 'image_read_failed', __( 'WordPress could not read the image data for Gemini analysis.', 'wp-seo-blog-automater' ) );
		}

		$response = $this->make_image_api_request(
			$this->get_image_alt_text_prompt( $context ),
			$image_data,
			$mime_type
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$alt_text = self::normalize_image_alt_text( $this->extract_text_from_response( $response ) );
		if ( is_wp_error( $alt_text ) ) {
			return $alt_text;
		}

		return $alt_text;
	}

	/**
	 * Build the instruction set for factual, accessible, SEO-safe image text.
	 *
	 * @since 1.4.0
	 * @param array $context Website and attachment context.
	 * @return string
	 */
	protected function get_image_alt_text_prompt( $context ) {
		$context = is_array( $context ) ? $context : array();
		$lines   = array();

		foreach ( array( 'site_name', 'site_tagline', 'site_url', 'brand_context', 'attachment_title', 'parent_title', 'parent_context' ) as $key ) {
			if ( ! empty( $context[ $key ] ) && is_scalar( $context[ $key ] ) ) {
				$lines[] = str_replace( '_', ' ', $key ) . ': ' . trim( (string) $context[ $key ] );
			}
		}

		return "You are the senior accessibility editor and image SEO specialist for a WordPress website. Analyze the supplied image itself first, then use the website context only to disambiguate visible subjects, products, locations, or branding.\n\n"
			. "Return exactly one JSON object in this shape: {\"alt_text\":\"...\"}. Do not return Markdown, a code fence, an explanation, or additional keys.\n\n"
			. "Write one concise, natural-language alternative text suitable for a WordPress image. It will be copied unchanged into the image alt text, caption, and description fields.\n\n"
			. "Quality rules:\n"
			. "- Describe the meaningful visible subject, action, setting, and any essential visible text.\n"
			. "- Be factual. Do not invent people, locations, products, benefits, events, or brand names that are not clearly visible or supported by the supplied site context.\n"
			. "- Use site/brand context to identify a logo or product only when it is clearly relevant; never guess a logo from context alone.\n"
			. "- Prefer 8 to 18 words, normally 60 to 125 characters, and never exceed 125 characters.\n"
			. "- Avoid filler such as 'image of', 'picture of', 'photo of', file names, camera details, hashtags, quotations, HTML, and keyword stuffing.\n"
			. "- Do not repeat the surrounding article title unless it genuinely clarifies the visible image.\n"
			. "- Use the language implied by the website context when clear; otherwise use English.\n"
			. "- If the image is a logo, describe the visible logo and brand only when the brand is legible or contextually confirmed.\n"
			. "- If the image is decorative but still selected for generation, provide the most useful concise visual description rather than empty text.\n\n"
			. "Website and attachment context:\n"
			. ( empty( $lines ) ? 'No additional context was provided.' : implode( "\n", $lines ) );
	}

	/**
	 * Send an image-plus-text request to Gemini's generateContent endpoint.
	 *
	 * @since 1.4.0
	 * @param string $prompt Instructions and context.
	 * @param string $image_data Raw image bytes.
	 * @param string $mime_type Image MIME type.
	 * @return array|WP_Error
	 */
	protected function make_image_api_request( $prompt, $image_data, $mime_type ) {
		$url = $this->base_url . $this->model_id . ':generateContent?key=' . $this->api_key;
		$body = array(
			'contents' => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array( 'text' => $prompt ),
						array(
							'inline_data' => array(
								'mime_type' => $mime_type,
								'data'      => base64_encode( $image_data ),
							),
						),
					),
				),
			),
			'generationConfig' => array(
				'temperature'      => 0.2,
				'maxOutputTokens'  => 128,
				'responseMimeType' => 'application/json',
				'responseSchema'   => array(
					'type'       => 'OBJECT',
					'properties' => array(
						'alt_text' => array(
							'type'        => 'STRING',
							'description' => 'A concise factual image alt text.',
						),
					),
					'required'   => array( 'alt_text' ),
				),
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => max( 30, (int) apply_filters( 'wp_seo_automater_image_alt_timeout', 90 ) ),
				'method'  => 'POST',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$body_error = wp_remote_retrieve_body( $response );
			return new WP_Error( 'image_alt_api_error', sprintf( __( 'Gemini image analysis failed (HTTP %1$d): %2$s', 'wp-seo-blog-automater' ), $code, $body_error ) );
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'image_alt_invalid_response', __( 'Gemini returned an unreadable image-analysis response.', 'wp-seo-blog-automater' ) );
		}

		return $decoded;
	}

	/**
	 * Extract a schema-valid model response into a usable WordPress field.
	 *
	 * @since 1.4.0
	 * @param string $response_text Gemini text response.
	 * @return string|WP_Error
	 */
	public static function normalize_image_alt_text( $response_text ) {
		$response_text = trim( (string) $response_text );
		if ( preg_match( '/^```(?:json)?\s*(.*?)\s*```$/is', $response_text, $matches ) ) {
			$response_text = trim( $matches[1] );
		}

		$decoded = json_decode( $response_text, true );
		if ( ! is_array( $decoded ) ) {
			$json_start = strpos( $response_text, '{' );
			$json_end   = strrpos( $response_text, '}' );
			if ( false !== $json_start && false !== $json_end && $json_end > $json_start ) {
				$decoded = json_decode( substr( $response_text, $json_start, $json_end - $json_start + 1 ), true );
			}
		}

		if ( ! is_array( $decoded ) || ! isset( $decoded['alt_text'] ) || ! is_string( $decoded['alt_text'] ) ) {
			return new WP_Error( 'invalid_image_alt_response', __( 'Gemini returned image text in an invalid format. No image metadata was changed; please try again.', 'wp-seo-blog-automater' ) );
		}

		$response_text = $decoded['alt_text'];

		$response_text = strip_tags( (string) $response_text );
		$response_text = trim( preg_replace( '/\s+/', ' ', $response_text ), " \t\n\r\0\x0B\"'`" );

		if ( function_exists( 'sanitize_text_field' ) ) {
			$response_text = sanitize_text_field( $response_text );
		}

		if ( '' === $response_text ) {
			return new WP_Error( 'empty_image_alt_text', __( 'Gemini did not return usable image text. Please try again.', 'wp-seo-blog-automater' ) );
		}

		if ( function_exists( 'mb_substr' ) ) {
			$response_text = mb_substr( $response_text, 0, 125 );
		} else {
			$response_text = substr( $response_text, 0, 125 );
		}

		return trim( $response_text );
	}


	/**
	 * Main function to generate article content.
	 *
	 * Handles the full article generation flow including initial request,
	 * continuation handling for long content, and response processing.
	 *
	 * @since 1.0.0
	 * @param string $title         Article title/topic.
	 * @param string $keywords      Target keywords.
	 * @param string $master_prompt The system/master prompt with instructions.
	 * @return string|WP_Error Generated article content or WP_Error on failure.
	 */
	public function generate_article( $title, $keywords, $master_prompt ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_key', __( 'Gemini API Key is missing. Please configure it in settings.', 'wp-seo-blog-automater' ) );
		}

		$full_prompt = $this->construct_prompt( $title, $keywords, $master_prompt );
		
		// Initial Call
		WP_SEO_Automater_Admin::log_activity( 'API Request', "Initial generation request for: $title", 'info' );
		$response = $this->make_api_request( $full_prompt );

		if ( is_wp_error( $response ) ) {
			WP_SEO_Automater_Admin::log_activity( 'API Error', $response->get_error_message(), 'error' );
			return $response;
		}

		$generated_text = $this->extract_text_from_response( $response );
		$finish_reason = $this->get_finish_reason_from_response( $response );
		WP_SEO_Automater_Admin::log_activity( 'API Response', "Received initial chunk (" . strlen($generated_text) . " chars, finish reason: " . ( $finish_reason ? $finish_reason : 'unknown' ) . ')', 'success' );

		// Check for continuation trigger
		$max_loops = 3; 
		$loop_count = 0;
		$history = [
			['role' => 'user', 'parts' => [['text' => $full_prompt]]],
			['role' => 'model', 'parts' => [['text' => $generated_text]]]
		];

		$should_continue = $this->should_continue_generation( $generated_text, $response );

		while ( $should_continue && $loop_count < $max_loops ) {
			$loop_count++;
			WP_SEO_Automater_Admin::log_activity( 'Continuation', "Loop #$loop_count triggered due to continuation signal.", 'info' );
			
			$fail_safe_prompt = 'Continue exactly where you left off. Output only the remaining article content and final schema. Do not repeat prior text, add recap notes, mention previous responses, offer a new task, or include CMS insertion instructions.';
			
			// Add user "Continue" message to history
			$history[] = ['role' => 'user', 'parts' => [['text' => $fail_safe_prompt]]];

			// Make next request with chat history
			$next_response = $this->make_api_request( null, $history );
			
			if ( is_wp_error( $next_response ) ) {
				WP_SEO_Automater_Admin::log_activity( 'Continuation Error', "Failed in loop #$loop_count: " . $next_response->get_error_message(), 'error' );
				break; // Stop on error, return what we have
			}

			$next_chunk = $this->extract_text_from_response( $next_response );
			$next_finish_reason = $this->get_finish_reason_from_response( $next_response );
			WP_SEO_Automater_Admin::log_activity( 'API Response', "Received continuation chunk (" . strlen($next_chunk) . " chars, finish reason: " . ( $next_finish_reason ? $next_finish_reason : 'unknown' ) . ')', 'success' );
			
			// Update history with new chunk
			$history[] = ['role' => 'model', 'parts' => [['text' => $next_chunk]]];
			
			// Append to full text
			$generated_text .= "\n" . $next_chunk;
			$should_continue = $this->should_continue_generation( $next_chunk, $next_response );
		}

		// Final cleanup: Remove the [PAUSING...] markers
		$final_clean_text = str_replace( '[PAUSING FOR CONTINUATION]', '', $generated_text );

		return $final_clean_text;
	}

	/**
	 * Get the effective timeout for Gemini generation requests.
	 *
	 * @since 1.3.15
	 * @return int
	 */
	public function get_generation_timeout() {
		$timeout = (int) apply_filters( 'wp_seo_automater_generation_timeout', self::DEFAULT_GENERATION_TIMEOUT );

		return max( 60, $timeout );
	}

	/**
	 * Decide whether the model output needs another continuation request.
	 *
	 * @since 1.3.13
	 * @param string $chunk Generated text chunk.
	 * @param array  $response Full Gemini response payload.
	 * @return bool
	 */
	protected function should_continue_generation( $chunk, $response ) {
		if ( false !== strpos( $chunk, '[PAUSING FOR CONTINUATION]' ) ) {
			return true;
		}

		$finish_reason = $this->get_finish_reason_from_response( $response );

		return in_array( $finish_reason, array( 'MAX_TOKENS', 'FINISH_REASON_MAX_TOKENS' ), true );
	}

	/**
	 * Extract the primary finish reason from a Gemini API response.
	 *
	 * @since 1.3.13
	 * @param array $response_data Decoded Gemini response.
	 * @return string
	 */
	protected function get_finish_reason_from_response( $response_data ) {
		if ( isset( $response_data['candidates'][0]['finishReason'] ) && is_string( $response_data['candidates'][0]['finishReason'] ) ) {
			return $response_data['candidates'][0]['finishReason'];
		}

		return '';
	}

	/**
	 * Generate dedicated Unsplash search queries when the main article keywords are weak.
	 *
	 * @since 1.3.6
	 * @param string $context_title Fallback title/meta-title context.
	 * @param string $image_keywords Initial image keywords extracted from the article.
	 * @param string $content Article content for visual context.
	 * @return array|WP_Error List of candidate search queries or WP_Error.
	 */
	public function generate_image_search_keywords( $context_title, $image_keywords, $content = '' ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_key', __( 'Gemini API Key is missing. Please configure it in settings.', 'wp-seo-blog-automater' ) );
		}

		$content_excerpt = trim( preg_replace( '/\s+/', ' ', strip_tags( $content ) ) );
		if ( strlen( $content_excerpt ) > 1200 ) {
			$content_excerpt = substr( $content_excerpt, 0, 1200 );
		}

		$prompt = "You are generating Unsplash image search queries for a WordPress featured image.\n"
			. "Return 4 distinct search queries only, one per line.\n"
			. "Rules:\n"
			. "- Each query must be 2 to 4 words.\n"
			. "- Focus on visible photographic subjects or scenes.\n"
			. "- Avoid generic words alone such as luxury, premium, scottsdale, arizona.\n"
			. "- Avoid brand names unless the scene truly requires them.\n"
			. "- Prefer concrete visual nouns such as eyeglasses, eyewear, optical boutique, titanium frames, eyewear styling.\n"
			. "- Do not add numbering, bullets, commentary, or quotes.\n\n"
			. "Context title: {$context_title}\n"
			. "Initial image keywords: {$image_keywords}\n"
			. "Article excerpt: {$content_excerpt}";

		$response = $this->make_api_request( $prompt );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$text = $this->extract_text_from_response( $response );
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$queries = array();

		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$line = preg_replace( '/^\s*(?:[-*]|\d+[\).\-\s])\s*/', '', trim( $line ) );
				$line = trim( preg_replace( '/\s+/', ' ', $line ), "\"'` " );
				if ( '' !== $line && ! in_array( $line, $queries, true ) ) {
					$queries[] = $line;
				}
			}
		}

		return array_slice( $queries, 0, 4 );
	}

	/**
	 * Helper to build the full prompt.
	 * 
	 * Combines master prompt with user-specific task instructions.
	 *
	 * @since 1.0.0
	 * @param string $title         Article title/topic.
	 * @param string $keywords      Target keywords.
	 * @param string $master_prompt The system/master prompt.
	 * @return string Complete prompt for AI.
	 */
	protected function construct_prompt( $title, $keywords, $master_prompt ) {
		$user_instruction = "\n\n=== TASK ===\n";
		$user_instruction .= "Topic/Working Title: " . $title . "\n";
		$user_instruction .= "Target Keywords: " . $keywords . "\n";
		$user_instruction .= "Please write the full article now following all directives.";

		return $master_prompt . $user_instruction;
	}

	/**
	 * Send Request to Gemini API.
	 * 
	 * Makes HTTP POST request to Google's Gemini API.
	 * Supports both initial requests and continuation with chat history.
	 *
	 * @since 1.0.0
	 * @param string|null $prompt_text Initial prompt text (for first request).
	 * @param array|null  $history     Chat history for continuation requests.
	 * @return array|WP_Error API response data or error.
	 */
	protected function make_api_request( $prompt_text = null, $history = null ) {
		$url = $this->base_url . $this->model_id . ':generateContent?key=' . $this->api_key;

		$body = [
			'generationConfig' => [
				'temperature' => 0.7,
				'maxOutputTokens' => 8192, // High limit for long articles
			]
		];

		if ( $history ) {
			$body['contents'] = $history;
		} else {
			$body['contents'] = [
				[
					'role' => 'user',
					'parts' => [[ 'text' => $prompt_text ]]
				]
			];
		}

		$args = [
			'body'    => json_encode( $body ),
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => $this->get_generation_timeout(),
			'method'  => 'POST'
		];

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$body_err = wp_remote_retrieve_body( $response );
			return new WP_Error( 'api_error', sprintf( __( 'Gemini API Error (%d): %s', 'wp-seo-blog-automater' ), $code, $body_err ) );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Extract text content from API response.
	 * 
	 * Parses the Gemini API response structure and extracts the generated text.
	 *
	 * @since 1.0.0
	 * @param array $response_data Decoded API response.
	 * @return string Extracted text content.
	 */
	protected function extract_text_from_response( $response_data ) {
		if ( empty( $response_data['candidates'][0]['content']['parts'] ) || ! is_array( $response_data['candidates'][0]['content']['parts'] ) ) {
			return '';
		}

		$text_parts = array();
		foreach ( $response_data['candidates'][0]['content']['parts'] as $part ) {
			if ( isset( $part['text'] ) && is_string( $part['text'] ) ) {
				$text_parts[] = $part['text'];
			}
		}

		return implode( '', $text_parts );
	}
}
