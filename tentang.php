<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <title>Tentang — TrendHidro</title>
    <meta name="description"
        content="Informasi profil peneliti dan institusi di balik pengembangan aplikasi TrendHidro.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #F9FAFB;
            overflow-y: auto;
            height: auto;
        }

        /* --- Floating Gradient Orbs --- */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
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

        .about-header {
            padding: 120px 24px 40px;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .about-header h1 {
            font-size: 3rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.025em;
            margin-bottom: 16px;
        }

        .about-header p {
            font-size: 1.15rem;
            color: #4B5563;
            line-height: 1.6;
        }

        .about-container {
            max-width: 1100px;
            margin: 0 auto 100px;
            padding: 0 24px;
            position: relative;
            z-index: 10;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 32px;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        .info-card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-card h2::before {
            content: '';
            width: 4px;
            height: 24px;
            background: #2563EB;
            border-radius: 2px;
        }

        .field-group {
            margin-bottom: 20px;
        }

        .field-group:last-child {
            margin-bottom: 0;
        }

        .field-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6B7280;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .field-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
        }

        .field-subtext {
            font-size: 0.9rem;
            color: #4B5563;
            margin-top: 2px;
        }

        .uni-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #EFF6FF;
            color: #1D4ED8;
            padding: 12px 20px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.95rem;
            margin-top: 12px;
        }

        .uni-badge svg {
            width: 20px;
            height: 20px;
        }

        .profile-img-wrap {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 24px;
            overflow: hidden;
            position: relative;
            border: 4px solid #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .profile-img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.2s;
            color: #4B5563;
            text-decoration: none;
        }

        .contact-item:hover {
            background: #EFF6FF;
            color: #2563EB;
        }

        .contact-item svg {
            width: 20px;
            height: 20px;
            color: #9CA3AF;
        }

        .landing-footer {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 24px;
            font-size: 0.82rem;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            margin-top: 60px;
        }
    </style>
</head>

<body>
    <!-- BACKGROUND ORBS -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- NAVBAR -->
    <?php include 'navbar.php'; ?>

    <!-- HEADER -->
    <div class="about-header">
        <h1>Tentang</h1>
        <p>Aplikasi ini dikembangkan sebagai bagian dari penelitian akademis di Magister Teknik Sipil, Departemen Teknik
            Sipil dan Lingkungan, Universitas Gadjah Mada.</p>
    </div>



    <!-- MAIN CONTAINER -->
    <div class="about-container">
        <div class="profile-grid">

            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-img-wrap">
                    <div class="profile-img-placeholder">
                        <img src="img/Logo-UGM(3).png" alt="Logo UGM" style="width: 180px; height: auto;">
                    </div>
                </div>

                <div class="info-card">
                    <h2>Kontak</h2>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <a href="mailto:ginanjar@rekayasa-sipil.my.id" class="contact-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <span>Email</span>
                        </a>
                        <a href="https://linkedin.com/in/ginanjar-dwi-prasetyo" target="_blank" class="contact-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z" />
                                <rect x="2" y="9" width="4" height="12" />
                                <circle cx="4" cy="4" r="2" />
                            </svg>
                            <span>LinkedIn</span>
                        </a>
                        <a href="https://instagram.com/gind.id" target="_blank" class="contact-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                                <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" />
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                            </svg>
                            <span>Instagram</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-main">
                <div class="info-card">
                    <h2>Identitas Diri</h2>

                    <div class="field-group">
                        <div class="field-label">Nama Lengkap</div>
                        <div class="field-value">Ginanjar Dwi Prasetyo</div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Program Studi</div>
                        <div class="field-value">Magister Teknik Sipil</div>
                        <div class="field-subtext">Departemen Teknik Sipil dan Lingkungan</div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">ORCID</div>
                        <div class="field-subtext"><a href="https://orcid.org/0000-0002-6766-0391" target="_blank"
                                style="color:#2563EB;">https://orcid.org/0000-0002-6766-0391</a></div>
                    </div>

                </div>

                <div class="info-card">
                    <h2>Pembimbing</h2>

                    <div class="field-group">
                        <div class="field-label">Dosen Pembimbing Utama</div>
                        <div class="field-value">Dr. Ir. Istiarto, M.Eng., IPU.</div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Dosen Pembimbing Pendamping</div>
                        <div class="field-value">Ir. Vempi Satriya Adi Hendrawan, S.T., M.Env., Ph.D.</div>
                    </div>
                </div>

                <!-- <div class="info-card">
                    <h2>Publikasi</h2>
                    <p style="color: #4B5563; line-height: 1.6; font-size: 0.95rem;">
                        Dalam proses..
                    </p>
                </div> -->
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <?php include 'footer.php'; ?>

</body>

</html>