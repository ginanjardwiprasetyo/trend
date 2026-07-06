<?php
/**
 * TrendHidro - Sen's Slope Estimator
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

// ====== HITUNG KEMIRINGAN ======
$slopes = [];
for ($i = 0; $i < $n - 1; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        $dx = $j - $i;
        if ($dx != 0) {
            $slopes[] = ($data[$j]['value'] - $data[$i]['value']) / $dx;
        }
    }
}


// ====== MEDIAN KEMIRINGAN (SEN'S SLOPE) ======
sort($slopes);
$slopeCount = count($slopes);
if ($slopeCount === 0) {
    $senSlope = 0;
} elseif ($slopeCount % 2 === 0) {
    $senSlope = ($slopes[$slopeCount / 2 - 1] + $slopes[$slopeCount / 2]) / 2;
} else {
    $senSlope = $slopes[floor($slopeCount / 2)];
}

// ====== HITUNG INTERCEPT ======
$years = range(0, $n - 1);
$values = array_column($data, 'value');

$intercepts = [];
for ($i = 0; $i < $n; $i++) {
    $intercepts[] = $values[$i] - $senSlope * $years[$i];
}
sort($intercepts);
if ($n % 2 === 0) {
    $intercept = ($intercepts[$n / 2 - 1] + $intercepts[$n / 2]) / 2;
} else {
    $intercept = $intercepts[floor($n / 2)];
}

require_once __DIR__ . '/lib/stats.php';

// ====== TENTUKAN TREND ======
// Gunakan Mann-Kendall S untuk signifikansi
$mk = calcMannKendallBase($values, $n);
$S = $mk['S'];
$Z = $mk['Z'];
$pValue = $mk['pValue'];
$alpha = 0.05;

// ====== TENTUKAN TREND ======
// Hitung Confidence Interval 95% (alpha = 0.05)
$zCrit = getCriticalZ(0.05);
$C_alpha = $zCrit * sqrt($mk['varS']);
$M1 = ($slopeCount - $C_alpha) / 2;
$M2 = ($slopeCount + $C_alpha) / 2;

// Indices (0-based) for M1-th and (M2+1)-th largest slopes
$idxLower = max(0, floor($M1) - 1); // Approximation for M1-th
$idxUpper = min($slopeCount - 1, floor($M2 + 1) - 1); // Approximation for (M2+1)-th

$Qmin = $slopes[$idxLower] ?? 0;
$Qmax = $slopes[$idxUpper] ?? 0;

// Signifikan, jika nol TIDAK berada di dalam rentang (Qmin, Qmax)
$significant = ($Qmin > 0) || ($Qmax < 0);

$trend = 'Tidak Ada Trend';
if ($senSlope > 0) {
    $trend = 'Meningkat';
} elseif ($senSlope < 0) {
    $trend = 'Menurun';
}

if ($trend !== 'Tidak Ada Trend') {
    $trend .= $significant ? ' (Signifikan)' : ' (Tidak Signifikan)';
}

// ====== OUTPUT ======
echo json_encode([
    'method' => "Sen's Slope",
    'slope' => round($senSlope, 3),
    'intercept' => round($intercept, 3),
    'S' => $S,
    'Z' => round($Z, 3),
    'pValue' => round($pValue, 3),
    'Qmin' => round($Qmin, 3),
    'Qmax' => round($Qmax, 3),
    'alpha' => $alpha,
    'significant' => $significant,
    'trend' => $trend,
    'n' => $n,
    'slopeCount' => $slopeCount
]);
