#!/usr/bin/env php
<?php
/**
 * Run a live Gemini generation and inspect the exact payload sent to the editor box.
 *
 * Usage: php tests/run-live-preview.php
 */

require_once __DIR__ . '/lib/test-bootstrap.php';

$gemini_key = wp_seo_automater_env( 'WP_SEO_AUTOMATER_GEMINI_KEY', '' );
if ( '' === $gemini_key ) {
	wp_seo_automater_test_fail( 'WP_SEO_AUTOMATER_GEMINI_KEY is missing. Copy .env.example to .env and add your Gemini key first.' );
}

$title = wp_seo_automater_env( 'WP_SEO_AUTOMATER_TEST_TITLE', 'Luxury Eyeglasses Arizona' );
$keywords = wp_seo_automater_env( 'WP_SEO_AUTOMATER_TEST_KEYWORDS', 'luxury eyeglasses arizona, designer eyewear scottsdale, lumiere optique' );
$admin = wp_seo_automater_test_admin();
$payload = $admin->generate_preview_data( $title, $keywords );

if ( is_wp_error( $payload ) ) {
	wp_seo_automater_test_fail( 'Live generation failed: ' . $payload->get_error_message() );
}

wp_seo_automater_test_assert( ! empty( $payload['content'] ), 'Live generation returned empty content.' );
wp_seo_automater_test_assert( ! empty( $payload['title'] ), 'Live generation returned empty title.' );
wp_seo_automater_test_assert( ! empty( $payload['slug'] ), 'Live generation returned empty slug.' );
wp_seo_automater_test_assert( ! empty( $payload['meta_title'] ), 'Live generation returned empty meta title.' );
wp_seo_automater_test_assert( ! empty( $payload['schema'] ), 'Live generation returned empty schema.' );

foreach ( wp_seo_automater_test_forbidden_phrases() as $phrase ) {
	wp_seo_automater_test_assert( false === stripos( $payload['content'], $phrase ), "Live generation leaked forbidden phrase: {$phrase}" );
}

$output_dir = __DIR__ . '/output';
if ( ! is_dir( $output_dir ) ) {
	mkdir( $output_dir, 0777, true );
}

$output_file = $output_dir . '/live-preview.json';
file_put_contents( $output_file, json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

echo "[OK] Live preview payload generated.\n";
echo "Title: {$payload['title']}\n";
echo "Slug: {$payload['slug']}\n";
echo "Meta title: {$payload['meta_title']}\n";
echo "Image keywords: {$payload['debug_info']['keywords']}\n";
echo "Unsplash status: {$payload['debug_info']['unsplash_status']}\n";
echo "Image query source: " . ( empty( $payload['debug_info']['image_query_source'] ) ? '(none)' : $payload['debug_info']['image_query_source'] ) . "\n";
echo "Image query used: " . ( empty( $payload['debug_info']['image_query_used'] ) ? '(none)' : $payload['debug_info']['image_query_used'] ) . "\n";
echo "Image query attempts: " . implode( ', ', $payload['debug_info']['image_query_attempts'] ) . "\n";
echo "Image URL: " . ( empty( $payload['image_url'] ) ? '(none)' : $payload['image_url'] ) . "\n";
echo "Content length: " . strlen( $payload['content'] ) . "\n";
echo "Content preview (start): " . substr( trim( strip_tags( $payload['content'] ) ), 0, 220 ) . "\n";
echo "Content preview (end): " . substr( trim( strip_tags( $payload['content'] ) ), -220 ) . "\n";
echo "Saved payload: {$output_file}\n";
