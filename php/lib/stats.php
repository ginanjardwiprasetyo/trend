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

/**
 * Fungsi untuk mendapatkan nilai kritis distribusi t
 */
function getCriticalT($df, $alpha = 0.05) {
    if ($df <= 0) return 1.96;
    
    // T-table values for alpha = 0.05 (two-tailed)
    $tTable = [
        1 => 12.706, 2 => 4.303, 3 => 3.182, 4 => 2.776, 5 => 2.571,
        6 => 2.447, 7 => 2.365, 8 => 2.306, 9 => 2.262, 10 => 2.228,
        11 => 2.201, 12 => 2.179, 13 => 2.160, 14 => 2.145, 15 => 2.131,
        16 => 2.120, 17 => 2.110, 18 => 2.101, 19 => 2.093, 20 => 2.086,
        21 => 2.080, 22 => 2.074, 23 => 2.069, 24 => 2.064, 25 => 2.060,
        26 => 2.056, 27 => 2.052, 28 => 2.048, 29 => 2.045, 30 => 2.042,
        40 => 2.021, 50 => 2.009, 60 => 2.000, 80 => 1.990, 100 => 1.984,
        120 => 1.980
    ];

    if (isset($tTable[$df])) {
        return $tTable[$df];
    }
    
    // For df not directly in table but <= 120, find closest (or just interpolate, but simple fallback is fine)
    if ($df <= 30) {
        return $tTable[$df]; // Already covered
    } elseif ($df <= 40) {
        return 2.021;
    } elseif ($df <= 50) {
        return 2.009;
    } elseif ($df <= 60) {
        return 2.000;
    } elseif ($df <= 80) {
        return 1.990;
    } elseif ($df <= 100) {
        return 1.984;
    } elseif ($df <= 120) {
        return 1.980;
    }
    
    return 1.96; // Approximation for very large df
}

