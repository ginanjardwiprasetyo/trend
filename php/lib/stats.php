<?php
/**
 * TrendHidro - Fungsi Statistik Umum
 */

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;

use MathPHP\Probability\Distribution\Continuous\StudentT;
use MathPHP\Probability\Distribution\Continuous\Normal;

/**
 * Fungsi CDF distribusi normal standar (pendekatan Abramowitz & Stegun)
 */
function normalCDF($x) {
    $a1 = 0.254829592;
    $a2 = -0.284496736;
    $a3 = 1.421413741;
    $a4 = -1.453152027;
    $a5 = 1.061405429;
    $p  = 0.3275911;

    $sign = $x < 0 ? -1 : 1;
    $x = abs($x) / sqrt(2);

    $t = 1.0 / (1.0 + $p * $x);
    $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

    return 0.5 * (1.0 + $sign * $y);
}

/**
 * Fungsi menghitung signifikansi Mann-Kendall
 */
function calcMannKendallBase($values, $n) {
    if ($n < 3) return ['S' => 0, 'varS' => 0, 'Z' => 0, 'pValue' => 1];

    $S = 0;
    for ($k = 0; $k < $n - 1; $k++) {
        for ($j = $k + 1; $j < $n; $j++) {
            $diff = $values[$j] - $values[$k];
            if ($diff > 0) {
                $S += 1;
            } elseif ($diff < 0) {
                $S -= 1;
            }
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

    if ($S > 0) {
        $Z = ($S - 1) / sqrt($varS);
    } elseif ($S < 0) {
        $Z = ($S + 1) / sqrt($varS);
    } else {
        $Z = 0;
    }

    $pValue = 2 * (1 - normalCDF(abs($Z)));

    return ['S' => $S, 'varS' => $varS, 'Z' => $Z, 'pValue' => $pValue];
}

/**
 * Fungsi untuk mendapatkan nilai kritis distribusi t (two-tailed)
 * Menggunakan math-php StudentT distribution
 */
function getCriticalT($df, $alpha = 0.05) {
    if ($df <= 0) return 1.96;
    if (class_exists('MathPHP\Probability\Distribution\Continuous\StudentT')) {
        try {
            $t = new StudentT($df);
            return $t->inverse2Tails($alpha);
        } catch (\Throwable $e) {}
    }
    $table = [1=>12.706,2=>4.303,3=>3.182,4=>2.776,5=>2.571,6=>2.447,7=>2.365,8=>2.306,9=>2.262,10=>2.228,
        11=>2.201,12=>2.179,13=>2.160,14=>2.145,15=>2.131,16=>2.120,17=>2.110,18=>2.101,19=>2.093,20=>2.086,
        21=>2.080,22=>2.074,23=>2.069,24=>2.064,25=>2.060,26=>2.056,27=>2.052,28=>2.048,29=>2.045,30=>2.042];
    if ($df <= 30) return $table[$df] ?? 1.96;
    if ($df <= 60) return 1.671;
    if ($df <= 120) return 1.658;
    return 1.96;
}

function getCriticalZ($alpha = 0.05) {
    if (class_exists('MathPHP\Probability\Distribution\Continuous\Normal')) {
        try {
            $n = new Normal(0, 1);
            return $n->inverse(1 - $alpha / 2);
        } catch (\Throwable $e) {}
    }
    if ($alpha == 0.01) return 2.576;
    if ($alpha == 0.10) return 1.645;
    return 1.96;
}

/**
 * Seasonal Mann-Kendall Test (Hirsch, Slack & Smith, 1982)
 * Menerima data per season, menggabungkan statistik S dan var(S) dari semua season
 * 
 * @param array $dataBySeason [season_id => [year => value, ...], ...]
 * @return array ['S', 'varS', 'Z', 'pValue', 'seasonCount']
 */
function calcSeasonalMannKendall($dataBySeason) {
    $totalS = 0;
    $totalVarS = 0;
    $seasonCount = 0;

    foreach ($dataBySeason as $season => $values) {
        $n = count($values);
        if ($n < 3) continue;

        $values = array_values($values);
        $mk = calcMannKendallBase($values, $n);
        $totalS += $mk['S'];
        $totalVarS += $mk['varS'];
        $seasonCount++;
    }

    if ($seasonCount < 2) {
        return ['S' => $totalS, 'varS' => $totalVarS, 'Z' => 0, 'pValue' => 1, 'seasonCount' => $seasonCount];
    }

    if ($totalS > 0) {
        $Z = ($totalS - 1) / sqrt($totalVarS);
    } elseif ($totalS < 0) {
        $Z = ($totalS + 1) / sqrt($totalVarS);
    } else {
        $Z = 0;
    }

    $pValue = 2 * (1 - normalCDF(abs($Z)));

    return ['S' => $totalS, 'varS' => $totalVarS, 'Z' => $Z, 'pValue' => $pValue, 'seasonCount' => $seasonCount];
}

/**
 * Seasonal Sen's Slope Estimator
 * Menghitung slope dalam setiap season lalu menggabungkan semua slope
 * 
 * @param array $dataBySeason [season_id => [['year' => float, 'value' => float], ...], ...]
 * @return array ['slope', 'slopeCount', 'seasonCount']
 */
function calcSeasonalSensSlope($dataBySeason, $totalVarS = 0) {
    $allSlopes = [];
    $seasonCount = 0;

    foreach ($dataBySeason as $season => $points) {
        $n = count($points);
        if ($n < 3) continue;

        $seasonCount++;
        usort($points, function($a, $b) { return $a['year'] <=> $b['year']; });

        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $dx = $points[$j]['year'] - $points[$i]['year'];
                if ($dx != 0) {
                    $allSlopes[] = ($points[$j]['value'] - $points[$i]['value']) / $dx;
                }
            }
        }
    }

    if ($seasonCount < 2 || empty($allSlopes)) {
        return ['slope' => 0, 'Qmin' => 0, 'Qmax' => 0, 'slopeCount' => 0, 'seasonCount' => $seasonCount, 'significant' => false];
    }

    sort($allSlopes);
    $count = count($allSlopes);
    $mid = floor($count / 2);
    $medianSlope = ($count % 2 == 0) ? ($allSlopes[$mid - 1] + $allSlopes[$mid]) / 2 : $allSlopes[$mid];

    // Confidence interval via totalVarS dari seasonal MK
    $zC = getCriticalZ(0.05);
    $Ca = $zC * sqrt($totalVarS);
    $M1 = ($count - $Ca) / 2;
    $M2 = ($count + $Ca) / 2;
    $idxLower = max(0, floor($M1) - 1);
    $idxUpper = min($count - 1, floor($M2 + 1) - 1);
    $Qmin = $allSlopes[$idxLower] ?? 0;
    $Qmax = $allSlopes[$idxUpper] ?? 0;
    $significant = ($Qmin > 0) || ($Qmax < 0);

    return ['slope' => $medianSlope, 'Qmin' => round($Qmin, 3), 'Qmax' => round($Qmax, 3), 'slopeCount' => $count, 'seasonCount' => $seasonCount, 'significant' => $significant];
}

