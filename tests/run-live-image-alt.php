#!/usr/bin/env php
<?php
/**
 * Run a live Gemini image-text request through the production handler.
 *
 * Usage: php tests/run-live-image-alt.php
 */

require_once __DIR__ . '/lib/test-bootstrap.php';

class WP_SEO_Automater_Live_Image_Alt_Handler extends Gemini_API_Handler {
	/**
	 * Last decoded Gemini response, retained only for the current CLI process.
	 *
	 * @var array|WP_Error|null
	 */
	private $last_response;

	/**
	 * Capture the response shape without persisting the API request or key.
	 *
	 * @param string $prompt Gemini prompt.
	 * @param string $image_data Raw image bytes.
	 * @param string $mime_type Image MIME type.
	 * @return array|WP_Error
	 */
	protected function make_image_api_request( $prompt, $image_data, $mime_type ) {
		$this->last_response = parent::make_image_api_request( $prompt, $image_data, $mime_type );
		return $this->last_response;
	}

	/**
	 * Return the model text only, never the request URL or API key.
	 *
	 * @return string
	 */
	public function get_response_text() {
		return is_array( $this->last_response ) ? $this->extract_text_from_response( $this->last_response ) : '';
	}

	/**
	 * Return a key-free summary of Gemini's candidate response for debugging.
	 *
	 * @return string
	 */
	public function get_response_summary() {
		if ( ! is_array( $this->last_response ) || empty( $this->last_response['candidates'][0] ) ) {
			return '(no candidate response)';
		}

		$candidate = $this->last_response['candidates'][0];
		$summary   = array(
			'finish_reason' => isset( $candidate['finishReason'] ) ? $candidate['finishReason'] : '',
			'finish_message' => isset( $candidate['finishMessage'] ) ? $candidate['finishMessage'] : '',
			'parts'         => array(),
		);

		foreach ( isset( $candidate['content']['parts'] ) && is_array( $candidate['content']['parts'] ) ? $candidate['content']['parts'] : array() as $part ) {
			$summary['parts'][] = array(
				'keys'    => array_keys( $part ),
				'thought' => ! empty( $part['thought'] ),
				'text'    => isset( $part['text'] ) ? substr( preg_replace( '/\s+/', ' ', $part['text'] ), 0, 500 ) : '',
			);
		}

		return json_encode( $summary, JSON_UNESCAPED_SLASHES );
	}
}

$gemini_key = wp_seo_automater_env( 'WP_SEO_AUTOMATER_GEMINI_KEY', '' );
if ( '' === $gemini_key ) {
	wp_seo_automater_test_fail( 'WP_SEO_AUTOMATER_GEMINI_KEY is missing. Add it to the ignored .env file before running this live check.' );
}

$image_path = wp_seo_automater_env( 'WP_SEO_AUTOMATER_IMAGE_ALT_TEST_IMAGE', dirname( __DIR__ ) . '/images/logo.png' );
if ( ! is_readable( $image_path ) ) {
	wp_seo_automater_test_fail( 'Image test fixture is not readable: ' . $image_path );
}

$image_info = @getimagesize( $image_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
$mime_type  = is_array( $image_info ) && ! empty( $image_info['mime'] ) ? $image_info['mime'] : 'image/png';
$handler    = new WP_SEO_Automater_Live_Image_Alt_Handler( $gemini_key, wp_seo_automater_env( 'WP_SEO_AUTOMATER_IMAGE_ALT_MODEL', 'gemini-3.6-flash' ) );
$result     = $handler->generate_image_alt_text(
	$image_path,
	$mime_type,
	array(
		'site_name'       => 'Codezela Technologies',
		'site_url'        => 'https://codezela.com/',
		'brand_context'   => 'The website belongs to Codezela Technologies. Identify this brand only if it is visible in the image.',
		'attachment_title' => basename( $image_path ),
	)
);

$response_text = trim( preg_replace( '/\s+/', ' ', $handler->get_response_text() ) );
if ( is_wp_error( $result ) ) {
	if ( '' !== $response_text ) {
		echo 'Gemini response text: ' . substr( $response_text, 0, 500 ) . "\n";
	}
		echo 'Gemini response summary: ' . $handler->get_response_summary() . "\n";

	wp_seo_automater_test_fail( 'Live image analysis failed (' . $result->get_error_code() . '): ' . $result->get_error_message() );
}

wp_seo_automater_test_assert( '' !== $response_text, 'Live Gemini response text was empty.' );
wp_seo_automater_test_assert( '' !== $result, 'Live image analysis returned empty text.' );

echo "[OK] Live image analysis succeeded.\n";
echo 'Model response text: ' . substr( $response_text, 0, 500 ) . "\n";
echo 'Normalized image text: ' . $result . "\n";
