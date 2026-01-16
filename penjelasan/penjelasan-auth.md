# Penjelasan Folder Auth

Folder `auth/` berisi semua file yang berkaitan dengan sistem autentikasi dan otorisasi pengguna dalam aplikasi Hadrahin.

## Struktur File

```
auth/
├── login.php          # Halaman form login
├── proses_login.php   # Logika proses login
└── logout.php         # Logika proses logout
```

## Penjelasan Tiap File

### 1. login.php

**Tujuan:** Menampilkan halaman form login untuk pengguna.

**Fungsi utama:**
- Menampilkan form dengan input username dan password
- Mengalihkan ke dashboard jika user sudah login
- Mengirim data form ke `proses_login.php` menggunakan method POST

**Session yang digunakan:**
- Memeriksa `$_SESSION['user_id']` untuk menentukan apakah user sudah login

**Flow:**
```
User mengakses login.php
    ↓
Apakah sudah login? (cek $_SESSION['user_id'])
    ├── YA → Redirect ke dashboard
    └── TIDAK → Tampilkan form login
```

---

### 2. proses_login.php

**Tujuan:** Memproses data login dan mengautentikasi pengguna.

**Fungsi utama:**
1. Menerima input username dan password dari form POST
2. Validasi input (tidak boleh kosong)
3. Mengambil data user dari database berdasarkan username
4. Memvalidasi password (mendukung password hash dan plain text)
5. Menyimpan data user ke session
6. Mengarahkan ke halaman dashboard sesuai peran user

**Session yang disimpan:**
- `$_SESSION['user_id']` - ID user dari database
- `$_SESSION['nama']` - Nama lengkap user
- `$_SESSION['role']` - Peran user (admin/pembina/anggota)
- `$_SESSION['login']` - Status login (true/false)

**Redirect berdasarkan peran:**
| Peran | Halaman Tujuan |
|-------|----------------|
| admin | `/dashboard/admin.php` |
| pembina | `/dashboard/pembina.php` |
| anggota | `/dashboard/anggota.php` |

**Keamanan yang diterapkan:**
- Menggunakan **Prepared Statements** untuk mencegah SQL Injection
- Validasi input tidak kosong
- Mendukung password hash (password_verify) dan plain text untuk compatibility

**Flow:**
```
proses_login.php menerima POST
    ↓
Validasi input (username & password tidak kosong)
    ├── Kosong → Redirect ke login.php
    └── Lanjut
        ↓
Query database dengan prepared statement
    ↓
Cek user ditemukan?
    ├── TIDAK → Tampilkan error "Username tidak ditemukan"
    └── YA → Lanjut
        ↓
Cek password valid?
    ├── SALAH → Tampilkan error "Password salah"
    └── BENAR → Lanjut
        ↓
Set session user
        ↓
Redirect ke dashboard sesuai peran
```

---

### 3. logout.php

**Tujuan:** Mengakhiri sesi user dan keluar dari sistem.

**Fungsi utama:**
1. Meng-include config untuk mendapatkan BASE_URL
2. Menghancurkan semua session dengan `session_destroy()`
3. Mengarahkan user kembali ke halaman login

**Flow:**
```
User mengklik logout
    ↓
logout.php dieksekusi
    ↓
session_destroy() → hapus semua session
    ↓
Redirect ke login.php
```

---

## Konfigurasi Session

Session dikonfigurasi di `config/config.php` dengan:
- Timezone: Asia/Jakarta
- BASE_URL: http://localhost/hadrahin
- Session dimulai jika belum aktif

## Variabel Session yang Digunakan

| Variabel | Sumber | Kegunaan |
|----------|--------|----------|
| `$_SESSION['user_id']` | proses_login.php | Menyimpan ID user untuk cek login |
| `$_SESSION['nama']` | proses_login.php | Menyimpan nama user untuk ditampilkan |
| `$_SESSION['role']` | proses_login.php | Menyimpan peran user untuk otorisasi |
| `$_SESSION['login']` | proses_login.php | Flag status login |

## Middleware Terkait

File `core/middleware.php` berisi fungsi untuk:
- `cekLogin()` - Memeriksa apakah user sudah login
- `cekRole($roles)` - Memeriksa peran user (hanya untuk admin/pembina tertentu)

## Keamanan

### Implementasi Saat Ini:
1. ✅ Prepared Statements untuk SQL Injection
2. ✅ Session-based authentication
3. ✅ Role-based redirection
4. ✅ Validasi input tidak kosong

### Rekomendasi Peningkatan:
1. 🔒 Tambahkan CSRF token pada form login
2. 🔒 Implementasi rate limiting untuk mencegah brute force
3. 🔒 Gunakan password_hash() untuk password baru
4. 🔒 Tambahkan remember me functionality dengan cookie
5. 🔒 Session timeout setelah inactivity

## Troubleshooting

### HTTP 500 Error pada proses_login.php

**Kemungkinan penyebab:**
1. Session tidak dimulai - cek `config/config.php`
2. Koneksi database gagal - cek `config/koneksi.php`
3. Variabel session tidak konsisten - cek konfigurasi session di semua file
4. Password verification gagal - cek format password di database

**Cara debug:**
1. Cek error log PHP: `/opt/lampp/logs/php_error_log`
2. Sementara ubah `die()` menjadi `var_dump()` untuk melihat nilai variabel
3. Cek apakah database sudah di-import dengan benar

## Contoh Penggunaan

### Login berhasil (admin):
```php
// Setelah login berhasil, session akan berisi:
$_SESSION['user_id'] = 2;
$_SESSION['nama'] = 'Admin Sistem';
$_SESSION['role'] = 'admin';
$_SESSION['login'] = true;

// Redirect ke:
header('Location: http://localhost/hadrahin/dashboard/admin.php');
```

### Proteksi halaman dashboard:
```php
require_once '../core/middleware.php';

cekLogin();        // Jika belum login, redirect ke login.php
cekRole(['admin']); // Jika bukan admin, redirect ke dashboard
```

---

## Catatan Penting

1. **Konsistensi Nama Variabel:** Pastikan menggunakan nama variabel session yang sama di seluruh aplikasi:
   - `user_id` (bukan `id_user`)
   - `role` (bukan `peran`)
   - `nama` (bukan `nama_user`)

2. **Password:** Untuk data baru, gunakan `password_hash()` dan `password_verify()`. Untuk data lama (plain text), kode sudah support kedua jenis.

3. **Session Start:** Session sudah dimulai di `config/config.php`, tidak perlu dipanggil lagi di file lain.

