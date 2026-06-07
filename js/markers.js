/**
 * TrendHidro - markers.js
 * Render marker stasiun dan perbarui ikon panah tren
 */

// ====== SVG IKON ======
function createStationIcon() {
    return L.divIcon({
        className: 'station-marker',
        html: `<div style="
            width: 14px; height: 14px;
            background: #2563EB;
            border: 2.5px solid #fff;
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
        "></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7]
    });
}

function createTrendIcon(trend, completeness = 1.0) {
    let color, symbol;
    
    switch (trend) {
        // Mann-Kendall: Signifikan (warna lebih pekat)
        case 'Meningkat (Signifikan)':
            color = '#0B6E2F';
            symbol = '▲';
            break;
        case 'Menurun (Signifikan)':
            color = '#991B1B';
            symbol = '▼';
            break;
        // Mann-Kendall: Tidak Signifikan (warna biasa)
        case 'Meningkat (Tidak Signifikan)':
        case 'Meningkat':
            color = '#16A34A';
            symbol = '▲';
            break;
        case 'Menurun (Tidak Signifikan)':
        case 'Menurun':
            color = '#DC2626';
            symbol = '▼';
            break;
        default:
            color = '#6B7280';
            symbol = '—';
    }

    // Indikator peringatan jika data < 16 tahun
    let warningOverlay = '';
    if (parseFloat(completeness) < 16) {
        warningOverlay = `
            <div style="
                position: absolute;
                top: -5px;
                right: -5px;
                width: 16px;
                height: 16px;
                background: #FACC15;
                color: #B91C1C;
                border: 1.5px solid #fff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                font-weight: 900;
                box-shadow: 0 1px 3px rgba(0,0,0,0.4);
                z-index: 10;
            " title="Panjang Data Kurang dari 16 Tahun">!</div>
        `;
    }

    return L.divIcon({
        className: 'trend-marker',
        html: `<div style="
            position: relative;
            width: 32px; height: 32px;
            background: ${color};
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            line-height: 1;
        ">${symbol}${warningOverlay}</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });
}

// ====== RENDER MARKER STASIUN ======
function renderStationMarkers(stations) {
    clearStationMarkers();

    stations.forEach(station => {
        const props = station.properties;
        const latlng = [props.lat, props.lon];

        const marker = L.marker(latlng, {
            icon: createStationIcon()
        }).addTo(map);

        // Simpan data di marker
        marker.stationData = props;

        // Klik → lightbox
        marker.on('click', () => {
            openLightbox(props);
        });

        // Tooltip nama stasiun
        marker.bindTooltip(props.name, {
            direction: 'top',
            offset: [0, -10],
            className: 'station-tooltip'
        });

        stationMarkers.push(marker);
    });
}

// ====== HAPUS MARKER ======
function clearStationMarkers() {
    stationMarkers.forEach(m => map.removeLayer(m));
    stationMarkers = [];
}

// ====== UPDATE IKON TREN ======
function updateTrendMarkers(results) {
    // Cek status filter kualitas data dari UI
    const t16 = document.getElementById('toggleHide16');
    const t30 = document.getElementById('toggleHide30');
    const hide16 = t16 ? t16.classList.contains('on') : false;
    const hide30 = t30 ? t30.classList.contains('on') : false;

    // Optimasi O(N): Gunakan Map untuk pencarian cepat berdasarkan ID
    const resultMap = new Map();
    results.forEach(r => resultMap.set(r.id, r));

    stationMarkers.forEach(marker => {
        const result = resultMap.get(marker.stationData.id);
        
        // Panjang Data (Tahun)
        const start = marker.stationData.yearStart;
        const end = marker.stationData.yearEnd;
        const hasRange = (start !== null && start !== undefined && end !== null && end !== undefined);
        const dataLength = hasRange ? (end - start + 1) : 0;

        // Filter Kualitas Data: Sembunyikan marker jika < 16 atau < 30 tahun dan filter aktif.
        // Stasiun tanpa yearStart/yearEnd (dataLength = 0) tidak disembunyikan oleh filter
        // karena tidak ada info panjang data yang valid.
        if (hasRange && ((hide16 && dataLength < 16) || (hide30 && dataLength < 30))) {
            map.removeLayer(marker);
        } else {
            // Pastikan muncul kembali jika sebelumnya disembunyikan
            if (!map.hasLayer(marker)) marker.addTo(map);
            
            if (result) {
                // Pass dataLength as completeness to createTrendIcon so it knows whether to show warning
                // warning is only shown if hide16 is false, which is naturally handled if dataLength < 16 is passed and not filtered
                marker.setIcon(createTrendIcon(result.trend, hide16 ? 99 : dataLength));
            } else {
                marker.setIcon(createStationIcon());
            }
        }
    });
}

// ====== RESET MARKER KE DEFAULT ======
function resetMarkerIcons() {
    stationMarkers.forEach(marker => {
        if (!map.hasLayer(marker)) marker.addTo(map);
        marker.setIcon(createStationIcon());
    });
}

// ====== APPLY QUALITY FILTER (tanpa metode aktif) ======
function applyQualityFilter() {
    const t16 = document.getElementById('toggleHide16');
    const t30 = document.getElementById('toggleHide30');
    const hide16 = t16 ? t16.classList.contains('on') : false;
    const hide30 = t30 ? t30.classList.contains('on') : false;

    stationMarkers.forEach(marker => {
        const start = marker.stationData.yearStart;
        const end = marker.stationData.yearEnd;
        const hasRange = (start !== null && start !== undefined && end !== null && end !== undefined);
        const dataLength = hasRange ? (end - start + 1) : 0;

        // Stasiun tanpa yearStart/yearEnd (dataLength = 0) tidak disembunyikan oleh filter
        if (hasRange && ((hide16 && dataLength < 16) || (hide30 && dataLength < 30))) {
            map.removeLayer(marker);
        } else {
            if (!map.hasLayer(marker)) marker.addTo(map);
        }
    });
}
