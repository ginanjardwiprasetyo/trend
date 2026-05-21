<?php
require_once __DIR__ . '/DpuScraper.php';
$scraper = new DpuScraper();
$scraper->login();
$ch = curl_init('https://dpupesdm.monitoring4system.com/analisa');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/dpu_cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/dpu_cookie.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$html = curl_exec($ch);
file_put_contents(__DIR__ . '/debug_analisa.html', $html);
echo "HTML length: " . strlen($html) . "\n";
if (preg_match('/location_new\s*=\s*(\[[\s\S]*?\])\s*;/i', $html, $m)) {
    echo "MATCH FOUND\n";
} else {
    echo "MATCH NOT FOUND\n";
}
