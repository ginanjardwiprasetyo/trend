/**
 * TrendHidro - olah-data.js
 * Client-side file parsing & analysis for user-uploaded time series data
 */

// ====== GLOBAL STATE ======
let rawData = [];       // All parsed daily records: [{date: Date, value: Number}]
let aggregatedData = []; // Aggregated for analysis: [{year: Number, value: Number}]
let olahChart = null;
let inputPeriod = 'harian'; // 'harian' | 'bulanan' | 'tahunan' — auto-detected after upload
let cachedRegularMK = null;
let cachedRegularSS = null;
let cachedSeasonalMK = null;
let cachedSeasonalSS = null;

const fM = (val) => val === undefined || val === null || val === '—' ? '—' : String(val).replace('-', '−');

// ====== UI UTILS ======
function togglePreviewAccordion() {
    const header = document.getElementById('previewAccordionHeader');
    const content = document.getElementById('previewAccordionContent');
    header.classList.toggle('active');
    content.classList.toggle('show');
    document.getElementById('configSection').classList.toggle('preview-open');
}

function resetUpload() {
    rawData = [];
    inputPeriod = 'harian';
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('configSection').classList.remove('show');
    document.getElementById('resultsSection').classList.remove('show');
    document.getElementById('uploadZone').style.display = '';

    // Reset accordion
    document.getElementById('previewAccordionHeader').classList.remove('active');
    document.getElementById('previewAccordionContent').classList.remove('show');
    document.getElementById('configSection').classList.remove('preview-open');

    // Reset headers
    document.getElementById('previewDateHeader').textContent = 'Tanggal';
    document.getElementById('previewDataHeader').textContent = 'Data';
}

function toggleOlahMonth() {
    const dtType = document.getElementById('olahDtType').value;
    const mo = document.getElementById('olahMonth');
    mo.style.display = 'none';
    if (dtType === 'musiman') {
        mo.innerHTML = `
            <option value="1,2,3">Jan–Feb–Mar</option>
            <option value="4,5,6">Apr–Mei–Jun</option>
            <option value="7,8,9">Jul–Agus–Sep</option>
            <option value="10,11,12">Okt–Nov–Des</option>
        `;
    } else {
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
    syncAggVisibility();
    const evt = new Event('optionsChanged');
    mo.dispatchEvent(evt);
}

// ====== INPUT PERIOD DETECTION ======
function detectInputPeriod(rawStrs) {
    let hasDaily = false, hasMonthly = false, hasAnnual = false;
    for (const s of rawStrs) {
        const t = s.trim();
        if (/^\d{4}$/.test(t)) { hasAnnual = true; continue; }
        if (/^\d{4}-\d{1,2}$/.test(t) || /^\d{1,2}\/\d{4}$/.test(t) || /^[a-zA-Z]{3,}\s+\d{4}$/.test(t)) { hasMonthly = true; continue; }
        if (/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/.test(t) || /^\d{4}-\d{1,2}-\d{1,2}/.test(t)) { hasDaily = true; continue; }
        hasDaily = true; // fallback
    }
    if (hasDaily) return 'harian';
    if (hasMonthly) return 'bulanan';
    if (hasAnnual) return 'tahunan';
    return 'harian';
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
        const rawStrs = [];
        rows.forEach(line => {
            const parts = line.split(sep).map(s => s.trim().replace(/^"|"$/g, ''));
            if (parts.length >= 2) {
                rawStrs.push(parts[0]);
                const dateVal = parseFlexDate(parts[0]);
                const value = parseFloat(parts[1].replace(',', '.'));
                if (dateVal && !isNaN(value)) {
                    rawData.push({ date: dateVal, value: value });
                }
            }
        });

        if (rawData.length === 0) {
            alert('Tidak ada data valid yang berhasil di-parse dari file CSV.');
            return;
        }

        inputPeriod = detectInputPeriod(rawStrs);
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
            const rawStrs = [];
            for (let i = 1; i < json.length; i++) {
                const row = json[i];
                if (row.length >= 2) {
                    rawStrs.push(String(row[0]));
                    const dateVal = parseFlexDate(String(row[0]));
                    const value = parseFloat(String(row[1]).replace(',', '.'));
                    if (dateVal && !isNaN(value)) {
                        rawData.push({ date: dateVal, value: value });
                    }
                }
            }

            if (rawData.length === 0) {
                alert('Tidak ada data valid yang berhasil di-parse dari file Excel.');
                return;
            }

            inputPeriod = detectInputPeriod(rawStrs);
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

    // Annual: YYYY (4 digit)
    const yearMatch = str.match(/^(\d{4})$/);
    if (yearMatch) {
        return new Date(parseInt(yearMatch[1]), 0, 1);
    }

    // Monthly: YYYY-MM
    const ymMatch = str.match(/^(\d{4})-(\d{1,2})$/);
    if (ymMatch) {
        return new Date(parseInt(ymMatch[1]), parseInt(ymMatch[2]) - 1, 1);
    }

    // Monthly: MM/YYYY
    const myMatch = str.match(/^(\d{1,2})\/(\d{4})$/);
    if (myMatch) {
        return new Date(parseInt(myMatch[2]), parseInt(myMatch[1]) - 1, 1);
    }

    // Monthly: Mon YYYY or Month YYYY
    const txtMatch = str.match(/^([a-zA-Z]{3,})\s+(\d{4})$/);
    if (txtMatch) {
        const months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        const idx = months.indexOf(txtMatch[1].toLowerCase().substring(0, 3));
        if (idx >= 0) {
            return new Date(parseInt(txtMatch[2]), idx, 1);
        }
    }

    // Daily: dd/mm/yy or dd/mm/yyyy
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

    // Daily: yyyy-mm-dd (ISO)
    const isoMatch = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (isoMatch) {
        return new Date(parseInt(isoMatch[1]), parseInt(isoMatch[2]) - 1, parseInt(isoMatch[3]));
    }

    // Try native Date parse
    const d = new Date(str);
    if (!isNaN(d.getTime())) return d;

    return null;
}

// ====== UI ADJUSTMENT BASED ON INPUT PERIOD ======
function adjustUIBasedOnPeriod() {
    const dtType = document.getElementById('olahDtType');
    const aggWrap = document.getElementById('olahAgg').nextElementSibling;
    const monthWrap = document.getElementById('olahMonth').nextElementSibling;

    // Reset all options to enabled first
    Array.from(dtType.options).forEach(o => o.disabled = false);
    if (aggWrap) aggWrap.style.display = '';
    if (monthWrap) monthWrap.style.display = '';

    if (inputPeriod === 'tahunan') {
        // Only 'tahunan' is allowed
        dtType.value = 'tahunan';
        Array.from(dtType.options).forEach(o => {
            if (o.value !== 'tahunan') o.disabled = true;
        });
        if (aggWrap) aggWrap.style.display = 'none';
        if (monthWrap) monthWrap.style.display = 'none';
    }

    // Refresh custom select UI
    dtType.dispatchEvent(new Event('change'));
    syncAggVisibility();
    document.getElementById('olahMonth').dispatchEvent(new Event('optionsChanged'));
    document.getElementById('olahAgg').dispatchEvent(new Event('optionsChanged'));
}

function syncAggVisibility() {
    const dtType = document.getElementById('olahDtType').value;
    const aggWrap = document.getElementById('olahAgg').nextElementSibling;
    if (!aggWrap) return;
    // Aggregation hidden only when: input is annual, OR (input is monthly AND dtType is bulanan)
    const hideAgg = inputPeriod === 'tahunan' || (inputPeriod === 'bulanan' && dtType === 'bulanan');
    aggWrap.style.display = hideAgg ? 'none' : '';
}

// ====== ON DATA PARSED ======
function onDataParsed() {
    // Hide upload zone
    document.getElementById('uploadZone').style.display = 'none';

    // Show config section
    document.getElementById('configSection').classList.add('show');

    // Update period indicator
    const badge = document.getElementById('inputPeriodBadge');
    const labels = { harian: 'Harian', bulanan: 'Bulanan', tahunan: 'Tahunan' };
    if (badge) {
        badge.textContent = 'Data ' + (labels[inputPeriod] || 'Harian');
        badge.className = 'period-badge period-' + inputPeriod;
    }

    // Adjust UI controls based on detected period
    adjustUIBasedOnPeriod();

    // Auto-open accordion
    document.getElementById('previewAccordionHeader').classList.add('active');
    document.getElementById('previewAccordionContent').classList.add('show');
    document.getElementById('configSection').classList.add('preview-open');

    // Preview table
    const previewBody = document.getElementById('previewBody');
    previewBody.innerHTML = '';

    // Update preview header based on input period
    const dateHeader = document.getElementById('previewDateHeader');
    const headerLabels = { harian: 'Tanggal', bulanan: 'Periode', tahunan: 'Tahun' };
    if (dateHeader) {
        dateHeader.textContent = headerLabels[inputPeriod] || 'Tanggal';
    }

    const displayLimit = Math.min(rawData.length, 20);
    for (let i = 0; i < displayLimit; i++) {
        const d = rawData[i];
        let dateStr;
        if (inputPeriod === 'tahunan') {
            dateStr = String(d.date.getFullYear());
        } else if (inputPeriod === 'bulanan') {
            dateStr = `${(d.date.getMonth() + 1).toString().padStart(2, '0')}/${d.date.getFullYear()}`;
        } else {
            dateStr = `${d.date.getDate().toString().padStart(2, '0')}/${(d.date.getMonth() + 1).toString().padStart(2, '0')}/${d.date.getFullYear()}`;
        }
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

// ====== AGGREGATE DATA ======
function aggregateData(dtType, monthFilter, yFrom, yTo, aggMode) {
    aggMode = aggMode || 'kumulatif';

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

    const reduceValues = (arr) => {
        if (aggMode === 'min') return Math.min(...arr);
        if (aggMode === 'maks') return Math.max(...arr);
        if (aggMode === 'rerata') return arr.reduce((a, b) => a + b, 0) / arr.length;
        return arr.reduce((a, b) => a + b, 0);
    };

    if (dtType === 'tahunan') {
        const yearMap = {};
        filtered.forEach(d => {
            const y = d.date.getFullYear();
            if (!yearMap[y]) yearMap[y] = [];
            yearMap[y].push(d.value);
        });
        return Object.keys(yearMap).sort().map(y => ({ year: parseFloat(y), value: reduceValues(yearMap[y]) }));
    }

    if (dtType === 'bulanan') {
        if (monthFilter === 'all') {
            const monthMap = {};
            filtered.forEach(d => {
                const y = d.date.getFullYear();
                const m = d.date.getMonth();
                const key = `${y}-${m}`;
                if (!monthMap[key]) monthMap[key] = { year: y, month: m, values: [] };
                monthMap[key].values.push(d.value);
            });
            return Object.values(monthMap)
                .sort((a, b) => a.year !== b.year ? a.year - b.year : a.month - b.month)
                .map(d => ({ year: d.year + (d.month / 12), value: reduceValues(d.values) }));
        } else {
            const yearMap = {};
            filtered.forEach(d => {
                const y = d.date.getFullYear();
                if (!yearMap[y]) yearMap[y] = [];
                yearMap[y].push(d.value);
            });
            return Object.keys(yearMap).sort().map(y => ({ year: parseFloat(y), value: reduceValues(yearMap[y]) }));
        }
    }

    if (dtType === 'musiman') {
        const yearMap = {};
        filtered.forEach(d => {
            const y = d.date.getFullYear();
            if (!yearMap[y]) yearMap[y] = [];
            yearMap[y].push(d.value);
        });
        return Object.keys(yearMap).sort().map(y => ({ year: parseFloat(y), value: reduceValues(yearMap[y]) }));
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
        document.getElementById('configSection').classList.remove('preview-open');
    }

    const dtType = document.getElementById('olahDtType').value;
    let mo = document.getElementById('olahMonth').value;
    if (dtType === 'tahunan') mo = 'all';
    const agg = (inputPeriod === 'tahunan' || (inputPeriod === 'bulanan' && dtType === 'bulanan')) ? 'kumulatif' : document.getElementById('olahAgg').value;
    const yFrom = parseInt(document.getElementById('olahYFrom').value);
    const yTo = parseInt(document.getElementById('olahYTo').value);

    if (yFrom > yTo) {
        alert('Tahun awal harus lebih kecil atau sama dengan tahun akhir.');
        return;
    }

    document.getElementById('olahSpinner').style.display = 'block';
    document.getElementById('btnOlahRun').disabled = true;
    document.getElementById('resultsSection').classList.add('show');

    aggregatedData = aggregateData(dtType, mo, yFrom, yTo, agg);

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
            const mkSig = mk.significant;
            const tTrendMK = mkSig ? (mk.Z > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
            let mkColor = '#6B7280';
            if (tTrendMK === 'Meningkat') mkColor = '#16A34A';
            else if (tTrendMK === 'Menurun') mkColor = '#DC2626';

            const zKritis = mk.zCritical !== undefined ? mk.zCritical : 1.96;
            const zUjiVal = fM(Number(mk.Z).toFixed(3)) + (mkSig ? '<sup style="color:#DC2626;">*</sup>' : '');
            document.getElementById('olahMkResult').innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;"><i>Trend</i></td><td style="padding:3px 0;text-align:right;font-weight:600;color:${mkColor};">${tTrendMK}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">S</td><td style="padding:3px 0;text-align:right;">${fM(mk.S)}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Z<sub>uji</sub></td><td style="padding:3px 0;text-align:right;">${zUjiVal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Z<sub>kritis</sub></td><td style="padding:3px 0;text-align:right;">±${fM(zKritis.toFixed(3))}</td></tr></table>`;
            cachedRegularMK = document.getElementById('olahMkResult').innerHTML;
        } else {
            document.getElementById('olahMkResult').innerHTML = 'Gagal menghitung';
            cachedRegularMK = 'Gagal menghitung';
        }

        // Sen's Slope
        const ssRes = results[1];
        if (ssRes.status === 'fulfilled' && !ssRes.value.error) {
            const ss = ssRes.value;
            const ssSig = ss.significant;
            const tTrendSS = ssSig ? (ss.slope > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
            let ssColor = '#6B7280';
            if (tTrendSS === 'Meningkat') ssColor = '#16A34A';
            else if (tTrendSS === 'Menurun') ssColor = '#DC2626';

            const qmedVal = fM(Number(ss.slope).toFixed(3)) + (ssSig ? '<sup style="color:#DC2626;">*</sup>' : '');
            const qminHtml = ss.Qmin !== undefined ? fM(Number(ss.Qmin).toFixed(3)) : '—';
            const qmaxHtml = ss.Qmax !== undefined ? fM(Number(ss.Qmax).toFixed(3)) : '—';
            document.getElementById('olahSsResult').innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;"><i>Trend</i></td><td style="padding:3px 0;text-align:right;font-weight:600;color:${ssColor};">${tTrendSS}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>med</sub></td><td style="padding:3px 0;text-align:right;">${qmedVal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>min</sub></td><td style="padding:3px 0;text-align:right;">${qminHtml}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>max</sub></td><td style="padding:3px 0;text-align:right;">${qmaxHtml}</td></tr></table>`;
            cachedRegularSS = document.getElementById('olahSsResult').innerHTML;
        } else {
            document.getElementById('olahSsResult').innerHTML = 'Gagal menghitung';
            cachedRegularSS = 'Gagal menghitung';
        }

        // Linear Regression
        const lrRes = results[2];
        if (lrRes.status === 'fulfilled' && !lrRes.value.error) {
            const lr = lrRes.value;
            const lrSig = lr.significant;
            const tTrendLR = lrSig ? (lr.slope > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
            let lrColor = '#6B7280';
            if (tTrendLR === 'Meningkat') lrColor = '#16A34A';
            else if (tTrendLR === 'Menurun') lrColor = '#DC2626';

            const slopeLR = lr.slope !== undefined ? fM(Number(lr.slope).toFixed(3)) : '—';
            const tUji = lr.tStatistic !== undefined ? fM(Number(lr.tStatistic).toFixed(3)) : '—';
            const tKrit = lr.tCritical !== undefined ? `±${fM(Number(lr.tCritical).toFixed(3))}` : '—';
            const tUjiVal = tUji + (lrSig ? '<sup style="color:#DC2626;">*</sup>' : '');
            document.getElementById('olahLrResult').innerHTML = `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;"><i>Trend</i></td><td style="padding:3px 0;text-align:right;font-weight:600;color:${lrColor};">${tTrendLR}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Slope</td><td style="padding:3px 0;text-align:right;">${slopeLR}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">t<sub>uji</sub></td><td style="padding:3px 0;text-align:right;">${tUjiVal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">t<sub>kritis</sub></td><td style="padding:3px 0;text-align:right;">${tKrit}</td></tr></table>`;

            // Add trend line
            if (olahChart && lr.slope !== undefined && lr.intercept !== undefined) {
                const trendPts = aggregatedData.map((d, index) => lr.intercept + (lr.slope * index));
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

        // Seasonal Mann-Kendall & Sen's Slope (cache for toggle swap)
        const dtType = document.getElementById('olahDtType').value;
        const mo = document.getElementById('olahMonth').value;
        if (dtType === 'bulanan' && mo === 'all') {
            const seasonalMk = calcSeasonalMannKendallJS(aggregatedData);
            const seasonalSs = calcSeasonalSenSlopeJS(aggregatedData);
            if (seasonalMk && seasonalSs) {
                const smkSig = seasonalMk.significant;
                const smkDir = smkSig ? (seasonalMk.Z > 0 ? 'Meningkat' : 'Menurun') : 'Tidak ada';
                let smkColor = '#6B7280';
                if (smkDir === 'Meningkat') smkColor = '#16A34A';
                else if (smkDir === 'Menurun') smkColor = '#DC2626';
                const zUjiSeasonal = fM(Number(seasonalMk.Z).toFixed(3)) + (smkSig ? '<sup style="color:#DC2626;">*</sup>' : '');
                cachedSeasonalMK =
                    `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;"><i>Trend</i></td><td style="padding:3px 0;text-align:right;font-weight:600;color:${smkColor};">${smkDir}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">S<sub>gab</sub></td><td style="padding:3px 0;text-align:right;">${fM(seasonalMk.S)}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Z<sub>gab</sub></td><td style="padding:3px 0;text-align:right;">${zUjiSeasonal}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Musim</td><td style="padding:3px 0;text-align:right;">${seasonalMk.seasonCount} bulan</td></tr></table>`;

                const sssDir = seasonalSs.slope > 0 ? 'Meningkat' : (seasonalSs.slope < 0 ? 'Menurun' : 'Tidak ada');
                let sssColor = '#6B7280';
                if (sssDir === 'Meningkat') sssColor = '#16A34A';
                else if (sssDir === 'Menurun') sssColor = '#DC2626';
                cachedSeasonalSS =
                    `<table style="width:100%;border-collapse:collapse;"><tr><td style="padding:3px 0;color:#6B7280;"><i>Trend</i></td><td style="padding:3px 0;text-align:right;font-weight:600;color:${sssColor};">${sssDir}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Q<sub>gab</sub></td><td style="padding:3px 0;text-align:right;">${fM(Number(seasonalSs.slope).toFixed(3))}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Jumlah Slope</td><td style="padding:3px 0;text-align:right;">${seasonalSs.slopeCount}</td></tr><tr><td style="padding:3px 0;color:#6B7280;">Musim</td><td style="padding:3px 0;text-align:right;">${seasonalSs.seasonCount} bulan</td></tr></table>`;

                const seasonalToggle = document.getElementById('olahSeasonalToggle');
                if (seasonalToggle && seasonalToggle.checked) {
                    document.getElementById('olahMkResult').innerHTML = cachedSeasonalMK;
                    document.getElementById('olahSsResult').innerHTML = cachedSeasonalSS;
                }
            } else {
                cachedSeasonalMK = null;
                cachedSeasonalSS = null;
            }
        } else {
            cachedSeasonalMK = null;
            cachedSeasonalSS = null;
        }
        updateSeasonalToggleVisibility();
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

// ====== Seasonal Toggle Functions ======
function updateSeasonalToggleVisibility() {
    const dtType = document.getElementById('olahDtType').value;
    const mo = document.getElementById('olahMonth').value;
    const wrap = document.getElementById('olahSeasonalToggleWrap');
    if (wrap) {
        wrap.style.display = (dtType === 'bulanan' && mo === 'all') ? 'inline-flex' : 'none';
    }
    updateSeasonalLabels();
}

function updateSeasonalLabels() {
    const toggle = document.getElementById('olahSeasonalToggle');
    const wrap = document.getElementById('olahSeasonalToggleWrap');
    const mkLabel = document.getElementById('olahMkLabel');
    const ssLabel = document.getElementById('olahSsLabel');
    const isSeasonal = wrap && wrap.style.display !== 'none' && toggle && toggle.checked;
    if (mkLabel) {
        mkLabel.textContent = isSeasonal ? 'Seasonal Mann-Kendall' : 'Mann Kendall';
        mkLabel.style.color = isSeasonal ? '#7C3AED' : '#2563EB';
    }
    if (ssLabel) {
        ssLabel.textContent = isSeasonal ? "Seasonal Sen's Slope" : "Sen's Slope";
        ssLabel.style.color = isSeasonal ? '#7C3AED' : '#2563EB';
    }
}

function swapTrendCards() {
    const toggle = document.getElementById('olahSeasonalToggle');
    const mkEl = document.getElementById('olahMkResult');
    const ssEl = document.getElementById('olahSsResult');
    if (!toggle || !mkEl || !ssEl) return;
    const useSeasonal = toggle.checked;
    const mkHtml = useSeasonal ? cachedSeasonalMK : cachedRegularMK;
    const ssHtml = useSeasonal ? cachedSeasonalSS : cachedRegularSS;
    if (!mkHtml || !ssHtml) return;
    mkEl.classList.add('fading');
    ssEl.classList.add('fading');
    setTimeout(() => {
        mkEl.innerHTML = mkHtml;
        ssEl.innerHTML = ssHtml;
        requestAnimationFrame(() => {
            mkEl.classList.remove('fading');
            ssEl.classList.remove('fading');
        });
    }, 200);
}

// ====== Fungsi Bantu Statistik (Client-side) ======
function normalCDF(x) {
    const a1 = 0.254829592, a2 = -0.284496736, a3 = 1.421413741;
    const a4 = -1.453152027, a5 = 1.061405429, p = 0.3275911;
    const sign = x < 0 ? -1 : 1;
    x = Math.abs(x) / Math.sqrt(2);
    const t = 1.0 / (1.0 + p * x);
    const y = 1.0 - ((((a5 * t + a4) * t + a3) * t + a2) * t + a1) * t * Math.exp(-x * x);
    return 0.5 * (1.0 + sign * y);
}

function getCriticalZ(alpha) {
    if (alpha === 0.05) return 1.96;
    if (alpha === 0.01) return 2.576;
    if (alpha === 0.10) return 1.645;
    return 1.96;
}

function calcMannKendallBaseJS(values) {
    const n = values.length;
    if (n < 3) return { S: 0, varS: 0, Z: 0, pValue: 1 };
    let S = 0;
    for (let k = 0; k < n - 1; k++)
        for (let j = k + 1; j < n; j++) {
            const d = values[j] - values[k];
            if (d > 0) S += 1; else if (d < 0) S -= 1;
        }
    const cnt = {};
    values.forEach(v => { const k = String(v); cnt[k] = (cnt[k]||0) + 1; });
    let tSum = 0;
    Object.values(cnt).forEach(c => { if (c > 1) tSum += c * (c-1) * (2*c+5); });
    const varS = (n * (n-1) * (2*n+5) - tSum) / 18;
    let Z = 0;
    if (S > 0) Z = (S - 1) / Math.sqrt(varS);
    else if (S < 0) Z = (S + 1) / Math.sqrt(varS);
    return { S, varS, Z, pValue: 2 * (1 - normalCDF(Math.abs(Z))) };
}

// ====== Seasonal Mann-Kendall & Sen's Slope (Client-side) ======
function calcSeasonalMannKendallJS(dataArray) {
    const byMonth = {};
    dataArray.forEach(d => {
        const yr = Math.floor(d.year);
        const mo = Math.round((d.year - yr) * 12);
        if (!byMonth[mo]) byMonth[mo] = [];
        byMonth[mo].push(d.value);
    });
    let totalS = 0, totalVarS = 0, seasonCount = 0;
    for (const mo in byMonth) {
        const vals = byMonth[mo];
        if (vals.length < 3) continue;
        const mk = calcMannKendallBaseJS(vals);
        totalS += mk.S;
        totalVarS += mk.varS;
        seasonCount++;
    }
    if (seasonCount < 2) return null;
    let Z = 0;
    if (totalS > 0) Z = (totalS - 1) / Math.sqrt(totalVarS);
    else if (totalS < 0) Z = (totalS + 1) / Math.sqrt(totalVarS);
    const pVal = 2 * (1 - normalCDF(Math.abs(Z)));
    const sig = Math.abs(Z) > getCriticalZ(0.05);
    let trend = 'Tidak Ada Trend';
    if (sig) trend = Z > 0 ? 'Meningkat' : 'Menurun';
    return { S: totalS, varS: totalVarS, Z, pValue: pVal, seasonCount, significant: sig, trend };
}

function calcSeasonalSenSlopeJS(dataArray) {
    const byMonth = {};
    dataArray.forEach(d => {
        const yr = Math.floor(d.year);
        const mo = Math.round((d.year - yr) * 12);
        if (!byMonth[mo]) byMonth[mo] = [];
        byMonth[mo].push({ year: yr, value: d.value });
    });
    const allSlopes = [];
    let seasonCount = 0;
    for (const mo in byMonth) {
        const pts = byMonth[mo].sort((a,b) => a.year - b.year);
        if (pts.length < 3) continue;
        seasonCount++;
        for (let i = 0; i < pts.length - 1; i++)
            for (let j = i + 1; j < pts.length; j++) {
                const dx = pts[j].year - pts[i].year;
                if (dx !== 0) allSlopes.push((pts[j].value - pts[i].value) / dx);
            }
    }
    if (seasonCount < 2 || allSlopes.length === 0) return null;
    allSlopes.sort((a,b) => a - b);
    const sc = allSlopes.length;
    const senSlope = sc % 2 === 0 ? (allSlopes[sc/2-1] + allSlopes[sc/2]) / 2 : allSlopes[Math.floor(sc/2)];
    let trend = 'Tidak Ada Trend';
    if (senSlope > 0) trend = 'Meningkat';
    else if (senSlope < 0) trend = 'Menurun';
    return { slope: senSlope, slopeCount: sc, seasonCount, trend };
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
                    filter: (item) => item.dataset.label !== 'Garis Regresi Linear',
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
            if (selectEl.dataset.customized) return;
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
                    optEl.className = 'custom-select-option' + (opt.selected ? ' selected' : '') + (opt.disabled ? ' disabled' : '');
                    optEl.textContent = opt.textContent;
                    optEl.dataset.value = opt.value;

                    if (!opt.disabled) {
                        optEl.addEventListener('click', (e) => {
                            e.stopPropagation();
                            selectEl.value = opt.value;
                            selectEl.dispatchEvent(new Event('change'));
                            wrapper.classList.remove('open');
                            renderOptions();
                        });
                    }
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
                if (selectEl.id === 'olahAgg') {
                    syncAggVisibility();
                } else if (selectEl.id === 'olahMonth') {
                    const dtVal = document.getElementById('olahDtType').value;
                    wrapper.style.display = (dtVal === 'tahunan' || inputPeriod === 'tahunan') ? 'none' : 'inline-block';
                }
                renderOptions();
            });

            // Initial visibility check
            if (selectEl.id === 'olahAgg') {
                syncAggVisibility();
            } else if (selectEl.id === 'olahMonth') {
                const dtVal = document.getElementById('olahDtType').value;
                wrapper.style.display = (dtVal === 'tahunan' || inputPeriod === 'tahunan') ? 'none' : 'inline-block';
            }
        });

        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
        });
    }

    // Call custom setup
    setupCustomSelects();

    // Seasonal toggle change → swap with animation, no reload
    const toggle = document.getElementById('olahSeasonalToggle');
    if (toggle) {
        toggle.addEventListener('change', () => {
            swapTrendCards();
            updateSeasonalLabels();
        });
    }
});
