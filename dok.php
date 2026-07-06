<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Dokumentasi — TrendHidro</title>
    <meta name="description"
        content="Dokumentasi TrendHidro: rumus, metode, panduan penggunaan, dan changelog.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;1,14..32,400;1,14..32,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
        onload="renderMathInElement(document.body, {delimiters:[{left:'$$',right:'$$',display:true},{left:'$',right:'$',display:false}]});"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #F4F6F9;
            overflow-y: auto;
            height: auto;
        }

        /* ===== Layout ===== */
        .docs-layout {
            display: flex;
            min-height: calc(100vh - 56px);
            padding-top: 56px;
        }

        /* ===== Sidebar ===== */
        .docs-sidebar {
            width: 240px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid #E5E7EB;
            padding: 20px 0 32px;
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
        }

        .docs-sidebar h4 {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #9CA3AF;
            padding: 16px 20px 6px;
            margin: 0;
        }

        .docs-sidebar h4:first-child {
            padding-top: 4px;
        }

        .docs-sidebar a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 20px;
            font-size: 0.855rem;
            font-weight: 500;
            color: #4B5563;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.18s ease;
            line-height: 1.4;
        }

        .docs-sidebar a:hover {
            color: #2563EB;
            background: #F5F7FF;
            border-left-color: #BFDBFE;
        }

        .docs-sidebar a.active {
            color: #2563EB;
            border-left-color: #2563EB;
            background: #EFF6FF;
            font-weight: 600;
        }

        /* ===== Floating TOC (right) ===== */
        .docs-toc {
            width: 230px;
            flex-shrink: 0;
            position: sticky;
            top: 80px;
            align-self: flex-start;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            padding: 16px 14px;
            margin-top: 36px;
        }

        .docs-toc h5 {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: capitalize;
            letter-spacing: 0.02em;
            color: #9CA3AF;
            margin: 0 0 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #E5E7EB;
        }

        .docs-toc a {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: #6B7280;
            text-decoration: none;
            padding: 3px 0 3px 10px;
            border-left: 2px solid transparent;
            transition: all 0.15s ease;
            line-height: 1.5;
        }

        .docs-toc a:hover {
            color: #2563EB;
            border-left-color: #BFDBFE;
        }

        .docs-toc a.toc-h3 {
            padding-left: 22px;
            font-size: 0.82rem;
            color: #6B7280;
        }



        .docs-toc a.toc-h1 {
            font-weight: 600;
            color: #374151;
        }

        .docs-toc a.toc-active {
            color: #2563EB;
            border-left-color: #2563EB;
            font-weight: 600;
        }

        .docs-content h2,
        .docs-content h3 {
            scroll-margin-top: 72px;
        }

        /* ===== Content ===== */
        .docs-content {
            flex: 1;
            padding: 36px 48px 72px 44px;
            max-width: 820px;
            min-width: 0;
        }

        .docs-content h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 6px;
            color: #111827;
            letter-spacing: -0.02em;
        }

        .docs-content .page-lead {
            font-size: 0.95rem;
            color: #6B7280;
            margin-bottom: 28px;
            line-height: 1.7;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 20px;
        }

        .docs-content h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 32px 0 10px;
            color: #1F2937;
            padding-bottom: 8px;
            border-bottom: 1px solid #E5E7EB;
        }

        .docs-content h3 {
            font-size: 0.98rem;
            font-weight: 700;
            margin: 22px 0 8px;
            color: #374151;
        }

        .docs-content p {
            font-size: 0.92rem;
            color: #4B5563;
            line-height: 1.85;
            margin-bottom: 10px;
        }

        .docs-content ul,
        .docs-content ol {
            padding-left: 20px;
            margin-bottom: 12px;
        }

        .docs-content li {
            font-size: 0.92rem;
            color: #4B5563;
            line-height: 1.8;
            margin-bottom: 5px;
        }

        .docs-content code {
            background: #F1F5F9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.83rem;
            color: #DC2626;
            font-family: 'Menlo', 'Consolas', monospace;
        }

        /* ===== Formula / Info Boxes ===== */
        .formula-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-left: 4px solid #3B82F6;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 14px 0;
            overflow-x: auto;
        }

        .info-box {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 8px;
            padding: 14px 18px;
            margin: 14px 0;
            font-size: 0.9rem;
            color: #1D4ED8;
            line-height: 1.75;
        }

        .warn-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 8px;
            padding: 14px 18px;
            margin: 14px 0;
            font-size: 0.9rem;
            color: #991B1B;
            line-height: 1.75;
        }

        .warn-box ul,
        .info-box ul {
            margin: 6px 0 0 16px;
        }

        /* ===== Section animation ===== */
        .doc-section {
            display: none;
        }

        .doc-section.active {
            display: block;
            animation: docFade 0.25s ease;
        }

        @keyframes docFade {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== Changelog ===== */
        .changelog-item {
            padding: 0 0 16px;
            border-bottom: 1px solid #F1F5F9;
        }

        #sec-changelog .page-lead {
            margin-bottom: 12px;
            padding-bottom: 10px;
        }

        .changelog-item:last-child {
            border-bottom: none;
        }

        .changelog-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1F2937;
            margin: 0 0 6px;
            padding: 0;
            border: none;
        }

        .changelog-sub {
            font-weight: 400;
            font-size: 0.88rem;
            color: #6B7280;
        }

        .changelog-desc {
            font-size: 0.87rem;
            color: #6B7280;
            line-height: 1.7;
        }

        /* ===== Summary Card Grid (for peta & olah guide) ===== */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin: 14px 0 20px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 14px 16px;
            transition: box-shadow 0.18s;
        }

        .summary-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
        }

        .summary-card-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #9CA3AF;
            margin-bottom: 4px;
        }

        .summary-card-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1F2937;
            line-height: 1.4;
        }

        .summary-card-sub {
            font-size: 0.8rem;
            color: #6B7280;
            margin-top: 2px;
            line-height: 1.5;
        }

        /* ===== Marker color legend ===== */
        .color-legend {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 10px 0 16px;
        }

        .color-legend-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.89rem;
            color: #374151;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* ===== Notation table in Methods ===== */
        .notation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin: 10px 0 16px;
        }

        .notation-table th {
            background: #F8FAFC;
            color: #374151;
            font-weight: 600;
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #E5E7EB;
        }

        .notation-table td {
            padding: 7px 12px;
            border: 1px solid #E5E7EB;
            color: #4B5563;
            vertical-align: top;
            line-height: 1.6;
        }

        .notation-table tr:nth-child(even) td {
            background: #F9FAFB;
        }

        /* ===== stat-table (existing) override ===== */
        .stat-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin: 10px 0 16px;
        }

        .stat-table th {
            background: #F8FAFC;
            color: #374151;
            font-weight: 600;
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #E5E7EB;
        }

        .stat-table td {
            padding: 7px 12px;
            border: 1px solid #E5E7EB;
            color: #4B5563;
            vertical-align: top;
            line-height: 1.6;
        }

        .stat-table tr:nth-child(even) td {
            background: #F9FAFB;
        }

        /* ===== FAQ ===== */
        .faq-item {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            margin-bottom: 10px;
            padding: 16px 20px;
        }

        .faq-q {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1F2937;
            margin: 0 0 6px;
            padding: 0;
            line-height: 1.5;
        }

        .faq-a {
            font-size: 0.9rem;
            color: #4B5563;
            line-height: 1.8;
            padding: 0;
        }

        /* ===== Glosarium ===== */
        .glos-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }

        .glos-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 0;
            border-bottom: 1px solid #F1F5F9;
            padding: 10px 0;
            align-items: start;
        }

        .glos-row:last-child { border-bottom: none; }

        .glos-term {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1F2937;
            padding-right: 16px;
            margin: 0;
        }

        .glos-def {
            font-size: 0.88rem;
            color: #4B5563;
            line-height: 1.7;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1024px) {
            .docs-toc { display: none; }
        }

        @media (max-width: 768px) {
            .docs-sidebar { display: none; }
            .docs-content { padding: 24px 18px 48px; }
            .summary-grid { grid-template-columns: 1fr; }
            .glos-row { grid-template-columns: 1fr; gap: 2px; }
            .glos-term { padding-right: 0; }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="docs-layout">
        <!-- Sidebar -->
        <aside class="docs-sidebar" id="docsSidebar">
            <h4>Umum</h4>
            <a href="#tentang" class="active" onclick="showSection('tentang', this)">Tentang TrendHidro</a>
            <a href="#changelog" onclick="showSection('changelog', this)"><i>Changelog</i></a>

            <h4>Panduan</h4>
            <a href="#peta-guide" onclick="showSection('peta-guide', this)">Peta Interaktif</a>
            <a href="#olah-guide" onclick="showSection('olah-guide', this)">Olah Data Anda</a>

            <h4>Metode</h4>
            <a href="#mann-kendall" onclick="showSection('mann-kendall', this)">Mann-Kendall</a>
            <a href="#sens-slope" onclick="showSection('sens-slope', this)">Sen's Slope</a>
            <a href="#regresi-linear" onclick="showSection('regresi-linear', this)">Regresi Linear</a>

            <h4>Lanjutan</h4>
            <a href="#interpretasi" onclick="showSection('interpretasi', this)">Interpretasi Hasil</a>
            <a href="#faq" onclick="showSection('faq', this)">FAQ</a>
            <a href="#glosarium" onclick="showSection('glosarium', this)">Glosarium</a>
        </aside>

        <!-- Content -->
        <main class="docs-content">

            <!-- ==================== TENTANG ==================== -->
            <div class="doc-section active" id="sec-tentang">
                <h1>Tentang TrendHidro</h1>
                <p class="page-lead">
                    Platform pengolah data runtut waktu (<i>time series</i>) hidrometeorologi, khususnya data curah hujan, berbasis metode statistik untuk mendeteksi dan mengestimasi <i>trend</i>.
                </p>

                <h2>Apa itu TrendHidro?</h2>
                <p>TrendHidro menyediakan tiga metode uji statistik yang dapat dijalankan langsung di browser:</p>
                <ul>
                    <li><strong>Uji Mann-Kendall</strong> — Mendeteksi keberadaan <i>trend</i> monoton secara non-parametrik</li>
                    <li><strong>Sen's Slope Estimator</strong> — Mengestimasi besaran kemiringan <i>trend</i> secara non-parametrik (<i>robust</i> terhadap <i>outlier</i>)</li>
                    <li><strong>Regresi Linear</strong> — Uji <i>trend</i> parametrik dengan estimasi <i>slope</i>, koefisien determinasi ($R^2$), dan uji signifikansi $t$</li>
                </ul>

                <h2>Sumber Data</h2>
                <p>
                    Data curah hujan harian yang tersedia pada halaman <strong>Peta Interaktif</strong> bersumber dari
                    <a href="https://hidrologi.bbws-bsolo.net/" target="_blank" rel="noopener">BBWS Bengawan Solo</a>
                    — Balai Besar Wilayah Sungai Bengawan Solo. Dataset dapat diunduh di
                    <a href="https://data.mendeley.com/preview/wswmz8t6zn" target="_blank" rel="noopener">Mendeley Data</a>.
                </p>

                <h2>Batasan Platform</h2>
                <ul>
                    <li>Data yang tersedia mencakup stasiun curah hujan di wilayah sungai Bengawan Solo yang terdaftar dalam <i>database</i>.</li>
                    <li>Semua uji signifikansi menggunakan tingkat kepercayaan 95% ($\alpha = 0{,}05$).</li>
                </ul>
            </div>

            <!-- ==================== CHANGELOG ==================== -->
            <div class="doc-section" id="sec-changelog">
                <h1><i>Changelog</i></h1>
                <p class="page-lead">Riwayat versi TrendHidro.</p>

                <div class="changelog-item">
                    <h2 class="changelog-title">v1.0 <span class="changelog-sub">— Juni 2026</span></h2>
                    <div class="changelog-desc">
                        Peluncuran TrendHidro dengan fitur lengkap: Peta Interaktif berbasis Leaflet.js dengan marker berwarna hasil uji statistik, halaman Detail Stasiun dengan grafik dan ketersediaan data harian, halaman Olah Data untuk upload file CSV/XLS/XLSX, serta ketiga metode statistik (Mann-Kendall, Sen's Slope, Regresi Linear) yang berjalan di server PHP.
                    </div>
                </div>
            </div>

            <!-- ==================== PETA GUIDE ==================== -->
            <div class="doc-section" id="sec-peta-guide">
                <h1>Panduan Peta Interaktif</h1>
                <p class="page-lead">
                    Halaman <strong>Peta Interaktif</strong> menampilkan seluruh stasiun curah hujan dalam satu peta. Warna setiap marker mencerminkan hasil uji <i>trend</i> sesuai metode dan parameter yang dipilih.
                </p>

                <h2>Panel Kontrol</h2>
                <p>Panel kiri dapat disembunyikan/ditampilkan dengan tombol <strong>‹ ›</strong> di tepi layar.</p>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-label">Wilayah Sungai</div>
                        <div class="summary-card-value">Pilih WS</div>
                        <div class="summary-card-sub">Peta memperbesar otomatis ke batas wilayah terpilih dan memuat semua stasiunnya.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Tipe Data</div>
                        <div class="summary-card-value">Bulanan / Tahunan / Musiman</div>
                        <div class="summary-card-sub">Agregasi periode sebelum data dianalisis. Musiman: Jan–Mar, Apr–Jun, Jul–Sep, Okt–Des.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Agregasi</div>
                        <div class="summary-card-value">SUM / AVG / MAX / MIN</div>
                        <div class="summary-card-sub">Fungsi peringkasan nilai curah hujan dalam periode terpilih.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Periode Tahun</div>
                        <div class="summary-card-value">Year picker</div>
                        <div class="summary-card-sub">Klik angka tahun untuk membuka pemilih. Tahun tidak tersedia ditampilkan abu-abu.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Metode</div>
                        <div class="summary-card-value">Toggle switch</div>
                        <div class="summary-card-sub">Aktifkan satu metode (Mann-Kendall, Sen's Slope, atau Regresi Linear). Marker berubah warna sesuai hasil.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Filter Kualitas</div>
                        <div class="summary-card-value">&lt; 16 thn / &lt; 30 thn</div>
                        <div class="summary-card-sub">Sembunyikan stasiun dengan data pendek. Muncul setelah metode diaktifkan.</div>
                    </div>
                </div>

                <h2>Legenda Marker</h2>
                <div class="color-legend">
                    <div class="color-legend-row"><span class="color-dot" style="background:#0B6E2F;"></span> <strong style="color:#0B6E2F;">▲ Hijau Gelap</strong> — Meningkat signifikan</div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#16A34A;"></span> <strong style="color:#16A34A;">▲ Hijau Terang</strong> — Meningkat tidak signifikan</div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#991B1B;"></span> <strong style="color:#991B1B;">▼ Merah Gelap</strong> — Menurun signifikan</div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#DC2626;"></span> <strong style="color:#DC2626;">▼ Merah Terang</strong> — Menurun tidak signifikan</div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#6B7280;"></span> <strong style="color:#6B7280;">— Abu-abu</strong> — Tidak ada <i>trend</i></div>
                </div>
                <p>Tanda <strong>⚠︎ (kuning)</strong> pada marker menandakan panjang data &lt; 16 tahun — hasil perlu diinterpretasi dengan hati-hati.</p>

                <h2>Interaksi Peta</h2>
                <ul>
                    <li><strong>Klik marker</strong> — Membuka <i>lightbox</i> informasi stasiun (nama, koordinat, elevasi, pengelola, rentang data, nilai <i>trend</i>)</li>
                    <li><strong>Tombol Detail</strong> di <i>lightbox</i> — Membuka halaman detail stasiun</li>
                    <li><strong>Scroll / Zoom</strong> — Perbesar/perkecil peta; tombol +/− di kiri bawah</li>
                    <li><strong>Ganti <i>base map</i></strong> — Ikon lapisan di kiri bawah: OpenStreetMap, ESRI Gray, atau Topo v4</li>
                </ul>

                <h2>Halaman Detail Stasiun</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-label">Grafik Curah Hujan</div>
                        <div class="summary-card-value">Bar + garis regresi</div>
                        <div class="summary-card-sub">Diagram batang data runtut waktu; garis merah menunjukkan regresi linear.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Rekapitulasi <i>Trend</i></div>
                        <div class="summary-card-value">3 kartu metode</div>
                        <div class="summary-card-sub">Hasil Mann-Kendall, Sen's Slope, dan Regresi Linear ditampilkan berdampingan.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Ketersediaan Data</div>
                        <div class="summary-card-value">% kelengkapan periode</div>
                        <div class="summary-card-sub">Hijau ≥ 80%, Kuning 50–79%, Merah &lt; 50%.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Statistik Deskriptif</div>
                        <div class="summary-card-value">Rerata, Maks, Min, CV</div>
                        <div class="summary-card-sub">Simpangan baku, koefisien variasi, dan deteksi <i>outlier</i> (IQR).</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Ketersediaan Data Harian</div>
                        <div class="summary-card-value">Grid setahun penuh</div>
                        <div class="summary-card-sub">Biru = data tersedia, Merah = data hilang. Tombol diagram lingkaran tersedia.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Peta Mini</div>
                        <div class="summary-card-value">Lokasi stasiun</div>
                        <div class="summary-card-sub">Peta kecil yang menampilkan posisi geografis stasiun.</div>
                    </div>
                </div>
            </div>

            <!-- ==================== OLAH GUIDE ==================== -->
            <div class="doc-section" id="sec-olah-guide">
                <h1>Panduan Olah Data Anda</h1>
                <p class="page-lead">
                    Halaman <strong>Olah Data</strong> memungkinkan Anda mengunggah data curah hujan sendiri dan menjalankan ketiga uji statistik langsung di browser. Data <strong>tidak</strong> disimpan di server.
                </p>

                <h2>Cara Penggunaan</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-label">1 — Upload</div>
                        <div class="summary-card-value">CSV, XLS, atau XLSX</div>
                        <div class="summary-card-sub">Seret file ke area upload atau klik untuk memilih. Format tanggal dan separator CSV terdeteksi otomatis.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">2 — Pratinjau</div>
                        <div class="summary-card-value">20 baris pertama</div>
                        <div class="summary-card-sub">Tabel pratinjau muncul setelah file diproses. Klik "Ganti File" untuk mengganti.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">3 — Parameter</div>
                        <div class="summary-card-value">Tipe, Bulan, Periode</div>
                        <div class="summary-card-sub">Pilih tipe data (Bulanan/Tahunan/Musiman), filter bulan/musim, dan rentang tahun.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">4 — Olah</div>
                        <div class="summary-card-value">Klik "Olah Data"</div>
                        <div class="summary-card-sub">Server menjalankan ketiga metode dan hasilnya ditampilkan langsung di halaman.</div>
                    </div>
                </div>

                <h2>Format File</h2>
                <p>File harus memiliki minimal <strong>2 kolom</strong>:</p>
                <table class="stat-table" style="max-width:460px;">
                    <thead>
                        <tr><th>Posisi</th><th>Isi</th><th>Format</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Kolom 1</td><td>Tanggal</td><td><code>dd/mm/yy</code>, <code>dd/mm/yyyy</code>, <code>dd-mm-yyyy</code>, <code>dd.mm.yyyy</code>, atau <code>yyyy-mm-dd</code></td></tr>
                        <tr><td>Kolom 2</td><td>Nilai curah hujan</td><td>Numerik (mm)</td></tr>
                        <tr><td colspan="3" style="font-size:0.83rem;color:#6B7280;">Baris pertama (header) dilewati otomatis. Separator CSV (koma atau titik koma) terdeteksi otomatis.</td></tr>
                    </tbody>
                </table>

                <h3>Contoh CSV</h3>
                <div class="formula-box" style="font-family:'Menlo','Consolas',monospace;font-size:0.85rem;line-height:1.7;">
                    Tanggal,Curah Hujan<br>
                    01/01/2020,12<br>
                    02/01/2020,0<br>
                    03/01/2020,5<br>
                    04/01/2020,8<br>
                    05/01/2020,15
                </div>

                <h2>Hasil yang Ditampilkan</h2>
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-label">Grafik</div>
                        <div class="summary-card-value">Bar + garis <i>trend</i></div>
                        <div class="summary-card-sub">Diagram batang data agregat. Jika Regresi Linear aktif, garis merah ditampilkan di atas batang.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Rekapitulasi <i>Trend</i></div>
                        <div class="summary-card-value">3 kartu metode</div>
                        <div class="summary-card-sub">MK: Z uji & Z kritis. Sen's Slope: Q<sub>med</sub>, Q<sub>min</sub>, Q<sub>max</sub>. Regresi: t uji & t kritis.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Ketersediaan Data</div>
                        <div class="summary-card-value">% pada periode terpilih</div>
                        <div class="summary-card-sub">Hijau ≥ 80%, Kuning 50–79%, Merah &lt; 50%.</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-label">Statistik Deskriptif</div>
                        <div class="summary-card-value">Rerata, Maks, Min, CV</div>
                        <div class="summary-card-sub">Simpangan baku, koefisien variasi, jumlah titik data, dan deteksi <i>outlier</i> (IQR).</div>
                    </div>
                </div>

                <h2>Pesan Galat Umum</h2>
                <ul>
                    <li><strong>"Format file tidak didukung"</strong> — File bukan CSV, XLS, atau XLSX</li>
                    <li><strong>"File CSV kosong"</strong> — File tidak memiliki baris data</li>
                    <li><strong>"Tidak ada data valid"</strong> — Semua baris gagal dibaca (tanggal tidak dikenali atau nilai bukan angka)</li>
                    <li><strong>"Gagal membaca file Excel"</strong> — File Excel rusak atau format tidak kompatibel</li>
                    <li><strong>"Tahun awal &gt; tahun akhir"</strong> — Rentang tahun tidak valid</li>
                    <li><strong>"Tidak ada data untuk periode"</strong> — Data kosong setelah filter diterapkan</li>
                </ul>
            </div>

            <!-- ==================== MANN-KENDALL ==================== -->
            <div class="doc-section" id="sec-mann-kendall">
                <h1>Uji Mann-Kendall</h1>
                <p class="page-lead">
                    Uji non-parametrik untuk mendeteksi keberadaan <i>trend</i> monoton dalam data runtut waktu. Tidak memerlukan asumsi distribusi normal — cocok untuk data hidrologi yang sering miring (<i>skewed</i>).
                </p>

                <h2>Rumus</h2>

                <h3>Statistik S</h3>
                <div class="formula-box">
                    $$S = \sum_{k=1}^{n-1} \sum_{j=k+1}^{n} \text{sign}(x_j - x_k)$$
                    $$\text{sign}(\theta) = \begin{cases} +1 & \theta > 0 \\ 0 & \theta = 0 \\ -1 & \theta < 0 \end{cases}$$
                </div>
                <p>$S$ = jumlah selisih bertanda dari semua pasangan; $n$ = jumlah titik data; $\text{sign}(\theta)$ = fungsi tanda.</p>

                <h3>Varians S</h3>
                <div class="formula-box">
                    $$\text{Var}(S) = \frac{n(n-1)(2n+5) - \displaystyle\sum_{p=1}^{g} t_p(t_p - 1)(2t_p + 5)}{18}$$
                </div>
                <p>$\text{Var}(S)$ = varians $S$ dengan koreksi <i>tied groups</i>; $t_p$ = jumlah data dalam <i>tied group</i> ke-$p$; $g$ = banyaknya <i>tied groups</i>.</p>

                <h3>Statistik Z (uji)</h3>
                <div class="formula-box">
                    $$Z = \begin{cases}
                    \dfrac{S - 1}{\sqrt{\text{Var}(S)}} & S > 0 \\[10pt]
                    0 & S = 0 \\[10pt]
                    \dfrac{S + 1}{\sqrt{\text{Var}(S)}} & S < 0
                    \end{cases}$$
                </div>
                <p>$Z$ = statistik uji (distribusi normal standar); koreksi ±1 meningkatkan akurasi pada sampel kecil. $|Z| > 1{,}96$ → <i>trend</i> signifikan pada $\alpha = 0{,}05$; $Z > 0$ → meningkat, $Z < 0$ → menurun.</p>

                <h3><i>p-value</i></h3>
                <div class="formula-box">
                    $$p = 2 \times \bigl[1 - \Phi(|Z|)\bigr]$$
                </div>
                <p>$p$ = <i>p-value</i> uji dua sisi; $\Phi$ = CDF normal standar $N(0,1)$.</p>

                <h2>Hipotesis</h2>
                <ul>
                    <li>$H_0$: Tidak ada <i>trend</i> monoton — data bersifat <i>i.i.d.</i></li>
                    <li>$H_1$: Terdapat <i>trend</i> monoton (meningkat atau menurun)</li>
                </ul>
            </div>

            <!-- ==================== SEN'S SLOPE ==================== -->
            <div class="doc-section" id="sec-sens-slope">
                <h1>Sen's Slope Estimator</h1>
                <p class="page-lead">
                    Estimator non-parametrik untuk menghitung besaran kemiringan <i>trend</i>. Lebih <em>robust</em> terhadap <i>outlier</i> dibandingkan regresi linear karena menggunakan <strong>median</strong> dari seluruh kemiringan pasangan data.
                </p>

                <h2>Rumus</h2>

                <h3>Kemiringan setiap pasangan</h3>
                <div class="formula-box">
                    $$Q_i = \frac{y_j - y_k}{t_j - t_k} \quad \text{untuk semua } j > k, \quad i = 1, 2, \ldots, N$$
                    $$N = \frac{n(n-1)}{2}$$
                </div>
                <p>$Q_i$ = kemiringan pasangan ke-$i$; $t_j, t_k$ = indeks waktu; $y_j, y_k$ = nilai data; $N$ = total jumlah pasangan; $n$ = jumlah titik data.</p>

                <h3>Sen's Slope (median kemiringan)</h3>
                <div class="formula-box">
                    $$\hat{\beta} = \text{median}(Q_1, Q_2, \ldots, Q_N)$$
                </div>
                <p>$\hat{\beta}$ = median dari seluruh $Q_i$ — besarnya perubahan per satuan waktu (<i>robust</i> terhadap <i>outlier</i>).</p>

                <h3><i>Intercept</i></h3>
                <div class="formula-box">
                    $$\hat{\alpha} = \text{median}(y_i - \hat{\beta} \cdot t_i)$$
                </div>
                <p>$\hat{\alpha}$ = <i>intercept</i> garis <i>trend</i>.</p>

                <h3>Interval Kepercayaan 95%</h3>
                <div class="formula-box">
                    $$C_{\alpha} = Z_{\alpha/2} \cdot \sqrt{\text{Var}(S)}, \quad
                    M_1 = \frac{N - C_{\alpha}}{2}, \quad
                    M_2 = \frac{N + C_{\alpha}}{2}$$
                    $$Q_{\min} = Q_{(M_1)}, \quad Q_{\max} = Q_{(M_2)}$$
                </div>
                <p>$C_{\alpha}$ = lebar interval (jumlah kemiringan); $\text{Var}(S)$ = varians $S$ dari Mann-Kendall; $Z_{\alpha/2}=1{,}96$ pada $\alpha=0{,}05$; $Q_{\min}, Q_{\max}$ = batas bawah/atas IK 95%. Jika 0 tidak berada dalam ($Q_{\min}, Q_{\max}$), <i>trend</i> signifikan.</p>

                <h2>Hipotesis</h2>
                <ul>
                    <li>$H_0$: Tidak ada <i>trend</i> — kemiringan populasi ($\beta$) = 0</li>
                    <li>$H_1$: Terdapat <i>trend</i> — $\beta \ne 0$ (meningkat atau menurun)</li>
                </ul>
            </div>

            <!-- ==================== REGRESI LINEAR ==================== -->
            <div class="doc-section" id="sec-regresi-linear">
                <h1>Regresi Linear</h1>
                <p class="page-lead">
                    Metode parametrik (<i>Ordinary Least Squares</i>) untuk menentukan garis lurus terbaik yang meminimalkan jumlah kuadrat residual. Memberikan $R^2$ dan uji signifikansi $t$.
                </p>

                <h2>Rumus</h2>

                <h3>Komponen dasar</h3>
                <div class="formula-box">
                    $$S_{xx} = \sum_{i=1}^{n}(x_i - \bar{x})^2, \quad
                    S_{yy} = \sum_{i=1}^{n}(y_i - \bar{y})^2, \quad
                    S_{xy} = \sum_{i=1}^{n}(x_i - \bar{x})(y_i - \bar{y})$$
                </div>
                <p>$S_{xx}, S_{yy}, S_{xy}$ = jumlah kuadrat dan hasil kali silang deviasi; $x_i$ = indeks waktu; $y_i$ = nilai data; $\bar{x}, \bar{y}$ = rata-rata; $n$ = jumlah titik data.</p>

                <h3>Slope dan intercept</h3>
                <div class="formula-box">
                    $$\hat{\beta} = \frac{S_{xy}}{S_{xx}}, \quad
                    \hat{\alpha} = \bar{y} - \hat{\beta} \cdot \bar{x}$$
                </div>
                <p>$\hat{\beta}$ = slope regresi (perubahan $y$ per satuan waktu); $\hat{\alpha}$ = <i>intercept</i>.</p>

                <h3>Koefisien determinasi ($R^2$)</h3>
                <div class="formula-box">
                    $$R^2 = 1 - \frac{SS_{\text{res}}}{SS_{\text{tot}}}
                    = 1 - \frac{\displaystyle\sum_{i=1}^{n}(y_i - \hat{y}_i)^2}{\displaystyle\sum_{i=1}^{n}(y_i - \bar{y})^2}$$
                </div>
                <p>$R^2$ = proporsi variasi data yang dijelaskan model (0–1); $\hat{y}_i$ = nilai prediksi; $SS_{\text{res}}$ = jumlah kuadrat residual; $SS_{\text{tot}}$ = jumlah kuadrat total.</p>

                <h3>Uji signifikansi ($t$-test)</h3>
                <div class="formula-box">
                    $$MSE = \frac{SS_{\text{res}}}{n - 2}, \quad
                    SE(\hat{\beta}) = \sqrt{\frac{MSE}{S_{xx}}}, \quad
                    t = \frac{\hat{\beta}}{SE(\hat{\beta})}$$
                </div>
                <p>$MSE$ = <i>mean squared error</i>; $SE(\hat{\beta})$ = <i>standard error</i> slope; $t$ = statistik uji. $|t| > t_{\text{kritis}}(df=n-2,\; \alpha=0{,}05)$ → <i>trend</i> signifikan.</p>

                <h3>Koefisien korelasi ($r$)</h3>
                <div class="formula-box">
                    $$r = \frac{S_{xy}}{\sqrt{S_{xx} \cdot S_{yy}}}$$
                </div>
                <p>$r$ = koefisien korelasi Pearson ($-1 \le r \le 1$); tanda $r$ mengikuti arah $\hat{\beta}$.</p>

                <h2>Hipotesis</h2>
                <ul>
                    <li>$H_0$: Tidak ada <i>trend</i> linear — $\beta = 0$</li>
                    <li>$H_1$: Terdapat <i>trend</i> linear — $\beta \ne 0$ (meningkat atau menurun)</li>
                </ul>
            </div>

            <!-- ==================== INTERPRETASI ==================== -->
            <div class="doc-section" id="sec-interpretasi">
                <h1>Interpretasi Hasil</h1>
                <p class="page-lead">Panduan membaca output yang ditampilkan di Peta Interaktif maupun halaman Olah Data.</p>

                <h2>Arah <i>Trend</i></h2>
                <div class="color-legend" style="margin-bottom:16px;">
                    <div class="color-legend-row"><span class="color-dot" style="background:#0B6E2F;"></span> <span><strong style="color:#0B6E2F;">Meningkat Signifikan</strong> — Warna gelap; bukti statistik kuat bahwa nilai data naik seiring waktu</span></div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#16A34A;"></span> <span><strong style="color:#16A34A;">Meningkat Tidak Signifikan</strong> — Warna terang; kenaikan terdeteksi namun belum cukup bukti pada tingkat kepercayaan 95%</span></div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#991B1B;"></span> <span><strong style="color:#991B1B;">Menurun Signifikan</strong> — Warna gelap; bukti statistik kuat bahwa nilai data turun seiring waktu</span></div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#DC2626;"></span> <span><strong style="color:#DC2626;">Menurun Tidak Signifikan</strong> — Warna terang; penurunan terdeteksi namun belum cukup bukti</span></div>
                    <div class="color-legend-row"><span class="color-dot" style="background:#6B7280;"></span> <span><strong style="color:#6B7280;">Tidak Ada <i>Trend</i></strong> — Tidak terdeteksi perubahan sistematis</span></div>
                </div>

                <h2>Nilai yang Ditampilkan per Metode</h2>
                <table class="stat-table">
                    <thead>
                        <tr><th>Metode</th><th>Nilai Output</th><th>Artinya</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td rowspan="2"><strong>Mann-Kendall</strong></td>
                            <td>Z uji</td>
                            <td>Nilai statistik Z yang dihitung; dibandingkan dengan Z kritis (±1,96)</td>
                        </tr>
                        <tr>
                            <td>Z kritis</td>
                            <td>Nilai batas signifikansi = ±1,96 pada $\alpha = 0{,}05$</td>
                        </tr>
                        <tr>
                            <td rowspan="3"><strong>Sen's Slope</strong></td>
                            <td>$Q_{\text{med}}$</td>
                            <td>Kemiringan median — besarnya perubahan per satuan waktu (mm/tahun)</td>
                        </tr>
                        <tr>
                            <td>$Q_{\min}$</td>
                            <td>Batas bawah interval kepercayaan 95%</td>
                        </tr>
                        <tr>
                            <td>$Q_{\max}$</td>
                            <td>Batas atas interval kepercayaan 95%</td>
                        </tr>
                        <tr>
                            <td rowspan="3"><strong>Regresi Linear</strong></td>
                            <td>$t$ uji</td>
                            <td>Nilai statistik $t$; dibandingkan dengan $t$ kritis</td>
                        </tr>
                        <tr>
                            <td>$t$ kritis</td>
                            <td>Nilai batas dari tabel $t$-distribusi pada $df = n-2$ dan $\alpha = 0{,}05$</td>
                        </tr>
                        <tr>
                            <td>$R^2$</td>
                            <td>Proporsi variasi data yang dapat dijelaskan oleh waktu (0–1)</td>
                        </tr>
                    </tbody>
                </table>

                <h2>Perbandingan Antar Metode</h2>
                <table class="stat-table">
                    <thead>
                        <tr><th>Skenario</th><th>Interpretasi</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ketiga metode sepakat signifikan</td>
                            <td><strong>Keyakinan tinggi</strong> — <i>trend</i> sangat mungkin nyata</td>
                        </tr>
                        <tr>
                            <td>Mann-Kendall dan Sen's Slope sepakat; Regresi Linear berbeda</td>
                            <td>Kemungkinan ada <i>outlier</i> yang mempengaruhi regresi. Hasil non-parametrik lebih andal.</td>
                        </tr>
                        <tr>
                            <td>Hanya Regresi Linear yang signifikan</td>
                            <td>Data kemungkinan pendek (&lt; 10 titik). Regresi punya <i>power</i> lebih tinggi pada sampel kecil, namun bergantung asumsi normalitas.</td>
                        </tr>
                        <tr>
                            <td>Semua tidak signifikan / tidak ada <i>trend</i></td>
                            <td>Tidak terdeteksi perubahan sistematis. Data mungkin terlalu pendek atau variabilitas terlalu tinggi.</td>
                        </tr>
                    </tbody>
                </table>

                <h2>Ketersediaan Data</h2>
                <ul>
                    <li><strong>Baik (≥ 80%):</strong> Data cukup lengkap, hasil dapat diandalkan</li>
                    <li><strong>Cukup (50–79%):</strong> Data cukup, interpretasi perlu hati-hati</li>
                    <li><strong>Kurang (&lt; 50%):</strong> Data banyak hilang, hasil mungkin tidak representatif</li>
                </ul>
            </div>

            <!-- ==================== FAQ ==================== -->
            <div class="doc-section" id="sec-faq">
                <h1>FAQ</h1>
                <p class="page-lead">Pertanyaan yang sering diajukan.</p>

                <div class="faq-item">
                    <h3 class="faq-q">Apa perbedaan Mann-Kendall dan Regresi Linear?</h3>
                    <div class="faq-a">
                        Mann-Kendall adalah uji non-parametrik yang mendeteksi <i>trend</i> monoton tanpa asumsi distribusi normal — lebih <em>robust</em> terhadap <i>outlier</i>. Regresi Linear adalah uji parametrik yang mengasumsikan hubungan linear dan residual normal, namun memberikan informasi tambahan berupa $R^2$. Untuk data hidrologi, Mann-Kendall dan Sen's Slope umumnya lebih direkomendasikan.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Mengapa hasil Mann-Kendall dan Regresi Linear bisa berbeda?</h3>
                    <div class="faq-a">
                        Perbedaan bisa terjadi karena: (1) adanya <i>outlier</i> yang memengaruhi regresi, (2) hubungan non-linear yang tidak tertangkap regresi, (3) asumsi normalitas tidak terpenuhi, atau (4) sampel terlalu kecil. Dalam kasus perbedaan, hasil non-parametrik (Mann-Kendall / Sen's Slope) umumnya lebih <em>reliable</em>.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Berapa minimal jumlah data yang diperlukan?</h3>
                    <div class="faq-a">
                        Platform memerlukan minimal <strong>3 titik data</strong> untuk menjalankan perhitungan. Namun, untuk hasil yang bermakna, direkomendasikan minimal <strong>10–15 tahun</strong> data tahunan. WMO merekomendasikan <strong>30 tahun</strong> untuk kajian klimatologi.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Apa arti tingkat kepercayaan 95%?</h3>
                    <div class="faq-a">
                        Tingkat kepercayaan 95% ($\alpha = 0{,}05$) berarti ada 5% kemungkinan bahwa <i>trend</i> yang terdeteksi adalah <i>false positive</i> — yaitu kita menyimpulkan ada <i>trend</i> padahal sebenarnya tidak. Atau: jika studi diulang 100 kali dengan data acak tanpa <i>trend</i>, sekitar 5 kali akan terdeteksi "signifikan" secara kebetulan.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Mengapa data perlu diagregasi (bulanan/tahunan/musiman)?</h3>
                    <div class="faq-a">
                        Data curah hujan harian memiliki variabilitas sangat tinggi sehingga <i>trend</i> jangka panjang sulit terdeteksi di tengah <i>noise</i>. Agregasi (penjumlahan atau rata-rata per bulan/tahun/musim) meredam <i>noise</i> dan memperkuat sinyal <i>trend</i>.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Apa arti tanda ⚠ pada marker peta?</h3>
                    <div class="faq-a">
                        Menandakan stasiun memiliki panjang data kurang dari 16 tahun. Periode data yang pendek mengurangi keandalan uji statistik, sehingga hasil perlu diinterpretasi dengan lebih hati-hati.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Apakah data saya aman saat diunggah ke Olah Data?</h3>
                    <div class="faq-a">
                        Ya. Data dikirim ke server hanya untuk diproses (perhitungan statistik) dan <strong>tidak disimpan</strong> setelah hasil ditampilkan. Untuk keamanan optimal, pastikan koneksi menggunakan HTTPS.
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">Bagaimana cara menangani nilai hilang dalam data?</h3>
                    <div class="faq-a">
                        Baris dengan nilai kosong atau tidak valid dilewati otomatis. Indikator Ketersediaan Data akan menunjukkan persentase kelengkapan data pada periode yang dipilih, sehingga Anda dapat menilai seberapa besar pengaruh data hilang terhadap hasil.
                    </div>
                </div>
            </div>

            <!-- ==================== GLOSARIUM ==================== -->
            <div class="doc-section" id="sec-glosarium">
                <h1>Glosarium</h1>
                <p class="page-lead">Istilah teknis yang muncul di antarmuka TrendHidro.</p>

                <div class="glos-list">
                    <div class="glos-row">
                        <h3 class="glos-term">Agregasi</h3>
                        <div class="glos-def">Proses meringkas data harian menjadi nilai bulanan, tahunan, atau musiman menggunakan fungsi SUM, AVG, MAX, atau MIN.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">IQR</h3>
                        <div class="glos-def"><i>Interquartile Range</i> — selisih antara kuartil ketiga (Q3) dan kuartil pertama (Q1). Digunakan untuk mendeteksi <i>outlier</i>: nilai di luar $[Q1 - 1{,}5 \times IQR,\; Q3 + 1{,}5 \times IQR]$ dianggap <i>outlier</i>.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term"><i>Outlier</i></h3>
                        <div class="glos-def">Pencilan — nilai data yang jauh dari pola umum. Dapat memengaruhi hasil regresi linear secara signifikan; Mann-Kendall dan Sen's Slope lebih tahan terhadapnya.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">$R^2$</h3>
                        <div class="glos-def">Koefisien determinasi dari Regresi Linear — proporsi variasi data yang dapat dijelaskan oleh model. Rentang 0–1; semakin mendekati 1 semakin baik kecocokan model.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term"><i>Slope</i> ($\hat{\beta}$)</h3>
                        <div class="glos-def">Kemiringan garis <i>trend</i> — besarnya perubahan nilai per satuan waktu (misalnya mm/tahun). Positif berarti meningkat, negatif berarti menurun.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">Signifikan</h3>
                        <div class="glos-def">Hasil uji statistik yang menolak hipotesis nol ($H_0$) pada tingkat kepercayaan 95% — artinya ada bukti kuat bahwa <i>trend</i> bukan sekadar variasi acak.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term"><i>Time series</i></h3>
                        <div class="glos-def">Runtut waktu — data yang dikumpulkan secara berurutan dalam interval waktu yang teratur (misalnya curah hujan harian selama beberapa dekade).</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term"><i>Trend</i></h3>
                        <div class="glos-def">Kecenderungan sistematis nilai data untuk naik atau turun secara monoton seiring berjalannya waktu. Bukan fluktuasi musiman, melainkan perubahan jangka panjang.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">Z kritis / t kritis</h3>
                        <div class="glos-def">Nilai batas distribusi yang digunakan untuk menentukan signifikansi. Pada $\alpha = 0{,}05$: Z kritis = ±1,96; t kritis bergantung pada derajat bebas ($df = n-2$).</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">$Q_{\text{med}}$</h3>
                        <div class="glos-def">Median kemiringan (Sen's Slope) — estimasi robust besarnya perubahan per satuan waktu.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">$Q_{\min}$, $Q_{\max}$</h3>
                        <div class="glos-def">Batas bawah dan atas interval kepercayaan 95% dari Sen's Slope. Jika keduanya bertanda sama, <i>trend</i> dinyatakan signifikan.</div>
                    </div>
                    <div class="glos-row">
                        <h3 class="glos-term">Ketersediaan Data</h3>
                        <div class="glos-def">Persentase titik data yang tersedia dibandingkan total yang diharapkan pada periode dan tipe data terpilih. Hijau ≥ 80%, Kuning 50–79%, Merah &lt; 50%.</div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Floating TOC -->
        <aside class="docs-toc" id="docsToc">
            <h5>Daftar Isi</h5>
            <nav id="tocNav"></nav>
        </aside>
    </div>

    <script>
        // ===== Build TOC =====
        function buildToc() {
            const nav = document.getElementById('tocNav');
            if (!nav) return;
            const active = document.querySelector('.doc-section.active');
            if (!active) { nav.innerHTML = ''; return; }
            let headings = active.querySelectorAll('h1, h2, h3');
            // skip the very first h1 (page title) if h2/h3 exist
            const hasSub = active.querySelectorAll('h2, h3').length > 0;
            if (hasSub) headings = active.querySelectorAll('h2, h3');
            if (!headings.length) { nav.innerHTML = ''; return; }
            let html = '';
            headings.forEach(h => {
                const text = h.textContent.trim();
                const id = h.id || text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
                h.id = h.id || id;
                const cls = h.tagName === 'H3' ? 'toc-h3' : h.tagName === 'H1' ? 'toc-h1' : '';
                html += `<a href="#${h.id}" class="${cls}">${h.innerHTML}</a>`;
            });
            nav.innerHTML = html;

            // smooth scroll
            nav.querySelectorAll('a').forEach(a => {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.getElementById(this.getAttribute('href').slice(1));
                    if (target) {
                        nav.querySelectorAll('a').forEach(x => x.classList.remove('toc-active'));
                        this.classList.add('toc-active');
                        window._tocClicked = true;
                        setTimeout(() => { window._tocClicked = false; }, 200);
                        target.scrollIntoView({ behavior: 'instant', block: 'start' });
                    }
                });
            });
        }

        // ===== Scroll spy =====
        let tocScrollFn = null;
        function watchToc() {
            if (tocScrollFn) { tocScrollFn(); tocScrollFn = null; }
            const active = document.querySelector('.doc-section.active');
            if (!active) return;
            const headings = active.querySelectorAll('h1, h2, h3');
            if (!headings.length) return;
            const nav = document.getElementById('tocNav');
            if (!nav) return;

            function findActive() {
                if (window._tocClicked) return;
                let closest = null, min = Infinity;
                headings.forEach(h => {
                    const top = h.getBoundingClientRect().top;
                    const dist = Math.abs(top - 80);
                    if (dist < min) { min = dist; closest = h; }
                });
                if (closest) {
                    nav.querySelectorAll('a').forEach(a => {
                        a.classList.toggle('toc-active', a.getAttribute('href') === '#' + closest.id);
                    });
                }
            }

            let ticking = false;
            const onScroll = () => {
                if (!ticking) {
                    requestAnimationFrame(() => { findActive(); ticking = false; });
                    ticking = true;
                }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            tocScrollFn = () => window.removeEventListener('scroll', onScroll);
            // delay initial check so toc click can take effect
            requestAnimationFrame(() => { if (window._tocClicked) return; findActive(); });
        }

        // ===== Section switching =====
        function showSection(id, el) {
            document.querySelectorAll('.doc-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.docs-sidebar a').forEach(a => a.classList.remove('active'));
            const sec = document.getElementById('sec-' + id);
            if (sec) sec.classList.add('active');
            if (el) el.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            buildToc();
            watchToc();
        }

        // ===== Navbar dropdown =====
        const dropBtn = document.getElementById('nav-fitur');
        if (dropBtn) {
            dropBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                this.closest('.nav-dropdown').classList.toggle('open');
            });
        }
        document.addEventListener('click', () => {
            document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.remove('open'));
        });

        // ===== Hash-based routing on load =====
        (function () {
            const hash = location.hash.replace('#', '');
            if (hash) {
                const link = document.querySelector(`.docs-sidebar a[href="#${hash}"]`);
                if (link) showSection(hash, link);
            }
            buildToc();
            watchToc();
        })();
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>