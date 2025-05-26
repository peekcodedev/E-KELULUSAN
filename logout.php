<?php
// logout.php

// Memulai session
session_start();

// Menghapus semua variabel session
$_SESSION = array();

// Menghancurkan session
session_destroy();

// Mengarahkan ke halaman login
header("location: login.php");
exit;
?>
