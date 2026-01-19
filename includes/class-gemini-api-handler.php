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
		$this->model_id = $model_id ? $model_id : get_option( 'wp_seo_automater_gemini_model', 'gemini-pro-latest' );
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
		WP_SEO_Automater_Admin::log_activity( 'API Response', "Received initial chunk (" . strlen($generated_text) . " chars)", 'success' );

		// Check for continuation trigger
		$max_loops = 3; 
		$loop_count = 0;
		$history = [
			['role' => 'user', 'parts' => [['text' => $full_prompt]]],
			['role' => 'model', 'parts' => [['text' => $generated_text]]]
		];

		while ( strpos( $generated_text, '[PAUSING FOR CONTINUATION]' ) !== false && $loop_count < $max_loops ) {
			$loop_count++;
			WP_SEO_Automater_Admin::log_activity( 'Continuation', "Loop #$loop_count triggered due to pause marker.", 'info' );
			
			$fail_safe_prompt = "Continue exactly where you left off. Do not repeat the last sentence.";
			
			// Add user "Continue" message to history
			$history[] = ['role' => 'user', 'parts' => [['text' => $fail_safe_prompt]]];

			// Make next request with chat history
			$next_response = $this->make_api_request( null, $history );
			
			if ( is_wp_error( $next_response ) ) {
				WP_SEO_Automater_Admin::log_activity( 'Continuation Error', "Failed in loop #$loop_count: " . $next_response->get_error_message(), 'error' );
				break; // Stop on error, return what we have
			}

			$next_chunk = $this->extract_text_from_response( $next_response );
			WP_SEO_Automater_Admin::log_activity( 'API Response', "Received continuation chunk (" . strlen($next_chunk) . " chars)", 'success' );
			
			// Update history with new chunk
			$history[] = ['role' => 'model', 'parts' => [['text' => $next_chunk]]];
			
			// Append to full text
			$generated_text .= "\n" . $next_chunk;
		}

		// Final cleanup: Remove the [PAUSING...] markers
		$final_clean_text = str_replace( '[PAUSING FOR CONTINUATION]', '', $generated_text );

		return $final_clean_text;
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
	private function construct_prompt( $title, $keywords, $master_prompt ) {
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
	private function make_api_request( $prompt_text = null, $history = null ) {
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
			'timeout' => 120, // Long timeout for generation
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
	private function extract_text_from_response( $response_data ) {
		if ( isset( $response_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return $response_data['candidates'][0]['content']['parts'][0]['text'];
		}
		return '';
	}
}
