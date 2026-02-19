# TODO - Implementasi Upload Foto Pakaian pada Dresscode

## Progres: [==============] 100%

### Langkah-langkah:

- [x] 1. Analisis kode yang ada (tambah.php, edit.php, index.php, dokumentasi.php)
- [x] 2. Database Migration - Tambah kolom `foto` pada tabel dresscode
- [x] 3. Create upload directory - Buat folder `assets/uploads/pakaian/`
- [x] 4. Modifikasi tambah.php - Tambahkan input upload foto
- [x] 5. Modifikasi edit.php - Tambahkan input upload foto dengan preview
- [x] 6. Modifikasi index.php - Tampilkan foto di tabel dan modal detail

---

## Detail:

### 2. Database Migration
```sql
ALTER TABLE `dresscode` ADD COLUMN `foto` VARCHAR(255) DEFAULT NULL AFTER `warna`;
```

### 3. Create Upload Directory
Path: `assets/uploads/pakaian/`

### 4-6. Modifikasi PHP Files
- tambah.php: Handle upload foto saat create
- edit.php: Handle upload & delete foto lama saat update  
- index.php: Display foto di table/card view dan detail modal

