<?php
require_once __DIR__ . '/DpuScraper.php';
$scraper = new DpuScraper();
$scraper->login();
$stations = $scraper->getStationList();
$scraper->loadStationMetadata();

echo "Station Count: " . count($stations) . "\n";
$enrichedCount = 0;
foreach ($stations as $s) {
    $meta = $scraper->getStationMeta($s['id_logger'], $s['type']);
    if ($meta['latitude'] !== null) {
        $enrichedCount++;
    } else {
        echo "FAILED for ID: {$s['id_logger']}, Type: {$s['type']}, Name: {$s['nama_pos']}\n";
    }
}
echo "Enriched successfully: $enrichedCount / " . count($stations) . "\n";
