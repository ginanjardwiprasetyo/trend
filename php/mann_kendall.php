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
        'trend' => 'Tidak Ada Tren'
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

// ====== TENTUKAN TREN ======
// Z > 1.96: Meningkat (Signifikan), Z < -1.96: Menurun (Signifikan)
// 0 < Z <= 1.96: Meningkat (Tidak Signifikan), -1.96 <= Z < 0: Menurun (Tidak Signifikan)
// Z = 0: Tidak Ada Tren
$trend = 'Tidak Ada Tren';
$significant = false;
if ($Z > 1.96) {
    $trend = 'Meningkat (Signifikan)';
    $significant = true;
} elseif ($Z < -1.96) {
    $trend = 'Menurun (Signifikan)';
    $significant = true;
} elseif ($Z > 0) {
    $trend = 'Meningkat (Tidak Signifikan)';
} elseif ($Z < 0) {
    $trend = 'Menurun (Tidak Signifikan)';
}

// ====== OUTPUT ======
echo json_encode([
    'method' => 'Mann-Kendall',
    'S' => $S,
    'varS' => round($varS, 3),
    'Z' => round($Z, 3),
    'pValue' => round($pValue, 3),
    'alpha' => 0.05,
    'significant' => $significant,
    'trend' => $trend,
    'n' => $n
]);
