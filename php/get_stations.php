<?php
/**
 * TrendHidro - Ambil Stasiun
 * Mengambil semua stasiun dari tabel `pos_hujan` dan rentang tahun riil + kelengkapan dari `data_ch`
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

ini_set('max_execution_time', '60');
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

require_once __DIR__ . '/../config/database.php';

// --- Cegah Akses Langsung (Browser) ---
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
    header("Location: ../error?error=forbidden");
    exit;
}

try {
    // --- CACHING LOGIC ---
    $cacheFile = __DIR__ . '/cache_stations.json';
    $cacheTime = 300; // 5 menit
    
    $isLite = isset($_GET['lite']) && $_GET['lite'] == '1';
    
    // Jika bukan mode lite, cek cache
    if (!$isLite && file_exists($cacheFile) && filesize($cacheFile) > 0 && (time() - filemtime($cacheFile) < $cacheTime)) {
        header('X-Cache: HIT');
        readfile($cacheFile);
        exit;
    }

    $conn = getConnection();
    
    // 1. Ambil data stasiun dari `pos_hujan`
    $stations = [];
    $stmt = $conn->query("SELECT * FROM pos_hujan");
    $stations = $stmt->fetchAll();

    // 2. Ambil parameter statistik dari `data_ch`
    $stats = [];
    
    if (!$isLite) {
        try {
            // Optimasi: Hanya hitung jika benar-benar dibutuhkan atau cache expired
            $statStmt = $conn->query("
                SELECT pos_id, 
                       MAX(wilayah) as wilayah,
                       MIN(CASE WHEN rain IS NOT NULL THEN EXTRACT(YEAR FROM (tanggal::date)) END) as minYear, 
                       MAX(CASE WHEN rain IS NOT NULL THEN EXTRACT(YEAR FROM (tanggal::date)) END) as maxYear, 
                       COUNT(CASE WHEN rain IS NOT NULL THEN 1 END) as countRain,
                       MAX(tanggal::date) - MIN(tanggal::date) + 1 as totalDays
                FROM data_ch 
                GROUP BY pos_id
            ");
            
            while ($row = $statStmt->fetch()) {
                $total = (int)$row['totaldays'];
                $available = (int)$row['countrain'];
                $pct = ($total > 0) ? round(($available / $total) * 100, 2) : 0;
                $row['completeness'] = min(100, max(0, $pct));
                $stats[$row['pos_id']] = $row;
            }
        } catch (Exception $e) {
            error_log("Gagal mengambil statistik data: " . $e->getMessage());
        }
    }

    // 3. Mapping dinamis
    $features = [];
    foreach ($stations as $st) {
        $lat = 0; $lon = 0;
        if (isset($st['koordinat']) && !empty($st['koordinat'])) {
            $pts = explode(',', str_replace(['(', ')'], '', $st['koordinat']));
            if(count($pts) >= 2) {
                $lat = (float)trim($pts[0]);
                $lon = (float)trim($pts[1]);
            }
        }
        if (abs($lat) < 0.1 && abs($lon) < 0.1) continue;
        
        $pos_id = isset($st['pos_id']) ? $st['pos_id'] : (isset($st['id']) ? $st['id'] : 'unknown');
        $nama = (isset($st['nama_pos']) && !empty($st['nama_pos'])) ? $st['nama_pos'] : $pos_id;
        $lokasi = (isset($st['lokasi']) && !empty($st['lokasi'])) ? $st['lokasi'] : '-';
        
        $st_stats = isset($stats[$pos_id]) ? $stats[$pos_id] : [
            'minyear' => null, 'maxyear' => null, 'completeness' => 0, 'wilayah' => '-'
        ];

        $wilayah = $st_stats['wilayah'] ?: '-';
        
        $features[] = [
            'type' => 'Feature',
            'properties' => [
                'type' => 'station',
                'id' => $pos_id,
                'name' => $nama,
                'location' => $lokasi,
                'river' => $wilayah,
                'manager' => isset($st['pengelola']) ? $st['pengelola'] : 'BBWS Bengawan Solo',
                'lat' => $lat,
                'lon' => $lon,
                'yearStart' => $st_stats['minyear'] !== null ? (int)$st_stats['minyear'] : null,
                'yearEnd' => $st_stats['maxyear'] !== null ? (int)$st_stats['maxyear'] : null,
                'completeness' => (float)$st_stats['completeness'],
                'trendData' => []
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$lon, $lat]
            ]
        ];
    }
    
    $output = json_encode($features);
    
    // Simpan ke cache jika bukan mode lite
    if (!$isLite) {
        file_put_contents($cacheFile, $output);
    }
    
    header('X-Cache: MISS');
    echo $output;
    $conn = null;
    
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Terjadi kesalahan sistem saat memuat data', 'details' => $e->getMessage()]);
}
?>
