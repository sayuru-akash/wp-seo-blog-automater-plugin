<?php
// Function to simulate the markdown_to_html processing
function markdown_to_html( $text ) {
    // Convert Headers
    $text = preg_replace( '/^# (.*?)$/m', '<h1>$1</h1>', $text );
    $text = preg_replace( '/^## (.*?)$/m', '<h2>$1</h2>', $text );
    $text = preg_replace( '/^### (.*?)$/m', '<h3>$1</h3>', $text );
    
    // Bold
    $text = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text );
    
    return $text;
}

// The raw content snippet provided by the user (approximated based on their report)
// Note: User showed formatting that implies bolding and backticks.
$sample_content = <<<EOT
<h3><strong>Phase 1: Meta Data</strong></h3>

<strong>Meta Title:</strong> Expert Eye Glasses Frames Repair in Scottsdale | Lumiere Optique

<strong>Meta Description:</strong> Need emergency eye glasses frames repair in Scottsdale? Our expert opticians specialize in luxury brands like Cartier & Lindberg. Call (480) 699-1885 for precision service.

<strong>Slug:</strong> `eye-glasses-frames-repair-scottsdale`

---

<h3><strong>Phase 2: The Article</strong></h3>

<h1>The Art of Precision: Scottsdale's Premier Destination for Luxury Eye Glasses Frames Repair</h1>

The sound is unmistakable—a quiet, sharp crack...
EOT;

// 1. Convert (Simulate what happens in the plugin)
// If the AI returns HTML already (as seen in sample), this might not change much, but let's see.
$html_content = markdown_to_html($sample_content);

echo "--- PROCESSED CONTENT ---\n";
echo substr($html_content, 0, 500) . "...\n\n";

// 2. Test Slug Regex
echo "--- TESTING SLUG REGEX ---\n";
// Current Regex
$regex_slug = '/Slug:(?:<\/?[^>]+>)*\s*[`\'"]?([^`\'"<\n]+)/i';

if ( preg_match( $regex_slug, $html_content, $matches ) ) {
    echo "MATCH FOUND!\n";
    print_r($matches);
    $slug = trim( strip_tags( $matches[1] ) );
    echo "EXTRACTED SLUG: '" . $slug . "'\n";
} else {
    echo "NO MATCH for Slug Regex.\n";
}

// 3. Test Title Regex
echo "\n--- TESTING TITLE REGEX ---\n";
$regex_title = '/<h1>(.*?)<\/h1>/i';

if ( preg_match( $regex_title, $html_content, $matches ) ) {
    echo "MATCH FOUND!\n";
    print_r($matches);
    $extracted_title = strip_tags( $matches[1] );
    echo "EXTRACTED TITLE: '" . $extracted_title . "'\n";
} else {
    echo "NO MATCH for Title Regex.\n";
}
