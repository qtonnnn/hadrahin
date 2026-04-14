-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 21, 2026 at 03:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Database: `hadrahin`
--

-- --------------------------------------------------------

--
-- Table structure for table `absen_latihan`
--

CREATE TABLE `absen_latihan` (
    `id_absen` int(11) NOT NULL,
    `id_jadwal` int(11) NOT NULL,
    `id_user` int(11) NOT NULL,
    `status_hadir` enum('hadir', 'izin', 'alpa') DEFAULT 'hadir',
    `jam_absen` timestamp NULL DEFAULT current_timestamp(),
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
    `id_alat` int(11) NOT NULL,
    `nama_alat` varchar(100) NOT NULL,
    `jumlah_baik` int(11) DEFAULT 0,
    `jumlah_rusak` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alat_pengguna`
--

CREATE TABLE `alat_pengguna` (
    `id_alat_pengguna` int(11) NOT NULL,
    `id_alat` int(11) NOT NULL,
    `id_user` int(11) NOT NULL,
    `tanggal_diberikan` date NOT NULL,
    `status` enum('aktif', 'dikembalikan') DEFAULT 'aktif',
    `keterangan` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_acara`
--

CREATE TABLE `booking_acara` (
    `id_booking` int(11) NOT NULL,
    `id_user` int(11) DEFAULT NULL,
    `nama_acara` varchar(150) NOT NULL,
    `nama_pemesan` varchar(100) NOT NULL,
    `no_hp_pemesan` varchar(20) DEFAULT NULL,
    `tanggal_acara` date NOT NULL,
    `jam_mulai` time NOT NULL DEFAULT '00:00:00',
    `lokasi` varchar(150) NOT NULL,
    `id_dresscode` int(11) DEFAULT NULL,
    `status` enum(
        'menunggu',
        'diterima',
        'ditolak',
        'selesai'
    ) DEFAULT 'menunggu',
    `keterangan` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumentasi_acara`
--

CREATE TABLE `dokumentasi_acara` (
    `id_dokumentasi` int(11) NOT NULL,
    `id_booking` int(11) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `keterangan` varchar(150) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dresscode`
--

CREATE TABLE `dresscode` (
    `id_dresscode` int(11) NOT NULL,
    `nama_pakaian` varchar(100) NOT NULL,
    `deskripsi` text DEFAULT NULL,
    `warna` varchar(50) DEFAULT NULL,
    `status` enum('aktif', 'nonaktif') DEFAULT 'aktif',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_latihan`
--

CREATE TABLE `jadwal_latihan` (
    `id_jadwal` int(11) NOT NULL,
    `tanggal` date NOT NULL,
    `jam_mulai` time NOT NULL,
    `lokasi` varchar(150) NOT NULL,
    `status` enum(
        'direncanakan',
        'selesai',
        'dibatalkan'
    ) DEFAULT 'direncanakan',
    `catatan` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `keuangan`
--

CREATE TABLE `keuangan` (
    `id_kas` int(11) NOT NULL,
    `id_user` int(11) DEFAULT NULL,
    `tipe` enum('pemasukan', 'pengeluaran') NOT NULL,
    `jumlah` decimal(12, 2) NOT NULL,
    `keterangan` text DEFAULT NULL,
    `tanggal` date NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

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
    `peran` enum('admin', 'anggota') DEFAULT 'anggota',
    `status_aktif` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `user_modified` int(11) DEFAULT NULL,
    `user_record` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO
    `user` (
        `id_user`,
        `username`,
        `password`,
        `nama_lengkap`,
        `no_hp`,
        `peran`,
        `status_aktif`,
        `created_at`,
        `updated_at`,
        `user_modified`,
        `user_record`
    )
VALUES (
        1,
        'adminn',
        '$2y$10$8wexZ0opk9ZQJ6l2Isn6G.Ocu3n6ccgpCXTy4e0u7hzadeFQrk1r6',
        'adminaja',
        NULL,
        'admin',
        1,
        '2026-01-21 01:38:16',
        '2026-01-21 01:38:16',
        NULL,
        NULL
    );

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absen_latihan`
--
ALTER TABLE `absen_latihan`
ADD PRIMARY KEY (`id_absen`),
ADD UNIQUE KEY `uniq_user_jadwal` (`id_jadwal`, `id_user`),
ADD KEY `fk_absen_user` (`id_user`),
ADD KEY `idx_user_status` (`id_user`, `status_hadir`);

--
-- Indexes for table `alat`
--
ALTER TABLE `alat` ADD PRIMARY KEY (`id_alat`);

--
-- Indexes for table `alat_pengguna`
--
ALTER TABLE `alat_pengguna`
ADD PRIMARY KEY (`id_alat_pengguna`),
ADD KEY `fk_alatp_alat` (`id_alat`),
ADD KEY `fk_alatp_user` (`id_user`),
ADD UNIQUE KEY `uniq_alat_user` (
    `id_alat`,
    `id_user`,
    `status`
);

--
-- AUTO_INCREMENT for table `alat_pengguna`
--
ALTER TABLE `alat_pengguna`
MODIFY `id_alat_pengguna` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `alat_pengguna`
--
ALTER TABLE `alat_pengguna`
ADD CONSTRAINT `fk_alatp_alat` FOREIGN KEY (`id_alat`) REFERENCES `alat` (`id_alat`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_alatp_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Indexes for table `booking_acara`
--
ALTER TABLE `booking_acara`
ADD PRIMARY KEY (`id_booking`),
ADD KEY `fk_booking_user` (`id_user`),
ADD KEY `fk_booking_dresscode` (`id_dresscode`),
ADD KEY `idx_status_tanggal` (`status`, `tanggal_acara`),
ADD KEY `idx_tanggal` (`tanggal_acara`);

--
-- Indexes for table `dokumentasi_acara`
--
ALTER TABLE `dokumentasi_acara`
ADD PRIMARY KEY (`id_dokumentasi`),
ADD KEY `fk_dokumentasi_booking` (`id_booking`);

--
-- Indexes for table `dresscode`
--
ALTER TABLE `dresscode` ADD PRIMARY KEY (`id_dresscode`);

--
-- Indexes for table `jadwal_latihan`
--
ALTER TABLE `jadwal_latihan`
ADD PRIMARY KEY (`id_jadwal`),
ADD KEY `idx_tanggal_status` (`tanggal`, `status`);

--
-- Indexes for table `keuangan`
--
ALTER TABLE `keuangan`
ADD PRIMARY KEY (`id_kas`),
ADD KEY `fk_keuangan_user` (`id_user`),
ADD KEY `idx_tipe_tanggal` (`tipe`, `tanggal`),
ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
ADD PRIMARY KEY (`id_user`),
ADD UNIQUE KEY `username` (`username`),
ADD KEY `idx_peran_aktif` (`peran`, `status_aktif`);

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
-- AUTO_INCREMENT for table `dresscode`
--
ALTER TABLE `dresscode`
MODIFY `id_dresscode` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jadwal_latihan`
--
ALTER TABLE `jadwal_latihan`
MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keuangan`
--
ALTER TABLE `keuangan`
MODIFY `id_kas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

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
-- Constraints for table `alat`
--
-- Catatan: Relasi alat-pengguna sekarang ada di tabel `alat_pengguna`

--
-- Constraints for table `booking_acara`
--
ALTER TABLE `booking_acara`
ADD CONSTRAINT `fk_booking_dresscode` FOREIGN KEY (`id_dresscode`) REFERENCES `dresscode` (`id_dresscode`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `dokumentasi_acara`
--
ALTER TABLE `dokumentasi_acara`
ADD CONSTRAINT `fk_dokumentasi_booking` FOREIGN KEY (`id_booking`) REFERENCES `booking_acara` (`id_booking`) ON DELETE CASCADE;

--
-- Constraints for table `keuangan`
--
ALTER TABLE `keuangan`
ADD CONSTRAINT `fk_keuangan_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;