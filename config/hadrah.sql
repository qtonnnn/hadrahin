-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 16, 2026 at 07:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hadrah`
--

-- --------------------------------------------------------

--
-- Table structure for table `absen_latihan`
--

CREATE TABLE `absen_latihan` (
  `id_absen` int(11) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `status_hadir` enum('hadir','izin','alpa') DEFAULT 'hadir',
  `catatan` text DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `user_modified` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id_alat` int(11) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `kondisi` enum('baik','rusak','servis') DEFAULT 'baik',
  `catatan` text DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `user_modified` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_acara`
--

CREATE TABLE `booking_acara` (
  `id_booking` int(11) NOT NULL,
  `nama_acara` varchar(150) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `no_hp_pemesan` varchar(20) DEFAULT NULL,
  `tanggal_acara` date NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `lokasi` varchar(150) NOT NULL,
  `dresscode` varchar(150) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('menunggu','diterima','ditolak','selesai') DEFAULT 'menunggu',
  `user_record` varchar(50) DEFAULT NULL,
  `user_modified` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumentasi_acara`
--

CREATE TABLE `dokumentasi_acara` (
  `id_dokumentasi` int(11) NOT NULL,
  `id_history_acara` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `keterangan` varchar(150) DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history_acara`
--

CREATE TABLE `history_acara` (
  `id_history_acara` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL,
  `nama_acara` varchar(150) NOT NULL,
  `tanggal_acara` date NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `dresscode` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history_latihan`
--

CREATE TABLE `history_latihan` (
  `id_history_latihan` int(11) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `total_hadir` int(11) DEFAULT 0,
  `total_izin` int(11) DEFAULT 0,
  `total_alpa` int(11) DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_latihan`
--

CREATE TABLE `jadwal_latihan` (
  `id_jadwal` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time DEFAULT NULL,
  `lokasi` varchar(150) NOT NULL,
  `status` enum('direncanakan','selesai','dibatalkan') DEFAULT 'direncanakan',
  `catatan` text DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `user_modified` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `peran` enum('admin','pembina','anggota') DEFAULT 'anggota',
  `status_aktif` tinyint(1) DEFAULT 1,
  `tanggal_gabung` date DEFAULT NULL,
  `user_record` varchar(50) DEFAULT NULL,
  `user_modified` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `username`, `password`, `nama_lengkap`, `no_hp`, `alamat`, `peran`, `status_aktif`, `tanggal_gabung`, `user_record`, `user_modified`, `created_at`, `updated_at`) VALUES
(2, 'admin', '123', 'Admin Sistem', '081111111111', 'Sekretariat Tim Hadrah', 'admin', 1, '2024-01-01', 'system', 'system', '2026-01-16 06:44:57', '2026-01-16 06:46:41'),
(3, 'pembina', '123', 'Ustadz Ahmad', '082222222222', 'Jl. Masjid Raya', 'pembina', 1, '2024-01-05', 'admin', 'admin', '2026-01-16 06:45:08', '2026-01-16 06:46:28'),
(4, 'riski', '123', 'Muhammad Rizki', '083333333333', 'Jl. Kenanga No. 12', 'anggota', 1, '2024-02-01', 'admin', 'admin', '2026-01-16 06:46:03', '2026-01-16 06:46:03'),
(5, 'fauzan', '123', 'Ahmad Fauzan', '084444444444', 'Jl. Melati No. 5', 'anggota', 1, '2024-02-03', 'admin', 'admin', '2026-01-16 06:46:03', '2026-01-16 06:47:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absen_latihan`
--
ALTER TABLE `absen_latihan`
  ADD PRIMARY KEY (`id_absen`),
  ADD UNIQUE KEY `uniq_user_jadwal` (`id_jadwal`,`id_user`),
  ADD KEY `fk_absen_user` (`id_user`);

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id_alat`);

--
-- Indexes for table `booking_acara`
--
ALTER TABLE `booking_acara`
  ADD PRIMARY KEY (`id_booking`);

--
-- Indexes for table `dokumentasi_acara`
--
ALTER TABLE `dokumentasi_acara`
  ADD PRIMARY KEY (`id_dokumentasi`),
  ADD KEY `fk_dokumentasi_history` (`id_history_acara`);

--
-- Indexes for table `history_acara`
--
ALTER TABLE `history_acara`
  ADD PRIMARY KEY (`id_history_acara`),
  ADD KEY `fk_history_booking` (`id_booking`);

--
-- Indexes for table `history_latihan`
--
ALTER TABLE `history_latihan`
  ADD PRIMARY KEY (`id_history_latihan`),
  ADD KEY `fk_history_jadwal` (`id_jadwal`);

--
-- Indexes for table `jadwal_latihan`
--
ALTER TABLE `jadwal_latihan`
  ADD PRIMARY KEY (`id_jadwal`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absen_latihan`
--
ALTER TABLE `absen_latihan`
  MODIFY `id_absen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id_alat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_acara`
--
ALTER TABLE `booking_acara`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dokumentasi_acara`
--
ALTER TABLE `dokumentasi_acara`
  MODIFY `id_dokumentasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history_acara`
--
ALTER TABLE `history_acara`
  MODIFY `id_history_acara` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history_latihan`
--
ALTER TABLE `history_latihan`
  MODIFY `id_history_latihan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jadwal_latihan`
--
ALTER TABLE `jadwal_latihan`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absen_latihan`
--
ALTER TABLE `absen_latihan`
  ADD CONSTRAINT `fk_absen_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_latihan` (`id_jadwal`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_absen_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `dokumentasi_acara`
--
ALTER TABLE `dokumentasi_acara`
  ADD CONSTRAINT `fk_dokumentasi_history` FOREIGN KEY (`id_history_acara`) REFERENCES `history_acara` (`id_history_acara`) ON DELETE CASCADE;

--
-- Constraints for table `history_acara`
--
ALTER TABLE `history_acara`
  ADD CONSTRAINT `fk_history_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking_acara` (`id_booking`) ON DELETE CASCADE;

--
-- Constraints for table `history_latihan`
--
ALTER TABLE `history_latihan`
  ADD CONSTRAINT `fk_history_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_latihan` (`id_jadwal`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
