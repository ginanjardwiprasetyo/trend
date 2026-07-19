<!DOCTYPE html>
<html lang="id">

<!-- =O= Anchored Summary =O=
  v1  Dasar: toolbar, accordion preview, olah data analysis.
  v2  Toolbar order: btn "Olah Data" & spinner dipindah sebelum year-picker agar sejajar label "Pilih Data".
  v3  Dropdown direction dinamis: saat preview accordion terbuka → menu/select custom ke atas; saat tertutup → ke bawah.
      Mekanisme: CSS class `preview-open` pd #configSection dikontrol via togglePreviewAccordion() dan JS binding.
      Hapus `grid-up` dari HTML year-grid, ganti dg CSS .config-section.preview-open .year-grid.
  =O= Anchored Summary =O= -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Olah Data Anda — TrendHidro</title>
    <meta name="description"
        content="Upload dan olah data runtut waktu hidrologi Anda sendiri — Mann-Kendall, Sen's Slope, dan Regresi Linear langsung di peramban.">
    <meta name="keywords"
        content="upload data, olah data hidrologi, mann-kendall, sen's slope, regresi linear, olah data, trendhidro">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <!-- SheetJS for Excel parsing -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            background: #F3F4F6;
            overflow-y: auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .olah-container {
            padding: 80px 24px 40px;
            margin: 0 auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 1200px;
            box-sizing: border-box;
        }

        /* Widen container when config section is active */
        .olah-container:has(.config-section.show) {
            max-width: 100%;
        }

        .olah-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        .olah-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .olah-header p {
            font-size: 0.9rem;
            color: var(--color-text-secondary);
            margin-top: 4px;
        }

        /* Upload Zone */
        .upload-zone {
            border: 2px dashed #CBD5E1;
            border-radius: 24px;
            padding: 64px 40px;
            min-height: 400px;
            max-width: 740px;
            width: 100%;
            margin: 0 auto 24px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
        }

        .upload-zone svg {
            width: 48px;
            height: 48px;
            color: var(--color-primary);
        }

        .upload-zone h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .upload-zone p {
            font-size: 0.88rem;
            color: var(--color-text-secondary);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-zone .file-info {
            margin-top: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-primary);
        }

        /* Preview & Config Section */
        .config-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .config-section.show {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Data Table Preview */
        .preview-wrap {
            background: #fff;
            border-radius: var(--radius-panel);
            border: 1px solid var(--color-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .preview-scroll {
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }

        .accordion-content {
            height: 350px;
            display: flex;
            flex-direction: column;
            border: 0;
        }

        .accordion-content.show {
            border: 1px solid var(--color-border);
            border-top: none;
            border-bottom-left-radius: var(--radius-panel);
            border-bottom-right-radius: var(--radius-panel);
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .preview-table th {
            background: #F3F4F6;
            padding: 8px 12px;
            font-size: 0.75rem;
            color: #4B5563;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #E5E7EB;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .preview-table td {
            padding: 5px 12px;
            border-bottom: 1px solid #E5E7EB;
            color: #1F2937;
            font-size: 0.82rem;
        }

        .preview-table tbody tr:hover {
            background: #F9FAFB;
        }

        .preview-info {
            padding: 12px 16px;
            font-size: 0.85rem;
            color: #6B7280;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Toolbar */
        .olah-toolbar {
            background: #fff;
            border-radius: var(--radius-panel);
            border: 1px solid var(--color-border);
            padding: 16px 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 24px;
        }

        .olah-toolbar label {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--color-text-secondary);
            min-width: max-content;
        }

        .period-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: capitalize;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .period-badge.period-harian {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .period-badge.period-bulanan {
            background: #D1FAE5;
            color: #065F46;
        }

        .period-badge.period-tahunan {
            background: #FEF3C7;
            color: #92400E;
        }

        /* Results Section */
        .results-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .results-section.show {
            display: block;
        }

        .result-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .result-card {
            background: #fff;
            border-radius: var(--radius-panel);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--color-border);
            padding: 24px;
            position: relative;
        }

        .result-card.full {
            grid-column: 1 / -1;
            margin-bottom: 12px;
        }

        .result-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .result-card h3 svg {
            width: 20px;
            height: 20px;
            color: var(--color-primary);
        }

        /* Card Loader */
        .card-loader-olah {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
            gap: 10px;
            flex-direction: column;
        }

        .card-loader-olah.active {
            display: flex;
        }

        /* Dropdowns ke bawah secara default */
        /* Saat preview terbuka, dropdowns & year-grid ke atas */
        .config-section.preview-open .olah-toolbar .custom-select-options {
            top: auto;
            bottom: calc(100% + 6px);
            transform: translateY(8px);
        }
        .config-section.preview-open .olah-toolbar .custom-select-wrapper.open .custom-select-options {
            transform: translateY(0);
        }
        .config-section.preview-open .olah-toolbar .year-grid {
            top: auto;
            bottom: calc(100% + 8px);
        }

        #olahSeasonalToggleWrap .toggle-slider {
            position: relative;
            width: 40px;
            height: 22px;
            background: #D1D5DB;
            border-radius: 100px;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        #olahSeasonalToggleWrap .toggle-slider::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            top: 2px;
            left: 2px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        #olahSeasonalToggleWrap:hover .toggle-slider {
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1), 0 0 0 3px rgba(124,58,237,0.12);
        }
        #olahSeasonalToggle:checked ~ .toggle-slider {
            background: #7C3AED;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.15);
        }
        #olahSeasonalToggle:checked ~ .toggle-slider::after {
            transform: translateX(18px);
        }
        #olahSeasonalToggleWrap:has(#olahSeasonalToggle:checked):hover .toggle-slider {
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.15), 0 0 0 3px rgba(124,58,237,0.2);
        }
        .trend-result {
            transition: opacity 0.2s ease;
        }
        .trend-result.fading {
            opacity: 0;
        }

        /* Daily availability grid */
        .github-grid {
            display: grid;
            grid-template-columns: repeat(31, 1fr);
            gap: 2px;
            overflow-x: auto;
            padding: 8px 0;
        }
        .gh-cell {
            width: 35px;
            height: 35px;
            border-radius: 4px;
            background: #F3F4F6;
            transition: all 0.2s;
        }
        .gh-cell:hover {
            filter: brightness(0.9);
            transform: scale(1.05);
            z-index: 10;
        }
        .gh-cell.available { background: #3B82F6; }
        .gh-cell.missing { background: #EF4444; }
        .gh-cell.empty { background: transparent; pointer-events: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Availability year picker */
        .avail-year-picker-wrap {
            position: relative;
            display: inline-block;
        }
        .avail-year-btn {
            padding: 4px 14px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--color-primary, #2563EB);
            background: #EFF6FF;
            border: 1.5px solid #BFDBFE;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .avail-year-btn:hover {
            background: #DBEAFE;
            border-color: #93C5FD;
        }
        .avail-year-grid {
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            z-index: 1000;
            display: none;
            flex-direction: column;
            min-width: 200px;
            overflow: hidden;
        }
        .avail-year-grid.show { display: flex; }

        @media (max-width: 768px) {
            .result-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <!-- CONTENT -->
    <div class="olah-container">
        <div class="olah-header">
            <div>
                <h1><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px; vertical-align:middle; flex-shrink:0;"><path d="M3 20h18"/><path d="M6 16l4-6 4 4 6-8"/><circle cx="6" cy="16" r="1.5" fill="var(--color-primary)"/><circle cx="10" cy="10" r="1.5" fill="var(--color-primary)"/><circle cx="14" cy="14" r="1.5" fill="var(--color-primary)"/><circle cx="20" cy="6" r="1.5" fill="var(--color-primary)"/></svg>Olah
                    Data Anda</h1>
                <p>Upload file CSV atau Excel (.xls/.xlsx) dan hitung <i>trend</i> data runtut waktu langsung di
                    peramban Anda.
                </p>
            </div>
        </div>

        <!-- Upload Zone -->
        <div class="upload-zone" id="uploadZone">
            <input type="file" id="fileInput" accept=".csv,.xls,.xlsx">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" y1="3" x2="12" y2="15" />
            </svg>
            <h3>Seret file atau klik untuk upload</h3>
            <p>Format yang didukung: CSV, XLS, XLSX — Kolom: <strong>Tanggal</strong> (dd/mm/yy) dan
                <strong>Data</strong>
            </p>
            <div class="file-info" id="fileInfo" style="display:none;"></div>
        </div>

        <!-- Config & Preview -->
        <div class="config-section" id="configSection">
            <!-- Preview Table Accordion -->
            <div class="preview-accordion" style="margin-bottom: 24px;">
                <div class="accordion-header" id="previewAccordionHeader" onclick="togglePreviewAccordion()">
                    <h4>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18M9 3v18" />
                        </svg>
                        Pralihat Data <span id="previewCount"
                            style="font-weight:400; font-size:0.8rem; color:#6B7280; margin-left:8px;">—</span>
                    </h4>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <button class="btn btn-secondary" style="padding:4px 12px; font-size:0.75rem;"
                            onclick="event.stopPropagation(); resetUpload()">Ganti File</button>
                        <svg class="accordion-icon" viewBox="0 0 24 24" width="18" height="18" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </div>
                </div>
                <div class="accordion-content" id="previewAccordionContent">
                    <div class="preview-scroll" id="previewScroll" style="max-height: 400px; overflow-y: auto;">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th id="previewDateHeader">Tanggal</th>
                                    <th id="previewDataHeader">Data</th>
                                </tr>
                            </thead>
                            <tbody id="previewBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="olah-toolbar">
                <label>Pilih Data:</label>
                <select id="olahDtType" class="form-select" style="width:auto; min-width:120px;"
                    onchange="toggleOlahMonth()">
                    <option value="bulanan" selected>Bulanan</option>
                    <option value="tahunan">Tahunan</option>
                    <option value="musiman">Musiman</option>
                </select>
                <select id="olahAgg" class="form-select" style="width:auto; min-width:100px;">
                    <option value="kumulatif" selected>Kumulatif</option>
                    <option value="min">Min</option>
                    <option value="maks">Maks</option>
                    <option value="rerata">Rerata</option>
                </select>
                <select id="olahMonth" class="form-select" style="width:auto; min-width:130px;">
                    <option value="all">Semua Bulan</option>
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>

                <div class="year-picker-wrapper" style="width: auto; min-width: 220px;">
                    <div class="year-picker" id="pickerFrom">
                        <button type="button" class="year-display" id="displayFrom">1980</button>
                        <div class="year-grid hidden" id="gridFrom"></div>
                    </div>
                    <div class="year-sep">—</div>
                    <div class="year-picker" id="pickerTo">
                        <button type="button" class="year-display" id="displayTo">2025</button>
                        <div class="year-grid hidden" id="gridTo"></div>
                    </div>
                    <input type="hidden" id="olahYFrom" value="1980">
                    <input type="hidden" id="olahYTo" value="2025">
                </div>

                <div style="display:flex; align-items:center; gap:6px;">
                    <label style="font-size:0.82rem; font-weight:600; color:var(--color-text-secondary); white-space:nowrap;">Satuan:</label>
                    <select id="olahSatuan" class="form-select" style="width:auto; min-width:100px;" onchange="toggleOlahSatuanManual()">
                        <option value="mm">mm</option>
                        <option value="m³/det">m³/det</option>
                        <option value="°C">°C</option>
                        <option value="liter/det">liter/det</option>
                        <option value="manual">Lainnya...</option>
                    </select>
                </div>

                <button class="btn btn-primary" onclick="runOlahAnalysis()" id="btnOlahRun" style="white-space:nowrap;">
                    Olah Data
                </button>
                <div id="olahSpinner" class="spinner"
                    style="display:none; width:22px; height:22px; border-width:2.5px;"></div>
                    
                <span id="inputPeriodBadge" class="period-badge period-harian" style="margin-left:auto;">Data
                    Harian</span>
            </div>
        </div>

        <!-- Results -->
        <div class="results-section" id="resultsSection">
            <!-- Chart -->
            <div class="result-card full">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px;">
                    <h3 style="margin: 0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18" />
                            <path d="M7 16l4-8 4 4 4-10" />
                        </svg>
                        Grafik Data Runtut Waktu
                    </h3>
                    <div style="display:flex; gap:12px; font-size:0.8rem; font-weight:600; color:#4B5563;">
                        <span style="display:flex; align-items:center; gap:4px;">
                            <span style="width:16px; height:3px; background:#3B82F6; border-radius:2px;"></span> Nilai
                            Data
                        </span>
                        <span style="display:flex; align-items:center; gap:4px;">
                            <span style="width:16px; height:3px; background:#DC2626; border-radius:2px;"></span> Garis
                            Regresi
                        </span>
                    </div>
                </div>
                <div style="height:320px;">
                    <canvas id="olahChart"></canvas>
                </div>
            </div>

            <!-- Trend Results + Availability -->
            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 24px;">
                <!-- Trend Recap -->
                <div class="result-card" style="flex: 2; min-width: 380px;">
                    <div class="card-loader-olah" id="olahTrendLoader">
                        <div class="spinner"></div>
                        <span style="font-size:0.8rem; font-weight:600; color:var(--color-primary);">Menghitung
                            <i>Trend</i>...</span>
                    </div>
                    <h3 style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="display:inline-flex; align-items:center; gap:6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                                <path d="M23 6l-9.5 9.5-5-5L1 18" />
                            </svg>
                            Rekapitulasi <i>Trend</i> Data Runtut Waktu
                        </span>
                        <label id="olahSeasonalToggleWrap" style="display:none; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" id="olahSeasonalToggle" checked style="display:none;">
                            <span style="font-size:0.8rem; font-weight:600; color:#6B7280; letter-spacing:0.02em;">Musiman</span>
                            <span class="toggle-slider"></span>
                        </label>
                    </h3>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div style="padding:14px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                            <strong id="olahMkLabel" style="display:block; margin-bottom:8px; color:#2563EB;">Mann Kendall</strong>
                            <div id="olahMkResult" class="trend-result" style="font-size:0.9rem; color:#4B5563;">Menunggu data...</div>
                        </div>
                        <div style="padding:14px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                            <strong id="olahSsLabel" style="display:block; margin-bottom:8px; color:#2563EB;">Sen's Slope</strong>
                            <div id="olahSsResult" class="trend-result" style="font-size:0.9rem; color:#4B5563;">Menunggu data...</div>
                        </div>
                        <div style="padding:14px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                            <strong style="display:block; margin-bottom:8px; color:#2563EB;">Regresi Linear</strong>
                            <div id="olahLrResult" style="font-size:0.9rem; color:#4B5563;">Menunggu data...</div>
                        </div>
                    </div>
                    <div style="font-size: 0.8rem; color: #6B7280; margin-top: 12px;"><span
                            style="color:#DC2626;">*</span> tingkat kepercayaan 95% (α = 0.05)
                    </div>
                </div>

                <!-- Availability -->
                <div class="result-card" style="flex: 1; min-width: 220px; display: flex; flex-direction: column;">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        <span id="olahAvailTitle">Ketersediaan Data Periode Terpilih</span>
                    </h3>
                    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; gap:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                            <span id="olahAvailPct"
                                style="font-size:2.5rem; font-weight:800; color:#1F2937; line-height:1;">—</span>
                        </div>
                        <div style="height:6px; background:#F1F5F9; border-radius:100px; overflow:hidden; width:100%;">
                            <div id="olahAvailBar"
                                style="height:100%; width:0%; background:#3B82F6; transition: width 1s ease, background 0.4s;">
                            </div>
                        </div>
                        <div id="olahAvailLabel" style="font-size:0.85rem; color:#6B7280; font-weight:500;">
                            Menunggu data...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="result-grid">
                <div class="result-card">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18M9 3v18" />
                        </svg>
                        Parameter Statistik
                    </h3>
                    <table class="stat-table" id="olahStatTable">
                        <tbody>
                            <tr>
                                <td>Rerata</td>
                                <td id="olahStatMean">—</td>
                            </tr>
                            <tr>
                                <td>Maksimum</td>
                                <td id="olahStatMax">—</td>
                            </tr>
                            <tr>
                                <td>Minimum</td>
                                <td id="olahStatMin">—</td>
                            </tr>
                            <tr>
                                <td>Simpangan Baku</td>
                                <td id="olahStatStd">—</td>
                            </tr>
                            <tr>
                                <td>Koefisien Variansi</td>
                                <td id="olahStatCv">—</td>
                            </tr>
                            <tr>
                                <td>Jumlah Data</td>
                                <td id="olahStatLength">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="result-card">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18M9 3v18" />
                        </svg>
                        <i>Outlier</i> (Pencilan)
                    </h3>
                    <table class="stat-table">
                        <tbody>
                            <tr>
                                <td>Batas Bawah</td>
                                <td id="olahStatLb">—</td>
                            </tr>
                            <tr>
                                <td>Batas Atas</td>
                                <td id="olahStatUb">—</td>
                            </tr>
                            <tr>
                                <td>Terdapat <i>Outlier</i>?</td>
                                <td id="olahStatOutlier">—</td>
                            </tr>
                            <tr>
                                <td>Periode Data</td>
                                <td id="olahStatRange">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ketersediaan Data Harian (hanya untuk data harian) -->
            <div class="result-card full" id="olahDailyAvailCard" style="margin-bottom:24px; display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap;">
                    <h3 style="margin:0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        Ketersediaan Data Harian
                    </h3>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <button class="btn btn-secondary" id="btnTogglePie" style="padding:6px 14px; font-size:0.82rem; font-weight:600; border-radius:8px; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s ease; box-shadow:0 1px 2px rgba(0,0,0,0.04);" onclick="toggleOlahPieChart()">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path d="M12 3v9l4 2"/>
                            </svg>
                            <span id="btnTogglePieText">Tampilkan Ringkasan (Pie Chart)</span>
                        </button>
                        <div id="olahDailyYearNav" style="display:flex; gap:8px; align-items:center;">
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:0.8rem;"
                                onclick="changeOlahAvailYear(-1)">← Mundur</button>
                            <div class="avail-year-picker-wrap">
                                <button type="button" class="avail-year-btn" id="olahAvailYearDisplay" onclick="toggleOlahAvailYearPicker(event)">-</button>
                                <div class="avail-year-grid" id="olahAvailYearGrid"></div>
                            </div>
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:0.8rem;"
                                onclick="changeOlahAvailYear(1)">Maju →</button>
                        </div>
                    </div>
                </div>

                <div id="olahDailyGridWrapper">
                    <div id="olahDailyGridContent">
                        <div style="display:flex; align-items:flex-start; gap:8px; justify-content:center;">
                            <!-- Nama Bulan -->
                            <div style="display:grid; grid-template-rows:repeat(12,35px); gap:2px; font-size:0.75rem; color:#6B7280; text-align:right; font-weight:600; padding-top:15px;">
                                <div>Januari</div><div>Februari</div><div>Maret</div><div>April</div>
                                <div>Mei</div><div>Juni</div><div>Juli</div><div>Agustus</div>
                                <div>September</div><div>Oktober</div><div>November</div><div>Desember</div>
                            </div>
                            <div style="flex:none; overflow-x:auto;" class="no-scrollbar">
                                <div id="olahAvailGrid" class="github-grid" style="width:max-content;"></div>
                                <div style="display:grid; grid-template-columns:repeat(31,35px); gap:2px; margin-top:8px; font-size:0.7rem; color:#9CA3AF; text-align:center; font-weight:500;">
                                    <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
                                    <span>6</span><span>7</span><span>8</span><span>9</span><span>10</span>
                                    <span>11</span><span>12</span><span>13</span><span>14</span><span>15</span>
                                    <span>16</span><span>17</span><span>18</span><span>19</span><span>20</span>
                                    <span>21</span><span>22</span><span>23</span><span>24</span><span>25</span>
                                    <span>26</span><span>27</span><span>28</span><span>29</span><span>30</span>
                                    <span>31</span>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; gap:16px; margin-top:20px; border-top:1px solid #E5E7EB; padding-top:12px; font-size:0.85rem; flex-wrap:wrap;">
                            <div style="flex:1; min-width:300px;">
                                <div id="olahAvailSummary">-</div>
                            </div>
                            <div style="display:flex; gap:16px; align-items:center; font-size:0.8rem; color:#4B5563;">
                                <span><span style="display:inline-block;width:12px;height:12px;background:#3B82F6;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Tersedia</span>
                                <span><span style="display:inline-block;width:12px;height:12px;background:#EF4444;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Hilang</span>
                            </div>
                        </div>
                    </div>

                    <div id="olahDailyPieWrapper" style="display:none; align-items:center; justify-content:center; flex-direction:column; min-height:300px; width:100%; padding:24px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; box-sizing:border-box;">
                        <div style="width:min(320px,100%); aspect-ratio:1/1; margin-bottom:20px; position:relative;">
                            <canvas id="olahDailyPieChart"></canvas>
                        </div>
                        <div id="olahDailyPieSummary" style="font-size:0.95rem; color:#4B5563; text-align:center; max-width:90%; line-height:1.6;"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="js/olah-data.js"></script>

</body>

</html>