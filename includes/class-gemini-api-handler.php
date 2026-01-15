<?php

class Gemini_API_Handler {

	private $api_key;
	private $base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
	private $model_id;

	public function __construct( $api_key = null, $model_id = null ) {
		$this->api_key = $api_key ? $api_key : get_option( 'wp_seo_automater_gemini_key', '' );
		$this->model_id = $model_id ? $model_id : get_option( 'wp_seo_automater_gemini_model', 'gemini-pro-latest' );
	}


	/**
	 * Main function to generate content.
	 */
	public function generate_article( $title, $keywords, $master_prompt ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'missing_key', 'Gemini API Key is missing.' );
		}

		$full_prompt = $this->construct_prompt( $title, $keywords, $master_prompt );
		
		// Initial Call
		$response = $this->make_api_request( $full_prompt );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$generated_text = $this->extract_text_from_response( $response );

		// Check for continuation trigger
		// The prompt instructions say: "Stop at a natural paragraph break and write [PAUSING FOR CONTINUATION]"
		// We loop until complete or max retries.
		$max_loops = 3; 
		$loop_count = 0;
		$history = [
			['role' => 'user', 'parts' => [['text' => $full_prompt]]],
			['role' => 'model', 'parts' => [['text' => $generated_text]]]
		];

		while ( strpos( $generated_text, '[PAUSING FOR CONTINUATION]' ) !== false && $loop_count < $max_loops ) {
			// Remove the pause marker from the accumulated text (optional, but keeps it clean)
			// Actually we keep it in the history so the model knows where it left off, 
			// but we can strip it from the final result.
			
			$fail_safe_prompt = "Continue exactly where you left off. Do not repeat the last sentence.";
			
			// Add user "Continue" message to history
			$history[] = ['role' => 'user', 'parts' => [['text' => $fail_safe_prompt]]];

			// Make next request with chat history
			$next_response = $this->make_api_request( null, $history );
			
			if ( is_wp_error( $next_response ) ) {
				break; // Stop on error, return what we have
			}

			$next_chunk = $this->extract_text_from_response( $next_response );
			
			// Update history with new chunk
			$history[] = ['role' => 'model', 'parts' => [['text' => $next_chunk]]];
			
			// Append to full text
			$generated_text .= "\n" . $next_chunk;
			$loop_count++;
		}

		// Final cleanup: Remove the [PAUSING...] markers
		$final_clean_text = str_replace( '[PAUSING FOR CONTINUATION]', '', $generated_text );

		return $final_clean_text;
	}

	/**
	 * Helper to build the prompt.
	 */
	private function construct_prompt( $title, $keywords, $master_prompt ) {
		$user_instruction = "\n\n=== TASK ===\n";
		$user_instruction .= "Topic/Working Title: " . $title . "\n";
		$user_instruction .= "Target Keywords: " . $keywords . "\n";
		$user_instruction .= "Please write the full article now following all directives.";

		return $master_prompt . $user_instruction;
	}

	/**
	 * Send Request to Gemini.
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
			return new WP_Error( 'api_error', "Gemini API Error ($code): $body_err" );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	private function extract_text_from_response( $response_data ) {
		if ( isset( $response_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return $response_data['candidates'][0]['content']['parts'][0]['text'];
		}
		return '';
	}
}
