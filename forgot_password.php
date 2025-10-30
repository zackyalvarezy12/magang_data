<?php
session_start();
include 'db_connect.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    
    // Cari user berdasarkan username dan no HP
    $query = "SELECT * FROM users WHERE username = '$username' AND no_hp = '$no_hp'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Generate password baru (random 8 karakter)
        $new_password = bin2hex(random_bytes(4)); // 8 karakter random
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password di database
        $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = {$user['id']}";
        
        if (mysqli_query($conn, $update_query)) {
            // Format nomor WhatsApp (hapus 0 di depan, tambah 62)
            $wa_number = $no_hp;
            if (substr($wa_number, 0, 1) == '0') {
                $wa_number = '62' . substr($wa_number, 1);
            }
            
            // Pesan WhatsApp
            $message = "🔐 *Reset Password Berhasil*\n\n";
            $message .= "Halo *{$user['nama_lengkap']}*,\n\n";
            $message .= "Password Anda telah direset. Berikut kredensial login baru Anda:\n\n";
            $message .= "👤 Username: *{$username}*\n";
            $message .= "🔑 Password Baru: *{$new_password}*\n\n";
            $message .= "⚠️ *PENTING:*\n";
            $message .= "- Segera login dan ubah password Anda di menu Pengaturan\n";
            $message .= "- Jangan bagikan password ini kepada siapapun\n";
            $message .= "- Simpan password ini dengan aman\n\n";
            $message .= "🌐 Login di: " . $_SERVER['HTTP_HOST'] . "/login.php\n\n";
            $message .= "_Sistem Daftar Harian Magang - Politeknik Negeri Padang_";
            
            // Encode pesan untuk URL
            $encoded_message = urlencode($message);
            
            // WhatsApp API URL
            $whatsapp_url = "https://api.whatsapp.com/send?phone={$wa_number}&text={$encoded_message}";
            
            $success = "Password berhasil direset! Password baru akan dikirim ke WhatsApp Anda.";
            $whatsapp_link = $whatsapp_url;
        } else {
            $error = "Gagal mereset password. Silakan coba lagi.";
        }
    } else {
        $error = "Username atau nomor HP tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sistem Magang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
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

        .card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .card-header .icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .card-header .icon i {
            font-size: 2.5rem;
            color: var(--primary);
        }

        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .card-body {
            padding: 40px 35px;
        }

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

        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 0.9rem;
            line-height: 1.6;
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

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .form-hint {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 5px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-back {
            background: #64748b;
            color: white;
            margin-top: 15px;
        }

        .btn-back:hover {
            background: #475569;
            color: white;
        }

        .wa-button {
            margin-top: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .card-footer {
            text-align: center;
            padding: 25px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.85rem;
        }

        @media (max-width: 480px) {
            .card-header {
                padding: 30px 20px;
            }

            .card-header h1 {
                font-size: 1.5rem;
            }

            .card-body {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="icon">
                    <i class="fas fa-key"></i>
                </div>
                <h1>Lupa Password</h1>
                <p>Reset password Anda via WhatsApp</p>
            </div>

            <div class="card-body">
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                    <span><?= $success ?></span>
                </div>
                
                <?php if (isset($whatsapp_link)): ?>
                <div class="wa-button">
                    <a href="<?= $whatsapp_link ?>" target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp" style="font-size: 1.5rem;"></i>
                        Buka WhatsApp untuk Melihat Password Baru
                    </a>
                </div>
                <?php endif; ?>
                
                <a href="login.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Login
                </a>
                <?php else: ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                    <span><?= $error ?></span>
                </div>
                <?php endif; ?>

                <div class="info-box">
                    <p>
                        <i class="fas fa-info-circle"></i>
                        <strong>Cara Reset Password:</strong><br>
                        1. Masukkan username dan nomor HP Anda<br>
                        2. Password baru akan dikirim ke WhatsApp Anda<br>
                        3. Login dengan password baru<br>
                        4. Ubah password di menu Pengaturan
                    </p>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i>
                            Nomor HP (WhatsApp)
                        </label>
                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Nomor HP yang terdaftar di akun Anda
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i>
                        Reset Password
                    </button>

                    <a href="login.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Login
                    </a>
                </form>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <p>&copy; 2025 Politeknik Negeri Padang. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>