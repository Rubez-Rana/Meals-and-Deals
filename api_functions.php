<?php
include 'config.php';

function getNearbyStores($lat, $lng) {
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?";
    $url .= "location=$lat,$lng";
    $url .= "&radius=1000"; // 1km radius
    $url .= "&type=grocery_or_supermarket";
    $url .= "&key=" . GOOGLE_PLACES_API_KEY;
    
    $response = file_get_contents($url);
    return json_decode($response, true);
}

function checkIngredientAvailability($ingredient, $lat, $lng) {
    // Spoonacular doesn't have real store availability, so we'll use their product matching
    $url = "https://api.spoonacular.com/food/products/search?";
    $url .= "query=" . urlencode($ingredient);
    $url .= "&number=5"; // Get top 5 matches
    $url .= "&apiKey=" . SPOONACULAR_API_KEY;
    
    $response = file_get_contents($url);
    return json_decode($response, true);
}

function getPlaceDetails($place_id) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?";
    $url .= "place_id=$place_id";
    $url .= "&fields=name,formatted_address,geometry";
    $url .= "&key=" . GOOGLE_PLACES_API_KEY;
    
    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>