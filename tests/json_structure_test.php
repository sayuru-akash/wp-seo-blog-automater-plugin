<?php

// SIMULATE THE CLASS LOGIC
$gemini_key = 'AIzaSyCGrH7RnRa3TbcIBmBnAOCovPE_D5lqgr0';
$unsplash_key = 'b86HRDS4-FEf8u-Ttqh4oNG5aEXEY1kA1qnxe5LOTpU'; 

echo "1. SIMULATING GENERATION...\n";

// 1. CALL GEMINI
$prompt = "Role: SEO Expert. Write about 'Red Sports Cars'. Output: Image Search Keywords: red ferrari race track";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-latest:generateContent?key={$gemini_key}";
$data = array('contents' => array(array('parts' => array(array('text' => $prompt)))));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);
$content = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
echo "   AI Output: " . trim($content) . "\n";

// 2. EXTRACT (REGEX FROM CLASS)
$image_keywords = '';
if ( preg_match( '/(?:Image|Visuals)\s*(?:Search)?\s*(?:Keywords?|Query|Terms)?.*?(?:[:\-]|\s)[\s\*]*([^\n\r]+)/i', $content, $matches ) ) {
    $image_keywords = trim( str_replace( array( '*', '_', '`', '"', "'", '<', '>' ), '', $matches[1] ) );
}
echo "   Extracted Key: '$image_keywords'\n";

// 3. UNSPLASH
$unsplash_url = '';
$unsplash_debug = 'Not Attempted';
if ( !empty($image_keywords) ) {
    $ep = 'https://api.unsplash.com/search/photos';
    $params = array( 'client_id' => $unsplash_key, 'query' => $image_keywords, 'per_page' => 1 );
    $ch2 = curl_init($ep . '?' . http_build_query($params));
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    $resp2 = curl_exec($ch2);
    curl_close($ch2);
    $d2 = json_decode($resp2, true);
    
    if ( isset($d2['results'][0]['urls']['regular']) ) {
        $unsplash_url = $d2['results'][0]['urls']['regular'];
        $unsplash_debug = 'Success';
    } else {
        $unsplash_debug = 'No Results';
    }
}

// 4. CONSTRUCT JSON RESPONSE
$response_array = array(
    'success' => true,
    'data' => array(
        'content' => '...',
        'image_url' => $unsplash_url,
        'debug_info' => array(
            'keywords' => $image_keywords,
            'unsplash_status' => $unsplash_debug,
            'has_key' => !empty($unsplash_key)
        )
    )
);

echo "2. FINAL JSON RESPONSE PREVIEW:\n";
print_r($response_array);
