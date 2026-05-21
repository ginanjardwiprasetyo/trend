<?php
require_once __DIR__ . '/DpuScraper.php';
$scraper = new DpuScraper();
$scraper->login();
echo "Logging in...\n";
$meta = $scraper->loadStationMetadata();
echo "Found metadata for " . count($meta) . " items.\n";
if (count($meta) > 0) {
    print_r(array_slice($meta, 0, 5));
} else {
    echo "NO METADATA FOUND.\n";
}
