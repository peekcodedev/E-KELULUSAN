<?php
// config/db.php

// Konfigurasi koneksi database
define('DB_SERVER', 'localhost'); // Ganti dengan host database Anda jika berbeda
define('DB_USERNAME', 'root');     // Ganti dengan username database Anda
define('DB_PASSWORD', '');         // Ganti dengan password database Anda
define('DB_NAME', 'db_kelulusan_smk'); // Nama database yang telah kita buat

// Membuat koneksi ke database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Mengecek koneksi
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>