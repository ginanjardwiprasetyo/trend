<?php
$cookieFile = __DIR__ . '/dpu_cookie.txt';
$ch = curl_init();
$url = 'https://dpupesdm.monitoring4system.com/datapos/fetch_week?id_logger=10001&awal=2023-01-01&akhir=2023-01-07';
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$res = curl_exec($ch);
curl_close($ch);
file_put_contents('scratch_fetch.json', $res);
echo "Fetched fetch_week.";
