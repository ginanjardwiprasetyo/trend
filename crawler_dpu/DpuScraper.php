<?php
/**
 * DpuScraper — Scraper untuk situs DPUPESDM monitoring4system.com
 * 
 * Menangani:
 *  - Login sebagai tamu
 *  - Mengambil daftar pos dari halaman /datapos
 *  - Mengambil metadata pos (koordinat) dari halaman /analisa
 *  - Mengambil data per minggu via API /datapos/fetch_week
 */
class DpuScraper
{
    private const BASE_URL    = 'https://dpupesdm.monitoring4system.com';
    private const LOGIN_URL   = self::BASE_URL . '/login/login_tamu';
    private const DATAPOS_URL = self::BASE_URL . '/datapos';
    private const ANALISA_URL = self::BASE_URL . '/analisa';
    private const FETCH_URL   = self::BASE_URL . '/datapos/fetch_week';

    private string $cookieFile;
    private bool $loggedIn = false;

    /** @var array Cached metadata dari /analisa — keyed by id_logger */
    private array $stationMeta = [];

    /** @var array Cached station list dari /datapos — [['id_logger'=>..., 'nama_pos'=>..., 'type'=>...], ...] */
    private array $stationList = [];

    public function __construct(?string $cookieFile = null)
    {
        // Gunakan path absolut yang bisa ditulis oleh web server
        $this->cookieFile = $cookieFile ?: __DIR__ . '/dpu_cookie.txt';
    }

    // ─── HTTP Helper ─────────────────────────────────────────────────

    private function request(string $url, bool $followRedirects = true, int $maxRetries = 3): string
    {
        $retryCount = 0;
        $lastError  = '';

        while ($retryCount <= $maxRetries) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIEJAR      => $this->cookieFile,
                CURLOPT_COOKIEFILE     => $this->cookieFile,
                CURLOPT_FOLLOWLOCATION => $followRedirects,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 DpuCrawler/1.0',
                CURLOPT_ENCODING       => '', 
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            // Update permissions on cookie file if it was created/modified
            if (file_exists($this->cookieFile)) {
                @chmod($this->cookieFile, 0666);
            }

            // Jika sukses (2xx)
            if ($httpCode >= 200 && $httpCode < 300) {
                return $response;
            }

            // Jika error 5xx (Server Error/Timeout), coba lagi
            if ($httpCode >= 500 || $response === false) {
                $retryCount++;
                $lastError = $response === false ? "cURL Error: $curlErr" : "HTTP $httpCode";
                
                if ($retryCount <= $maxRetries) {
                    // Tunggu makin lama setiap retry (1s, 2s, 3s)
                    sleep($retryCount);
                    continue;
                }
            }

            // Jika error 4xx atau sudah mentok retry, lempar exception
            throw new \RuntimeException("Gagal mengambil data setelah $retryCount percobaan. Error: $lastError (URL: $url)");
        }

        return '';
    }

    // ─── Login ───────────────────────────────────────────────────────

    /**
     * Login sebagai tamu. Menyimpan session cookie.
     */
    public function login(): bool
    {
        $html = $this->request(self::LOGIN_URL);
        // Cek apakah kita berhasil masuk (redirect ke beranda atau ada konten beranda)
        $this->loggedIn = (stripos($html, 'Beranda') !== false || stripos($html, 'navbar') !== false);

        if (!$this->loggedIn) {
            // Coba akses halaman beranda langsung
            $html2 = $this->request(self::BASE_URL . '/beranda');
            $this->loggedIn = (stripos($html2, 'Beranda') !== false);
        }

        return $this->loggedIn;
    }

    // ─── Station List ────────────────────────────────────────────────

    /**
     * Mengambil daftar pos dari halaman /datapos
     * Mengembalikan array: [['id_logger' => '10001', 'nama_pos' => 'Pos AWR Kaliurang', 'type' => 'awr'], ...]
     */
    public function getStationList(): array
    {
        if (!empty($this->stationList)) {
            return $this->stationList;
        }

        $html = $this->request(self::DATAPOS_URL);

        // Parse <option value="10001|Pos AWR Kaliurang">Pos AWR Kaliurang</option>
        preg_match_all('/<option\s+value="(\d+)\|([^"]+)"/', $html, $matches, PREG_SET_ORDER);

        $stations = [];
        foreach ($matches as $m) {
            $idLogger = $m[1];
            $namaPos  = trim($m[2]);

            // Tentukan tipe berdasarkan nama
            $type = 'unknown';
            if (preg_match('/\bAWR\b/i', $namaPos))       $type = 'awr';
            elseif (preg_match('/\bARR\b/i', $namaPos))    $type = 'arr';
            elseif (preg_match('/\bAWLR\b/i', $namaPos))   $type = 'awlr';

            $stations[] = [
                'id_logger' => $idLogger,
                'nama_pos'  => $namaPos,
                'type'      => $type,
            ];
        }

        $this->stationList = $stations;
        return $stations;
    }

    // ─── Station Metadata (Koordinat) ────────────────────────────────

    /**
     * Mengambil metadata pos (latitude, longitude, nama_das) dari halaman /analisa.
     * Data ada di JavaScript variable `location_new` di halaman tersebut.
     */
    public function loadStationMetadata(): array
    {
        if (!empty($this->stationMeta)) {
            return $this->stationMeta;
        }

        // Coba beberapa URL karena terkadang data location_new hanya muncul di sub-halaman tertentu
        $urls = [
            self::ANALISA_URL,
            self::ANALISA_URL . '/index/awr',
            self::ANALISA_URL . '/index/arr',
            self::ANALISA_URL . '/index/awlr'
        ];

        foreach ($urls as $url) {
            try {
                $html = $this->request($url);
                
                // Regex yang lebih agresif untuk mencari array location_new
                // Mencari pola: location_new = [ ... ];
                if (preg_match('/location_new\s*=\s*(\[[\s\S]*?\])\s*;/i', $html, $m)) {
                    $jsonStr = $m[1];
                    // Bersihkan jika ada komentar JS di dalam array yang merusak json_decode
                    $jsonStr = preg_replace('!/\*.*?\*/!s', '', $jsonStr);
                    $jsonStr = preg_replace('!//.*?\n!', '', $jsonStr);
                    
                    $decoded = json_decode($jsonStr, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $item) {
                            $id = isset($item['id_logger']) ? (string)$item['id_logger'] : null;
                            if ($id) {
                                $cat = $item['category'] ?? '';
                                $key = $id . '_' . $cat;
                                if (!isset($this->stationMeta[$key])) {
                                    $this->stationMeta[$key] = [
                                        'latitude'  => isset($item['latitude']) ? floatval($item['latitude']) : null,
                                        'longitude' => isset($item['longitude']) ? floatval($item['longitude']) : null,
                                        'nama_das'  => $item['nama_das'] ?? '',
                                        'category'  => $cat,
                                    ];
                                    // Fallback key tanpa kategori
                                    if (!isset($this->stationMeta[$id])) {
                                        $this->stationMeta[$id] = $this->stationMeta[$key];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("DpuScraper: Gagal akses $url - " . $e->getMessage());
            }
            
            // Jika sudah ketemu banyak, tidak perlu cek URL berikutnya
            if (count($this->stationMeta) > 10) break;
        }

        if (empty($this->stationMeta)) {
            error_log("DpuScraper: Gagal menemukan metadata di semua URL analisa.");
        }

        return $this->stationMeta;
    }

    /**
     * Mendapatkan metadata untuk sebuah pos.
     */
    public function getStationMeta(string $idLogger, string $type = ''): array
    {
        $this->loadStationMetadata();

        // Coba cari dengan key spesifik (id + category) dulu
        $key = $idLogger . '_' . $type;
        if (isset($this->stationMeta[$key])) {
            return $this->stationMeta[$key];
        }

        // Fallback ke id saja
        return $this->stationMeta[$idLogger] ?? [
            'latitude'  => null,
            'longitude' => null,
            'nama_das'  => '',
            'category'  => '',
        ];
    }

    // ─── Data Fetch ──────────────────────────────────────────────────

    /**
     * Mengambil data untuk satu pos dalam rentang tanggal tertentu.
     * Mengembalikan array of rows (assoc arrays).
     * 
     * @param string $idLogger ID logger (e.g. '10001')
     * @param string $awal     Tanggal awal YYYY-MM-DD
     * @param string $akhir    Tanggal akhir YYYY-MM-DD
     * @return array
     */
    public function fetchWeekData(string $idLogger, string $awal, string $akhir): array
    {
        $url = self::FETCH_URL . '?' . http_build_query([
            'id_logger' => $idLogger,
            'awal'      => $awal,
            'akhir'     => $akhir,
        ]);

        $response = $this->request($url);
        $data     = json_decode($response, true);

        if (!$data || ($data['status'] ?? '') === 'error') {
            return [];
        }

        return $data['rows'] ?? [];
    }

    /**
     * Generate rentang minggu antara dua tanggal.
     * Mengembalikan array of ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    public static function generateWeeks(string $awal, string $akhir): array
    {
        $weeks  = [];
        $end    = new \DateTime($akhir);
        $cursor = new \DateTime($awal);

        while ($cursor <= $end) {
            $wStart = clone $cursor;
            $wEnd   = clone $cursor;
            $wEnd->modify('+6 days');
            if ($wEnd > $end) {
                $wEnd = clone $end;
            }
            $weeks[] = [
                'start' => $wStart->format('Y-m-d'),
                'end'   => $wEnd->format('Y-m-d'),
            ];
            $cursor->modify('+7 days');
        }

        return $weeks;
    }

    // ─── Data Transformer ────────────────────────────────────────────

    /**
     * Mengubah baris data mentah dari API menjadi format yang sesuai untuk tabel BigQuery.
     * 
     * @param array  $rows     Baris data dari fetchWeekData()
     * @param string $type     Tipe pos: 'awr', 'arr', 'awlr'
     * @param string $namaPos  Nama pos (e.g. 'Pos AWR Kaliurang')
     * @param string $idLogger ID logger
     * @return array
     */
    public function transformRows(array $rows, string $type, string $idLogger): array
    {
        $result = [];

        foreach ($rows as $row) {
            $waktu = $row['waktu'] ?? $row['Waktu'] ?? null;
            if (!$waktu) continue;

            $base = [
                'waktu'     => $waktu,
                'id_logger' => $idLogger,
            ];

            switch ($type) {
                case 'awr':
                    $base['kecepatan_angin']  = $this->toFloat($row, 'Kecepatan Angin');
                    $base['arah_angin']       = $this->toFloat($row, 'Arah Angin');
                    $base['temperatur_udara'] = $this->toFloat($row, 'Temperatur Udara');
                    $base['tekanan_udara']    = $this->toFloat($row, 'Tekanan Udara');
                    $base['kelembaban_udara'] = $this->toFloat($row, 'Kelembaban Udara');
                    break;

                case 'arr':
                    $base['hujan'] = $this->toFloat($row, 'Curah Hujan')
                                  ?? $this->toFloat($row, 'Curah Hujan 1')
                                  ?? $this->toFloat($row, 'curah_hujan');
                    break;

                case 'awlr':
                    $base['tinggi_muka_air'] = $this->toFloat($row, 'Tinggi Muka Air')
                                            ?? $this->toFloat($row, 'Level');
                    $base['debit']           = $this->toFloat($row, 'Debit');
                    break;
            }

            $result[] = $base;
        }

        return $result;
    }

    /**
     * Helper: ambil nilai float dari row, case-insensitive.
     */
    private function toFloat(array $row, string $key): ?float
    {
        // Coba exact match dulu
        if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
            return floatval($row[$key]);
        }

        // Case-insensitive fallback
        foreach ($row as $k => $v) {
            if (strcasecmp($k, $key) === 0 && $v !== '' && $v !== null) {
                return floatval($v);
            }
        }

        return null;
    }
}
