-- Migration: Add keterangan column to alat table
-- Date: 2024
-- Description: Menambahkan kolom keterangan untuk menyimpan catatan tambahan alat

ALTER TABLE `alat` ADD COLUMN `keterangan` TEXT DEFAULT NULL AFTER `jumlah_rusak`;

-- Update comment untuk dokumentasi
-- Kolom keterangan dapat digunakan untuk menyimpan catatan tambahan tentang alat,
-- seperti spesifikasi teknis, catatan perbaikan, atau informasi penting lainnya.
