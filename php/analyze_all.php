<?php
/**
 * TrendHidro - Analyze All
 * Menerima parameter filter, melakukan agregasi + hitung MK, SS, LR untuk SEMUA stasiun,
 * lalu mengembalikan hasil yang digabungkan sebagai JSON.
 */
// --- Cegah Akses Langsung ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Location: ../error?error=forbidden");
    exit;
}

header('Content-Type: application/json; charset=utf-8');

header('Access-Control-Allow-Origin: *');

// Tambahkan limit untuk pemrosesan bulk
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '120'); // 2 menit
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan di output agar tidak merusak JSON
ini_set('log_errors', 1);
ini_set('error_log', sys_get_temp_dir() . '/php_error.log');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/lib/stats.php';
require_once 'db_fetch_timeseries.php'; // untuk fungsi fetchTimeseriesData tapi kita butuh bulk query
// Namun untuk efisiensi, kita query langsung ALL stations grouping dari data_ch

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => true, 'message' => 'Data tidak valid']);
    exit;
}

$dataType = isset($input['dataType']) ? $input['dataType'] : 'tahunan';
$agregasi = isset($input['aggregation']) ? $input['aggregation'] : 'rerata';
$yearFrom = (int) (isset($input['yearFrom']) ? $input['yearFrom'] : 1980);
$yearTo = (int) (isset($input['yearTo']) ? $input['yearTo'] : 2025);
$monthFilter = isset($input['month']) ? $input['month'] : 'all';

// Jika tahunan, paksa month filter ke all
if ($dataType === 'tahunan') $monthFilter = 'all';

try {
    $conn = getConnection();
    
    // Pilihan fungsi agregasi SQL
    $aggFunc = 'AVG';
    if ($agregasi === 'kumulatif') $aggFunc = 'SUM';
    elseif ($agregasi === 'maks') $aggFunc = 'MAX';
    elseif ($agregasi === 'min') $aggFunc = 'MIN';
    
    $where = "EXTRACT(YEAR FROM tanggal::date) >= :yearFrom AND EXTRACT(YEAR FROM tanggal::date) <= :yearTo AND rain IS NOT NULL";
    $params = ['yearFrom' => $yearFrom, 'yearTo' => $yearTo];
    
    if ($monthFilter !== 'all') {
        if (strpos($monthFilter, ',') !== false) {
            // Multi-month filter (seasonal)
            $months = array_map('intval', explode(',', $monthFilter));
            $placeholders = implode(',', array_fill(0, count($months), '?'));
            $where = str_replace(':yearFrom', '?', str_replace(':yearTo', '?', $where));
            $where .= " AND EXTRACT(MONTH FROM tanggal::date) IN ($placeholders)";
            $params = array_merge([$yearFrom, $yearTo], $months);
        } else {
            $where = str_replace(':yearFrom', '?', str_replace(':yearTo', '?', $where));
            $where .= " AND EXTRACT(MONTH FROM tanggal::date) = ?";
            $params = [$yearFrom, $yearTo, (int)$monthFilter];
        }
    } else {
        $where = str_replace(':yearFrom', '?', str_replace(':yearTo', '?', $where));
        $params = [$yearFrom, $yearTo];
    }
    
    // Query Bulk
    if ($dataType === 'tahunan' || $dataType === 'musiman') {
        $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as x, $aggFunc(rain::numeric) as y FROM data_ch WHERE $where GROUP BY pos_id, EXTRACT(YEAR FROM tanggal::date) ORDER BY pos_id, x";
    } elseif ($dataType === 'bulanan') {
        $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(MONTH FROM tanggal::date) as mo, $aggFunc(rain::numeric) as y FROM data_ch WHERE $where GROUP BY pos_id, yr, mo ORDER BY pos_id, yr, mo";
    } else {
        $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(DOY FROM tanggal::date) as dy, rain::numeric as y FROM data_ch WHERE $where ORDER BY pos_id, tanggal::date";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $stationsData = [];
    while ($row = $stmt->fetch()) {
        $pos_id = $row['pos_id'];
        if (!isset($stationsData[$pos_id])) {
            $stationsData[$pos_id] = [];
        }
        
        $x = 0;
        if ($dataType === 'tahunan' || $dataType === 'musiman') {
            $x = $row['x'];
        } elseif ($dataType === 'bulanan') {
            $x = $row['yr'] + ($row['mo'] - 1) / 12.0;
        } else {
            $x = $row['yr'] + ($row['dy'] - 1) / 365.0;
        }
        
        $entry = [
            'year' => (float)$x,
            'value' => (float)$row['y'] // Menggunakan nilai riil desimal untuk keakuratan statistik
        ];
        if ($dataType === 'bulanan' && isset($row['mo'])) {
            $entry['month'] = (int)$row['mo'];
        }
        $stationsData[$pos_id][] = $entry;
    }
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Terjadi kesalahan sistem saat mengolah data: ' . $e->getMessage()]);
    exit;
}

// Fungsi-fungsi Hitung
function calcMannKendall($data) {
    $n = count($data);
    if ($n < 3) return ['trend' => 'Tidak Ada Trend', 'S' => 0, 'Z' => 0, 'pValue' => 1];
    
    $values = array_column($data, 'value');
    $S = 0;
    for ($k = 0; $k < $n - 1; $k++) {
        for ($j = $k + 1; $j < $n; $j++) {
            $diff = $values[$j] - $values[$k];
            if ($diff > 0) $S += 1;
            elseif ($diff < 0) $S -= 1;
        }
    }
    
    $uniqueValues = array_count_values(array_map('strval', $values));
    $tiedGroupSum = 0;
    foreach ($uniqueValues as $count) {
        if ($count > 1) {
            $tiedGroupSum += $count * ($count - 1) * (2 * $count + 5);
        }
    }
    
    $varS = ($n * ($n - 1) * (2 * $n + 5) - $tiedGroupSum) / 18;
    
    if ($S > 0) $Z = ($S - 1) / sqrt($varS);
    elseif ($S < 0) $Z = ($S + 1) / sqrt($varS);
    else $Z = 0;
    
    $pValue = 2 * (1 - normalCDF(abs($Z)));
    
    $trend = 'Tidak Ada Trend';
    $significant = false;
    $zCrit = getCriticalZ(0.05);
    if ($Z > $zCrit) {
        $trend = 'Meningkat';
        $significant = true;
    } elseif ($Z < -$zCrit) {
        $trend = 'Menurun';
        $significant = true;
    }
    
    return [
        'trend' => $trend,
        'significant' => $significant,
        'S' => round($S, 3),
        'Z' => round($Z, 3),
        'pValue' => round($pValue, 3),
        'n' => $n
    ];
}

function calcSensSlope($data) {
    $n = count($data);
    if ($n < 3) return ['trend' => 'Tidak Ada Trend', 'slope' => 0];
    
    $slopes = [];
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $dx = $j - $i;
            if ($dx != 0) {
                $slopes[] = ($data[$j]['value'] - $data[$i]['value']) / $dx;
            }
        }
    }
    
    if (empty($slopes)) return ['trend' => 'Tidak Ada Trend', 'slope' => 0];
    
    sort($slopes);
    $count = count($slopes);
    $mid = floor($count / 2);
    if ($count % 2 == 0) {
        $medianSlope = ($slopes[$mid - 1] + $slopes[$mid]) / 2;
    } else {
        $medianSlope = $slopes[$mid];
    }
    
    // ====== SIGNIFIKANSI via Confidence Interval 95% (alpha = 0.05) ======
    // Reuse the Mann-Kendall S untuk mendapatkan varS (variansi S)
    $values = array_column($data, 'value');
    $mk = calcMannKendallBase($values, $n);
    $varS = $mk['varS'];
    
    $C_alpha = getCriticalZ(0.05) * sqrt($varS);
    $M1 = ($count - $C_alpha) / 2;
    $M2 = ($count + $C_alpha) / 2;
    
    $idxLower = max(0, floor($M1) - 1);
    $idxUpper = min($count - 1, floor($M2 + 1) - 1);
    
    $Qmin = $slopes[$idxLower] ?? 0;
    $Qmax = $slopes[$idxUpper] ?? 0;
    
    // Signifikan jika nol TIDAK berada di dalam rentang (Qmin, Qmax)
    $significant = ($Qmin > 0) || ($Qmax < 0);
    
    $trend = 'Tidak Ada Trend';
    if ($significant) {
        if ($medianSlope > 0) $trend = 'Meningkat';
        elseif ($medianSlope < 0) $trend = 'Menurun';
    }
    
    return [
        'trend' => $trend,
        'significant' => $significant,
        'slope' => round($medianSlope, 3),
        'Qmin' => round($Qmin, 3),
        'Qmax' => round($Qmax, 3)
    ];
}

function calcLinearRegression($data) {
    $n = count($data);
    if ($n < 3) return ['trend' => 'Tidak Ada Trend', 'slope' => 0, 'rSquared' => 0];
    
    $x = range(0, $n - 1);
    $y = array_column($data, 'value');
    
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

    $SSres = 0;
    $SStot = $Syy;

    for ($i = 0; $i < $n; $i++) {
        $yPred = $slope * $x[$i] + $intercept;
        $SSres += pow($y[$i] - $yPred, 2);
    }

    $rSquared = ($SStot > 0) ? 1 - ($SSres / $SStot) : 0;

    // ====== UJI SIGNIFIKANSI (t-test) ======
    $df = max(1, $n - 2);
    $tStat = 0;
    $tCritical = getCriticalT($df, 0.05);
    $significant = false;
    
    if ($n > 2 && $Sxx > 0) {
        $MSE = $SSres / $df;
        $stdErrSlope = sqrt($MSE / $Sxx);
        $tStat = ($stdErrSlope > 0) ? $slope / $stdErrSlope : 0;
        $significant = abs($tStat) > $tCritical;
    }
    
    $trend = 'Tidak Ada Trend';
    if ($significant) {
        if ($slope > 0) $trend = 'Meningkat';
        elseif ($slope < 0) $trend = 'Menurun';
    }
    
    return [
        'trend' => $trend,
        'significant' => $significant,
        'slope' => round($slope, 3),
        'rSquared' => round($rSquared, 3),
        'tStatistic' => round($tStat, 3)
    ];
}

// Proses semua
$results = [];
foreach ($stationsData as $pos_id => $data) {
    $n = count($data);
    
    // Hitung ekspektasi n untuk ketersediaan data (%)
    $expectedN = 1;
    $yearsSpan = $yearTo - $yearFrom + 1;

    if ($dataType === 'tahunan' || $dataType === 'musiman') {
        $expectedN = $yearsSpan;
    } elseif ($dataType === 'bulanan') {
        if ($monthFilter === 'all') {
            $expectedN = $yearsSpan * 12;
        } else {
            // Jika filter per bulan, ekspektasi hanya 1 data per tahun
            $expectedN = $yearsSpan;
        }
    } else {
        $expectedN = $yearsSpan * 365.25; // pendekatan harian
    }
    
    $completeness = ($expectedN > 0) ? ($n / $expectedN) : 0;
    
    // Safety limit: Skip ultra-heavy calculation for daily data bulk analysis if n is too large
    // Linear Regression is O(n), so it's always fast. MK and SS are O(n^2).
    if ($n > 1000) {
        $results[$pos_id] = [
            'completeness_period' => round($completeness, 3),
            'mann-kendall' => ['trend' => 'too_large', 'message' => 'Data terlalu besar untuk bulk analysis'],
            'sens-slope' => ['trend' => 'too_large', 'message' => 'Data terlalu besar untuk bulk analysis'],
            'regresi-linear' => calcLinearRegression($data)
        ];
    } else {
        $stationResult = [
            'completeness_period' => round($completeness, 3),
            'mann-kendall' => calcMannKendall($data),
            'sens-slope' => calcSensSlope($data),
            'regresi-linear' => calcLinearRegression($data)
        ];

        // Seasonal Mann-Kendall & Sen's Slope (hanya untuk bulanan dengan semua bulan)
        if ($dataType === 'bulanan' && $monthFilter === 'all') {
            $byMonthVal = [];
            $byMonthPts = [];
            foreach ($data as $point) {
                if (!isset($point['month'])) continue;
                $m = $point['month'];
                $yr = (int)floor($point['year']);
                $byMonthVal[$m][$yr] = $point['value'];
                $byMonthPts[$m][] = ['year' => (float)$yr, 'value' => $point['value']];
            }
            if (count($byMonthVal) >= 2) {
                $smk = calcSeasonalMannKendall($byMonthVal);
                $sss = calcSeasonalSensSlope($byMonthPts);

                $zCrit = getCriticalZ(0.05);
                $smkSig = abs($smk['Z']) > $zCrit;
                $smkTrend = 'Tidak Ada Trend';
                if ($smk['Z'] > $zCrit) $smkTrend = 'Meningkat';
                elseif ($smk['Z'] < -$zCrit) $smkTrend = 'Menurun';

                $stationResult['seasonal-mann-kendall'] = [
                    'trend' => $smkTrend,
                    'significant' => $smkSig,
                    'S' => round($smk['S'], 3),
                    'Z' => round($smk['Z'], 3),
                    'pValue' => round($smk['pValue'], 3),
                    'seasonCount' => $smk['seasonCount']
                ];

                $sssTrend = 'Tidak Ada Trend';
                if ($sss['slope'] > 0) $sssTrend = 'Meningkat';
                elseif ($sss['slope'] < 0) $sssTrend = 'Menurun';
                $stationResult['seasonal-sens-slope'] = [
                    'trend' => $sssTrend,
                    'slope' => round($sss['slope'], 3),
                    'slopeCount' => $sss['slopeCount'],
                    'seasonCount' => $sss['seasonCount']
                ];
            }
        }

        $results[$pos_id] = $stationResult;
    }
}

echo json_encode(['success' => true, 'results' => $results]);
?>
