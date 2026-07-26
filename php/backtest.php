<?php
/**
 * TrendHidro - Back Test API
 * Mengembalikan perhitungan step-by-step dari setiap metode analisis trend
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('memory_limit', '512M');
set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../error?error=forbidden");
    exit;
}

require_once 'lib/stats.php';

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
    echo json_encode(['error' => true, 'message' => 'Minimal 3 data diperlukan']);
    exit;
}

usort($data, function($a, $b) { return $a['year'] <=> $b['year']; });

$values = array_column($data, 'value');
$years = range(0, $n - 1);

// Always show sample rows for traceability
$maxSampleRows = 10; // 5 awal + 5 akhir

// ====== 1. MANN-KENDALL STEP-BY-STEP ======
$mkSteps = [];
$S = 0;
$signFirst5 = [];
$signLast5 = [];
$pairCount = 0;

for ($k = 0; $k < $n - 1; $k++) {
    for ($j = $k + 1; $j < $n; $j++) {
        $diff = $values[$j] - $values[$k];
        $sign = 0;
        if ($diff > 0) $sign = 1;
        elseif ($diff < 0) $sign = -1;
        $S += $sign;

        $row = [
            'i' => $k + 1,
            'j' => $j + 1,
            'xi' => round($values[$k], 3),
            'xj' => round($values[$j], 3),
            'diff' => round($diff, 3),
            'sign' => $sign
        ];

        if ($pairCount < 5) {
            $signFirst5[] = $row;
        }
        // Ring buffer for last 5
        $signLast5[] = $row;
        if (count($signLast5) > 5) array_shift($signLast5);

        $pairCount++;
    }
}

// Build sample table
if ($pairCount <= $maxSampleRows) {
    $signTable = $signFirst5;
    // Merge remaining from last5 that aren't already in first5
    foreach ($signLast5 as $lr) {
        $found = false;
        foreach ($signFirst5 as $fr) {
            if ($fr['i'] === $lr['i'] && $fr['j'] === $lr['j']) { $found = true; break; }
        }
        if (!$found) $signTable[] = $lr;
    }
} else {
    $signTable = $signFirst5;
    $signTable[] = ['separator' => true, 'skipped' => $pairCount - 10];
    $signTable = array_merge($signTable, $signLast5);
}

// Tied groups
$uniqueValues = array_count_values(array_map('strval', $values));
$tiedGroups = [];
$tiedGroupSum = 0;
foreach ($uniqueValues as $val => $count) {
    if ($count > 1) {
        $contribution = $count * ($count - 1) * (2 * $count + 5);
        $tiedGroupSum += $contribution;
        $tiedGroups[] = ['value' => $val, 'count' => $count, 'contribution' => $contribution];
    }
}

$varS_numerator = $n * ($n - 1) * (2 * $n + 5);
$varS = ($varS_numerator - $tiedGroupSum) / 18;
$stdS = sqrt($varS);

// Z-score with numeric substitution
if ($S > 0) {
    $Z = ($S - 1) / $stdS;
    $Z_formula_generic = '(S − 1) / √Var(S)  [karena S > 0]';
    $Z_formula_numeric = '(' . $S . ' − 1) / √' . round($varS, 4) . ' = ' . ($S - 1) . ' / ' . round($stdS, 4);
} elseif ($S < 0) {
    $Z = ($S + 1) / $stdS;
    $Z_formula_generic = '(S + 1) / √Var(S)  [karena S < 0]';
    $Z_formula_numeric = '(' . $S . ' + 1) / √' . round($varS, 4) . ' = ' . ($S + 1) . ' / ' . round($stdS, 4);
} else {
    $Z = 0;
    $Z_formula_generic = '0  [karena S = 0]';
    $Z_formula_numeric = '0';
}

$absZ = abs($Z);
$zCrit = getCriticalZ(0.05);
$significant = $absZ > $zCrit;

// Count positive and negative signs
$positiveCount = 0; $negativeCount = 0; $tieCount = 0;
for ($k = 0; $k < $n - 1; $k++) {
    for ($j2 = $k + 1; $j2 < $n; $j2++) {
        $d = $values[$j2] - $values[$k];
        if ($d > 0) $positiveCount++;
        elseif ($d < 0) $negativeCount++;
        else $tieCount++;
    }
}

$mkTrend = 'Tidak Ada Tren';
if ($Z > $zCrit) $mkTrend = 'Meningkat (Signifikan)';
elseif ($Z < -$zCrit) $mkTrend = 'Menurun (Signifikan)';
elseif ($Z > 0) $mkTrend = 'Meningkat (Tidak Signifikan)';
elseif ($Z < 0) $mkTrend = 'Menurun (Tidak Signifikan)';

$mkResult = [
    'input_n' => $n,
    'sign_table' => $signTable,
    'total_pairs' => $pairCount,
    'positive_count' => $positiveCount,
    'negative_count' => $negativeCount,
    'tie_count' => $tieCount,
    'S' => $S,
    'S_formula_numeric' => '(+' . $positiveCount . ') + (−' . $negativeCount . ') = ' . $S,
    'tied_groups' => $tiedGroups,
    'n_for_var' => $n,
    'n_minus_1' => $n - 1,
    'two_n_plus_5' => 2 * $n + 5,
    'varS_formula' => "[n(n−1)(2n+5) − Σtₚ(tₚ−1)(2tₚ+5)] / 18",
    'varS_formula_numeric' => "[$n × " . ($n - 1) . " × " . (2 * $n + 5) . " − $tiedGroupSum] / 18 = ($varS_numerator − $tiedGroupSum) / 18",
    'varS_numerator' => $varS_numerator,
    'tied_sum' => $tiedGroupSum,
    'varS' => round($varS, 4),
    'stdS' => round($stdS, 4),
    'Z_formula' => $Z_formula_generic,
    'Z_formula_numeric' => $Z_formula_numeric,
    'Z' => round($Z, 4),
    'abs_Z' => round($absZ, 4),
    'threshold' => round($zCrit, 4),
    'alpha' => 0.05,
    'significant' => $significant,
    'trend' => $mkTrend
];

// ====== 2. SEN'S SLOPE STEP-BY-STEP ======
$slopes = [];
$slopeFirst5 = [];
$slopeLast5 = [];
$slopePairCount = 0;

for ($i = 0; $i < $n - 1; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        $dx = $years[$j] - $years[$i];
        if ($dx != 0) {
            $slope = ($values[$j] - $values[$i]) / $dx;
            $slopes[] = $slope;

            $row = [
                'i' => $i + 1,
                'j' => $j + 1,
                'yi' => round($values[$i], 3),
                'yj' => round($values[$j], 3),
                'xi' => round($years[$i], 4),
                'xj' => round($years[$j], 4),
                'slope' => round($slope, 6)
            ];

            if ($slopePairCount < 5) {
                $slopeFirst5[] = $row;
            }
            $slopeLast5[] = $row;
            if (count($slopeLast5) > 5) array_shift($slopeLast5);

            $slopePairCount++;
        }
    }
}

// Build sample table
if ($slopePairCount <= $maxSampleRows) {
    $slopeTable = $slopeFirst5;
    foreach ($slopeLast5 as $lr) {
        $found = false;
        foreach ($slopeFirst5 as $fr) {
            if ($fr['i'] === $lr['i'] && $fr['j'] === $lr['j']) { $found = true; break; }
        }
        if (!$found) $slopeTable[] = $lr;
    }
} else {
    $slopeTable = $slopeFirst5;
    $slopeTable[] = ['separator' => true, 'skipped' => $slopePairCount - 10];
    $slopeTable = array_merge($slopeTable, $slopeLast5);
}

sort($slopes);
$slopeCount = count($slopes);
if ($slopeCount === 0) {
    $senSlope = 0;
    $medianExplanation = 'Tidak ada slope yang dihitung';
} elseif ($slopeCount % 2 === 0) {
    $pos1 = $slopeCount / 2 - 1;
    $pos2 = $slopeCount / 2;
    $senSlope = ($slopes[$pos1] + $slopes[$pos2]) / 2;
    $medianExplanation = "N genap → median = rata-rata posisi ke-" . ($pos1 + 1) . " dan ke-" . ($pos2 + 1) . " = (" . round($slopes[$pos1], 6) . " + " . round($slopes[$pos2], 6) . ") / 2";
} else {
    $pos = floor($slopeCount / 2);
    $senSlope = $slopes[$pos];
    $medianExplanation = "N ganjil → median = nilai posisi ke-" . ($pos + 1) . " = " . round($slopes[$pos], 6);
}

// Sorted slopes sample: show first 3, around median, last 3
$sortedSlopesSample = [];
if ($slopeCount <= 15) {
    foreach ($slopes as $idx => $sl) {
        $sortedSlopesSample[] = ['pos' => $idx + 1, 'value' => round($sl, 6)];
    }
} else {
    // First 3
    for ($si = 0; $si < 3; $si++) {
        $sortedSlopesSample[] = ['pos' => $si + 1, 'value' => round($slopes[$si], 6)];
    }
    // Separator
    $medIdx = intval(floor($slopeCount / 2));
    $aroundStart = max(3, $medIdx - 2);
    $aroundEnd = min($slopeCount - 4, $medIdx + 2);
    if ($aroundStart > 3) {
        $sortedSlopesSample[] = ['pos' => '⋮', 'value' => '...(' . ($aroundStart - 3) . ' baris)...'];
    }
    // Around median
    for ($si = $aroundStart; $si <= $aroundEnd; $si++) {
        $sortedSlopesSample[] = ['pos' => $si + 1, 'value' => round($slopes[$si], 6), 'is_median' => ($si === $medIdx)];
    }
    // Separator
    if ($aroundEnd < $slopeCount - 4) {
        $sortedSlopesSample[] = ['pos' => '⋮', 'value' => '...(' . ($slopeCount - 4 - $aroundEnd) . ' baris)...'];
    }
    // Last 3
    for ($si = $slopeCount - 3; $si < $slopeCount; $si++) {
        $sortedSlopesSample[] = ['pos' => $si + 1, 'value' => round($slopes[$si], 6)];
    }
}

// Intercept
$intercepts = [];
for ($i = 0; $i < $n; $i++) {
    $intercepts[] = $values[$i] - $senSlope * $years[$i];
}
sort($intercepts);
if ($n % 2 === 0) {
    $ssIntercept = ($intercepts[$n / 2 - 1] + $intercepts[$n / 2]) / 2;
} else {
    $ssIntercept = $intercepts[floor($n / 2)];
}

// Intercept sample table (always show sample)
$allIntercepts = [];
for ($i = 0; $i < $n; $i++) {
    $allIntercepts[] = [
        'i' => $i + 1,
        'y' => round($values[$i], 3),
        'x' => round($years[$i], 4),
        'intercept' => round($values[$i] - $senSlope * $years[$i], 4)
    ];
}
$interceptSample = [];
if ($n <= $maxSampleRows) {
    $interceptSample = $allIntercepts;
} else {
    for ($s = 0; $s < 5; $s++) $interceptSample[] = $allIntercepts[$s];
    $interceptSample[] = ['separator' => true, 'skipped' => $n - 10];
    for ($s = $n - 5; $s < $n; $s++) $interceptSample[] = $allIntercepts[$s];
}
unset($allIntercepts);

$C_alpha = getCriticalZ(0.05) * sqrt($varS);
$M1 = ($slopeCount - $C_alpha) / 2;
$M2 = ($slopeCount + $C_alpha) / 2;

$idxLower = max(0, floor($M1) - 1);
$idxUpper = min($slopeCount - 1, floor($M2 + 1) - 1);

$Qmin = $slopes[$idxLower] ?? 0;
$Qmax = $slopes[$idxUpper] ?? 0;

$ssSignificant = ($Qmin > 0) || ($Qmax < 0);

$ssTrend = 'Tidak Ada Tren';
if ($senSlope > 0) {
    $ssTrend = 'Meningkat';
} elseif ($senSlope < 0) {
    $ssTrend = 'Menurun';
}

if ($ssTrend !== 'Tidak Ada Tren') {
    $ssTrend .= $ssSignificant ? ' (Signifikan)' : ' (Tidak Signifikan)';
}

$ssResult = [
    'slope_table' => $slopeTable,
    'total_slope_pairs' => $slopePairCount,
    'slopes_sorted_count' => $slopeCount,
    'sorted_slopes_sample' => $sortedSlopesSample,
    'median_position' => $slopeCount % 2 === 0
        ? "rata-rata posisi " . ($slopeCount / 2) . " dan " . ($slopeCount / 2 + 1)
        : "posisi " . (floor($slopeCount / 2) + 1),
    'median_explanation' => $medianExplanation,
    'sen_slope' => round($senSlope, 6),
    'intercept_sample' => $interceptSample,
    'intercept_formula' => 'bᵢ = yᵢ − slope × xᵢ, lalu ambil median dari semua bᵢ',
    'intercept' => round($ssIntercept, 4),
    'trend' => $ssTrend,
    'equation' => 'y = ' . round($senSlope, 6) . ' × x + (' . round($ssIntercept, 4) . ')',
    'C_alpha' => round($C_alpha, 4),
    'M1' => round($M1, 4),
    'M2' => round($M2, 4),
    'Qmin' => round($Qmin, 6),
    'Qmax' => round($Qmax, 6),
    'significant' => $ssSignificant
];

// ====== 3. REGRESI LINEAR STEP-BY-STEP ======
$sumX = array_sum($years);
$sumY = array_sum($values);
$meanX = $sumX / $n;
$meanY = $sumY / $n;

$Sxx = 0; $Syy = 0; $Sxy = 0;
$allLrRows = [];

for ($i = 0; $i < $n; $i++) {
    $dx = $years[$i] - $meanX;
    $dy = $values[$i] - $meanY;
    $Sxx += $dx * $dx;
    $Syy += $dy * $dy;
    $Sxy += $dx * $dy;
    $allLrRows[] = [
        'i' => $i + 1,
        'x' => round($years[$i], 4),
        'y' => round($values[$i], 3),
        'dx' => round($dx, 4),
        'dy' => round($dy, 3),
        'dx2' => round($dx * $dx, 4),
        'dy2' => round($dy * $dy, 4),
        'dxdy' => round($dx * $dy, 4)
    ];
}

// Build sample: first 5 + last 5
$lrTable = [];
if ($n <= $maxSampleRows) {
    $lrTable = $allLrRows;
} else {
    for ($s = 0; $s < 5; $s++) $lrTable[] = $allLrRows[$s];
    $lrTable[] = ['separator' => true, 'skipped' => $n - 10];
    for ($s = $n - 5; $s < $n; $s++) $lrTable[] = $allLrRows[$s];
}
unset($allLrRows);

$lrSlope = ($Sxx != 0) ? $Sxy / $Sxx : 0;
$lrIntercept = $meanY - $lrSlope * $meanX;

// R² with prediction table
$SSres = 0;
$allPredRows = [];
for ($i = 0; $i < $n; $i++) {
    $yPred = $lrSlope * $years[$i] + $lrIntercept;
    $residual = $values[$i] - $yPred;
    $SSres += $residual * $residual;
    $allPredRows[] = [
        'i' => $i + 1,
        'x' => round($years[$i], 4),
        'y_actual' => round($values[$i], 3),
        'y_pred' => round($yPred, 3),
        'residual' => round($residual, 3),
        'residual_sq' => round($residual * $residual, 4)
    ];
}

// Build sample: first 5 + last 5
$predictionTable = [];
if ($n <= $maxSampleRows) {
    $predictionTable = $allPredRows;
} else {
    for ($s = 0; $s < 5; $s++) $predictionTable[] = $allPredRows[$s];
    $predictionTable[] = ['separator' => true, 'skipped' => $n - 10];
    for ($s = $n - 5; $s < $n; $s++) $predictionTable[] = $allPredRows[$s];
}
unset($allPredRows);
$rSquared = ($Syy > 0) ? 1 - ($SSres / $Syy) : 0;
$r = ($lrSlope >= 0 ? 1 : -1) * sqrt(max(0, $rSquared));

// t-test
$SE = 0; $tStat = 0; $MSE = 0;
$df = max(1, $n - 2);
$tCritical = getCriticalT($df, 0.05);

if ($n > 2 && $Sxx > 0) {
    $MSE = $SSres / $df;
    $SE = sqrt($MSE / $Sxx);
    $tStat = ($SE > 0) ? $lrSlope / $SE : 0;
}

$tSignificant = abs($tStat) > $tCritical;

$lrTrend = 'Tidak Ada Tren';
if ($lrSlope > 0) {
    $lrTrend = 'Meningkat';
} elseif ($lrSlope < 0) {
    $lrTrend = 'Menurun';
}

if ($lrTrend !== 'Tidak Ada Tren') {
    $lrTrend .= $tSignificant ? ' (Signifikan)' : ' (Tidak Signifikan)';
}

$lrResult = [
    'detail_table' => $lrTable,
    'prediction_table' => $predictionTable,
    'n' => $n,
    'sum_x' => round($sumX, 4),
    'sum_y' => round($sumY, 3),
    'mean_x' => round($meanX, 4),
    'mean_y' => round($meanY, 3),
    'mean_x_formula' => "Σx / n = " . round($sumX, 4) . " / $n",
    'mean_y_formula' => "Σy / n = " . round($sumY, 3) . " / $n",
    'Sxx' => round($Sxx, 4),
    'Syy' => round($Syy, 4),
    'Sxy' => round($Sxy, 4),
    'Sxx_formula' => 'Sxx = Σ(xᵢ − x̄)² = ' . round($Sxx, 4),
    'Syy_formula' => 'Syy = Σ(yᵢ − ȳ)² = ' . round($Syy, 4),
    'Sxy_formula' => 'Sxy = Σ(xᵢ − x̄)(yᵢ − ȳ) = ' . round($Sxy, 4),
    'slope_formula' => 'Sxy / Sxx = ' . round($Sxy, 4) . ' / ' . round($Sxx, 4),
    'slope' => round($lrSlope, 6),
    'intercept_formula' => 'ȳ − slope × x̄ = ' . round($meanY, 3) . ' − ' . round($lrSlope, 6) . ' × ' . round($meanX, 4),
    'intercept' => round($lrIntercept, 4),
    'SSres' => round($SSres, 4),
    'SSres_formula' => 'SSres = Σ(yᵢ − ŷᵢ)² = ' . round($SSres, 4),
    'SStot' => round($Syy, 4),
    'SStot_formula' => 'SStot = Syy = ' . round($Syy, 4),
    'rSquared' => round($rSquared, 6),
    'rSquared_formula' => 'R² = 1 − SSres/SStot = 1 − ' . round($SSres, 4) . '/' . round($Syy, 4),
    'r' => round($r, 6),
    'r_formula' => 'r = ' . ($lrSlope >= 0 ? '+' : '−') . '√R² = ' . ($lrSlope >= 0 ? '+' : '−') . '√' . round($rSquared, 6),
    'df' => $df,
    'MSE' => $n > 2 ? round($MSE, 4) : null,
    'MSE_formula' => $n > 2 ? 'SSres / (n−2) = ' . round($SSres, 4) . ' / ' . $df : null,
    'SE' => round($SE, 6),
    'SE_formula' => $n > 2 ? '√(MSE / Sxx) = √(' . round($MSE, 4) . ' / ' . round($Sxx, 4) . ')' : null,
    't_statistic' => round($tStat, 4),
    't_formula' => 'slope / SE = ' . round($lrSlope, 6) . ' / ' . round($SE, 6),
    't_critical' => $tCritical,
    't_significant' => $tSignificant,
    'equation' => 'y = ' . round($lrSlope, 6) . 'x + (' . round($lrIntercept, 4) . ')',
    'trend' => $lrTrend
];

// ====== OUTPUT ======
echo json_encode([
    'success' => true,
    'n' => $n,
    'data_input' => array_map(function($d) {
        return ['year' => round($d['year'], 4), 'value' => round($d['value'], 3)];
    }, $data),
    'mann_kendall' => $mkResult,
    'sens_slope' => $ssResult,
    'regresi_linear' => $lrResult
]);
