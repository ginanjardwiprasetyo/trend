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
        $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as x, $aggFunc(ROUND(rain::numeric, 0)) as y FROM data_ch WHERE $where GROUP BY pos_id, EXTRACT(YEAR FROM tanggal::date) ORDER BY pos_id, x";
    } elseif ($dataType === 'bulanan') {
        $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(MONTH FROM tanggal::date) as mo, $aggFunc(ROUND(rain::numeric, 0)) as y FROM data_ch WHERE $where GROUP BY pos_id, yr, mo ORDER BY pos_id, yr, mo";
    } else {
        $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(DOY FROM tanggal::date) as dy, ROUND(rain::numeric, 0) as y FROM data_ch WHERE $where ORDER BY pos_id, tanggal::date";
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
        
        $stationsData[$pos_id][] = [
            'year' => (float)$x,
            'value' => round((float)$row['y'], 0) // Pembulatan angka 0 desimal hanya berlaku untuk data di database
        ];
    }
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Terjadi kesalahan sistem saat mengolah data: ' . $e->getMessage()]);
    exit;
}

// Fungsi-fungsi Hitung
function calcMannKendall($data) {
    $n = count($data);
    if ($n < 3) return ['trend' => 'Tidak Ada Tren', 'S' => 0, 'Z' => 0, 'pValue' => 1];
    
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
    if ($n < 3) return ['trend' => 'Tidak Ada Tren', 'slope' => 0];
    
    $slopes = [];
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $dx = $data[$j]['year'] - $data[$i]['year'];
            if ($dx != 0) {
                $slopes[] = ($data[$j]['value'] - $data[$i]['value']) / $dx;
            }
        }
    }
    
    if (empty($slopes)) return ['trend' => 'Tidak Ada Tren', 'slope' => 0];
    
    sort($slopes);
    $count = count($slopes);
    $mid = floor($count / 2);
    if ($count % 2 == 0) {
        $medianSlope = ($slopes[$mid - 1] + $slopes[$mid]) / 2;
    } else {
        $medianSlope = $slopes[$mid];
    }
    
    // Signifikansi pinjam MK
    $trend = 'Tidak Ada Tren';
    if ($medianSlope > 0) {
        $trend = 'Meningkat';
    } elseif ($medianSlope < 0) {
        $trend = 'Menurun';
    }
    
    return [
        'trend' => $trend,
        'slope' => round($medianSlope, 3)
    ];
}

function calcLinearRegression($data) {
    $n = count($data);
    if ($n < 3) return ['trend' => 'Tidak Ada Tren', 'slope' => 0, 'rSquared' => 0];
    
    $x = array_column($data, 'year');
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

    $stdErrSlope = ($n > 2 && $Sxx > 0) ? sqrt(($SSres / ($n - 2)) / $Sxx) : 0;
    $tStat = $stdErrSlope == 0 ? 0 : $slope / $stdErrSlope;
    
    $significant = abs($tStat) > 2.0; // aprox
    
    $trend = 'Tidak Ada Tren';
    if ($slope > 0) {
        $trend = 'Meningkat';
    } elseif ($slope < 0) {
        $trend = 'Menurun';
    }
    
    return [
        'trend' => $trend,
        'slope' => round($slope, 3),
        'rSquared' => round($rSquared, 3) 
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
        $results[$pos_id] = [
            'completeness_period' => round($completeness, 3),
            'mann-kendall' => calcMannKendall($data),
            'sens-slope' => calcSensSlope($data),
            'regresi-linear' => calcLinearRegression($data)
        ];
    }
}

echo json_encode(['success' => true, 'results' => $results]);
?>
