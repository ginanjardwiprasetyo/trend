/**
 * TrendHidro - sidebar.js
 * Logika panel sisi: toggle, pemilih DAS, jenis data, agregasi, metode
 */

document.addEventListener('DOMContentLoaded', () => {

    // ====== TOGGLE SIDEBAR ======
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    // Panah default
    updateToggleArrow();

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        updateToggleArrow();
    });

    function updateToggleArrow() {
        if (sidebar.classList.contains('collapsed')) {
            toggle.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
        } else {
            toggle.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
        }
    }

    // ====== PEMILIH DAS ======
    const dasSelect = document.getElementById('dasSelect');
    dasSelect.addEventListener('change', (e) => {
        const val = e.target.value;
        if (val) {
            if (typeof loadDAS === 'function') loadDAS(val);
        } else {
            if (typeof clearDAS === 'function') clearDAS();
            if (typeof map !== 'undefined') map.setView([-2.5, 118], 5);
        }
    });

    // ====== ELEMEN DOM JENIS DATA & METODE ======
    const dataRadioBtns = document.querySelectorAll('#dataTypeGroup .radio-btn');
    const aggBtns = document.querySelectorAll('#aggregationGroup .agg-btn');
    const methodItems = document.querySelectorAll('.method-item');
    const aggregationWrapper = document.getElementById('aggregationWrapper');
    const legendWrapper = document.getElementById('legendWrapper');

    // ====== AGREGASI & BULAN VISIBILITY ======
    function updateAggregationVisibility() {
        const activeData = document.querySelector('#dataTypeGroup .radio-btn.active');
        if (!activeData) return;

        const value = activeData.dataset.value;
        const monthWrapper = document.getElementById('monthWrapper');
        const monthSelect = document.getElementById('monthSelect');
        
        if (value === 'musiman') {
            if (monthWrapper) monthWrapper.classList.remove('hidden');
            // Update options to seasonal blocks
            if (monthSelect) {
                monthSelect.innerHTML = `
                    <option value="1,2,3">Jan–Feb–Mar</option>
                    <option value="4,5,6">Apr–Mei–Jun</option>
                    <option value="7,8,9">Jul–Agus–Sep</option>
                    <option value="10,11,12">Okt–Nov–Des</option>
                `;
            }
        } else if (value === 'bulanan') {
            if (monthWrapper) monthWrapper.classList.remove('hidden');
            // Reset to monthly options
            if (monthSelect) {
                monthSelect.innerHTML = `
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
        } else {
            // Tahunan
            if (monthWrapper) monthWrapper.classList.add('hidden');
        }
    }

    // ====== LEGENDA VISIBILITY ======
    function updateLegendVisibility() {
        const activeMethodItem = document.querySelector('.method-item.active');
        if (activeMethodItem) {
            const method = activeMethodItem.dataset.method;
            if (typeof showLegend === 'function') showLegend(method);
            legendWrapper.classList.remove('hidden');
        } else {
            legendWrapper.classList.add('hidden');
        }
    }

    // ====== HANDLE KLIK JENIS DATA ======
    dataRadioBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            dataRadioBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            updateAggregationVisibility();
            if (typeof fetchAnalytics === 'function') fetchAnalytics();
        });
    });

    // ====== HANDLE KLIK AGREGASI ======
    aggBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            aggBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (typeof fetchAnalytics === 'function') fetchAnalytics();
        });
    });

    // ====== HANDLE KLIK BULAN ======
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', () => {
            if (typeof fetchAnalytics === 'function') fetchAnalytics();
        });
    }

    // ====== HANDLE KLIK METODE (Radio Behavior + Toggle) ======
    methodItems.forEach(item => {
        item.addEventListener('click', () => {
            const method = item.dataset.method;
            const toggleSwitch = item.querySelector('.toggle-switch');
            const isActive = toggleSwitch.classList.contains('on');

            if (isActive) {
                // Jika sudah aktif, matikan
                toggleSwitch.classList.remove('on');
                item.classList.remove('active');
                if (typeof currentMethod !== 'undefined') currentMethod = null;

                // Sembunyikan filter kualitas data
                const qWrapper = document.getElementById('qualityFilterWrapper');
                if (qWrapper) qWrapper.classList.add('hidden');

                if (typeof resetMarkerIcons === 'function') resetMarkerIcons();
            } else {
                // Matikan semua metode lainnya (radio-behavior)
                methodItems.forEach(mi => {
                    mi.classList.remove('active');
                    mi.querySelector('.toggle-switch').classList.remove('on');
                });

                // Aktifkan yang ini
                toggleSwitch.classList.add('on');
                item.classList.add('active');
                if (typeof currentMethod !== 'undefined') currentMethod = method;

                // Munculkan filter kualitas data
                const qWrapper = document.getElementById('qualityFilterWrapper');
                if (qWrapper) qWrapper.classList.remove('hidden');

                if (typeof showLoading === 'function') showLoading(true, false); // Quiet mode
                setTimeout(async () => {
                    if (typeof runAnalysis === 'function') await runAnalysis(method);
                    if (typeof showLoading === 'function') showLoading(false, false);
                }, 300);
            }

            updateLegendVisibility();
        });
    });

    // ====== HANDLE KLIK FILTER KUALITAS DATA ======
    const qualityToggle16Item = document.getElementById('qualityToggle16Item');
    const toggleHide16 = document.getElementById('toggleHide16');
    const qualityToggle30Item = document.getElementById('qualityToggle30Item');
    const toggleHide30 = document.getElementById('toggleHide30');
    
    if (qualityToggle16Item && toggleHide16) {
        qualityToggle16Item.addEventListener('click', (e) => {
            const isFiltering = toggleHide16.classList.toggle('on');
            if (isFiltering) qualityToggle16Item.style.borderColor = 'var(--color-primary)';
            else qualityToggle16Item.style.borderColor = 'var(--color-border)';

            // Re-run analysis to update markers with new filter
            if (typeof currentMethod !== 'undefined' && currentMethod) {
                if (typeof runAnalysis === 'function') runAnalysis(currentMethod);
            } else {
                // Even without a method, apply filter to station markers
                if (typeof applyQualityFilter === 'function') applyQualityFilter();
            }
            // Update legend to show/hide warning icon
            if (typeof showLegend === 'function') showLegend();
        });
    }

    if (qualityToggle30Item && toggleHide30) {
        qualityToggle30Item.addEventListener('click', (e) => {
            const isFiltering = toggleHide30.classList.toggle('on');
            if (isFiltering) qualityToggle30Item.style.borderColor = 'var(--color-primary)';
            else qualityToggle30Item.style.borderColor = 'var(--color-border)';

            // Re-run analysis to update markers with new filter
            if (typeof currentMethod !== 'undefined' && currentMethod) {
                if (typeof runAnalysis === 'function') runAnalysis(currentMethod);
            } else {
                if (typeof applyQualityFilter === 'function') applyQualityFilter();
            }
            if (typeof showLegend === 'function') showLegend();
        });
    }

    // ====== CUSTOM YEAR PICKER LOGIC ======
    const displayFrom = document.getElementById('displayFrom');
    const displayTo = document.getElementById('displayTo');
    const gridFrom = document.getElementById('gridFrom');
    const gridTo = document.getElementById('gridTo');
    const inputFrom = document.getElementById('yearFrom');
    const inputTo = document.getElementById('yearTo');

    if (displayFrom && gridFrom) {
        displayFrom.addEventListener('click', (e) => {
            e.stopPropagation();
            if (gridTo) gridTo.classList.add('hidden');
            gridFrom.classList.toggle('hidden');
            const isOpening = !gridFrom.classList.contains('hidden');
            if (isOpening) {
                const year = parseInt(document.getElementById('yearFrom').value) || 1980;
                window.viewedDecadeFrom = Math.floor(year / 10) * 10;
                if (typeof renderDecadeGrid === 'function') renderDecadeGrid('From', window.viewedDecadeFrom);
            }
        });
    }

    if (displayTo && gridTo) {
        displayTo.addEventListener('click', (e) => {
            e.stopPropagation();
            if (gridFrom) gridFrom.classList.add('hidden');
            gridTo.classList.toggle('hidden');
            const isOpening = !gridTo.classList.contains('hidden');
            if (isOpening) {
                const year = parseInt(document.getElementById('yearTo').value) || 2025;
                window.viewedDecadeTo = Math.floor(year / 10) * 10;
                if (typeof renderDecadeGrid === 'function') renderDecadeGrid('To', window.viewedDecadeTo);
            }
        });
    }

    document.addEventListener('click', (e) => {
        // Navigasi dekade
        const navBtn = e.target.closest('.year-nav-btn');
        if (navBtn) {
            e.stopPropagation();
            const target = navBtn.dataset.target;
            const isNext = navBtn.classList.contains('next');
            
            if (target === 'From') {
                window.viewedDecadeFrom += isNext ? 10 : -10;
                if (typeof renderDecadeGrid === 'function') renderDecadeGrid('From', window.viewedDecadeFrom);
            } else {
                window.viewedDecadeTo += isNext ? 10 : -10;
                if (typeof renderDecadeGrid === 'function') renderDecadeGrid('To', window.viewedDecadeTo);
            }
            return;
        }

        const item = e.target.closest('.year-item');
        if (!item) {
            if (gridFrom) gridFrom.classList.add('hidden');
            if (gridTo) gridTo.classList.add('hidden');
            return;
        }

        const grid = item.closest('.year-grid');
        const year = item.dataset.year;

        if (grid.id === 'gridFrom') {
            inputFrom.value = year;
            displayFrom.textContent = year;
            gridFrom.classList.add('hidden');
            inputFrom.dispatchEvent(new Event('change'));
        } else {
            inputTo.value = year;
            displayTo.textContent = year;
            gridTo.classList.add('hidden');
            inputTo.dispatchEvent(new Event('change'));
        }

        if (typeof updatePickerActiveState === 'function') updatePickerActiveState();
    });

    // Handle year inputs
    if (inputFrom) inputFrom.addEventListener('change', () => { if (typeof fetchAnalytics === 'function') fetchAnalytics(); });
    if (inputTo) inputTo.addEventListener('change', () => { if (typeof fetchAnalytics === 'function') fetchAnalytics(); });

    // Inisialisasi awal saat load
    updateAggregationVisibility();
    updateLegendVisibility();
});

// ====== RESET TOGGLES ======
function resetMethodToggles() {
    document.querySelectorAll('.method-item').forEach(mi => {
        mi.classList.remove('active');
        mi.querySelector('.toggle-switch').classList.remove('on');
    });

    const legendWrapper = document.getElementById("legendWrapper");
    if (legendWrapper) legendWrapper.classList.add("hidden");
}

// ====== DAPATKAN PARAMETER SAAT INI ======
function getCurrentParams() {
    const dataType = document.querySelector('#dataTypeGroup .radio-btn.active')?.dataset.value || 'bulanan';
    const yearFrom = document.getElementById('yearFrom')?.value || null;
    const yearTo = document.getElementById('yearTo')?.value || null;
    let month = document.getElementById('monthSelect')?.value || 'all';
    
    // Jika tahunan, paksa month filter ke all agar tidak terfilter sisa pilihan sebelumnya
    if (dataType === 'tahunan') month = 'all';
    if (dataType === 'musiman' && (!month || month === 'all')) month = '1,2,3';

    const aggregation = document.querySelector('#aggregationGroup .agg-btn.active')?.dataset.value || 'rerata';

    return {
        dataType,
        aggregation: aggregation,
        yearFrom: yearFrom ? parseInt(yearFrom) : null,
        yearTo: yearTo ? parseInt(yearTo) : null,
        month: month
    };
}
