<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM internship_log WHERE id = $id");
$row = mysqli_fetch_assoc($result);

// Path foto dari database
$current_photo = $row['bukti_foto'] ? $row['bukti_foto'] : '';

// Ambil file dokumentasi yang sudah ada
$files_result = mysqli_query($conn, "SELECT * FROM dokumentasi WHERE log_id = $id ORDER BY upload_date DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Laporan - <?= htmlspecialchars($row['tanggal']) ?></title>
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

        /* Form Card */
        .form-card {
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

        .form-header {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            padding: 25px 35px;
        }

        .form-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-body {
            padding: 35px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .required {
            color: var(--danger);
            font-weight: 700;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: 'Poppins', sans-serif;
        }

        .time-hint {
            font-size: 0.85rem;
            color: #64748b;
            font-style: italic;
            margin-top: 5px;
        }

        /* Photo Section */
        .photo-section {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            border: 2px dashed #cbd5e1;
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

        .current-photo {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .current-photo img {
            width: 100%;
            max-width: 400px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .photo-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .no-photo {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .no-photo i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .file-input-wrapper {
            position: relative;
        }

        .custom-file-input {
            display: none;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--dark);
        }

        .file-input-label:hover {
            border-color: var(--primary);
            background: #f8fafc;
            color: var(--primary);
        }

        .file-input-label i {
            font-size: 1.2rem;
        }

        .file-name {
            margin-top: 10px;
            padding: 10px 15px;
            background: #dbeafe;
            border-radius: 8px;
            color: #1e40af;
            font-size: 0.9rem;
            display: none;
        }

        .file-name.show {
            display: block;
        }

        /* Preview Image */
        #photoPreview {
            max-width: 100%;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Files Section */
        .files-section {
            margin-top: 30px;
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

        .file-name-text {
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

        .btn-delete-file {
            padding: 8px 16px;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-delete-file:hover {
            background: var(--danger);
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

        /* Action Buttons */
        .action-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            border: none;
            cursor: pointer;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            flex: 1;
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
        }

        .btn-cancel:hover {
            background: linear-gradient(135deg, #475569, #334155);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
            color: white;
        }

        /* Time Inputs Section */
        #jamKerja {
            display: none;
            animation: slideDown 0.3s ease;
        }

        #jamKerja.show {
            display: block;
        }

        /* Alert Messages */
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid #3b82f6;
        }

        .alert-info i {
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            .header-card h1 {
                font-size: 1.4rem;
            }

            .form-header h2 {
                font-size: 1.2rem;
            }

            .form-body {
                padding: 25px 20px;
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

        /* Loading State */
        .btn-save.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-save.loading::after {
            content: "";
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Grid Layout for Form */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Laporan Harian
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" style="color: var(--primary); text-decoration: none;">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="view_log.php?id=<?= $row['id'] ?>" style="color: var(--primary); text-decoration: none;">Detail</a></li>
                    <li class="breadcrumb-item active">Edit Laporan</li>
                </ol>
            </nav>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <div class="form-header">
                <h2>
                    <i class="far fa-calendar-check"></i>
                    Edit Laporan Tanggal <?= date('d F Y', strtotime($row['tanggal'])) ?>
                </h2>
            </div>

            <div class="form-body">
                <div class="alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Perhatian:</strong> Pastikan semua data yang Anda ubah sudah benar sebelum menyimpan.
                    </div>
                </div>

                <form action="update_log.php" method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="old_photo" value="<?= htmlspecialchars($row['bukti_foto']) ?>">

                    <!-- Tanggal & Status -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="far fa-calendar"></i>
                                Tanggal Kegiatan <span class="required">*</span>
                            </label>
                            <input type="date" name="tanggal" class="form-control" value="<?= $row['tanggal'] ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-info-circle"></i>
                                Status Hari <span class="required">*</span>
                            </label>
                            <select name="status_hari" class="form-select" required onchange="toggleTimeInputs(this.value)">
                                <option value="Masuk Kerja" <?= $row['status_hari'] == 'Masuk Kerja' ? 'selected' : '' ?>>Masuk Kerja</option>
                                <option value="Sakit" <?= $row['status_hari'] == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                                <option value="Izin/Libur Resmi" <?= $row['status_hari'] == 'Izin/Libur Resmi' ? 'selected' : '' ?>>Izin/Libur Resmi</option>
                                <option value="Cuti Pribadi" <?= $row['status_hari'] == 'Cuti Pribadi' ? 'selected' : '' ?>>Cuti Pribadi</option>
                            </select>
                        </div>
                    </div>

                    <!-- Waktu Kerja -->
                    <div id="jamKerja" class="<?= $row['status_hari'] == 'Masuk Kerja' ? 'show' : '' ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Jam Masuk
                                </label>
                                <input type="time" name="waktu_mulai" class="form-control" value="<?= $row['waktu_mulai'] ?>">
                                <div class="time-hint">Format 24 jam (contoh: 07:30)</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Jam Pulang
                                </label>
                                <input type="time" name="waktu_selesai" class="form-control" value="<?= $row['waktu_selesai'] ?>">
                                <div class="time-hint">Format 24 jam (contoh: 17:00)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi Tugas -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tasks"></i>
                            Deskripsi Tugas
                        </label>
                        <textarea name="deskripsi_tugas" class="form-control" rows="4" placeholder="Tuliskan aktivitas atau tugas hari ini..."><?= htmlspecialchars($row['deskripsi_tugas']) ?></textarea>
                    </div>

                    <!-- Photo Section -->
                    <div class="photo-section">
                        <h3>
                            <i class="fas fa-camera"></i>
                            Bukti Foto Kegiatan
                        </h3>

                        <!-- Current Photo -->
                        <?php if (!empty($current_photo)): ?>
                            <div class="current-photo">
                                <span class="photo-badge">
                                    <i class="fas fa-image"></i> Foto Saat Ini
                                </span>
                                <img src="<?= htmlspecialchars($current_photo) ?>" alt="Bukti Foto" id="currentPhoto">
                            </div>
                        <?php else: ?>
                            <div class="no-photo">
                                <i class="far fa-image"></i>
                                <p>Belum ada foto yang diunggah</p>
                            </div>
                        <?php endif; ?>

                        <!-- Upload New Photo -->
                        <div class="file-input-wrapper">
                            <input type="file" name="bukti_foto" class="custom-file-input" id="fotoInput" accept="image/*" onchange="previewPhoto(event)">
                            <label for="fotoInput" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Ganti Foto (Opsional)</span>
                            </label>
                            <div class="file-name" id="fileName"></div>
                        </div>

                        <!-- Preview New Photo -->
                        <img id="photoPreview" style="display:none;">
                    </div>

                    <!-- Files Documentation Section -->
                    <div class="files-section">
                        <h3>
                            <i class="fas fa-paperclip"></i>
                            File Dokumentasi Tambahan
                            <a href="manage_files.php?log_id=<?= $row['id'] ?>" class="btn-manage-files">
                                <i class="fas fa-cog"></i>
                                Kelola File
                            </a>
                        </h3>

                        <?php if (mysqli_num_rows($files_result) > 0): ?>
                            <div style="margin-bottom: 15px; padding: 12px; background: #dbeafe; border-radius: 8px; color: #1e40af; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> <strong><?= mysqli_num_rows($files_result) ?></strong> file dokumentasi terlampir. Klik "Kelola File" untuk menambah atau menghapus file.
                            </div>
                            <?php mysqli_data_seek($files_result, 0); // Reset pointer ?>
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
                                        <div class="file-name-text"><?= htmlspecialchars($file['file_name']) ?></div>
                                        <div class="file-meta">
                                            <?= strtoupper($file['file_type']) ?> • <?= $file_size_kb ?> KB
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-files">
                                <i class="fas fa-inbox"></i>
                                <p>Belum ada file dokumentasi. Klik "Kelola File" untuk menambahkan.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Keterangan -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-sticky-note"></i>
                            Keterangan Tambahan
                        </label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan catatan atau keterangan tambahan..."><?= htmlspecialchars($row['keterangan']) ?></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-section">
                        <a href="view_log.php?id=<?= $row['id'] ?>" class="btn-action btn-cancel">
                            <i class="fas fa-times"></i>
                            Batal
                        </a>
                        <button type="submit" class="btn-action btn-save">
                            <i class="fas fa-save"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle waktu kerja berdasarkan status
        function toggleTimeInputs(status) {
            const jamKerja = document.getElementById('jamKerja');
            if (status === 'Masuk Kerja') {
                jamKerja.classList.add('show');
            } else {
                jamKerja.classList.remove('show');
                document.querySelector('input[name="waktu_mulai"]').value = '';
                document.querySelector('input[name="waktu_selesai"]').value = '';
            }
        }

        // Preview foto baru
        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Hide current photo
                    const currentPhoto = document.getElementById('currentPhoto');
                    if (currentPhoto) {
                        currentPhoto.parentElement.style.opacity = '0.5';
                    }
                }
                reader.readAsDataURL(file);

                // Show file name
                const fileName = document.getElementById('fileName');
                fileName.textContent = '📎 ' + file.name;
                fileName.classList.add('show');
            }
        }

        // Form validation & loading state
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const tanggal = this.querySelector('[name="tanggal"]').value;
            const status = this.querySelector('[name="status_hari"]').value;
            
            if (!tanggal || !status) {
                e.preventDefault();
                alert('Tanggal dan Status Hari wajib diisi!');
                return;
            }

            // Add loading state to button
            const submitBtn = this.querySelector('.btn-save');
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.querySelector('[name="status_hari"]');
            toggleTimeInputs(statusSelect.value);
        });

        // Confirm before leaving with unsaved changes
        let formChanged = false;
        const form = document.getElementById('editForm');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        form.addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>