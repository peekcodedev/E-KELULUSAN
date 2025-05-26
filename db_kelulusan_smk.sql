-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 26 Bulan Mei 2025 pada 03.08
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
(1, 1, 7, 80.00),
(2, 1, 6, 80.00),
(3, 1, 8, 80.00),
(4, 1, 5, 90.00),
(5, 1, 3, 80.00),
(6, 1, 1, 80.00),
(7, 1, 4, 80.00),
(8, 1, 2, 80.00);

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
(1, 'school_name', 'SMK NURUL ISLAM'),
(2, 'school_address', 'Jl. Pangklengan. Rt 15 /03 Geneng Batealit Jepara Pos 59461'),
(3, 'school_phone', '0822 2129 3036'),
(4, 'school_email', 'smknurulislamgeneng@gmail.com'),
(5, 'school_npsn', '69916826'),
(6, 'principal_name', 'Ahmad Syarif Hidayat, S.Pd.I'),
(7, 'principal_nip', '-'),
(8, 'skl_number', ' 400.3.14.5/045.2/363/SMK.NI/V/2025'),
(9, 'announcement_date', '2025-05-19T00:33'),
(19, 'school_logo_path', 'uploads/logo_1748227880_logo-sekolah.png.png'),
(20, 'principal_signature_path', 'uploads/signature_1748227880_image-removebg-preview.png');

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
(1, '0076837947', 'AFANSA DILA SUGIARTO', 'Jepara', '2007-02-07', '69916826', '2025-05-05', 'Teknik Otomotif', 'Teknik Sepeda Motor', NULL, '2025-05-26 02:38:16');

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
(1, 'Pendidikan Agama dan Budi Pekerti', 'Umum'),
(2, 'Pendidikan Pancasila', 'Umum'),
(3, 'Bahasa Indonesia', 'Umum'),
(4, 'Pendidikan Jasmani, Olahraga dan Kesehatan', 'Umum'),
(5, 'Bahasa Jawa', 'Muatan Lokal'),
(6, 'Matematika', 'Kejuruan'),
(7, 'Bahasa Inggris', 'Kejuruan'),
(8, 'Projek Kreatif dan Kewirausahaan', 'Kejuruan');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT untuk tabel `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
