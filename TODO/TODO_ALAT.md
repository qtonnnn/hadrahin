# TODO - Modul Alat dengan Fitur Pengguna

## Rencana Implementasi

### 1. Update SQL Database
- [x] Menambahkan tabel `alat_pengguna` di `config/hadrahin.sql`
- [x] Update constraint untuk tabel `alat` jika diperlukan

### 2. Buat File PHP Modul Alat
- [x] `modules/alat/index.php` - Daftar alat + daftar pengguna per alat
- [x] `modules/alat/tambah.php` - Tambah alat baru + assign pengguna + Total Alat
- [x] `modules/alat/edit.php` - Edit alat + assign/unassign pengguna + Total Alat
- [x] `modules/alat/hapus.php` - Hapus alat (sederhana)
- [x] `modules/alat/api_check_alat.php` - Validasi realtime nama alat

### 3. Update Sidebar & Header
- [x] Sidebar dan Header sudah ada sebelumnya

### 4. Fitur Tambahan
- [x] Filter pengguna (hanya role 'anggota')
- [x] Hapus notifikasi konfirmasi di index
- [x] Hapus pilihan radio di hapus.php
- [x] Kolom "Tanggal Diberikan" disembunyikan
- [x] Kolom "Total Alat" dengan validasi otomatis
  - Total = Baik + Rusak
  - Validasi: jumlah baik + rusak tidak boleh melebihi total
  - Saat edit, Total = jumlah_baik + jumlah_rusak existing

---

## Fitur Total Alat

### Validasi Otomatis:
1. Input **Total Alat** (field baru)
2. Input **Jumlah Baik** - otomatis di-limit oleh Total - Rusak
3. Input **Jumlah Rusak** - otomatis di-limit oleh Total - Baik
4. Summary alert menampilkan: Total | Baik | Rusak | Sisa

### Contoh:
- Total: 5
- Baik: 3 → Rusak maks: 2 (5-3)
- Rusak: 2 → Baik maks: 3 (5-2)
- Sisa: 0 (5-3-2)
