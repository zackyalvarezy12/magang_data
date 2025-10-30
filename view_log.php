<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = intval($_GET['id']);
$result = mysqli_query($conn, "SELECT * FROM internship_log WHERE id = $id");
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tidak ditemukan.");
}

$fotoPath = '';
if (!empty($data['bukti_foto'])) {
    if (file_exists('uploads/' . $data['bukti_foto'])) {
        $fotoPath = 'uploads/' . $data['bukti_foto'];
    } elseif (file_exists($data['bukti_foto'])) {
        $fotoPath = $data['bukti_foto'];
    }
}

// Ambil file dokumentasi
$files_result = mysqli_query($conn, "SELECT * FROM dokumentasi WHERE log_id = $id ORDER BY upload_date DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan Harian - <?= htmlspecialchars($data['tanggal']) ?></title>
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
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Header Card */
        .header-card {
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

        .header-card h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-card .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }

        .header-card .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #94a3b8;
        }

        /* Detail Card */
        .detail-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
            margin-bottom: 30px;
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

        .detail-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px 35px;
        }

        .detail-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .detail-body {
            padding: 35px;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 30px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 15px;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .info-value {
            color: var(--dark);
            font-weight: 500;
            font-size: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-masuk {
            background: #d1fae5;
            color: #065f46;
        }

        .status-sakit {
            background: #fef3c7;
            color: #92400e;
        }

        .status-izin {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Description Box */
        .desc-section {
            margin-top: 30px;
        }

        .desc-section h3 {
            color: var(--dark);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .desc-box {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 12px;
            white-space: pre-line;
            color: var(--dark);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        /* Photo Section */
        .photo-section {
            margin-top: 35px;
        }

        .photo-section h3 {
            color: var(--dark);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .photo-container {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            background: #f1f5f9;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 0 auto;
        }

        .photo-container img {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .photo-container img:hover {
            transform: scale(1.02);
        }

        .no-photo {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .no-photo i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Files Section */
        .files-section {
            margin-top: 35px;
            padding: 25px;
            background: #f8fafc;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
        }

        .files-section h3 {
            color: var(--dark);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .file-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .file-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .icon-pdf { background: #fee2e2; color: #dc2626; }
        .icon-doc { background: #dbeafe; color: #2563eb; }
        .icon-xls { background: #d1fae5; color: #059669; }
        .icon-ppt { background: #fed7aa; color: #ea580c; }
        .icon-img { background: #e0e7ff; color: #6366f1; }
        .icon-zip { background: #fef3c7; color: #d97706; }
        .icon-other { background: #e5e7eb; color: #6b7280; }

        .file-info {
            flex: 1;
            min-width: 0;
        }

        .file-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
            word-break: break-word;
            font-size: 0.9rem;
        }

        .file-meta {
            font-size: 0.8rem;
            color: #64748b;
        }

        .btn-download-file {
            padding: 8px 16px;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-download-file:hover {
            background: var(--success);
            color: white;
        }

        .btn-manage-files {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-manage-files:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
        }

        /* Action Buttons */
        .action-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-action {
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            border: none;
        }

        .btn-back {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #475569, #334155);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
            color: white;
        }

        /* Modal for Image */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-content img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 10px;
        }

        .close-modal {
            position: absolute;
            top: -40px;
            right: 0;
            background: white;
            border: none;
            color: var(--dark);
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-card h1 {
                font-size: 1.4rem;
            }

            .detail-header h2 {
                font-size: 1.2rem;
            }

            .detail-body {
                padding: 25px 20px;
            }

            .info-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .action-section {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .file-item {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Time Badge */
        .time-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #dbeafe;
            color: #1e40af;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Empty keterangan */
        .empty-note {
            color: #94a3b8;
            font-style: italic;
        }

        .empty-files {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-files i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <h1>
                <i class="fas fa-file-alt"></i>
                Detail Laporan Harian
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" style="color: var(--primary); text-decoration: none;">Beranda</a></li>
                    <li class="breadcrumb-item active">Detail Laporan</li>
                </ol>
            </nav>
        </div>

        <!-- Detail Card -->
        <div class="detail-card">
            <div class="detail-header">
                <h2>
                    <i class="far fa-calendar-check"></i>
                    Laporan Tanggal <?= date('d F Y', strtotime($data['tanggal'])) ?>
                </h2>
            </div>

            <div class="detail-body">
                <!-- Basic Info -->
                <div class="info-section">
                    <div class="info-row">
                        <div class="info-label">
                            <i class="far fa-calendar"></i>
                            Tanggal
                        </div>
                        <div class="info-value">
                            <?= date('l, d F Y', strtotime($data['tanggal'])) ?>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-info-circle"></i>
                            Status Hari
                        </div>
                        <div class="info-value">
                            <?php
                            $statusClass = 'status-masuk';
                            $statusIcon = 'fa-check-circle';
                            
                            if($data['status_hari'] == 'Sakit') {
                                $statusClass = 'status-sakit';
                                $statusIcon = 'fa-heartbeat';
                            } elseif(in_array($data['status_hari'], ['Izin/Libur Resmi', 'Cuti Pribadi'])) {
                                $statusClass = 'status-izin';
                                $statusIcon = 'fa-calendar-times';
                            }
                            ?>
                            <span class="status-badge <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i>
                                <?= htmlspecialchars($data['status_hari']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($data['waktu_mulai']) && !empty($data['waktu_selesai'])): ?>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="far fa-clock"></i>
                            Waktu Kerja
                        </div>
                        <div class="info-value">
                            <span class="time-badge">
                                <i class="fas fa-sign-in-alt"></i>
                                <?= date('H:i', strtotime($data['waktu_mulai'])) ?>
                            </span>
                            <span style="margin: 0 10px; color: #94a3b8;">—</span>
                            <span class="time-badge">
                                <i class="fas fa-sign-out-alt"></i>
                                <?= date('H:i', strtotime($data['waktu_selesai'])) ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="desc-section">
                    <h3>
                        <i class="fas fa-tasks"></i>
                        Deskripsi Tugas
                    </h3>
                    <div class="desc-box">
                        <?= nl2br(htmlspecialchars($data['deskripsi_tugas'])) ?>
                    </div>
                </div>

                <!-- Additional Notes -->
                <div class="desc-section">
                    <h3>
                        <i class="fas fa-sticky-note"></i>
                        Keterangan Tambahan
                    </h3>
                    <div class="desc-box">
                        <?php if(!empty($data['keterangan'])): ?>
                            <?= nl2br(htmlspecialchars($data['keterangan'])) ?>
                        <?php else: ?>
                            <span class="empty-note">Tidak ada keterangan tambahan</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Photo -->
                <div class="photo-section">
                    <h3>
                        <i class="fas fa-camera"></i>
                        Bukti Foto Kegiatan
                    </h3>
                    <?php if (!empty($fotoPath)): ?>
                        <div class="photo-container" onclick="openModal()">
                            <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Bukti Foto Kegiatan" id="mainPhoto">
                        </div>
                    <?php else: ?>
                        <div class="photo-container">
                            <div class="no-photo">
                                <i class="far fa-image"></i>
                                <p>Tidak ada foto kegiatan</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Files Documentation -->
                <div class="files-section">
                    <h3>
                        <i class="fas fa-paperclip"></i>
                        File Dokumentasi Tambahan
                        <a href="manage_files.php?log_id=<?= $data['id'] ?>" class="btn-manage-files">
                            <i class="fas fa-cog"></i>
                            Kelola File
                        </a>
                    </h3>

                    <?php if (mysqli_num_rows($files_result) > 0): ?>
                        <?php while ($file = mysqli_fetch_assoc($files_result)): ?>
                            <?php
                            $icon_class = 'icon-other';
                            $icon = 'fa-file';
                            
                            switch($file['file_type']) {
                                case 'pdf':
                                    $icon_class = 'icon-pdf';
                                    $icon = 'fa-file-pdf';
                                    break;
                                case 'doc':
                                case 'docx':
                                    $icon_class = 'icon-doc';
                                    $icon = 'fa-file-word';
                                    break;
                                case 'xls':
                                case 'xlsx':
                                    $icon_class = 'icon-xls';
                                    $icon = 'fa-file-excel';
                                    break;
                                case 'ppt':
                                case 'pptx':
                                    $icon_class = 'icon-ppt';
                                    $icon = 'fa-file-powerpoint';
                                    break;
                                case 'jpg':
                                case 'jpeg':
                                case 'png':
                                    $icon_class = 'icon-img';
                                    $icon = 'fa-file-image';
                                    break;
                                case 'zip':
                                case 'rar':
                                    $icon_class = 'icon-zip';
                                    $icon = 'fa-file-zipper';
                                    break;
                            }
                            
                            $file_size = file_exists($file['file_path']) ? filesize($file['file_path']) : 0;
                            $file_size_kb = round($file_size / 1024, 2);
                            ?>
                            <div class="file-item">
                                <div class="file-icon <?= $icon_class ?>">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div class="file-info">
                                    <div class="file-name"><?= htmlspecialchars($file['file_name']) ?></div>
                                    <div class="file-meta">
                                        <?= strtoupper($file['file_type']) ?> • <?= $file_size_kb ?> KB • 
                                        <?= date('d/m/Y H:i', strtotime($file['upload_date'])) ?>
                                    </div>
                                </div>
                                <a href="<?= $file['file_path'] ?>" class="btn-download-file" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-files">
                            <i class="fas fa-inbox"></i>
                            <p>Tidak ada file dokumentasi tambahan</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="action-section">
                    <a href="index.php" class="btn-action btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Kembali
                    </a>
                    <a href="edit_log.php?id=<?= $data['id'] ?>" class="btn-action btn-edit">
                        <i class="fas fa-edit"></i>
                        Edit Laporan
                    </a>
                    <button onclick="confirmDelete(<?= $data['id'] ?>)" class="btn-action btn-delete">
                        <i class="fas fa-trash-alt"></i>
                        Hapus Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Bukti Foto">
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus laporan ini? Data yang dihapus tidak dapat dikembalikan.')) {
                window.location = 'delete_log.php?id=' + id;
            }
        }

        function openModal() {
            document.getElementById('imageModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>