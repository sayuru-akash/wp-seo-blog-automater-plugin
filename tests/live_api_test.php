<?php

// CONFIG
$api_key = 'AIzaSyCPChJZHwYrGihvU2LGrYTBa8gan94Bm5c'; // User provided key
$model_id = 'gemini-pro-latest';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model_id}:generateContent?key={$api_key}";

// PROMPT CONSTRUCTION
$master_prompt = "Role & Persona:
You are the Lead SEO Content Strategist & Senior Medical Copywriter for Lumiere Optique. Your goal is to dominate the #1 Google ranking position for targeted keywords in Scottsdale, AZ. You are writing for high-net-worth individuals who value both medical precision and luxury fashion.

Voice: Professional, sophisticated, knowledgeable, and reassuring.

Perspective: You write as the Lumiere Optique Brand (we/our/the team).

Core Directives (Non-Negotiable)
1. Website Grounding (Mandatory Start): Action: You MUST visit and analyze https://lumiereoptique.com/ before writing.
2. Content Depth & \"No-Fluff\" Strategy (Target: 2,300+ Words)
3. Medical Responsibility (YMYL)
4. E-E-A-T & Local SEO Integration

Content Creation Checklist
Phase 1: Meta Data (Output First)
Meta Title: <60 chars. Front-load keyword. Compelling & Scottsdale-specific.
Meta Description: ~155-160 chars. Keyword + Value Prop + Phone Number in text.
Slug: lowercase-hyphenated-keyword.

Phase 2: The Article (Structure & Formatting)
H1 Title: Catchy, includes primary keyword. Only one H1.
Introduction: Hook the reader immediately.
Keyword Strategy: Primary: Natural placement in H1, first paragraph, and at least one H2.
Body Content: Hierarchy: Logical H2 and H3 headers. Readability: Short, elegant paragraphs.
Mandatory FAQ Section: Place at the end of the body (H2: \"Frequently Asked Questions\"). Include 5 distinct Q&As.

Phase 3: The Close
Tone Check: Ensure the voice is professional yet warm.
Mandatory CTA: The final paragraph must exactly match this format:
[Transition text encouraging health/style priority...] Book Your Appointment: (480) 699-1885 | Visit Us in Scottsdale, AZ

Phase 4: Technical Deliverables
Output Purity: No conversational filler. Just the content.
Schema Markup: Immediately after the CTA, provide a valid JSON-LD FAQPage Schema script block.
CRITICAL: The Schema Question/Answer text must match the on-page FAQ text word-for-word.
";

$user_instruction = "Topic: Benefits of Blue Light Glasses\nKeywords: eye strain, digital protection, sleep quality";
$final_prompt = $master_prompt . "\n\n" . $user_instruction;

// API REQUEST
$data = array(
    'contents' => array(
        array(
            'parts' => array(
                array( 'text' => $final_prompt )
            )
        )
    )
);

echo "Sending Request to Gemini ($model_id)...\n";

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );

$response = curl_exec( $ch );

if ( curl_errno( $ch ) ) {
    die( 'Curl Error: ' . curl_error( $ch ) );
}
curl_close( $ch );

$json = json_decode( $response, true );

if ( isset( $json['error'] ) ) {
    die( "API Error: " . print_r($json['error'], true) );
}

$content = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ( empty( $content ) ) {
    die( "Empty content received from API.\n" );
}

echo "Content Received (" . strlen($content) . " chars).\n\n";

// ==========================================================
// SIMULATING CLASS EXTRACTION LOGIC (Exact Copy)
// ==========================================================

echo "--- RUNNING EXTRACTION LOGIC ---\n";

// RAW CONTENT PROCESSING (Metadata)
$slug = '';
if ( preg_match( '/Slug.*?(?:[:\-]|\s)\s*(.*)(?:\n|$)/i', $content, $matches ) ) {
    $slug = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
}

$meta_title = '';
if ( preg_match( '/Meta\s*Title.*?(?:[:\-]|\s)\s*(.*)(?:\n|$)/i', $content, $matches ) ) {
    $meta_title = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
}

$meta_desc = '';
if ( preg_match( '/Meta\s*Description.*?(?:[:\-]|\s)\s*(.*)(?:\n|$)/i', $content, $matches ) ) {
    $meta_desc = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
}

echo "EXTRACTED METADATA:\n";
echo "Slug: [$slug]\n";
echo "Title: [$meta_title]\n";
echo "Desc: [$meta_desc]\n\n";

// HTML CONVERSION (Mock)
// In plugin we use a helper. Here we'll just assuming simple markdown to html for testing.
// Just doing simple bolding for test.
$html_content = $content; // In reality this would be parsed HTML.

// EXTRACT SCHEMA
$extracted_schema = '';
if ( preg_match( '/<script type="application\/ld\+json">(.*?)<\/script>/is', $html_content, $matches ) ) {
    $extracted_schema = trim( $matches[1] );
    $html_content = str_replace( $matches[0], '', $html_content );
} elseif ( preg_match( '/```json(.*?)```/is', $content, $matches ) ) { 
    if ( strpos( $matches[1], '@context' ) !== false ) {
        $extracted_schema = trim( $matches[1] );
        // $html_content = str_replace( $matches[0], '', $html_content ); // Don't strip from raw in test
    }
}

echo "SCHEMA FOUND: " . ( !empty($extracted_schema) ? "YES" : "NO" ) . "\n\n";

// EXTRACT CONTENT BODY
$h1_start_pos = stripos( $html_content, '<h1' ); // Note: Gemini might output # which parser converts.
// For this test, if Gemini output Markdown #, our mock logic won't see <h1>.
// Let's assume we do a quick markdown replace for H1 for the test to match plugin behavior.
$html_content = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html_content);

$h1_start_pos = stripos( $html_content, '<h1' );
if ( $h1_start_pos !== false ) {
    $html_content = substr( $html_content, $h1_start_pos );
} else {
    $html_content = preg_replace( '/^Phase \d+.*?(?=\n)/is', '', $html_content ); 
}

$stop_phrases = array( 'Phase 2:', 'Phase 3:', 'Output Management', '***', '---', '___' );
$cutoff_pos = strlen( $html_content );

foreach ( $stop_phrases as $phrase ) {
    $pos = stripos( $html_content, $phrase );
    if ( $pos !== false && $pos < $cutoff_pos ) {
        $cutoff_pos = $pos;
    }
}

$html_content = substr( $html_content, 0, $cutoff_pos );
$html_content = trim( $html_content );

echo "--- FINAL BODY CONTENT PREVIEW ---\n";
echo substr( $html_content, 0, 300 ) . "...\n";
echo "--- END PREVIEW ---\n";

if ( strlen($html_content) > 100 && !empty($slug) && !empty($meta_title) ) {
    echo "\nTEST RESULT: PASS\n";
} else {
    echo "\nTEST RESULT: FAIL\n";
}
