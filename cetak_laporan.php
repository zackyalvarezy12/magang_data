<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Action Bar */
        .action-bar {
            max-width: 210mm;
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

        /* Print Container - UKURAN A4 */
        .print-container {
            width: 190mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 10px;
            overflow: hidden;
        }

        .page-content {
            padding: 10mm;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 14px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 12px;
            margin-bottom: 5px;
            font-weight: normal;
        }

        .header p {
            font-size: 9px;
            color: #555;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 12px;
            border: 1px solid #ddd;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 100px 1fr;
            margin-bottom: 4px;
            font-size: 9px;
        }

        .info-label {
            font-weight: bold;
            color: #333;
        }

        .info-value {
            color: #555;
        }

        /* Statistics */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }

        .stat-box {
            border: 1px solid #ddd;
            padding: 8px 6px;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 4px;
        }

        .stat-box h3 {
            font-size: 16px;
            margin-bottom: 3px;
            color: #4f46e5;
            font-weight: 700;
        }

        .stat-box p {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 7.5px;
            border: 1px solid #333;
        }

        table th {
            background: #1e293b;
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            border: 1px solid #333;
            vertical-align: middle;
            line-height: 1.2;
        }

        table td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            vertical-align: top;
            line-height: 1.2;
        }

        table td:first-child {
            text-align: center;
            font-weight: bold;
        }

        table td:nth-child(2) {
            text-align: center;
            font-size: 7px;
        }

        table td:nth-child(3) {
            text-align: center;
        }

        table td:nth-child(4) {
            text-align: center;
            font-size: 7px;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
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

        /* Footer */
        .footer {
            margin-top: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            page-break-inside: avoid;
        }

        .signature {
            text-align: center;
        }

        .signature p {
            font-size: 9px;
            margin-bottom: 40px;
        }

        .signature .name {
            font-weight: bold;
            border-top: 1px solid #000;
            display: inline-block;
            padding-top: 4px;
            margin-top: 30px;
            min-width: 120px;
            font-size: 9px;
        }

        .print-info {
            margin-top: 15px;
            padding: 8px;
            background: #f5f5f5;
            border-top: 2px solid #ddd;
            font-size: 7px;
            color: #666;
            text-align: center;
            border-radius: 0 0 4px 4px;
            line-height: 1.4;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
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

            .print-container {
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }

            .page-content {
                padding: 0;
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

            .footer {
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .print-container {
                width: 100%;
            }

            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-bar {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
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
        <button class="btn-action btn-download-pdf" onclick="downloadPDF()">
            <i class="fas fa-file-pdf"></i> Download PDF
        </button>
        <button class="btn-action btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Langsung
        </button>
        <a href="index.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Print Container -->
    <div class="print-container" id="printContent">
        <div class="page-content">
            <!-- Header -->
            <div class="header">
                <h1>Politeknik Negeri Padang</h1>
                <h2>Laporan Harian Magang</h2>
                <p>Jl. Kampus Limau Manis, Padang, Sumatera Barat</p>
            </div>

            <!-- Info Peserta -->
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Nama Peserta:</div>
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
                    <div class="info-label">Periode Laporan:</div>
                    <div class="info-value"><?= $periode ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tanggal Cetak:</div>
                    <div class="info-value"><?= date('d F Y, H:i') ?> WIB</div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-section">
                <div class="stat-box">
                    <h3><?= $total_data ?></h3>
                    <p>Total Laporan</p>
                </div>
                <div class="stat-box">
                    <h3><?= $total_masuk ?></h3>
                    <p>Masuk Kerja</p>
                </div>
                <div class="stat-box">
                    <h3><?= $total_sakit ?></h3>
                    <p>Sakit</p>
                </div>
                <div class="stat-box">
                    <h3><?= $total_izin ?></h3>
                    <p>Izin/Cuti</p>
                </div>
            </div>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 25px;">No</th>
                        <th style="width: 50px;">Tanggal</th>
                        <th style="width: 60px;">Status</th>
                        <th style="width: 50px;">Waktu</th>
                        <th>Deskripsi Tugas</th>
                        <th style="width: 70px;">Ket</th>
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
                                $waktu = date('H:i', strtotime($row['waktu_mulai'])) . "-" . date('H:i', strtotime($row['waktu_selesai']));
                            }
                            
                            // Truncate deskripsi jika terlalu panjang
                            $deskripsi = htmlspecialchars($row['deskripsi_tugas']);
                            if (strlen($deskripsi) > 200) {
                                $deskripsi = substr($deskripsi, 0, 200) . '...';
                            }
                            
                            $keterangan = !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : '-';
                            if (strlen($keterangan) > 80) {
                                $keterangan = substr($keterangan, 0, 80) . '...';
                            }
                            
                            echo "<tr>
                                <td>{$no}</td>
                                <td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                                <td><span class='status-badge {$badge_class}'>" . ($row['status_hari'] == 'Masuk Kerja' ? 'Masuk' : ($row['status_hari'] == 'Sakit' ? 'Sakit' : 'Izin')) . "</span></td>
                                <td>{$waktu}</td>
                                <td style='text-align: left;'>{$deskripsi}</td>
                                <td style='font-size: 7px; text-align: left;'>{$keterangan}</td>
                            </tr>";
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='6'>
                            <div class='empty-state'>
                                <p>Tidak ada data laporan untuk periode yang dipilih</p>
                            </div>
                        </td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Footer / Signature -->
            <div class="footer">
                <div class="signature">
                    <p>Pembimbing Lapangan</p>
                    <div class="name">( ___________________ )</div>
                </div>
                <div class="signature">
                    <p>Peserta Magang</p>
                    <div class="name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                </div>
            </div>

            <!-- Print Info -->
            <div class="print-info">
                Dokumen ini dicetak pada: <?= date('d F Y, H:i') ?> WIB<br>
                Dokumen ini merupakan bukti sah kegiatan magang dan dapat digunakan sebagai arsip
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            // Show loading
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
            doc.rect(0, 0, 210, 35, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('POLITEKNIK NEGERI PADANG', 105, 12, { align: 'center' });
            
            doc.setFontSize(12);
            doc.text('Laporan Harian Kegiatan Magang', 105, 19, { align: 'center' });
            
            doc.setFontSize(8);
            doc.setFont(undefined, 'normal');
            doc.text('Jl. Kampus Limau Manis, Padang, Sumatera Barat', 105, 25, { align: 'center' });
            doc.text('Telp: (0751) 72590', 105, 30, { align: 'center' });

            // Info Section
            let y = 43;
            doc.setTextColor(0, 0, 0);
            doc.setFillColor(248, 250, 252);
            doc.roundedRect(10, y, 190, 40, 2, 2, 'F');
            doc.setDrawColor(226, 232, 240);
            doc.setLineWidth(0.5);
            doc.roundedRect(10, y, 190, 40, 2, 2, 'S');
            
            doc.setFontSize(9);
            const infoLeft = 15;
            let infoY = y + 7;
            
            doc.setFont(undefined, 'bold');
            doc.text('Nama:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.nama, infoLeft + 35, infoY);
            
            infoY += 6;
            doc.setFont(undefined, 'bold');
            doc.text('Email:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.email, infoLeft + 35, infoY);
            
            infoY += 6;
            doc.setFont(undefined, 'bold');
            doc.text('No. HP:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.noHp, infoLeft + 35, infoY);
            
            infoY += 6;
            doc.setFont(undefined, 'bold');
            doc.text('Durasi Magang:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.durasi + ' Hari', infoLeft + 35, infoY);
            
            infoY += 6;
            doc.setFont(undefined, 'bold');
            doc.text('Periode:', infoLeft, infoY);
            doc.setFont(undefined, 'normal');
            doc.text(userData.periode, infoLeft + 35, infoY);
            
            // Statistics
            y = 88;
            const statWidth = 45;
            const statGap = 2;
            
            const statData = [
                { label: 'Total', value: stats.total, color: [79, 70, 229] },
                { label: 'Masuk', value: stats.masuk, color: [16, 185, 129] },
                { label: 'Sakit', value: stats.sakit, color: [245, 158, 11] },
                { label: 'Izin', value: stats.izin, color: [239, 68, 68] }
            ];
            
            statData.forEach((item, index) => {
                const x = 10 + (index * (statWidth + statGap));
                
                doc.setFillColor(248, 250, 252);
                doc.roundedRect(x, y, statWidth, 15, 2, 2, 'F');
                
                doc.setDrawColor(...item.color);
                doc.setLineWidth(0.5);
                doc.roundedRect(x, y, statWidth, 15, 2, 2, 'S');
                
                doc.setFillColor(...item.color);
                doc.roundedRect(x, y, 2, 15, 2, 2, 'F');
                
                doc.setTextColor(...item.color);
                doc.setFontSize(14);
                doc.setFont(undefined, 'bold');
                doc.text(item.value.toString(), x + 7, y + 9);
                
                doc.setTextColor(100, 116, 139);
                doc.setFontSize(7);
                doc.setFont(undefined, 'bold');
                doc.text(item.label.toUpperCase(), x + 7, y + 12.5);
                
                const percent = stats.total > 0 ? ((item.value / stats.total) * 100).toFixed(1) : 0;
                doc.setTextColor(...item.color);
                doc.setFontSize(9);
                doc.setFont(undefined, 'bold');
                doc.text(percent + '%', x + statWidth - 3, y + 9, { align: 'right' });
            });

            // Table
            y = 108;
            
            const tableData = [];
            <?php
            mysqli_data_seek($result, 0);
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $waktu = "-";
                if(!empty($row['waktu_mulai']) && !empty($row['waktu_selesai'])) {
                    $waktu = date('H:i', strtotime($row['waktu_mulai'])) . "-" . date('H:i', strtotime($row['waktu_selesai']));
                }
                
                $deskripsi = str_replace(["\r\n", "\n", "\r", "'"], [' ', ' ', ' ', ""], htmlspecialchars($row['deskripsi_tugas']));
                $keterangan = !empty($row['keterangan']) ? str_replace(["\r\n", "\n", "\r", "'"], [' ', ' ', ' ', ""], htmlspecialchars($row['keterangan'])) : '-';
                
                // Shorten status
                $status_short = $row['status_hari'];
                if ($status_short == 'Masuk Kerja') $status_short = 'Masuk';
                if ($status_short == 'Izin/Libur Resmi') $status_short = 'Izin';
                if ($status_short == 'Cuti Pribadi') $status_short = 'Cuti';
                
                echo "tableData.push([
                    '{$no}',
                    '" . date('d/m/Y', strtotime($row['tanggal'])) . "',
                    '{$status_short}',
                    '{$waktu}',
                    '{$deskripsi}'
                ]);\n";
                $no++;
            }
            ?>

            doc.autoTable({
                startY: y,
                head: [['No', 'Tanggal', 'Status', 'Waktu', 'Deskripsi Kegiatan']],
                body: tableData,
                theme: 'grid',
                styles: {
                    fontSize: 7,
                    cellPadding: 2,
                    lineColor: [203, 213, 225],
                    lineWidth: 0.3,
                    textColor: [30, 41, 59],
                    font: 'helvetica',
                    overflow: 'linebreak',
                    cellWidth: 'wrap'
                },
                headStyles: {
                    fillColor: [30, 41, 59],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center',
                    fontSize: 8
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10, fontStyle: 'bold', textColor: [79, 70, 229] },
                    1: { halign: 'center', cellWidth: 22, fontSize: 6.5 },
                    2: { halign: 'center', cellWidth: 18 },
                    3: { halign: 'center', cellWidth: 20, fontSize: 6.5 },
                    4: { halign: 'left', cellWidth: 120 }
                },
                alternateRowStyles: {
                    fillColor: [248, 250, 252]
                },
                margin: { left: 10, right: 10 },
                didParseCell: function(data) {
                    if (data.column.index === 2 && data.section === 'body') {
                        const status = data.cell.raw;
                        if (status === 'Masuk') {
                            data.cell.styles.fillColor = [209, 250, 229];
                            data.cell.styles.textColor = [6, 95, 70];
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fontSize = 6.5;
                        } else if (status === 'Sakit') {
                            data.cell.styles.fillColor = [254, 243, 199];
                            data.cell.styles.textColor = [146, 64, 14];
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fontSize = 6.5;
                        } else {
                            data.cell.styles.fillColor = [254, 226, 226];
                            data.cell.styles.textColor = [153, 27, 27];
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fontSize = 6.5;
                        }
                    }
                }
            });

            // Signature
            let summaryY = doc.lastAutoTable.finalY + 15;
            const pageHeight = doc.internal.pageSize.getHeight();
            
            if (summaryY > pageHeight - 50) {
                doc.addPage();
                summaryY = 20;
            }
            
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(0, 0, 0);
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
            
            // Footer
            doc.setFillColor(248, 250, 252);
            doc.rect(0, pageHeight - 15, 210, 15, 'F');
            
            doc.setFontSize(7);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text('Dokumen dicetak: ' + userData.tanggalCetak + ' WIB', 105, pageHeight - 9, { align: 'center' });
            doc.text('Dokumen resmi kegiatan magang - POLITEKNIK NEGERI PADANG', 105, pageHeight - 5, { align: 'center' });

            const fileName = 'Laporan_Magang_<?= str_replace(' ', '_', $user['nama_lengkap']) ?>_<?= date('Y-m-d') ?>.pdf';
            doc.save(fileName);

            setTimeout(() => {
                document.getElementById('loadingOverlay').classList.remove('show');
            }, 500);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                downloadPDF();
            }
        });
    </script>
</body>
</html> 