<?php
require_once __DIR__ . '/config/database.php';
try {
    $conn = getConnection();
    $stmt = $conn->query("SELECT * FROM pos_hujan LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo implode(", ", array_keys($row));
    } else {
        echo "Table empty, but querying information_schema:\n";
        $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'pos_hujan'");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo implode(", ", $cols);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
