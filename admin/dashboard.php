<?php
// admin/dashboard.php

// Memasukkan header admin (akan melakukan cek session dan koneksi DB)
include_once 'includes/header.php';

// Ambil data statistik untuk dashboard
$total_students = 0;
$total_subjects = 0;
$total_users = 0;

// Query untuk total siswa
$sql_students_count = "SELECT COUNT(*) AS total FROM students";
$result_students_count = mysqli_query($conn, $sql_students_count);
if ($result_students_count) {
    $row = mysqli_fetch_assoc($result_students_count);
    $total_students = $row['total'];
}

// Query untuk total mata pelajaran
$sql_subjects_count = "SELECT COUNT(*) AS total FROM subjects";
$result_subjects_count = mysqli_query($conn, $sql_subjects_count);
if ($result_subjects_count) {
    $row = mysqli_fetch_assoc($result_subjects_count);
    $total_subjects = $row['total'];
}

// Query untuk total pengguna admin
$sql_users_count = "SELECT COUNT(*) AS total FROM users";
$result_users_count = mysqli_query($conn, $sql_users_count);
if ($result_users_count) {
    $row = mysqli_fetch_assoc($result_users_count);
    $total_users = $row['total'];
}

?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="card p-6 text-center">
        <div class="text-blue-500 mb-3">
            <i class="fas fa-users fa-4x"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Total Siswa</h3>
        <p class="text-5xl font-bold text-gray-900"><?php echo $total_students; ?></p>
    </div>

    <div class="card p-6 text-center">
        <div class="text-green-500 mb-3">
            <i class="fas fa-book fa-4x"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Total Mata Pelajaran</h3>
        <p class="text-5xl font-bold text-gray-900"><?php echo $total_subjects; ?></p>
    </div>

    <div class="card p-6 text-center">
        <div class="text-purple-500 mb-3">
            <i class="fas fa-user-shield fa-4x"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">Total Pengguna Admin</h3>
        <p class="text-5xl font-bold text-gray-900"><?php echo $total_users; ?></p>
    </div>
</div>

<?php
// Memasukkan footer admin
include_once 'includes/footer.php';
?>
