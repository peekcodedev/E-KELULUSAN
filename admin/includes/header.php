<?php
// admin/includes/header.php

// Memulai session PHP
session_start();

// Cek jika user belum login, arahkan ke halaman login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

// Memasukkan file koneksi database
require_once "../config/db.php";

// Mengambil nama sekolah dari pengaturan
$school_name = 'SMK NURUL ISLAM'; // Default name
$sql_school_name = "SELECT setting_value FROM settings WHERE setting_name = 'school_name'";
$result_school_name = mysqli_query($conn, $sql_school_name);
if ($result_school_name && mysqli_num_rows($result_school_name) > 0) {
    $row = mysqli_fetch_assoc($result_school_name);
    $school_name = htmlspecialchars($row['setting_value']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo $school_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .sidebar {
            width: 250px;
            min-width: 250px;
        }
        .main-content {
            flex-grow: 1;
        }
        .card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="flex min-h-screen">
    <?php include 'sidebar.php'; ?>

    <div class="main-content flex flex-col">
        <nav class="bg-white p-4 shadow-md flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-gray-800">Dashboard Admin</h2>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Halo, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b></span>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </nav>
        <div class="p-6 flex-grow">
