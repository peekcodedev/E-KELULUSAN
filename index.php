<?php
// index.php

// Memulai session PHP
session_start();

// Memasukkan file koneksi database
require_once "config/db.php";

// Mengambil pengaturan dari database
$announcement_datetime = '';
$school_name = 'SMK NURUL ISLAM'; // Default name
$school_logo_path = ''; // Default logo path

$sql_settings = "SELECT setting_name, setting_value FROM settings WHERE setting_name IN ('announcement_date', 'school_name', 'school_logo_path')";
$result_settings = mysqli_query($conn, $sql_settings);

if ($result_settings) {
    while ($row = mysqli_fetch_assoc($result_settings)) {
        if ($row['setting_name'] == 'announcement_date') {
            $announcement_datetime = $row['setting_value'];
        } elseif ($row['setting_name'] == 'school_name') {
            $school_name = $row['setting_value'];
        } elseif ($row['setting_name'] == 'school_logo_path') {
            $school_logo_path = $row['setting_value'];
        }
    }
}

$nisn = $status = $student_data = null;
$error_message = '';
$show_result = false;
$show_print_button = false;

// Proses form jika NISN disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nisn'])) {
    $nisn = trim($_POST['nisn']);

    if (empty($nisn)) {
        $error_message = "NISN tidak boleh kosong.";
    } else {
        // Cek apakah tanggal pengumuman sudah lewat
        $current_time = new DateTime();
        $announcement_time_obj = new DateTime($announcement_datetime);

        if ($current_time < $announcement_time_obj) {
            $error_message = "Pengumuman kelulusan belum dibuka. Mohon tunggu hingga waktu yang ditentukan.";
        } else {
            // Query untuk mencari siswa berdasarkan NISN
            $sql_student = "SELECT * FROM students WHERE nisn = ?";
            if ($stmt = mysqli_prepare($conn, $sql_student)) {
                mysqli_stmt_bind_param($stmt, "s", $param_nisn);
                $param_nisn = $nisn;

                if (mysqli_stmt_execute($stmt)) {
                    $result_student = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result_student) == 1) {
                        $student_data = mysqli_fetch_assoc($result_student);
                        // Untuk contoh ini, kita asumsikan semua siswa yang ditemukan LULUS.
                        // Di masa depan, Anda bisa menambahkan kolom status kelulusan di tabel students.
                        $status = "LULUS";
                        $show_result = true;
                        $show_print_button = true; // Tampilkan tombol cetak jika siswa ditemukan
                    } else {
                        $error_message = "NISN tidak ditemukan. Mohon periksa kembali.";
                    }
                } else {
                    $error_message = "Terjadi kesalahan saat mencari data siswa. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman Kelulusan - <?php echo htmlspecialchars($school_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .countdown-timer {
            font-size: 2.5rem;
            font-weight: 700;
            color: #3b82f6; /* blue-500 */
        }
        @media (max-width: 640px) {
            .countdown-timer {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">
                <?php echo htmlspecialchars($school_name); ?>
            </h1>
            <nav>
                <a href="login.php" class="bg-blue-700 hover:bg-blue-800 text-white py-2 px-4 rounded-lg transition duration-300 ease-in-out">Login Admin</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow container mx-auto p-4 flex items-center justify-center">
        <div class="w-full max-w-2xl card p-8">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Pengumuman Kelulusan</h2>
            <p class="text-center text-gray-600 mb-6">Masukkan NISN Anda untuk melihat status kelulusan.</p>

            <?php if (!empty($announcement_datetime)): ?>
            <div class="text-center mb-6">
                <p class="text-gray-700 text-lg mb-2">Pengumuman akan dibuka pada:</p>
                <p id="announcement-date" class="text-xl font-semibold text-blue-600"><?php echo date('d F Y H:i:s', strtotime($announcement_datetime)); ?> WIB</p>
                <div id="countdown" class="countdown-timer text-blue-600 mt-4"></div>
            </div>
            <?php endif; ?>

            <?php
            if (!empty($error_message)) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">' . $error_message . '</span>
                      </div>';
            }
            ?>

            <form id="nisnForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-6">
                <div class="mb-4">
                    <label for="nisn" class="block text-gray-700 text-sm font-bold mb-2">NISN:</label>
                    <input type="text" name="nisn" id="nisn" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan NISN Anda" value="<?php echo htmlspecialchars($nisn ?? ''); ?>" required>
                </div>
                <div class="flex items-center justify-center">
                    <button type="submit" id="checkButton" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out w-full disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Cek Kelulusan
                    </button>
                </div>
            </form>

            <?php if ($show_result && $student_data): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-800 p-4 rounded-lg" role="alert">
                    <p class="font-bold text-lg mb-2">Hasil Kelulusan:</p>
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($student_data['full_name']); ?></p>
                    <p><strong>NISN:</strong> <?php echo htmlspecialchars($student_data['nisn']); ?></p>
                    <p><strong>Status:</strong> <span class="font-bold text-green-700 text-xl"><?php echo htmlspecialchars($status); ?></span></p>
                    <?php if ($show_print_button): ?>
                        <div class="mt-4 text-center">
                            <a href="generate_skl.php?nisn=<?php echo htmlspecialchars($student_data['nisn']); ?>" target="_blank" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                                Cetak SKL
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 text-center shadow-inner mt-8">
        <div class="container mx-auto">
            <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($school_name); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // JavaScript untuk Countdown Timer
        const announcementDateTime = "<?php echo $announcement_datetime; ?>";
        const countdownElement = document.getElementById("countdown");
        const checkButton = document.getElementById("checkButton");
        const nisnInput = document.getElementById("nisn");
        let countdownInterval;

        function updateCountdown() {
            const now = new Date().getTime();
            const announcementDate = new Date(announcementDateTime).getTime();
            const distance = announcementDate - now;

            if (distance < 0) {
                clearInterval(countdownInterval);
                countdownElement.innerHTML = "Pengumuman Dibuka!";
                checkButton.disabled = false;
                nisnInput.disabled = false;
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                checkButton.disabled = true;
                nisnInput.disabled = true;
            }
        }

        if (announcementDateTime) {
            updateCountdown(); // Panggil pertama kali untuk menghindari jeda
            countdownInterval = setInterval(updateCountdown, 1000);
        } else {
            // Jika tanggal pengumuman belum diatur, langsung aktifkan form
            countdownElement.innerHTML = "Pengumuman Dibuka!";
            checkButton.disabled = false;
            nisnInput.disabled = false;
        }

        // Pastikan tombol aktif jika pengumuman sudah dibuka saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date().getTime();
            const announcementDate = new Date(announcementDateTime).getTime();
            if (announcementDate <= now) {
                checkButton.disabled = false;
                nisnInput.disabled = false;
            }
        });
    </script>
</body>
</html>