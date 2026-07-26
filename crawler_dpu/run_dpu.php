<?php
/**
 * run_dpu.php – API Endpoint untuk Crawler DPUPESDM
 * 
 * Diakses via AJAX dari crawler_dpu.php
 * 
 * Parameter GET:
 *   action = 'init_tables' | 'get_stations' | 'crawl_station'
 * 
 * Parameter tambahan untuk crawl_station:
 *   id_logger = string
 *   nama_pos  = string
 *   type      = 'awr' | 'arr' | 'awlr'
 *   awal      = 'YYYY-MM-DD'
 *   akhir     = 'YYYY-MM-DD'
 */

set_time_limit(600); // 10 menit max per request
ini_set('memory_limit', '512M');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/DpuScraper.php';
require_once __DIR__ . '/GoogleBigQueryClient.php';

// ─── Konfigurasi ─────────────────────────────────────────────────

$keyFile   = __DIR__ . '/../config/key/seismic-vista-340403-490b28128128.json';
$datasetId = 'hidrologi';

// ─── Helper ──────────────────────────────────────────────────────

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function flushLog(string $msg): void
{
    // Untuk streaming (jika digunakan di masa depan)
    echo json_encode(['log' => $msg]) . "\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// ─── Router ──────────────────────────────────────────────────────

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── 1. Init Tables ────────────────────────────────────────
        case 'init_tables':
            $bq = new GoogleBigQueryClient($keyFile, $datasetId);
            $force = (($_GET['force'] ?? '') === 'true');
            $results = $bq->initTables($force);
            jsonResponse([
                'status'  => 'ok',
                'message' => $force ? 'Tabel berhasil di-reset dan diinisialisasi.' : 'Tabel berhasil diinisialisasi.',
                'tables'  => $results,
                'project' => $bq->getProjectId(),
                'dataset' => $bq->getDatasetId(),
            ]);
            break;

        // ── 2. Get Station List ───────────────────────────────────
        case 'get_stations':
            $scraper = new DpuScraper();
            $scraper->login();
            $stations = $scraper->getStationList();
            $scraper->loadStationMetadata();

            // Enrich with coordinates
            foreach ($stations as &$s) {
                $s['id_logger'] = (string)$s['id_logger'];
                $meta = $scraper->getStationMeta($s['id_logger'], $s['type']);
                $s['latitude']  = $meta['latitude'] !== null ? floatval($meta['latitude']) : null;
                $s['longitude'] = $meta['longitude'] !== null ? floatval($meta['longitude']) : null;
                $s['nama_das']  = (string)($meta['nama_das'] ?? '');
            }
            unset($s);

            // Simpan metadata ke BigQuery stasiun_dpu (Overwrite agar fresh)
            $bq = new GoogleBigQueryClient($keyFile, $datasetId);
            $resBq = $bq->insertRows('stasiun_dpu', $stations, 'WRITE_TRUNCATE');

            jsonResponse([
                'status'   => 'ok',
                'count'    => count($stations),
                'stations' => $stations,
                'bq_res'   => $resBq,
                'debug_sample' => count($stations) > 0 ? $stations[0] : null,
                'meta_count'   => count($scraper->loadStationMetadata())
            ]);
            break;

        // ── 3. Get Station List from BigQuery ─────────────────────
        case 'get_stations_bq':
            $bq = new GoogleBigQueryClient($keyFile, $datasetId);
            $sql = "SELECT * FROM `{$datasetId}.stasiun_dpu` ORDER BY nama_pos ASC";
            try {
                $stations = $bq->query($sql);
                jsonResponse([
                    'status'   => 'ok',
                    'count'    => count($stations),
                    'stations' => $stations,
                    'source'   => 'bigquery'
                ]);
            } catch (\Exception $e) {
                jsonResponse([
                    'status'  => 'error',
                    'message' => 'Gagal mengambil data dari BigQuery: ' . $e->getMessage()
                ], 500);
            }
            break;

        // ── 4. Get Weeks List ────────────────────────────────────
        case 'get_weeks':
            $awal  = $_GET['awal']  ?? '';
            $akhir = $_GET['akhir'] ?? '';
            if (!$awal || !$akhir) jsonResponse(['status' => 'error', 'message' => 'Range tanggal diperlukan.'], 400);
            
            $weeks = DpuScraper::generateWeeks($awal, $akhir);
            jsonResponse(['status' => 'ok', 'weeks' => $weeks]);
            break;

        // ── 4. Crawl Single Week ─────────────────────────────────
        case 'crawl_week':
            $idLogger = $_GET['id_logger'] ?? '';
            $type     = $_GET['type']      ?? '';
            $start    = $_GET['start']     ?? '';
            $end      = $_GET['end']       ?? '';

            if (!$idLogger || !$type || !$start || !$end) {
                jsonResponse(['status' => 'error', 'message' => 'Parameter tidak lengkap.'], 400);
            }

            $tableMap = ['awr' => 'awr_data', 'arr' => 'arr_data', 'awlr' => 'awlr_data'];
            $tableId  = $tableMap[$type] ?? null;

            $scraper = new DpuScraper();
            $scraper->login();
            
            $bq = new GoogleBigQueryClient($keyFile, $datasetId);
            
            $rawRows = $scraper->fetchWeekData($idLogger, $start, $end);
            $count   = count($rawRows);
            
            if ($count === 0) {
                jsonResponse(['status' => 'ok', 'count' => 0, 'inserted' => 0, 'message' => 'Tidak ada data.']);
            }

            $transformed = $scraper->transformRows($rawRows, $type, $idLogger);
            $res = $bq->insertRows($tableId, $transformed);

            jsonResponse([
                'status'   => 'ok',
                'count'    => $count,
                'inserted' => $res['inserted'],
                'errors'   => $res['errors'],
                'job_id'   => $res['job_id'] ?? null
            ]);
            break;

        // ── 5. Detect Data Range ──────────────────────────────────
        case 'detect_data_range':
            $idLogger = $_GET['id_logger'] ?? '';
            if (!$idLogger) {
                jsonResponse(['status' => 'error', 'message' => 'ID Logger diperlukan.'], 400);
            }

            $scraper = new DpuScraper();
            $scraper->login();
            
            $startYear = null;
            $endYear = null;
            $currentYear = (int)date('Y');
            
            // 1. Cari Tahun Akhir (cek dari sekarang mundur)
            for ($year = $currentYear; $year >= 2010; $year--) {
                // Jangan cek setahun penuh (menghindari 504 timeout)
                // Cek bulan Desember, lalu Juni, lalu Januari
                $checks = ["$year-12-01", "$year-06-01", "$year-01-01"];
                $hasData = false;
                foreach ($checks as $date) {
                    $d = new \DateTime($date);
                    $awal = $d->format('Y-m-d');
                    $d->modify('+25 days'); // Cek ~1 bulan
                    $akhir = $d->format('Y-m-d');
                    
                    $rows = $scraper->fetchWeekData($idLogger, $awal, $akhir);
                    if (!empty($rows)) {
                        $hasData = true;
                        break;
                    }
                    usleep(100000); // 100ms gap
                }

                if ($hasData) {
                    $endYear = $year;
                    break;
                }
            }

            // 2. Cari Tahun Awal (cek dari 2010 maju ke endYear)
            if ($endYear !== null) {
                for ($year = 2010; $year <= $endYear; $year++) {
                    // Cek Januari, lalu Juni, lalu Desember
                    $checks = ["$year-01-01", "$year-06-01", "$year-12-01"];
                    $hasData = false;
                    foreach ($checks as $date) {
                        $d = new \DateTime($date);
                        $awal = $d->format('Y-m-d');
                        $d->modify('+25 days');
                        $akhir = $d->format('Y-m-d');
                        
                        $rows = $scraper->fetchWeekData($idLogger, $awal, $akhir);
                        if (!empty($rows)) {
                            $hasData = true;
                            break;
                        }
                        usleep(100000);
                    }

                    if ($hasData) {
                        $startYear = $year;
                        break;
                    }
                }
            }

            jsonResponse([
                'status'     => 'ok',
                'id_logger'  => $idLogger,
                'start_year' => $startYear,
                'end_year'   => $endYear,
                'message'    => $startYear ? "Rentang data: $startYear s/d $endYear." : "Tidak ditemukan data."
            ]);
            break;

        // ── 5. Test Connection ────────────────────────────────────
        case 'test_connection':
            // Test BigQuery
            $bq = new GoogleBigQueryClient($keyFile, $datasetId);
            $projectId = $bq->getProjectId();

            // Test Scraper login
            $scraper = new DpuScraper();
            $loginOk = $scraper->login();

            jsonResponse([
                'status'     => 'ok',
                'bigquery'   => ['project' => $projectId, 'dataset' => $datasetId],
                'scraper'    => ['login' => $loginOk],
            ]);
            break;

        default:
            jsonResponse([
                'status'  => 'error',
                'message' => 'Action tidak dikenali. Gunakan: test_connection, init_tables, get_stations, crawl_station',
            ], 400);
    }

} catch (\Throwable $e) {
    jsonResponse([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ], 500);
}
