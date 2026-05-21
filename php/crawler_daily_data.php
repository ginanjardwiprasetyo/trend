<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Parameter validation
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['year']) || !is_numeric($_GET['year'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter id dan year diperlukan dan harus berupa angka']);
    exit;
}

$id = intval($_GET['id']);
$year = intval($_GET['year']);

try {
    $pdo = getConnection();
    
    // Matikan SSL verifier
    $contextOptions = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ];
    $context = stream_context_create($contextOptions);
    
    $url = "https://hidrologi.net/datahujan-{$id}/{$year}";
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        throw new Exception("Gagal mengambil data dari URL: " . $url);
    }
    
    // Check if data is available (e.g. "Data hujan untuk stasiun ini di tahun 2010 belum ada")
    if (strpos($html, 'belum ada') !== false || strpos($html, 'Data hujan') !== false && strpos($html, 'belum ada') !== false) {
        echo json_encode([
            'success' => true,
            'message' => "Tidak ada data untuk tahun $year",
            'count' => 0
        ]);
        exit;
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    $xpath = new DOMXPath($dom);
    
    // Tabel kedua biasanya adalah tabel data curah hujan
    $tables = $xpath->query("//table");
    
    if ($tables->length < 2) {
        // Jika tidak ada tabel data harian
        echo json_encode([
            'success' => true,
            'message' => "Tabel data harian tidak ditemukan di tahun $year",
            'count' => 0
        ]);
        exit;
    }
    
    $dataTable = $tables->item(1);
    $rows = $xpath->query(".//tr", $dataTable);
    
    $insertData = [];
    
    // Helper function to validate date
    function isValidDate($y, $m, $d) {
        return checkdate($m, $d, $y);
    }
    
    foreach ($rows as $index => $row) {
        // Lewati header (Tanggal, Jan, Feb, dst)
        if ($index === 0) continue;
        
        $cols = $xpath->query(".//td", $row);
        
        // Pastikan baris memiliki 13 kolom (Tanggal + 12 Bulan)
        if ($cols->length >= 13) {
            $day = intval(trim($cols->item(0)->nodeValue));
            if ($day < 1 || $day > 31) continue;
            
            for ($month = 1; $month <= 12; $month++) {
                // Periksa apakah tanggal valid di kalender (hindari 30 Februari, 31 April, dll)
                if (!isValidDate($year, $month, $day)) {
                    continue;
                }
                
                $valString = trim($cols->item($month)->nodeValue);
                // "0.00", "-", atau "15.5" dsb.
                if ($valString === '-' || $valString === '') {
                    $val = null; // Tidak ada data
                } else {
                    $val = floatval($valString);
                }
                
                // Format tanggal YYYY-MM-DD
                $dateString = sprintf("%04d-%02d-%02d", $year, $month, $day);
                
                // Masukkan ke array batch insert
                if ($val !== null) {
                    $insertData[] = [
                        $id,
                        $dateString,
                        $val
                    ];
                }
            }
        }
    }
    
    if (empty($insertData)) {
        echo json_encode([
            'success' => true,
            'message' => "Data ditemukan tetapi semuanya bernilai kosong/strip di tahun $year",
            'count' => 0
        ]);
        exit;
    }
    
    // Bulk Insert ke Supabase dengan ON CONFLICT
    $pdo->beginTransaction();
    
    // Batch size 100 untuk menghindari query string terlalu panjang
    $batchSize = 100;
    $insertedCount = 0;
    
    for ($i = 0; $i < count($insertData); $i += $batchSize) {
        $batch = array_slice($insertData, $i, $batchSize);
        
        $placeholders = array_fill(0, count($batch), '(?, ?, ?)');
        $sql = "INSERT INTO hidrologinet_daily_rainfall (station_id, tanggal, curah_hujan) VALUES " . implode(', ', $placeholders) . " 
                ON CONFLICT (station_id, tanggal) DO UPDATE SET curah_hujan = EXCLUDED.curah_hujan";
        
        $stmt = $pdo->prepare($sql);
        
        $flatValues = [];
        foreach ($batch as $row) {
            $flatValues[] = $row[0];
            $flatValues[] = $row[1];
            $flatValues[] = $row[2];
        }
        
        $stmt->execute($flatValues);
        $insertedCount += count($batch);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Berhasil sinkronisasi $insertedCount data harian stasiun ID $id tahun $year",
        'count' => $insertedCount
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
