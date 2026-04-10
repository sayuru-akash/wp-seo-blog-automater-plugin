#!/usr/bin/env php
<?php
/**
 * Simulate Gemini continuation responses to verify chunk stitching.
 *
 * Usage: php tests/run-gemini-continuation.php
 */

require_once __DIR__ . '/lib/test-bootstrap.php';

class WP_SEO_Automater_Fake_Gemini_Handler extends Gemini_API_Handler {
	private $responses;
	public $request_count = 0;

	public function __construct( $responses ) {
		parent::__construct( 'test-key', 'test-model' );
		$this->responses = $responses;
	}

	protected function make_api_request( $prompt_text = null, $history = null ) {
		$this->request_count++;

		if ( empty( $this->responses ) ) {
			return new WP_Error( 'missing_fake_response', 'No fake response left for continuation test.' );
		}

		return array_shift( $this->responses );
	}
}

$responses = array(
	array(
		'candidates' => array(
			array(
				'content' => array(
					'parts' => array(
						array(
							'text' => "Meta Title: Luxury Eyewear Arizona\nMeta Description: Scottsdale luxury eyewear guidance.\nSlug: luxury-eyewear-arizona\nImage Search Keywords: luxury eyeglasses arizona boutique\n\n<h1>Luxury Eyewear Arizona</h1>\n<p>Choosing luxury eyewear in Arizona starts with fit, light management, and elevated styling.</p>\n[PAUSING FOR CONTINUATION]",
						),
					),
				),
			),
		),
	),
	array(
		'candidates' => array(
			array(
				'content' => array(
					'parts' => array(
						array(
							'text' => "<h2>What to Expect at Lumiere Optique</h2>\n<p>The fitting process should account for Arizona glare, daily driving, and premium frame balance.</p>\n<p>Book Your Appointment: (480) 699-1885 | Visit Us in Scottsdale, AZ</p>\n<script type=\"application/ld+json\">{\"@context\":\"https://schema.org\",\"@type\":\"FAQPage\",\"mainEntity\":[]}</script>",
						),
					),
				),
			),
		),
	),
);

$handler = new WP_SEO_Automater_Fake_Gemini_Handler( $responses );
$content = $handler->generate_article(
	'Luxury Eyewear Arizona',
	'luxury eyeglasses arizona, lumiere optique',
	'Output only the article.'
);

wp_seo_automater_test_assert( ! is_wp_error( $content ), 'Continuation test returned WP_Error.' );
wp_seo_automater_test_assert( false !== strpos( $content, 'Choosing luxury eyewear in Arizona starts with fit' ), 'Initial chunk missing from merged article.' );
wp_seo_automater_test_assert( false !== strpos( $content, 'What to Expect at Lumiere Optique' ), 'Continuation chunk missing from merged article.' );
wp_seo_automater_test_assert( false === strpos( $content, '[PAUSING FOR CONTINUATION]' ), 'Pause marker was not removed from final content.' );
wp_seo_automater_test_assert( 2 === $handler->request_count, 'Continuation loop requested more chunks than expected.' );

echo "[OK] Gemini continuation stitching uses the initial chunk plus one continuation chunk.\n";
echo "Merged length: " . strlen( $content ) . "\n";
