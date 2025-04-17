<?php
include 'config.php';
include 'api_functions.php';

$ingredient = $_GET['ingredient'] ?? '';
$lat = $_GET['lat'] ?? 0;
$lng = $_GET['lng'] ?? 0;

// Get nearby stores
$storesData = getNearbyStores($lat, $lng);
$stores = [];

if (!empty($storesData['results'])) {
    foreach ($storesData['results'] as $store) {
        $distance = haversineGreatCircleDistance(
            $lat, $lng, 
            $store['geometry']['location']['lat'], 
            $store['geometry']['location']['lng']
        );
        
        $stores[] = [
            'name' => $store['name'],
            'address' => $store['vicinity'],
            'distance' => $distance
        ];
    }
}

// Get product matches
$productsData = checkIngredientAvailability($ingredient, $lat, $lng);
$products = [];

if (!empty($productsData['products'])) {
    foreach ($productsData['products'] as $product) {
        $products[] = [
            'title' => $product['title'],
            'image' => $product['image']
        ];
    }
}

$response = [
    'stores' => $stores,
    'products' => $products
];

header('Content-Type: application/json');
echo json_encode($response);
?>