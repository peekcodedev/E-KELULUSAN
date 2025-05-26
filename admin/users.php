<?php
// admin/users.php

include_once 'includes/header.php'; // Memasukkan header admin

$success_message = '';
$error_message = '';

// Cek apakah pengguna yang login adalah super admin (jika ada role khusus)
// Untuk saat ini, kita asumsikan semua yang login adalah admin dan bisa mengelola user.
// Di aplikasi yang lebih kompleks, Anda akan menambahkan kolom 'role' di tabel users.
// if ($_SESSION['role'] !== 'superadmin') {
//     header("location: dashboard.php");
//     exit;
// }

// Variabel untuk mode edit/tambah
$is_edit = false;
$user_id_to_edit = null;
$user_data_edit = [
    'username' => '',
    'role' => 'admin' // Default role
];

// --- Handle Form Submission (Add/Edit User) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($username) || empty($role)) {
        $error_message = "Username dan Role tidak boleh kosong.";
    } else {
        if ($action == 'add') {
            if (empty($password)) {
                $error_message = "Password tidak boleh kosong untuk pengguna baru.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);
                    if (mysqli_stmt_execute($stmt)) {
                        $success_message = "Pengguna admin berhasil ditambahkan.";
                    } else {
                        if (mysqli_errno($conn) == 1062) { // Duplicate entry error code
                            $error_message = "Username sudah terdaftar. Mohon gunakan username lain.";
                        } else {
                            $error_message = "Gagal menambahkan pengguna admin. Mohon coba lagi.";
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        } elseif ($action == 'edit') {
            $user_id = $_POST['user_id'];
            $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            if (!empty($password)) { // Hanya update password jika diisi
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
            }

            if ($stmt = mysqli_prepare($conn, $sql)) {
                if (!empty($password)) {
                    mysqli_stmt_bind_param($stmt, "sssi", $username, $hashed_password, $role, $user_id);
                } else {
                    mysqli_stmt_bind_param($stmt, "ssi", $username, $role, $user_id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Data pengguna admin berhasil diperbarui.";
                } else {
                    if (mysqli_errno($conn) == 1062) { // Duplicate entry error code
                        $error_message = "Username sudah terdaftar. Mohon gunakan username lain.";
                    } else {
                        $error_message = "Gagal memperbarui pengguna admin. Mohon coba lagi.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- Handle Delete User ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Jangan izinkan admin menghapus akunnya sendiri
    if ($delete_id == $_SESSION['id']) {
        $error_message = "Anda tidak dapat menghapus akun Anda sendiri.";
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $delete_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Pengguna admin berhasil dihapus.";
            } else {
                $error_message = "Gagal menghapus pengguna admin. Mohon coba lagi.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// --- Handle Edit Request ---
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $user_id_to_edit = $_GET['edit_id'];
    $sql = "SELECT id, username, role FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id_to_edit);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $user_data_edit = mysqli_fetch_assoc($result);
            } else {
                $error_message = "Data pengguna tidak ditemukan untuk diedit.";
                $is_edit = false; // Kembali ke mode tambah
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Fetch All Users for Display ---
$users = [];
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

$sql_select = "SELECT id, username, role FROM users";
if (!empty($search_query)) {
    $sql_select .= " WHERE username LIKE ? OR role LIKE ?";
}
$sql_select .= " ORDER BY username ASC";

if ($stmt_select = mysqli_prepare($conn, $sql_select)) {
    if (!empty($search_query)) {
        $param_search = "%" . $search_query . "%";
        mysqli_stmt_bind_param($stmt_select, "ss", $param_search, $param_search);
    }
    if (mysqli_stmt_execute($stmt_select)) {
        $result_select = mysqli_stmt_get_result($stmt_select);
        while ($row = mysqli_fetch_assoc($result_select)) {
            $users[] = $row;
        }
    }
    mysqli_stmt_close($stmt_select);
}

?>

<div class="card p-6">
    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Kelola Pengguna Admin</h3>

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
        <h4 class="text-xl font-semibold text-gray-700 mb-4"><?php echo $is_edit ? 'Edit Pengguna Admin' : 'Tambah Pengguna Admin Baru'; ?></h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_edit); ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                    <input type="text" name="username" id="username" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($user_data_edit['username']); ?>" required>
                </div>
                <div>
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password <?php echo $is_edit ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?>:</label>
                    <input type="password" name="password" id="password" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" <?php echo $is_edit ? '' : 'required'; ?>>
                </div>
                <div>
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
                    <select name="role" id="role" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="admin" <?php echo ($user_data_edit['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                    <i class="fas fa-<?php echo $is_edit ? 'save' : 'plus'; ?> mr-2"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Tambah Pengguna'; ?>
                </button>
                <?php if ($is_edit): ?>
                    <a href="users.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i> Batal Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="mb-4">
        <h4 class="text-xl font-semibold text-gray-700 mb-4">Daftar Pengguna Admin</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mb-4 flex items-center space-x-2">
            <input type="text" name="search" placeholder="Cari Username atau Role" class="shadow appearance-none border rounded-lg py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 flex-grow" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-search mr-2"></i>Cari
            </button>
            <?php if (!empty($search_query)): ?>
                <a href="users.php" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                    <i class="fas fa-sync-alt mr-2"></i>Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Username</th>
                    <th class="py-3 px-6 text-left">Role</th>
                    <th class="py-3 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm font-light">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="users.php?edit_id=<?php echo $user['id']; ?>" class="w-8 h-8 rounded-full bg-yellow-200 text-yellow-600 flex items-center justify-center hover:bg-yellow-300 transition duration-200" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['id']): // Jangan tampilkan tombol hapus untuk akun sendiri ?>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="w-8 h-8 rounded-full bg-red-200 text-red-600 flex items-center justify-center hover:bg-red-300 transition duration-200" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="py-3 px-6 text-center text-gray-500">Tidak ada data pengguna admin.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="deleteConfirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full">
        <h4 class="text-xl font-bold text-gray-800 mb-4">Konfirmasi Hapus</h4>
        <p class="text-gray-700 mb-6">Apakah Anda yakin ingin menghapus pengguna <span id="userNameToDelete" class="font-semibold"></span>?</p>
        <div class="flex justify-end space-x-4">
            <button onclick="closeDeleteModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">Batal</button>
            <a id="confirmDeleteButton" href="#" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out">Hapus</a>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('userNameToDelete').innerText = name;
        document.getElementById('confirmDeleteButton').href = 'users.php?delete_id=' + id;
        document.getElementById('deleteConfirmationModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteConfirmationModal').classList.add('hidden');
    }
</script>

<?php
include_once 'includes/footer.php'; // Memasukkan footer admin
?>