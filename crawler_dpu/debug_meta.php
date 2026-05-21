<?php
require_once __DIR__ . '/DpuScraper.php';
$scraper = new DpuScraper();
$scraper->login();
$ch = curl_init('https://dpupesdm.monitoring4system.com/analisa');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/dpu_crawler_cookie_' . md5(__DIR__) . '.txt');
$html = curl_exec($ch);
if (preg_match('/location_new\s*=\s*(\[.*?\]);/s', $html, $m)) {
    $decoded = json_decode($m[1], true);
    echo "First item in location_new:\n";
    print_r($decoded[0]);
} else {
    echo "NOT FOUND";
}
