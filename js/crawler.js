function log(msg, type = '') {
    const box = document.getElementById('logBox');
    const p = document.createElement('p');
    p.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
    if (type) p.className = `log-${type}`;
    box.appendChild(p);
    box.scrollTop = box.scrollHeight;
}

function setButtonsState(disabled) {
    document.getElementById('btnPhase1').disabled = disabled;
    document.getElementById('btnPhase2').disabled = disabled;
    document.getElementById('btnPhase3').disabled = disabled;
    
    const spinner = document.getElementById('mainSpinner');
    if (disabled) {
        spinner.style.display = 'block';
    } else {
        spinner.style.display = 'none';
    }
}

async function fetchStations() {
    try {
        const res = await fetch('php/get_crawled_stations.php');
        const json = await res.json();
        if (json.success) {
            document.getElementById('statStations').textContent = json.data.length;
            return json.data;
        }
    } catch (e) {
        console.error(e);
    }
    return [];
}

async function init() {
    await fetchStations();
}

async function fetchWithRetry(url, maxRetries = 3, delayMs = 2000) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const res = await fetch(url);
            // Coba parse json, jika gagal (mungkin server error/timeout/bad gateway) akan masuk ke catch
            const json = await res.json();
            return json;
        } catch (e) {
            if (i === maxRetries - 1) throw e;
            log(`  Request gagal (${e.message}). Mencoba lagi dalam ${delayMs/1000} detik... (Percobaan ${i+2}/${maxRetries})`, 'warn');
            await new Promise(resolve => setTimeout(resolve, delayMs));
        }
    }
}

// Tahap 1: Ambil Daftar Stasiun Utama
async function runPhase1() {
    setButtonsState(true);
    log('Memulai Tahap 1: Sinkronisasi Daftar Stasiun...', 'info');
    
    let page = 1;
    let totalInserted = 0;
    
    while (true) {
        log(`Membaca daftar stasiun Halaman ${page}...`);
        try {
            const json = await fetchWithRetry(`php/crawler_stations.php?page=${page}`);
            
            if (json.success) {
                log(`  Sukses: ${json.message}`);
                if (json.count === 0) {
                    log(`  Tidak ada data lagi di Halaman ${page}. Selesai.`);
                    break; // No more rows
                }
                totalInserted += json.count;
            } else {
                log(`  Error: ${json.message}`, 'error');
                break;
            }
        } catch (e) {
            log(`  Fatal Error: ${e.message}`, 'error');
            break;
        }
        
        page++;
        // Safety break
        if (page > 100) break;
    }
    
    await fetchStations();
    log(`Tahap 1 Selesai. Total stasiun diproses: ${totalInserted}.`, 'info');
    setButtonsState(false);
}

// Tahap 2: Ambil Metadata tiap stasiun
async function runPhase2() {
    setButtonsState(true);
    log('Memulai Tahap 2: Ambil Metadata Stasiun (Pengelola, Tahun)...', 'info');
    
    const stations = await fetchStations();
    if (stations.length === 0) {
        log('Daftar stasiun kosong. Jalankan Tahap 1 terlebih dahulu.', 'warn');
        setButtonsState(false);
        return;
    }
    
    let successCount = 0;
    
    for (let i = 0; i < stations.length; i++) {
        const st = stations[i];
        log(`Mendapatkan metadata untuk stasiun: ${st.nama_stasiun} (ID: ${st.id}) [${i+1}/${stations.length}]`);
        
        try {
            const json = await fetchWithRetry(`php/crawler_station_meta.php?id=${st.id}`);
            
            if (json.success) {
                log(`  Berhasil: ${json.data.pengelola || 'Tanpa Pengelola'}, ${json.data.tahun_awal}-${json.data.tahun_akhir}`);
                successCount++;
            } else {
                log(`  Gagal: ${json.message}`, 'warn');
            }
        } catch (e) {
            log(`  Request Error: ${e.message}`, 'error');
        }
    }
    
    log(`Tahap 2 Selesai. Berhasil sinkronisasi ${successCount} stasiun.`, 'info');
    setButtonsState(false);
}

// Tahap 3: Ambil Data Hujan Harian
async function runPhase3() {
    setButtonsState(true);
    log('Memulai Tahap 3: Tarik Data Hujan Harian...', 'info');
    
    const stations = await fetchStations();
    if (stations.length === 0) {
        log('Daftar stasiun kosong.', 'warn');
        setButtonsState(false);
        return;
    }
    
    let totalInsert = 0;
    
    for (let i = 0; i < stations.length; i++) {
        const st = stations[i];
        if (!st.tahun_awal || !st.tahun_akhir) {
            log(`Melewati stasiun ${st.nama_stasiun} karena tahun awal/akhir kosong.`, 'warn');
            continue;
        }
        
        log(`>> Mulai stasiun: ${st.nama_stasiun} (ID: ${st.id}) [${i+1}/${stations.length}]`);
        
        for (let y = st.tahun_awal; y <= st.tahun_akhir; y++) {
            try {
                const json = await fetchWithRetry(`php/crawler_daily_data.php?id=${st.id}&year=${y}`);
                
                if (json.success) {
                    log(`  Tahun ${y}: ${json.message}`);
                    if (json.count) totalInsert += json.count;
                } else {
                    log(`  Tahun ${y} Error: ${json.message}`, 'error');
                }
            } catch (e) {
                log(`  Tahun ${y} Request Error: ${e.message}`, 'error');
            }
        }
    }
    
    log(`Tahap 3 Selesai. Total data harian baru/diperbarui: ${totalInsert}`, 'info');
    setButtonsState(false);
}

document.addEventListener('DOMContentLoaded', init);
