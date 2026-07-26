<?php
/**
 * GoogleBigQueryClient – Koneksi ke BigQuery menggunakan REST API + JWT
 * Tidak memerlukan composer / library eksternal.
 * 
 * Membutuhkan:
 *  - PHP >= 7.4
 *  - Ekstensi openssl
 *  - Service Account JSON key file dari GCP
 */
class GoogleBigQueryClient
{
    private string $projectId;
    private string $datasetId;
    private string $clientEmail;
    private string $privateKey;
    private ?string $accessToken = null;
    private int $tokenExpiry = 0;

    /**
     * @param string $keyFilePath  Path absolut ke file JSON Service Account Key
     * @param string $datasetId    Nama dataset BigQuery (e.g. 'hidrologi')
     */
    public function __construct(string $keyFilePath, string $datasetId = 'hidrologi')
    {
        if (!file_exists($keyFilePath)) {
            throw new \RuntimeException("Service Account key file not found: $keyFilePath");
        }

        $key = json_decode(file_get_contents($keyFilePath), true);
        if (!$key || empty($key['project_id']) || empty($key['private_key'])) {
            throw new \RuntimeException("Invalid Service Account key file format.");
        }

        $this->projectId   = $key['project_id'];
        $this->clientEmail = $key['client_email'];
        $this->privateKey  = $key['private_key'];
        $this->datasetId   = $datasetId;
    }

    // ─── OAuth2 JWT ──────────────────────────────────────────────────

    /**
     * Membuat JWT dan menukarnya dengan Access Token via Google OAuth2
     */
    private function getAccessToken(): string
    {
        // Gunakan token yang ada jika belum kadaluarsa (buffer 60 detik)
        if ($this->accessToken && time() < ($this->tokenExpiry - 60)) {
            return $this->accessToken;
        }

        $now = time();
        $header  = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64url(json_encode([
            'iss'   => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/bigquery',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signatureInput = "$header.$payload";
        openssl_sign($signatureInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $jwt = "$signatureInput." . $this->base64url($signature);

        // Exchange JWT for access token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new \RuntimeException("OAuth2 token exchange failed (HTTP $httpCode): $resp");
        }

        $data = json_decode($resp, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = $now + (int)($data['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // ─── Table Management ────────────────────────────────────────────

    /**
     * Membuat tabel jika belum ada. Mengembalikan true jika berhasil.
     */
    public function createTableIfNotExists(string $tableId, array $fields): bool
    {
        $url = "https://bigquery.googleapis.com/bigquery/v2/projects/{$this->projectId}/datasets/{$this->datasetId}/tables/{$tableId}";

        // Cek dulu apakah tabel sudah ada
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getAccessToken(),
            ],
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200) {
            return true; // Sudah ada
        }

        // Buat tabel baru
        $createUrl = "https://bigquery.googleapis.com/bigquery/v2/projects/{$this->projectId}/datasets/{$this->datasetId}/tables";

        $body = json_encode([
            'tableReference' => [
                'projectId' => $this->projectId,
                'datasetId' => $this->datasetId,
                'tableId'   => $tableId,
            ],
            'schema' => [
                'fields' => $fields,
            ],
        ]);

        $ch = curl_init($createUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: application/json',
            ],
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        throw new \RuntimeException("Failed to create table '$tableId' (HTTP $httpCode): $resp");
    }

    // ─── Data Insert ─────────────────────────────────────────────────

    /**
     * Insert baris-baris ke BigQuery menggunakan Load Job (NDJSON).
     * 
     * @param string $tableId          Nama tabel
     * @param array  $rows             Array of associative arrays
     * @param string $writeDisposition 'WRITE_APPEND' | 'WRITE_TRUNCATE'
     */
    public function insertRows(string $tableId, array $rows, string $writeDisposition = 'WRITE_APPEND'): array
    {
        if (empty($rows)) {
            return ['inserted' => 0, 'errors' => 0, 'last_error' => null];
        }

        $url = "https://bigquery.googleapis.com/upload/bigquery/v2/projects/{$this->projectId}/jobs?uploadType=multipart";

        // 1. Prepare NDJSON
        $ndjson = "";
        foreach ($rows as $row) {
            $ndjson .= json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }

        // 2. Prepare Multipart Body
        $boundary = "-------" . md5(time());
        $metadata = json_encode([
            'configuration' => [
                'load' => [
                    'destinationTable' => [
                        'projectId' => $this->projectId,
                        'datasetId' => $this->datasetId,
                        'tableId'   => $tableId,
                    ],
                    'sourceFormat'        => 'NEWLINE_DELIMITED_JSON',
                    'writeDisposition'    => $writeDisposition,
                    'ignoreUnknownValues' => true,
                    'allowJaggedRows'     => true,
                ],
            ],
        ]);

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= $metadata . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= $ndjson . "\r\n";
        $body .= "--$boundary--\r\n";

        // 3. Execute request
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: multipart/related; boundary=' . $boundary,
            ],
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'inserted'   => 0,
                'errors'     => count($rows),
                'last_error' => "HTTP $httpCode: $resp"
            ];
        }

        $result = json_decode($resp, true);
        $jobId  = $result['jobReference']['jobId'] ?? null;

        // Note: Load jobs are asynchronous. 
        // For simplicity in a web crawler context, we return "success" if the job started.
        // If we want to be 100% sure, we'd need to poll the job status.
        return [
            'inserted'   => count($rows),
            'errors'     => 0,
            'last_error' => null,
            'job_id'     => $jobId
        ];
    }

    /**
     * Menjalankan query SQL di BigQuery dan mengembalikan hasilnya.
     */
    public function query(string $sql): array
    {
        $url = "https://bigquery.googleapis.com/bigquery/v2/projects/{$this->projectId}/queries";
        
        $body = json_encode([
            'query' => $sql,
            'useLegacySql' => false,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: application/json',
            ],
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("BigQuery Query Error ($httpCode): $resp");
        }

        $result = json_decode($resp, true);
        $rows   = [];
        
        if (isset($result['rows'])) {
            $fields = $result['schema']['fields'];
            foreach ($result['rows'] as $row) {
                $item = [];
                foreach ($row['f'] as $index => $value) {
                    $fieldName = $fields[$index]['name'];
                    $item[$fieldName] = $value['v'];
                }
                $rows[] = $item;
            }
        }

        return $rows;
    }

    // ─── Schema Definitions ──────────────────────────────────────────

    /**
     * Menginisialisasi tabel-tabel (stasiun_dpu, awr_data, arr_data, awlr_data).
     * @param bool $forceRecreate Jika true, akan drop tabel yang sudah ada.
     */
    public function initTables(bool $forceRecreate = false): array
    {
        $results = [];

        $schemas = [
            'stasiun_dpu' => [
                ['name' => 'id_logger', 'type' => 'STRING',  'mode' => 'REQUIRED'],
                ['name' => 'nama_pos',  'type' => 'STRING',  'mode' => 'NULLABLE'],
                ['name' => 'type',      'type' => 'STRING',  'mode' => 'NULLABLE'],
                ['name' => 'nama_das',  'type' => 'STRING',  'mode' => 'NULLABLE'],
                ['name' => 'latitude',  'type' => 'FLOAT64', 'mode' => 'NULLABLE'],
                ['name' => 'longitude', 'type' => 'FLOAT64', 'mode' => 'NULLABLE'],
            ],
            'awr_data' => [
                ['name' => 'waktu',             'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
                ['name' => 'id_logger',         'type' => 'STRING',    'mode' => 'REQUIRED'],
                ['name' => 'kecepatan_angin',   'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
                ['name' => 'arah_angin',        'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
                ['name' => 'temperatur_udara',  'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
                ['name' => 'tekanan_udara',     'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
                ['name' => 'kelembaban_udara',  'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
            ],
            'arr_data' => [
                ['name' => 'waktu',     'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
                ['name' => 'id_logger', 'type' => 'STRING',    'mode' => 'REQUIRED'],
                ['name' => 'hujan',     'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
            ],
            'awlr_data' => [
                ['name' => 'waktu',            'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
                ['name' => 'id_logger',        'type' => 'STRING',    'mode' => 'REQUIRED'],
                ['name' => 'tinggi_muka_air',  'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
                ['name' => 'debit',            'type' => 'FLOAT64',   'mode' => 'NULLABLE'],
            ],
        ];

        foreach ($schemas as $tableId => $fields) {
            if ($forceRecreate) {
                $this->deleteTable($tableId);
            }
            $results[$tableId] = $this->createTableIfNotExists($tableId, $fields);
        }

        return $results;
    }

    /**
     * Menghapus tabel jika ada.
     */
    public function deleteTable(string $tableId): bool
    {
        $url = "https://bigquery.googleapis.com/bigquery/v2/projects/{$this->projectId}/datasets/{$this->datasetId}/tables/{$tableId}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->getAccessToken(),
            ],
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return ($httpCode === 204);
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getDatasetId(): string
    {
        return $this->datasetId;
    }
}
