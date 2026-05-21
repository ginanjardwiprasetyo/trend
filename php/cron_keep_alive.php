<?php
/**
 * Cron Job Keep Alive - Supabase
 * TrendHidro - Aplikasi Pemetaan Tren Hidrologi
 * 
 * Jalankan file ini secara periodik menggunakan Cron Job di hosting Anda (misal: 1x seminggu)
 * untuk mencegah Supabase Free-Tier di-pause otomatis karena tidak ada aktivitas selama 7 hari.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getConnection();
    
    // Melakukan query ringan ke database postgres untuk menandai aktivitas
    $stmt = $conn->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Koneksi ke Supabase aktif dan berhasil diping!',
        'timestamp' => date('Y-m-d H:i:s'),
        'db_response' => $result ? 'OK (1)' : 'FAIL'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memicu aktivitas database: ' . $e->getMessage()
    ]);
}
