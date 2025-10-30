<?php
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Sisa kode delete_log.php yang sudah ada...

include 'db_connect.php';
$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT bukti_foto FROM internship_log WHERE id=$id");
$data = mysqli_fetch_assoc($result);
if ($data && $data['bukti_foto']) {
    @unlink("uploads/" . $data['bukti_foto']);
}

mysqli_query($conn, "DELETE FROM internship_log WHERE id=$id");
header("Location: index.php");
exit;
?>
