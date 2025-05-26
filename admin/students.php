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
    'nisn_number' => '', // Assuming this might be different from 'nisn'
    'graduation_date' => '',
    'program_keahlian' => '',
    'konsentrasi_keahlian' => ''
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

    if (empty($nisn) || empty($full_name) || empty($graduation_date)) {
        $error_message = "NISN, Nama Lengkap, dan Tanggal Kelulusan tidak boleh kosong.";
    } else {
        if ($action == 'add') {
            $sql = "INSERT INTO students (nisn, full_name, place_of_birth, date_of_birth, nisn_number, graduation_date, program_keahlian, konsentrasi_keahlian) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssss", $nisn, $full_name, $place_of_birth, $date_of_birth, $nisn_number, $graduation_date, $program_keahlian, $konsentrasi_keahlian);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Data siswa berhasil ditambahkan.";
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
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
            $sql = "UPDATE students SET nisn = ?, full_name = ?, place_of_birth = ?, date_of_birth = ?, nisn_number = ?, graduation_date = ?, program_keahlian = ?, konsentrasi_keahlian = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssssssi", $nisn, $full_name, $place_of_birth, $date_of_birth, $nisn_number, $graduation_date, $program_keahlian, $konsentrasi_keahlian, $student_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Data siswa berhasil diperbarui.";
                } else {
                    $error_message = "Error: " . mysqli_error($conn);
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
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id_to_edit); ?>">
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
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')" class="w-8 h-8 rounded-full bg-red-200 text-red-600 flex items-center justify-center hover:bg-red-300 transition duration-200" title="Hapus">
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
