<?php
// admin/students.php

include_once 'includes/header.php'; // Memasukkan header admin

$success_message = '';
$error_message = '';

// Variabel untuk mode edit/tambah
$is_edit = false;
$student_id_to_edit = null;
$student_data_edit = [
    'nisn' => '',
    'full_name' => '',
    'place_of_birth' => '',
    'date_of_birth' => '',
    'nisn_number' => '',
    'graduation_date' => '',
    'program_keahlian' => '',
    'konsentrasi_keahlian' => '',
    'photo_path' => '' // Tambahkan photo_path
];

// --- Handle Form Submission (Add/Edit Student) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nisn = trim($_POST['nisn']);
    $full_name = trim($_POST['full_name']);
    $place_of_birth = trim($_POST['place_of_birth']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $nisn_number = trim($_POST['nisn_number']);
    $graduation_date = trim($_POST['graduation_date']);
    $program_keahlian = trim($_POST['program_keahlian']);
    $konsentrasi_keahlian = trim($_POST['konsentrasi_keahlian']);

    $current_photo_path = $_POST['current_photo_path'] ?? ''; // Untuk menyimpan path foto lama saat edit

    // Initialize photo_path_for_db with current path in case no new photo is uploaded
    $photo_path_for_db = $current_photo_path;

    // Process upload foto siswa jika ada
    if (isset($_FILES["student_photo"]) && $_FILES["student_photo"]["error"] == 0) {
        // Define the relative upload directory from the project root
        $relative_upload_dir = 'uploads' . DIRECTORY_SEPARATOR . 'students' . DIRECTORY_SEPARATOR;
        $absolute_upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relative_upload_dir;

        // Create the directory if it doesn't exist
        if (!is_dir($absolute_upload_dir)) {
            if (!mkdir($absolute_upload_dir, 0777, true)) { // 0777 for development ease, consider 0755 for production
                $error_message .= "Gagal membuat direktori upload: " . $absolute_upload_dir . ". Mohon periksa izin folder. ";
                goto skip_photo_upload;
            }
        }

        $file_name = basename($_FILES["student_photo"]["name"]);
        // Sanitize filename to prevent issues with special characters in path
        $file_name_sanitized = preg_replace("/[^a-zA-Z0-9_\-.]/", "", $file_name);
        $new_file_name = "student_photo_" . time() . "_" . $nisn . "_" . $file_name_sanitized;
        $target_file_absolute = $absolute_upload_dir . $new_file_name;

        $imageFileType = strtolower(pathinfo($target_file_absolute, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["student_photo"]["tmp_name"]);
        if ($check !== false) {
            if (in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
                if (move_uploaded_file($_FILES["student_photo"]["tmp_name"], $target_file_absolute)) {
                    $photo_path_for_db = $relative_upload_dir . $new_file_name;

                    // Hapus foto lama jika ada dan berbeda dengan yang baru
                    if (!empty($current_photo_path) && $current_photo_path != $photo_path_for_db) {
                        $old_photo_absolute_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $current_photo_path;
                        if (file_exists($old_photo_absolute_path)) {
                            unlink($old_photo_absolute_path);
                        }
                    }
                } else {
                    $error_message .= "Terjadi kesalahan saat mengunggah foto siswa. File tidak dapat dipindahkan. Mohon periksa izin folder 'uploads/students/'. ";
                }
            } else {
                $error_message .= "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan untuk foto siswa. ";
            }
        } else {
            $error_message = "File yang diunggah bukan gambar untuk foto siswa. ";
        }
    }
    skip_photo_upload: 
    
    // Continue with database insert/update
    if (empty($nisn) || empty($full_name) || empty($graduation_date)) {
        $error_message = "NISN, Nama Lengkap, dan Tanggal Kelulusan tidak boleh kosong.";
    } else {
        if ($action == 'add') {
            $sql = "INSERT INTO students (nisn, full_name, place_of_birth, date_of_birth, nisn_number, graduation_date, program_keahlian, konsentrasi_keahlian, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssss", $nisn, $full_name, $place_of_birth, $date_of_birth, $nisn_number, $graduation_date, $program_keahlian, $konsentrasi_keahlian, $photo_path_for_db);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Data siswa berhasil ditambahkan.";
                } else {
                    if (mysqli_errno($conn) == 1062) { // Duplicate entry error code
                        $error_message = "NISN sudah terdaftar. Mohon gunakan NISN lain.";
                    } else {
                        $error_message = "Gagal menambahkan data siswa. Mohon coba lagi.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit') {
            $student_id = $_POST['student_id'];
            $sql = "UPDATE students SET nisn = ?, full_name = ?, place_of_birth = ?, date_of_birth = ?, nisn_number = ?, graduation_date = ?, program_keahlian = ?, konsentrasi_keahlian = ?, photo_path = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssssi", $nisn, $full_name, $place_of_birth, $date_of_birth, $nisn_number, $graduation_date, $program_keahlian, $konsentrasi_keahlian, $photo_path_for_db, $student_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Data siswa berhasil diperbarui.";
                } else {
                     if (mysqli_errno($conn) == 1062) { // Duplicate entry error code
                        $error_message = "NISN sudah terdaftar. Mohon gunakan NISN lain.";
                    } else {
                        $error_message = "Gagal memperbarui data siswa. Mohon coba lagi.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- Handle Delete Student ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Ambil photo_path sebelum menghapus siswa
    $sql_get_photo = "SELECT photo_path FROM students WHERE id = ?";
    if ($stmt_get_photo = mysqli_prepare($conn, $sql_get_photo)) {
        mysqli_stmt_bind_param($stmt_get_photo, "i", $delete_id);
        mysqli_stmt_execute($stmt_get_photo);
        mysqli_stmt_store_result($stmt_get_photo);
        if (mysqli_stmt_num_rows($stmt_get_photo) == 1) {
            mysqli_stmt_bind_result($stmt_get_photo, $photo_to_delete);
            mysqli_stmt_fetch($stmt_get_photo);
            // Construct absolute path for deletion
            $absolute_photo_path_to_delete = dirname(__DIR__) . DIRECTORY_SEPARATOR . $photo_to_delete;
            if (!empty($photo_to_delete) && file_exists($absolute_photo_path_to_delete)) {
                unlink($absolute_photo_path_to_delete); // Hapus file foto fisik
            }
        }
        mysqli_stmt_close($stmt_get_photo);
    }

    $sql = "DELETE FROM students WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Data siswa berhasil dihapus.";
        } else {
            $error_message = "Gagal menghapus data siswa. Mohon coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Handle Edit Request ---
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $student_id_to_edit = $_GET['edit_id'];
    $sql = "SELECT * FROM students WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id_to_edit);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $student_data_edit = mysqli_fetch_assoc($result);
                // Format tanggal untuk input date
                $student_data_edit['date_of_birth'] = $student_data_edit['date_of_birth'] ? date('Y-m-d', strtotime($student_data_edit['date_of_birth'])) : '';
                $student_data_edit['graduation_date'] = $student_data_edit['graduation_date'] ? date('Y-m-d', strtotime($student_data_edit['graduation_date'])) : '';
            } else {
                $error_message = "Data siswa tidak ditemukan untuk diedit.";
                $is_edit = false; // Kembali ke mode tambah
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Handle Import CSV ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['import_action']) && $_POST['import_action'] == 'import_csv') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file_tmp_path = $_FILES['csv_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($file_ext == 'csv') {
            $handle = fopen($file_tmp_path, "r");
            if ($handle !== FALSE) {
                // Skip header row
                fgetcsv($handle); 

                $imported_count = 0;
                $skipped_count = 0;
                $updated_count = 0;
                $row_num = 1; // Start from 1 for header, actual data from 2

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $row_num++;
                    // Assuming CSV columns order: nisn, full_name, place_of_birth, date_of_birth, nisn_number, graduation_date, program_keahlian, konsentrasi_keahlian, photo_filename
                    if (count($data) >= 8) { // Minimum required fields
                        $import_nisn = trim($data[0]);
                        $import_full_name = trim($data[1]);
                        $import_place_of_birth = trim($data[2]);
                        $import_date_of_birth = trim($data[3]);
                        $import_nisn_number = trim($data[4]);
                        $import_graduation_date = trim($data[5]);
                        $import_program_keahlian = trim($data[6]);
                        $import_konsentrasi_keahlian = trim($data[7]);
                        $import_photo_filename = isset($data[8]) ? trim($data[8]) : ''; // Optional photo filename

                        $import_photo_path = !empty($import_photo_filename) ? 'uploads/students/' . $import_photo_filename : NULL;

                        // Check if student with NISN already exists
                        $sql_check_nisn = "SELECT id FROM students WHERE nisn = ?";
                        if ($stmt_check = mysqli_prepare($conn, $sql_check_nisn)) {
                            mysqli_stmt_bind_param($stmt_check, "s", $import_nisn);
                            mysqli_stmt_execute($stmt_check);
                            mysqli_stmt_store_result($stmt_check);

                            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                                // Student exists, update data
                                $sql_update = "UPDATE students SET full_name=?, place_of_birth=?, date_of_birth=?, nisn_number=?, graduation_date=?, program_keahlian=?, konsentrasi_keahlian=?, photo_path=? WHERE nisn=?";
                                if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
                                    mysqli_stmt_bind_param($stmt_update, "sssssssss", $import_full_name, $import_place_of_birth, $import_date_of_birth, $import_nisn_number, $import_graduation_date, $import_program_keahlian, $import_konsentrasi_keahlian, $import_photo_path, $import_nisn);
                                    if (mysqli_stmt_execute($stmt_update)) {
                                        $updated_count++;
                                    } else {
                                        $error_message .= "Gagal memperbarui data siswa NISN " . htmlspecialchars($import_nisn) . " (Baris " . $row_num . "). ";
                                    }
                                    mysqli_stmt_close($stmt_update);
                                }
                            } else {
                                // Student does not exist, insert new data
                                $sql_insert = "INSERT INTO students (nisn, full_name, place_of_birth, date_of_birth, nisn_number, graduation_date, program_keahlian, konsentrasi_keahlian, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
                                    mysqli_stmt_bind_param($stmt_insert, "sssssssss", $import_nisn, $import_full_name, $import_place_of_birth, $import_date_of_birth, $import_nisn_number, $import_graduation_date, $import_program_keahlian, $import_konsentrasi_keahlian, $import_photo_path);
                                    if (mysqli_stmt_execute($stmt_insert)) {
                                        $imported_count++;
                                    } else {
                                        $error_message .= "Gagal menambahkan data siswa NISN " . htmlspecialchars($import_nisn) . " (Baris " . $row_num . "). ";
                                    }
                                    mysqli_stmt_close($stmt_insert);
                                }
                            }
                            mysqli_stmt_close($stmt_check);
                        }
                    } else {
                        $skipped_count++;
                        $error_message .= "Baris " . $row_num . " dilewati karena format tidak lengkap. ";
                    }
                }
                fclose($handle);
                $success_message = "Impor selesai. Ditambahkan: " . $imported_count . ", Diperbarui: " . $updated_count . ", Dilewati: " . $skipped_count . ".";
            } else {
                $error_message = "Gagal membuka file CSV.";
            }
        } else {
            $error_message = "Mohon unggah file CSV yang valid.";
        }
    } else {
        $error_message = "Mohon pilih file CSV untuk diimpor.";
    }
}

// --- Handle Export CSV (Moved to a separate file: admin/export_students.php) ---
// This block is removed from here.

// --- Fetch All Students for Display ---
$students = [];
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

$sql_select = "SELECT * FROM students";
if (!empty($search_query)) {
    $sql_select .= " WHERE nisn LIKE ? OR full_name LIKE ?";
}
$sql_select .= " ORDER BY full_name ASC";

if ($stmt_select = mysqli_prepare($conn, $sql_select)) {
    if (!empty($search_query)) {
        $param_search = "%" . $search_query . "%";
        mysqli_stmt_bind_param($stmt_select, "ss", $param_search, $param_search);
    }
    if (mysqli_stmt_execute($stmt_select)) {
        $result_select = mysqli_stmt_get_result($stmt_select);
        while ($row = mysqli_fetch_assoc($result_select)) {
            $students[] = $row;
        }
    }
    mysqli_stmt_close($stmt_select);
}

?>

<div class="card p-6">
    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Kelola Data Siswa</h3>

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

    <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
        <h4 class="text-xl font-semibold text-gray-700 mb-4"><?php echo $is_edit ? 'Edit Data Siswa' : 'Tambah Siswa Baru'; ?></h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id_to_edit); ?>">
                <input type="hidden" name="current_photo_path" value="<?php echo htmlspecialchars($student_data_edit['photo_path']); ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="nisn" class="block text-gray-700 text-sm font-bold mb-2">NISN:</label>
                    <input type="text" name="nisn" id="nisn" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['nisn']); ?>" required>
                </div>
                <div>
                    <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
                    <input type="text" name="full_name" id="full_name" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['full_name']); ?>" required>
                </div>
                <div>
                    <label for="place_of_birth" class="block text-gray-700 text-sm font-bold mb-2">Tempat Lahir:</label>
                    <input type="text" name="place_of_birth" id="place_of_birth" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['place_of_birth']); ?>">
                </div>
                <div>
                    <label for="date_of_birth" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Lahir:</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['date_of_birth']); ?>">
                </div>
                <div>
                    <label for="nisn_number" class="block text-gray-700 text-sm font-bold mb-2">Nomor Induk Siswa Nasional (jika berbeda):</label>
                    <input type="text" name="nisn_number" id="nisn_number" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['nisn_number']); ?>">
                </div>
                <div>
                    <label for="graduation_date" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Kelulusan:</label>
                    <input type="date" name="graduation_date" id="graduation_date" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['graduation_date']); ?>" required>
                </div>
                <div>
                    <label for="program_keahlian" class="block text-gray-700 text-sm font-bold mb-2">Program Keahlian:</label>
                    <input type="text" name="program_keahlian" id="program_keahlian" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['program_keahlian']); ?>">
                </div>
                <div>
                    <label for="konsentrasi_keahlian" class="block text-gray-700 text-sm font-bold mb-2">Konsentrasi Keahlian:</label>
                    <input type="text" name="konsentrasi_keahlian" id="konsentrasi_keahlian" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($student_data_edit['konsentrasi_keahlian']); ?>">
                </div>
                <div class="md:col-span-2">
                    <label for="student_photo" class="block text-gray-700 text-sm font-bold mb-2">Upload Foto Siswa (PNG, JPG, GIF) <?php echo $is_edit ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?>:</label>
                    <input type="file" name="student_photo" id="student_photo" accept=".png,.jpg,.jpeg,.gif" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php if ($is_edit && !empty($student_data_edit['photo_path']) && file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . $student_data_edit['photo_path'])): ?>
                        <p class="text-gray-600 text-sm mt-2">Foto saat ini:</p>
                        <img src="<?php echo htmlspecialchars('../' . $student_data_edit['photo_path']); ?>" alt="Foto Siswa" class="mt-2 h-24 w-auto object-contain rounded-md border border-gray-300 p-1">
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                    <i class="fas fa-<?php echo $is_edit ? 'save' : 'plus'; ?> mr-2"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Tambah Siswa'; ?>
                </button>
                <?php if ($is_edit): ?>
                    <a href="students.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i> Batal Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
        <h4 class="text-xl font-semibold text-gray-700 mb-4">Impor / Ekspor Data Siswa</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h5 class="text-lg font-semibold text-gray-600 mb-2">Impor dari CSV</h5>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="import_action" value="import_csv">
                    <div class="mb-4">
                        <label for="csv_file" class="block text-gray-700 text-sm font-bold mb-2">Pilih file CSV:</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <p class="text-gray-500 text-xs mt-1">Format CSV: `nisn, full_name, place_of_birth, date_of_birth, nisn_number, graduation_date, program_keahlian, konsentrasi_keahlian, photo_filename` (opsional)</p>
                        <p class="text-red-500 text-xs mt-1">**Penting:** File foto harus disalin manual ke folder `uploads/students/` dengan nama yang sesuai di CSV.</p>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                        <i class="fas fa-file-import mr-2"></i>Impor CSV
                    </button>
                </form>
            </div>

            <div>
                <h5 class="text-lg font-semibold text-gray-600 mb-2">Ekspor ke CSV</h5>
                <p class="text-gray-700 mb-4">Ekspor semua data siswa ke file CSV.</p>
                <a href="export_students.php?export=csv" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                    <i class="fas fa-file-export mr-2"></i>Ekspor CSV
                </a>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <h4 class="text-xl font-semibold text-gray-700 mb-4">Daftar Siswa</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mb-4 flex items-center space-x-2">
            <input type="text" name="search" placeholder="Cari NISN atau Nama" class="shadow appearance-none border rounded-lg py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 flex-grow" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-search mr-2"></i>Cari
            </button>
            <?php if (!empty($search_query)): ?>
                <a href="students.php" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-sync-alt mr-2"></i>Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">NISN</th>
                    <th class="py-3 px-6 text-left">Nama Lengkap</th>
                    <th class="py-3 px-6 text-left">Program Keahlian</th>
                    <th class="py-3 px-6 text-center">Tanggal Kelulusan</th>
                    <th class="py-3 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm font-light">
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($student['nisn']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($student['program_keahlian']); ?></td>
                            <td class="py-3 px-6 text-center"><?php echo date('d M Y', strtotime($student['graduation_date'])); ?></td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="manage_grades.php?student_id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-blue-200 text-blue-600 flex items-center justify-center hover:bg-blue-300 transition duration-200" title="Kelola Nilai">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    <a href="students.php?edit_id=<?php echo $student['id']; ?>" class="w-8 h-8 rounded-full bg-yellow-200 text-yellow-600 flex items-center justify-center hover:bg-yellow-300 transition duration-200" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')" class="w-8 h-8 rounded-full bg-red-200 text-red-600 flex items-center justify-center hover:bg-red-300 transition duration-300 ease-in-out" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-3 px-6 text-center text-gray-500">Tidak ada data siswa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="deleteConfirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full">
        <h4 class="text-xl font-bold text-gray-800 mb-4">Konfirmasi Hapus</h4>
        <p class="text-gray-700 mb-6">Apakah Anda yakin ingin menghapus data siswa <span id="studentNameToDelete" class="font-semibold"></span>?</p>
        <div class="flex justify-end space-x-4">
            <button onclick="closeDeleteModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">Batal</button>
            <a id="confirmDeleteButton" href="#" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">Hapus</a>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('studentNameToDelete').innerText = name;
        document.getElementById('confirmDeleteButton').href = 'students.php?delete_id=' + id;
        document.getElementById('deleteConfirmationModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteConfirmationModal').classList.add('hidden');
    }
</script>

<?php
include_once 'includes/footer.php'; // Memasukkan footer admin
?>
