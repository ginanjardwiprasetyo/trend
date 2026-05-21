<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dpupesdm.monitoring4system.com/login/login_tamu');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
// Enable cookies
$cookieFile = __DIR__ . '/dpu_cookie.txt';
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

$response = curl_exec($ch);
curl_close($ch);

// Now access datapos
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'https://dpupesdm.monitoring4system.com/datapos');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookieFile);
$datapos = curl_exec($ch2);
curl_close($ch2);

file_put_contents('scratch_datapos.html', $datapos);

// Now access analisa
$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, 'https://dpupesdm.monitoring4system.com/analisa');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch3, CURLOPT_COOKIEFILE, $cookieFile);
$analisa = curl_exec($ch3);
curl_close($ch3);

file_put_contents('scratch_analisa.html', $analisa);

echo "Fetched. Files size: \n";
echo "datapos: " . strlen($datapos) . " bytes\n";
echo "analisa: " . strlen($analisa) . " bytes\n";

