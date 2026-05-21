<?php
require_once __DIR__ . '/GoogleBigQueryClient.php';
$keyFile = __DIR__ . '/../config/key/seismic-vista-340403-490b28128128.json';
$bq = new GoogleBigQueryClient($keyFile, 'hidrologi');

try {
    $bq->initTables();
    echo "Tables initialized.\n";
    
    $row = [
        'waktu' => date('Y-m-d H:i:s'),
        'nama_pos' => 'Test Pos',
        'id_logger' => '99999',
        'latitude' => -7.0,
        'longitude' => 110.0,
        'nama_das' => 'Test DAS'
    ];
    
    $res = $bq->insertRows('awr_data', [$row]);
    echo "Insert result: " . json_encode($res) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
