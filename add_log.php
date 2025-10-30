<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// BAGIAN 1: PROSES FORM SUBMIT (SIMPAN DATA BARU)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $status_hari = $_POST['status_hari'];
    $waktu_mulai = $_POST['waktu_mulai'] ?? '';
    $waktu_selesai = $_POST['waktu_selesai'] ?? '';
    $deskripsi_tugas = mysqli_real_escape_string($conn, $_POST['deskripsi_tugas']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    // Handle upload bukti foto
    $bukti_foto = '';
    if (!empty($_FILES['bukti_foto']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file = $_FILES['bukti_foto'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_images = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_ext, $allowed_images)) {
            if ($file_size <= 5242880) { // 5MB
                $new_file_name = time() . "_" . basename($file_name);
                $target_file = $target_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $bukti_foto = $target_file;
                }
            }
        }
    }

    // Insert data laporan ke database
    $insert_query = "INSERT INTO internship_log 
              (tanggal, status_hari, waktu_mulai, waktu_selesai, deskripsi_tugas, bukti_foto, keterangan) 
              VALUES 
              ('$tanggal', '$status_hari', '$waktu_mulai', '$waktu_selesai', '$deskripsi_tugas', '$bukti_foto', '$keterangan')";

    if (mysqli_query($conn, $insert_query)) {
        $log_id = mysqli_insert_id($conn);
        
        // Upload file dokumentasi tambahan jika ada
        if (!empty($_FILES['dokumen']['name'][0])) {
            $target_dir = "uploads/dokumen/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $allowed = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip', 'rar');
            
            foreach ($_FILES['dokumen']['tmp_name'] as $key => $tmp_name) {
                if (!empty($_FILES['dokumen']['name'][$key])) {
                    $file_name = $_FILES['dokumen']['name'][$key];
                    $file_tmp = $_FILES['dokumen']['tmp_name'][$key];
                    $file_size = $_FILES['dokumen']['size'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, $allowed) && $file_size <= 10485760) {
                        $new_file_name = time() . "_" . $key . "_" . basename($file_name);
                        $target_file = $target_dir . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $target_file)) {
                            $insert_doc = "INSERT INTO dokumentasi (log_id, file_name, file_path, file_type) 
                                          VALUES ($log_id, '$file_name', '$target_file', '$file_ext')";
                            mysqli_query($conn, $insert_doc);
                        }
                    }
                }
            }
        }
        
        // Redirect dengan success message
        header("Location: index.php?success=1");
        exit();
    } else {
        header("Location: index.php?error=1");
        exit();
    }
}

// BAGIAN 2: TAMPILAN CETAK LAPORAN (KETIKA DIAKSES VIA GET)
// Ambil data user
$user_query = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
$user = mysqli_fetch_assoc($user_query);

// Filter berdasarkan parameter GET
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

$query .= " ORDER BY tanggal ASC";
$result = mysqli_query($conn, $query);

// Hitung statistik
$total_data = mysqli_num_rows($result);
$total_masuk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM internship_log WHERE status_hari='Masuk Kerja'" . (isset($dari) ? " AND tanggal >= '$dari'" : "") . (isset($sampai) ? " AND tanggal <= '$sampai'" : "")));
$total_sakit = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM internship_log WHERE status_hari='Sakit'" . (isset($dari) ? " AND tanggal >= '$dari'" : "") . (isset($sampai) ? " AND tanggal <= '$sampai'" : "")));
$total_izin = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM internship_log WHERE status_hari IN ('Izin/Libur Resmi', 'Cuti Pribadi')" . (isset($dari) ? " AND tanggal >= '$dari'" : "") . (isset($sampai) ? " AND tanggal <= '$sampai'" : "")));

// Format periode
$periode = "Semua Periode";
if(isset($dari) && isset($sampai)) {
    $periode = date('d/m/Y', strtotime($dari)) . " - " . date('d/m/Y', strtotime($sampai));
} elseif(isset($dari)) {
    $periode = "Dari " . date('d/m/Y', strtotime($dari));
} elseif(isset($sampai)) {
    $periode = "Sampai " . date('d/m/Y', strtotime($sampai));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Magang - <?= htmlspecialchars($user['nama_lengkap']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Action Bar */
        .action-bar {
            max-width: 1000px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-action {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-download-pdf {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-download-pdf:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-print {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-print:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-back {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #475569, #334155);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
            color: white;
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #f3f4f6;
            border-top: 6px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-content h3 {
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .loading-content p {
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Preview Container */
        .preview-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .preview-content {
            padding: 40px;
        }

        /* Header */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1e293b;
        }

        .report-header h1 {
            font-size: 22px;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #1e293b;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .report-header h2 {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
            color: #475569;
        }

        .report-header p {
            font-size: 13px;
            color: #64748b;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }

        .info-item {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 10px;
            font-size: 14px;
            padding: 8px 0;
        }

        .info-label {
            font-weight: 700;
            color: #1e293b;
        }

        .info-value {
            color: #475569;
        }

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #cbd5e1;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 8px;
            color: #4f46e5;
            font-weight: 700;
        }

        .stat-card p {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table thead th {
            background: #1e293b;
            color: white;
            padding: 14px 10px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            border: 1px solid #0f172a;
            letter-spacing: 0.5px;
        }

        table tbody td {
            padding: 12px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            color: #1e293b;
        }

        table tbody td:first-child {
            text-align: center;
            font-weight: 700;
            color: #4f46e5;
        }

        table tbody td:nth-child(2) {
            text-align: center;
            font-size: 11px;
        }

        table tbody td:nth-child(3) {
            text-align: center;
        }

        table tbody td:nth-child(4) {
            text-align: center;
            font-size: 11px;
        }

        table tbody td:nth-child(5) {
            text-align: left;
            line-height: 1.6;
        }

        table tbody td:nth-child(6) {
            text-align: left;
            font-size: 11px;
            line-height: 1.5;
        }

        table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        table tbody tr:hover {
            background: #f1f5f9;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-masuk {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .badge-sakit {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .badge-izin {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Summary Section */
        .summary-section {
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .summary-title {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 20px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-top: none;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 2px solid;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .summary-card.total-card {
            border-color: #818cf8;
        }

        .summary-card.total-card::before {
            background: linear-gradient(180deg, #4f46e5, #818cf8);
        }

        .summary-card.masuk-card {
            border-color: #6ee7b7;
        }

        .summary-card.masuk-card::before {
            background: linear-gradient(180deg, #10b981, #6ee7b7);
        }

        .summary-card.sakit-card {
            border-color: #fcd34d;
        }

        .summary-card.sakit-card::before {
            background: linear-gradient(180deg, #f59e0b, #fcd34d);
        }

        .summary-card.izin-card {
            border-color: #fca5a5;
        }

        .summary-card.izin-card::before {
            background: linear-gradient(180deg, #ef4444, #fca5a5);
        }

        .summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            flex-shrink: 0;
        }

        .total-card .summary-icon {
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            color: white;
        }

        .masuk-card .summary-icon {
            background: linear-gradient(135deg, #10b981, #6ee7b7);
            color: white;
        }

        .sakit-card .summary-icon {
            background: linear-gradient(135deg, #f59e0b, #fcd34d);
            color: white;
        }

        .izin-card .summary-icon {
            background: linear-gradient(135deg, #ef4444, #fca5a5);
            color: white;
        }

        .summary-content {
            flex: 1;
        }

        .summary-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .summary-percentage {
            font-size: 20px;
            font-weight: 700;
            color: #4f46e5;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 8px;
        }

        .masuk-card .summary-percentage {
            color: #10b981;
        }

        .sakit-card .summary-percentage {
            color: #f59e0b;
        }

        .izin-card .summary-percentage {
            color: #ef4444;
        }

        /* Progress Bars */
        .progress-bars {
            padding: 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
        }

        .progress-item {
            margin-bottom: 20px;
        }

        .progress-item:last-child {
            margin-bottom: 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-percent {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }

        .progress-bar-wrapper {
            height: 12px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .masuk-bg {
            background: linear-gradient(90deg, #10b981, #6ee7b7);
        }

        .sakit-bg {
            background: linear-gradient(90deg, #f59e0b, #fcd34d);
        }

        .izin-bg {
            background: linear-gradient(90deg, #ef4444, #fca5a5);
        }

        /* Footer Signatures */
        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 80px;
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
        }

        .signature-box p {
            font-size: 13px;
            margin-bottom: 70px;
            color: #475569;
            font-weight: 500;
        }

        .signature-name {
            font-weight: 700;
            border-top: 2px solid #1e293b;
            display: inline-block;
            padding-top: 8px;
            min-width: 200px;
            color: #1e293b;
            font-size: 14px;
        }

        /* Footer Info */
        .footer-info {
            margin-top: 40px;
            padding: 20px;
            background: #f8fafc;
            border-top: 3px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
            text-align: center;
        }

        .footer-info p {
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
            margin: 3px 0;
        }

        .footer-info strong {
            color: #1e293b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Print styles */
        @media print {
            body {
                padding: 0;
                background: white;
            }

            .action-bar, .loading-overlay {
                display: none !important;
            }

            .preview-container {
                max-width: 100%;
                box-shadow: none;
                border-radius: 0;
            }

            .preview-content {
                padding: 20px;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            .signatures {
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid,
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .signatures {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .action-bar {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .preview-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Sedang Membuat PDF...</h3>
            <p>Mohon tunggu sebentar</p>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar">
        <button class="btn-action btn-download-pdf" onclick="generatePDF()">
            <i class="fas fa-file-pdf"></i> Download PDF
        </button>
        <button class="btn-action btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Langsung
        </button>
        <a href="index.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Preview Container -->
    <div class="preview-container" id="reportContent">
        <div class="preview-content">
            <!-- Header -->
            <div class="report-header">
                <h1>POLITEKNIK NEGERI PADANG</h1>
                <h2>Laporan Harian Kegiatan Magang</h2>
                <p>Jl. Kampus Limau Manis, Padang, Sumatera Barat • Telp: (0751) 72590</p>
            </div>

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama Peserta:</div>
                    <div class="info-value"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">No. HP:</div>
                    <div class="info-value"><?= htmlspecialchars($user['no_hp']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Durasi Magang:</div>
                    <div class="info-value"><?= $user['durasi_magang'] ?> Hari</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Periode Laporan:</div>
                    <div class="info-value"><?= $periode ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tanggal Cetak:</div>
                    <div class="info-value"><?= date('d F Y, H:i') ?> WIB</div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-wrapper">
                <table id="reportTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th style="width: 90px;">Tanggal</th>
                            <th style="width: 120px;">Status Hari</th>
                            <th style="width: 80px;">Waktu</th>
                            <th style="width: 300px;">Deskripsi Kegiatan</th>
                            <th style="width: 150px;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $badge_class = 'badge-masuk';
                                if($row['status_hari'] == 'Sakit') $badge_class = 'badge-sakit';
                                if(in_array($row['status_hari'], ['Izin/Libur Resmi', 'Cuti Pribadi'])) $badge_class = 'badge-izin';
                                
                                $waktu = "-";
                                if(!empty($row['waktu_mulai']) && !empty($row['waktu_selesai'])) {
                                    $waktu = date('H:i', strtotime($row['waktu_mulai'])) . " - " . date('H:i', strtotime($row['waktu_selesai']));
                                }
                                
                                $deskripsi = htmlspecialchars($row['deskripsi_tugas']);
                                $keterangan = !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : '-';
                                
                                echo "<tr>
                                    <td>{$no}</td>
                                    <td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                                    <td><span class='status-badge {$badge_class}'>{$row['status_hari']}</span></td>
                                    <td>{$waktu}</td>
                                    <td>{$deskripsi}</td>
                                    <td>{$keterangan}</td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='6'>
                                <div class='empty-state'>
                                    <i class='fas fa-inbox'></i>
                                    <p>Tidak ada data laporan untuk periode yang dipilih</p>
                                </div>
                            </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Statistics -->
            <div class="summary-section">
                <h3 class="summary-title">
                    <i class="fas fa-chart-bar"></i>
                    RINGKASAN STATISTIK KEHADIRAN
                </h3>
                <div class="summary-grid">
                    <div class="summary-card total-card">
                        <div class="summary-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value"><?= $total_data ?></div>
                            <div class="summary-label">Total Laporan</div>
                        </div>
                        <div class="summary-percentage">100%</div>
                    </div>
                    
                    <div class="summary-card masuk-card">
                        <div class="summary-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value"><?= $total_masuk ?></div>
                            <div class="summary-label">Masuk Kerja</div>
                        </div>
                        <div class="summary-percentage">
                            <?= $total_data > 0 ? number_format(($total_masuk / $total_data) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                    
                    <div class="summary-card sakit-card">
                        <div class="summary-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value"><?= $total_sakit ?></div>
                            <div class="summary-label">Sakit</div>
                        </div>
                        <div class="summary-percentage">
                            <?= $total_data > 0 ? number_format(($total_sakit / $total_data) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                    
                    <div class="summary-card izin-card">
                        <div class="summary-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value"><?= $total_izin ?></div>
                            <div class="summary-label">Izin / Cuti</div>
                        </div>
                        <div class="summary-percentage">
                            <?= $total_data > 0 ? number_format(($total_izin / $total_data) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bars -->
                <div class="progress-bars">
                    <div class="progress-item">
                        <div class="progress-header">
                            <span class="progress-label">Masuk Kerja</span>
                            <span class="progress-percent"><?= $total_data > 0 ? number_format(($total_masuk / $total_data) * 100, 1) : 0 ?>%</span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill masuk-bg" style="width: <?= $total_data > 0 ? ($total_masuk / $total_data) * 100 : 0 ?>%;"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-header">
                            <span class="progress-label">Sakit</span>
                            <span class="progress-percent"><?= $total_data > 0 ? number_format(($total_sakit / $total_data) * 100, 1) : 0 ?>%</span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill sakit-bg" style="width: <?= $total_data > 0 ? ($total_sakit / $total_data) * 100 : 0 ?>%;"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-header">
                            <span class="progress-label">Izin / Cuti</span>
                            <span class="progress-percent"><?= $total_data > 0 ? number_format(($total_izin / $total_data) * 100, 1) : 0 ?>%</span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill izin-bg" style="width: <?= $total_data > 0 ? ($total_izin / $total_data) * 100 : 0 ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-box">
                    <p>Mengetahui,<br>Pembimbing Lapangan</p>
                    <div class="signature-name">( ___________________ )</div>
                </div>
                <div class="signature-box">
                    <p>Yang Melaporkan,<br>Peserta Magang</p>
                    <div class="signature-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="footer-info">
                <p><strong>Dokumen ini dicetak pada:</strong> <?= date('d F Y, H:i') ?> WIB</p>
                <p>Dokumen ini merupakan bukti sah kegiatan magang dan dapat digunakan sebagai arsip resmi</p>
                <p><strong>Politeknik Negeri Padang</strong> - Jl. Kampus Limau Manis, Padang, Sumatera Barat</p>
            </div>
        </div>
    </div>

    <script>
        function generatePDF() {
            document.getElementById('loadingOverlay').classList.add('show');

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            const userData = {
                nama: "<?= htmlspecialchars($user['nama_lengkap']) ?>",
                email: "<?= htmlspecialchars($user['email']) ?>",
                noHp: "<?= htmlspecialchars($user['no_hp']) ?>",
                durasi: "<?= $user['durasi_magang'] ?>",
                periode: "<?= $periode ?>",
                tanggalCetak: "<?= date('d F Y, H:i') ?>"
            };

            const stats = {
                total: <?= $total_data ?>,
                masuk: <?= $total_masuk ?>,
                sakit: <?= $total_sakit ?>,
                izin: <?= $total_izin ?>
            };

            // Header
            doc.setFillColor(30, 41, 59);
            doc.rect(0, 0, 210, 40, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.text('POLITEKNIK NEGERI PADANG', 105, 14, { align: 'center' });
            
            doc.setFontSize(14);
            doc.text('Laporan Harian Kegiatan Magang', 105, 22, { align: 'center' });
            
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text('Jl. Kampus Limau Manis, Padang, Sumatera Barat', 105, 29, { align: 'center' });
            doc.text('Telp: (0751) 72590', 105, 35, { align: 'center' });

            // Info Section
            let y = 50;
            doc.setTextColor(0, 0, 0);
            doc.setFillColor(248, 250, 252);
            doc.roundedRect(10, y, 190, 45, 2, 2, 'F');
            doc.setDrawColor(226, 232, 240);
            doc.setLineWidth(0.5);
            doc.roundedRect(10, y, 190, 45, 2, 2, 'S');
            
            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            
            const infoLeft = 15;
            let infoY = y + 8;
            
            doc.setFont(undefined, 'normal');
            doc.text(userData.nama, infoLeft + 40, infoY);
            
            doc.text('Nama Peserta:', infoLeft, infoY);
            infoY += 7;
            doc.setFont(undefined, 'bold');
            doc.text('Email:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.email, infoLeft + 40, infoY);
            
            infoY += 7;
            doc.setFont(undefined, 'bold');
            doc.text('No. HP:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.noHp, infoLeft + 40, infoY);
            
            infoY += 7;
            doc.setFont(undefined, 'bold');
            doc.text('Durasi Magang:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.durasi + ' Hari', infoLeft + 40, infoY);
            
            infoY += 7;
            doc.setFont(undefined, 'bold');
            doc.text('Periode Laporan:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.periode, infoLeft + 40, infoY);
            
            infoY += 7;
            doc.setFont(undefined, 'bold');
            doc.text('Tanggal Cetak:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.tanggalCetak + ' WIB', infoLeft + 40, infoY);

            // Table
            y = 105;
            
            const tableData = [];
            <?php
            mysqli_data_seek($result, 0);
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $waktu = "-";
                if(!empty($row['waktu_mulai']) && !empty($row['waktu_selesai'])) {
                    $waktu = date('H:i', strtotime($row['waktu_mulai'])) . " - " . date('H:i', strtotime($row['waktu_selesai']));
                }
                
                $deskripsi = str_replace(["\r\n", "\n", "\r"], ' ', htmlspecialchars($row['deskripsi_tugas']));
                $keterangan = !empty($row['keterangan']) ? str_replace(["\r\n", "\n", "\r"], ' ', htmlspecialchars($row['keterangan'])) : '-';
                
                echo "tableData.push([
                    '{$no}',
                    '" . date('d/m/Y', strtotime($row['tanggal'])) . "',
                    '{$row['status_hari']}',
                    '{$waktu}',
                    '{$deskripsi}',
                    '{$keterangan}'
                ]);\n";
                $no++;
            }
            ?>

            doc.autoTable({
                startY: y,
                head: [['No', 'Tanggal', 'Status', 'Waktu', 'Deskripsi Kegiatan']],
                body: tableData.map(row => [row[0], row[1], row[2], row[3], row[4]]),
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 3,
                    lineColor: [203, 213, 225],
                    lineWidth: 0.5,
                    textColor: [30, 41, 59],
                    font: 'helvetica'
                },
                headStyles: {
                    fillColor: [30, 41, 59],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center',
                    fontSize: 9
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 12, fontStyle: 'bold', textColor: [79, 70, 229] },
                    1: { halign: 'center', cellWidth: 22, fontSize: 7 },
                    2: { halign: 'center', cellWidth: 28 },
                    3: { halign: 'center', cellWidth: 25, fontSize: 7 },
                    4: { halign: 'left', cellWidth: 103 }
                },
                alternateRowStyles: {
                    fillColor: [248, 250, 252]
                },
                margin: { left: 10, right: 10 },
                didParseCell: function(data) {
                    if (data.column.index === 2 && data.section === 'body') {
                        const status = data.cell.raw;
                        if (status === 'Masuk Kerja') {
                            data.cell.styles.fillColor = [209, 250, 229];
                            data.cell.styles.textColor = [6, 95, 70];
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fontSize = 7;
                        } else if (status === 'Sakit') {
                            data.cell.styles.fillColor = [254, 243, 199];
                            data.cell.styles.textColor = [146, 64, 14];
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fontSize = 7;
                        } else {
                            data.cell.styles.fillColor = [254, 226, 226];
                            data.cell.styles.textColor = [153, 27, 27];
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fontSize = 7;
                        }
                    }
                }
            });

            // Signature & Footer
            let summaryY = doc.lastAutoTable.finalY + 10;
            const pageHeight = doc.internal.pageSize.getHeight();
            
            if (summaryY > pageHeight - 100) {
                doc.addPage();
                summaryY = 20;
            }

            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text('Mengetahui,', 45, summaryY);
            doc.text('Pembimbing Lapangan', 45, summaryY + 5);
            
            doc.text('Yang Melaporkan,', 140, summaryY);
            doc.text('Peserta Magang', 140, summaryY + 5);
            
            doc.setLineWidth(0.5);
            doc.line(25, summaryY + 25, 65, summaryY + 25);
            doc.line(120, summaryY + 25, 160, summaryY + 25);
            
            doc.setFont(undefined, 'bold');
            doc.text('( _______________ )', 45, summaryY + 30, { align: 'center' });
            doc.text(userData.nama, 140, summaryY + 30, { align: 'center' });
            
            doc.setFillColor(248, 250, 252);
            doc.rect(0, pageHeight - 20, 210, 20, 'F');
            
            doc.setFontSize(7);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text('Dokumen ini dicetak pada: ' + userData.tanggalCetak + ' WIB', 105, pageHeight - 13, { align: 'center' });
            doc.text('Dokumen ini merupakan bukti sah kegiatan magang', 105, pageHeight - 9, { align: 'center' });
            doc.setFont(undefined, 'bold');
            doc.setTextColor(30, 41, 59);
            doc.text('POLITEKNIK NEGERI PADANG', 105, pageHeight - 5, { align: 'center' });

            const fileName = 'Laporan_Magang_' + userData.nama.replace(/\s+/g, '_') + '_<?= date('Y-m-d') ?>.pdf';
            doc.save(fileName);

            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.remove('show');
            }, 500);
        }

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                generatePDF();
            }
        });
    </script>
</body>
</html