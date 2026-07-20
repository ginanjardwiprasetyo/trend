<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>TrendHidro — Platform Olah Data Runtut Waktu</title>
    <meta name="description"
        content="TrendHidro adalah platform olah data runtut waktu interaktif. Jelajahi peta curah hujan atau olah data Anda sendiri menggunakan metode Mann-Kendall, Sen's Slope, dan Regresi Linear.">
    <meta name="keywords"
        content="hidrologi, trend curah hujan, mann-kendall, sen's slope, regresi linear, pemetaan interaktif, olah data, trendhidro">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            overflow-y: auto;
            height: auto;
        }

        /* --- Floating Gradient Orbs --- */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
            animation: float-orb 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #3B82F6 0%, transparent 70%);
            top: -120px;
            right: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #8B5CF6 0%, transparent 70%);
            bottom: -80px;
            left: -80px;
            animation-delay: -7s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #06B6D4 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -14s;
        }

        @keyframes float-orb {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            25% {
                transform: translate(30px, -20px) scale(1.05);
            }

            50% {
                transform: translate(-20px, 15px) scale(0.95);
            }

            75% {
                transform: translate(15px, 25px) scale(1.02);
            }
        }

        @keyframes float-orb-center {

            0%,
            100% {
                transform: translate(-50%, -50%) scale(1);
            }

            25% {
                transform: translate(calc(-50% + 30px), calc(-50% - 20px)) scale(1.05);
            }

            50% {
                transform: translate(calc(-50% - 20px), calc(-50% + 15px)) scale(0.95);
            }

            75% {
                transform: translate(calc(-50% + 15px), calc(-50% + 25px)) scale(1.02);
            }
        }

        .orb-3 {
            animation: float-orb-center 20s ease-in-out infinite;
            animation-delay: -14s;
        }


        /* --- Hero Section --- */
        .hero {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 140px 24px 40px;
            animation: fadeUp 0.8s ease-out both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.15);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 20px;
            animation: fadeUp 0.8s ease-out 0.1s both;
        }

        .hero-badge svg {
            width: 14px;
            height: 14px;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
            max-width: 700px;
            animation: fadeUp 0.8s ease-out 0.15s both;
        }

        .hero h1 span {
            background: linear-gradient(135deg, #2563EB 0%, #7C3AED 50%, #06B6D4 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--color-text-secondary);
            max-width: 540px;
            margin-bottom: 48px;
            animation: fadeUp 0.8s ease-out 0.25s both;
        }

        /* --- Main Cards --- */
        .cards-container {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            max-width: 820px;
            margin: 0 auto;
            padding: 0 24px 48px;
            animation: fadeUp 0.8s ease-out 0.35s both;
        }

        .main-card {
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: var(--radius-panel);
            padding: 36px 28px;
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }

        .main-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: rgba(37, 99, 235, 0.3);
        }

        .main-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.02) 0%, transparent 60%);
            transition: opacity 0.4s;
            opacity: 0;
        }

        .main-card:hover::before {
            opacity: 1;
        }

        .card-icon {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            margin-bottom: 20px;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-card:hover .card-icon {
            transform: scale(1.1) rotate(-3deg);
        }

        .card-icon svg {
            width: 28px;
            height: 28px;
        }

        .card-icon.blue {
            background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
            color: #2563EB;
        }

        .card-icon.violet {
            background: linear-gradient(135deg, #EDE9FE 0%, #DDD6FE 100%);
            color: #7C3AED;
        }

        .main-card h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .main-card p {
            font-size: 0.9rem;
            color: var(--color-text-secondary);
            line-height: 1.65;
            margin-bottom: 20px;
        }

        .card-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--color-primary);
            transition: gap 0.3s;
        }

        .main-card:hover .card-cta {
            gap: 10px;
        }

        .card-cta svg {
            width: 16px;
            height: 16px;
            transition: transform 0.3s;
        }

        .main-card:hover .card-cta svg {
            transform: translateX(2px);
        }

        /* --- Secondary Nav --- */
        .secondary-nav {
            position: relative;
            z-index: 1;
            max-width: 820px;
            margin: 0 auto;
            padding: 0 24px 80px;
            animation: fadeUp 0.8s ease-out 0.5s both;
            text-align: center;
        }

        .secondary-nav h3 {
            font-size: 0.78rem;
            font-weight: 700;
            color: #9CA3AF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 20px;
        }

        .sec-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .sec-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-btn);
            text-decoration: none;
            color: var(--color-text);
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.3s;
            min-width: 160px;
            justify-content: center;
        }

        .sec-link:hover {
            background: #fff;
            border-color: var(--color-primary);
            color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .sec-link svg {
            width: 18px;
            height: 18px;
            color: var(--color-primary);
            flex-shrink: 0;
        }

        /* --- Footer --- */
        .landing-footer {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 24px;
            font-size: 0.82rem;
            color: #9CA3AF;
            border-top: 1px solid var(--color-border);
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .cards-container {
                grid-template-columns: 1fr;
                max-width: 420px;
            }

            .sec-links {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>

<body>

    <!-- Floating Gradient Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- NAVBAR -->
    <?php include 'navbar.php'; ?>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-badge">
            Platform Olah Data Runtut Waktu
        </div>
        <h1>Olah <span>Data Runtut Waktu</span> dengan Mudah</h1>
        <p>Jelajahi peta curah hujan interaktif atau olah data runtut waktu Anda sendiri memakai metode uji
            Mann-Kendall,
            Sen's Slope, dan Regresi Linear.</p>
    </section>

    <!-- MAIN CARDS -->
    <div class="cards-container">
        <!-- Card 1: Peta Interaktif -->
        <a href="peta" class="main-card" id="card-peta">
            <div class="card-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6" />
                    <line x1="8" y1="2" x2="8" y2="18" />
                    <line x1="16" y1="6" x2="16" y2="22" />
                </svg>
            </div>
            <h2>Peta Interaktif</h2>
            <p>Lihat trend curah hujan di wilayah sungai Bengawan Solo dengan peta interaktif.</p>
            <span class="card-cta">
                Buka Peta
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
            </span>
        </a>

        <!-- Card 2: Olah Data Anda -->
        <a href="olah-data" class="main-card" id="card-olah">
            <div class="card-icon violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
            </div>
            <h2>Olah Data Anda</h2>
            <p>Upload file CSV atau Excel Anda sendiri dan lihat hasil olahan berupa grafik,
                statistik, dan rekapitulasi lengkap.
            </p>
            <span class="card-cta">
                Upload Data
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
            </span>
        </a>
    </div>

    <!-- SECONDARY NAV -->
    <div class="secondary-nav">
        <h3>Jelajahi Lebih Lanjut</h3>
        <div class="sec-links">
            <a href="data" class="sec-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <ellipse cx="12" cy="5" rx="9" ry="3" />
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                </svg>
                Data
            </a>
            <a href="dok" class="sec-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                Dokumentasi
            </a>
            <a href="tentang" class="sec-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12.01" y2="8" />
                </svg>
                Tentang
            </a>
        </div>
    </div>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

</body>

</html>