# Rencana Update ER Diagram dan Database

## Perubahan pada Database (`config/hadrahin.sql`):

### 1. Tabel `user`
- [x] Tambah kolom `no_hp` varchar(20) DEFAULT NULL
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 2. Tabel `absen_latihan`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 3. Tabel `jadwal_latihan`
- [x] Tambah kolom `catatan` text DEFAULT NULL
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 4. Tabel `keuangan`
- [x] Tambah kolom `jam_mulai` time DEFAULT NULL
- [x] Tambah kolom `no_hp_pemesan` varchar(20) DEFAULT NULL
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 5. Tabel `alat`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 6. Tabel `booking_acara`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 7. Tabel `dokumentasi_acara`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 8. Tabel `dresscode`
- [x] Tambah kolom `user_modified`, `user_record` (sudah ada created_at, updated_at)

---

## Perubahan pada ER Diagram (`MD/ER_DIAGRAM.md`):

### 1. Tabel `user`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 2. Tabel `absen_latihan`
- [x] Hapus kolom `catatan`
- [x] Tambah kolom `jam_absen` (sudah ada di database)
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 3. Tabel `alat`
- [x] Tambah representasi `id_user` (FK) di diagram
- [x] Tambah tabel `alat` terpisah di diagram dengan relasi ke user
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 4. Tabel `booking_acara`
- [x] Hapus kolom `no_hp_pemesan` dan `jam_mulai` (pindah ke keuangan)
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 5. Tabel `dokumentasi_acara`
- [x] Hapus kolom `tipe_file`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 6. Tabel `jadwal_latihan`
- [x] Hapus kolom `materi_latihan`
- [x] Hapus kolom `jam_selesai`
- [x] Tambah kolom `catatan`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 7. Tabel `keuangan`
- [x] Hapus kolom `kategori`
- [x] Hapus kolom `bukti_transaksi`
- [x] Tambah kolom `jam_mulai` dan `no_hp_pemesan`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

### 8. Tabel `dresscode`
- [x] Tambah kolom `created_at`, `updated_at`, `user_modified`, `user_record`

---

## ✅ STATUS: SEMUA PERUBAHAN SELESAI DIIMPLEMENTASIKAN

