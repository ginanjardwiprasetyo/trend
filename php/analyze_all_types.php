<?php
/**
 * TrendHidro - Analyze All Types Per Station
 * For each station, calculates MK, SS, LR for 18 data types:
 * Kumulatif Bulanan, Jan-Dec, JFM, AMJ, JAS, OND, Tahunan
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Location: ../error?error=forbidden");
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', sys_get_temp_dir() . '/php_error.log');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/lib/stats.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => true, 'message' => 'Data tidak valid']);
    exit;
}

$yearFrom = (int) ($input['yearFrom'] ?? 1980);
$yearTo = (int) ($input['yearTo'] ?? 2025);

try {
    $conn = getConnection();

    // Fetch all daily data in range
    $sql = "SELECT pos_id, EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(MONTH FROM tanggal::date) as mo, rain::numeric as val 
            FROM data_ch 
            WHERE EXTRACT(YEAR FROM tanggal::date) >= ? AND EXTRACT(YEAR FROM tanggal::date) <= ? AND rain IS NOT NULL 
            ORDER BY pos_id, tanggal::date";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$yearFrom, $yearTo]);

    // Group by station -> year -> month -> values[]
    $dailyData = [];
    while ($row = $stmt->fetch()) {
        $pos_id = $row['pos_id'];
        $y = (int) $row['yr'];
        $m = (int) $row['mo'];
        $val = (float) $row['val'];

        if (!isset($dailyData[$pos_id])) $dailyData[$pos_id] = [];
        if (!isset($dailyData[$pos_id][$y])) $dailyData[$pos_id][$y] = array_fill(1, 12, []);
        $dailyData[$pos_id][$y][$m][] = $val;
    }

    if (empty($dailyData)) {
        echo json_encode(['success' => true, 'results' => []]);
        exit;
    }

    // Define 18 types
    $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $types = ['Kumulatif Bulanan' => ['dt' => 'bulanan', 'mo' => 'all']];
    for ($i = 1; $i <= 12; $i++) {
        $types[$monthNames[$i - 1]] = ['dt' => 'bulanan', 'mo' => (string) $i];
    }
    $types['JFM'] = ['dt' => 'musiman', 'mo' => '1,2,3'];
    $types['AMJ'] = ['dt' => 'musiman', 'mo' => '4,5,6'];
    $types['JAS'] = ['dt' => 'musiman', 'mo' => '7,8,9'];
    $types['OND'] = ['dt' => 'musiman', 'mo' => '10,11,12'];
    $types['Tahunan'] = ['dt' => 'tahunan', 'mo' => 'all'];

    $results = [];

    foreach ($dailyData as $pos_id => $yearsData) {
        ksort($yearsData);
        $stationResults = [];

        foreach ($types as $typeName => $tc) {
            $dtType = $tc['dt'];
            $mo = $tc['mo'];
            $aggregated = [];

            if ($dtType === 'tahunan') {
                foreach ($yearsData as $y => $months) {
                    $sum = 0;
                    $hasData = false;
                    foreach ($months as $vals) {
                        if (!empty($vals)) {
                            $sum += array_sum($vals);
                            $hasData = true;
                        }
                    }
                    if ($hasData) {
                        $aggregated[] = ['year' => (float) $y, 'value' => $sum];
                    }
                }
            } elseif ($dtType === 'bulanan') {
                if ($mo === 'all') {
                    foreach ($yearsData as $y => $months) {
                        foreach ($months as $m => $vals) {
                            if (!empty($vals)) {
                                $aggregated[] = ['year' => $y + ($m - 1) / 12.0, 'value' => array_sum($vals)];
                            }
                        }
                    }
                } else {
                    $tm = (int) $mo;
                    foreach ($yearsData as $y => $months) {
                        if (!empty($months[$tm])) {
                            $aggregated[] = ['year' => (float) $y, 'value' => array_sum($months[$tm])];
                        }
                    }
                }
            } elseif ($dtType === 'musiman') {
                $seasonMonths = array_map('intval', explode(',', $mo));
                foreach ($yearsData as $y => $months) {
                    $sum = 0;
                    $hasData = false;
                    foreach ($seasonMonths as $sm) {
                        if (!empty($months[$sm])) {
                            $sum += array_sum($months[$sm]);
                            $hasData = true;
                        }
                    }
                    if ($hasData) {
                        $aggregated[] = ['year' => (float) $y, 'value' => $sum];
                    }
                }
            }

            $n = count($aggregated);
            $mk = ['trend' => 'Tidak Ada Trend', 'S' => 0, 'Z' => 0, 'pValue' => 1, 'n' => $n];
            $ss = ['trend' => 'Tidak Ada Trend', 'slope' => 0, 'Qmin' => 0, 'Qmax' => 0];
            $lr = ['trend' => 'Tidak Ada Trend', 'slope' => 0, 'rSquared' => 0, 'tStatistic' => 0];

            if ($n >= 3) {
                $mk = calcMannKendall($aggregated);
                $ss = calcSensSlope($aggregated);
                $lr = calcLinearRegression($aggregated);
            }

            $stationResults[$typeName] = [
                'mk' => $mk,
                'ss' => $ss,
                'lr' => $lr,
                'n' => $n
            ];
        }

        $results[$pos_id] = $stationResults;
    }

    echo json_encode(['success' => true, 'results' => $results]);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Gagal memproses: ' . $e->getMessage()]);
}

// ====== Calculation Functions ======

function calcMannKendall($data) {
    $n = count($data);
    if ($n < 3) return ['trend' => 'Tidak Ada Trend', 'S' => 0, 'Z' => 0, 'pValue' => 1, 'n' => $n];

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
    if ($varS <= 0) $varS = 1;

    if ($S > 0) $Z = ($S - 1) / sqrt($varS);
    elseif ($S < 0) $Z = ($S + 1) / sqrt($varS);
    else $Z = 0;

    $pValue = 2 * (1 - normalCDF(abs($Z)));
    $zCrit = getCriticalZ(0.05);

    $trend = 'Tidak Ada Trend';
    $significant = false;
    if ($Z > $zCrit) {
        $trend = 'Meningkat (Signifikan)';
        $significant = true;
    } elseif ($Z < -$zCrit) {
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
    if ($n < 3) return ['trend' => 'Tidak Ada Trend', 'slope' => 0, 'Qmin' => 0, 'Qmax' => 0];

    $slopes = [];
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $dx = $j - $i;
            if ($dx != 0) {
                $slopes[] = ($data[$j]['value'] - $data[$i]['value']) / $dx;
            }
        }
    }

    if (empty($slopes)) return ['trend' => 'Tidak Ada Trend', 'slope' => 0, 'Qmin' => 0, 'Qmax' => 0];

    sort($slopes);
    $count = count($slopes);
    $mid = floor($count / 2);
    $medianSlope = ($count % 2 == 0) ? ($slopes[$mid - 1] + $slopes[$mid]) / 2 : $slopes[$mid];

    $values = array_column($data, 'value');
    $mkBase = calcMannKendallBase($values, $n);
    $varS = $mkBase['varS'];
    if ($varS <= 0) $varS = 1;

    $C_alpha = getCriticalZ(0.05) * sqrt($varS);
    $M1 = ($count - $C_alpha) / 2;
    $M2 = ($count + $C_alpha) / 2;

    $idxLower = max(0, floor($M1) - 1);
    $idxUpper = min($count - 1, floor($M2 + 1) - 1);

    $Qmin = $slopes[$idxLower] ?? 0;
    $Qmax = $slopes[$idxUpper] ?? 0;

    $significant = ($Qmin > 0) || ($Qmax < 0);
    $trend = 'Tidak Ada Trend';
    if ($medianSlope > 0) $trend = 'Meningkat';
    elseif ($medianSlope < 0) $trend = 'Menurun';

    if ($trend !== 'Tidak Ada Trend') {
        $trend .= $significant ? ' (Signifikan)' : ' (Tidak Signifikan)';
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
    if ($n < 3) return ['trend' => 'Tidak Ada Trend', 'slope' => 0, 'rSquared' => 0, 'tStatistic' => 0];

    $x = range(0, $n - 1);
    $y = array_column($data, 'value');

    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $meanX = $sumX / $n;
    $meanY = $sumY / $n;

    $Sxx = 0; $Syy = 0; $Sxy = 0;
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
    for ($i = 0; $i < $n; $i++) {
        $yPred = $slope * $x[$i] + $intercept;
        $SSres += pow($y[$i] - $yPred, 2);
    }

    $rSquared = ($Syy > 0) ? 1 - ($SSres / $Syy) : 0;

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
    if ($slope > 0) $trend = 'Meningkat';
    elseif ($slope < 0) $trend = 'Menurun';

    if ($trend !== 'Tidak Ada Trend') {
        $trend .= $significant ? ' (Signifikan)' : ' (Tidak Signifikan)';
    }

    return [
        'trend' => $trend,
        'significant' => $significant,
        'slope' => round($slope, 3),
        'rSquared' => round($rSquared, 3),
        'tStatistic' => round($tStat, 3)
    ];
}
