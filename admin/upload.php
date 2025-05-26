<?php
// admin/upload.php

include_once 'includes/header.php'; // Memasukkan header admin

$success_message = '';
$error_message = '';

// Fungsi untuk mendapatkan path pengaturan dari database
function getSettingPath($conn, $setting_name) {
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
    return '';
}

// Fungsi untuk menyimpan atau memperbarui pengaturan di database
function saveSettingPath($conn, $setting_name, $setting_value) {
    $sql = "INSERT INTO settings (setting_name, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sss", $setting_name, $setting_value, $setting_value);
        return mysqli_stmt_execute($stmt);
    }
    return false;
}

// Proses upload file
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tentukan direktori target relatif terhadap root aplikasi
    // Karena upload.php ada di admin/, kita perlu naik satu level (../)
    // baru masuk ke folder uploads/
    $target_dir_absolute = realpath(__DIR__ . '/../uploads/');
    
    // Pastikan folder uploads ada
    if (!is_dir($target_dir_absolute)) {
        mkdir($target_dir_absolute, 0777, true); // Buat folder jika belum ada
    }

    // Upload Logo Sekolah
    if (isset($_FILES["school_logo"]) && $_FILES["school_logo"]["error"] == 0) {
        $file_name = basename($_FILES["school_logo"]["name"]);
        // Buat nama file unik untuk menghindari duplikasi
        $new_file_name = "logo_" . time() . "_" . $file_name;
        $target_file_absolute = $target_dir_absolute . DIRECTORY_SEPARATOR . $new_file_name;
        $imageFileType = strtolower(pathinfo($target_file_absolute, PATHINFO_EXTENSION));

        // Path yang akan disimpan di database (relatif dari root aplikasi)
        $relative_path_for_db = 'uploads/' . $new_file_name;

        // Cek apakah file gambar asli
        $check = getimagesize($_FILES["school_logo"]["tmp_name"]);
        if ($check !== false) {
            // Izinkan format file tertentu
            if (in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
                if (move_uploaded_file($_FILES["school_logo"]["tmp_name"], $target_file_absolute)) {
                    if (saveSettingPath($conn, 'school_logo_path', $relative_path_for_db)) {
                        $success_message .= "Logo sekolah berhasil diunggah. ";
                    } else {
                        // Jika gagal menyimpan ke DB, hapus file yang sudah terunggah
                        unlink($target_file_absolute);
                        $error_message .= "Gagal menyimpan path logo ke database. ";
                    }
                } else {
                    $error_message .= "Terjadi kesalahan saat mengunggah logo. ";
                }
            } else {
                $error_message .= "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan untuk logo. ";
            }
        } else {
            $error_message .= "File yang diunggah bukan gambar. ";
        }
    }

    // Upload Tanda Tangan Kepala Sekolah
    if (isset($_FILES["principal_signature"]) && $_FILES["principal_signature"]["error"] == 0) {
        $file_name = basename($_FILES["principal_signature"]["name"]);
        $new_file_name = "signature_" . time() . "_" . $file_name;
        $target_file_absolute = $target_dir_absolute . DIRECTORY_SEPARATOR . $new_file_name;
        $imageFileType = strtolower(pathinfo($target_file_absolute, PATHINFO_EXTENSION));

        // Path yang akan disimpan di database (relatif dari root aplikasi)
        $relative_path_for_db = 'uploads/' . $new_file_name;

        // Cek apakah file gambar asli
        $check = getimagesize($_FILES["principal_signature"]["tmp_name"]);
        if ($check !== false) {
            // Izinkan format file tertentu
            if (in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
                if (move_uploaded_file($_FILES["principal_signature"]["tmp_name"], $target_file_absolute)) {
                    if (saveSettingPath($conn, 'principal_signature_path', $relative_path_for_db)) {
                        $success_message .= "Tanda tangan kepala sekolah berhasil diunggah. ";
                    } else {
                        // Jika gagal menyimpan ke DB, hapus file yang sudah terunggah
                        unlink($target_file_absolute);
                        $error_message .= "Gagal menyimpan path tanda tangan ke database. ";
                    }
                } else {
                    $error_message .= "Terjadi kesalahan saat mengunggah tanda tangan. ";
                }
            } else {
                $error_message .= "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan untuk tanda tangan. ";
            }
        } else {
            $error_message .= "File yang diunggah bukan gambar. ";
        }
    }
}

// Ambil path file yang sudah ada untuk ditampilkan
// Karena path di DB sekarang relatif terhadap root aplikasi (misal: uploads/logo.png),
// dan kita ingin menampilkan pratinjau dari admin/upload.php,
// kita perlu menambahkan ../ di depannya.
$current_school_logo_relative_db_path = getSettingPath($conn, 'school_logo_path');
$current_principal_signature_relative_db_path = getSettingPath($conn, 'principal_signature_path');

$display_school_logo_path = !empty($current_school_logo_relative_db_path) ? '../' . $current_school_logo_relative_db_path : '';
$display_principal_signature_path = !empty($current_principal_signature_relative_db_path) ? '../' . $current_principal_signature_relative_db_path : '';


?>

<div class="card p-6">
    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Upload File Penting</h3>

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

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <div class="mb-6">
            <label for="school_logo" class="block text-gray-700 text-sm font-bold mb-2">Upload Logo Sekolah (PNG, JPG, GIF):</label>
            <input type="file" name="school_logo" id="school_logo" accept=".png,.jpg,.jpeg,.gif" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            <?php if (!empty($display_school_logo_path) && file_exists($display_school_logo_path)): ?>
                <p class="text-gray-600 text-sm mt-2">Logo saat ini:</p>
                <img src="<?php echo htmlspecialchars($display_school_logo_path); ?>" alt="Logo Sekolah" class="mt-2 h-20 w-auto object-contain rounded-md border border-gray-300 p-1">
            <?php endif; ?>
        </div>

        <div class="mb-6">
            <label for="principal_signature" class="block text-gray-700 text-sm font-bold mb-2">Upload Tanda Tangan Kepala Sekolah (PNG, JPG, GIF):</label>
            <input type="file" name="principal_signature" id="principal_signature" accept=".png,.jpg,.jpeg,.gif" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            <?php if (!empty($display_principal_signature_path) && file_exists($display_principal_signature_path)): ?>
                <p class="text-gray-600 text-sm mt-2">Tanda Tangan saat ini:</p>
                <img src="<?php echo htmlspecialchars($display_principal_signature_path); ?>" alt="Tanda Tangan Kepala Sekolah" class="mt-2 h-20 w-auto object-contain rounded-md border border-gray-300 p-1">
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-upload mr-2"></i>Upload File
            </button>
        </div>
    </form>
</div>

<?php
include_once 'includes/footer.php'; // Memasukkan footer admin
?>
