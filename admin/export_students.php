<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

require_once "../config/db.php";

if (isset($_GET['export']) && $_GET['export'] == 'csv') {

    $sql = "SELECT nisn, full_name, place_of_birth, date_of_birth, nisn_number, graduation_date, program_keahlian, konsentrasi_keahlian FROM students ORDER BY full_name ASC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("Query error: " . mysqli_error($conn));
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="data_siswa_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');

    // Tulis header, tambahkan "No." di depan, hapus photo_path
    fputcsv($output, ['No.', 'NISN', 'Full Name', 'Place of Birth', 'Date of Birth', 'NISN Number', 'Graduation Date', 'Program Keahlian', 'Konsentrasi Keahlian']);

    function format_date($date_str) {
        if (!$date_str) return '';
        $dt = DateTime::createFromFormat('Y-m-d', $date_str);
        if (!$dt) return $date_str;
        return $dt->format('d-m-Y');
    }

    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        // Format tanggal
        $row['date_of_birth'] = format_date($row['date_of_birth']);
        $row['graduation_date'] = format_date($row['graduation_date']);

        // Buat array tanpa photo_path dan tambah nomor urut di depan
        $data = [
            $no,
            $row['nisn'],
            $row['full_name'],
            $row['place_of_birth'],
            $row['date_of_birth'],
            $row['nisn_number'],
            $row['graduation_date'],
            $row['program_keahlian'],
            $row['konsentrasi_keahlian']
        ];

        fputcsv($output, $data);
        $no++;
    }

    fclose($output);
    mysqli_close($conn);
    exit;
} else {
    header("location: students.php");
    exit;
}