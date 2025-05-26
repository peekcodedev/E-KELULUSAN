<?php
// admin/profile.php

include_once 'includes/header.php'; // Memasukkan header admin

$success_message = '';
$error_message = '';

// Fungsi untuk mendapatkan pengaturan dari database
function getSetting($conn, $setting_name) {
    $sql = "SELECT setting_value FROM settings WHERE setting_name = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $setting_name);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $setting_value);
                mysqli_stmt_fetch($stmt);
                return $setting_value;
            }
        }
        mysqli_stmt_close($stmt);
    }
    return ''; // Nilai default jika tidak ditemukan
}

// Fungsi untuk menyimpan atau memperbarui pengaturan di database
function saveSetting($conn, $setting_name, $setting_value) {
    $sql = "INSERT INTO settings (setting_name, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sss", $setting_name, $setting_value, $setting_value);
        return mysqli_stmt_execute($stmt);
    }
    return false;
}

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $settings_to_save = [
        'school_name' => $_POST['school_name'] ?? '',
        'school_address' => $_POST['school_address'] ?? '',
        'school_phone' => $_POST['school_phone'] ?? '',
        'school_email' => $_POST['school_email'] ?? '',
        'school_npsn' => $_POST['school_npsn'] ?? '',
        'principal_name' => $_POST['principal_name'] ?? '',
        'principal_nip' => $_POST['principal_nip'] ?? '',
        'skl_number' => $_POST['skl_number'] ?? '',
        'announcement_date' => $_POST['announcement_date'] ?? '',
    ];

    $all_saved = true;
    foreach ($settings_to_save as $name => $value) {
        if (!saveSetting($conn, $name, $value)) {
            $all_saved = false;
            break;
        }
    }

    if ($all_saved) {
        $success_message = "Pengaturan berhasil diperbarui.";
    } else {
        $error_message = "Gagal memperbarui beberapa pengaturan. Mohon coba lagi.";
    }
}

// Ambil semua pengaturan untuk ditampilkan di form
$school_name = getSetting($conn, 'school_name');
$school_address = getSetting($conn, 'school_address');
$school_phone = getSetting($conn, 'school_phone');
$school_email = getSetting($conn, 'school_email');
$school_npsn = getSetting($conn, 'school_npsn');
$principal_name = getSetting($conn, 'principal_name');
$principal_nip = getSetting($conn, 'principal_nip');
$skl_number = getSetting($conn, 'skl_number');
$announcement_date = getSetting($conn, 'announcement_date');

// Format tanggal untuk input datetime-local
$announcement_date_formatted = '';
if (!empty($announcement_date)) {
    try {
        $dt = new DateTime($announcement_date);
        $announcement_date_formatted = $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        $error_message = "Format tanggal pengumuman tidak valid.";
    }
}

?>

<div class="card p-6">
    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Atur Profil & Pengaturan Sekolah</h3>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Berhasil!</strong>
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="school_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Sekolah:</label>
                <input type="text" name="school_name" id="school_name" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($school_name); ?>">
            </div>
            <div>
                <label for="school_address" class="block text-gray-700 text-sm font-bold mb-2">Alamat Sekolah:</label>
                <input type="text" name="school_address" id="school_address" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($school_address); ?>">
            </div>
            <div>
                <label for="school_phone" class="block text-gray-700 text-sm font-bold mb-2">Telepon Sekolah:</label>
                <input type="text" name="school_phone" id="school_phone" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($school_phone); ?>">
            </div>
            <div>
                <label for="school_email" class="block text-gray-700 text-sm font-bold mb-2">Email Sekolah:</label>
                <input type="email" name="school_email" id="school_email" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($school_email); ?>">
            </div>
            <div>
                <label for="school_npsn" class="block text-gray-700 text-sm font-bold mb-2">NPSN Sekolah:</label>
                <input type="text" name="school_npsn" id="school_npsn" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($school_npsn); ?>">
            </div>
            <div>
                <label for="principal_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Kepala Sekolah:</label>
                <input type="text" name="principal_name" id="principal_name" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($principal_name); ?>">
            </div>
            <div>
                <label for="principal_nip" class="block text-gray-700 text-sm font-bold mb-2">NIP Kepala Sekolah:</label>
                <input type="text" name="principal_nip" id="principal_nip" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($principal_nip); ?>">
            </div>
            <div>
                <label for="skl_number" class="block text-gray-700 text-sm font-bold mb-2">Nomor SKL:</label>
                <input type="text" name="skl_number" id="skl_number" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($skl_number); ?>">
            </div>
            <div class="md:col-span-2">
                <label for="announcement_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal & Waktu Pengumuman:</label>
                <input type="datetime-local" name="announcement_date" id="announcement_date" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($announcement_date_formatted); ?>">
                <p class="text-gray-500 text-xs mt-1">Format: YYYY-MM-DD HH:MM (misal: 2025-06-05 10:00)</p>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-save mr-2"></i>Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<?php
include_once 'includes/footer.php'; // Memasukkan footer admin
?>
