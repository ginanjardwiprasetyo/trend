<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>TrenHidro – Peta Tren Curah Hujan Interaktif</title>
    <meta name="description"
        content="Jelajahi peta tren curah hujan interaktif dari stasiun-stasiun di wilayah sungai Indonesia. Olah data runtut waktu dengan Mann-Kendall, Sen's Slope, dan Regresi Linear.">
    <meta name="keywords"
        content="hidrologi, tren curah hujan, mann-kendall, sen's slope, regresi linear, pemetaan interaktif, data hidrologi indonesia, trenhidro">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://app.rekayasa-sipil.my.id">
    <meta property="og:title" content="TrenHidro – Peta Tren Curah Hujan Interaktif">
    <meta property="og:description"
        content="Jelajahi peta tren curah hujan interaktif – olah data runtut waktu dengan metode Mann-Kendall, Sen's Slope, dan Regresi Linear.">
    <meta property="og:image" content="https://app.rekayasa-sipil.my.id/favicon.svg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://app.rekayasa-sipil.my.id">
    <meta property="twitter:title" content="TrenHidro – Peta Tren Curah Hujan Interaktif">
    <meta property="twitter:description"
        content="Jelajahi peta tren curah hujan interaktif – olah data runtut waktu dengan metode statistik.">
    <meta property="twitter:image" content="https://app.rekayasa-sipil.my.id/favicon.svg">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Referrer Policy untuk OpenStreetMap -->
    <meta name="referrer" content="origin">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- SEO H1 -->
    <h1
        style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">
        Peta Tren Curah Hujan Interaktif – TrenHidro</h1>

    <!-- ====== LOADER LAYAR PENUH ====== -->
    <div class="loader-overlay hidden" id="fullLoader">
        <div class="loader-content">
            <div class="spinner-large"></div>
            <p class="loader-text">Memproses Data...</p>
        </div>
    </div>

    <!-- ====== NAVBAR ====== -->
    <?php include 'navbar.php'; ?>

    <!-- ====== PETA ====== -->
    <div id="map"></div>

    <!-- ====== PANEL SISI (SIDEBAR) ====== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-content">

            <!-- Pilih Wilayah Sungai -->
            <div class="control-group">
                <label class="control-label" for="dasSelect">Wilayah Sungai</label>
                <select class="form-select" id="dasSelect" disabled
                    style="cursor:default; background:#F3F4F6; color:#6B7280; appearance:none; -webkit-appearance:none;">
                    <option value="bbwsbs">WS Bengawan Solo</option>
                </select>
            </div>

            <!-- Pilih Data -->
            <div class="control-group">
                <label class="control-label">Pilih Data</label>
                <div class="radio-group" id="dataTypeGroup">
                    <button class="radio-btn active" data-value="bulanan">Bulanan</button>
                    <button class="radio-btn" data-value="tahunan">Tahunan</button>
                    <button class="radio-btn" data-value="musiman">Musiman</button>
                </div>
            </div>

            <!-- Agregasi -->
            <div class="control-group" id="aggregationWrapper">
                <label class="control-label">Agregasi</label>
                <div class="aggregation-group" id="aggregationGroup">
                    <button class="agg-btn" data-value="maks">Maks</button>
                    <button class="agg-btn" data-value="rerata">Rerata</button>
                    <button class="agg-btn active" data-value="kumulatif">Kumulatif</button>
                </div>
            </div>

            <!-- Pilih Bulan (muncul sesuai kebutuhan) -->
            <div class="control-group" id="monthWrapper">
                <label class="control-label" for="monthSelect">Pilih Bulan</label>
                <select class="form-select" id="monthSelect">
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

            <!-- Periode -->
            <div class="control-group">
                <label class="control-label">Periode</label>
                <div class="year-picker-wrapper">
                    <div class="year-picker" id="pickerFrom">
                        <button type="button" class="year-display" id="displayFrom">1980</button>
                        <div class="year-grid hidden" id="gridFrom"></div>
                    </div>
                    <div class="year-sep">–</div>
                    <div class="year-picker" id="pickerTo">
                        <button type="button" class="year-display" id="displayTo">2025</button>
                        <div class="year-grid hidden" id="gridTo"></div>
                    </div>
                    <!-- Hidden for JS compatibility -->
                    <input type="hidden" id="yearFrom" value="1980">
                    <input type="hidden" id="yearTo" value="2025">
                </div>
            </div>

            <!-- Opsi Tampilan -->
            <div class="control-group" id="qualityFilterWrapper">
                <label class="control-label">Opsi Tampilan</label>
                <div class="method-list" style="gap: 8px;">
                    <div class="section-loader-overlay" id="qualityLoader">
                        <div class="spinner-small"></div>
                    </div>
                    <div class="filter-item" id="qualityToggle16Item"
                        style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #fff; border: 1.5px solid var(--color-border); border-radius: var(--radius-btn); transition: all var(--transition);">
                        <span class="method-name"
                            style="font-size: 0.9rem; font-weight: 600; color: var(--color-text);">Sembunyikan Data < 16 Tahun</span>
                        <div class="toggle-switch" id="toggleHide16"></div>
                    </div>
                    <div class="filter-item" id="qualityToggle30Item"
                        style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #fff; border: 1.5px solid var(--color-border); border-radius: var(--radius-btn); transition: all var(--transition);">
                        <span class="method-name"
                            style="font-size: 0.9rem; font-weight: 600; color: var(--color-text);">Sembunyikan Data < 30 Tahun</span>
                        <div class="toggle-switch" id="toggleHide30"></div>
                    </div>
                </div>
            </div>

            <!-- Pilih Metode -->
            <div class="control-group">
                <label class="control-label">Pilih Metode</label>
                <div class="method-list" id="methodList">
                    <!-- Overlay Loader Sektoral -->
                    <div class="section-loader-overlay" id="methodLoader">
                        <div class="spinner-small"></div>
                    </div>

                    <div class="method-item" data-method="mann-kendall" id="methodMK">
                        <span class="method-name">Mann-Kendall</span>
                        <div class="toggle-switch" id="toggleMK"></div>
                    </div>
                    <div class="method-item" data-method="sens-slope" id="methodSS">
                        <span class="method-name">Sen's Slope</span>
                        <div class="toggle-switch" id="toggleSS"></div>
                    </div>
                    <div class="method-item" data-method="regresi-linear" id="methodRL">
                        <span class="method-name">Regresi Linear</span>
                        <div class="toggle-switch" id="toggleRL"></div>
                    </div>
                    <div class="method-item" data-method="seasonal-mann-kendall" id="methodSMK">
                        <span class="method-name" style="color:#7C3AED;">Seasonal Mann-Kendall</span>
                        <div class="toggle-switch" id="toggleSMK"></div>
                    </div>
                    <div class="method-item" data-method="seasonal-sens-slope" id="methodSSS">
                        <span class="method-name" style="color:#7C3AED;">Seasonal Sen's Slope</span>
                        <div class="toggle-switch" id="toggleSSS"></div>
                    </div>
                </div>
            </div>

            <!-- Legenda (tersembunyi by default) -->
            <div class="control-group hidden" id="legendWrapper">
                <label class="control-label">Legenda</label>
                <div class="legend-box" id="legendBox">
                    <!-- Diisi dinamis oleh JS berdasarkan metode aktif -->
                </div>
            </div>
    </aside>

    <!-- ====== TOMBOL TOGGLE SIDEBAR ====== -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Buka/Tutup Panel">
        ‹
    </button>

    <!-- ====== LIGHTBOX MODAL ====== -->
    <div class="lightbox-overlay" id="lightboxOverlay">
        <div class="lightbox-modal" id="lightboxModal">
            <div class="lightbox-header">
                <h2 id="lightboxTitle">Nama Stasiun</h2>
                <button class="lightbox-close" id="lightboxClose" title="Tutup">✕</button>
            </div>
            <div class="lightbox-body" id="lightboxBody">
                <div class="lightbox-field">
                    <span class="field-label">Lokasi</span>
                    <span class="field-value" id="lbLocation">–</span>
                </div>
                <div class="lightbox-field">
                    <span class="field-label" id="lbTrendLabel">Nilai Tren</span>
                    <span class="field-value" id="lbTrendValue">–</span>
                </div>
                <div class="lightbox-field">
                    <span class="field-label">Elevasi</span>
                    <span class="field-value" id="lbElevation">Memuat...</span>
                </div>
                <div class="lightbox-field">
                    <span class="field-label">Koordinat</span>
                    <span class="field-value" id="lbCoords">–</span>
                </div>
                <div class="lightbox-field">
                    <span class="field-label">Pengelola</span>
                    <span class="field-value" id="lbManager">–</span>
                </div>
                <div class="lightbox-field">
                    <span class="field-label">Rentang Data</span>
                    <span class="field-value" id="lbRange">–</span>
                </div>
                <div class="lightbox-field">
                    <span class="field-label">Panjang Data</span>
                    <span class="field-value" id="lbLength">–</span>
                </div>
                <div id="confirmSection" style="display:none; padding: 24px 16px; grid-column: 1 / -1;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:16px; text-align:center;">
                        <div style="width:48px; height:48px; border-radius:50%; background:#FEF3C7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div>
                            <p style="margin:0 0 4px; font-size:1rem; font-weight:700; color:#1F2937;" id="confirmTitle">Perhatian</p>
                            <p style="margin:0; color:#6B7280; line-height:1.6; font-size:0.9rem;" id="confirmMessage"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lightbox-footer" id="lightboxFooter">
                <button class="btn btn-secondary" id="lightboxCloseBtn">Tutup</button>
                <button class="btn btn-primary" id="lightboxDetailBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                    </svg>
                    Detail
                </button>
                <button class="btn btn-secondary" id="confirmNo" style="display:none;">Tidak</button>
                <button class="btn btn-primary" id="confirmYes" style="display:none;">Ya, Tetap Buka</button>
            </div>
        </div>
    </div>

    <!-- ====== SCRIPTS ====== -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/app.js?v=7"></script>
    <script src="js/sidebar.js?v=8"></script>
    <script src="js/markers.js?v=5"></script>
    <script src="js/lightbox.js?v=5"></script>

    <?php include 'footer.php'; ?>
</body>

</html>