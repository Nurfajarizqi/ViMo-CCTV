<?php
$host = "localhost"; 
$user = "capstone";      // default user XAMPP
$password = "capstone";      // default password kosong di XAMPP
$database = "capstone"; // ganti dengan nama database kamu

// Membuat koneksi
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
/*
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
} else {
    echo "Koneksi berhasil!";
}
*/
?>