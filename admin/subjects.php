<?php
// admin/subjects.php

include_once 'includes/header.php'; // Memasukkan header admin

$success_message = '';
$error_message = '';

// Variabel untuk mode edit/tambah
$is_edit = false;
$subject_id_to_edit = null;
$subject_data_edit = [
    'subject_name' => '',
    'category' => ''
];

// --- Handle Form Submission (Add/Edit Subject) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $subject_name = trim($_POST['subject_name']);
    $category = trim($_POST['category']);

    if (empty($subject_name) || empty($category)) {
        $error_message = "Nama Mata Pelajaran dan Kategori tidak boleh kosong.";
    } else {
        if ($action == 'add') {
            $sql = "INSERT INTO subjects (subject_name, category) VALUES (?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $subject_name, $category);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Mata pelajaran berhasil ditambahkan.";
                } else {
                    if (mysqli_errno($conn) == 1062) { // Duplicate entry error code
                        $error_message = "Mata pelajaran dengan nama tersebut sudah terdaftar.";
                    } else {
                        $error_message = "Gagal menambahkan mata pelajaran. Mohon coba lagi.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit') {
            $subject_id = $_POST['subject_id'];
            $sql = "UPDATE subjects SET subject_name = ?, category = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $subject_name, $category, $subject_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Mata pelajaran berhasil diperbarui.";
                } else {
                    if (mysqli_errno($conn) == 1062) { // Duplicate entry error code
                        $error_message = "Mata pelajaran dengan nama tersebut sudah terdaftar.";
                    } else {
                        $error_message = "Gagal memperbarui mata pelajaran. Mohon coba lagi.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- Handle Delete Subject ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM subjects WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Mata pelajaran berhasil dihapus.";
        } else {
            $error_message = "Gagal menghapus mata pelajaran. Mohon coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Handle Edit Request ---
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $subject_id_to_edit = $_GET['edit_id'];
    $sql = "SELECT * FROM subjects WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $subject_id_to_edit);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $subject_data_edit = mysqli_fetch_assoc($result);
            } else {
                $error_message = "Data mata pelajaran tidak ditemukan untuk diedit.";
                $is_edit = false; // Kembali ke mode tambah
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Fetch All Subjects for Display ---
$subjects = [];
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

$sql_select = "SELECT * FROM subjects";
if (!empty($search_query)) {
    $sql_select .= " WHERE subject_name LIKE ? OR category LIKE ?";
}
$sql_select .= " ORDER BY category, subject_name ASC";

if ($stmt_select = mysqli_prepare($conn, $sql_select)) {
    if (!empty($search_query)) {
        $param_search = "%" . $search_query . "%";
        mysqli_stmt_bind_param($stmt_select, "ss", $param_search, $param_search);
    }
    if (mysqli_stmt_execute($stmt_select)) {
        $result_select = mysqli_stmt_get_result($stmt_select);
        while ($row = mysqli_fetch_assoc($result_select)) {
            $subjects[] = $row;
        }
    }
    mysqli_stmt_close($stmt_select);
}

?>

<div class="card p-6">
    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Kelola Mata Pelajaran</h3>

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
        <h4 class="text-xl font-semibold text-gray-700 mb-4"><?php echo $is_edit ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran Baru'; ?></h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id_to_edit); ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="subject_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Mata Pelajaran:</label>
                    <input type="text" name="subject_name" id="subject_name" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($subject_data_edit['subject_name']); ?>" required>
                </div>
                <div>
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Kategori:</label>
                    <select name="category" id="category" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Umum" <?php echo ($subject_data_edit['category'] == 'Umum') ? 'selected' : ''; ?>>Umum</option>
                        <option value="Kejuruan" <?php echo ($subject_data_edit['category'] == 'Kejuruan') ? 'selected' : ''; ?>>Kejuruan</option>
                        <option value="Muatan Lokal" <?php echo ($subject_data_edit['category'] == 'Muatan Lokal') ? 'selected' : ''; ?>>Muatan Lokal</option>
                        <option value="Pilihan" <?php echo ($subject_data_edit['category'] == 'Pilihan') ? 'selected' : ''; ?>>Pilihan</option>
                        </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                    <i class="fas fa-<?php echo $is_edit ? 'save' : 'plus'; ?> mr-2"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Tambah Mata Pelajaran'; ?>
                </button>
                <?php if ($is_edit): ?>
                    <a href="subjects.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i> Batal Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="mb-4">
        <h4 class="text-xl font-semibold text-gray-700 mb-4">Daftar Mata Pelajaran</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mb-4 flex items-center space-x-2">
            <input type="text" name="search" placeholder="Cari Nama atau Kategori" class="shadow appearance-none border rounded-lg py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 flex-grow" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-search mr-2"></i>Cari
            </button>
            <?php if (!empty($search_query)): ?>
                <a href="subjects.php" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-sync-alt mr-2"></i>Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Nama Mata Pelajaran</th>
                    <th class="py-3 px-6 text-left">Kategori</th>
                    <th class="py-3 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm font-light">
                <?php if (count($subjects) > 0): ?>
                    <?php foreach ($subjects as $subject): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($subject['category']); ?></td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="subjects.php?edit_id=<?php echo $subject['id']; ?>" class="w-8 h-8 rounded-full bg-yellow-200 text-yellow-600 flex items-center justify-center hover:bg-yellow-300 transition duration-200" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>')" class="w-8 h-8 rounded-full bg-red-200 text-red-600 flex items-center justify-center hover:bg-red-300 transition duration-200" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="py-3 px-6 text-center text-gray-500">Tidak ada data mata pelajaran.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="deleteConfirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full">
        <h4 class="text-xl font-bold text-gray-800 mb-4">Konfirmasi Hapus</h4>
        <p class="text-gray-700 mb-6">Apakah Anda yakin ingin menghapus mata pelajaran <span id="subjectNameToDelete" class="font-semibold"></span>?</p>
        <div class="flex justify-end space-x-4">
            <button onclick="closeDeleteModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">Batal</button>
            <a id="confirmDeleteButton" href="#" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">Hapus</a>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('subjectNameToDelete').innerText = name;
        document.getElementById('confirmDeleteButton').href = 'subjects.php?delete_id=' + id;
        document.getElementById('deleteConfirmationModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteConfirmationModal').classList.add('hidden');
    }
</script>

<?php
include_once 'includes/footer.php'; // Memasukkan footer admin
?>