<?php
/**
 * TrendHidro - Fetch Timeseries from DB
 * Helper function to query `data_ch` and aggregate the `rain` column
 */
require_once __DIR__ . '/../config/database.php';

function fetchTimeseriesData($input) {
    if (!isset($input['pos_id']) || !isset($input['yearFrom']) || !isset($input['yearTo'])) {
        return [];
    }

    $pos_id = $input['pos_id'];
    $dataType = isset($input['dataType']) ? $input['dataType'] : 'tahunan';
    $agregasi = isset($input['aggregation']) ? $input['aggregation'] : 'rerata';
    $yearFrom = (int) $input['yearFrom'];
    $yearTo = (int) $input['yearTo'];
    $monthFilter = isset($input['month']) ? $input['month'] : 'all';

    // Jika tahunan, paksa month filter ke all agar tidak terfilter sisa pilihan sebelumnya
    if ($dataType === 'tahunan') $monthFilter = 'all';

    $conn = getConnection();
    
    // As per user: "variabel total & rain memiliki nilai NULL. data yang dipakai utk perhitungan... adalah variabel rain"
    $where = "pos_id = :pos_id AND EXTRACT(YEAR FROM tanggal::date) >= :yearFrom AND EXTRACT(YEAR FROM tanggal::date) <= :yearTo AND rain IS NOT NULL";
    $params = ['pos_id' => $pos_id, 'yearFrom' => $yearFrom, 'yearTo' => $yearTo];
    
    if ($monthFilter !== 'all') {
        if (strpos($monthFilter, ',') !== false) {
            // Multi-month filter (seasonal)
            $months = array_map('intval', explode(',', $monthFilter));
            $placeholders = implode(',', array_fill(0, count($months), '?'));
            $where = str_replace(':pos_id', '?', str_replace(':yearFrom', '?', str_replace(':yearTo', '?', $where)));
            $where .= " AND EXTRACT(MONTH FROM tanggal::date) IN ($placeholders)";
            $params = array_merge([$pos_id, $yearFrom, $yearTo], $months);
        } else {
            $where = str_replace(':pos_id', '?', str_replace(':yearFrom', '?', str_replace(':yearTo', '?', $where)));
            $where .= " AND EXTRACT(MONTH FROM tanggal::date) = ?";
            $params = [$pos_id, $yearFrom, $yearTo, (int)$monthFilter];
        }
    } else {
        $where = str_replace(':pos_id', '?', str_replace(':yearFrom', '?', str_replace(':yearTo', '?', $where)));
        $params = [$pos_id, $yearFrom, $yearTo];
    }
    
    $data = [];
    
    // Choose agg func
    $aggFunc = 'AVG';
    if ($agregasi === 'kumulatif') $aggFunc = 'SUM';
    elseif ($agregasi === 'maks') $aggFunc = 'MAX';
    elseif ($agregasi === 'min') $aggFunc = 'MIN';
    
    if ($dataType === 'tahunan' || $dataType === 'musiman') {
        $sql = "SELECT EXTRACT(YEAR FROM tanggal::date) as x, $aggFunc(ROUND(rain::numeric, 0)) as y FROM data_ch WHERE $where GROUP BY x ORDER BY x";
        $stmt = $conn->prepare($sql);
    } 
    elseif ($dataType === 'bulanan') {
        $sql = "SELECT EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(MONTH FROM tanggal::date) as mo, $aggFunc(ROUND(rain::numeric, 0)) as y FROM data_ch WHERE $where GROUP BY yr, mo ORDER BY yr, mo";
        $stmt = $conn->prepare($sql);
    }
    else { // harian
        $sql = "SELECT EXTRACT(YEAR FROM tanggal::date) as yr, EXTRACT(DOY FROM tanggal::date) as dy, ROUND(rain::numeric, 0) as y FROM data_ch WHERE $where ORDER BY tanggal::date";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute($params);

    while ($row = $stmt->fetch()) {
        $x = 0;
        if ($dataType === 'tahunan' || $dataType === 'musiman') {
            $x = $row['x'];
        } elseif ($dataType === 'bulanan') {
            $x = $row['yr'] + ($row['mo'] - 1) / 12.0;
        } else {
            $x = $row['yr'] + ($row['dy'] - 1) / 365.0;
        }
        $data[] = [
            'year' => (float)$x,
            'value' => round((float)$row['y'], 0)
        ];
    }
    
    $conn = null;
    
    return $data;
}
?>
