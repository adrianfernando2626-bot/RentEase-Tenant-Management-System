<?php
// reverse_proxy.php
// Local proxy to bypass CORS for OpenStreetMap reverse geocoding

if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$lat = $_GET['lat'];
$lon = $_GET['lon'];

$url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lon}&format=json";

$context = stream_context_create([
    'http' => [
        'header' => "User-Agent: ApartmentSystem/1.0\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch address']);
} else {
    header("Content-Type: application/json");
    echo $response;
}
