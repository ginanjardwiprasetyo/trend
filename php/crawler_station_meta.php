<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID stasiun tidak valid']);
    exit;
}

$id = intval($_GET['id']);

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
    
    $url = "https://hidrologi.net/stasiunhujan/" . $id;
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        throw new Exception("Gagal mengambil data dari URL: " . $url);
    }
    
    // Parse the HTML using RegEx since the structure is consistent and simple
    // Example: <td>Pengelola</td> <td>:</td> <td>BBWS Bengawan Solo</td>
    
    $pengelola = null;
    $terlama = null;
    $terakhir = null;
    
    // Regex for Pengelola
    if (preg_match('/<td>Pengelola<\/td>\s*<td>:<\/td>\s*<td>(.*?)<\/td>/is', $html, $matches)) {
        $pengelola = trim(strip_tags($matches[1]));
    }
    
    // Regex for Data Terlama
    if (preg_match('/<td>Data Terlama<\/td>\s*<td>:<\/td>\s*<td>(.*?)<\/td>/is', $html, $matches)) {
        $terlama = intval(trim(strip_tags($matches[1])));
        if ($terlama == 0) $terlama = null; // if not valid int
    }
    
    // Regex for Data Terakhir
    if (preg_match('/<td>Data Terakhir<\/td>\s*<td>:<\/td>\s*<td>(.*?)<\/td>/is', $html, $matches)) {
        $terakhir = intval(trim(strip_tags($matches[1])));
        if ($terakhir == 0) $terakhir = null;
    }
    
    if (!$terlama && !$terakhir && !$pengelola) {
        // Mungkin stasiun tidak ditemukan atau struktur web berubah
        echo json_encode([
            'success' => false,
            'message' => 'Metadata stasiun tidak ditemukan atau kosong'
        ]);
        exit;
    }
    
    // Update Supabase
    $stmt = $pdo->prepare("
        UPDATE hidrologinet_stations 
        SET pengelola = ?, tahun_awal = ?, tahun_akhir = ?
        WHERE id = ?
    ");
    $stmt->execute([$pengelola, $terlama, $terakhir, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => "Berhasil sinkronisasi metadata stasiun ID $id",
        'data' => [
            'pengelola' => $pengelola,
            'tahun_awal' => $terlama,
            'tahun_akhir' => $terakhir
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
