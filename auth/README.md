# Modul Autentikasi (Auth)

Dokumen ini menjelaskan struktur, fungsi, dan mekanisme sistem autentikasi dalam aplikasi Hadrah.

---

## Daftar Isi

1. [Struktur Direktori](#struktur-direktori)
2. [File-File Utama](#file-file-utama)
3. [Mekanisme Login](#mekanisme-login)
4. [Mekanisme Logout](#mekanisme-logout)
5. [Reset Password](#reset-password)
6. [Cek Session](#cek-session)
7. [Fitur Keamanan](#fitur-keamanan)
8. [Perubahan dari Desain Awal](#perubahan-dari-desain-awal)

---

## Struktur Direktori

```
auth/
├── check_session.php    # Cek validitas session
├── login.php           # Halaman login utama
├── logout.php          # Proses logout
└── reset.php           # Halaman reset password
```

---

## File-File Utama

### 1. login.php

**Deskripsi:** Halaman utama untuk autentikasi user.

**Fitur:**
- Form login dengan username dan password
- Rate limiting untuk mencegah brute force attack
- Validasi input real-time
- Modal ubah password (tanpa perlu login)
- Redirect berdasarkan peran user

**Alur Login:**
1. User membuka halaman login
2. Sistem cek rate limit (IP-based)
3. Jika diblokir, tampilkan countdown timer
4. User submit form login
5. Sistem validasi input (tidak kosong)
6. Query database cari user
7. Cek status aktif akun
8. Verifikasi password
9. Jika berhasil: reset rate limit, buat session, redirect berdasarkan peran
10. Jika gagal: catat percobaan gagal, tampilkan error

**Redirect Berdasarkan Peran:**
- **Admin** → `/dashboard/admin.php`
- **Anggota** → `/dashboard/anggota.php`

> **Catatan:** Tidak ada role Pembina dalam sistem ini.

**Keamanan:**
- Anti-cache headers untuk mencegah cached pages
- Anti-back button script
- Rate limiting dengan blokir 15 menit setelah 5x gagal
- Password menggunakan `password_hash()` (bcrypt)

---

### 2. logout.php

**Deskripsi:** Proses penghapusan session dan redirect ke halaman login.

**Proses:**
1. Hapus semua variabel session
2. Destroy session
3. Redirect ke halaman login
4. Tampilkan pesan "Logout berhasil"

---

### 3. reset.php

**Deskripsi:** Halaman untuk reset password user.

**Fitur:**
- Form input username
- Validasi username exists
- Reset password ke default (atau generate baru)
- Update password di database

---

### 4. check_session.php

**Deskripsi:** API untuk cek validitas session secara real-time.

**Fungsi:**
- Cek apakah user sudah login
- Validasi session tidak expired
- Return status dalam format JSON

**Response:**
```json
{
  "valid": true,
  "user_id": 1,
  "username": "admin",
  "peran": "admin"
}
```

---

## Mekanisme Login

### Diagram Alur Login

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ALUR LOGIN SISTEM                                   │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │      Buka Halaman       │
                         │      Login Page         │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │     Cek Rate Limit     │
                         │     (IP-based)        │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                        TIDAK                     YA
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Lanjutkan Proses   │    │ TAMPILKAN PESAN    │
              │ Login             │    │ BLOKIR (15 menit) │
              └─────────┬──────────┘    └────────────────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Validasi Input     │
              │ (tidak kosong)    │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │  Query Database    │
              │  Cari User        │
              └─────────┬──────────┘
                        │
           ┌────────────┴────────────┐
           │                         │
          YA                        TIDAK
           │                         │
           ▼                         ▼
┌────────────────────┐    ┌────────────────────┐
│  Cek Status Akun   │    │ Tampilkan Error    │
│  (status_aktif)   │    │ "Username tidak    │
└─────────┬──────────┘    │ ditemukan!"        │
          │               └────────────────────┘
          │
     ┌────┴──────────────────┐
     │                       │
   AKTIF                 TIDAK AKTIF
     │                       │
     ▼                       ▼
┌───────────────┐  ┌────────────────────┐
│ Verifikasi    │  │ Tampilkan Error    │
│ Password      │  │ "Akun tidak aktif!"│
└───────┬───────┘  └────────────────────┘
        │
   ┌────┴─────────────────────┐
   │                          │
  BENAR                     SALAH
   │                          │
   ▼                          ▼
┌───────────────┐  ┌────────────────────┐
│ Reset Rate    │  │ Record Failed     │
│ Limit         │  │ Attempt (5x gagal│
│ & Buat        │  │ = blokir 15 menit│
│ Session       │  └────────────────────┘
└───────┬───────┘
        │
        ▼
┌───────────────┐
│ Redirect      │
│Berdasarkan    │
│Peran:        │
│- Admin →     │
│  dashboard   │
│- Anggota →   │
│  dashboard   │
└───────────────┘
```

---

## Mekanisme Logout

### Diagram Alur Logout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            ALUR LOGOUT                                       │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │  User Klik Tombol       │
                         │  "Logout"              │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Hapus Session:        │
                         │  - user_id             │
                         │  - username            │
                         │  - nama_lengkap        │
                         │  - peran               │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  session_destroy()     │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Redirect ke Halaman   │
                         │  Login                 │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Tampilkan Halaman      │
                         │  Login + Pesan Success  │
                         └─────────────────────────┘
```

---

## Reset Password

### Mekanisme

1. User klik "Lupa Password" di halaman login
2. Sistem tampilkan form input username
3. User masukkan username
4. Sistem validasi username exists
5. Jika valid, reset password ke nilai default
6. Tampilkan password baru ke user

> **Catatan:** Fitur reset password saat ini menggunakan password default. Fiturbetter dengan email belum tersedia.

---

## Cek Session

### Endpoint: check_session.php

**Method:** GET

**Response Sukses:**
```json
{
  "valid": true,
  "user_id": 1,
  "username": "adminn",
  "nama_lengkap": "adminaja",
  "peran": "admin"
}
```

**Response Tidak Valid:**
```json
{
  "valid": false,
  "message": "Session tidak valid atau expired"
}
```

---

## Fitur Keamanan

### 1. Rate Limiting

| Percobaan Gagal | Durasi Blokir |
|-----------------|---------------|
| 5x | 15 menit |

**Mekanisme:**
- Menggunakan file `logs/login_attempts.dat` untuk menyimpan data
- IP-based blocking
- Countdown timer real-time
- Auto-refresh setelah blokir lifted

### 2. Password Security

- Menggunakan `password_hash()` dengan algoritma bcrypt
- Tidak pernah menyimpan password plain text
- Validasi kekuatan password

### 3. Anti-Cache

```php
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
```

### 4. Anti-Back Button

JavaScript untuk mencegah user kembali ke halaman sebelumnya setelah logout.

---

## Perubahan dari Desain Awal

| Aspek | Desain Awal | Implementasi Saat Ini |
|-------|-------------|----------------------|
| **Role User** | Admin, Pembina, Anggota | Admin, Anggota |
| **Jumlah Role** | 3 | 2 |
| **Dashboard Pembina** | Ada | Tidak ada |
| **Rate Limit** | 12x gagal = blokir | 5x gagal = blokir 15 menit |
| **Durasi Blokir** | 5/10/15 menit | 15 menit (fix) |

### Detail Perubahan:

1. **Role Pembina Dihapus**
   - Sistem hanya memiliki 2 peran: Admin dan Anggota
   - Tidak ada dashboard Pembina
   - Semua fitur yang awalnya untuk Pembina sekarang untuk Admin

2. **Rate Limiting Disederhanakan**
   - Dari sistem dinamis (12x/24x/36x) menjadi fixed (5x gagal = 15 menit)
   - Lebih simple dan mudah dipahami

3. **Redirect Lebih Sederhana**
   - Dari 3 kemungkinan (Admin/Pembina/Anggota) menjadi 2 (Admin/Anggota)

---

## Referensi

- File Database: `config/database.php`, `config/hadrahin.sql`
- File Rate Limit: `includes/rate_limit.php`
- File Error Handling: `includes/error_handler.php`
- ER Diagram: `MD/ER_DIAGRAM.md`
- Diagram Sistem: `MD/DIAGRAM_ALUR_SISTEM.md`

---

*Dokumen ini dibuat berdasarkan analisis kode sumber di folder `/opt/lampp/htdocs/hadrahin/auth/`*

