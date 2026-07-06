<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['pos_id'])) {
    die("Parameter pos_id tidak ditemukan.");
}
$pos_id = $_GET['pos_id'];

try {
    $conn = getConnection();
    
    // Check if station exists
    $stmt = $conn->prepare("SELECT nama_pos FROM pos_hujan WHERE pos_id = ?");
    $stmt->execute([$pos_id]);
    $station = $stmt->fetch(PDO::FETCH_ASSOC);
    $st_name = $station ? $station['nama_pos'] : $pos_id;

    $sql = "SELECT tanggal, rain FROM data_ch WHERE pos_id = ? ORDER BY tanggal ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$pos_id]);
    
    $filename = "data_pos_" . preg_replace('/[^A-Za-z0-9_-]/', '', $st_name) . "_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8-sig');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // Add BOM for UTF-8 Excel compatibility
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Header
    fputcsv($output, ['Tanggal', 'Data'], ';');
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format tanggal ke dd/mm/yy (or dd/mm/yyyy as it's better)
        // Requested format: "Tanggal (date dd/mm/yy); Data (number)"
        $tgl = date('d/m/y', strtotime($row['tanggal']));
        
        $data = $row['rain'];
        if ($data === null) {
            $data = '';
        } else {
            // Ensure dot is used as decimal separator
            $data = rtrim(rtrim(sprintf('%.6f', (float)$data), '0'), '.');
        }
        
        fputcsv($output, [$tgl, $data], ';');
    }
    
    fclose($output);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
