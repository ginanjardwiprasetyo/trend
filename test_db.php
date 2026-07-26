<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = json_decode('{"pos_id":37,"dataType":"tahunan","aggregation":"sum","yearFrom":1980,"yearTo":2024,"month":"all"}', true);
// But wait, get_timeseries.php uses file_get_contents('php://input') for JSON payload!
// So we can't just set $_POST.
// Let's just include the db fetch function directly!
require 'config/database.php';
require 'php/db_fetch_timeseries.php';

$res = fetchTimeseries($pdo, 37, 'tahunan', 'sum', 1980, 2024, 'all');
echo json_encode($res);
