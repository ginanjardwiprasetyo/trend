<?php
/**
 * TrendHidro - Fungsi Statistik Umum
 */

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
