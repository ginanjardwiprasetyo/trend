<?php
/**
 * TrendHidro - Regresi Linear (Least Squares)
 * Menerima data time-series via POST, mengembalikan hasil analisis sebagai JSON
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Increase resources for consistency
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once __DIR__ . '/lib/stats.php';


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

$x = range(0, $n - 1);
$y = array_column($data, 'value');

// ====== HITUNG LEAST SQUARES SECARA MANUAL ======
$sumX = array_sum($x);
$sumY = array_sum($y);
$meanX = $sumX / $n;
$meanY = $sumY / $n;

$Sxx = 0;
$Syy = 0;
$Sxy = 0;

for ($i = 0; $i < $n; $i++) {
    $dx = $x[$i] - $meanX;
    $dy = $y[$i] - $meanY;
    $Sxx += $dx * $dx;
    $Syy += $dy * $dy;
    $Sxy += $dx * $dy;
}

$slope = ($Sxx != 0) ? $Sxy / $Sxx : 0;
$intercept = $meanY - $slope * $meanX;

// ====== KOEFISIEN DETERMINASI (R²) ======
$SSres = 0;
$SStot = $Syy;

for ($i = 0; $i < $n; $i++) {
    $yPred = $slope * $x[$i] + $intercept;
    $SSres += pow($y[$i] - $yPred, 2);
}

$rSquared = ($SStot > 0) ? 1 - ($SSres / $SStot) : 0;

// ====== UJI SIGNIFIKANSI (t-test) ======
$SE = 0;
$tStat = 0;
$pValue = 1;
$df = max(1, $n - 2);

if ($n > 2 && $Sxx > 0) {
    $MSE = $SSres / $df;
    $SE = sqrt($MSE / $Sxx);
    
    $tStat = ($SE > 0) ? $slope / $SE : 0;
    
    // Pendekatan p-value menggunakan distribusi normal untuk n besar (masih dipertahankan untuk pValue API)
    $pValue = 2 * (1 - normalCDF(abs($tStat)));
}

$alpha = 0.05;
$tCritical = getCriticalT($df, $alpha);
$significant = abs($tStat) > $tCritical;

// ====== TENTUKAN TREND ======
$trend = 'Tidak Ada Trend';
if ($significant) {
    if ($slope > 0) $trend = 'Meningkat';
    elseif ($slope < 0) $trend = 'Menurun';
}

// ====== KOEFISIEN KORELASI (r) ======
$r = ($rSquared >= 0) ? sqrt($rSquared) : 0;
if ($slope < 0) $r = -$r;

// ====== OUTPUT ======
echo json_encode([
    'method' => 'Regresi Linear',
    'slope' => round($slope, 3),
    'intercept' => round($intercept, 3),
    'rSquared' => round($rSquared, 3),
    'r' => round($r, 3),
    'tStatistic' => round($tStat, 3),
    'tCritical' => round($tCritical, 3),
    'pValue' => round($pValue, 3),
    'alpha' => $alpha,
    'significant' => $significant,
    'trend' => $trend,
    'n' => $n,
    'meanX' => $meanX,
    'meanY' => $meanY
]);
