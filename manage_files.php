<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Sisa kode manage_files.php yang sudah ada...

include 'db_connect.php';

if (!isset($_GET['log_id'])) {
    die("ID log tidak ditemukan.");
}

$log_id = intval($_GET['log_id']);

// Ambil data log
$log_result = mysqli_query($conn, "SELECT * FROM internship_log WHERE id = $log_id");
$log_data = mysqli_fetch_assoc($log_result);

if (!$log_data) {
    die("Data log tidak ditemukan.");
}

// Ambil file-file yang sudah diupload
$files_result = mysqli_query($conn, "SELECT * FROM dokumentasi WHERE log_id = $log_id ORDER BY upload_date DESC");

// Handle upload file
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['dokumen'])) {
    $target_dir = "uploads/dokumen/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file = $_FILES['dokumen'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip', 'rar');
    
    if (in_array($file_ext, $allowed)) {
        if ($file_size <= 10485760) { // 10MB
            $new_file_name = time() . "_" . basename($file_name);
            $target_file = $target_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $target_file)) {
                $query = "INSERT INTO dokumentasi (log_id, file_name, file_path, file_type) 
                          VALUES ($log_id, '$file_name', '$target_file', '$file_ext')";
                
                if (mysqli_query($conn, $query)) {
                    header("Location: manage_files.php?log_id=$log_id&success=1");
                    exit();
                } else {
                    $error = "Gagal menyimpan data ke database.";
                }
            } else {
                $error = "Gagal mengupload file.";
            }
        } else {
            $error = "Ukuran file terlalu besar (maksimal 10MB).";
        }
    } else {
        $error = "Format file tidak diizinkan.";
    }
}

// Handle delete file
if (isset($_GET['delete'])) {
    $file_id = intval($_GET['delete']);
    $file_query = mysqli_query($conn, "SELECT * FROM dokumentasi WHERE id = $file_id AND log_id = $log_id");
    $file_data = mysqli_fetch_assoc($file_query);
    
    if ($file_data) {
        if (file_exists($file_data['file_path'])) {
            unlink($file_data['file_path']);
        }
        mysqli_query($conn, "DELETE FROM dokumentasi WHERE id = $file_id");
        header("Location: manage_files.php?log_id=$log_id&deleted=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola File Dokumentasi - <?= date('d/m/Y', strtotime($log_data['tanggal'])) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
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

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
            display: flex;
            gap: 8px;
            color: #64748b;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Upload Card */
        .upload-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .upload-card h2 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-area {
            border: 3px dashed #cbd5e1;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: var(--primary);
            background: #f0f4ff;
        }

        .upload-area.dragover {
            border-color: var(--success);
            background: #d1fae5;
        }

        .upload-icon {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .upload-area h3 {
            color: var(--dark);
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .upload-area p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .file-input {
            display: none;
        }

        .btn-choose {
            background: var(--primary);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-choose:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .file-info {
            margin-top: 15px;
            padding: 12px;
            background: #dbeafe;
            border-radius: 8px;
            color: #1e40af;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        /* Files List */
        .files-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .files-card h2 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .file-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .icon-pdf { background: #fee2e2; color: #dc2626; }
        .icon-doc { background: #dbeafe; color: #2563eb; }
        .icon-xls { background: #d1fae5; color: #059669; }
        .icon-ppt { background: #fed7aa; color: #ea580c; }
        .icon-img { background: #e0e7ff; color: #6366f1; }
        .icon-zip { background: #fef3c7; color: #d97706; }
        .icon-other { background: #e5e7eb; color: #6b7280; }

        .file-info-content {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            word-break: break-word;
        }

        .file-meta {
            font-size: 0.85rem;
            color: #64748b;
        }

        .file-actions {
            display: flex;
            gap: 10px;
        }

        .btn-download, .btn-delete {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-download {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-download:hover {
            background: var(--success);
            color: white;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

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

        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #64748b;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: #475569;
            transform: translateY(-2px);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .file-item {
                flex-direction: column;
                text-align: center;
            }

            .file-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn-download, .btn-delete {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="view_log.php?id=<?= $log_id ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Laporan
        </a>

        <!-- Header -->
        <div class="header-card">
            <h1>
                <i class="fas fa-folder-open"></i>
                Kelola File Dokumentasi
            </h1>
            <div class="breadcrumb">
                <a href="index.php">Beranda</a> › 
                <a href="view_log.php?id=<?= $log_id ?>">Detail Laporan</a> › 
                <span>Kelola File</span>
            </div>
            <p style="margin-top: 15px; color: #64748b;">
                Laporan tanggal: <strong><?= date('d F Y', strtotime($log_data['tanggal'])) ?></strong>
            </p>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            File berhasil diupload!
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            File berhasil dihapus!
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>

        <!-- Upload Section -->
        <div class="upload-card">
            <h2>
                <i class="fas fa-cloud-upload-alt"></i>
                Upload File Baru
            </h2>
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="dropArea">
                    <div class="upload-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h3>Tarik & Lepas File atau Klik untuk Memilih</h3>
                    <p>Format: PDF, DOC, XLS, PPT, Gambar, ZIP (Max 10MB)</p>
                    <input type="file" name="dokumen" id="fileInput" class="file-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar" required>
                    <button type="button" class="btn-choose" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-folder-open"></i> Pilih File
                    </button>
                    <div class="file-info" id="fileInfo"></div>
                </div>
                <button type="submit" class="btn-choose" style="width: 100%; margin-top: 20px; background: var(--success);">
                    <i class="fas fa-upload"></i> Upload File
                </button>
            </form>
        </div>

        <!-- Files List -->
        <div class="files-card">
            <h2>
                <i class="fas fa-list"></i>
                Daftar File (<?= mysqli_num_rows($files_result) ?>)
            </h2>

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
                    
                    $file_size = filesize($file['file_path']);
                    $file_size_kb = round($file_size / 1024, 2);
                    ?>
                    <div class="file-item">
                        <div class="file-icon <?= $icon_class ?>">
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        <div class="file-info-content">
                            <div class="file-name"><?= htmlspecialchars($file['file_name']) ?></div>
                            <div class="file-meta">
                                <?= strtoupper($file['file_type']) ?> • <?= $file_size_kb ?> KB • 
                                <?= date('d/m/Y H:i', strtotime($file['upload_date'])) ?>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="<?= $file['file_path'] ?>" class="btn-download" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button class="btn-delete" onclick="confirmDelete(<?= $file['id'] ?>)">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>Belum ada file</h5>
                    <p>Upload file dokumentasi untuk laporan ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // File input change
        document.getElementById('fileInput').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileInfo = document.getElementById('fileInfo');
                const fileSizeKB = (file.size / 1024).toFixed(2);
                
                fileInfo.innerHTML = `
                    <i class="fas fa-file"></i> 
                    <strong>${file.name}</strong> (${fileSizeKB} KB)
                `;
                fileInfo.classList.add('show');
            }
        });

        // Drag and drop
        const dropArea = document.getElementById('dropArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => {
                dropArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => {
                dropArea.classList.remove('dragover');
            }, false);
        });

        dropArea.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            document.getElementById('fileInput').files = files;
            
            // Trigger change event
            const event = new Event('change');
            document.getElementById('fileInput').dispatchEvent(event);
        });

        // Delete confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus file ini?')) {
                window.location.href = `manage_files.php?log_id=<?= $log_id ?>&delete=${id}`;
            }
        }

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('fileInput');
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Silakan pilih file terlebih dahulu!');
            }
        });
    </script>
</body>
</html>