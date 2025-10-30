<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Ambil data user
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

$success = '';
$error = '';

// Proses update data profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $durasi_magang = intval($_POST['durasi_magang']);
    
    // Validasi
    if (empty($nama_lengkap) || empty($email) || empty($no_hp) || $durasi_magang <= 0) {
        $error = "Semua field wajib diisi dengan benar!";
    } else {
        $update_query = "UPDATE users SET 
            nama_lengkap = '$nama_lengkap',
            email = '$email',
            no_hp = '$no_hp',
            durasi_magang = $durasi_magang
            WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Data profil berhasil diperbarui!";
            // Refresh data
            $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
            $user = mysqli_fetch_assoc($user_query);
            
            // Redirect jika dari first setup
            if (isset($_GET['first'])) {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    }
}

// Proses ubah password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Semua field password wajib diisi!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Password baru dan konfirmasi password tidak sama!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Verifikasi password lama
        if (password_verify($old_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password: " . mysqli_error($conn);
            }
        } else {
            $error = "Password lama tidak sesuai!";
        }
    }
}

$is_first_setup = isset($_GET['first']) && $_GET['first'] == '1';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Sistem Magang</title>
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
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
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

        .header-card p {
            color: #64748b;
            font-size: 0.95rem;
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

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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

        .form-card h2 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

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
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .form-hint {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 5px;
            font-style: italic;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .form-control.with-icon {
            padding-left: 48px;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Duration Options */
        .duration-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .duration-option {
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .duration-option:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        .duration-option.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .duration-option .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .duration-option .label {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Action Buttons */
        .action-section {
            margin-top: 30px;
            padding-top: 25px;
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

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            margin-bottom: 25px;
        }

        .info-box h4 {
            color: var(--dark);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-card h1 {
                font-size: 1.4rem;
            }

            .form-card {
                padding: 25px 20px;
            }

            .action-section {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .duration-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <h1>
                <i class="fas fa-cog"></i>
                Pengaturan
            </h1>
            <p><?= $is_first_setup ? 'Lengkapi data Anda untuk memulai' : 'Kelola informasi profil dan keamanan akun Anda' ?></p>
        </div>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
            <span><?= $success ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <?php if ($is_first_setup): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" style="font-size: 1.5rem;"></i>
            <div>
                <strong>Selamat Datang!</strong> Silakan lengkapi data profil dan tentukan durasi magang Anda untuk memulai.
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Profil -->
        <div class="form-card">
            <h2>
                <i class="fas fa-user-edit"></i>
                Informasi Profil
            </h2>

            <form method="POST" id="settingsForm">
                <input type="hidden" name="update_profile" value="1">
                
                <!-- Nama Lengkap -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Nama Lengkap <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="nama_lengkap" class="form-control with-icon" 
                               value="<?= htmlspecialchars($user['nama_lengkap']) ?>" 
                               placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control with-icon" 
                               value="<?= htmlspecialchars($user['email']) ?>" 
                               placeholder="email@example.com" required>
                    </div>
                </div>

                <!-- No. HP -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone"></i>
                        No. HP (WhatsApp) <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="no_hp" class="form-control with-icon" 
                               value="<?= htmlspecialchars($user['no_hp']) ?>" 
                               placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Nomor ini akan digunakan untuk reset password via WhatsApp
                    </div>
                </div>

                <h2 style="margin-top: 40px;">
                    <i class="fas fa-calendar-alt"></i>
                    Durasi Magang
                </h2>

                <div class="info-box">
                    <h4>
                        <i class="fas fa-lightbulb"></i>
                        Informasi Durasi Magang
                    </h4>
                    <p>
                        Tentukan berapa lama durasi magang Anda (dalam hari). Durasi ini akan digunakan sebagai 
                        target dalam perhitungan progress magang Anda di dashboard.
                    </p>
                </div>

                <!-- Duration Quick Select -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock"></i>
                        Pilih Durasi Cepat
                    </label>
                    <div class="duration-options">
                        <div class="duration-option <?= $user['durasi_magang'] == 30 ? 'active' : '' ?>" 
                             onclick="setDuration(30)">
                            <div class="value">30</div>
                            <div class="label">Hari</div>
                        </div>
                        <div class="duration-option <?= $user['durasi_magang'] == 60 ? 'active' : '' ?>" 
                             onclick="setDuration(60)">
                            <div class="value">60</div>
                            <div class="label">Hari</div>
                        </div>
                        <div class="duration-option <?= $user['durasi_magang'] == 90 ? 'active' : '' ?>" 
                             onclick="setDuration(90)">
                            <div class="value">90</div>
                            <div class="label">Hari</div>
                        </div>
                        <div class="duration-option <?= $user['durasi_magang'] == 120 ? 'active' : '' ?>" 
                             onclick="setDuration(120)">
                            <div class="value">120</div>
                            <div class="label">Hari</div>
                        </div>
                        <div class="duration-option <?= $user['durasi_magang'] == 180 ? 'active' : '' ?>" 
                             onclick="setDuration(180)">
                            <div class="value">180</div>
                            <div class="label">Hari</div>
                        </div>
                    </div>
                </div>

                <!-- Custom Duration -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-edit"></i>
                        Atau Masukkan Durasi Custom <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-calendar-check input-icon"></i>
                        <input type="number" name="durasi_magang" id="durasiInput" 
                               class="form-control with-icon" 
                               value="<?= $user['durasi_magang'] ?>" 
                               placeholder="Contoh: 90" min="1" required>
                    </div>
                    <div class="form-hint">Masukkan jumlah hari (minimal 1 hari)</div>
                </div>

                <!-- Action Buttons -->
                <div class="action-section">
                    <?php if (!$is_first_setup): ?>
                    <a href="dashboard.php" class="btn-action btn-cancel">
                        <i class="fas fa-times"></i>
                        Batal
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="btn-action btn-save">
                        <i class="fas fa-save"></i>
                        <?= $is_first_setup ? 'Simpan & Lanjutkan' : 'Simpan Perubahan' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Form Ubah Password -->
        <?php if (!$is_first_setup): ?>
        <div class="form-card">
            <h2>
                <i class="fas fa-lock"></i>
                Ubah Password
            </h2>

            <div class="info-box">
                <h4>
                    <i class="fas fa-shield-alt"></i>
                    Keamanan Password
                </h4>
                <p>
                    Pastikan password Anda kuat dan unik. Gunakan kombinasi huruf besar, kecil, angka, dan simbol.
                    Password minimal 6 karakter.
                </p>
            </div>

            <form method="POST" id="passwordForm">
                <input type="hidden" name="change_password" value="1">
                
                <!-- Password Lama -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i>
                        Password Lama <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" name="old_password" id="oldPassword" 
                               class="form-control with-icon" 
                               placeholder="Masukkan password lama" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('oldPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Baru -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i>
                        Password Baru <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="new_password" id="newPassword" 
                               class="form-control with-icon" 
                               placeholder="Masukkan password baru" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-hint">Minimal 6 karakter</div>
                </div>

                <!-- Konfirmasi Password -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i>
                        Konfirmasi Password Baru <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="confirmPassword" 
                               class="form-control with-icon" 
                               placeholder="Ulangi password baru" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-section">
                    <button type="submit" class="btn-action btn-save" style="background: linear-gradient(135deg, var(--warning), #d97706);">
                        <i class="fas fa-key"></i>
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Set duration from quick select
        function setDuration(days) {
            document.getElementById('durasiInput').value = days;
            
            // Update active state
            const options = document.querySelectorAll('.duration-option');
            options.forEach(option => {
                option.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        // Update active state when typing custom duration
        document.getElementById('durasiInput').addEventListener('input', function() {
            const value = parseInt(this.value);
            const options = document.querySelectorAll('.duration-option');
            
            options.forEach(option => {
                const optionValue = parseInt(option.querySelector('.value').textContent);
                if (optionValue === value) {
                    option.classList.add('active');
                } else {
                    option.classList.remove('active');
                }
            });
        });

        // Form validation
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const durasi = parseInt(document.getElementById('durasiInput').value);
            
            if (durasi < 1) {
                e.preventDefault();
                alert('Durasi magang minimal 1 hari!');
                return;
            }
        });

        // Password form validation
        <?php if (!$is_first_setup): ?>
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password baru dan konfirmasi password tidak sama!');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return;
            }
        });
        <?php endif; ?>

        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>