<?php
/**
 * TrendHidro - Mann-Kendall Trend Test
 * Menerima data time-series via POST, mengembalikan hasil analisis sebagai JSON
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Increase resources for large datasets (e.g. daily data)
ini_set('memory_limit', '512M');
set_time_limit(300);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../error?error=forbidden");
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => true, 'message' => 'Data tidak valid']);
    exit;
}

if (isset($input['data']) && is_array($input['data']) && count($input['data']) > 0) {
    $data = $input['data'];
} else if (isset($input['pos_id'])) {
    require_once 'db_fetch_timeseries.php';
    $data = fetchTimeseriesData($input);
} else {
    echo json_encode(['error' => true, 'message' => 'Data tidak valid']);
    exit;
}

$n = count($data);

if ($n < 3) {
    echo json_encode([
        'error' => true,
        'message' => 'Minimal 3 data diperlukan',
        'trend' => 'Tidak Ada Trend'
    ]);
    exit;
}

// Urutkan berdasarkan tahun
usort($data, function($a, $b) {
    return $a['year'] <=> $b['year'];
});

$values = array_column($data, 'value');

require_once __DIR__ . '/lib/stats.php';

$mk = calcMannKendallBase($values, $n);
$S = $mk['S'];
$varS = $mk['varS'];
$Z = $mk['Z'];
$pValue = $mk['pValue'];

$alpha = 0.05;
$zCritical = getCriticalZ($alpha);

// ====== TENTUKAN TREND ======
$trend = 'Tidak Ada Trend';
$significant = false;
if ($Z > $zCritical) {
    $trend = 'Meningkat';
    $significant = true;
} elseif ($Z < -$zCritical) {
    $trend = 'Menurun';
    $significant = true;
}

// ====== OUTPUT ======
echo json_encode([
    'method' => 'Mann-Kendall',
    'S' => $S,
    'varS' => round($varS, 3),
    'Z' => round($Z, 3),
    'pValue' => round($pValue, 3),
    'alpha' => $alpha,
    'significant' => $significant,
    'trend' => $trend,
    'n' => $n,
    'zCritical' => round($zCritical, 4)
]);
