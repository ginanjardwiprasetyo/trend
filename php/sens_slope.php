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
        'trend' => 'Tidak Ada Tren'
    ]);
    exit;
}

// Urutkan berdasarkan tahun
usort($data, function($a, $b) {
    return $a['year'] <=> $b['year'];
});

// ====== HITUNG KEMIRINGAN ======
$slopes = [];
$totalPairs = ($n * ($n - 1)) / 2;
$maxTargetPairs = 2000000; // Limit to 2 million pairs to save memory/time

if ($totalPairs > $maxTargetPairs) {
    // Randomized Sampling for very large datasets (e.g. 10+ years of daily data)
    for ($k = 0; $k < $maxTargetPairs; $k++) {
        $i = rand(0, $n - 2);
        $j = rand($i + 1, $n - 1);
        $dx = $data[$j]['year'] - $data[$i]['year'];
        if ($dx != 0) {
            $slopes[] = ($data[$j]['value'] - $data[$i]['value']) / $dx;
        }
    }
} else {
    // Full Iteration for smaller datasets
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $dx = $data[$j]['year'] - $data[$i]['year'];
            if ($dx != 0) {
                $slopes[] = ($data[$j]['value'] - $data[$i]['value']) / $dx;
            }
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
$years = array_column($data, 'year');
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

// ====== TENTUKAN TREN ======
// Gunakan Mann-Kendall S untuk signifikansi
$mk = calcMannKendallBase($values, $n);
$S = $mk['S'];
$Z = $mk['Z'];
$pValue = $mk['pValue'];
$alpha = 0.05;

// ====== TENTUKAN TREN ======
$trend = 'Tidak Ada Tren';
if ($senSlope > 0) {
    $trend = 'Meningkat';
} elseif ($senSlope < 0) {
    $trend = 'Menurun';
}

// ====== OUTPUT ======
echo json_encode([
    'method' => "Sen's Slope",
    'slope' => round($senSlope, 3),
    'intercept' => round($intercept, 3),
    'S' => $S,
    'Z' => round($Z, 3),
    'pValue' => round($pValue, 3),
    'alpha' => $alpha,
    'significant' => $pValue <= $alpha,
    'trend' => $trend,
    'n' => $n,
    'slopeCount' => $slopeCount
]);
