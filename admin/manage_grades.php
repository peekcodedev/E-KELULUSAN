<?php
// admin/manage_grades.php

include_once 'includes/header.php'; // Memasukkan header admin

$success_message = '';
$error_message = '';

// Cek apakah student_id diberikan
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    header("location: students.php"); // Redirect jika tidak ada student_id
    exit;
}

$student_id = $_GET['student_id'];

// Ambil data siswa
$student_name = '';
$sql_student = "SELECT full_name FROM students WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql_student)) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $student_name);
            mysqli_stmt_fetch($stmt);
        } else {
            $error_message = "Siswa tidak ditemukan.";
        }
    }
    mysqli_stmt_close($stmt);
}

// --- Handle Form Submission (Add/Update Grades) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_grades') {
    if (isset($_POST['grades']) && is_array($_POST['grades'])) {
        $all_saved = true;
        foreach ($_POST['grades'] as $subject_id => $grade_value) {
            $grade_value = (float)$grade_value;

            // Cek apakah nilai sudah ada untuk siswa dan mata pelajaran ini
            $sql_check = "SELECT id FROM grades WHERE student_id = ? AND subject_id = ?";
            if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
                mysqli_stmt_bind_param($stmt_check, "ii", $student_id, $subject_id);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);

                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    // Update nilai yang sudah ada
                    $sql_upsert = "UPDATE grades SET grade_value = ? WHERE student_id = ? AND subject_id = ?";
                } else {
                    // Tambah nilai baru
                    $sql_upsert = "INSERT INTO grades (grade_value, student_id, subject_id) VALUES (?, ?, ?)";
                }
                mysqli_stmt_close($stmt_check);

                if ($stmt_upsert = mysqli_prepare($conn, $sql_upsert)) {
                    mysqli_stmt_bind_param($stmt_upsert, "dii", $grade_value, $student_id, $subject_id);
                    if (!mysqli_stmt_execute($stmt_upsert)) {
                        $all_saved = false;
                        $error_message .= "Gagal menyimpan nilai untuk mata pelajaran ID " . $subject_id . ". ";
                    }
                    mysqli_stmt_close($stmt_upsert);
                } else {
                    $all_saved = false;
                    $error_message .= "Gagal menyiapkan statement untuk mata pelajaran ID " . $subject_id . ". ";
                }
            } else {
                $all_saved = false;
                $error_message .= "Gagal menyiapkan statement cek untuk mata pelajaran ID " . $subject_id . ". ";
            }
        }

        if ($all_saved && empty($error_message)) {
            $success_message = "Nilai siswa berhasil diperbarui.";
        } elseif (!$all_saved && empty($error_message)) {
            $error_message = "Beberapa nilai gagal disimpan. Mohon periksa kembali.";
        }
    } else {
        $error_message = "Tidak ada nilai yang disubmit.";
    }
}

// --- Fetch All Subjects and Current Grades for the Student ---
$subjects_with_grades = [];
$sql_subjects_grades = "SELECT s.id AS subject_id, s.subject_name, s.category,
                               COALESCE(g.grade_value, 0) AS grade_value
                        FROM subjects s
                        LEFT JOIN grades g ON s.id = g.subject_id AND g.student_id = ?
                        ORDER BY s.category, s.subject_name";

if ($stmt_sg = mysqli_prepare($conn, $sql_subjects_grades)) {
    mysqli_stmt_bind_param($stmt_sg, "i", $student_id);
    if (mysqli_stmt_execute($stmt_sg)) {
        $result_sg = mysqli_stmt_get_result($stmt_sg);
        while ($row_sg = mysqli_fetch_assoc($result_sg)) {
            $subjects_with_grades[] = $row_sg;
        }
    }
    mysqli_stmt_close($stmt_sg);
}

?>

<div class="card p-6">
    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Kelola Nilai untuk Siswa: <?php echo htmlspecialchars($student_name); ?></h3>

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

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?student_id=' . $student_id; ?>" method="post">
        <input type="hidden" name="action" value="save_grades">

        <div class="overflow-x-auto mb-6">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Kategori</th>
                        <th class="py-3 px-6 text-left">Mata Pelajaran</th>
                        <th class="py-3 px-6 text-center">Nilai</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm font-light">
                    <?php if (count($subjects_with_grades) > 0): ?>
                        <?php
                        $current_category = '';
                        foreach ($subjects_with_grades as $item):
                            if ($current_category != $item['category']) {
                                $current_category = $item['category'];
                                echo '<tr class="bg-gray-50"><td colspan="3" class="py-2 px-6 font-semibold">' . htmlspecialchars($current_category) . '</td></tr>';
                            }
                        ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($item['category']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($item['subject_name']); ?></td>
                            <td class="py-3 px-6 text-center">
                                <input type="number" step="0.01" min="0" max="100" name="grades[<?php echo $item['subject_id']; ?>]" value="<?php echo htmlspecialchars($item['grade_value']); ?>" class="w-24 text-center border rounded-md py-1 px-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-3 px-6 text-center text-gray-500">Tidak ada mata pelajaran yang terdaftar. Silakan tambahkan mata pelajaran terlebih dahulu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-end space-x-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-save mr-2"></i>Simpan Nilai
            </button>
            <a href="students.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Data Siswa
            </a>
        </div>
    </form>
</div>

<?php
include_once 'includes/footer.php'; // Memasukkan footer admin
?>