/**
 * TrendHidro - olah-data.js
 * Client-side file parsing & analysis for user-uploaded time series data
 */

// ====== GLOBAL STATE ======
let rawData = [];       // All parsed daily records: [{date: Date, value: Number}]
let aggregatedData = []; // Aggregated for analysis: [{year: Number, value: Number}]
let olahChart = null;

const fM = (val) => val === undefined || val === null || val === '—' ? '—' : String(val).replace('-', '−');

// ====== UI UTILS ======
function togglePreviewAccordion() {
    const header = document.getElementById('previewAccordionHeader');
    const content = document.getElementById('previewAccordionContent');
    header.classList.toggle('active');
    content.classList.toggle('show');
}

function resetUpload() {
    rawData = [];
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('configSection').classList.remove('show');
    document.getElementById('resultsSection').classList.remove('show');
    document.getElementById('uploadZone').style.display = '';
    
    // Reset accordion
    document.getElementById('previewAccordionHeader').classList.remove('active');
    document.getElementById('previewAccordionContent').classList.remove('show');
    
    // Reset headers
    document.getElementById('previewDataHeader').textContent = 'Data';
}

function toggleOlahMonth() {
    const type = document.getElementById('olahDtType').value;
    const mo = document.getElementById('olahMonth');
    if (type === 'tahunan') {
        mo.style.display = 'none';
    } else if (type === 'musiman') {
        mo.style.display = 'inline-block';
        mo.innerHTML = `
            <option value="1,2,3">Jan–Feb–Mar</option>
            <option value="4,5,6">Apr–Mei–Jun</option>
            <option value="7,8,9">Jul–Agus–Sep</option>
            <option value="10,11,12">Okt–Nov–Des</option>
        `;
    } else {
        mo.style.display = 'inline-block';
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

// ====== FILE UPLOAD ======
document.addEventListener('DOMContentLoaded', () => {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');

    // Drag & Drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });
});

function handleFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    const fileInfo = document.getElementById('fileInfo');
    fileInfo.style.display = 'block';
    fileInfo.textContent = `📄 ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;

    if (ext === 'csv') {
        parseCSV(file);
    } else if (ext === 'xls' || ext === 'xlsx') {
        parseExcel(file);
    } else {
        alert('Format file tidak didukung. Gunakan CSV, XLS, atau XLSX.');
    }
}

// ====== CSV PARSER ======
function parseCSV(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const text = e.target.result;
        const lines = text.split(/\r?\n/).filter(l => l.trim());
        if (lines.length < 2) {
            alert('File CSV kosong atau tidak memiliki data.');
            return;
        }

        // Detect separator
        const sep = lines[0].includes(';') ? ';' : ',';
        const rows = lines.slice(1); // skip header

        rawData = [];
        rows.forEach(line => {
            const parts = line.split(sep).map(s => s.trim().replace(/^"|"$/g, ''));
            if (parts.length >= 2) {
                const dateVal = parseFlexDate(parts[0]);
                const value = Math.round(parseFloat(parts[1].replace(',', '.')));
                if (dateVal && !isNaN(value)) {
                    rawData.push({ date: dateVal, value: value });
                }
            }
        });

        if (rawData.length === 0) {
            alert('Tidak ada data valid yang berhasil di-parse dari file CSV.');
            return;
        }

        rawData.sort((a, b) => a.date - b.date);
        onDataParsed();
    };
    reader.readAsText(file);
}

// ====== EXCEL PARSER ======
function parseExcel(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array', cellDates: true });
            const sheetName = workbook.SheetNames[0];
            const sheet = workbook.Sheets[sheetName];
            const json = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, dateNF: 'dd/mm/yyyy' });

            if (json.length < 2) {
                alert('Sheet Excel kosong atau tidak memiliki data.');
                return;
            }

            rawData = [];
            for (let i = 1; i < json.length; i++) {
                const row = json[i];
                if (row.length >= 2) {
                    const dateVal = parseFlexDate(String(row[0]));
                    const value = Math.round(parseFloat(String(row[1]).replace(',', '.')));
                    if (dateVal && !isNaN(value)) {
                        rawData.push({ date: dateVal, value: value });
                    }
                }
            }

            if (rawData.length === 0) {
                alert('Tidak ada data valid yang berhasil di-parse dari file Excel.');
                return;
            }

            rawData.sort((a, b) => a.date - b.date);
            onDataParsed();
        } catch (err) {
            console.error('Excel parse error:', err);
            alert('Gagal membaca file Excel. Pastikan format file benar.');
        }
    };
    reader.readAsArrayBuffer(file);
}

// ====== FLEXIBLE DATE PARSER ======
function parseFlexDate(str) {
    if (!str) return null;
    str = str.trim();

    // Try dd/mm/yy or dd/mm/yyyy
    const slashMatch = str.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/);
    if (slashMatch) {
        let day = parseInt(slashMatch[1]);
        let month = parseInt(slashMatch[2]);
        let year = parseInt(slashMatch[3]);
        if (year < 100) year += (year > 50 ? 1900 : 2000);
        if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
            return new Date(year, month - 1, day);
        }
    }

    // Try yyyy-mm-dd (ISO)
    const isoMatch = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (isoMatch) {
        return new Date(parseInt(isoMatch[1]), parseInt(isoMatch[2]) - 1, parseInt(isoMatch[3]));
    }

    // Try native Date parse
    const d = new Date(str);
    if (!isNaN(d.getTime())) return d;

    return null;
}

// ====== ON DATA PARSED ======
function onDataParsed() {
    // Hide upload zone
    document.getElementById('uploadZone').style.display = 'none';

    // Show config section
    document.getElementById('configSection').classList.add('show');

    // Auto-open accordion
    document.getElementById('previewAccordionHeader').classList.add('active');
    document.getElementById('previewAccordionContent').classList.add('show');

    // Preview table (all rows with scroll, limit display rows to first few for speed)
    const previewBody = document.getElementById('previewBody');
    previewBody.innerHTML = '';
    const displayLimit = Math.min(rawData.length, 20); // Only render 20 to DOM for performance
    for (let i = 0; i < displayLimit; i++) {
        const d = rawData[i];
        const dateStr = `${d.date.getDate().toString().padStart(2, '0')}/${(d.date.getMonth() + 1).toString().padStart(2, '0')}/${d.date.getFullYear()}`;
        previewBody.innerHTML += `<tr><td>${i + 1}</td><td>${dateStr}</td><td>${d.value}</td></tr>`;
    }

    document.getElementById('previewCount').textContent =
        `Menampilkan ${displayLimit} baris pertama dari total ${rawData.length} baris data`;

    // Setup Year Picker
    const years = [...new Set(rawData.map(d => d.date.getFullYear()))].sort((a, b) => a - b);
    const ys = years[0];
    const ye = years[years.length - 1];
    
    window.stationMinYear = ys;
    window.stationMaxYear = ye;

    const yfInput = document.getElementById('olahYFrom');
    const ytInput = document.getElementById('olahYTo');
    const yfDisp = document.getElementById('displayFrom');
    const ytDisp = document.getElementById('displayTo');

    yfInput.value = ys;
    ytInput.value = ye;
    yfDisp.textContent = ys;
    ytDisp.textContent = ye;

    window.viewedDecadeFrom = Math.floor(ys / 10) * 10;
    window.viewedDecadeTo = Math.floor(ye / 10) * 10;
}

// ====== YEAR PICKER LOGIC ======
function renderDecadeGrid(idSuffix, startYear) {
    const gridElem = document.getElementById(`grid${idSuffix}`);
    if (!gridElem) return;
    const decadeStart = Math.floor(startYear / 10) * 10;
    const decadeEnd = decadeStart + 9;
    const activeYear = document.getElementById(`olahY${idSuffix}`).value;
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

document.addEventListener('DOMContentLoaded', () => {
    const dispFrom = document.getElementById('displayFrom');
    const dispTo = document.getElementById('displayTo');
    if (dispFrom) {
        dispFrom.addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('gridTo').classList.add('hidden');
            document.getElementById('gridFrom').classList.toggle('hidden');
            if (!document.getElementById('gridFrom').classList.contains('hidden')) renderDecadeGrid('From', window.viewedDecadeFrom);
        });
    }
    if (dispTo) {
        dispTo.addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('gridFrom').classList.add('hidden');
            document.getElementById('gridTo').classList.toggle('hidden');
            if (!document.getElementById('gridTo').classList.contains('hidden')) renderDecadeGrid('To', window.viewedDecadeTo);
        });
    }

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
            const gf = document.getElementById('gridFrom');
            const gt = document.getElementById('gridTo');
            if (gf) gf.classList.add('hidden');
            if (gt) gt.classList.add('hidden');
            return;
        }
        const grid = item.closest('.year-grid');
        const year = item.dataset.year;
        if (grid.id === 'gridFrom') {
            document.getElementById('olahYFrom').value = year;
            document.getElementById('displayFrom').textContent = year;
            document.getElementById('gridFrom').classList.add('hidden');
        } else {
            document.getElementById('olahYTo').value = year;
            document.getElementById('displayTo').textContent = year;
            document.getElementById('gridTo').classList.add('hidden');
        }
    });
});

// ====== TOGGLE MONTH SELECT ======
function toggleOlahMonth() {
    const dtType = document.getElementById('olahDtType').value;
    const mo = document.getElementById('olahMonth');

    if (dtType === 'tahunan') {
        mo.style.display = 'none';
    } else if (dtType === 'musiman') {
        mo.style.display = 'inline-block';
        mo.innerHTML = `
            <option value="1,2,3">Jan–Feb–Mar</option>
            <option value="4,5,6">Apr–Mei–Jun</option>
            <option value="7,8,9">Jul–Agus–Sep</option>
            <option value="10,11,12">Okt–Nov–Des</option>
        `;
    } else {
        mo.style.display = 'inline-block';
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
}

// ====== AGGREGATE DATA ======
function aggregateData(dtType, monthFilter, yFrom, yTo) {
    // Filter raw data by year range
    let filtered = rawData.filter(d => {
        const y = d.date.getFullYear();
        return y >= yFrom && y <= yTo;
    });

    // Filter by month if applicable
    if (dtType === 'bulanan' && monthFilter !== 'all') {
        const mo = parseInt(monthFilter);
        filtered = filtered.filter(d => (d.date.getMonth() + 1) === mo);
    } else if (dtType === 'musiman') {
        const months = monthFilter.split(',').map(Number);
        filtered = filtered.filter(d => months.includes(d.date.getMonth() + 1));
    }

    if (dtType === 'tahunan') {
        const yearMap = {};
        const countMap = {};
        filtered.forEach(d => {
            const y = d.date.getFullYear();
            if (!yearMap[y]) { yearMap[y] = 0; countMap[y] = 0; }
            yearMap[y] += d.value;
            countMap[y]++;
        });
        
        // Pilih fungsi agregasi yang sesuai (sementara default AVG/SUM, kita gunakan SUM untuk kesederhanaan,
        // namun aslinya harus membaca mode dari input user. Karena olah-data.html mungkin belum ada opsi agregasi,
        // biarkan tetap sederhana). Untuk saat ini user upload data tahunan biasanya 1 data per tahun.
        return Object.keys(yearMap).sort().map(y => ({ year: parseFloat(y), value: Math.round(yearMap[y]) }));
    }

    if (dtType === 'bulanan') {
        if (monthFilter === 'all') {
            // Monthly encoding
            const monthMap = {};
            filtered.forEach(d => {
                const y = d.date.getFullYear();
                const m = d.date.getMonth(); // 0-indexed
                const key = `${y}-${m}`;
                if (!monthMap[key]) monthMap[key] = { year: y, month: m, total: 0 };
                monthMap[key].total += d.value;
            });
            return Object.values(monthMap)
                .sort((a, b) => a.year !== b.year ? a.year - b.year : a.month - b.month)
                .map(d => ({ year: d.year + (d.month / 12), value: Math.round(d.total) }));
        } else {
            // Single month
            const yearMap = {};
            filtered.forEach(d => {
                const y = d.date.getFullYear();
                if (!yearMap[y]) yearMap[y] = 0;
                yearMap[y] += d.value;
            });
            return Object.keys(yearMap).sort().map(y => ({ year: parseFloat(y), value: Math.round(yearMap[y]) }));
        }
    }

    if (dtType === 'musiman') {
        const yearMap = {};
        filtered.forEach(d => {
            const y = d.date.getFullYear();
            if (!yearMap[y]) yearMap[y] = 0;
            yearMap[y] += d.value;
        });
        return Object.keys(yearMap).sort().map(y => ({ year: parseFloat(y), value: Math.round(yearMap[y]) }));
    }

    return [];
}

// ====== RUN ANALYSIS ======
async function runOlahAnalysis() {
    if (rawData.length === 0) {
        alert('Silakan upload file data terlebih dahulu.');
        return;
    }

    // Hide preview accordion on submit
    const accordionContent = document.getElementById('previewAccordionContent');
    const accordionHeader = document.getElementById('previewAccordionHeader');
    if (accordionContent.classList.contains('show')) {
        accordionContent.classList.remove('show');
        accordionHeader.classList.remove('active');
    }

    // Set year picker to open downwards after analysis
    document.querySelectorAll('.year-grid').forEach(g => {
        g.classList.remove('grid-up');
    });

    const dtType = document.getElementById('olahDtType').value;
    let mo = document.getElementById('olahMonth').value;
    if (dtType === 'tahunan') mo = 'all';
    const yFrom = parseInt(document.getElementById('olahYFrom').value);
    const yTo = parseInt(document.getElementById('olahYTo').value);

    if (yFrom > yTo) {
        alert('Tahun awal harus lebih kecil atau sama dengan tahun akhir.');
        return;
    }

    document.getElementById('olahSpinner').style.display = 'block';
    document.getElementById('btnOlahRun').disabled = true;
    document.getElementById('resultsSection').classList.add('show');

    aggregatedData = aggregateData(dtType, mo, yFrom, yTo);

    if (aggregatedData.length === 0) {
        alert('Tidak ada data untuk periode yang dipilih.');
        document.getElementById('olahSpinner').style.display = 'none';
        document.getElementById('btnOlahRun').disabled = false;
        return;
    }

    // Render chart
    renderOlahChart(aggregatedData, dtType);

    // Stats
    renderOlahStats(aggregatedData, dtType, yFrom, yTo, mo);

    // Update availability card title
    const titleMap = { bulanan: 'Bulanan', tahunan: 'Tahunan', musiman: 'Bulanan' };
    document.getElementById('olahAvailTitle').textContent = 'Ketersediaan Data ' + (titleMap[dtType] || 'Periode Terpilih');

    // Availability
    renderOlahAvailability(aggregatedData, dtType, yFrom, yTo, mo);

    // Trend calculations via backend
    const trendLoader = document.getElementById('olahTrendLoader');
    trendLoader.classList.add('active');

    document.getElementById('olahMkResult').innerHTML = 'Menghitung...';
    document.getElementById('olahSsResult').innerHTML = 'Menghitung...';
    document.getElementById('olahLrResult').innerHTML = 'Menghitung...';

    try {
        const results = await Promise.allSettled([
            fetch('php/mann_kendall.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ data: aggregatedData }) }).then(r => r.json()),
            fetch('php/sens_slope.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ data: aggregatedData }) }).then(r => r.json()),
            fetch('php/regresi_linear.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ data: aggregatedData }) }).then(r => r.json())
        ]);

        // Mann-Kendall
        const mkRes = results[0];
        if (mkRes.status === 'fulfilled' && !mkRes.value.error) {
            const mk = mkRes.value;
            const mkSig = mk.trend.includes('(Signifikan)');
            const tTrendMK = mkSig ? (mk.Z > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
            let mkColor = '#6B7280';
            if (tTrendMK === 'Meningkat') mkColor = '#16A34A';
            else if (tTrendMK === 'Menurun') mkColor = '#DC2626';
            
            const zKritis = 1.96;
            const zUjiVal = fM(mk.Z) + (mkSig ? '<sup style="color:#DC2626;">*</sup>' : '');
            document.getElementById('olahMkResult').innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;">Trend</td><td style="padding:3px 0;text-align:right;font-weight:600;color:${mkColor};">${tTrendMK}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Z<sub>uji</sub></td><td style="padding:3px 0;text-align:right;">${zUjiVal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Z<sub>kritis</sub></td><td style="padding:3px 0;text-align:right;">±${zKritis}</td></tr></table>`;
        } else {
            document.getElementById('olahMkResult').innerHTML = 'Gagal menghitung';
        }

        // Sen's Slope
        const ssRes = results[1];
        if (ssRes.status === 'fulfilled' && !ssRes.value.error) {
            const ss = ssRes.value;
            const ssSig = ss.trend.includes('(Signifikan)');
            const tTrendSS = ssSig ? (ss.slope > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
            let ssColor = '#6B7280';
            if (tTrendSS === 'Meningkat') ssColor = '#16A34A';
            else if (tTrendSS === 'Menurun') ssColor = '#DC2626';
            
            const qmedVal = fM(ss.slope) + (ssSig ? '<sup style="color:#DC2626;">*</sup>' : '');
            const qminHtml = ss.Qmin !== undefined ? fM(ss.Qmin) : '—';
            const qmaxHtml = ss.Qmax !== undefined ? fM(ss.Qmax) : '—';
            document.getElementById('olahSsResult').innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;">Trend</td><td style="padding:3px 0;text-align:right;font-weight:600;color:${ssColor};">${tTrendSS}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>med</sub></td><td style="padding:3px 0;text-align:right;">${qmedVal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>min</sub></td><td style="padding:3px 0;text-align:right;">${qminHtml}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>max</sub></td><td style="padding:3px 0;text-align:right;">${qmaxHtml}</td></tr></table>`;
        } else {
            document.getElementById('olahSsResult').innerHTML = 'Gagal menghitung';
        }

        // Linear Regression
        const lrRes = results[2];
        if (lrRes.status === 'fulfilled' && !lrRes.value.error) {
            const lr = lrRes.value;
            const lrSig = lr.trend.includes('(Signifikan)');
            const tTrendLR = lrSig ? (lr.slope > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
            let lrColor = '#6B7280';
            if (tTrendLR === 'Meningkat') lrColor = '#16A34A';
            else if (tTrendLR === 'Menurun') lrColor = '#DC2626';
            
            const slopeLR = lr.slope !== undefined ? fM(Number(lr.slope).toFixed(4)) : '—';
            const tUji = lr.tStatistic !== undefined ? fM(lr.tStatistic) : '—';
            const tKrit = lr.tCritical !== undefined ? `±${fM(lr.tCritical)}` : '—';
            const tUjiVal = tUji + (lrSig ? '<sup style="color:#DC2626;">*</sup>' : '');
            document.getElementById('olahLrResult').innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;">Trend</td><td style="padding:3px 0;text-align:right;font-weight:600;color:${lrColor};">${tTrendLR}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Slope (b)</td><td style="padding:3px 0;text-align:right;">${slopeLR}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">t<sub>uji</sub></td><td style="padding:3px 0;text-align:right;">${tUjiVal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">t<sub>kritis</sub></td><td style="padding:3px 0;text-align:right;">${tKrit}</td></tr></table>`;

            // Add trend line
            if (olahChart && lr.slope !== undefined && lr.intercept !== undefined) {
                const trendPts = aggregatedData.map(d => lr.intercept + (lr.slope * d.year));
                olahChart.data.datasets = olahChart.data.datasets.filter(ds => ds.label !== 'Garis Regresi Linear');
                olahChart.data.datasets.push({
                    type: 'line', label: 'Garis Regresi Linear',
                    data: trendPts, borderColor: '#DC2626', borderWidth: 2,
                    tension: 0, pointRadius: 0, fill: false, order: -1
                });
                olahChart.update();
            }
        } else {
            document.getElementById('olahLrResult').innerHTML = 'Gagal menghitung';
        }

    } catch (e) {
        console.error('Trend calculation error:', e);
        document.getElementById('olahMkResult').innerHTML = 'Error sistem';
        document.getElementById('olahSsResult').innerHTML = 'Error sistem';
        document.getElementById('olahLrResult').innerHTML = 'Error sistem';
    } finally {
        trendLoader.classList.remove('active');
        document.getElementById('olahSpinner').style.display = 'none';
        document.getElementById('btnOlahRun').disabled = false;
    }
}

// ====== RENDER CHART ======
function renderOlahChart(data, dtType) {
    const yearLabels = data.map(d => Math.floor(d.year).toString());
    const values = data.map(d => d.value);

    const tooltipLabels = data.map(d => {
        const y = Math.floor(d.year);
        if (dtType === 'tahunan') return `Tahun ${y}`;
        if (dtType === 'bulanan') {
            const frac = d.year - y;
            const m = Math.round(frac * 12);
            const names = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
            return `${names[m] || ''} ${y}`;
        }
        return `Tahun ${y}`;
    });

    const ctx = document.getElementById('olahChart').getContext('2d');
    if (olahChart) olahChart.destroy();

    olahChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: yearLabels,
            datasets: [{
                label: 'Nilai Data',
                data: values,
                backgroundColor: 'rgba(37, 99, 235, 0.7)',
                borderColor: '#2563EB',
                borderWidth: 1, borderRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            normalized: true,
            interaction: {
                mode: 'index',
                intersect: false,
                axis: 'x'
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
                        maxTicksLimit: 15
                    },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Nilai Data', font: { family: 'Inter', weight: 'bold' } },
                    ticks: { font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });
}

// ====== RENDER STATS ======
function renderOlahStats(data, dtType, yFrom, yTo, mo) {
    const values = data.map(d => d.value);
    if (values.length === 0) return;

    const mean = Math.round(values.reduce((a, b) => a + b, 0) / values.length);
    const max = Math.max(...values);
    const min = Math.min(...values);
    const std = values.length > 1 ? Math.round(Math.sqrt(values.reduce((s, v) => s + Math.pow(v - mean, 2), 0) / (values.length - 1))) : 0;
    const cv = mean > 0 ? (std / mean) : 0;

    document.getElementById('olahStatMean').textContent = fM(mean.toFixed(0)) + ' unit';
    document.getElementById('olahStatMax').textContent = fM(max.toFixed(0)) + ' unit';
    document.getElementById('olahStatMin').textContent = fM(min.toFixed(0)) + ' unit';
    document.getElementById('olahStatStd').textContent = fM(std.toFixed(0)) + ' unit';
    document.getElementById('olahStatCv').textContent = fM(cv.toFixed(2));
    document.getElementById('olahStatLength').textContent = `${values.length} unit`;
    document.getElementById('olahStatRange').textContent = `${yFrom}—${yTo}`;

    // Outlier
    const sorted = [...values].sort((a, b) => a - b);
    // Interpolation for Quartiles
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

    document.getElementById('olahStatLb').textContent = fM(lb.toFixed(0)) + ' unit';
    document.getElementById('olahStatUb').textContent = fM(ub.toFixed(0)) + ' unit';
    document.getElementById('olahStatOutlier').innerHTML = hasOutlier
        ? `<span style="color:#DC2626;font-weight:600;">Ya</span>`
        : `Tidak`;
}

// ====== RENDER AVAILABILITY ======
function renderOlahAvailability(data, dtType, yFrom, yTo, mo) {
    const actualCount = data.length;
    let expectedCount = 0;

    if (dtType === 'tahunan') {
        expectedCount = yTo - yFrom + 1;
    } else if (dtType === 'bulanan') {
        expectedCount = (mo === 'all') ? (yTo - yFrom + 1) * 12 : (yTo - yFrom + 1);
    } else if (dtType === 'musiman') {
        expectedCount = yTo - yFrom + 1;
    }

    if (expectedCount <= 0) expectedCount = 1;
        const pct = Math.min(100, (actualCount / expectedCount) * 100);
        const pctDisplay = pct.toFixed(2);
        const barFill = document.getElementById('olahAvailBar');
        barFill.style.width = `${pct}%`;
        let color = '#EF4444';
        if (pct >= 80) color = '#16A34A';
        else if (pct >= 50) color = '#F59E0B';
        barFill.style.background = color;
        const valEl = document.getElementById('olahAvailPct');
        valEl.textContent = pctDisplay + '%';
        valEl.style.color = color;
        const labelEl = document.getElementById('olahAvailLabel');
        labelEl.innerHTML = `<strong>${actualCount}</strong> dari <strong>${expectedCount}</strong> data tersedia`;
}

// ====== CUSTOM SELECT UI ======
document.addEventListener('DOMContentLoaded', () => {
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
            const triggerText = document.createElement('span');
            trigger.appendChild(triggerText);
            trigger.innerHTML += `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>`;
            
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'custom-select-options';
            
            wrapper.appendChild(trigger);
            wrapper.appendChild(optionsContainer);
            selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);

            function renderOptions() {
                // Cek visibilitas select asal
                if (selectEl.style.display === 'none' && !selectEl.dataset.customized) {
                    wrapper.style.display = 'none';
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
                if (selectEl.id === 'olahMonth') {
                    const dtType = document.getElementById('olahDtType').value;
                    wrapper.style.display = dtType === 'tahunan' ? 'none' : 'inline-block';
                }
                renderOptions();
            });
            
            // Initial visibility check
            if (selectEl.id === 'olahMonth') {
                const dtType = document.getElementById('olahDtType').value;
                wrapper.style.display = dtType === 'tahunan' ? 'none' : 'inline-block';
            }
        });
        
        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
        });
    }
    
    // Call custom setup
    setupCustomSelects();
});
