<?php
$host = "localhost";
$user = "root"; // default XAMPP
$pass = ""; // kosongkan jika belum diubah
$db   = "magang_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
