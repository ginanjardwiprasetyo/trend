<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPUPESDM Crawler – TrenHidro</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .crawler-container {
            max-width: 1000px;
            margin: 100px auto 40px;
            padding: 0 24px;
        }
        .crawler-card {
            background: #fff;
            border-radius: var(--radius-panel);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--color-border);
            padding: 24px;
            margin-bottom: 24px;
        }
        .crawler-card h2 {
            font-size: 1.25rem;
            margin-bottom: 16px;
            color: var(--color-text);
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }
        .btn-primary { background: #2563EB; color: #fff; }
        .btn-primary:hover { background: #1D4ED8; }
        .btn-warning { background: #F59E0B; color: #fff; }
        .btn-warning:hover { background: #D97706; }
        .btn-success { background: #10B981; color: #fff; }
        .btn-success:hover { background: #059669; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .log-box {
            background: #1F2937;
            color: #10B981;
            font-family: monospace;
            padding: 16px;
            border-radius: 8px;
            height: 400px;
            overflow-y: auto;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        .log-box p { margin: 0 0 4px 0; }
        .log-error { color: #EF4444; }
        .log-warn { color: #FBBF24; }
        .log-info { color: #60A5FA; }
        
        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-text-secondary);
        }
        .form-control {
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-btn);
            font-size: 0.9rem;
            font-family: inherit;
        }

        /* Spinner */
        .spinner {
            display: none;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(37, 99, 235, 0.3);
            border-radius: 50%;
            border-top-color: #2563EB;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .info-note {
            background: #EFF6FF;
            border-left: 4px solid #2563EB;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: #1E40AF;
        }
    </style>
</head>
<body style="background: #F9FAFB; overflow-y: auto; height: auto;">

    <!-- NAVBAR -->
    <nav class="navbar" id="navbar">
        <a href="./" class="navbar-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 7v10" />
                <path d="M9 10l3-3 3 3" />
                <path d="M9 14l3 3 3-3" />
            </svg>
            <span>TrenHidro</span>
        </a>
        <ul class="navbar-nav">
            <li><a href="../">Beranda</a></li>
            <li><a href="./" class="active">DPUPESDM Crawler</a></li>
        </ul>
    </nav>

    <div class="crawler-container">
        <div class="crawler-card">
            <div style="display: flex; align-items: center; margin-bottom: 16px;">
                <h2 style="margin-bottom: 0;">Dashboard Crawler DPUPESDM (BigQuery)</h2>
                <div id="mainSpinner" class="spinner"></div>
            </div>
            
            <div class="info-note">
                <strong>Catatan:</strong> Sistem ini secara otomatis melewati (*skip*) minggu yang tidak memiliki data. 
                Data yang kosong akan disimpan sebagai <code>NULL</code> (bukan nol), sehingga tidak akan merusak perhitungan statistik Anda.
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Pilih Pos</label>
                    <select id="selectPos" class="form-control" disabled>
                        <option value="">Memuat pos...</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>Tahun Awal</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="number" id="tahunAwal" class="form-control" value="2017" min="2010" max="2030">
                        <button class="btn" onclick="detectYear()" title="Deteksi otomatis tahun awal data" style="padding: 8px; background: #F3F4F6;">🔍</button>
                    </div>
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>Tahun Akhir</label>
                    <input type="number" id="tahunAkhir" class="form-control" value="2024" min="2010" max="2030">
                </div>
                <div class="form-group" style="flex: 0.3; justify-content: flex-end; padding-bottom: 5px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" id="checkForce"> Reset Tabel
                    </label>
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-warning" id="btnInit" onclick="initTables()">1. Init Tabel BQ</button>
                <button class="btn btn-primary" id="btnLoadStations" onclick="loadStations()">2. Muat Pos (Scrape)</button>
                <button class="btn" id="btnLoadBQ" onclick="loadStationsBQ()" style="background:#8B5CF6; color:#fff;">2. Muat Pos (BQ)</button>
                <button class="btn" id="btnScanAll" onclick="scanRangesAll()" style="background:#6366F1; color:#fff;" disabled>Cek Rentang (Semua)</button>
                <button class="btn btn-success" id="btnCrawl" onclick="startCrawling()" disabled>3. Crawl Manual</button>
                <button class="btn" id="btnCrawlAll" onclick="startCrawlingAll()" style="background:#4B5563; color:#fff;" disabled>Crawl Semua</button>
                <button class="btn" id="btnStop" onclick="stopProcess()" style="background:#EF4444; color:#fff; display:none;">Berhenti / Pause</button>
            </div>
            
            <div class="log-box" id="logBox">
                <p class="log-info">>> Sistem siap.</p>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'run_dpu.php';
        let isRunning = false;
        let stopFlag = false;
        let stationList = [];
        let stationRanges = {}; 

        function appendLog(msg, type = 'log') {
            const box = document.getElementById('logBox');
            const p = document.createElement('p');
            p.className = 'log-' + type;
            const time = new Date().toLocaleTimeString();
            p.textContent = `[${time}] ${msg}`;
            box.appendChild(p);
            box.scrollTop = box.scrollHeight;
        }

        async function apiCall(action, params = {}) {
            const qs = new URLSearchParams({ action, ...params }).toString();
            try {
                const res = await fetch(`${API_URL}?${qs}`);
                if (!res.ok) {
                    const text = await res.text();
                    throw new Error(`HTTP ${res.status}: ${text}`);
                }
                return await res.json();
            } catch (err) {
                appendLog(err.message, 'error');
                throw err;
            }
        }

        async function initTables() {
            const force = document.getElementById('checkForce').checked;
            if (force && !confirm('PERINGATAN: Seluruh data di BigQuery akan DIHAPUS dan tabel akan dibuat ulang. Lanjutkan?')) {
                return;
            }

            document.getElementById('mainSpinner').style.display = 'block';
            document.getElementById('btnInit').disabled = true;
            appendLog(force ? 'Mereset dan menginisialisasi ulang tabel BigQuery...' : 'Menginisialisasi tabel BigQuery...', 'info');
            try {
                const res = await apiCall('init_tables', { force: force });
                if (res.status === 'ok') {
                    appendLog(res.message, 'success');
                    Object.entries(res.tables).forEach(([table, status]) => {
                        appendLog(`- ${table}: ${status ? 'OK' : 'Error'}`);
                    });
                    if (force) document.getElementById('checkForce').checked = false;
                }
            } catch (err) {
                appendLog('Gagal init tables.', 'error');
            }
            document.getElementById('btnInit').disabled = false;
            document.getElementById('mainSpinner').style.display = 'none';
        }

        async function loadStations() {
            document.getElementById('mainSpinner').style.display = 'block';
            document.getElementById('btnLoadStations').disabled = true;
            appendLog('Menghubungi server DPUPESDM (Scraping)...', 'info');
            try {
                const res = await apiCall('get_stations');
                handleStationResponse(res);
            } catch (err) {
                appendLog('Gagal memuat stasiun via scrape.', 'error');
            }
            document.getElementById('btnLoadStations').disabled = false;
            document.getElementById('mainSpinner').style.display = 'none';
        }

        async function loadStationsBQ() {
            document.getElementById('mainSpinner').style.display = 'block';
            document.getElementById('btnLoadBQ').disabled = true;
            appendLog('Mengambil daftar pos dari BigQuery...', 'info');
            try {
                const res = await apiCall('get_stations_bq');
                handleStationResponse(res);
            } catch (err) {
                appendLog('Gagal memuat stasiun dari BigQuery. Pastikan tabel stasiun_dpu sudah ada.', 'error');
            }
            document.getElementById('btnLoadBQ').disabled = false;
            document.getElementById('mainSpinner').style.display = 'none';
        }

        function handleStationResponse(res) {
            if (res.status === 'ok') {
                stationList = res.stations;
                appendLog(`Berhasil memuat ${res.count} pos dari ${res.source || 'server'}.`);
                if (res.debug_sample) {
                    const s = res.debug_sample;
                    appendLog(`Sampel: ${s.nama_pos} (Lat: ${s.latitude}, Lon: ${s.longitude}, DAS: ${s.nama_das})`, 'info');
                    appendLog(`Total Metadata ditemukan: ${res.meta_count}`, 'info');
                }
                
                const select = document.getElementById('selectPos');
                select.innerHTML = '<option value="">-- Pilih Pos --</option>';
                stationList.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id_logger;
                    opt.dataset.nama = s.nama_pos;
                    opt.dataset.type = s.type;
                    opt.textContent = `[${s.type.toUpperCase()}] ${s.nama_pos} (ID: ${s.id_logger})`;
                    select.appendChild(opt);
                });
                select.disabled = false;
                document.getElementById('btnCrawl').disabled = false;
                document.getElementById('btnCrawlAll').disabled = false;
                document.getElementById('btnScanAll').disabled = false;
                if (res.source === 'bigquery') {
                    appendLog(`Data stasiun dimuat dari database lokal BigQuery.`, 'success');
                } else {
                    appendLog(`Sinkronisasi metadata stasiun ke BigQuery selesai.`, 'success');
                }
            }
        }

        async function detectYear() {
            const select = document.getElementById('selectPos');
            if (!select.value) {
                alert('Pilih pos terlebih dahulu!');
                return;
            }
            appendLog(`Mendeteksi rentang data untuk ${select.options[select.selectedIndex].text}...`, 'info');
            document.getElementById('mainSpinner').style.display = 'block';
            try {
                const res = await apiCall('detect_data_range', { id_logger: select.value });
                if (res.status === 'ok') {
                    if (res.start_year) document.getElementById('tahunAwal').value = res.start_year;
                    if (res.end_year) document.getElementById('tahunAkhir').value = res.end_year;
                    appendLog(res.message, 'success');
                }
            } catch (err) {
                appendLog('Pengecekan gagal. Server target mungkin lambat.', 'error');
            }
            document.getElementById('mainSpinner').style.display = 'none';
        }

        function stopProcess() {
            if (isRunning) {
                stopFlag = true;
                appendLog('Mengirim perintah berhenti... tunggu hingga tugas saat ini selesai.', 'warn');
            }
        }

        async function scanRangesAll() {
            if (stationList.length === 0) return;
            appendLog('Memulai pemindaian rentang data untuk SEMUA POS...', 'info');
            document.getElementById('mainSpinner').style.display = 'block';
            document.getElementById('btnScanAll').disabled = true;
            document.getElementById('btnStop').style.display = 'inline-flex';
            
            isRunning = true;
            stopFlag = false;

            for (let i = 0; i < stationList.length; i++) {
                if (stopFlag) {
                    appendLog('Pemindaian dihentikan oleh pengguna.', 'warn');
                    break;
                }
                const s = stationList[i];
                try {
                    const res = await apiCall('detect_data_range', { id_logger: s.id_logger });
                    if (res.status === 'ok' && res.start_year) {
                        stationRanges[s.id_logger] = { start: res.start_year, end: res.end_year };
                        appendLog(`[${s.type.toUpperCase()}] ${s.nama_pos}: ${res.start_year} - ${res.end_year}`, 'success');
                    } else {
                        appendLog(`[${s.type.toUpperCase()}] ${s.nama_pos}: Tidak ada data.`, 'warn');
                    }
                } catch (err) {
                    appendLog(`Gagal memindai ${s.nama_pos}.`, 'error');
                }
                // Delay 500ms
                await new Promise(r => setTimeout(r, 500));
            }

            appendLog('Pemindaian selesai.', 'info');
            document.getElementById('mainSpinner').style.display = 'none';
            document.getElementById('btnScanAll').disabled = false;
            document.getElementById('btnStop').style.display = 'none';
            isRunning = false;
        }

        async function startCrawling() {
            const select = document.getElementById('selectPos');
            if (!select.value) {
                alert('Pilih pos terlebih dahulu!');
                return;
            }

            const idLogger = select.value;
            const selectedOpt = select.options[select.selectedIndex];
            const namaPos = selectedOpt.dataset.nama;
            const type = selectedOpt.dataset.type;

            await crawlSingleStation(idLogger, namaPos, type);
        }

        async function startCrawlingAll() {
            if (stationList.length === 0) {
                alert('Muat daftar pos terlebih dahulu!');
                return;
            }

            if (!confirm(`Yakin ingin melakukan crawling untuk ${stationList.length} pos? Ini akan memakan waktu.`)) {
                return;
            }

            document.getElementById('mainSpinner').style.display = 'block';
            document.getElementById('btnCrawlAll').disabled = true;
            document.getElementById('btnCrawl').disabled = true;
            document.getElementById('btnStop').style.display = 'inline-flex';
            
            isRunning = true;
            stopFlag = false;

            for (let i = 0; i < stationList.length; i++) {
                if (stopFlag) {
                    appendLog('Crawling dihentikan oleh pengguna.', 'warn');
                    break;
                }
                const s = stationList[i];
                appendLog(`=== PROSES POS ${i+1}/${stationList.length} ===`, 'info');
                
                // Gunakan rentang yang terdeteksi jika ada, jika tidak gunakan input manual
                let tAwal = document.getElementById('tahunAwal').value;
                let tAkhir = document.getElementById('tahunAkhir').value;
                
                if (stationRanges[s.id_logger]) {
                    tAwal = stationRanges[s.id_logger].start;
                    tAkhir = stationRanges[s.id_logger].end;
                    appendLog(`Menggunakan rentang terdeteksi: ${tAwal} - ${tAkhir}`, 'info');
                }

                try {
                    await crawlSingleStationWithRange(s.id_logger, s.nama_pos, s.type, tAwal, tAkhir);
                } catch (err) {
                    appendLog(`Skipping ${s.nama_pos} due to error.`, 'warn');
                }
            }

            appendLog(`=== CRAWLING SEMUA POS SELESAI ===`, 'info');
            document.getElementById('mainSpinner').style.display = 'none';
            document.getElementById('btnCrawlAll').disabled = false;
            document.getElementById('btnCrawl').disabled = false;
            document.getElementById('btnStop').style.display = 'none';
            isRunning = false;
        }

        async function crawlSingleStation(idLogger, namaPos, type, disableUI = true) {
            const tAwal = document.getElementById('tahunAwal').value;
            const tAkhir = document.getElementById('tahunAkhir').value;
            await crawlSingleStationWithRange(idLogger, namaPos, type, tAwal, tAkhir, disableUI);
        }

        async function crawlSingleStationWithRange(idLogger, namaPos, type, tAwal, tAkhir, disableUI = true) {
            // Validasi tahun
            if (tAwal > tAkhir) {
                appendLog(`Error: Tahun awal (${tAwal}) tidak boleh lebih besar dari tahun akhir (${tAkhir}).`, 'error');
                return;
            }

            if (disableUI) {
                document.getElementById('mainSpinner').style.display = 'block';
                document.getElementById('btnCrawl').disabled = true;
                document.getElementById('btnStop').style.display = 'inline-flex';
                isRunning = true;
                stopFlag = false;
            }

            appendLog(`Memulai crawling: ${namaPos} (${tAwal} s/d ${tAkhir})`, 'info');

            try {
                // 1. Ambil daftar minggu
                const resWeeks = await apiCall('get_weeks', { awal: `${tAwal}-01-01`, akhir: `${tAkhir}-12-31` });
                if (resWeeks.status !== 'ok') throw new Error(resWeeks.message);

                const weeks = resWeeks.weeks;
                let totalRows = 0;
                let totalInserted = 0;

                // 2. Loop minggu-minggu di JS
                for (let i = 0; i < weeks.length; i++) {
                    if (stopFlag) {
                        appendLog(`Proses ${namaPos} dihentikan oleh pengguna.`, 'warn');
                        break;
                    }

                    const w = weeks[i];
                    try {
                        const res = await apiCall('crawl_week', {
                            id_logger: idLogger,
                            type: type,
                            start: w.start,
                            end: w.end
                        });

                        if (res.status === 'ok') {
                            if (res.count > 0) {
                                appendLog(`  > Minggu ${i + 1}/${weeks.length} (${w.start}): ${res.inserted} baris`, 'log');
                                totalRows += res.count;
                                totalInserted += res.inserted;
                            }
                        }
                    } catch (err) {
                        appendLog(`  > Gagal di minggu ${w.start}: ${err.message}`, 'error');
                    }
                    
                    // Jeda 100ms antar minggu
                    await new Promise(r => setTimeout(r, 100));
                }

                appendLog(`SELESAI: ${namaPos}. Total: ${totalRows} baris, Inserted: ${totalInserted}.`, 'success');

            } catch (err) {
                appendLog(`Gagal crawling ${namaPos}: ${err.message}`, 'error');
            }

            if (disableUI) {
                document.getElementById('mainSpinner').style.display = 'none';
                document.getElementById('btnCrawl').disabled = false;
                document.getElementById('btnStop').style.display = 'none';
                isRunning = false;
            }
        }
    </script>
</body>
</html>
