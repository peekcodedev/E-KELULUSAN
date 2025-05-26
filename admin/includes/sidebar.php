<?php
// admin/includes/sidebar.php
?>
<div class="sidebar bg-gray-800 text-white flex flex-col p-4">
    <div class="text-3xl font-bold text-center mb-8">
        Admin Panel
    </div>
    <nav class="flex-grow">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="flex items-center py-2 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="profile.php" class="flex items-center py-2 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">
                    <i class="fas fa-cogs mr-3"></i> Atur Profil & Pengaturan
                </a>
            </li>
            <li>
                <a href="students.php" class="flex items-center py-2 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">
                    <i class="fas fa-users mr-3"></i> Data Siswa
                </a>
            </li>
            <li>
                <a href="subjects.php" class="flex items-center py-2 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">
                    <i class="fas fa-book mr-3"></i> Mata Pelajaran
                </a>
            </li>
            <li>
                <a href="upload.php" class="flex items-center py-2 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">
                    <i class="fas fa-upload mr-3"></i> Upload File
                </a>
            </li>
            <li>
                <a href="users.php" class="flex items-center py-2 px-4 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition duration-200">
                    <i class="fas fa-user-shield mr-3"></i> Pengguna Admin
                </a>
            </li>
        </ul>
    </nav>
</div>
