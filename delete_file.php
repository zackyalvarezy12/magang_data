<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Sisa kode delete_file.php yang sudah ada...

include 'db_connect.php';

if (!isset($_GET['id']) || !isset($_GET['log_id'])) {
    die("Parameter tidak lengkap.");
}

$file_id = intval($_GET['id']);
$log_id = intval($_GET['log_id']);

// Ambil informasi file
$file_query = mysqli_query($conn, "SELECT * FROM dokumentasi WHERE id = $file_id AND log_id = $log_id");
$file_data = mysqli_fetch_assoc($file_query);

if ($file_data) {
    // Hapus file fisik jika ada
    if (file_exists($file_data['file_path'])) {
        unlink($file_data['file_path']);
    }
    
    // Hapus dari database
    mysqli_query($conn, "DELETE FROM dokumentasi WHERE id = $file_id");
}

// Redirect kembali ke manage_files atau view_log
if (isset($_GET['from']) && $_GET['from'] == 'view') {
    header("Location: view_log.php?id=$log_id");
} else {
    header("Location: manage_files.php?log_id=$log_id&deleted=1");
}
exit();
?>