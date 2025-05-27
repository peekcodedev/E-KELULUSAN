-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 27 Bulan Mei 2025 pada 00.25
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kelulusan_smk`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `grades`
--

CREATE TABLE `grades` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `grade_value` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `grade_value`) VALUES
(9, 1, 16, 80.00),
(10, 1, 19, 80.00),
(11, 1, 17, 80.00),
(12, 1, 20, 80.00),
(13, 1, 23, 80.00),
(14, 1, 15, 80.00),
(15, 1, 22, 98.00),
(16, 1, 18, 89.00),
(17, 1, 21, 86.00),
(18, 1, 14, 87.00),
(19, 1, 11, 89.00),
(20, 1, 9, 89.00),
(21, 1, 12, 90.00),
(22, 1, 10, 90.00),
(23, 1, 13, 90.00),
(24, 1, 24, 90.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `setting_value`) VALUES
(1, 'school_name', 'SMK CONTOH'),
(2, 'school_address', 'Jl. Pangklengan. Rt 15 /03 Geneng Batealit Jepara Pos 59461'),
(3, 'school_phone', '0822 2129 3036'),
(4, 'school_email', 'smknurulislamgeneng@gmail.com'),
(5, 'school_npsn', '69916826'),
(6, 'principal_name', 'Ahmad Syarif Hidayat, S.Pd.I'),
(7, 'principal_nip', '-'),
(8, 'skl_number', ' 400.3.14.5/045.2/363/SMK.NI/V/2025'),
(9, 'announcement_date', '2025-05-31T00:33'),
(19, 'school_logo_path', 'uploads/logo_1748229602_logo-sekolah.png.png'),
(20, 'principal_signature_path', 'uploads/signature_1748227880_image-removebg-preview.png'),
(35, 'school_stamp_path', 'uploads/stamp_1748229602_stempel.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nisn_number` varchar(20) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `program_keahlian` varchar(100) DEFAULT NULL,
  `konsentrasi_keahlian` varchar(100) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `students`
--

INSERT INTO `students` (`id`, `nisn`, `full_name`, `place_of_birth`, `date_of_birth`, `nisn_number`, `graduation_date`, `program_keahlian`, `konsentrasi_keahlian`, `photo_path`, `created_at`) VALUES
(1, '0076837947', 'AFANSA DILA SUGIARTO', 'Jepara', '2007-02-07', '69916826', '2025-05-05', 'Teknik Otomotif', 'Teknik Sepeda Motor', 'uploads\\students\\student_photo_1748229462_0076837947_AFANSADILASUGIARTO.JPG', '2025-05-26 02:38:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `category`) VALUES
(9, 'Pendidikan Agama dan Budi Pekerti', 'Umum'),
(10, 'Pendidikan Pancasila', 'Umum'),
(11, 'Bahasa Indonesia', 'Umum'),
(12, 'Pendidikan Jasmani, Olahraga dan Kesehatan', 'Umum'),
(13, 'Sejarah', 'Umum'),
(14, 'Bahasa Jawa', 'Muatan Lokal'),
(15, 'Matematika', 'Kejuruan'),
(16, 'Bahasa Inggris', 'Kejuruan'),
(17, 'Informatika', 'Kejuruan'),
(18, 'Projek Ilmu Pengetahuan Alam dan Sosial', 'Kejuruan'),
(19, 'Dasar-dasar Program Keahlian', 'Kejuruan'),
(20, 'Konsentrasi Keahlian', 'Kejuruan'),
(21, 'Projek Kreatif dan Kewirausahaan', 'Kejuruan'),
(22, 'Praktek Kerja Lapangan', 'Kejuruan'),
(23, 'Mata Pelajaran pilihan', 'Kejuruan'),
(24, 'Seni Budaya', 'Umum');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$et96C5oJJ87aHwPF3vx0OuA6TSuXh4I9i6q3hqZDSQ6lG6WPa.i9i', 'admin', '2025-05-26 02:29:40');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indeks untuk tabel `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`);

--
-- Indeks untuk tabel `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
