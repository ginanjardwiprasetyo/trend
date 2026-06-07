<?php
/**
 * TrendHidro - Halaman Detail Stasiun
 * Menampilkan grafik hujan, parameter statistik, ketersediaan data, dan peta lokasi
 */
$stationId = isset($_GET['id']) ? htmlspecialchars(trim($_GET['id'])) : '';

// --- Cegah Akses Langsung ---
if (empty($stationId)) {
    header("Location: error?error=forbidden");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Detail Stasiun <?php echo $stationId; ?> — TrendHidro</title>
    <meta name="description"
        content="Analisis detail <i>trend</i> hidrologi, grafik harian, parameter statistik, dan ketersediaan data untuk stasiun hidrologi ID: <?php echo $stationId; ?> di sistem TrendHidro.">
    <meta name="keywords"
        content="stasiun hidrologi, <i>trend</i> hidrologi, <?php echo $stationId; ?>, data hidrologi detail, statistik harian, trendhidro">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
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

        .gh-cell.available {
            background: #3B82F6;
        }

        .gh-cell.missing {
            background: #EF4444;
        }

        .gh-cell.empty {
            background: transparent;
            pointer-events: none;
        }

        /* Tak valid, ex 31 Feb */
        .mini-map {
            z-index: 0 !important;
            isolation: isolate;
        }

        /* Hide scrollbar */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Card Loader */
        .detail-card {
            position: relative;
        }

        .card-loader {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            border-radius: 12px;
            gap: 12px;
        }

        .card-loader.active {
            opacity: 1;
            visibility: visible;
        }

        .card-loader .spinner {
            width: 32px;
            height: 32px;
        }

        .loader-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-primary);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Sticky toolbar below navbar */
        .toolbar-sticky {
            position: sticky;
            top: 56px;
            z-index: 49;
            margin: -20px -24px 20px;
            padding: 16px 24px;
            border-radius: 0;
            border-left: none;
            border-right: none;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

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
            bottom: calc(100% + 8px);
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
        .avail-year-grid.show {
            display: flex;
        }
    </style>
</head>

<body style="overflow-y: auto; height: auto;">

    <!-- NAVBAR -->
    <nav class="navbar" id="navbar">
        <a href="./" class="navbar-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 7v10" />
                <path d="M9 10l3-3 3 3" />
                <path d="M9 14l3 3 3-3" />
            </svg>
            <span>TrendHidro</span>
        </a>
        <ul class="navbar-nav">
            <li><a href="./" id="nav-beranda">Beranda</a></li>
            <li class="nav-dropdown">
                <button class="nav-drop-btn" id="nav-fitur">
                    Fitur
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                        style="margin-left:4px;">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </button>
                <div class="nav-drop-content">
                    <a href="peta">Peta Interaktif</a>
                    <a href="olah-data">Olah Data Anda</a>
                </div>
            </li>
            <li><a href="data" id="nav-data">Ketersediaan Data</a></li>
            <li><a href="dok" id="nav-docs">Dokumentasi</a></li>
            <li><a href="tentang" id="nav-about">Tentang</a></li>
        </ul>
    </nav>

    <!-- KONTEN -->
    <div class="detail-container">
        <div class="detail-header">
            <a href="./" class="back-btn" title="Kembali ke Peta">←</a>
            <h1 id="stationName">Memuat...</h1>
        </div>

        <div class="detail-grid">

            <!-- Toolbar Grafik -->
            <div class="detail-card toolbar-sticky"
                style="grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap; align-items:stretch; overflow:visible !important;">
                <label style="font-size:0.9rem; font-weight:600; color:#4B5563; min-width:max-content; flex-shrink:0; display:flex; align-items:center;">Pilih
                    Data:</label>
                <select id="dtType" class="form-select" style="min-width:135px; flex-shrink:0;" onchange="toggleMonthAgg()">
                    <option value="bulanan" selected>Bulanan</option>
                    <option value="tahunan">Tahunan</option>
                    <option value="musiman">Musiman</option>
                </select>
                <select id="dtMonth" class="form-select" style="min-width:165px; flex-shrink:0;">
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
                <!-- Agregasi dihilangkan sesuai request -->
                <input type="hidden" id="dtAgg" value="kumulatif">
<label style="font-size:0.9rem; font-weight:600; color:#4B5563; min-width:max-content; flex-shrink:0; display:flex; align-items:center; margin-left:auto;">Rentang
                    Tahun:</label>
                <div class="year-picker-wrapper" style="width: auto; min-width: 230px; flex-shrink:0; height:42px;">
                    <div class="year-picker" id="pickerFrom">
                        <button type="button" class="year-display" id="displayFrom">1980</button>
                        <div class="year-grid hidden" id="gridFrom"></div>
                    </div>
                    <div class="year-sep">—</div>
                    <div class="year-picker" id="pickerTo">
                        <button type="button" class="year-display" id="displayTo">2025</button>
                        <div class="year-grid hidden" id="gridTo"></div>
                    </div>
                    <input type="hidden" id="yFrom" value="1980">
                    <input type="hidden" id="yTo" value="2025">
                </div>

                <button class="btn btn-primary" onclick="updateGraphData()" id="btnUpdateData"
                    style="white-space: nowrap; flex-shrink:0; height:42px; padding:0 22px;">Olah Data</button>
                <div id="graphLoader" class="spinner"
                    style="display:none; width:22px; height:22px; border-width:2.5px; margin-left:4px; flex-shrink:0; aspect-ratio:1/1; align-self:center;">
                </div>
            </div>

            <!-- Grafik Hujan -->
            <div class="detail-card" style="grid-column: 1 / -1;">
                <div class="card-loader active" id="graphLoaderOverlay">
                    <div class="spinner"></div>
                    <span class="loader-label">Memuat Grafik...</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <h3 style="margin: 0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18" />
                            <path d="M7 16l4-8 4 4 4-10" />
                        </svg>
                        Grafik Curah Hujan
                    </h3>
                    <div style="display:flex; gap:12px; font-size:0.8rem; font-weight:600; color:#4B5563;">
                        <span style="display:flex; align-items:center; gap:4px;">
                            <span style="width:16px; height:3px; background:#3B82F6; border-radius:2px;"></span> Curah Hujan
                        </span>
                        <span style="display:flex; align-items:center; gap:4px;">
                            <span style="width:16px; height:3px; background:#DC2626; border-radius:2px;"></span> Garis Regresi
                        </span>
                    </div>
                </div>
                <div class="chart-container" style="height:320px;">
                    <canvas id="rainfallChart"></canvas>
                </div>
            </div>

            <!-- Rekap Perhitungan Tren + Ketersediaan Data Periode -->
            <div style="grid-column: 1 / -1; display: flex; gap: 20px; flex-wrap: wrap;">
                <!-- Rekap Tren (kiri, 2/3) -->
                <div class="detail-card" style="flex: 2; min-width: 400px;">
                    <div class="card-loader active" id="trendLoaderOverlay">
                        <div class="spinner"></div>
                        <span class="loader-label">Menghitung <i>Trend</i>...</span>
                    </div>
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 6l-9.5 9.5-5-5L1 18" />
                        </svg>
                        Rekapitulasi <i>Trend</i> Data Curah Hujan
                    </h3>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div style="padding:14px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                            <strong style="display:block; margin-bottom:8px; color:#2563EB;">Mann Kendall</strong>
                            <div id="mkResult" style="font-size:0.9rem; color:#4B5563;">Menunggu data...</div>
                        </div>
                        <div style="padding:14px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                            <strong style="display:block; margin-bottom:8px; color:#2563EB;">Sen's Slope</strong>
                            <div id="ssResult" style="font-size:0.9rem; color:#4B5563;">Menunggu data...</div>
                        </div>
                        <div style="padding:14px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                            <strong style="display:block; margin-bottom:8px; color:#2563EB;">Regresi Linear</strong>
                            <div id="lrResult" style="font-size:0.9rem; color:#4B5563;">Menunggu data...</div>
                        </div>
                    </div>
                    <div style="font-size: 0.8rem; color: #6B7280; margin-top: 12px;">*Warna gelap menunjukkan signifikansi tingkat kepercayaan 95% (α = 0.05)</div>
                </div>

                <!-- Ketersediaan Data Periode (kanan, 1/3) -->
                <div class="detail-card" style="flex: 1; min-width: 280px; display: flex; flex-direction: column;">
                    <div class="card-loader active" id="availPeriodLoaderOverlay">
                        <div class="spinner"></div>
                        <span class="loader-label">Menghitung...</span>
                    </div>
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        Ketersediaan Data Periode Terpilih
                    </h3>

                    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; gap:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                            <span id="availPctValue"
                                style="font-size:2.5rem; font-weight:800; color:#1F2937; line-height:1;">—</span>
                            <div id="availPctBadge"
                                style="padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:700; background:#F3F4F6; color:#6B7280;">
                                —
                            </div>
                        </div>
                        <div style="height:6px; background:#F1F5F9; border-radius:100px; overflow:hidden; width:100%;">
                            <div id="availBarFill"
                                style="height:100%; width:0%; background:#3B82F6; transition: width 1s ease, background 0.4s;">
                            </div>
                        </div>
                        <div id="availPctLabel" style="font-size:0.85rem; color:#6B7280; font-weight:500;">
                            Menunggu data...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peta Lokasi -->
            <div class="detail-card">
                <div class="card-loader active" id="mapLoaderOverlay">
                    <div class="spinner"></div>
                    <span class="loader-label">Menyiapkan Peta...</span>
                </div>
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    Lokasi Stasiun
                </h3>
                <div id="miniMap" class="mini-map"></div>
                <div class="coord-info" id="coordInfo">-</div>
                <div style="font-size:0.85rem; color:#4B5563; margin-top:8px;"><strong>Elevasi:</strong> <span
                        id="dtElevation">Memuat...</span></div>
            </div>

            <!-- Parameter Statistik -->
            <div class="detail-card">
                <div class="card-loader active" id="statLoaderOverlay">
                    <div class="spinner"></div>
                    <span class="loader-label">Menghitung Statistik...</span>
                </div>
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <path d="M3 9h18M9 3v18" />
                    </svg>
                    Parameter Statistik
                </h3>
                <table class="stat-table" id="statTable">
                    <tbody>
                        <tr>
                            <td>Rerata</td>
                            <td id="statMean">-</td>
                        </tr>
                        <tr>
                            <td>Maksimum</td>
                            <td id="statMax">-</td>
                        </tr>
                        <tr>
                            <td>Minimum</td>
                            <td id="statMin">-</td>
                        </tr>
                        <tr>
                            <td>Simpangan Baku</td>
                            <td id="statStd">-</td>
                        </tr>
                        <tr>
                            <td>Koefisien Variansi</td>
                            <td id="statCv">-</td>
                        </tr>
                        <tr>
                            <td>Periode Data</td>
                            <td id="statRange">-</td>
                        </tr>
                        <tr>
                            <td>Jumlah Data</td>
                            <td id="statLength">-</td>
                        </tr>
                        <tr>
                            <td>Batas Bawah <i>Outlier</i></td>
                            <td id="statLb">-</td>
                        </tr>
                        <tr>
                            <td>Batas Atas <i>Outlier</i></td>
                            <td id="statUb">-</td>
                        </tr>
                        <tr>
                            <td>Terdapat <i>Outlier</i>?</td>
                            <td id="statOutlier">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Ketersediaan Data Harian 1 Tahun Terakhir -->
            <div class="detail-card" style="grid-column: 1 / -1;">
                <div class="card-loader active" id="availLoaderOverlay">
                    <div class="spinner"></div>
                    <span class="loader-label">Menyusun Data Harian...</span>
                </div>
                <div
                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap;">
                    <h3 style="margin:0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        Ketersediaan Data Curah Hujan Harian
                    </h3>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <button class="btn btn-secondary" id="btnTogglePie" style="padding:6px 14px; font-size:0.82rem; font-weight:600; border-radius:8px; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s ease; box-shadow:0 1px 2px rgba(0,0,0,0.04);" onclick="toggleDailyPieChart()">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path d="M12 3v9l4 2"/>
                            </svg>
                            <span id="btnTogglePieText">Tampilkan Ringkasan (Pie Chart)</span>
                        </button>
                        <div id="dailyYearNavControls" style="display:flex; gap:8px; align-items:center;">
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:0.8rem;"
                                onclick="changeAvailabilityYear(-1)">← Mundur</button>
                            <div class="avail-year-picker-wrap">
                                <button type="button" class="avail-year-btn" id="availYearDisplay" onclick="toggleAvailYearPicker(event)">-</button>
                                <div class="avail-year-grid" id="availYearGrid"></div>
                            </div>
                            <button class="btn btn-secondary" style="padding:4px 8px; font-size:0.8rem;"
                                onclick="changeAvailabilityYear(1)">Maju →</button>
                        </div>
                    </div>
                </div>

                <div id="dailyGridWrapper">
                    <div id="dailyGridContent">
                        <div style="display:flex; align-items: flex-start; gap: 8px; justify-content: center;">
                            <!-- Nama Bulan -->
                            <div
                                style="display:grid; grid-template-rows: repeat(12, 35px); gap: 2px; font-size: 0.75rem; color: #6B7280; text-align: right; font-weight: 600; padding-top: 15px;">
                                <div>Januari</div>
                                <div>Februari</div>
                                <div>Maret</div>
                                <div>April</div>
                                <div>Mei</div>
                                <div>Juni</div>
                                <div>Juli</div>
                                <div>Agustus</div>
                                <div>September</div>
                                <div>Oktober</div>
                                <div>November</div>
                                <div>Desember</div>
                            </div>

                            <div style="flex:none; overflow-x: auto;" class="no-scrollbar">
                                <div id="availabilityGrid" class="github-grid" style="width: max-content;"></div>
                                <!-- Angka Tanggal (1-31) -->
                                <div
                                    style="display:grid; grid-template-columns: repeat(31, 35px); gap: 2px; margin-top: 8px; font-size: 0.7rem; color:#9CA3AF; text-align: center; font-weight: 500;">
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

                        <div id="dailyGridFooter"
                            style="display:flex; gap:16px; margin-top:20px; border-top:1px solid #E5E7EB; padding-top:12px; font-size:0.85rem; flex-wrap:wrap;">
                            <div style="flex:1; min-width:300px;">
                                <div class="avail-summary" id="availSummary">-</div>
                            </div>
                            <div style="display:flex; gap:16px; align-items:center; font-size:0.8rem; color:#4B5563;">
                                <span><span
                                        style="display:inline-block;width:12px;height:12px;background:#3B82F6;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Tersedia</span>
                                <span><span
                                        style="display:inline-block;width:12px;height:12px;background:#EF4444;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Hilang</span>
                            </div>
                        </div>
                    </div>

                    <div id="dailyPieWrapper" style="display:none; align-items:center; justify-content:center; flex-direction:column; min-height: 300px; width: 100%; padding: 24px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; box-sizing:border-box;">
                        <div style="width: min(320px, 100%); aspect-ratio: 1/1; margin-bottom: 20px; position: relative;">
                            <canvas id="dailyPieChart"></canvas>
                        </div>
                        <div id="dailyPieSummary" style="font-size: 0.95rem; color: #4B5563; text-align: center; max-width: 90%; line-height:1.6;"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const STATION_ID = '<?php echo $stationId; ?>';
        let stationMeta = null;
        let chartInstance = null;
        let pieChartInstance = null;
        let currentAvailYear = new Date().getFullYear();
        let minAvailYear = 1980;
        let maxAvailYear = currentAvailYear;


        const fM = (val) => val === undefined || val === null || val === '—' ? '—' : String(val).replace('-', '−');

        function setCardLoading(loaderId, isLoading) {
            const loader = document.getElementById(loaderId);
            if (loader) {
                if (isLoading) loader.classList.add('active');
                else loader.classList.remove('active');
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            // Show initial loaders
            setCardLoading('mapLoaderOverlay', true);
            setCardLoading('graphLoaderOverlay', true);
            setCardLoading('trendLoaderOverlay', true);
            setCardLoading('statLoaderOverlay', true);
            setCardLoading('availLoaderOverlay', true);
            setCardLoading('availPeriodLoaderOverlay', true);

            try {
                // Parse params if redirected from lightbox
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('dtType')) document.getElementById('dtType').value = urlParams.get('dtType');
                if (urlParams.has('agg')) document.getElementById('dtAgg').value = urlParams.get('agg');
                if (urlParams.has('mo')) document.getElementById('dtMonth').value = urlParams.get('mo');
                toggleMonthAgg();

                // Bersihkan URL dari parameter tambahan agar alamat web tetap clean
                if (urlParams.toString()) {
                    window.history.replaceState({}, document.title, window.location.pathname + '?id=' + STATION_ID);
                }

                const apiRes = await fetch("php/get_stations.php");
                const features = await apiRes.json();

                const stFeature = features.find(d => d.properties.id === STATION_ID);
                if (!stFeature) {
                    document.getElementById('stationName').textContent = 'Stasiun Tidak Ditemukan';
                    // Hide all loaders if station not found
                    document.querySelectorAll('.card-loader').forEach(el => el.classList.remove('active'));
                    return;
                }
                stationMeta = stFeature.properties;

                document.getElementById('stationName').textContent = "Pos " + (stationMeta.name || '');
                document.title = `Pos ${stationMeta.name || ''} — TrendHidro`;

                minAvailYear = stationMeta.yearStart || 1980;
                maxAvailYear = stationMeta.yearEnd || new Date().getFullYear();
                currentAvailYear = maxAvailYear;

                window.stationMinYear = minAvailYear;
                window.stationMaxYear = maxAvailYear;

                // Set From / To default from URL or Meta (Clamped to station availability)
                let reqYF = parseInt(urlParams.get('yF'));
                let reqYT = parseInt(urlParams.get('yT'));

                if (isNaN(reqYF) || reqYF < minAvailYear) reqYF = minAvailYear;
                if (reqYF > maxAvailYear) reqYF = maxAvailYear;
                if (isNaN(reqYT) || reqYT > maxAvailYear) reqYT = maxAvailYear;
                if (reqYT < minAvailYear) reqYT = minAvailYear;
                if (reqYF > reqYT) reqYF = reqYT;

                const yfInput = document.getElementById('yFrom');
                const ytInput = document.getElementById('yTo');
                const yfDisp = document.getElementById('displayFrom');
                const ytDisp = document.getElementById('displayTo');

                yfInput.value = reqYF;
                ytInput.value = reqYT;
                yfDisp.textContent = reqYF;
                ytDisp.textContent = reqYT;

                window.viewedDecadeFrom = Math.floor(reqYF / 10) * 10;
                window.viewedDecadeTo = Math.floor(reqYT / 10) * 10;

                // Map
                const miniMap = L.map('miniMap', {
                    center: [stationMeta.lat, stationMeta.lon],
                    zoom: 13, zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false
                });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
                L.marker([stationMeta.lat, stationMeta.lon]).addTo(miniMap);
                setCardLoading('mapLoaderOverlay', false);

                const locNames = (stationMeta.location || '—').toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                document.getElementById('coordInfo').innerHTML =
                    `<strong>Lat:</strong> ${fM(stationMeta.lat.toFixed(4))}° &nbsp; <strong>Lon:</strong> ${fM(stationMeta.lon.toFixed(4))}°<br>` +
                    `<span style="color:#9CA3AF;">${locNames}</span>`;

                // Elevation
                fetch(`https://api.maptiler.com/elevation/${stationMeta.lon},${stationMeta.lat}.json?key=gynZH4DjPEOBUg0mYiwd`)
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data) && data.length > 0 && data[0].length >= 3) {
                            document.getElementById('dtElevation').textContent = `${data[0][2]} mdpl`;
                        } else {
                            document.getElementById('dtElevation').textContent = "Tidak tersedia";
                        }
                    }).catch(() => document.getElementById('dtElevation').textContent = "Gagal memuat");

                updateGraphData();
                loadAvailabilityYear();

            } catch (err) {
                console.error(err);
            }
        });

        function toggleMonthAgg() {
            const dtType = document.getElementById('dtType').value;
            const mo = document.getElementById('dtMonth');

            // PENTING: Jangan ubah mo.style.display di sini.
            // setupCustomSelects() sudah menyembunyikan native select (display:none)
            // dan menggantinya dengan custom select wrapper. Visibilitas wrapper
            // diatur oleh listener 'optionsChanged' di bawah.
            mo.style.display = 'none';

            if (dtType === 'musiman') {
                // Update ke seasonal blocks
                mo.innerHTML = `
                    <option value="1,2,3">Jan–Feb–Mar</option>
                    <option value="4,5,6">Apr–Mei–Jun</option>
                    <option value="7,8,9">Jul–Agus–Sep</option>
                    <option value="10,11,12">Okt–Nov–Des</option>
                `;
            } else {
                // bulanan (default & tahunan aman-aman saja, fallback ke bulanan)
                // Reset ke bulanan
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
            // Trigger update on custom select if it exists
            const evt = new Event('optionsChanged');
            mo.dispatchEvent(evt);
        }

        async function updateGraphData() {
            if (!stationMeta) return;

            document.getElementById('graphLoader').style.display = 'block';
            document.getElementById('btnUpdateData').disabled = true;

            const dtType = document.getElementById('dtType').value;
            const agg = document.getElementById('dtAgg').value;
            const mo = document.getElementById('dtMonth').value;
            const yFrom = parseInt(document.getElementById('yFrom').value);
            const yTo = parseInt(document.getElementById('yTo').value);

            // Show loaders
            setCardLoading('graphLoaderOverlay', true);
            setCardLoading('trendLoaderOverlay', true);
            setCardLoading('statLoaderOverlay', true);
            setCardLoading('availPeriodLoaderOverlay', true);

            try {
                let mo = document.getElementById('dtMonth').value;
                if (dtType === 'tahunan') mo = 'all';

                const payload = {
                    pos_id: STATION_ID,
                    dataType: dtType,
                    aggregation: agg,
                    yearFrom: yFrom,
                    yearTo: yTo,
                    month: mo
                };

                // Pastikan URL tetap clean setiap kali data diupdate/diganti
                window.history.replaceState({}, document.title, window.location.pathname + '?id=' + STATION_ID);

                const res = await fetch('php/get_timeseries.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const tsData = await res.json();
                if (!Array.isArray(tsData)) {
                    throw new Error(tsData.message || "Gagal memuat data dari server");
                }



                renderGraphAndStats(tsData, dtType, agg);
                computeTrendsBackground(tsData);
                updateAvailabilityPeriod(tsData, dtType, yFrom, yTo, mo);

            } catch (e) {
                console.error("Gagal memperbarui data:", e);
                // Clear all loaders on error
                setCardLoading('graphLoaderOverlay', false);
                setCardLoading('trendLoaderOverlay', false);
                setCardLoading('statLoaderOverlay', false);
                setCardLoading('availPeriodLoaderOverlay', false);

                // Show error message in results
                const errorMsg = `<span style="color:#DC2626;">Error: ${e.message}</span>`;
                document.getElementById('mkResult').innerHTML = errorMsg;
                document.getElementById('ssResult').innerHTML = errorMsg;
                document.getElementById('lrResult').innerHTML = errorMsg;
            }

            document.getElementById('graphLoader').style.display = 'none';
            document.getElementById('btnUpdateData').disabled = false;
        }

        function renderGraphAndStats(tsData, dtType, agg) {
            setCardLoading('graphLoaderOverlay', false);
            setCardLoading('statLoaderOverlay', false);
            const values = tsData.map(d => d.value);

            // Extract year for each data point (used for X-axis display)
            const yearLabels = tsData.map(d => Math.floor(d.year).toString());

            // Build tooltip labels (more descriptive for hover)
            const tooltipLabels = tsData.map(d => {
                const y = Math.floor(d.year);
                if (dtType === 'tahunan') return `Tahun ${y}`;
                if (dtType === 'bulanan') {
                    const frac = d.year - y;
                    const m = Math.round(frac * 12) + 1;
                    const b = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
                    return `${b[m - 1]} ${y}`;
                }
                if (dtType === 'harian') {
                    const doy = Math.round((d.year - y) * 365.0) + 1;
                    return `Hari ke-${doy}, Tahun ${y}`;
                }
                return `Tahun ${y}`;
            });

            const ctx = document.getElementById('rainfallChart').getContext('2d');
            if (chartInstance) chartInstance.destroy();

            // Use Line chart for daily data (too much for bars), Bar for monthly/annual
            const chartType = (dtType === 'harian') ? 'line' : 'bar';

            chartInstance = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: yearLabels,
                    datasets: [{
                        label: 'Curah Hujan (mm)',
                        data: values,
                        backgroundColor: (chartType === 'line') ? 'rgba(37, 99, 235, 0.15)' : 'rgba(37, 99, 235, 0.7)',
                        borderColor: '#2563EB',
                        borderWidth: (chartType === 'line') ? 1.5 : 1,
                        borderRadius: (chartType === 'line') ? 0 : 2,
                        fill: (chartType === 'line'),
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        tension: 0.1,
                        spanGaps: true // Optimization: ignore nulls
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Performance: Disable all animations
                    normalized: true, // Performance: Assume data is already sorted
                    spanGaps: true,    // Performance: Don't look for gaps
                    interaction: {
                        mode: 'index',
                        intersect: false,
                        axis: 'x' // Performance: Only search along x axis
                    },
                    hover: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            position: 'nearest',
                            external: null,
                            callbacks: {
                                title: (context) => {
                                    const idx = context[0].dataIndex;
                                    return tooltipLabels[idx] || context[0].label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Tahun', font: { family: 'Inter', weight: 'bold' } },
                            ticks: {
                                font: { family: 'Inter', size: 10 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 15,
                                callback: function (value, index, ticks) {
                                    const label = this.getLabelForValue(value);
                                    // Always show first and last tick (start & end year)
                                    if (index === 0 || index === ticks.length - 1) return label;
                                    // For intermediate ticks, only show if year changes from previous shown label
                                    if (index > 0) {
                                        const prevLabel = this.getLabelForValue(ticks[index - 1].value);
                                        if (label === prevLabel) return null; // skip duplicate years
                                    }
                                    return label;
                                }
                            },
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Curah Hujan (mm)', font: { family: 'Inter', weight: 'bold' } },
                            ticks: { font: { family: 'Inter', size: 11 } }
                        }
                    }
                }
            });



            // Statistics (Math)
            if (values.length === 0) {
                document.getElementById('statMean').textContent = "—";
                document.getElementById('statMax').textContent = "—";
                document.getElementById('statMin').textContent = "—";
                document.getElementById('statStd').textContent = "—";
                document.getElementById('statCv').textContent = "—";
                document.getElementById('statLb').textContent = "—";
                document.getElementById('statUb').textContent = "—";
                document.getElementById('statOutlier').textContent = "—";
                document.getElementById('statRange').textContent = "—";
                document.getElementById('statLength').textContent = "—";
                return;
            }

            const mean = Math.round(values.reduce((a, b) => a + b, 0) / values.length);
            const max = Math.max(...values);
            const min = Math.min(...values);
            const std = values.length > 1 ? Math.round(Math.sqrt(values.reduce((s, v) => s + Math.pow(v - mean, 2), 0) / (values.length - 1))) : 0;
            const cv = mean > 0 ? (std / mean) : 0;

            document.getElementById('statMean').textContent = fM(mean.toFixed(0)) + ' mm';
            document.getElementById('statMax').textContent = fM(max.toFixed(0)) + ' mm';
            document.getElementById('statMin').textContent = fM(min.toFixed(0)) + ' mm';
            document.getElementById('statStd').textContent = fM(std.toFixed(0)) + ' mm';
            document.getElementById('statCv').textContent = fM(cv.toFixed(2));

            const startYearData = Math.floor(tsData[0].year);
            const endYearData = Math.floor(tsData[tsData.length - 1].year);
            document.getElementById('statRange').textContent = `${startYearData}—${endYearData}`;
            document.getElementById('statLength').textContent = `${values.length} unit`;

            // Outlier (Q1 - 1.5 IQR, Q3 + 1.5 IQR) - Linear Interpolation to match olah-data.js
            const sorted = [...values].sort((a, b) => a - b);
            const n = sorted.length;
            const calcQuartile = (p) => {
                const pos = (n - 1) * p;
                const base = Math.floor(pos);
                const rest = pos - base;
                let val = sorted[base];
                if (sorted[base + 1] !== undefined) {
                    val = sorted[base] + rest * (sorted[base + 1] - sorted[base]);
                }
                return Math.round(val);
            };

            const q1 = calcQuartile(0.25);
            const q3 = calcQuartile(0.75);
            const iqr = q3 - q1;
            const lb = Math.max(0, q1 - 1.5 * iqr);
            const ub = q3 + 1.5 * iqr;

            const hasOutlier = values.some(v => v < lb || v > ub);
            document.getElementById('statLb').textContent = fM(lb.toFixed(0)) + ' mm';
            document.getElementById('statUb').textContent = fM(ub.toFixed(0)) + ' mm';
            document.getElementById('statOutlier').innerHTML = hasOutlier
                ? `<span style="color:#DC2626;font-weight:600;">Ya</span>`
                : `Tidak`;
        }

        async function computeTrendsBackground(dataArray) {
            if (dataArray.length < 3) {
                setCardLoading('trendLoaderOverlay', false);
                document.getElementById('mkResult').innerHTML = "Data tidak cukup (<3)";
                document.getElementById('ssResult').innerHTML = "Data tidak cukup (<3)";
                document.getElementById('lrResult').innerHTML = "Data tidak cukup (<3)";
                return;
            }

            // Reset results to loading state
            document.getElementById('mkResult').innerHTML = "Menghitung...";
            document.getElementById('ssResult').innerHTML = "Menghitung...";
            document.getElementById('lrResult').innerHTML = "Menghitung...";

            try {
                // Execute all calculations in parallel to prevent blocking
                const results = await Promise.allSettled([
                    fetch('php/mann_kendall.php', { method: 'POST', body: JSON.stringify({ data: dataArray }) }).then(r => r.json()),
                    fetch('php/sens_slope.php', { method: 'POST', body: JSON.stringify({ data: dataArray }) }).then(r => r.json()),
                    fetch('php/regresi_linear.php', { method: 'POST', body: JSON.stringify({ data: dataArray }) }).then(r => r.json())
                ]);

                // 1. Process Mann-Kendall
                const mkRes = results[0];
                if (mkRes.status === 'fulfilled' && !mkRes.value.error) {
                    const mk = mkRes.value;
                    let mkColor = '#6B7280';
                    let tTrendMK = mk.trend === 'Tidak Ada Tren' ? 'tidak ada' : mk.trend;
                    if (tTrendMK.includes('Meningkat') && tTrendMK.includes('Signifikan)') && !tTrendMK.includes('Tidak')) mkColor = '#0B6E2F';
                    else if (tTrendMK.includes('Meningkat')) mkColor = '#16A34A';
                    else if (tTrendMK.includes('Menurun') && tTrendMK.includes('Signifikan)') && !tTrendMK.includes('Tidak')) mkColor = '#991B1B';
                    else if (tTrendMK.includes('Menurun')) mkColor = '#DC2626';
                    
                    const zKritis = 1.96;
                    document.getElementById('mkResult').innerHTML = `<i>Trend</i>: <strong style="color:${mkColor}">${tTrendMK}</strong><br>Z Uji: ${fM(mk.Z)}<br>Z Kritis: ±${zKritis}`;
                } else {
                    document.getElementById('mkResult').innerHTML = `Gagal menghitung (Data terlalu besar atau timeout)`;
                }

                // 2. Process Sen's Slope
                const ssRes = results[1];
                if (ssRes.status === 'fulfilled' && !ssRes.value.error) {
                    const ss = ssRes.value;
                    let ssColor = '#6B7280';
                    let tTrendSS = ss.trend === 'Tidak Ada Tren' ? 'tidak ada' : ss.trend;
                    if (tTrendSS.includes('Meningkat') && tTrendSS.includes('Signifikan)') && !tTrendSS.includes('Tidak')) ssColor = '#0B6E2F';
                    else if (tTrendSS.includes('Meningkat')) ssColor = '#16A34A';
                    else if (tTrendSS.includes('Menurun') && tTrendSS.includes('Signifikan)') && !tTrendSS.includes('Tidak')) ssColor = '#991B1B';
                    else if (tTrendSS.includes('Menurun')) ssColor = '#DC2626';
                    
                    let boundsHtml = '';
                    if (ss.Qmin !== undefined && ss.Qmax !== undefined) {
                        boundsHtml = `<br>Q<sub>min</sub>: ${fM(ss.Qmin)}<br>Q<sub>max</sub>: ${fM(ss.Qmax)}`;
                    }
                    
                    document.getElementById('ssResult').innerHTML = `<i>Trend</i>: <strong style="color:${ssColor}">${tTrendSS}</strong><br>Q<sub>med</sub>: ${fM(ss.slope)}${boundsHtml}`;
                } else {
                    document.getElementById('ssResult').innerHTML = `Gagal menghitung (Data terlalu besar atau timeout)`;
                }

                // 3. Process Linear Regression
                const lrRes = results[2];
                if (lrRes.status === 'fulfilled' && !lrRes.value.error) {
                    const lr = lrRes.value;
                    let lrColor = '#6B7280';
                    let tTrendLR = lr.trend === 'Tidak Ada Tren' ? 'tidak ada' : lr.trend;
                    if (tTrendLR.includes('Meningkat') && tTrendLR.includes('Signifikan)') && !tTrendLR.includes('Tidak')) lrColor = '#0B6E2F';
                    else if (tTrendLR.includes('Meningkat')) lrColor = '#16A34A';
                    else if (tTrendLR.includes('Menurun') && tTrendLR.includes('Signifikan)') && !tTrendLR.includes('Tidak')) lrColor = '#991B1B';
                    else if (tTrendLR.includes('Menurun')) lrColor = '#DC2626';
                    
                    const tUji = lr.tStatistic !== undefined ? fM(lr.tStatistic) : '—';
                    const tKrit = lr.tCritical !== undefined ? `±${fM(lr.tCritical)}` : '—';
                    document.getElementById('lrResult').innerHTML = `<i>Trend</i>: <strong style="color:${lrColor}">${tTrendLR}</strong><br>t Uji: ${tUji}<br>t Kritis: ${tKrit}`;

                    // Update main chart with trend line if available
                    if (chartInstance && lr.slope !== undefined && lr.intercept !== undefined) {
                        let trendDataPts = dataArray.map((d) => {
                            return lr.intercept + (lr.slope * d.year);
                        });

                        // Remove existing trend line if any
                        chartInstance.data.datasets = chartInstance.data.datasets.filter(ds => ds.label !== 'Garis Regresi');

                        chartInstance.data.datasets.push({
                            type: 'line',
                            label: 'Garis Regresi',
                            data: trendDataPts,
                            borderColor: '#DC2626',
                            borderWidth: 2,
                            tension: 0,
                            pointRadius: 0,
                            fill: false,
                            order: -1 // Ensure it's on top
                        });
                        chartInstance.update();
                    }
                } else {
                    document.getElementById('lrResult').innerHTML = `Gagal menghitung`;
                }

            } catch (e) {
                console.error("Gagal Kalkulasi Lanjutan", e);
                document.getElementById('mkResult').innerHTML = "Terjadi kesalahan sistem";
                document.getElementById('ssResult').innerHTML = "Terjadi kesalahan sistem";
                document.getElementById('lrResult').innerHTML = "Terjadi kesalahan sistem";
            } finally {
                setCardLoading('trendLoaderOverlay', false);
            }
        }


        function updateAvailabilityPeriod(tsData, dtType, yFrom, yTo, mo) {
            setCardLoading('availPeriodLoaderOverlay', false);
            const valEl = document.getElementById('availPctValue');
            const labelEl = document.getElementById('availPctLabel');
            const badgeEl = document.getElementById('availPctBadge');
            const barFill = document.getElementById('availBarFill');
            const actualCount = tsData.length;

            // Hitung jumlah data yang diharapkan berdasarkan tipe
            let expectedCount = 0;
            if (dtType === 'tahunan') {
                expectedCount = yTo - yFrom + 1;
            } else if (dtType === 'bulanan') {
                if (mo === 'all') {
                    expectedCount = (yTo - yFrom + 1) * 12;
                } else {
                    expectedCount = yTo - yFrom + 1;
                }
            } else if (dtType === 'musiman') {
                expectedCount = yTo - yFrom + 1;
            } else {
                // harian
                let totalDays = 0;
                for (let y = yFrom; y <= yTo; y++) {
                    const isLeap = (y % 4 === 0 && y % 100 !== 0) || (y % 400 === 0);
                    totalDays += isLeap ? 366 : 365;
                }
                expectedCount = totalDays;
            }

            if (expectedCount <= 0) expectedCount = 1;
            const pct = Math.min(100, (actualCount / expectedCount) * 100);
            const pctDisplay = pct.toFixed(2);

            // Update Bar
            barFill.style.width = `${pct}%`;

            // Color based on percentage
            let color = '#EF4444'; // red < 50
            let badgeBg = '#FEE2E2'; let badgeColor = '#991B1B'; let badgeText = 'Kurang';
            if (pct >= 80) { color = '#16A34A'; badgeBg = '#DCFCE7'; badgeColor = '#166534'; badgeText = 'Baik'; }
            else if (pct >= 50) { color = '#F59E0B'; badgeBg = '#FEF9C3'; badgeColor = '#854D0E'; badgeText = 'Cukup'; }

            barFill.style.background = color;
            valEl.textContent = pctDisplay + '%';
            valEl.style.color = color;
            labelEl.innerHTML = `<strong>${actualCount}</strong> dari <strong>${expectedCount}</strong> data tersedia`;
            badgeEl.style.background = badgeBg;
            badgeEl.style.color = badgeColor;
            badgeEl.textContent = badgeText;
        }

        async function loadAvailabilityYear() {
            setCardLoading('availLoaderOverlay', true);
            document.getElementById('availYearDisplay').textContent = currentAvailYear;
            const grid = document.getElementById('availabilityGrid');
            grid.innerHTML = `<div style="display:flex; justify-content:center; align-items:center; padding: 20px; width: 100%;">
                <div class="spinner" style="width:20px; height:20px; border-width:2px; margin-right:8px; display:inline-block;"></div>
                <span style="font-size:0.85rem; color:#6B7280; font-weight:500;">Memuat rekapitulasi harian...</span>
            </div>`;

            try {
                const payload = {
                    pos_id: STATION_ID,
                    dataType: 'harian',
                    yearFrom: currentAvailYear,
                    yearTo: currentAvailYear,
                    month: 'all'
                };
                const res = await fetch('php/get_timeseries.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
                });
                const tsData = await res.json();

                grid.innerHTML = '';

                // Siapkan pemetaan dari doy (1-366) ke value, agar kita tahu apakah tersedia (nilai valid)
                // Harian x dikonversi fraction = year + (doy-1)/365
                const dataSet = new Set();
                tsData.forEach(d => {
                    let doy = Math.round((d.year - currentAvailYear) * 365.0) + 1;
                    dataSet.add(doy);
                });

                let availableTokens = 0;
                const isLeap = (currentAvailYear % 4 === 0 && currentAvailYear % 100 !== 0) || (currentAvailYear % 400 === 0);
                const daysInMonth = [31, isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let totalValidDays = isLeap ? 366 : 365;

                // Bangun Div elements untuk X = col(31), Y = row(12)
                // Di CSS Grid: baris 1 = Jan, baris 12 = Des
                let doyCounter = 1;
                // Kita harus iterasi per BULAN (baris), dan per TANGGAL (kolom)
                for (let m = 0; m < 12; m++) { // Row (Y axis)
                    for (let d = 1; d <= 31; d++) { // Col (X axis)
                        const cell = document.createElement('div');
                        cell.classList.add('gh-cell');

                        if (d > daysInMonth[m]) {
                            // Tanggal tak valid misal 31 Feb
                            cell.classList.add('empty');
                            cell.title = `Tanggal tidak valid: ${d}/${m + 1}`;
                        } else {
                            // Tanggal valid
                            if (dataSet.has(doyCounter)) {
                                cell.classList.add('available');
                                availableTokens++;
                                cell.title = `Tanggal ${d}/${m + 1}/${currentAvailYear} (Tersedia)`;
                            } else {
                                cell.classList.add('missing');
                                cell.title = `Tanggal ${d}/${m + 1}/${currentAvailYear} (Hilang)`;
                            }
                            doyCounter++;
                        }
                        grid.appendChild(cell);
                    }
                }

                const pct = ((availableTokens / totalValidDays) * 100).toFixed(2);
                document.getElementById('availSummary').innerHTML = `Data hujan harian tahun ${currentAvailYear} tersedia <strong>${pct}%</strong> (${availableTokens} dari ${totalValidDays} hari).`;
                setCardLoading('availLoaderOverlay', false);
            } catch (e) {
                console.error(e);
                grid.innerHTML = 'Gagal memuat';
                setCardLoading('availLoaderOverlay', false);
            }
        }

        function changeAvailabilityYear(step) {
            let next = currentAvailYear + step;
            if (next < minAvailYear || next > maxAvailYear) return;
            currentAvailYear = next;
            loadAvailabilityYear();
        }

        let isDailyPieChartActive = false;
        async function toggleDailyPieChart() {
            isDailyPieChartActive = !isDailyPieChartActive;
            const btn = document.getElementById('btnTogglePie');
            const btnText = document.getElementById('btnTogglePieText');
            const btnIcon = btn.querySelector('svg');
            const gridContent = document.getElementById('dailyGridContent');
            const pieWrapper = document.getElementById('dailyPieWrapper');
            const navControls = document.getElementById('dailyYearNavControls');

            if (isDailyPieChartActive) {
                btnText.textContent = "Kembali ke Kalender";
                btnIcon.innerHTML = '<path d="M19 12H5M12 19l-7-7 7-7"/>';
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-primary');
                btn.style.boxShadow = '0 2px 8px rgba(37, 99, 235, 0.25)';
                gridContent.style.display = 'none';
                navControls.style.display = 'none';
                pieWrapper.style.display = 'flex';
                
                setCardLoading('availLoaderOverlay', true);
                try {
                    const payload = {
                        pos_id: STATION_ID,
                        dataType: 'harian',
                        yearFrom: minAvailYear,
                        yearTo: maxAvailYear,
                        month: 'all'
                    };
                    const res = await fetch('php/get_timeseries.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
                    });
                    const tsData = await res.json();
                    
                    const actualDataCount = tsData.length;
                    let expectedDataCount = 0;
                    for (let y = minAvailYear; y <= maxAvailYear; y++) {
                        const isLeap = (y % 4 === 0 && y % 100 !== 0) || (y % 400 === 0);
                        expectedDataCount += isLeap ? 366 : 365;
                    }
                    if (expectedDataCount === 0) expectedDataCount = 1;
                    const missingCount = expectedDataCount - actualDataCount;
                    const pct = ((actualDataCount / expectedDataCount) * 100).toFixed(2);
                    
                    document.getElementById('dailyPieSummary').innerHTML = `Dari rentang tahun <strong>${minAvailYear} — ${maxAvailYear}</strong>,<br>Data tersedia: <strong>${actualDataCount}</strong> hari (${pct}%)<br>Data hilang: <strong>${missingCount}</strong> hari`;
                    
                    const ctx = document.getElementById('dailyPieChart').getContext('2d');
                    if (pieChartInstance) {
                        pieChartInstance.destroy();
                    }
                    pieChartInstance = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Tersedia', 'Hilang'],
                            datasets: [{
                                data: [actualDataCount, missingCount],
                                backgroundColor: ['#3B82F6', '#EF4444'],
                                borderWidth: 2,
                                borderColor: '#fff',
                                hoverOffset: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { 
                                        font: { family: 'Inter', size: 13, weight: '600' },
                                        padding: 16,
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                                    padding: 12,
                                    titleFont: { family: 'Inter', size: 13, weight: '600' },
                                    bodyFont: { family: 'Inter', size: 12 },
                                    cornerRadius: 8,
                                    displayColors: true,
                                    boxPadding: 4
                                }
                            },
                            animation: {
                                animateRotate: true,
                                animateScale: true,
                                duration: 600,
                                easing: 'easeOutQuart'
                            }
                        }
                    });
                } catch (err) {
                    console.error(err);
                    document.getElementById('dailyPieSummary').innerHTML = "Gagal memuat pie chart.";
                } finally {
                    setCardLoading('availLoaderOverlay', false);
                }
            } else {
                btnText.textContent = "Tampilkan Ringkasan (Pie Chart)";
                btnIcon.innerHTML = '<path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M12 3v9l4 2"/>';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
                btn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.04)';
                gridContent.style.display = 'block';
                navControls.style.display = 'flex';
                pieWrapper.style.display = 'none';
            }
        }

        // ====== AVAILABILITY YEAR PICKER ======
        let availViewedDecade = Math.floor(new Date().getFullYear() / 10) * 10;

        function toggleAvailYearPicker(e) {
            e.stopPropagation();
            const grid = document.getElementById('availYearGrid');
            const isOpen = grid.classList.contains('show');
            // Close toolbar pickers
            document.getElementById('gridFrom').classList.add('hidden');
            document.getElementById('gridTo').classList.add('hidden');
            if (isOpen) {
                grid.classList.remove('show');
            } else {
                availViewedDecade = Math.floor(currentAvailYear / 10) * 10;
                renderAvailDecadeGrid(availViewedDecade);
                grid.classList.add('show');
            }
        }

        function renderAvailDecadeGrid(startYear) {
            const grid = document.getElementById('availYearGrid');
            const decadeStart = Math.floor(startYear / 10) * 10;
            const decadeEnd = decadeStart + 9;
            let yearsHtml = '';
            for (let i = 0; i < 12; i++) {
                const y = decadeStart + i;
                const isDisabled = (y < minAvailYear || y > maxAvailYear);
                const isActive = (y === currentAvailYear);
                if (i > 9) {
                    yearsHtml += `<div class="year-item hidden" data-year="${y}">${y}</div>`;
                } else {
                    yearsHtml += `<div class="year-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}" data-avail-year="${y}" onclick="selectAvailYear(${y})" style="cursor:pointer;">${y}</div>`;
                }
            }
            grid.innerHTML = `
                <div class="year-grid-header">
                    <button type="button" class="year-nav-btn" onclick="event.stopPropagation(); availViewedDecade -= 10; renderAvailDecadeGrid(availViewedDecade);">‹</button>
                    <span class="range-text">${decadeStart} — ${decadeEnd}</span>
                    <button type="button" class="year-nav-btn" onclick="event.stopPropagation(); availViewedDecade += 10; renderAvailDecadeGrid(availViewedDecade);">›</button>
                </div>
                <div class="year-grid-content p-2"><div class="decade-grid">${yearsHtml}</div></div>
            `;
        }

        function selectAvailYear(year) {
            if (year < minAvailYear || year > maxAvailYear) return;
            currentAvailYear = year;
            document.getElementById('availYearGrid').classList.remove('show');
            loadAvailabilityYear();
        }

        // Close avail picker on outside click
        document.addEventListener('click', function(e) {
            const wrap = document.querySelector('.avail-year-picker-wrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('availYearGrid').classList.remove('show');
            }
        });

        // ====== YEAR PICKER LOGIC ======
        function renderDecadeGrid(idSuffix, startYear) {
            const gridElem = document.getElementById(`grid${idSuffix}`);
            if (!gridElem) return;
            const decadeStart = Math.floor(startYear / 10) * 10;
            const decadeEnd = decadeStart + 9;
            const activeYear = document.getElementById(`y${idSuffix}`).value;
            let yearsHtml = '';
            for (let i = 0; i < 12; i++) {
                const y = decadeStart + i;
                const isDisabled = (y < window.stationMinYear || y > window.stationMaxYear);
                const isActive = (y == activeYear);
                if (i > 9) { yearsHtml += `<div class="year-item hidden" data-year="${y}">${y}</div>`; }
                else { yearsHtml += `<div class="year-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}" data-year="${y}">${y}</div>`; }
            }
            gridElem.innerHTML = `
                <div class="year-grid-header">
                    <button type="button" class="year-nav-btn prev" data-target="${idSuffix}">‹</button>
                    <span class="range-text">${decadeStart} — ${decadeEnd}</span>
                    <button type="button" class="year-nav-btn next" data-target="${idSuffix}">›</button>
                </div>
                <div class="year-grid-content p-2"><div class="decade-grid">${yearsHtml}</div></div>
            `;
        }

        document.getElementById('displayFrom').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('gridTo').classList.add('hidden');
            document.getElementById('gridFrom').classList.toggle('hidden');
            if (!document.getElementById('gridFrom').classList.contains('hidden')) renderDecadeGrid('From', window.viewedDecadeFrom);
        });

        document.getElementById('displayTo').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('gridFrom').classList.add('hidden');
            document.getElementById('gridTo').classList.toggle('hidden');
            if (!document.getElementById('gridTo').classList.contains('hidden')) renderDecadeGrid('To', window.viewedDecadeTo);
        });

        document.addEventListener('click', (e) => {
            const navBtn = e.target.closest('.year-nav-btn');
            if (navBtn) {
                e.stopPropagation();
                const target = navBtn.dataset.target;
                const isNext = navBtn.classList.contains('next');
                if (target === 'From') {
                    window.viewedDecadeFrom += isNext ? 10 : -10;
                    renderDecadeGrid('From', window.viewedDecadeFrom);
                } else {
                    window.viewedDecadeTo += isNext ? 10 : -10;
                    renderDecadeGrid('To', window.viewedDecadeTo);
                }
                return;
            }
            const item = e.target.closest('.year-item');
            if (!item) {
                document.getElementById('gridFrom').classList.add('hidden');
                document.getElementById('gridTo').classList.add('hidden');
                return;
            }
            const grid = item.closest('.year-grid');
            const year = item.dataset.year;
            if (grid.id === 'gridFrom') {
                document.getElementById('yFrom').value = year;
                document.getElementById('displayFrom').textContent = year;
                document.getElementById('gridFrom').classList.add('hidden');
            } else {
                document.getElementById('yTo').value = year;
                document.getElementById('displayTo').textContent = year;
                document.getElementById('gridTo').classList.add('hidden');
            }
        });

        // ====== CUSTOM SELECT UI ======
        function setupCustomSelects() {
            document.querySelectorAll('select.form-select').forEach(selectEl => {
                if(selectEl.dataset.customized) return;
                selectEl.dataset.customized = "true";
                selectEl.style.display = 'none'; // Hide native select
                
                const wrapper = document.createElement('div');
                wrapper.className = 'custom-select-wrapper';
                // Copy margin/width styles
                wrapper.style.minWidth = selectEl.style.minWidth;
                
                const trigger = document.createElement('div');
                trigger.className = 'custom-select-trigger';
                trigger.style.width = '100%';
                const triggerText = document.createElement('span');
                triggerText.style.whiteSpace = 'nowrap';
                triggerText.style.overflow = 'hidden';
                triggerText.style.textOverflow = 'ellipsis';
                trigger.appendChild(triggerText);
                trigger.innerHTML += `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M6 9l6 6 6-6"/></svg>`;
                
                const optionsContainer = document.createElement('div');
                optionsContainer.className = 'custom-select-options';
                
                wrapper.appendChild(trigger);
                wrapper.appendChild(optionsContainer);
                selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);

                function renderOptions() {
                    // Cek visibilitas select asal
                    if (selectEl.style.display === 'none' && !selectEl.dataset.customized) {
                        wrapper.style.display = 'none';
                    } else if (selectEl.style.display === 'none') { 
                        // sometimes native select is forced to none by our script, we should check its old display logic or let toggleMonthAgg handle wrapper display
                    }

                    optionsContainer.innerHTML = '';
                    let selectedOption = selectEl.options[selectEl.selectedIndex] || selectEl.options[0];
                    if (selectedOption) {
                        trigger.querySelector('span').textContent = selectedOption.textContent;
                    }

                    Array.from(selectEl.options).forEach(opt => {
                        const optEl = document.createElement('div');
                        optEl.className = 'custom-select-option' + (opt.selected ? ' selected' : '');
                        optEl.textContent = opt.textContent;
                        optEl.dataset.value = opt.value;
                        
                        optEl.addEventListener('click', (e) => {
                            e.stopPropagation();
                            selectEl.value = opt.value;
                            selectEl.dispatchEvent(new Event('change'));
                            wrapper.classList.remove('open');
                            renderOptions();
                        });
                        optionsContainer.appendChild(optEl);
                    });
                }
                
                renderOptions();

                // Toggle dropdown
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Close other open selects
                    document.querySelectorAll('.custom-select-wrapper').forEach(w => {
                        if (w !== wrapper) w.classList.remove('open');
                    });
                    wrapper.classList.toggle('open');
                });
                
                // Watch for changes via JS
                selectEl.addEventListener('change', renderOptions);
                selectEl.addEventListener('optionsChanged', () => {
                    if (selectEl.id === 'dtMonth' || selectEl.id === 'dtAgg') {
                        const dtType = document.getElementById('dtType').value;
                        wrapper.style.display = dtType === 'tahunan' ? 'none' : 'inline-block';
                    }
                    renderOptions();
                });
                
                // Initial visibility check
                if (selectEl.id === 'dtMonth' || selectEl.id === 'dtAgg') {
                    const dtType = document.getElementById('dtType').value;
                    wrapper.style.display = dtType === 'tahunan' ? 'none' : 'inline-block';
                }
            });
            
            // Close dropdowns on outside click
            document.addEventListener('click', () => {
                document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
            });
        }
        setupCustomSelects();
    </script>
</body>

</html>