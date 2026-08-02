#!/usr/bin/env php
<?php
/**
 * Verify Gemini image-text response handling without making a live API call.
 *
 * Usage: php tests/run-image-alt-text.php
 */

require_once __DIR__ . '/lib/test-bootstrap.php';

class WP_SEO_Automater_Fake_Image_Alt_Handler extends Gemini_API_Handler {
	private $response;

	public function __construct( $response ) {
		parent::__construct( 'test-key', 'gemini-2.5-flash' );
		$this->response = $response;
	}

	public function build_prompt( $context ) {
		return $this->get_image_alt_text_prompt( $context );
	}

	protected function make_image_api_request( $prompt, $image_data, $mime_type ) {
		return $this->response;
	}
}

$normalization_cases = array(
	'json' => array(
		'input'    => '{"alt_text":"Amber eyeglass frames displayed on a white pedestal"}',
		'expected' => 'Amber eyeglass frames displayed on a white pedestal',
	),
	'fenced-json' => array(
		'input'    => "```json\n{\"alt_text\":\"Blue ceramic mug beside a notebook on a desk\"}\n```",
		'expected' => 'Blue ceramic mug beside a notebook on a desk',
	),
	'plain-text-fallback' => array(
		'input'    => '<strong>Gold company logo on a dark storefront sign</strong>',
		'expected' => 'Gold company logo on a dark storefront sign',
	),
);

foreach ( $normalization_cases as $label => $case ) {
	$result = Gemini_API_Handler::normalize_image_alt_text( $case['input'] );
	wp_seo_automater_test_assert( ! is_wp_error( $result ), 'Normalization returned WP_Error for case: ' . $label );
	wp_seo_automater_test_assert( $case['expected'] === $result, 'Normalization did not return expected text for case: ' . $label );
	echo "[OK] Image alt text normalization: {$label}\n";
}

$long_text = str_repeat( 'visible subject ', 20 );
$truncated = Gemini_API_Handler::normalize_image_alt_text( $long_text );
wp_seo_automater_test_assert( ! is_wp_error( $truncated ), 'Long image text should be safely truncated.' );
wp_seo_automater_test_assert( strlen( $truncated ) <= 125, 'Image alt text exceeded the 125-character limit.' );
echo "[OK] Image alt text maximum length\n";

$empty = Gemini_API_Handler::normalize_image_alt_text( '   ' );
wp_seo_automater_test_assert( is_wp_error( $empty ), 'Empty model output must fail rather than overwrite attachment fields.' );
echo "[OK] Empty image alt text rejected\n";

$fake_response = array(
	'candidates' => array(
		array(
			'content' => array(
				'parts' => array(
					array( 'text' => '{"alt_text":"Codezela logo ' ),
					array( 'text' => 'on a white website header"}' ),
				),
			),
		),
	),
);
$handler = new WP_SEO_Automater_Fake_Image_Alt_Handler( $fake_response );
$temp_file = tempnam( sys_get_temp_dir(), 'wp-seo-image-alt-' );
file_put_contents( $temp_file, 'fixture-image-bytes' );

$generated = $handler->generate_image_alt_text(
	$temp_file,
	'image/jpeg',
	array(
		'site_name'      => 'Codezela',
		'brand_context'  => 'Codezela is a WordPress automation company.',
		'parent_title'   => 'Automation services',
	)
);
@unlink( $temp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

wp_seo_automater_test_assert( ! is_wp_error( $generated ), 'Image-analysis handler should accept a valid structured Gemini response.' );
wp_seo_automater_test_assert( 'Codezela logo on a white website header' === $generated, 'Image-analysis handler returned unexpected text.' );
echo "[OK] Image analysis response contract\n";

$prompt = $handler->build_prompt(
	array(
		'site_name'      => 'Codezela',
		'site_tagline'   => 'Automation for WordPress teams',
		'site_url'       => 'https://codezela.com/',
		'brand_context'  => 'Codezela logo is a black wordmark.',
		'parent_title'   => 'WordPress automation services',
	)
);

wp_seo_automater_test_assert( false !== strpos( $prompt, 'Codezela logo is a black wordmark.' ), 'Brand context was not included in the image prompt.' );
wp_seo_automater_test_assert( false !== strpos( $prompt, 'never guess a logo' ), 'Prompt must prohibit ungrounded logo identification.' );
wp_seo_automater_test_assert( false !== strpos( $prompt, 'Analyze the supplied image itself first' ), 'Prompt must prioritize visual evidence.' );
echo "[OK] Website and brand context prompt grounding\n";
