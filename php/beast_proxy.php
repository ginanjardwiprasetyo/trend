<?php
/**
 * TrendHidro - Beast API Proxy
 * Proxy ke beast-stl Render API untuk dekomposisi STL & BEAST
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

ini_set('max_execution_time', 120);
ini_set('default_socket_timeout', 90);

$BEAST_API_URL = 'https://beast-stl.onrender.com/gradio_api/api/api_analyze';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['pos_id']) || empty($input['metode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'pos_id dan metode wajib diisi.']);
    exit;
}

$posId    = $input['pos_id'];
$metode   = $input['metode'];
$th1      = isset($input['th1']) ? (int)$input['th1'] : 1980;
$th2      = isset($input['th2']) ? (int)$input['th2'] : 2025;
$bulan    = isset($input['bulan']) ? $input['bulan'] : '';
$musim    = isset($input['musim']) ? $input['musim'] : '';

$payload = json_encode([
    'data' => [$posId, $metode, $th1, $th2, $bulan, $musim]
]);

$ch = curl_init($BEAST_API_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$attempts = 0;
$maxAttempts = 2;
$httpCode = 0;
$response = '';

while ($attempts < $maxAttempts) {
    $attempts++;
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        http_response_code(502);
        echo json_encode(['error' => 'Gagal menghubungi beast-stl: ' . $err]);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200) break;

    // Render cold start → retry after delay
    if ($httpCode >= 500 && $attempts < $maxAttempts) {
        sleep(15);
    }
}
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'beast-stl mengembalikan HTTP ' . $httpCode . ' setelah ' . $maxAttempts . ' percobaan. Server mungkin sedang cold start.']);
    exit;
}

$gradioResp = json_decode($response, true);

if (!$gradioResp || !isset($gradioResp['data'][0])) {
    http_response_code(502);
    echo json_encode(['error' => 'Response beast-stl tidak valid.']);
    exit;
}

$apiResult = json_decode($gradioResp['data'][0], true);

if (!$apiResult) {
    http_response_code(502);
    echo json_encode(['error' => 'Gagal parse hasil beast-stl.']);
    exit;
}

echo json_encode($apiResult);
