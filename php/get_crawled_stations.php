<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    $stmt = $pdo->query("SELECT * FROM hidrologinet_stations ORDER BY id ASC");
    $stations = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $stations
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
