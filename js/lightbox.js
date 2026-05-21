/**
 * TrendHidro - lightbox.js
 * Modal inspeksi data stasiun
 */

// ====== BUKA LIGHTBOX ======
function openLightbox(stationData) {
    const overlay = document.getElementById('lightboxOverlay');

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
    } else {
        document.getElementById('lbRange').textContent = 'Tidak tersedia';
        document.getElementById('lbLength').textContent = '—';
    }

    // Nilai Tren (Jika metode aktif)
    const trendLabel = document.getElementById('lbTrendLabel');
    const trendValue = document.getElementById('lbTrendValue');

    trendLabel.textContent = "Nilai Tren";
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

    // Tombol Detail → halaman detail (Bawa Paremeter Filter)
    detailBtn.addEventListener('click', () => {
        const stationId = detailBtn.dataset.stationId;
        if (stationId) {
            // Ambil filter yang sedang aktif dari variabel globals / DOM
            const params = typeof getCurrentParams === 'function' ? getCurrentParams() : {};
            const dtType = params.dataType || 'tahunan';
            const agg = params.aggregation || 'rerata';
            const mo = params.month || 'all';
            const yF = params.yearFrom || '';
            const yT = params.yearTo || '';

            window.open(`detail?id=${stationId}&dtType=${dtType}&agg=${agg}&mo=${mo}&yF=${yF}&yT=${yT}`, '_blank');
        }
    });
});
