<?php
session_start();

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Jika tidak ada user, logout
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Ambil statistik
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log");
$total_laporan = mysqli_fetch_assoc($total_query)['total'];

$masuk_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari='Masuk Kerja'");
$total_masuk = mysqli_fetch_assoc($masuk_query)['total'];

$sakit_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari='Sakit'");
$total_sakit = mysqli_fetch_assoc($sakit_query)['total'];

$izin_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari IN ('Izin/Libur Resmi', 'Cuti Pribadi')");
$total_izin = mysqli_fetch_assoc($izin_query)['total'];

// Hitung progress
$durasi_magang = $user['durasi_magang'] ?? 90;
$progress_percentage = $durasi_magang > 0 ? round(($total_laporan / $durasi_magang) * 100, 1) : 0;
$sisa_hari = max(0, $durasi_magang - $total_laporan);

// Cek apakah user sudah setup
$need_setup = empty($user['durasi_magang']) || $user['durasi_magang'] == 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Sistem Daftar Harian Magang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite;
        }

        .shape:nth-child(1) { width: 80px; height: 80px; left: 10%; top: 20%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 120px; height: 120px; right: 10%; top: 60%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 60px; height: 60px; left: 80%; top: 30%; animation-delay: 4s; }
        .shape:nth-child(4) { width: 100px; height: 100px; left: 20%; bottom: 20%; animation-delay: 1s; }
        .shape:nth-child(5) { width: 90px; height: 90px; right: 25%; top: 15%; animation-delay: 3s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-30px) rotate(180deg); opacity: 1; }
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
            animation: fadeInDown 0.5s ease;
        }

        .user-menu {
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: #64748b;
        }

        .btn-logout {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            padding: 8px 18px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
            color: white;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInDown 0.8s ease;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-container {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo-container i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header h1 {
            color: white;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.3rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .alert-setup {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 20px;
            animation: fadeInUp 0.8s ease 0.1s both;
            border-left: 5px solid var(--warning);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-setup i {
            font-size: 2.5rem;
            color: var(--warning);
        }

        .alert-setup-content h3 {
            color: var(--dark);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .alert-setup-content p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 15px;
        }

        .btn-setup {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            padding: 10px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
            color: white;
        }

        .welcome-card {
            background: white;
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 50px;
            box-shadow: 0 20px 80px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .welcome-card h2 {
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .welcome-card .highlight {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-card p {
            color: #64748b;
            font-size: 1.1rem;
            line-height: 1.8;
            max-width: 700px;
            margin: 0 auto;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .menu-card {
            background: white;
            border-radius: 25px;
            padding: 40px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease both;
            text-decoration: none;
            display: block;
        }

        .menu-card:nth-child(1) { animation-delay: 0.3s; }
        .menu-card:nth-child(2) { animation-delay: 0.4s; }
        .menu-card:nth-child(3) { animation-delay: 0.5s; }
        .menu-card:nth-child(4) { animation-delay: 0.6s; }
        .menu-card:nth-child(5) { animation-delay: 0.7s; }
        .menu-card:nth-child(6) { animation-delay: 0.8s; }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .menu-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
        }

        .menu-card:hover::before {
            transform: scaleX(1);
        }

        .menu-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 25px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            transition: all 0.4s ease;
        }

        .menu-card:hover .menu-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .menu-card.add .menu-icon {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .menu-card.view .menu-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .menu-card.dashboard .menu-icon {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        .menu-card.print .menu-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        .menu-card.settings .menu-icon {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            box-shadow: 0 8px 25px rgba(100, 116, 139, 0.4);
        }

        .menu-card.stats .menu-icon {
            background: linear-gradient(135deg, #ec4899, #db2777);
            color: white;
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.4);
        }

        .menu-card h3 {
            color: var(--dark);
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .menu-card p {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .menu-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .quick-stats {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: fadeInUp 0.8s ease 0.9s both;
        }

        .quick-stats h3 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .stat-item {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.2);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            padding: 30px;
            color: rgba(255, 255, 255, 0.9);
            animation: fadeIn 1s ease 1s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .footer p {
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .footer .divider {
            width: 60px;
            height: 3px;
            background: white;
            margin: 20px auto;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .header h1 { font-size: 2.5rem; }
            .header p { font-size: 1.1rem; }
            .welcome-card { padding: 35px 25px; }
            .welcome-card h2 { font-size: 2rem; }
            .menu-grid { grid-template-columns: 1fr; gap: 20px; }
            .menu-card { padding: 30px 25px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .alert-setup { flex-direction: column; text-align: center; }
            .top-bar { justify-content: center; }
            .user-menu { width: 100%; justify-content: space-between; }
        }

        @media (max-width: 480px) {
            .header h1 { font-size: 2rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <!-- Top Bar dengan User Info -->
        <div class="top-bar">
            <div class="user-menu">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    <div class="user-role">Peserta Magang</div>
                </div>
                <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="header">
            <div class="logo-container">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Selamat Datang</h1>
            <p>Sistem Daftar Harian Magang - Politeknik Negeri Padang</p>
        </div>

        <?php if ($need_setup): ?>
        <div class="alert-setup">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="alert-setup-content">
                <h3>Lengkapi Pengaturan Awal</h3>
                <p>Silakan lengkapi profil dan durasi magang Anda untuk memulai menggunakan sistem.</p>
                <a href="settings.php?first=1" class="btn-setup">
                    <i class="fas fa-arrow-right"></i> Mulai Setup
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="welcome-card">
            <h2>Halo, <span class="highlight"><?= htmlspecialchars($user['nama_lengkap']) ?></span>! 👋</h2>
            <p>
                Selamat datang di platform pencatatan laporan harian magang Anda. 
                Kelola aktivitas magang dengan mudah, pantau progress, dan buat laporan profesional 
                dengan satu sistem yang terintegrasi.
            </p>
        </div>

        <div class="menu-grid">
            <a href="index.php" class="menu-card add">
                <div class="menu-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Tambah Laporan</h3>
                <p>Catat aktivitas harian magang Anda dengan mudah dan cepat</p>
            </a>

            <a href="index.php" class="menu-card view">
                <div class="menu-icon">
                    <i class="fas fa-list-alt"></i>
                </div>
                <h3>Lihat Laporan</h3>
                <p>Akses dan kelola semua laporan harian yang telah dibuat</p>
            </a>

            <a href="dashboard.php" class="menu-card dashboard">
                <span class="menu-badge">Populer</span>
                <div class="menu-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Dashboard</h3>
                <p>Pantau progress dan statistik magang Anda secara visual</p>
            </a>

            <a href="cetak_laporan.php" class="menu-card print">
                <div class="menu-icon">
                    <i class="fas fa-print"></i>
                </div>
                <h3>Cetak Laporan</h3>
                <p>Export dan cetak laporan dalam format PDF profesional</p>
            </a>

            <a href="settings.php" class="menu-card settings">
                <div class="menu-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h3>Pengaturan</h3>
                <p>Kelola profil dan konfigurasi sistem magang Anda</p>
            </a>

            <a href="dashboard.php" class="menu-card stats">
                <div class="menu-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Statistik</h3>
                <p>Lihat analisis lengkap aktivitas dan kehadiran magang</p>
            </a>
        </div>

        <div class="quick-stats">
            <h3>
                <i class="fas fa-bolt"></i>
                Ringkasan Cepat
            </h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= $total_laporan ?></div>
                    <div class="stat-label">Total Laporan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $total_masuk ?></div>
                    <div class="stat-label">Masuk Kerja</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $progress_percentage ?>%</div>
                    <div class="stat-label">Progress</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $sisa_hari ?></div>
                    <div class="stat-label">Hari Tersisa</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong>Politeknik Negeri Padang</strong></p>
            <div class="divider"></div>
            <p>Jl. Kampus Limau Manis, Padang, Sumatera Barat</p>
            <p style="margin-top: 10px; font-size: 0.85rem; opacity: 0.8;">
                © 2025 - Sistem Daftar Harian Magang
            </p>
        </div>
    </div>

    <script>
        document.querySelectorAll('.menu-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>