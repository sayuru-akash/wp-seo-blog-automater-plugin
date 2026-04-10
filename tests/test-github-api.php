<?php
/**
 * Test GitHub API Connection
 * 
 * Run this file directly to test if the GitHub API is accessible
 * and returns valid data.
 * 
 * Usage: php tests/test-github-api.php
 */

// Test GitHub API endpoint
$api_url = "https://api.github.com/repos/sayuru-akash/wp-seo-blog-automater-plugin/releases/latest";

echo "Testing GitHub API Endpoint...\n";
echo "URL: $api_url\n\n";

// Make request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/vnd.github.v3+json',
    'User-Agent: WP-SEO-Blog-Automater'
));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";

if ($error) {
    echo "Error: $error\n";
    exit(1);
}

if ($http_code !== 200) {
    echo "Failed to fetch data (HTTP $http_code)\n";
    echo "Response: $response\n";
    exit(1);
}

$data = json_decode($response, true);

if (empty($data)) {
    echo "Failed to decode JSON response\n";
    exit(1);
}

echo "\n✓ Success!\n\n";
echo "Latest Release:\n";
echo "  Tag: " . ($data['tag_name'] ?? 'N/A') . "\n";
echo "  Name: " . ($data['name'] ?? 'N/A') . "\n";
echo "  Published: " . ($data['published_at'] ?? 'N/A') . "\n";

if (!empty($data['assets'])) {
    echo "\nAssets:\n";
    foreach ($data['assets'] as $asset) {
        echo "  - " . $asset['name'] . " (" . round($asset['size'] / 1024, 2) . " KB)\n";
    }
}

echo "\nTest completed successfully!\n";
