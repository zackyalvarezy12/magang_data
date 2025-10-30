<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; 

// Ambil statistik
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log");
$total_data = mysqli_fetch_assoc($total_query)['total'];

$masuk_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari='Masuk Kerja'");
$total_masuk = mysqli_fetch_assoc($masuk_query)['total'];

$sakit_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari='Sakit'");
$total_sakit = mysqli_fetch_assoc($sakit_query)['total'];

$izin_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari IN ('Izin/Libur Resmi', 'Cuti Pribadi')");
$total_izin = mysqli_fetch_assoc($izin_query)['total'];

// Ambil data user untuk cek durasi magang
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = {$_SESSION['user_id']}");
$user = mysqli_fetch_assoc($user_query);
$durasi_magang = $user['durasi_magang'] ?? 0;
$progress_percentage = $durasi_magang > 0 ? ($total_data / $durasi_magang) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Harian Magang - <?= htmlspecialchars($user['nama_lengkap']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
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
            padding: 20px 0;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Section */
        .header-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-left p {
            color: #64748b;
            font-size: 1rem;
        }

        .header-left .user-greeting {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            padding: 10px 15px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .user-greeting .user-avatar {
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

        .user-greeting .user-info {
            flex: 1;
        }

        .user-greeting .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .user-greeting .user-role {
            font-size: 0.8rem;
            color: #64748b;
        }

        .header-right {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-nav {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, var(--secondary), #0891b2);
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }

        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
            color: white;
        }

        .btn-settings {
            background: white;
            color: var(--dark);
            border: 2px solid #e2e8f0;
        }

        .btn-settings:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-logout {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
            color: white;
        }

        /* Progress Mini Card */
        .progress-mini {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 30px;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .progress-mini-left {
            flex: 1;
            min-width: 200px;
        }

        .progress-mini-left p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .progress-bar-mini {
            background: #cbd5e1;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), var(--secondary));
            border-radius: 10px;
            transition: width 1s ease;
        }

        .progress-mini-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .progress-stat {
            text-align: center;
        }

        .progress-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-stat .label {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .stat-card.total .icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .stat-card.masuk .icon {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .stat-card.sakit .icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .stat-card.izin .icon {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-card h4 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .filter-section .row {
            align-items: end;
        }

        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 5px;
        }

        .btn-filter:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-print {
            background: var(--success);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-print:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-reset {
            background: #64748b;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 500;
            width: 100%;
        }

        .btn-reset:hover {
            background: #475569;
        }

        /* Table Section */
        .table-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease;
        }

        .table-section h2 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
            border: none;
            text-align: center;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            color: var(--dark);
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
        }

        .table tbody td:nth-child(3) {
            text-align: left;
        }

        .table tbody td:last-child {
            min-width: 220px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-masuk {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-sakit {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-izin {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .btn-action {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .btn-view:hover {
            background: #1e40af;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .btn-download {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .btn-download:hover {
            background: #10b981;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-edit {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .btn-edit:hover {
            background: #f59e0b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Required indicator */
        .required {
            color: var(--danger);
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-left h1 {
                font-size: 1.5rem;
            }

            .header-right {
                width: 100%;
                flex-direction: column;
            }

            .btn-nav {
                width: 100%;
                justify-content: center;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-card, .table-section {
                padding: 20px;
            }

            .table {
                font-size: 0.85rem;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .btn-action {
                width: 100%;
            }

            .table tbody td:last-child {
                min-width: auto;
            }

            .progress-mini {
                flex-direction: column;
            }

            .progress-mini-right {
                width: 100%;
                justify-content: space-around;
            }

            .user-greeting {
                width: 100%;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Time hint */
        .time-hint {
            font-size: 0.85rem;
            color: #64748b;
            font-style: italic;
            margin-top: 5px;
        }

        /* Form file input custom */
        .form-control[type="file"] {
            padding: 10px;
        }

        .form-text {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        /* Scroll to top button */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            z-index: 1000;
        }

        .scroll-top.show {
            opacity: 1;
        }

        .scroll-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #10b981;
            animation: slideDown 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-graduation-cap"></i>
                        Sistem Daftar Harian Magang
                    </h1>
                    <p>Politeknik Negeri Padang – Laporan Harian Peserta Magang</p>
                    
                    <!-- User Greeting -->
                    <div class="user-greeting">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                            <div class="user-role">Peserta Magang</div>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <a href="dashboard.php" class="btn-nav btn-dashboard">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                    <a href="settings.php" class="btn-nav btn-settings">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                    <a href="logout.php" class="btn-nav btn-logout" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
            <div>
                <strong>Berhasil!</strong> Laporan dan file dokumentasi berhasil ditambahkan.
            </div>
        </div>
        <?php endif; ?>

        <!-- Progress Mini -->
        <?php if ($durasi_magang > 0): ?>
        <div class="progress-mini">
            <div class="progress-mini-left">
                <p><i class="fas fa-tasks"></i> Progress Magang Anda</p>
                <div class="progress-bar-mini">
                    <div class="progress-bar-fill" style="width: <?= min(100, $progress_percentage) ?>%;"></div>
                </div>
            </div>
            <div class="progress-mini-right">
                <div class="progress-stat">
                    <div class="value"><?= $total_data ?></div>
                    <div class="label">Tercatat</div>
                </div>
                <div class="progress-stat">
                    <div class="value"><?= $durasi_magang ?></div>
                    <div class="label">Target</div>
                </div>
                <div class="progress-stat">
                    <div class="value"><?= number_format($progress_percentage, 1) ?>%</div>
                    <div class="label">Progress</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card total">
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3><?= $total_data ?></h3>
                <p>Total Laporan</p>
            </div>
            <div class="stat-card masuk">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3><?= $total_masuk ?></h3>
                <p>Masuk Kerja</p>
            </div>
            <div class="stat-card sakit">
                <div class="icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h3><?= $total_sakit ?></h3>
                <p>Sakit</p>
            </div>
            <div class="stat-card izin">
                <div class="icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3><?= $total_izin ?></h3>
                <p>Izin/Cuti</p>
            </div>
        </div>

        <!-- Form Input -->
        <div class="form-card">
            <h4>
                <i class="fas fa-plus-circle"></i>
                Tambah Laporan Harian
            </h4>
            <form action="add_log.php" method="POST" enctype="multipart/form-data" id="logForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Kegiatan <span class="required">*</span></label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Hari <span class="required">*</span></label>
                        <select name="status_hari" class="form-select" required onchange="toggleTimeInputs(this.value)">
                            <option value="">-- Pilih Status --</option>
                            <option value="Masuk Kerja">Masuk Kerja</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin/Libur Resmi">Izin/Libur Resmi</option>
                            <option value="Cuti Pribadi">Cuti Pribadi</option>
                        </select>
                    </div>
                </div>

                <div id="jamKerja" style="display:none;" class="mt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jam Masuk</label>
                            <input type="time" name="waktu_mulai" class="form-control">
                            <div class="time-hint">Gunakan format 24 jam (contoh: 07:30)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Pulang</label>
                            <input type="time" name="waktu_selesai" class="form-control">
                            <div class="time-hint">Gunakan format 24 jam (contoh: 17:00)</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Deskripsi Tugas/keterangan sakit</label>
                    <textarea name="deskripsi_tugas" rows="3" class="form-control" placeholder="Tuliskan aktivitas atau tugas hari ini..."></textarea>
                </div>

                <div class="mt-3">
                    <label class="form-label">Bukti Foto Kegiatan</label>
                    <input type="file" name="bukti_foto" class="form-control" accept="image/*">
                    <div class="form-text"><i class="fas fa-info-circle"></i> Format yang diizinkan: JPG, PNG, JPEG (Max 5MB)</div>
                </div>

                <div class="mt-3">
                    <label class="form-label">File Dokumentasi Tambahan (Opsional)</label>
                    <input type="file" name="dokumen[]" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar">
                    <div class="form-text"><i class="fas fa-info-circle"></i> Bisa upload multiple file: PDF, DOC, XLS, PPT, Gambar, ZIP (Max 10MB per file)</div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Keterangan Tambahan</label>
                    <textarea name="keterangan" rows="2" class="form-control" placeholder="Opsional..."></textarea>
                </div>

                <button type="submit" class="btn-submit mt-4">
                    <i class="fas fa-save"></i> Simpan Laporan
                </button>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="Masuk Kerja" <?= isset($_GET['status']) && $_GET['status'] == 'Masuk Kerja' ? 'selected' : '' ?>>Masuk Kerja</option>
                            <option value="Sakit" <?= isset($_GET['status']) && $_GET['status'] == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                            <option value="Izin/Libur Resmi" <?= isset($_GET['status']) && $_GET['status'] == 'Izin/Libur Resmi' ? 'selected' : '' ?>>Izin/Libur Resmi</option>
                            <option value="Cuti Pribadi" <?= isset($_GET['status']) && $_GET['status'] == 'Cuti Pribadi' ? 'selected' : '' ?>>Cuti Pribadi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="dari" class="form-control" id="filterDari" value="<?= isset($_GET['dari']) ? $_GET['dari'] : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="sampai" class="form-control" id="filterSampai" value="<?= isset($_GET['sampai']) ? $_GET['sampai'] : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Terapkan Filter
                        </button>
                        <button type="button" class="btn-print" onclick="cetakLaporan()">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <h2>
                <i class="fas fa-list-alt"></i>
                Daftar Laporan Harian
            </h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Tanggal</th>
                            <th style="width: 150px;">Status</th>
                            <th>Deskripsi</th>
                            <th style="width: 240px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM internship_log WHERE 1=1";
                        
                        if(isset($_GET['status']) && !empty($_GET['status'])) {
                            $status = mysqli_real_escape_string($conn, $_GET['status']);
                            $query .= " AND status_hari = '$status'";
                        }
                        
                        if(isset($_GET['dari']) && !empty($_GET['dari'])) {
                            $dari = mysqli_real_escape_string($conn, $_GET['dari']);
                            $query .= " AND tanggal >= '$dari'";
                        }
                        
                        if(isset($_GET['sampai']) && !empty($_GET['sampai'])) {
                            $sampai = mysqli_real_escape_string($conn, $_GET['sampai']);
                            $query .= " AND tanggal <= '$sampai'";
                        }
                        
                        $query .= " ORDER BY tanggal DESC";
                        
                        $result = mysqli_query($conn, $query);
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $desc = htmlspecialchars(substr($row['deskripsi_tugas'], 0, 70));
                                $badge_class = 'badge-masuk';
                                if($row['status_hari'] == 'Sakit') $badge_class = 'badge-sakit';
                                if(in_array($row['status_hari'], ['Izin/Libur Resmi', 'Cuti Pribadi'])) $badge_class = 'badge-izin';
                                
                                // Cek jumlah file
                                $file_count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM dokumentasi WHERE log_id = {$row['id']}");
                                $file_count = mysqli_fetch_assoc($file_count_query)['total'];
                                $file_badge = $file_count > 0 ? "<span style='background: #dbeafe; color: #1e40af; padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 8px;'><i class='fas fa-paperclip'></i> {$file_count}</span>" : "";
                                
                                echo "<tr>
                                    <td><i class='far fa-calendar-alt'></i> " . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                                    <td><span class='badge-status {$badge_class}'>{$row['status_hari']}</span></td>
                                    <td style='text-align: left;'>{$desc}...{$file_badge}</td>
                                    <td>
                                        <div class='action-buttons'>
                                            <a class='btn-action btn-view' href='view_log.php?id={$row['id']}'>
                                                <i class='fas fa-eye'></i> Lihat
                                            </a>
                                            <a class='btn-action btn-download' href='download_detail.php?id={$row['id']}' target='_blank'>
                                                <i class='fas fa-download'></i> Download
                                            </a>
                                            <a class='btn-action btn-edit' href='edit_log.php?id={$row['id']}'>
                                                <i class='fas fa-edit'></i> Edit
                                            </a>
                                            <button class='btn-action btn-delete' onclick='confirmDelete({$row['id']})'>
                                                <i class='fas fa-trash-alt'></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>
                                <div class='empty-state'>
                                    <i class='fas fa-inbox'></i>
                                    <h5>Belum ada data laporan</h5>
                                    <p>Mulai tambahkan laporan harian magang Anda</p>
                                </div>
                            </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTopBtn">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        function confirmDelete(id) {
            if (confirm("Yakin ingin menghapus data ini?")) {
                window.location = "delete_log.php?id=" + id;
            }
        }

        function toggleTimeInputs(status) {
            const jam = document.getElementById('jamKerja');
            if (status === 'Masuk Kerja') {
                jam.style.display = 'block';
            } else {
                jam.style.display = 'none';
                document.querySelector('input[name="waktu_mulai"]').value = '';
                document.querySelector('input[name="waktu_selesai"]').value = '';
            }
        }

        // Scroll to top functionality
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });

        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Form validation
        document.getElementById('logForm').addEventListener('submit', function(e) {
            const tanggal = this.querySelector('[name="tanggal"]').value;
            const status = this.querySelector('[name="status_hari"]').value;
            
            if (!tanggal || !status) {
                e.preventDefault();
                alert('Tanggal dan Status Hari wajib diisi!');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);

        // Fungsi cetak laporan
        function cetakLaporan() {
            const status = document.getElementById('filterStatus').value;
            const dari = document.getElementById('filterDari').value;
            const sampai = document.getElementById('filterSampai').value;
            
            let url = 'cetak_laporan.php?';
            if (status) url += 'status=' + encodeURIComponent(status) + '&';
            if (dari) url += 'dari=' + dari + '&';
            if (sampai) url += 'sampai=' + sampai;
            
            window.open(url, '_blank');
        }

        // Animate progress bar on load
        window.addEventListener('load', function() {
            const progressBar = document.querySelector('.progress-bar-fill');
            if (progressBar) {
                setTimeout(function() {
                    progressBar.style.width = progressBar.style.width;
                }, 100);
            }
        });
    </script>
</body>
</html>