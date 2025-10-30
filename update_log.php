<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$id = $_POST['id'];
$tanggal = $_POST['tanggal'];
$status_hari = $_POST['status_hari'];
$waktu_mulai = $_POST['waktu_mulai'];
$waktu_selesai = $_POST['waktu_selesai'];
$deskripsi_tugas = $_POST['deskripsi_tugas'];
$keterangan = $_POST['keterangan'];
$old_photo = $_POST['old_photo'];
$bukti_foto = $old_photo;

// Jika user upload foto baru
if (!empty($_FILES['bukti_foto']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir);
    $target_file = $target_dir . time() . "_" . basename($_FILES["bukti_foto"]["name"]);

    if (move_uploaded_file($_FILES["bukti_foto"]["tmp_name"], $target_file)) {
        // Hapus foto lama jika ada
        if (!empty($old_photo) && file_exists($old_photo)) {
            unlink($old_photo);
        }
        $bukti_foto = $target_file;
    }
}

// Update log
$query = "UPDATE internship_log 
          SET tanggal='$tanggal', status_hari='$status_hari', waktu_mulai='$waktu_mulai',
              waktu_selesai='$waktu_selesai', deskripsi_tugas='$deskripsi_tugas',
              bukti_foto='$bukti_foto', keterangan='$keterangan'
          WHERE id=$id";

mysqli_query($conn, $query);

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
                                  VALUES ($id, '$file_name', '$target_file', '$file_ext')";
                    mysqli_query($conn, $insert_doc);
                }
            }
        }
    }
}

header("Location: index.php");
?>