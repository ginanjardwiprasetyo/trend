<?php
/**
 * Proxy MapTiler Tiles
 */
$apiKey = 'gynZH4DjPEOBUg0mYiwd';
$z = isset($_GET['z']) ? $_GET['z'] : '';
$x = isset($_GET['x']) ? $_GET['x'] : '';
$y = isset($_GET['y']) ? $_GET['y'] : '';

if ($z === '' || $x === '' || $y === '') {
    http_response_code(400);
    exit;
}

$url = "https://api.maptiler.com/maps/topo-v2/256/{$z}/{$x}/{$y}.png?key={$apiKey}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
if ($contentType) {
    header("Content-Type: $contentType");
}
// Cache tiles locally in browser
header("Cache-Control: public, max-age=86400");
echo $response;
