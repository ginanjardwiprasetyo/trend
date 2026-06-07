/**
 * TrendHidro - app.js
 * Inisialisasi peta Leaflet dan konfigurasi dasar
 */

// ====== VARIABEL GLOBAL ======
let map;
let baseLayers = {};
let dasLayer = null;
let stationMarkers = [];
let currentDASData = null;
let currentMethod = null;

// ====== INISIALISASI PETA ======
function initMap() {
    // Peta centered di Indonesia, zoom 5
    map = L.map('map', {
        center: [3, 120],
        zoom: 5,
        zoomControl: false,
        attributionControl: true
    });

    // Kontrol zoom di kiri bawah
    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    // Base layers
    baseLayers['OpenStreetMap'] = L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19,
        crossOrigin: true
    }
    );

    baseLayers['ESRI Gray'] = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri',
        maxZoom: 16,
        crossOrigin: true
    });

    baseLayers['Topo v4'] = L.tileLayer(
        'php/proxy_maptiler_tiles.php?z={z}&x={x}&y={y}', {
        attribution: '&copy; MapTiler',
        maxZoom: 18,
        crossOrigin: true
    });

    // Default: OpenStreetMap
    baseLayers['OpenStreetMap'].addTo(map);


    // Layer control
    L.control.layers(baseLayers, null, {
        position: 'bottomleft',
        collapsed: true
    }).addTo(map);
}

// ====== MUAT GEOJSON DAS & API STASIUN ======
async function loadDAS(dasName) {
    try {
        showLoading(true);
        clearDAS();

        // 1. Muat batas DAS (Poligon) — file lokal, cepat
        const response = await fetch(`geojson_data/geojson/${dasName}.geojson`);
        if (!response.ok) throw new Error('Gagal memuat batas DAS');
        const boundaryData = await response.json();

        if (boundaryData.features && boundaryData.features.length > 0) {
            dasLayer = L.geoJSON(boundaryData, {
                style: {
                    color: '#2563EB',
                    weight: 2.5,
                    fillColor: '#DBEAFE',
                    fillOpacity: 0.15,
                    dashArray: '6 4'
                }
            }).addTo(map);

            map.fitBounds(dasLayer.getBounds(), { padding: [50, 50] });
        }

        // 2. Fetch Stasiun LITE (hanya tabel pos_hujan, skip query berat data_ch)
        const apiRes = await fetch("php/get_stations.php?lite=1");
        if (!apiRes.ok) {
            try {
                const errJson = await apiRes.json();
                if (errJson && errJson.error) {
                    throw new Error(errJson.message + (errJson.details ? " - " + errJson.details : ""));
                }
            } catch (jsonErr) {
                if (jsonErr.message && !jsonErr.message.includes('JSON')) {
                    throw jsonErr;
                }
            }
            throw new Error('Gagal memuat Data Stasiun (HTTP ' + apiRes.status + ')');
        }
        const stations = await apiRes.json();

        if (stations.error) {
            throw new Error(stations.message + (stations.details ? " - " + stations.details : ""));
        }

        if (!Array.isArray(stations)) {
            throw new Error("Format respons stasiun tidak didukung.");
        }

        currentDASData = { features: stations };

        // 3. Render Marker dan Dropdown — tampilan langsung muncul
        renderStationMarkers(stations);
        populateYearDropdowns(stations);

        // 4. SEMBUNYIKAN LOADER — peta sudah siap digunakan
        showLoading(false);

        // 5. Jeda sedikit agar UI responsif sebelum proses background dimulai
        setTimeout(() => {
            // 6. Background: fetch statistik lengkap (rentang data, kelengkapan) dari data_ch
            fetchStationStats();

            // 7. Background: hitung analytics tanpa memblokir UI
            fetchAnalytics(null, true);
        }, 500);
    } catch (error) {
        console.error('Error memuat data:', error);
        alert('Gagal memuat data: ' + error.message);
        showLoading(false);
    }
}

// ====== FETCH STATISTIK STASIUN (BACKGROUND) ======
async function fetchStationStats() {
    try {
        const res = await fetch("php/get_stations.php");
        if (!res.ok) return;
        const fullStations = await res.json();
        if (!Array.isArray(fullStations)) return;

        // Update data stasiun yang sudah ada di memori dengan statistik lengkap
        const stationMap = {};
        fullStations.forEach(s => {
            stationMap[s.properties.id] = s.properties;
        });

        if (currentDASData && currentDASData.features) {
            currentDASData.features.forEach(f => {
                const fullData = stationMap[f.properties.id];
                if (fullData) {
                    f.properties.yearStart = fullData.yearStart;
                    f.properties.yearEnd = fullData.yearEnd;
                    f.properties.completeness = fullData.completeness;
                    f.properties.river = fullData.river;
                }
            });

            // Update dropdown tahun dengan data riil
            populateYearDropdowns(currentDASData.features);
        }
    } catch (e) {
        console.error('Background stats fetch gagal:', e);
    }
}

// ====== HAPUS DAS ======
function clearDAS() {
    if (dasLayer) {
        map.removeLayer(dasLayer);
        dasLayer = null;
    }
    clearStationMarkers();
    currentDASData = null;
    currentMethod = null;
    hideLegend();
    resetMethodToggles();
}

// ====== POPULATE DROPDOWN TAHUN ======
// Variabel untuk melacak dekade yang sedang dilihat di picker
window.viewedDecadeFrom = 1980;
window.viewedDecadeTo = 2020;

function populateYearDropdowns(stations) {
    let minYear = Infinity;
    let maxYear = -Infinity;

    stations.forEach(s => {
        const ys = s.properties.yearStart;
        const ye = s.properties.yearEnd;
        if (ys && ys > 1800 && ys < minYear) minYear = ys;
        if (ye && ye > 1800 && ye > maxYear) maxYear = ye;
    });

    if (minYear === Infinity) minYear = 1980;
    if (maxYear === -Infinity) maxYear = 2025;

    window.stationMinYear = minYear;
    window.stationMaxYear = maxYear;

    const gridFrom = document.getElementById('gridFrom');
    const gridTo = document.getElementById('gridTo');

    if (!gridFrom || !gridTo) return;

    // Set awal dilihat berdasarkan nilai input atau min/max
    const valFrom = parseInt(document.getElementById('yearFrom').value) || 1980;
    const valTo = parseInt(document.getElementById('yearTo').value) || 2025;

    window.viewedDecadeFrom = Math.floor(valFrom / 10) * 10;
    window.viewedDecadeTo = Math.floor(valTo / 10) * 10;

    renderDecadeGrid('From', window.viewedDecadeFrom);
    renderDecadeGrid('To', window.viewedDecadeTo);

    document.getElementById('displayFrom').textContent = valFrom;
    document.getElementById('displayTo').textContent = valTo;
}

/**
 * Render grid tahun untuk dekade tertentu
 * @param {string} idSuffix 'From' atau 'To'
 * @param {number} startYear Tahun awal dekade (misal 1980)
 */
function renderDecadeGrid(idSuffix, startYear) {
    const gridElem = document.getElementById(`grid${idSuffix}`);
    if (!gridElem) return;

    const decadeStart = Math.floor(startYear / 10) * 10;
    const decadeEnd = decadeStart + 9;
    const activeYear = document.getElementById(`year${idSuffix}`).value;

    let yearsHtml = '';
    // Buat grid 12 tahun (3x4) agar lebih proporsional, atau cukup 10 tahun (3x3 + 1)
    // Di sini kita tampilkan 12 tahun agar grid 3 kolom penuh
    for (let i = 0; i < 12; i++) {
        const y = decadeStart + i;
        const isDisabled = (y < window.stationMinYear || y > window.stationMaxYear);
        const isActive = (y == activeYear);

        // Sembunyikan jika diluar dekade (opsional, tapi user minta 1980-1989)
        // Kita tampilkan saja 12 tahun tapi yang diluar 0-9 kita beri style berbeda atau sembunyikan
        if (i > 9) {
            yearsHtml += `<div class="year-item hidden" data-year="${y}">${y}</div>`;
        } else {
            yearsHtml += `<div class="year-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}" data-year="${y}">${y}</div>`;
        }
    }

    gridElem.innerHTML = `
        <div class="year-grid-header">
            <button type="button" class="year-nav-btn prev" data-target="${idSuffix}" title="Dekade Sebelumnya">‹</button>
            <span class="range-text">${decadeStart} — ${decadeEnd}</span>
            <button type="button" class="year-nav-btn next" data-target="${idSuffix}" title="Dekade Berikutnya">›</button>
        </div>
        <div class="year-grid-content p-2">
            <div class="decade-grid">
                ${yearsHtml}
            </div>
        </div>
    `;
}

function updatePickerActiveState() {
    renderDecadeGrid('From', window.viewedDecadeFrom);
    renderDecadeGrid('To', window.viewedDecadeTo);
}

// ====== LOADING ======
let hideTimeout = null;
/**
 * @param {boolean} show - Tampilkan atau sembunyikan loader
 * @param {boolean} isFull - Jika true, gunakan overlay full-screen (blocking). Jika false, hanya spinner sidebar.
 */
function showLoading(show, isFull = true) {
    const fullLoader = document.getElementById('fullLoader');
    const methodLoader = document.getElementById('methodLoader');

    // 1. Loader Layar Penuh (Blocking)
    if (fullLoader && (isFull || !show)) {
        if (show) {
            if (hideTimeout) {
                clearTimeout(hideTimeout);
                hideTimeout = null;
            }
            fullLoader.classList.remove('hidden');
            void fullLoader.offsetWidth;
            fullLoader.classList.add('show');
        } else {
            fullLoader.classList.remove('show');
            hideTimeout = setTimeout(() => {
                if (!fullLoader.classList.contains('show')) {
                    fullLoader.classList.add('hidden');
                }
                hideTimeout = null;
            }, 300);
        }
    }

    // 2. Loader Sektoral (Pilih Metode) - Menggantikan fungsi sidebar spinner lama
    if (methodLoader) {
        if (show) methodLoader.classList.add('active');
        else methodLoader.classList.remove('active');
    }
}

async function fetchAnalytics(params = null, isInitial = false) {
    if (!currentDASData) return;

    // Tampilkan loader: sidebar-only untuk interaksi parameter agar tidak memblokir UI
    if (params === null && !isInitial) {
        showLoading(true, false); // Gunakan non-blocking loader
        // Beri waktu sedikit bagi browser untuk me-render spinner sebelum fetch
        await new Promise(resolve => setTimeout(resolve, 50));
    } else if (isInitial) {
        // Tampilkan sidebar spinner untuk background initial fetch
        showLoading(true, false);
    }
    const { dataType, aggregation, yearFrom, yearTo } = params || getCurrentParams();

    // Pastikan valid
    if (!yearFrom || !yearTo || yearFrom > yearTo) return;

    try {
        const payload = { dataType, aggregation, yearFrom, yearTo, month: params?.month || getCurrentParams().month };
        const response = await fetch('php/analyze_all.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success && result.results) {
            // Pasang ke memori properties stasiun
            const stations = currentDASData.features.filter(f => f.properties.type === 'station');
            stations.forEach(st => {
                const posId = st.properties.id;
                if (result.results[posId]) {
                    // Simpan berdasarkan dataType
                    if (!st.properties.trendDataCache) st.properties.trendDataCache = {};
                    st.properties.trendDataCache[dataType] = result.results[posId];

                    // Jika ini dataType yang sedang aktif, update trendData untuk backward compatibility
                    const currentActiveType = document.querySelector('#dataTypeGroup .radio-btn.active')?.dataset.value || 'tahunan';
                    if (dataType === currentActiveType) {
                        st.properties.trendData = result.results[posId];
                    }
                }
            });

            const currentActiveType = document.querySelector('#dataTypeGroup .radio-btn.active')?.dataset.value || 'tahunan';
            if (currentMethod && dataType === currentActiveType) runAnalysis(currentMethod);
        }

        // Sembunyikan loader
        if (params === null && !isInitial) {
            showLoading(false);
        } else if (isInitial) {
            showLoading(false);
        }

        // --- LOGIKA BACKGROUND CHAIN ---
        // Jika pemanggilan ini adalah pemanggilan awal (params === null),
        // jalankan background fetch untuk skala lainnya secara sekuensial agar tidak membebani server
        if (params === null) {
            const scales = ['bulanan'].filter(s => s !== dataType);
            for (const scale of scales) {
                setTimeout(() => {
                    fetchAnalytics({ dataType: scale, aggregation, yearFrom, yearTo, month: params?.month || getCurrentParams().month });
                }, 2000); // Jeda 2 detik antar kategori
            }
        }

    } catch (e) {
        console.error(`Background analytics gagal untuk ${dataType}:`, e);
        if (isInitial) {
            showLoading(false);
        }
    }
}

// ====== JALANKAN ANALISIS TREN (Membaca Cache) ======
async function runAnalysis(method) {
    if (!currentDASData) {
        alert('Silakan pilih Wilayah Sungai terlebih dahulu.');
        return;
    }

    const dataType = document.querySelector('#dataTypeGroup .radio-btn.active')?.dataset.value || 'tahunan';
    const stations = currentDASData.features.filter(f => f.properties.type === 'station');

    const results = [];

    for (const station of stations) {
        const id = station.properties.id;
        // Ambil data dari cache untuk scale yang aktif
        const trendData = (station.properties.trendDataCache && station.properties.trendDataCache[dataType])
            ? station.properties.trendDataCache[dataType]
            : (station.properties.trendData || {});

        const methodData = trendData[method];
        if (methodData) {
            results.push({
                id: id,
                trend: methodData.trend || 'Tidak Ada Trend',
                details: methodData,
                completeness: trendData.completeness_period !== undefined ? trendData.completeness_period : 0
            });
        } else {
            results.push({
                id: id,
                trend: 'Tidak Ada Trend',
                message: 'Data tidak tersedia',
                completeness: 0
            });
        }
    }

    updateTrendMarkers(results);
    showLegend();
}



// ====== LEGENDA ======
function showLegend(method = null) {
    const legendBox = document.getElementById('legendBox');
    if (!legendBox) return;

    const activeMethod = method || currentMethod;
    if (!activeMethod) return;

    // Item Legend umum (Kualitas Data)
    const t16 = document.getElementById('toggleHide16');
    const hide16 = t16 ? t16.classList.contains('on') : false;

    let qualityLegend = '';
    // Hanya munculkan legenda "Data < 16 tahun" jika filternya NONAKTIF (data masih tampil di peta)
    if (!hide16) {
        qualityLegend = `
            <div class="legend-divider" style="height:1px; background:var(--color-border); margin:4px 0;"></div>
            <div class="legend-item">
                <span class="legend-icon" style="position:relative;">
                    <div style="width:16px; height:16px; background:#FACC15; color:#B91C1C; border:1px solid #B91C1C; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:900;">!</div>
                </span>
                <span>Data kurang dari 16 tahun</span>
            </div>
        `;
    }

    // Semua metode sekarang punya signifikansi
    legendBox.innerHTML = `
        <div class="legend-item"><span class="legend-icon" style="color:#0B6E2F;">▲</span><span>Meningkat (Signifikan)</span></div>
        <div class="legend-item"><span class="legend-icon" style="color:#16A34A;">▲</span><span>Meningkat (Tidak Signifikan)</span></div>
        <div class="legend-item"><span class="legend-icon" style="color:#991B1B;">▼</span><span>Menurun (Signifikan)</span></div>
        <div class="legend-item"><span class="legend-icon" style="color:#DC2626;">▼</span><span>Menurun (Tidak Signifikan)</span></div>
        <div class="legend-item"><span class="legend-icon neutral">—</span><span>Tidak Ada Tren</span></div>
        ${qualityLegend}
    `;

    legendBox.classList.add('visible');
}

function hideLegend() {
    document.getElementById('legendBox').classList.remove('visible');
}



// ====== INIT ======
document.addEventListener('DOMContentLoaded', () => {
    initMap();
    // Otomatis memuat Wilayah Sungai Bengawan Solo saat pertama kali aplikasi dibuka
    loadDAS('bbwsbs');

    // Mobile Navbar Hamburger Logic
    const navbar = document.getElementById('navbar');
    if (navbar) {
        // Create hamburger button if it doesn't exist
        if (!document.getElementById('mobile-menu-btn')) {
            const btn = document.createElement('button');
            btn.id = 'mobile-menu-btn';
            btn.className = 'mobile-menu-btn';
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            `;
            // Insert after navbar brand
            const brand = navbar.querySelector('.navbar-brand');
            if (brand) {
                brand.insertAdjacentElement('afterend', btn);
            } else {
                navbar.prepend(btn);
            }

            const navList = navbar.querySelector('.navbar-nav');
            btn.addEventListener('click', () => {
                if (navList) navList.classList.toggle('show');
            });
        }
    }
});
