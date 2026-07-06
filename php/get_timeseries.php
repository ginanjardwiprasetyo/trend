<?php
/**
 * TrendHidro - Ambil Timeseries Data Stasiun
 * Digunakan oleh klien (seperti detail.php) via AJAX POST
 */

header('Content-Type: application/json; charset=utf-8');

// --- Cegah Akses Langsung ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Location: ../error?error=forbidden");
    exit;
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_fetch_timeseries.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => true, 'message' => 'Payload JSON tidak valid']);
    exit;
}

$data = fetchTimeseriesData($input);

echo json_encode($data);
