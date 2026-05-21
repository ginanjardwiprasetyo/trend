<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    // Matikan SSL verifier karena hidrologi.net memiliki sertifikat SSL yang invalid
    $contextOptions = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ];
    $context = stream_context_create($contextOptions);
    
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $insertedCount = 0;
    
    $url = "https://hidrologi.net/daftar-stasiun-hujan/?halaman=" . $page;
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        throw new Exception("Gagal mengambil data dari URL: " . $url);
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query("//table//tr");
    
    foreach ($rows as $index => $row) {
        // Skip header
        if ($index === 0) continue;
        
        $cols = $xpath->query("td", $row);
        if ($cols->length >= 9) {
            $id = trim($cols->item(0)->nodeValue);
            if (!is_numeric($id)) continue;
            
            $nama = trim($cols->item(1)->nodeValue);
            $lon = trim($cols->item(2)->nodeValue);
            $lat = trim($cols->item(3)->nodeValue);
            $desa = trim($cols->item(4)->nodeValue);
            $kec = trim($cols->item(5)->nodeValue);
            $kab = trim($cols->item(6)->nodeValue);
            $prov = trim($cols->item(7)->nodeValue);
            $ws = trim($cols->item(8)->nodeValue);
            
            // Upsert into Supabase
            $stmt = $pdo->prepare("
                INSERT INTO hidrologinet_stations 
                (id, nama_stasiun, longitude, latitude, desa_kel, kecamatan, kab_kota, provinsi, wilayah_sungai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT (id) DO UPDATE SET
                    nama_stasiun = EXCLUDED.nama_stasiun,
                    longitude = EXCLUDED.longitude,
                    latitude = EXCLUDED.latitude,
                    desa_kel = EXCLUDED.desa_kel,
                    kecamatan = EXCLUDED.kecamatan,
                    kab_kota = EXCLUDED.kab_kota,
                    provinsi = EXCLUDED.provinsi,
                    wilayah_sungai = EXCLUDED.wilayah_sungai
            ");
            
            $stmt->execute([
                $id, $nama, floatval($lon), floatval($lat), $desa, $kec, $kab, $prov, $ws
            ]);
            $insertedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Berhasil mengambil $insertedCount stasiun dari halaman $page.",
        'count' => $insertedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
