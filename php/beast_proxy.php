<?php
/**
 * TrendHidro - Beast API Proxy
 * Supports two modes:
 * 1. pos_id mode: fetch from DB (detail.php)
 * 2. raw_data mode: accept uploaded data directly (olah-data.php)
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

$BEAST_API_BASE = 'https://beast-stl.rekayasa-sipil.my.id/gradio_api/api';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['metode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'metode wajib diisi.']);
    exit;
}

$metode   = $input['metode'];
$bulan    = isset($input['bulan']) ? $input['bulan'] : '';
$musim    = isset($input['musim']) ? $input['musim'] : '';

$hasRawData = !empty($input['raw_data']);

if ($hasRawData) {
    $BEAST_API_URL = $BEAST_API_BASE . '/api_analyze_data';
    $payload = json_encode([
        'data' => [$input['raw_data'], $metode, $bulan, $musim]
    ]);
} else {
    if (empty($input['pos_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'pos_id wajib diisi.']);
        exit;
    }
    $posId    = $input['pos_id'];
    $th1      = isset($input['th1']) ? (int)$input['th1'] : 1980;
    $th2      = isset($input['th2']) ? (int)$input['th2'] : 2025;

    $BEAST_API_URL = $BEAST_API_BASE . '/api_analyze';
    $payload = json_encode([
        'data' => [$posId, $metode, $th1, $th2, $bulan, $musim]
    ]);
}

$cacheDir = __DIR__ . '/cache_beast';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
$cacheKey = md5($payload);
$cacheFile = $cacheDir . '/' . $cacheKey . '.json';

// Cek apakah file cache ada dan masih valid (kita set TTL misalnya sangat lama karena historis, atau selamanya)
// Kecuali data raw dari olah-data.php bisa kita cache juga karena isinya ada dalam payload.
if (file_exists($cacheFile)) {
    $cachedData = file_get_contents($cacheFile);
    // Verifikasi validitas JSON
    $decodedCache = json_decode($cachedData, true);
    if ($decodedCache !== null) {
        // Cache hit! Langsung return
        echo $cachedData;
        exit;
    }
}

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
$maxAttempts = 3;
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
        sleep(45);
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

// Simpan ke cache jika berhasil
file_put_contents($cacheFile, json_encode($apiResult));

echo json_encode($apiResult);
