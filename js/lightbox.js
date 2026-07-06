/**
 * TrendHidro - lightbox.js
 * Modal inspeksi data stasiun
 */

// ====== TRACKING UNTUK LIVE UPDATE PANJANG DATA ======
let currentLightboxStationId = null;

function updateLightboxLength(stationId) {
    if (currentLightboxStationId !== stationId) return;
    if (!currentDASData || !currentDASData.features) return;
    const feat = currentDASData.features.find(f => f.properties.id === stationId);
    if (!feat) return;
    const p = feat.properties;
    if (p.yearStart && p.yearEnd) {
        document.getElementById('lbRange').textContent = `${p.yearStart}—${p.yearEnd}`;
        document.getElementById('lbLength').textContent = `${p.yearEnd - p.yearStart + 1} tahun`;
        document.getElementById('lightboxDetailBtn').disabled = false;
    }
}

// ====== BUKA LIGHTBOX ======
function openLightbox(stationData) {
    const overlay = document.getElementById('lightboxOverlay');
    const detailBtn = document.getElementById('lightboxDetailBtn');

    currentLightboxStationId = stationData.id;
    detailBtn.disabled = true;

    // Reset confirm mode jika sebelumnya aktif
    const cs = document.getElementById('confirmSection');
    if (cs) {
        cs.style.display = 'none';
        Array.from(document.getElementById('lightboxBody').children).forEach(el => {
            if (el.id !== 'confirmSection') el.style.display = '';
        });
        document.getElementById('lightboxCloseBtn').style.display = '';
        detailBtn.style.display = '';
        document.getElementById('confirmNo').style.display = 'none';
        document.getElementById('confirmYes').style.display = 'none';
    }

    // Helper untul format minus typografi
    const fM = (val) => val === undefined || val === null ? '—' : String(val).replace('-', '−');

    // Isi data
    document.getElementById('lightboxTitle').textContent = "Pos " + stationData.name;
    document.getElementById('lbLocation').textContent = (stationData.location || '—').toLowerCase().replace(/\b\w/g, c => c.toUpperCase());

    // Rentang Data (berdasarkan tahun pertama & terakhir rain non-null)
    let yearStart = stationData.yearStart;
    let yearEnd = stationData.yearEnd;
    if (yearStart && yearEnd) {
        document.getElementById('lbRange').textContent = `${yearStart}—${yearEnd}`;
        document.getElementById('lbLength').textContent = `${yearEnd - yearStart + 1} tahun`;
        detailBtn.disabled = false;
    } else {
        document.getElementById('lbRange').textContent = 'Menghitung...';
        document.getElementById('lbLength').textContent = 'Menghitung...';
    }

    // Nilai Trend (Jika metode aktif)
    const trendLabel = document.getElementById('lbTrendLabel');
    const trendValue = document.getElementById('lbTrendValue');

    trendLabel.textContent = "Nilai Trend";
    trendValue.textContent = "—";

    if (typeof currentMethod !== 'undefined' && currentMethod && stationData.trendData) {
        const trend = stationData.trendData[currentMethod];
        if (trend) {
            trendLabel.parentElement.style.display = "flex";
            if (currentMethod === 'mann-kendall') {
                trendLabel.textContent = "Uji Mann-Kendall";
                trendValue.textContent = `Z: ${fM(Number(trend.Z).toFixed(3))}`;
            } else if (currentMethod === 'sens-slope') {
                trendLabel.textContent = "Sen's Slope";
                trendValue.textContent = `Slope: ${fM(Number(trend.slope).toFixed(3))}`;
            } else if (currentMethod === 'regresi-linear') {
                trendLabel.textContent = "Regresi Linear";
                trendValue.textContent = `Slope: ${fM(Number(trend.slope).toFixed(3))}`; // rSquared tidak disuplai bulk
            }
        } else {
            trendLabel.parentElement.style.display = "none";
        }
    } else {
        trendLabel.parentElement.style.display = "none";
    }

    document.getElementById('lbCoords').textContent =
        `${fM(stationData.lat.toFixed(4))}, ${fM(stationData.lon.toFixed(4))}`;
    document.getElementById('lbManager').textContent = stationData.manager || '—';

    // Fetch Elevation
    const lat = stationData.lat;
    const lon = stationData.lon;
    document.getElementById('lbElevation').textContent = "Memuat...";
    fetch(`php/proxy_maptiler.php?type=elevation&lon=${lon}&lat=${lat}`)
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0 && data[0].length >= 3) {
                document.getElementById('lbElevation').textContent = `${data[0][2]} mdpl`;
            } else {
                document.getElementById('lbElevation').textContent = "Tidak tersedia";
            }
        })
        .catch(err => {
            console.error('Error fetching elevation:', err);
            document.getElementById('lbElevation').textContent = "Gagal memuat";
        });

    // Simpan ID stasiun untuk tombol detail
    document.getElementById('lightboxDetailBtn').dataset.stationId = stationData.id;

    // Tampilkan
    overlay.classList.add('active');
}

// ====== TUTUP LIGHTBOX ======
function closeLightbox() {
    currentLightboxStationId = null;
    document.getElementById('lightboxOverlay').classList.remove('active');
}

// ====== EVENT LISTENERS ======
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('lightboxOverlay');
    const closeBtn = document.getElementById('lightboxClose');
    const closeBtnFooter = document.getElementById('lightboxCloseBtn');
    const detailBtn = document.getElementById('lightboxDetailBtn');

    // Tutup via tombol ✕
    closeBtn.addEventListener('click', closeLightbox);
    closeBtnFooter.addEventListener('click', closeLightbox);

    // Tutup via klik overlay
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeLightbox();
        }
    });

    // Tutup via Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });

    const confirmYes = document.getElementById('confirmYes');
    const confirmNo = document.getElementById('confirmNo');
    const confirmSection = document.getElementById('confirmSection');
    const lightboxBody = document.getElementById('lightboxBody');
    const lightboxFooter = document.getElementById('lightboxFooter');
    let isConfirmMode = false;

    function openDetailPage(stationId, params) {
        const dtType = params.dataType || 'tahunan';
        const agg = params.aggregation || 'rerata';
        const mo = params.month || 'all';
        const yF = params.yearFrom || '';
        const yT = params.yearTo || '';
        window.open(`detail?id=${stationId}&dtType=${dtType}&agg=${agg}&mo=${mo}&yF=${yF}&yT=${yT}`, '_blank');
    }

    function setConfirmMode(enable, msg) {
        isConfirmMode = enable;
        Array.from(lightboxBody.children).forEach(el => {
            if (el.id !== 'confirmSection') {
                el.style.display = enable ? 'none' : '';
            }
        });
        confirmSection.style.display = enable ? 'block' : 'none';
        if (enable && msg) {
            document.getElementById('confirmTitle').textContent = 'Perhatian';
            document.getElementById('confirmMessage').innerHTML = msg;
        }
        document.getElementById('lightboxCloseBtn').style.display = enable ? 'none' : '';
        detailBtn.style.display = enable ? 'none' : '';
        confirmNo.style.display = enable ? '' : 'none';
        confirmYes.style.display = enable ? '' : 'none';
    }

    confirmYes.addEventListener('click', () => {
        const stationId = detailBtn.dataset.stationId;
        if (stationId) {
            const params = typeof getCurrentParams === 'function' ? getCurrentParams() : {};
            openDetailPage(stationId, params);
        }
        setConfirmMode(false);
    });

    confirmNo.addEventListener('click', closeLightbox);

    // Tombol Detail → halaman detail (Bawa Paremeter Filter)
    detailBtn.addEventListener('click', () => {
        const stationId = detailBtn.dataset.stationId;
        if (!stationId) return;

        const params = typeof getCurrentParams === 'function' ? getCurrentParams() : {};

        // Cari data stasiun
        let years = 0;
        let dataAvail = false;
        if (typeof currentDASData !== 'undefined' && currentDASData && currentDASData.features) {
            const feat = currentDASData.features.find(f => f.properties.id === stationId);
            if (feat) {
                const p = feat.properties;
                if (p.yearStart && p.yearEnd) {
                    years = p.yearEnd - p.yearStart + 1;
                    dataAvail = true;
                }
            }
        }

        if (dataAvail && years < 16) {
            setConfirmMode(true,
                `Stasiun ini hanya memiliki data <strong>${years} tahun</strong>. Analisis tren dengan data kurang dari 16 tahun mungkin kurang <i>reliable</i>. Tetap lakukan pengolahan <i>trend</i> data runtut waktu?`
            );
        } else if (!dataAvail) {
            setConfirmMode(true,
                `Stasiun ini hanya memiliki data <strong>${years} tahun</strong>. Tetap lakukan pengolahan <i>trend</i> data runtut waktu?`
            );
        } else {
            openDetailPage(stationId, params);
        }
    });
});
