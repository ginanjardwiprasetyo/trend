<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$payload = json_encode([
    'pos_id' => 37,
    'metode' => 'Kumulatif Tahunan',
    'th1' => 1980,
    'th2' => 2024,
    'bulan' => 'all',
    'musim' => ''
]);

// Write mock cache
$cacheDir = __DIR__ . '/php/cache_beast';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
$hash = md5($payload);
file_put_contents($cacheDir . '/' . $hash . '.json', json_encode(['test_cache' => 'OK']));

// Call beast_proxy
$out = shell_exec('php -r "$_POST = []; $_SERVER[\'REQUEST_METHOD\'] = \'POST\'; $input = \'' . addslashes($payload) . '\'; file_put_contents(\'php://memory\', $input); include \'php/beast_proxy.php\';"');

echo "Cache file hash: " . $hash . "\n";
echo "Output: " . $out . "\n";
