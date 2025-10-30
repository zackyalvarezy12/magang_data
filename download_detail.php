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

// Ambil data user
$user_query = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
$user = mysqli_fetch_assoc($user_query);



// Handle foto dengan path yang benar
$fotoPath = '';
$fotoBase64 = '';
if (!empty($data['bukti_foto'])) {
    // Cek berbagai kemungkinan path
    $possible_paths = [
        $data['bukti_foto'],                          // Path langsung dari database
        'uploads/' . basename($data['bukti_foto']),   // Path relatif uploads/
        '../uploads/' . basename($data['bukti_foto']), // Path parent
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $fotoPath = $path;
            break;
        }
    }
    
    // Convert image to base64 untuk embed dalam HTML
    if (!empty($fotoPath) && file_exists($fotoPath)) {
        $imageData = base64_encode(file_get_contents($fotoPath));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fotoPath);
        finfo_close($finfo);
        $fotoBase64 = 'data:' . $mimeType . ';base64,' . $imageData;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - <?= date('d-m-Y', strtotime($data['tanggal'])) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            background: #fff;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: normal;
        }

        .header p {
            font-size: 12px;
            color: #555;
        }

        /* Title Section */
        .title-section {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background: #f5f5f5;
            border-left: 4px solid #4f46e5;
        }

        .title-section h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .title-section p {
            font-size: 14px;
            color: #666;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .info-section h4 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            margin-bottom: 12px;
            font-size: 13px;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #333;
        }

        .info-value {
            color: #555;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
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

        /* Description Box */
        .desc-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .desc-section h4 {
            font-size: 14px;
            margin-bottom: 12px;
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }

        .desc-content {
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #4f46e5;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-line;
            color: #333;
        }

        /* Photo Section */
        .photo-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
            page-break-inside: avoid;
        }

        .photo-section h4 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }

        .photo-section img {
            max-width: 100%;
            max-height: 400px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
        }

        .no-photo {
            padding: 40px;
            background: #f9f9f9;
            color: #999;
            font-style: italic;
        }

        /* Files Section */
        .files-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .files-section h4 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
        }

        .file-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 12px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 15px;
            align-items: center;
        }

        .file-size {
            color: #888;
            font-size: 11px;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            page-break-inside: avoid;
        }

        .signature {
            text-align: center;
        }

        .signature p {
            font-size: 12px;
            margin-bottom: 70px;
        }

        .signature .name {
            font-weight: bold;
            border-top: 1px solid #000;
            display: inline-block;
            padding-top: 5px;
            min-width: 200px;
        }

        /* Print Info */
        .print-info {
            margin-top: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-top: 2px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: center;
        }

        /* Print Button */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print, .btn-back {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
            font-weight: 600;
        }

        .btn-print {
            background: #4f46e5;
            color: white;
        }

        .btn-print:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #64748b;
            color: white;
        }

        .btn-back:hover {
            background: #475569;
        }

        /* Print styles */
        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .print-container {
                max-width: 100%;
            }

            .footer, .photo-section {
                page-break-inside: avoid;
            }
        }

        /* Time Badge */
        .time-info {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .empty-note {
            color: #999;
            font-style: italic;
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #ddd;
        }

        .empty-files {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="downloadPDF()">📥 Download PDF</button>
        <button class="btn-print" onclick="window.print()" style="background: #10b981;">🖨️ Cetak Langsung</button>
        <a href="view_log.php?id=<?= $data['id'] ?>" class="btn-back">← Kembali ke Detail</a>
        <a href="index.php" class="btn-back">🏠 Beranda</a>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <h1>Politeknik Negeri Padang</h1>
            <h2>Laporan Harian Kegiatan Magang</h2>
            <p>Jl. Kampus Limau Manis, Padang, Sumatera Barat</p>
        </div>

        <!-- Title -->
        <div class="title-section">
            <h3>DETAIL LAPORAN HARIAN</h3>
            <p>Tanggal: <?= date('d F Y', strtotime($data['tanggal'])) ?></p>
        </div>

        <!-- Info Peserta -->
        <div class="info-section">
            <h4>📋 Informasi Peserta Magang</h4>
            <div class="info-row">
                <div class="info-label">Nama Lengkap:</div>
                <div class="info-value"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">No. HP:</div>
                <div class="info-value"><?= htmlspecialchars($user['no_hp']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Durasi Magang:</div>
                <div class="info-value"><?= $user['durasi_magang'] ?> Hari</div>
            </div>
        </div>

        <!-- Detail Kegiatan -->
        <div class="info-section">
            <h4>📅 Detail Kegiatan Harian</h4>
            <div class="info-row">
                <div class="info-label">Tanggal:</div>
                <div class="info-value"><?= date('l, d F Y', strtotime($data['tanggal'])) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status Hari:</div>
                <div class="info-value">
                    <?php
                    $statusClass = 'badge-masuk';
                    if($data['status_hari'] == 'Sakit') $statusClass = 'badge-sakit';
                    if(in_array($data['status_hari'], ['Izin/Libur Resmi', 'Cuti Pribadi'])) $statusClass = 'badge-izin';
                    ?>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= htmlspecialchars($data['status_hari']) ?>
                    </span>
                </div>
            </div>
            <?php if (!empty($data['waktu_mulai']) && !empty($data['waktu_selesai'])): ?>
            <div class="info-row">
                <div class="info-label">Jam Masuk:</div>
                <div class="info-value">
                    <span class="time-info">⏰ <?= date('H:i', strtotime($data['waktu_mulai'])) ?> WIB</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Jam Pulang:</div>
                <div class="info-value">
                    <span class="time-info">⏰ <?= date('H:i', strtotime($data['waktu_selesai'])) ?> WIB</span>
                </div>
            </div>
            <?php 
            $mulai = new DateTime($data['waktu_mulai']);
            $selesai = new DateTime($data['waktu_selesai']);
            $durasi = $mulai->diff($selesai);
            ?>
            <div class="info-row">
                <div class="info-label">Total Durasi:</div>
                <div class="info-value">
                    <strong><?= $durasi->h ?> Jam <?= $durasi->i ?> Menit</strong>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Deskripsi Tugas -->
        <div class="desc-section">
            <h4>📝 Deskripsi Tugas / Kegiatan</h4>
            <div class="desc-content">
                <?= nl2br(htmlspecialchars($data['deskripsi_tugas'])) ?>
            </div>
        </div>

        <!-- Keterangan Tambahan -->
        <div class="desc-section">
            <h4>📌 Keterangan Tambahan</h4>
            <?php if(!empty($data['keterangan'])): ?>
                <div class="desc-content">
                    <?= nl2br(htmlspecialchars($data['keterangan'])) ?>
                </div>
            <?php else: ?>
                <div class="empty-note">
                    Tidak ada keterangan tambahan
                </div>
            <?php endif; ?>
        </div>

        <!-- Bukti Foto -->
        <div class="photo-section">
            <h4>📸 Bukti Foto Kegiatan</h4>
            <?php if (!empty($fotoBase64)): ?>
                <img src="<?= $fotoBase64 ?>" alt="Bukti Foto Kegiatan">
                <p style="margin-top: 10px; font-size: 11px; color: #666;">Foto dokumentasi kegiatan magang</p>
            <?php else: ?>
                <div class="no-photo">
                    Tidak ada dokumentasi foto untuk kegiatan ini
                </div>
            <?php endif; ?>
        </div>



        <!-- Footer / Signature -->
        <div class="footer">
            <div class="signature">
                <p>Mengetahui,<br>Pembimbing Lapangan</p>
                <div class="name">( ___________________ )</div>
            </div>
            <div class="signature">
                <p>Yang Melaporkan,<br>Peserta Magang</p>
                <div class="name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
            </div>
        </div>

        <!-- Print Info -->
        <div class="print-info">
            Dokumen ini dicetak pada: <?= date('d F Y, H:i') ?> WIB<br>
            Dokumen ini merupakan bukti sah kegiatan magang dan dapat digunakan sebagai arsip
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            // Hide buttons before generating PDF
            const buttons = document.querySelector('.no-print');
            buttons.style.display = 'none';
            
            const element = document.querySelector('.print-container');
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'Laporan_Detail_<?= date('d-m-Y', strtotime($data['tanggal'])) ?>_<?= str_replace(' ', '_', $user['nama_lengkap']) ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    letterRendering: true
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait' 
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                // Show buttons again after PDF is generated
                buttons.style.display = 'block';
            });
        }
    </script>
</body>
</html>