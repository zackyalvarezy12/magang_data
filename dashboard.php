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

// Jika user belum set durasi magang, redirect ke settings
if (empty($user['durasi_magang'])) {
    header("Location: settings.php?first=1");
    exit();
}

// Hitung statistik
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log");
$total_laporan = mysqli_fetch_assoc($total_query)['total'];

$masuk_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari='Masuk Kerja'");
$total_masuk = mysqli_fetch_assoc($masuk_query)['total'];

$sakit_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari='Sakit'");
$total_sakit = mysqli_fetch_assoc($sakit_query)['total'];

$izin_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM internship_log WHERE status_hari IN ('Izin/Libur Resmi', 'Cuti Pribadi')");
$total_izin = mysqli_fetch_assoc($izin_query)['total'];

// Durasi magang
$durasi_magang = $user['durasi_magang'];
$progress_percentage = ($total_laporan / $durasi_magang) * 100;
$sisa_hari = $durasi_magang - $total_laporan;

// Data untuk grafik per bulan
$monthly_data = array();
$monthly_query = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(tanggal, '%Y-%m') as bulan,
        COUNT(*) as total,
        SUM(CASE WHEN status_hari='Masuk Kerja' THEN 1 ELSE 0 END) as masuk,
        SUM(CASE WHEN status_hari='Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status_hari IN ('Izin/Libur Resmi', 'Cuti Pribadi') THEN 1 ELSE 0 END) as izin
    FROM internship_log 
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC
");

while ($row = mysqli_fetch_assoc($monthly_query)) {
    $monthly_data[] = $row;
}

// Data untuk grafik mingguan (4 minggu terakhir)
$weekly_data = array();
for ($i = 3; $i >= 0; $i--) {
    $start_date = date('Y-m-d', strtotime("-$i week"));
    $end_date = date('Y-m-d', strtotime("-$i week +6 days"));
    
    $week_query = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status_hari='Masuk Kerja' THEN 1 ELSE 0 END) as masuk,
            SUM(CASE WHEN status_hari='Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status_hari IN ('Izin/Libur Resmi', 'Cuti Pribadi') THEN 1 ELSE 0 END) as izin
        FROM internship_log 
        WHERE tanggal BETWEEN '$start_date' AND '$end_date'
    ");
    
    $week_data = mysqli_fetch_assoc($week_query);
    $week_data['label'] = "Minggu " . (4 - $i);
    $weekly_data[] = $week_data;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Magang - <?= htmlspecialchars($user['nama_lengkap']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
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
        }

        .header-left p {
            color: #64748b;
            font-size: 1rem;
        }

        .header-right {
            display: flex;
            gap: 12px;
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Progress Card */
        .progress-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 0.6s ease;
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

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .progress-header h2 {
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            border: 2px solid #e2e8f0;
        }

        .info-box .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .info-box .label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .progress-bar-container {
            background: #e2e8f0;
            border-radius: 20px;
            height: 40px;
            overflow: hidden;
            position: relative;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--success), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 20px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            transition: width 1s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .progress-text {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
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
        }

        .stat-card.total::before { background: linear-gradient(90deg, #667eea, #764ba2); }
        .stat-card.masuk::before { background: linear-gradient(90deg, #10b981, #059669); }
        .stat-card.sakit::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .stat-card.izin::before { background: linear-gradient(90deg, #ef4444, #dc2626); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .stat-card.total .stat-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .stat-card.masuk .stat-icon {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .stat-card.sakit .stat-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .stat-card.izin .stat-icon {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-right {
                width: 100%;
                flex-direction: column;
            }

            .btn-nav {
                width: 100%;
                justify-content: center;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .progress-info {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid var(--warning);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-chart-line"></i> Dashboard Magang</h1>
                    <p>Selamat datang, <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong></p>
                </div>
                <div class="header-right">
                    <a href="index.php" class="btn-nav btn-primary">
                        <i class="fas fa-list"></i> Daftar Laporan
                    </a>
                    <a href="settings.php" class="btn-nav btn-secondary">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                </div>
            </div>
        </div>

        <?php if ($sisa_hari <= 7 && $sisa_hari > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
            <div>
                <strong>Perhatian!</strong> Masa magang Anda akan berakhir dalam <strong><?= $sisa_hari ?> hari</strong> lagi.
            </div>
        </div>
        <?php endif; ?>

        <!-- Progress Card -->
        <div class="progress-card">
            <div class="progress-header">
                <h2>
                    <i class="fas fa-tasks"></i>
                    Progress Magang
                </h2>
            </div>

            <div class="progress-info">
                <div class="info-box">
                    <div class="value"><?= $durasi_magang ?></div>
                    <div class="label">Target Hari</div>
                </div>
                <div class="info-box">
                    <div class="value"><?= $total_laporan ?></div>
                    <div class="label">Hari Tercatat</div>
                </div>
                <div class="info-box">
                    <div class="value"><?= max(0, $sisa_hari) ?></div>
                    <div class="label">Sisa Hari</div>
                </div>
                <div class="info-box">
                    <div class="value"><?= number_format($progress_percentage, 1) ?>%</div>
                    <div class="label">Persentase</div>
                </div>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?= min(100, $progress_percentage) ?>%;">
                    <?= number_format(min(100, $progress_percentage), 1) ?>%
                </div>
            </div>
            <div class="progress-text">
                <?php if ($progress_percentage >= 100): ?>
                    🎉 Selamat! Anda telah menyelesaikan target magang!
                <?php else: ?>
                    Anda telah menyelesaikan <?= $total_laporan ?> dari <?= $durasi_magang ?> hari magang
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-value"><?= $total_laporan ?></div>
                <div class="stat-label">Total Laporan</div>
            </div>
            <div class="stat-card masuk">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $total_masuk ?></div>
                <div class="stat-label">Masuk Kerja</div>
            </div>
            <div class="stat-card sakit">
                <div class="stat-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="stat-value"><?= $total_sakit ?></div>
                <div class="stat-label">Sakit</div>
            </div>
            <div class="stat-card izin">
                <div class="stat-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-value"><?= $total_izin ?></div>
                <div class="stat-label">Izin/Cuti</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- Chart 1: Status Kehadiran -->
            <div class="chart-card">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    Status Kehadiran
                </h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Trend Mingguan -->
            <div class="chart-card">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    Trend 4 Minggu Terakhir
                </h3>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 3: Trend Bulanan (Full Width) -->
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-line"></i>
                Trend Laporan Per Bulan
            </h3>
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Chart 1: Pie Chart - Status Kehadiran
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Masuk Kerja', 'Sakit', 'Izin/Cuti'],
                datasets: [{
                    data: [<?= $total_masuk ?>, <?= $total_sakit ?>, <?= $total_izin ?>],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                family: 'Poppins'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' hari (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Chart 2: Bar Chart - Trend Mingguan
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach($weekly_data as $w) echo "'" . $w['label'] . "',"; ?>],
                datasets: [
                    {
                        label: 'Masuk Kerja',
                        data: [<?php foreach($weekly_data as $w) echo $w['masuk'] . ","; ?>],
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 2
                    },
                    {
                        label: 'Sakit',
                        data: [<?php foreach($weekly_data as $w) echo $w['sakit'] . ","; ?>],
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 2
                    },
                    {
                        label: 'Izin/Cuti',
                        data: [<?php foreach($weekly_data as $w) echo $w['izin'] . ","; ?>],
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });

        // Chart 3: Line Chart - Trend Bulanan
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    foreach($monthly_data as $m) {
                        $date = DateTime::createFromFormat('Y-m', $m['bulan']);
                        echo "'" . $date->format('M Y') . "',";
                    }
                ?>],
                datasets: [
                    {
                        label: 'Masuk Kerja',
                        data: [<?php foreach($monthly_data as $m) echo $m['masuk'] . ","; ?>],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Sakit',
                        data: [<?php foreach($monthly_data as $m) echo $m['sakit'] . ","; ?>],
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Izin/Cuti',
                        data: [<?php foreach($monthly_data as $m) echo $m['izin'] . ","; ?>],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>