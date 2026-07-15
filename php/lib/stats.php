<?php
/**
 * TrendHidro - Fungsi Statistik Umum
 */

require_once __DIR__ . '/../../vendor/autoload.php';

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
    try {
        $t = new StudentT($df);
        return $t->inverse2Tails($alpha);
    } catch (\Exception $e) {
        return 1.96;
    }
}

/**
 * Fungsi untuk mendapatkan nilai kritis distribusi Z normal (two-tailed)
 * Menggunakan math-php Normal distribution
 */
function getCriticalZ($alpha = 0.05) {
    try {
        $n = new Normal(0, 1);
        return $n->inverse(1 - $alpha / 2);
    } catch (\Exception $e) {
        return 1.96;
    }
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
function calcSeasonalSensSlope($dataBySeason) {
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
        return ['slope' => 0, 'slopeCount' => 0, 'seasonCount' => $seasonCount];
    }

    sort($allSlopes);
    $count = count($allSlopes);
    $mid = floor($count / 2);
    $medianSlope = ($count % 2 == 0) ? ($allSlopes[$mid - 1] + $allSlopes[$mid]) / 2 : $allSlopes[$mid];

    return ['slope' => $medianSlope, 'slopeCount' => $count, 'seasonCount' => $seasonCount];
}

