# TODO - Modul User

## Task List

### index.php
- [x] Tambahkan styling Bootstrap yang konsisten
- [x] Tambahkan fitur search
- [x] Tambahkan pagination
- [x] Tampilkan badge untuk peran user (admin/pembina/anggota)
- [x] **PERBAIKAN UTAMA**: Definisikan BASE_URL di awal file untuk menghindari error "undefined constant"

### tambah.php
- [ ] Perbaiki styling form dengan Bootstrap
- [ ] Tambahkan required attributes
- [ ] Tambahkan konfirmasi password
- [ ] Validasi username unik

### edit.php
- [ ] Perbaiki styling form dengan Bootstrap
- [ ] Password opsional (hanya diubah jika diisi)
- [ ] Pre-fill data user yang diedit
- [ ] Validasi username unik (kecuali diri sendiri)

### hapus.php
- [ ] Tambah konfirmasi sebelum hapus
- [ ] Redirect kembali ke index.php dengan pesan sukses

---

## Perbaikan yang sudah dilakukan

### 1. index.php
- ✅ Menambahkan definisi BASE_URL di awal file untuk mencegah error "undefined constant"
- ✅ Styling Bootstrap yang konsisten
- ✅ Fitur search dengan pagination
- ✅ Badge untuk peran user (admin/pembina/anggota)
- ✅ Optimasi query dengan LIMIT dan OFFSET

### 2. auth/logout.php
- ✅ Menghapus penggunaan BASE_URL (yang menyebabkan error)
- ✅ Menggunakan path absolut langsung: `/hadrahin/auth/login.php`

### 3. auth/login.php
- ✅ Menambahkan definisi BASE_URL jika belum terdefinisi
- ✅ Mencegah error saat BASE_URL tidak tersedia

### 4. includes/auth_check.php
- ✅ Mengubah path database dari relative `../config/database.php` ke absolute `__DIR__ . '/../config/database.php'`

### 5. config/database.php
- ✅ Menambahkan pengecekan `php_sapi_name() !== 'cli'` agar tidak error saat dijalankan dari CLI
- ✅ Menambahkan `$db = $pdo` alias untuk kompatibilitas

---

## Catatan Penting

Semua file yang menggunakan BASE_URL sekarang memiliki fallback definisi di awal file. Ini mencegah error blank page saat:
1. File diakses langsung tanpa melalui index.php
2. ada masalah dengan path include
3. Konstanta BASE_URL belum terdefinisi saat file dimuat

Jika masih mengalami masalah blank page, coba:
1. Refresh browser (Ctrl+F5)
2. Restart Apache: `sudo /opt/lampp/lampp restart`
3. Cek error log: `tail -50 /opt/lampp/htdocs/hadrahin/logs/php_errors.log`

