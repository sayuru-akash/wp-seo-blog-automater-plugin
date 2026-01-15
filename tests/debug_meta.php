<?php
// Mock Data representing potential AI outputs
$samples = [
    // Case 1: Standard Plain Text
    "Plain Text" => "Meta Title: The Best SEO Guide\nMeta Description: Learn how to optimize your site.",
    
    // Case 2: Markdown Bold
    "Markdown Bold" => "**Meta Title:** The Best SEO Guide\n**Meta Description:** Learn how to optimize your site.",
    
    // Case 3: HTML Bold (Post-markdown processing)
    "HTML Bold" => "<strong>Meta Title:</strong> The Best SEO Guide<br><strong>Meta Description:</strong> Learn how to optimize your site.",

    // Case 4: With Quotes
    "Quotes" => "Meta Title: \"The Best SEO Guide\"\nMeta Description: \"Learn how to optimize your site.\"",

    // Case 5: List Format
    "List" => "- Meta Title: The Best SEO Guide\n- Meta Description: Learn how to optimize your site.",
    
    // Case 6: Hyphen Separator (Common AI variation)
    "Hyphen" => "Meta Title - The Best SEO Guide\nMeta Description - Learn how to optimize your site.",

    // Case 7: Markdown Artifacts (Bold formatting not caught)
    "Artifacts" => "**Meta Title:** **The Best SEO Guide**\n**Meta Description:** **Learn how to optimize your site.**",
    
    // Case 8: Extra Spacing/Chars
    "Messy" => "Phase 1: Meta Data\n\nMeta Title :  The Best SEO Guide  \n\nMeta Description : Learn how to optimize your site."
];

echo "Testing Meta Extraction Regex...\n\n";

foreach ($samples as $name => $content) {
    echo "--- Case: $name ---\n";
    
    $meta_title = '';
    // MATCH LOGIC FROM CLASS (UPDATED)
    // Use "Slug-style" nuclear regex: Meta Title -> optional separator -> optional tags -> optional quotes -> capture until quote/tag/newline
    if ( preg_match( '/Meta\s*Title.*?(?:[:\-])\s*(?:<\/?[^>]+>)*\s*[`\'"]?([^`\'"<\n\r]+)/is', $content, $matches ) ) {
        $meta_title = trim( strip_tags( $matches[1] ) );
    } else {
        $meta_title = "FAIL";
    }

    $meta_desc = '';
    // MATCH LOGIC FROM CLASS (UPDATED)
    if ( preg_match( '/Meta\s*Description.*?(?:[:\-])\s*(?:<\/?[^>]+>)*\s*[`\'"]?([^`\'"<\n\r]+)/is', $content, $matches ) ) {
        $meta_desc = trim( strip_tags( $matches[1] ) );
    } else {
        $meta_desc = "FAIL";
    }

    echo "Title: " . ($meta_title !== "FAIL" ? "[OK] '$meta_title'" : "[FAILED]") . "\n";
    echo "Desc : " . ($meta_desc !== "FAIL" ? "[OK] '$meta_desc'" : "[FAILED]") . "\n";
    echo "\n";
}
