<?php
include 'config.php';
include 'api_functions.php';

$lat = $_GET['lat'] ?? 0;
$lng = $_GET['lng'] ?? 0;

$storesData = getNearbyStores($lat, $lng);
$stores = [];

if (!empty($storesData['results'])) {
    foreach ($storesData['results'] as $store) {
        // Calculate distance
        $distance = haversineGreatCircleDistance(
            $lat, $lng, 
            $store['geometry']['location']['lat'], 
            $store['geometry']['location']['lng']
        );
        
        $stores[] = [
            'name' => $store['name'],
            'vicinity' => $store['vicinity'],
            'distance' => $distance,
            'place_id' => $store['place_id']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($stores);

function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371) {
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);
  
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
  
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}
?>