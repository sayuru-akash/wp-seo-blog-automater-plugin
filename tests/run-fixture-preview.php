#!/usr/bin/env php
<?php
/**
 * Fixture-driven verification for the preview payload that populates the admin box.
 *
 * Usage: php tests/run-fixture-preview.php
 */

require_once __DIR__ . '/lib/test-bootstrap.php';

$admin = wp_seo_automater_test_admin();
$fixtures = array(
	array(
		'name'          => 'single-part-clean',
		'file'          => __DIR__ . '/fixtures/lumiere-single-part.txt',
		'expected_slug' => 'luxury-eyeglasses-arizona',
		'expected_title'=> 'Luxury Eyeglasses in Arizona: Why Scottsdale Clients Choose Lumiere Optique',
		'expected_image_keywords' => 'luxury eyeglasses',
	),
	array(
		'name'          => 'two-part-merged-with-trailing-chatter',
		'file'          => __DIR__ . '/fixtures/lumiere-two-part-merged.txt',
		'expected_slug' => 'designer-eyewear-arizona',
		'expected_title'=> 'Designer Eyewear in Arizona: A Luxury Buying Guide from Lumiere Optique',
		'expected_image_keywords' => 'luxury eyeglasses',
	),
	array(
		'name'          => 'bracketed-raw-url-link',
		'file'          => __DIR__ . '/fixtures/bracketed-raw-url-link.txt',
		'expected_slug' => 'office-cleaning-melbourne-corporate',
		'expected_title'=> 'Office Cleaning Service Melbourne: A Corporate Facility Guide',
		'expected_image_keywords' => 'office cleaning',
		'content_must_contain' => '<a href="https://omintagroup.com.au/preventing-cross-contamination-in-the-workplace/">https://omintagroup.com.au/preventing-cross-contamination-in-the-workplace/</a>',
	),
);

foreach ( $fixtures as $fixture ) {
	$raw_content = file_get_contents( $fixture['file'] );
	wp_seo_automater_test_assert( false !== $raw_content, "Unable to read fixture {$fixture['file']}" );

	$payload = $admin->build_generation_result_payload( $raw_content );

	wp_seo_automater_test_assert( $fixture['expected_slug'] === $payload['slug'], "{$fixture['name']}: slug extraction failed." );
	wp_seo_automater_test_assert( $fixture['expected_title'] === $payload['title'], "{$fixture['name']}: title extraction failed." );
	wp_seo_automater_test_assert( ! empty( $payload['meta_title'] ), "{$fixture['name']}: meta title missing." );
	wp_seo_automater_test_assert( ! empty( $payload['meta_desc'] ), "{$fixture['name']}: meta description missing." );
	wp_seo_automater_test_assert( ! empty( $payload['schema'] ), "{$fixture['name']}: schema missing." );
	wp_seo_automater_test_assert( false !== strpos( $payload['schema'], '"@type": "FAQPage"' ), "{$fixture['name']}: schema not preserved." );
	wp_seo_automater_test_assert( false !== strpos( $payload['content'], 'Book Your Appointment: (480) 699-1885 | Visit Us in Scottsdale, AZ' ), "{$fixture['name']}: CTA missing from content payload." );
	wp_seo_automater_test_assert( false === stripos( $payload['content'], '<h1' ), "{$fixture['name']}: H1 should not remain in content payload." );
	wp_seo_automater_test_assert( $fixture['expected_image_keywords'] === $payload['debug_info']['keywords'], "{$fixture['name']}: image keyword normalization failed." );

	if ( isset( $fixture['content_must_contain'] ) ) {
		wp_seo_automater_test_assert(
			false !== strpos( $payload['content'], $fixture['content_must_contain'] ),
			"{$fixture['name']}: expected converted HTML link missing."
		);
	}

	foreach ( wp_seo_automater_test_forbidden_phrases() as $phrase ) {
		wp_seo_automater_test_assert( false === stripos( $payload['content'], $phrase ), "{$fixture['name']}: forbidden phrase leaked into content payload." );
	}

	echo "[OK] {$fixture['name']}\n";
	echo "  Slug: {$payload['slug']}\n";
	echo "  Title: {$payload['title']}\n";
	echo "  Image keywords: {$payload['debug_info']['keywords']}\n";
	echo "  Unsplash status: {$payload['debug_info']['unsplash_status']}\n";
	echo "  Content length: " . strlen( $payload['content'] ) . "\n";
}

echo "\nFixture preview parsing passed.\n";
