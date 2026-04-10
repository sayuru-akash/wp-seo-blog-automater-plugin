<?php
// Simulate AI output where "Phase 1" is at the top
$content = "Phase 1: Configuration
Meta Title: My Title
Meta Description: My Desc

<h1>The Real Post</h1>
<p>This is the actual content that the user wants to keep.</p>

Phase 2: Technical
Schema: {}";

echo "ORIGINAL LENGTH: " . strlen($content) . "\n";

// The suspicious regex
$cleaned = preg_replace( '/(Phase \d+.*|Schema Markup|Output Management).*$/is', '', $content );

echo "CLEANED LENGTH: " . strlen($cleaned) . "\n";
echo "CLEANED CONTENT:\n" . $cleaned . "\n";

if (strlen($cleaned) < 50) {
    echo "\n[CONFIRMED] The regex deleted the content because it found 'Phase 1' at the start and matched to the end.\n";
} else {
    echo "\n[FAILED] Regex did not delete content. Look elsewhere.\n";
}
