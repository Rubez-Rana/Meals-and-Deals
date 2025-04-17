<?php
// Include your config file that contains the API key
include 'config.php';

// Check if query parameter exists
if (!isset($_GET['query'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No search query provided']);
    exit;
}

// Get and sanitize the search query
$query = urlencode(trim($_GET['query']));

// Spoonacular API endpoint
$url = "https://api.spoonacular.com/food/products/search?query={$query}&number=5&apiKey=" . SPOONACULAR_API_KEY;

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FAILONERROR, true);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API request failed: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Close cURL session
curl_close($ch);

// Set proper headers and return the response
header('Content-Type: application/json');
echo $response;
?>