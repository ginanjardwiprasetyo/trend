<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Rekapitulasi Data & Olahan Trend — TrendHidro</title>
    <meta name="description"
        content="Rekapitulasi ketersediaan data curah hujan dan ringkasan olahan trend untuk laporan hidrologi.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

    <style>
        body {
            background: #F9FAFB;
            overflow-y: auto;
            height: auto;
        }

        /* --- Floating Gradient Orbs --- */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.25;
            pointer-events: none;
            z-index: 0;
            animation: float-orb 25s ease-in-out infinite;
        }

        .orb-1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #3B82F6 0%, transparent 70%);
            top: -150px;
            right: -100px;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #8B5CF6 0%, transparent 70%);
            bottom: -100px;
            left: -100px;
            animation-delay: -5s;
        }

        @keyframes float-orb {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(40px, -30px) scale(1.08);
            }
        }

        .rekap-container {
            position: relative;
            z-index: 1;
            padding: 90px 24px 60px;
            max-width: 1240px;
            margin: 0 auto;
        }

        .rekap-header {
            margin-bottom: 32px;
            text-align: left;
        }

        .rekap-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #1F2937;
            margin-bottom: 8px;
        }

        .rekap-header h1 span {
            background: linear-gradient(135deg, #2563EB 0%, #7C3AED 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .rekap-header p {
            color: #4B5563;
            font-size: 1rem;
            max-width: 800px;
            line-height: 1.6;
        }

        /* --- Glassmorphism Panel --- */
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            margin-bottom: 32px;
            transition: transform 0.3s;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #F3F4F6;
            padding-bottom: 12px;
        }

        .section-title svg {
            color: #2563EB;
            width: 22px;
            height: 22px;
        }

        /* --- Custom Responsive Tables --- */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 8px 16px;
            border: 1.5px solid #E5E7EB;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 300px;
            max-width: 100%;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-select-premium {
            padding: 8px 16px;
            border: 1.5px solid #E5E7EB;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            background-color: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }

        .form-select-premium:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 16px;
        }

        .premium-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            background: #fff;
            min-width: 800px;
        }

        .premium-table th {
            background: #F8FAFC;
            padding: 14px 20px;
            font-size: 0.85rem;
            color: #475569;
            font-weight: 700;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #E2E8F0;
            text-align: center;
        }

        .premium-table td {
            padding: 14px 20px;
            font-size: 0.9rem;
            color: #334155;
            border-bottom: 1px solid #E2E8F0;
            text-align: center;
            vertical-align: middle;
        }

        .premium-table tr:last-child td {
            border-bottom: none;
        }

        .premium-table tbody tr:hover {
            background: #F8FAFC;
        }

        .badge-premium {
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-block;
        }

        .badge-success {
            background: #DCFCE7;
            color: #166534;
        }

        .badge-warning {
            background: #FEF9C3;
            color: #854D0E;
        }

        .badge-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        .academic-note {
            background: #F0F9FF;
            border-left: 4px solid #0284C7;
            padding: 16px;
            border-radius: 0 12px 12px 0;
            margin-bottom: 24px;
            font-size: 0.92rem;
            color: #0369A1;
            line-height: 1.6;
        }

        /* --- Pagination --- */
        .rekap-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }

        .page-info {
            font-size: 0.85rem;
            color: #64748B;
        }

        .page-ctrls {
            display: flex;
            gap: 6px;
        }

        /* Export/Print Button */
        .export-btn {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #fff;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        .export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        /* Loader */
        .table-loading {
            text-align: center;
            padding: 40px;
            color: #64748B;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .spinner-rekap {
            width: 32px;
            height: 32px;
            border: 3px solid #E2E8F0;
            border-top-color: #2563EB;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }

        /* Tab Menu */
        .tabs-header {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 8px;
        }

        .tab-btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #64748B;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: #EFF6FF;
            color: #2563EB;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .filter-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
            align-items: flex-end;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.05em;
        }

        /* --- Multi-Select with Search --- */
        .ms-container { position:relative; width:100%; }
        .ms-trigger { display:flex; align-items:center; gap:6px; padding:8px 12px; border:1.5px solid #E5E7EB; border-radius:8px; background:#fff; cursor:pointer; transition:border-color .2s; min-height:42px; }
        .ms-trigger:focus-within, .ms-trigger:hover { border-color:#2563EB; box-shadow:0 0 0 3px rgba(37,99,235,0.15); }
        .ms-tags { flex:1; display:flex; flex-wrap:wrap; gap:4px; }
        .ms-tag { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; background:#EFF6FF; color:#1E40AF; font-size:0.78rem; font-weight:600; border-radius:4px; border:1px solid #BFDBFE; }
        .ms-tag-remove { cursor:pointer; font-size:0.9rem; line-height:1; color:#1E40AF; opacity:0.6; }
        .ms-tag-remove:hover { opacity:1; }
        .ms-placeholder { color:#9CA3AF; font-size:0.85rem; }
        .ms-chevron { width:16px; height:16px; color:#6B7280; flex-shrink:0; transition:transform .2s; }
        .ms-container.open .ms-chevron { transform:rotate(180deg); }
        .ms-dropdown { display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #E2E8F0; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,0.1); z-index:100; max-height:260px; flex-direction:column; overflow:hidden; }
        .ms-container.open .ms-dropdown { display:flex; }
        .ms-search-wrap { padding:8px; border-bottom:1px solid #F1F5F9; }
        .ms-search { width:100%; padding:8px 10px; border:1px solid #E2E8F0; border-radius:6px; font-size:0.82rem; outline:none; box-sizing:border-box; }
        .ms-search:focus { border-color:#2563EB; }
        .ms-options { overflow-y:auto; flex:1; }
        .ms-opt { display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:0.85rem; color:#334155; cursor:pointer; }
        .ms-opt:hover { background:#F8FAFC; }
        .ms-opt.selected { background:#EFF6FF; color:#1E40AF; font-weight:600; }
        .ms-opt input[type="checkbox"] { accent-color:#2563EB; margin:0; flex-shrink:0; }
        .ms-hidden { display:none; }

        .landing-footer {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 24px;
            font-size: 0.82rem;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
        }
    </style>
</head>

<body>
    <!-- Floating Gradient Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- NAVBAR -->
    <?php include 'navbar.php'; ?>

    <!-- CONTENT -->
    <div class="rekap-container">
        <div class="rekap-header">
            <h1>Rekapitulasi & <span>Statistik Ketersediaan Data</span></h1>
            <p>Rincian basis data hidrologi curah hujan yang komprehensif, disiapkan dalam format tabel standar untuk
                mempermudah penyusunan laporan atau publikasi ilmiah Anda.</p>
        </div>

        <!-- Catatan Akademik -->
        <div class="academic-note">
            <strong>💡 Petunjuk:</strong> Filter, mencari stasiun tertentu, dan mengekspor langsung hasil tabel ke format
            cetak atau menyalinnya ke dokumen.
        </div>

        <!-- TABS HEADER -->
        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab('tab-ringkasan', this)">Ringkasan & Basis Data</button>
            <button class="tab-btn" onclick="switchTab('tab-tren', this)">Olahan Trend Curah Hujan</button>
            <button class="tab-btn" onclick="switchTab('tab-deskriptif', this)">Statistik Deskriptif</button>
            <button class="tab-btn" onclick="switchTab('tab-per-stasiun', this)">Olahan Per Stasiun</button>
        </div>

        <!-- ==================== TAB 1: RINGKASAN & BASIS DATA ==================== -->
        <div id="tab-ringkasan" class="tab-content active">
            <!-- Glass Panel Ringkasan -->
            <div class="glass-panel">
                <h2 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="9" x2="15" y2="9"></line>
                        <line x1="9" y1="13" x2="15" y2="13"></line>
                        <line x1="9" y1="17" x2="15" y2="17"></line>
                    </svg>
                    Ringkasan Ketersediaan Data Curah Hujan dalam Basis Data Program
                </h2>

                <div class="table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th style="width: 70%;">Uraian Parameter Basis Data</th>
                                <th style="width: 30%;">Jumlah / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="summaryTableBody">
                            <tr>
                                <td colspan="2" class="table-loading">
                                    <div class="spinner-rekap"></div>
                                    Menganalisis basis data stasiun...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Glass Panel Rincian Stasiun -->
            <div class="glass-panel">
                <h2 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    Tabel Ketersediaan Data Curah Hujan Detail Per Stasiun
                </h2>

                <div class="table-controls">
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
                        <input type="text" class="search-input" id="searchStation"
                            placeholder="Cari nama stasiun atau lokasi..." oninput="handleSearch()">
                        <select class="form-select-premium" id="filterCompleteness"
                            onchange="handleCompletenessFilter()">
                            <option value="all">Semua Stasiun Data</option>
                            <option value="gt50">Kelengkapan > 50%</option>
                            <option value="ge30">Panjang Data &ge; 30 Tahun</option>
                            <option value="ge16">Panjang Data &ge; 16 Tahun</option>
                            <option value="lt16">Panjang Data &lt; 16 Tahun</option>
                        </select>
                    </div>
                    <button class="export-btn"
                        onclick="exportToExcel('detail-table', 'Tabel_Detail_Ketersediaan_Data_Stasiun')">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                        </svg>
                        Unduh Excel (.xlsx)
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="premium-table" id="detail-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Stasiun</th>
                                <th>Lokasi Administratif</th>
                                <th>Rentang Data (Tahun)</th>
                                <th>Panjang Data</th>
                                <th>Persen Kelengkapan</th>
                            </tr>
                        </thead>
                        <tbody id="detailTableBody">
                            <tr>
                                <td colspan="8" class="table-loading">
                                    <div class="spinner-rekap"></div>
                                    Sedang melakukan analisis statistik untuk seluruh stasiun...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rekap-pagination">
                    <div class="page-info" id="detailPageInfo"></div>
                    <div class="page-ctrls" id="detailPaginationCtrls"></div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: ANALISIS TREN ==================== -->
        <div id="tab-tren" class="tab-content">
            <div class="glass-panel">
                <h2 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    Olahan Trend Curah Hujan
                </h2>

                <!-- Filter Panel for Table 4.2 -->
                <div class="filter-panel">
                    <div class="filter-group">
                        <label class="filter-label">Kelengkapan Data</label>
                        <select class="form-select-premium" id="trenCompleteness" onchange="handleTrenDisplayChange()">
                            <option value="all">Semua Stasiun Data</option>
                            <option value="gt50">Kelengkapan > 50%</option>
                            <option value="ge30">Panjang Data &ge; 30 Tahun</option>
                            <option value="ge16">Panjang Data &ge; 16 Tahun</option>
                            <option value="lt16">Panjang Data &lt; 16 Tahun</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Pilih Data</label>
                        <select class="form-select-premium" id="trenDtType" onchange="toggleTrenMonth()">
                            <option value="bulanan">Bulanan</option>
                            <option value="tahunan" selected>Tahunan</option>
                            <option value="musiman">Musiman</option>
                        </select>
                    </div>
                    <div class="filter-group" id="groupTrenMonth" style="display: none;">
                        <label class="filter-label" id="labelTrenMonth">Pilih Bulan</label>
                        <select class="form-select-premium" id="trenMonth" onchange="applyTrenFilters()">
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
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Tampilan Data</label>
                        <select class="form-select-premium" id="trenDisplayStyle" onchange="handleTrenDisplayChange()">
                            <option value="sign">Tanda (+/−/*)</option>
                            <option value="icon">Ikon Panah (▲/▼)</option>
                            <option value="iconOnly">Ikon Saja (Export)</option>
                        </select>
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer; margin-top:6px; font-size:0.82rem; color:#475569;">
                            <input type="checkbox" id="trenShowSig" checked onchange="renderTrendTable()" style="accent-color:#2563EB; width:15px; height:15px;">
                            Tampilkan tanda * (signifikan)
                        </label>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Tahun Mulai</label>
                        <select class="form-select-premium" id="trenYearFrom" onchange="applyTrenFilters()">
                            <!-- JS populated -->
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Tahun Selesai</label>
                        <select class="form-select-premium" id="trenYearTo" onchange="applyTrenFilters()">
                            <!-- JS populated -->
                        </select>
                    </div>
                    <div class="filter-group" style="justify-content: flex-end; align-items: flex-end;">
                        <button class="export-btn"
                            onclick="exportToExcel('tren-table', 'Tabel_4_2_Olahan_Trend_Curah_Hujan')"
                            style="width: 100%;">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                            </svg>
                            Unduh Excel
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="premium-table" id="tren-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Stasiun</th>
                                <th id="th-mk">Z</th>
                                <th id="th-ss">Q<sub>med</sub></th>
                                <th>Z<sub>SMK</sub></th>
                                <th>Q<sub>med,gab</sub></th>
                                <th id="th-lr">t</th>
                            </tr>
                        </thead>
                        <tbody id="trenTableBody">
                            <tr>
                                <td colspan="8" class="table-loading">
                                    <div class="spinner-rekap"></div>
                                    Sedang melakukan analisis statistik untuk seluruh stasiun...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Legenda dinamis di bawah tabel -->
                <div id="trenLegendContainer" style="margin-top: 16px;"></div>
            </div>
        </div>

        <!-- ==================== TAB 3: OLAHAN PER STASIUN ==================== -->
        <div id="tab-per-stasiun" class="tab-content">
            <div class="glass-panel">
                <h2 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    Olahan Trend Per Stasiun — Semua Tipe Data
                </h2>

                <div class="filter-panel">
                    <div class="filter-group" style="grid-column: 1 / -1;">
                        <label class="filter-label">Pilih Stasiun</label>
                        <div class="ms-container" id="psMsContainer">
                            <div class="ms-trigger" id="psMsTrigger" tabindex="0">
                                <div class="ms-tags" id="psMsTags"><span class="ms-placeholder">Pilih stasiun...</span></div>
                                <svg class="ms-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                            </div>
                            <div class="ms-dropdown" id="psMsDropdown">
                                <div class="ms-search-wrap"><input class="ms-search" id="psMsSearch" type="text" placeholder="Cari stasiun..."></div>
                                <div class="ms-options" id="psMsOptions"></div>
                            </div>
                            <select class="form-select-premium" id="psStationSelect" multiple style="display:none;"></select>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Tahun Mulai</label>
                        <select class="form-select-premium" id="psYearFrom" onchange="applyPerStasiunFilters()">
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Tahun Selesai</label>
                        <select class="form-select-premium" id="psYearTo" onchange="applyPerStasiunFilters()">
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Tampilan Data</label>
                        <select class="form-select-premium" id="psDisplayStyle" onchange="handlePerStasiunDisplayChange()">
                            <option value="sign">Tanda (+/−/*)</option>
                            <option value="icon">Ikon Panah (▲/▼)</option>
                            <option value="iconOnly">Ikon Saja (Export)</option>
                        </select>
                    </div>
                    <div class="filter-group" style="justify-content: flex-end; align-items: flex-end;">
                        <button class="export-btn"
                            onclick="exportToExcel('per-stasiun-table', 'Tabel_Per_Stasiun_Olahan_Trend')"
                            style="width: 100%;">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                            </svg>
                            Unduh Excel
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="premium-table" id="per-stasiun-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Stasiun</th>
                                <th>Jenis Data</th>
                                <th id="ps-th-mk">Z</th>
                                <th id="ps-th-ss">Q<sub>med</sub></th>
                                <th>Z<sub>SMK</sub></th>
                                <th>Q<sub>med,gab</sub></th>
                                <th id="ps-th-lr">t</th>
                            </tr>
                        </thead>
                        <tbody id="perStasiunTableBody">
                            <tr>
                                <td colspan="6" style="padding:30px; text-align:center; color:#9CA3AF; font-size:0.9rem;">
                                    Pilih minimal satu stasiun untuk melihat hasil olahan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="psLegendContainer" style="margin-top: 16px;"></div>
            </div>
        </div>

        <!-- ==================== TAB 4: STATISTIK DESKRIPTIF ==================== -->
        <div id="tab-deskriptif" class="tab-content">
            <div class="glass-panel">
                <h2 class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Statistik Deskriptif Parameter Curah Hujan Harian Stasiun
                </h2>

                <div class="table-controls">
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
                        <select class="form-select-premium" id="filterDeskriptifCompleteness"
                            onchange="handleDeskriptifFilter()">
                            <option value="all">Semua Stasiun Data</option>
                            <option value="gt50">Kelengkapan > 50%</option>
                            <option value="ge30">Panjang Data &ge; 30 Tahun</option>
                            <option value="ge16">Panjang Data &ge; 16 Tahun</option>
                            <option value="lt16">Panjang Data &lt; 16 Tahun</option>
                        </select>
                    </div>
                    <button class="export-btn"
                        onclick="exportToExcel('deskriptif-table', 'Tabel_4_3_Statistik_Deskriptif_Stasiun')">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
                        </svg>
                        Unduh Excel (.xlsx)
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="premium-table" id="deskriptif-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pos Hujan</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Rentang temporal</th>
                                <th>Panjang Rekaman</th>
                                <th>Curah Hujan Maks (mm/hari)</th>
                                <th>Rerata Tahunan (mm/tahun)</th>
                            </tr>
                        </thead>
                        <tbody id="deskriptifTableBody">
                            <tr>
                                <td colspan="7" class="table-loading">
                                    <div class="spinner-rekap"></div>
                                    Sedang melakukan analisis statistik untuk seluruh stasiun...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

    <script>
        let stationsData = [];
        let filteredStations = [];
        let currentPage = 1;
        const rowsPerPage = 15;

        // Years populated dynamically
        let minYearGlobal = 1980;
        let maxYearGlobal = 2025;
        let activeTrenResults = null;
        let activePerStasiunResults = null;
        let perStasiunTypes = ['Kumulatif Bulanan', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'JFM', 'AMJ', 'JAS', 'OND', 'Tahunan'];

        // Switch Tabs
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');

            if (tabId === 'tab-tren' && !activeTrenResults) {
                loadTrendAnalysis();
            }
        }

        // DOM Load
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                // Clear local cache just in case to get updated stats
                localStorage.removeItem('stations_cache_full');

                const res = await fetch('php/get_stations.php');
                const rawFeatures = await res.json();
                stationsData = rawFeatures.map(f => f.properties);

                filteredStations = [...stationsData];

                // Calculate global years range
                stationsData.forEach(st => {
                    if (st.yearStart && st.yearStart < minYearGlobal) minYearGlobal = st.yearStart;
                    if (st.yearEnd && st.yearEnd > maxYearGlobal) maxYearGlobal = st.yearEnd;
                });

                populateYearSelects();
                populatePerStasiunYearSelects();
                populatePerStasiunStationSelect();
                renderSummaryTable();
                renderDetailTable();
                renderDeskriptifTable();
            } catch (err) {
                console.error(err);
                document.getElementById('summaryTableBody').innerHTML = '<tr><td colspan="2" style="color:red; text-align:center;">Gagal memuat basis data stasiun. Silakan segarkan halaman.</td></tr>';
            }
        });

        // Populate dynamic years filter dropdowns
        function populateYearSelects() {
            const fromSelect = document.getElementById('trenYearFrom');
            const toSelect = document.getElementById('trenYearTo');

            fromSelect.innerHTML = '';
            toSelect.innerHTML = '';

            for (let y = minYearGlobal; y <= maxYearGlobal; y++) {
                const optFrom = document.createElement('option');
                optFrom.value = y;
                optFrom.innerText = y;
                if (y === minYearGlobal) optFrom.selected = true;
                fromSelect.appendChild(optFrom);

                const optTo = document.createElement('option');
                optTo.value = y;
                optTo.innerText = y;
                if (y === maxYearGlobal) optTo.selected = true;
                toSelect.appendChild(optTo);
            }
        }

        // Render Tabel 4.1 Ringkasan
        function renderSummaryTable() {
            const total = stationsData.length;
            const withData = stationsData.filter(s => s.yearStart !== null).length;
            const withoutData = total - withData;

            // Stasiun dengan kelengkapan > 50%
            const completenessGe50 = stationsData.filter(s => s.completeness > 50).length;

            // Kategori durasi
            const durationGe30 = stationsData.filter(s => {
                if (s.yearStart === null) return false;
                const len = s.yearEnd - s.yearStart + 1;
                return len >= 30;
            }).length;

            const durationGe16 = stationsData.filter(s => {
                if (s.yearStart === null) return false;
                const len = s.yearEnd - s.yearStart + 1;
                return len >= 16;
            }).length;

            const durationLt16 = stationsData.filter(s => {
                const len = s.yearStart !== null ? (s.yearEnd - s.yearStart + 1) : 0;
                return len < 16;
            }).length;

            const durationLt30 = stationsData.filter(s => {
                const len = s.yearStart !== null ? (s.yearEnd - s.yearStart + 1) : 0;
                return len < 30;
            }).length;

            const tbody = document.getElementById('summaryTableBody');
            tbody.innerHTML = `
                <tr>
                    <td style="text-align:left; font-weight:600;">Total Stasiun Terdaftar</td>
                    <td style="font-weight:700;">${total} Stasiun</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-weight:600; padding-left: 30px;">• Stasiun dengan Rekaman Data</td>
                    <td style="color:#16A34A; font-weight:700;">${withData} Stasiun</td>
                </tr>
                <tr>
                    <td style="text-align:left; font-weight:600; padding-left: 30px;">• Stasiun tanpa Rekaman Data</td>
                    <td style="color:#DC2626; font-weight:700;">${withoutData} Stasiun</td>
                </tr>
                <tr style="background:#EFF6FF;">
                    <td style="text-align:left; font-weight:700; color:#1E3A8A;">Stasiun dengan Tingkat Kelengkapan Data &gt; 50%</td>
                    <td style="font-weight:800; color:#1E3A8A;">${completenessGe50} Stasiun</td>
                </tr>
                <tr style="background:#F0FDF4;">
                    <td style="text-align:left; font-weight:700; color:#166534;">Stasiun dengan Panjang Data &ge; 30 Tahun</td>
                    <td style="font-weight:800; color:#166534;">${durationGe30} Stasiun</td>
                </tr>
                <tr style="background:#F8FAFC;">
                    <td style="text-align:left; font-weight:700; color:#334155;">Stasiun dengan Panjang Data &ge; 16 Tahun</td>
                    <td style="font-weight:800; color:#334155;">${durationGe16} Stasiun</td>
                </tr>
                <tr style="background:#FEF2F2;">
                    <td style="text-align:left; font-weight:700; color:#991B1B;">Stasiun dengan Panjang Data &lt; 16 Tahun</td>
                    <td style="font-weight:800; color:#991B1B;">${durationLt16} Stasiun</td>
                </tr>
                <tr style="background:#FFF7ED;">
                    <td style="text-align:left; font-weight:700; color:#9A3412;">Stasiun dengan Panjang Data &lt; 30 Tahun</td>
                    <td style="font-weight:800; color:#9A3412;">${durationLt30} Stasiun</td>
                </tr>
            `;
        }

        // Completeness filter for Detail Table
        function handleCompletenessFilter() {
            const compFilter = document.getElementById('filterCompleteness').value;
            const searchQuery = document.getElementById('searchStation').value.toLowerCase();

            filteredStations = stationsData.filter(st => {
                const matchesSearch = st.name.toLowerCase().includes(searchQuery) || st.location.toLowerCase().includes(searchQuery);
                let matchesCompleteness = true;
                const len = st.yearStart !== null ? (st.yearEnd - st.yearStart + 1) : 0;
                if (compFilter === 'gt50') matchesCompleteness = st.completeness > 50;
                else if (compFilter === 'ge30') matchesCompleteness = len >= 30;
                else if (compFilter === 'ge16') matchesCompleteness = len >= 16;
                else if (compFilter === 'lt16') matchesCompleteness = len < 16 && len > 0;
                return matchesSearch && matchesCompleteness;
            });

            currentPage = 1;
            renderDetailTable();
        }

        // Render Rincian Stasiun Detail
        function renderDetailTable() {
            const tbody = document.getElementById('detailTableBody');
            tbody.innerHTML = '';

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = filteredStations.slice(start, end);

            if (pageData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="padding:20px; color:#6B7280;">Tidak ada stasiun cocok dengan pencarian Anda.</td></tr>';
                return;
            }

            pageData.forEach((st, idx) => {
                const tr = document.createElement('tr');
                const rowNo = start + idx + 1;

                const yearRange = (st.yearStart && st.yearEnd) ? `${st.yearStart} — ${st.yearEnd}` : '—';
                const lengthDisplay = (st.yearStart && st.yearEnd) ? `${st.yearEnd - st.yearStart + 1} Tahun` : '—';

                const pct = st.completeness || 0;
                const pctDisplay = pct.toFixed(2).replace('.', ',') + '%';
                let badgeClass = 'badge-danger';
                if (pct > 80) badgeClass = 'badge-success';
                else if (pct > 50) badgeClass = 'badge-warning';

                const locClean = st.location.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());

                tr.innerHTML = `
                    <td style="color:#64748B; font-weight:600;">${rowNo}</td>
                    <td style="font-weight:700; color:#1E293B;">Pos ${st.name}</td>
                    <td style="text-align:left;">${locClean}</td>
                    <td>${yearRange}</td>
                    <td style="font-weight:600;">${lengthDisplay}</td>
                    <td><span class="badge-premium ${badgeClass}">${pctDisplay}</span></td>
                `;
                tbody.appendChild(tr);
            });

            // Page info & ctrls
            document.getElementById('detailPageInfo').innerHTML = `Menampilkan <strong>${start + 1} - ${Math.min(end, filteredStations.length)}</strong> dari <strong>${filteredStations.length}</strong> stasiun`;

            const totalPages = Math.ceil(filteredStations.length / rowsPerPage);
            const ctrls = document.getElementById('detailPaginationCtrls');
            ctrls.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    const btn = document.createElement('button');
                    btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                    btn.innerText = i;
                    btn.style.cssText = 'padding:6px 12px; font-size:0.85rem; font-weight:600; border:1px solid #E2E8F0; border-radius:6px; margin:0 2px; cursor:pointer; background:#fff;';
                    if (i === currentPage) {
                        btn.style.background = '#2563EB';
                        btn.style.color = '#fff';
                        btn.style.borderColor = '#2563EB';
                    }
                    btn.onclick = () => {
                        currentPage = i;
                        renderDetailTable();
                    };
                    ctrls.appendChild(btn);
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    const span = document.createElement('span');
                    span.innerText = '...';
                    span.style.cssText = 'padding: 0 4px; color: #94A3B8;';
                    ctrls.appendChild(span);
                }
            }
        }

        // Filter Deskriptif Table
        function handleDeskriptifFilter() {
            renderDeskriptifTable();
        }

        // Render Deskriptif (Tabel 4.3) with completeness filter
        function renderDeskriptifTable() {
            const tbody = document.getElementById('deskriptifTableBody');
            tbody.innerHTML = '';

            const compFilter = document.getElementById('filterDeskriptifCompleteness').value;
            let counter = 1;

            stationsData.forEach((st) => {
                // Check duration and completeness criteria
                const len = st.yearStart !== null ? (st.yearEnd - st.yearStart + 1) : 0;
                if (compFilter === 'gt50' && st.completeness <= 50) return;
                if (compFilter === 'ge30' && len < 30) return;
                if (compFilter === 'ge16' && len < 16) return;
                if (compFilter === 'lt16' && (len >= 16 || len === 0)) return;

                const tr = document.createElement('tr');

                const yearRange = (st.yearStart && st.yearEnd) ? `${st.yearStart} — ${st.yearEnd}` : '—';
                const lengthDisplay = (st.yearStart && st.yearEnd) ? `${st.yearEnd - st.yearStart + 1} Tahun` : '—';
                const maxRainDisplay = st.maxRain !== null ? `${st.maxRain.toFixed(1).replace('.', ',')} mm` : '—';
                const avgAnnualDisplay = st.avgAnnualRain !== null ? `${st.avgAnnualRain.toFixed(1).replace('.', ',')} mm/tahun` : '—';

                tr.innerHTML = `
                    <td style="color:#64748B; font-weight:600;">${counter++}</td>
                    <td style="font-weight:700; color:#1E293B;">Pos ${st.name}</td>
                    <td>${st.lat.toFixed(6).replace('.', ',')}</td>
                    <td>${st.lon.toFixed(6).replace('.', ',')}</td>
                    <td>${yearRange}</td>
                    <td style="font-weight:600;">${lengthDisplay}</td>
                    <td style="color:#DC2626; font-weight:700;">${maxRainDisplay}</td>
                    <td style="color:#2563EB; font-weight:700;">${avgAnnualDisplay}</td>
                `;
                tbody.appendChild(tr);
            });

            if (counter === 1) {
                tbody.innerHTML = '<tr><td colspan="8" style="padding:20px; color:#6B7280; text-align:center;">Tidak ada stasiun yang memenuhi filter kelengkapan data.</td></tr>';
            }
        }

        // Toggle month/season selects based on dataType (same as olah-data.html)
        function toggleTrenMonth() {
            const dtType = document.getElementById('trenDtType').value;
            const group = document.getElementById('groupTrenMonth');
            const mo = document.getElementById('trenMonth');

            if (dtType === 'tahunan') {
                group.style.display = 'none';
            } else if (dtType === 'musiman') {
                group.style.display = 'inline-block';
                mo.innerHTML = `
                    <option value="1,2,3">Jan–Feb–Mar</option>
                    <option value="4,5,6">Apr–Mei–Jun</option>
                    <option value="7,8,9">Jul–Agus–Sep</option>
                    <option value="10,11,12">Okt–Nov–Des</option>
                `;
            } else {
                group.style.display = 'inline-block';
                mo.innerHTML = `
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
                `;
            }
            applyTrenFilters();
        }

        // Load or Re-load Trend Analysis with filters (Tabel 4.2)
        async function loadTrendAnalysis() {
            const tbody = document.getElementById('trenTableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="table-loading"><div class="spinner-rekap"></div>Melakukan analisis komputasi statistik runtut waktu...</td></tr>';

            const yrFrom = parseInt(document.getElementById('trenYearFrom').value) || minYearGlobal;
            const yrTo = parseInt(document.getElementById('trenYearTo').value) || maxYearGlobal;
            const dtType = document.getElementById('trenDtType') ? document.getElementById('trenDtType').value : 'tahunan';
            let mo = document.getElementById('trenMonth') ? document.getElementById('trenMonth').value : 'all';
            if (dtType === 'tahunan') mo = 'all';

            try {
                const response = await fetch('php/analyze_all.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        dataType: dtType,
                        aggregation: 'kumulatif',
                        yearFrom: yrFrom,
                        yearTo: yrTo,
                        month: mo
                    })
                });

                const resData = await response.json();
                if (!resData.success) throw new Error(resData.message);

                activeTrenResults = resData.results;
                renderTrendTable();

            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="7" style="color:red; padding:20px;">Gagal memuat olahan trend curah hujan. Silakan sesuaikan filter atau segarkan halaman.</td></tr>';
            }
        }

        // Helper: get slope unit label based on aggregation (always kumulatif) and dataType
        function getSlopeUnit() {
            const dtType = document.getElementById('trenDtType') ? document.getElementById('trenDtType').value : 'tahunan';
            const mo = document.getElementById('trenMonth') ? document.getElementById('trenMonth').value : 'all';

            if (dtType === 'tahunan') {
                return '(mm/th)';
            } else if (dtType === 'bulanan') {
                if (mo === 'all') {
                    return '(mm/bln)';
                } else {
                    return '(mm/th)';
                }
            } else if (dtType === 'musiman') {
                return '(mm/musim/th)';
            }
            return '(mm/th)';
        }

        // Helper: format numeric value with sign/icon based on display style
        function formatTrendVal(val, trendStr, displayStyle, isMk) {
            if (val === undefined || val === null) return '<span style="color:#94A3B8;">—</span>';
            const num = parseFloat(val);
            if (isNaN(num)) return val;
            const formatted = Math.abs(num).toFixed(3).replace('.', ',');
            const trend = (trendStr || '').trim();
            const isSig = isMk && (trend === 'Meningkat' || trend === 'Menurun');
            const showSig = document.getElementById('trenShowSig') ? document.getElementById('trenShowSig').checked : true;
            if (displayStyle === 'iconOnly') {
                if (!isSig) return '\u2013';
                return num > 0 ? '▲' : num < 0 ? '▼' : '\u2013';
            }
            if (displayStyle === 'sign') {
                const sign = num > 0 ? '+' : num < 0 ? '\u2212' : '';
                let text = `${sign}${formatted}`;
                if (isSig && showSig) text += '<span style="color:#B91C1C; font-weight:900;">*</span>';
                return text;
            } else {
                const icon = num > 0 ? '▲' : num < 0 ? '▼' : '•';
                let text = `<span style="font-weight:bold;">${icon}</span> ${formatted}`;
                if (isSig && showSig) text += '<span style="color:#B91C1C; font-weight:900;">*</span>';
                return text;
            }
        }

        // Filter and Render Trend Table (Tabel 4.2)
        function renderTrendTable() {
            const tbody = document.getElementById('trenTableBody');
            tbody.innerHTML = '';

            const completenessFilter = document.getElementById('trenCompleteness').value;
            const displayStyle = document.getElementById('trenDisplayStyle') ? document.getElementById('trenDisplayStyle').value : 'sign';
            let counter = 1;

            stationsData.forEach(st => {
                const trenResult = activeTrenResults[st.id];
                if (!trenResult) return;

                const mk = trenResult['mann-kendall'];
                const ss = trenResult['sens-slope'];
                const lr = trenResult['regresi-linear'];

                if (mk.trend === 'too_large') return;

                const len = st.yearStart !== null ? (st.yearEnd - st.yearStart + 1) : 0;

                if (completenessFilter === 'gt50' && st.completeness <= 50) return;
                if (completenessFilter === 'ge30' && len < 30) return;
                if (completenessFilter === 'ge16' && len < 16) return;
                if (completenessFilter === 'lt16' && (len >= 16 || len === 0)) return;

                const smk = trenResult['seasonal-mann-kendall'] || {};
                const sss = trenResult['seasonal-sens-slope'] || {};

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="color:#64748B; font-weight:600;">${counter++}</td>
                    <td style="font-weight:700; color:#1E293B;">Pos ${st.name}</td>
                    <td style="font-weight:600;">${formatTrendVal(mk.Z, mk.trend, displayStyle, true)}</td>
                    <td style="font-weight:600; color:#2563EB;">${formatTrendVal(ss.slope, ss.trend || '', displayStyle, true)}</td>
                    <td style="font-weight:600;">${formatTrendVal(smk.Z, smk.trend || '', displayStyle, true)}</td>
                    <td style="font-weight:600; color:#7C3AED;">${formatTrendVal(sss.slope, sss.trend || '', displayStyle, true)}</td>
                    <td style="font-weight:600; color:#7C3AED;">${formatTrendVal(lr.tStatistic, lr.trend || '', displayStyle, true)}</td>
                `;
                tbody.appendChild(tr);
            });

            if (counter === 1) {
                tbody.innerHTML = '<tr><td colspan="7" style="padding:20px; color:#6B7280;">Tidak ada stasiun yang memenuhi filter kelengkapan data periode ini.</td></tr>';
            }

            updateTrenLegend();
        }

        // Update legend below table
        function updateTrenLegend() {
            const container = document.getElementById('trenLegendContainer');
            if (!container) return;
            const displayStyle = document.getElementById('trenDisplayStyle') ? document.getElementById('trenDisplayStyle').value : 'sign';
            if (displayStyle === 'sign') {
                container.innerHTML = `
                    <div style="display:flex; flex-wrap:wrap; gap:12px; padding:14px 18px; background:linear-gradient(135deg,#F8FAFC,#EFF6FF); border:1px solid #E2E8F0; border-radius:10px; font-size:0.84rem; color:#475569; align-items:center;">
                        <span style="font-weight:700; color:#334155; margin-right:4px;">Keterangan:</span>
                        <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;"><b style="color:#2563EB;">+</b> Naik</span>
                        <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;"><b style="color:#2563EB;">\u2212</b> Turun</span>
                        <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;"><b style="color:#B91C1C;">*</b> Signifikan (\u03B1 = 0,05)</span>
                        <span style="font-size:0.8rem; color:#64748B; margin-left:8px;">Z<sub>SMK</sub> &amp; Q<sub>med,gab</sub> hanya tersedia untuk data bulanan (semua bulan).</span>
                    </div>`;
            } else if (displayStyle === 'icon') {
                container.innerHTML = `
                    <div style="display:flex; flex-wrap:wrap; gap:10px; padding:14px 18px; background:linear-gradient(135deg,#F8FAFC,#EFF6FF); border:1px solid #E2E8F0; border-radius:10px; font-size:0.84rem; color:#475569;">
                        <span style="font-weight:700; color:#334155; margin-right:4px;">Keterangan:</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;"><span style="font-weight:bold;">\u25B2</span> Naik</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;"><span style="font-weight:bold;">\u25BC</span> Turun</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;"><span style="font-weight:bold;">\u2022</span> Stabil</span>
                        <span style="font-size:0.8rem; color:#64748B; margin-left:8px;">Z<sub>SMK</sub> &amp; Q<sub>med,gab</sub> hanya tersedia untuk data bulanan (semua bulan).</span>
                    </div>`;
            } else {
                container.innerHTML = `
                    <div style="display:flex; flex-wrap:wrap; gap:10px; padding:14px 18px; background:linear-gradient(135deg,#F8FAFC,#EFF6FF); border:1px solid #E2E8F0; border-radius:10px; font-size:0.84rem; color:#475569; align-items:center;">
                        <span style="font-weight:700; color:#334155; margin-right:4px;">Keterangan:</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;">\u25B2 Meningkat Signifikan</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;">\u25BC Menurun Signifikan</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:#fff; border-radius:6px; border:1px solid #E2E8F0;">\u2013 Tidak Signifikan</span>
                        <span style="font-size:0.8rem; color:#64748B; margin-left:8px;">Hanya ikon tanpa angka. Cocok untuk ekspor Excel.</span>
                    </div>`;
            }
        }

        // ====== PER STASIUN (Tabel Semua Tipe) ======
        function populatePerStasiunStationSelect() {
            const sel = document.getElementById('psStationSelect');
            if (!sel) return;
            sel.innerHTML = '';
            stationsData.forEach(st => {
                const opt = document.createElement('option');
                opt.value = st.id;
                opt.textContent = 'Pos ' + st.name;
                sel.appendChild(opt);
            });
            initMultiSelect('psStationSelect', 'psMsContainer', 'psMsTrigger', 'psMsTags', 'psMsDropdown', 'psMsOptions', 'psMsSearch', handlePerStasiunStationChange);
        }

        function getSelectedPerStasiunStations() {
            const sel = document.getElementById('psStationSelect');
            return Array.from(sel.selectedOptions).map(opt => opt.value);
        }

        function handlePerStasiunStationChange() {
            const selected = getSelectedPerStasiunStations();
            if (selected.length === 0) {
                document.getElementById('perStasiunTableBody').innerHTML = '<tr><td colspan="8" style="padding:30px; text-align:center; color:#9CA3AF; font-size:0.9rem;">Pilih minimal satu stasiun untuk melihat hasil olahan.</td></tr>';
                return;
            }
            if (activePerStasiunResults) {
                renderPerStasiunTable();
            } else {
                loadPerStasiunAnalysis();
            }
        }

        function initMultiSelect(nativeId, containerId, triggerId, tagsId, dropdownId, optionsId, searchId, onChange) {
            const sel = document.getElementById(nativeId);
            const container = document.getElementById(containerId);
            const trigger = document.getElementById(triggerId);
            const tagsEl = document.getElementById(tagsId);
            const dropdown = document.getElementById(dropdownId);
            const optionsEl = document.getElementById(optionsId);
            const search = document.getElementById(searchId);

            function renderTags() {
                const selected = Array.from(sel.selectedOptions);
                if (selected.length === 0) {
                    tagsEl.innerHTML = '<span class="ms-placeholder">Pilih stasiun...</span>';
                    return;
                }
                tagsEl.innerHTML = '';
                selected.forEach(opt => {
                    const tag = document.createElement('span');
                    tag.className = 'ms-tag';
                    tag.innerHTML = `${opt.textContent} <span class="ms-tag-remove" data-value="${opt.value}">&times;</span>`;
                    tag.querySelector('.ms-tag-remove').addEventListener('click', (e) => {
                        e.stopPropagation();
                        opt.selected = false;
                        renderTags();
                        renderOptions();
                        if (onChange) onChange();
                    });
                    tagsEl.appendChild(tag);
                });
            }

            function renderOptions(filter) {
                optionsEl.innerHTML = '';
                const f = (filter || '').toLowerCase();
                Array.from(sel.options).forEach(opt => {
                    if (f && !opt.textContent.toLowerCase().includes(f)) return;
                    const div = document.createElement('div');
                    div.className = 'ms-opt' + (opt.selected ? ' selected' : '');
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.checked = opt.selected;
                    cb.addEventListener('change', () => {
                        opt.selected = cb.checked;
                        renderTags();
                        if (onChange) onChange();
                    });
                    div.appendChild(cb);
                    div.appendChild(document.createTextNode(opt.textContent));
                    div.addEventListener('click', (e) => {
                        if (e.target !== cb) {
                            cb.checked = !cb.checked;
                            cb.dispatchEvent(new Event('change'));
                        }
                    });
                    optionsEl.appendChild(div);
                });
            }

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                container.classList.toggle('open');
                if (container.classList.contains('open')) {
                    search.value = '';
                    renderOptions('');
                    search.focus();
                }
            });

            search.addEventListener('input', () => renderOptions(search.value));

            renderTags();
            renderOptions('');

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) container.classList.remove('open');
            });
        }

        function populatePerStasiunYearSelects() {
            const fromSelect = document.getElementById('psYearFrom');
            const toSelect = document.getElementById('psYearTo');
            if (!fromSelect || !toSelect) return;
            fromSelect.innerHTML = '';
            toSelect.innerHTML = '';
            for (let y = minYearGlobal; y <= maxYearGlobal; y++) {
                const optF = document.createElement('option');
                optF.value = y; optF.innerText = y;
                if (y === minYearGlobal) optF.selected = true;
                fromSelect.appendChild(optF);
                const optT = document.createElement('option');
                optT.value = y; optT.innerText = y;
                if (y === maxYearGlobal) optT.selected = true;
                toSelect.appendChild(optT);
            }
        }

        async function loadPerStasiunAnalysis() {
            const tbody = document.getElementById('perStasiunTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="table-loading"><div class="spinner-rekap"></div>Menghitung trend untuk seluruh tipe data...</td></tr>';

            const yrFrom = parseInt(document.getElementById('psYearFrom').value) || minYearGlobal;
            const yrTo = parseInt(document.getElementById('psYearTo').value) || maxYearGlobal;

            try {
                const response = await fetch('php/analyze_all_types.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ yearFrom: yrFrom, yearTo: yrTo })
                });
                const resData = await response.json();
                if (!resData.success) throw new Error(resData.message);
                activePerStasiunResults = resData.results;
                renderPerStasiunTable();
            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="8" style="color:red; padding:20px;">Gagal memuat olahan per stasiun.</td></tr>';
            }
        }

        function renderPerStasiunTable() {
            const tbody = document.getElementById('perStasiunTableBody');
            tbody.innerHTML = '';
            const displayStyle = document.getElementById('psDisplayStyle') ? document.getElementById('psDisplayStyle').value : 'sign';
            const selectedIds = getSelectedPerStasiunStations();

            if (selectedIds.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="padding:30px; text-align:center; color:#9CA3AF; font-size:0.9rem;">Pilih minimal satu stasiun untuk melihat hasil olahan.</td></tr>';
                return;
            }

            let counter = 1;

            stationsData.forEach(st => {
                if (!selectedIds.includes(st.id)) return;
                const stResults = activePerStasiunResults[st.id];
                if (!stResults) return;

                perStasiunTypes.forEach(typeName => {
                    const typeRes = stResults[typeName];
                    if (!typeRes) return;

                    const mk = typeRes.mk || {};
                    const ss = typeRes.ss || {};
                    const lr = typeRes.lr || {};
                    const smk = typeRes.seasonal_mk || {};
                    const sss = typeRes.seasonal_ss || {};

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="color:#64748B; font-weight:600;">${counter++}</td>
                        <td style="font-weight:700; color:#1E293B;">Pos ${st.name}</td>
                        <td style="font-weight:600; color:#4B5563;">${typeName}</td>
                        <td style="font-weight:600;">${formatTrendVal(mk.Z, mk.trend || '', displayStyle, true)}</td>
                        <td style="font-weight:600; color:#2563EB;">${formatTrendVal(ss.slope, ss.trend || '', displayStyle, true)}</td>
                        <td style="font-weight:600;">${formatTrendVal(smk.Z, smk.trend || '', displayStyle, true)}</td>
                        <td style="font-weight:600; color:#7C3AED;">${formatTrendVal(sss.slope, sss.trend || '', displayStyle, true)}</td>
                        <td style="font-weight:600; color:#7C3AED;">${formatTrendVal(lr.tStatistic, lr.trend || '', displayStyle, true)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            });

            if (counter === 1) {
                tbody.innerHTML = '<tr><td colspan="8" style="padding:20px; color:#6B7280;">Tidak ada data tersedia.</td></tr>';
            }

            updatePerStasiunLegend();
        }

        function updatePerStasiunLegend() {
            const container = document.getElementById('psLegendContainer');
            if (!container) return;
            container.innerHTML = document.getElementById('trenLegendContainer').innerHTML;
        }

        function handlePerStasiunDisplayChange() {
            if (activePerStasiunResults) renderPerStasiunTable();
        }

        function applyPerStasiunFilters() {
            const selected = getSelectedPerStasiunStations();
            if (selected.length === 0) {
                document.getElementById('perStasiunTableBody').innerHTML = '<tr><td colspan="8" style="padding:30px; text-align:center; color:#9CA3AF; font-size:0.9rem;">Pilih minimal satu stasiun untuk melihat hasil olahan.</td></tr>';
                return;
            }
            const yrFrom = parseInt(document.getElementById('psYearFrom').value);
            const yrTo = parseInt(document.getElementById('psYearTo').value);
            if (yrFrom > yrTo) {
                alert("Tahun Mulai tidak boleh lebih besar dari Tahun Selesai!");
                return;
            }
            const prevYrFrom = activePerStasiunResults ? window._psYrFrom : null;
            const prevYrTo = activePerStasiunResults ? window._psYrTo : null;
            if (yrFrom !== prevYrFrom || yrTo !== prevYrTo) {
                activePerStasiunResults = null;
                window._psYrFrom = yrFrom;
                window._psYrTo = yrTo;
                loadPerStasiunAnalysis();
            } else if (activePerStasiunResults) {
                renderPerStasiunTable();
            }
        }

        // Handle display-only filter changes (completeness, display style) — no re-fetch needed
        function handleTrenDisplayChange() {
            if (activeTrenResults) renderTrendTable();
        }

        // Apply Tren Filters (period or aggregation changed — requires re-fetch)
        function applyTrenFilters() {
            const yrFrom = parseInt(document.getElementById('trenYearFrom').value);
            const yrTo = parseInt(document.getElementById('trenYearTo').value);

            if (yrFrom > yrTo) {
                alert("Tahun Mulai tidak boleh lebih besar dari Tahun Selesai!");
                return;
            }

            loadTrendAnalysis();
        }

        // Search Handler for Tab 1 Detail Table
        function handleSearch() {
            handleCompletenessFilter();
        }

        // Export Table to Excel (.xlsx) using SheetJS
        function exportToExcel(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const showSig = (tableId === 'tren-table' || tableId === 'per-stasiun-table') && document.getElementById('trenShowSig') && document.getElementById('trenShowSig').checked;

            const clone = table.cloneNode(true);
            const rows = clone.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].cells;
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.tagName.toLowerCase() === 'th') continue;

                    let text = (cell.innerText || cell.textContent).trim();

                    // Strip icon symbols only (keep \u2212 and comma)
                    text = text.replace(/[\u25B2\u25BC\u2022\u2013+]/g, '').trim();
                    if (!showSig) text = text.replace(/\*/g, '');

                    if (text !== '' && !isNaN(Number(text))) {
                        cell.innerText = Number(text);
                        cell.setAttribute('data-t', 'n');
                        cell.setAttribute('t', 'n');
                    } else if (text !== '') {
                        // Force string type so SheetJS preserves comma and \u2212 as-is
                        cell.innerText = text;
                        cell.setAttribute('data-t', 's');
                        cell.setAttribute('t', 's');
                    }
                }
            }

            const wb = XLSX.utils.table_to_book(clone, { raw: false });
            XLSX.writeFile(wb, filename + '.xlsx');
        }
    </script>
</body>

</html>