<?php
/**
 * Proxy MapTiler API
 * Untuk menyembunyikan API key dari frontend
 */

header('Access-Control-Allow-Origin: *');

// Simple MapTiler Key (Should be in .env in production, hardcoded here as it was in JS before)
$apiKey = 'gynZH4DjPEOBUg0mYiwd';

$type = isset($_GET['type']) ? $_GET['type'] : '';
$url = '';

if ($type === 'elevation') {
    $lon = isset($_GET['lon']) ? $_GET['lon'] : '';
    $lat = isset($_GET['lat']) ? $_GET['lat'] : '';
    if (!$lon || !$lat) {
        http_response_code(400);
        exit('Missing params');
    }
    $url = "https://api.maptiler.com/elevation/{$lon},{$lat}.json?key={$apiKey}";
    header('Content-Type: application/json');
} else {
    http_response_code(400);
    exit('Unknown type');
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
echo $response;
