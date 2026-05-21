<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/DpuScraper.php';
$scraper = new DpuScraper();
echo "Cookie path: " . (new ReflectionClass($scraper))->getProperty('cookieFile')->setAccessible(true) || true ? "..." : "..."; // hack to see it
// Let's just try to login and fetch
$ok = $scraper->login();
echo "Login status: " . ($ok ? "OK" : "FAIL") . "\n";
$meta = $scraper->loadStationMetadata();
echo "Meta count: " . count($meta) . "\n";
if (count($meta) === 0) {
    // Show a bit of HTML to see where we are
    $ch = curl_init('https://dpupesdm.monitoring4system.com/analisa');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/dpu_cookie.txt');
    $html = curl_exec($ch);
    echo "HTML Snippet: " . substr(strip_tags($html), 0, 500) . "\n";
}
