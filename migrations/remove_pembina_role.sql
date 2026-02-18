-- Migration: Remove 'pembina' role
-- 1. Update existing 'pembina' users to 'anggota'
UPDATE user SET peran = 'anggota' WHERE peran = 'pembina';

-- 2. Modify `peran` column to remove 'pembina' option
ALTER TABLE user
MODIFY COLUMN peran ENUM('admin', 'anggota') DEFAULT 'anggota';