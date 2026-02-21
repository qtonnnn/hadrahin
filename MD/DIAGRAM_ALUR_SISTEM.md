# Diagram Alur Sistem Aplikasi Hadrah

Dokumen ini menjelaskan alur sistem aplikasi informasi grup hadrah secara komprehensif, mencakup alur autentikasi, navigasi berdasarkan peran user, dan interaksi antar modul.

---

## CATATAN PENTING - PERUBAHAN SISTEM

> **Versi Sistem Saat Ini:**
> - **Role User:** Admin dan Anggota (TIDAK ADA role Pembina)
> - **Validasi:** Menggunakan API real-time untuk validasi input
> - **Modul Booking:** Hanya dapat diakses oleh Admin
> - **Modul Absensi:** Admin dapat input absensi anggota
> - **Rate Limiting:** 5x percobaan gagal = blokir 15 menit

---

### Ringkasan Perubahan dari Diagram Asli:

1. **Role User:** Hanya Admin dan Anggota (hapus Pembina)
2. **bukti_kas:** Direktori ada tapi tidak digunakan
3. **API Validation:** Semua form menggunakan API real-time untuk validasi

---

---

## 1. Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ARSITEKTUR SISTEM HADRAH                            │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌─────────────────┐
                              │   Web Browser   │
                              │   (Frontend)    │
                              └────────┬────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           APPLICATION LAYER                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │
│  │   Auth      │  │  Dashboard  │  │   Modules   │  │   Includes  │       │
│  │  (login,    │  │  (admin,    │  │  (absen,    │  │  (header,   │       │
│  │   logout)   │  │   pembina,  │  │   acara,    │  │   footer,   │       │
│  │             │  │   anggota)   │  │   dresscode,│  │   auth)     │       │
│  │             │  │             │  │   alat,     │  │             │       │
│  │             │  │             │  │   keuangan) │  │             │       │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           DATABASE LAYER                                    │
│                    MySQL/MariaDB (hadrahin)                                │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│  │   user   │ │ jadwal_  │ │  absen_  │ │keuangan  │ │  alat    │        │
│  │          │ │ latihan  │ │ latihan  │ │          │ │          │        │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘        │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                                  │
│  │ booking_ │ │dokumentasi│ │ dresscode│                                 │
│  │  acara   │ │  acara   │ │          │                                  │
│  └──────────┘ └──────────┘ └──────────┘                                  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Alur Autentikasi dan Otorisasi

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
                         │     Tampilkan Form      │
                         │     Login (Username,    │
                         │     Password)           │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │     User Submit Form    │
                         │     Login               │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │     Cek Rate Limit      │
                         │     (IP-based)          │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                        TIDAK                     YA
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Validasi Input     │    │ TAMPILKAN PESAN    │
              │ Tidak Kosong?      │    │ BLOKIR:            │
              └─────────┬──────────┘    │ - "Terlalu banyak  │
                        │               │   percobaan login"  │
                        │               │ - Countdown Timer   │
                        │               │ - Form Dinonaktifkan│
                        │               └──────────┬──────────┘
                        │                          │
                        │                          ▼
                        │              ┌────────────────────┐
                        │              │ Auto-refresh Timer │
                        │              │ (15 menit)         │
                        │              └─────────┬──────────┘
                        │                          │
                        │                          ▼
                        │              ┌────────────────────┐
                        │              │ Cek Ulang Rate     │
                        │              │ Limit              │
                        │              └─────────┬──────────┘
                        │                          │
                        └─────────────────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                        YA                        TIDAK
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │  Query Database    │    │  Tampilkan Error   │
              │  Cari User         │    │  "Username dan     │
              └─────────┬──────────┘    │  Password harus    │
                        │               │  diisi!"           │
                        ▼               └────────────────────┘
              ┌────────────────────┐
              │  User Ditemukan?   │
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
│ Password      │  │ "Akun tidak aktif! │
│ (password_    │  │ Hubungi admin"     │
│  verify)      │  └────────────────────┘
└───────┬───────┘
        │
   ┌────┴─────────────────────┐
   │                          │
  BENAR                     SALAH
   │                          │
   ▼                          ▼
┌───────────────┐  ┌────────────────────┐
│ Reset Rate    │  │ Record Failed      │
│ Limit (Sukses)│  │ Attempt & Cek      │
└───────┬───────┘  │ Batas (5x gagal)  │
        │          └─────────┬──────────┘
        │                    │
        │                    ▼
        │          ┌────────────────────┐
        │          │ Tampilkan Error   │
        │          │ "Password salah!" │
        │          └────────────────────┘
        │
        ▼
┌───────────────┐
│ Buat Session  │
│ & Redirect    │
│ Berdasarkan   │
│ Peran User    │
│ - Admin       │
│ - Anggota     │
└───────────────┘
```

> **Catatan:** Rate limit berlaku untuk 5x percobaan gagal, dengan blokir 15 menit.

---

## 3. Alur Redirect Berdasarkan Peran User

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ALUR REDIRECT BERDASARKAN PERAN                          │
└─────────────────────────────────────────────────────────────────────────────┘

                          ┌─────────────────────┐
                          │    Login Berhasil   │
                          └──────────┬──────────┘
                                     │
                                     ▼
                          ┌─────────────────────┐
                          │  Cek Peran User     │
                          │  (session 'peran')  │
                          └──────────┬──────────┘
                                     │
                    ┌─────────────────┴─────────────────┐
                    │                                       │
                    ▼                                       ▼
            ┌───────────┐                           ┌───────────┐
            │   ADMIN   │                           │  ANGGOTA  │
            └─────┬─────┘                           └─────┬─────┘
                  │                                       │
                  ▼                                       ▼
            ┌───────────┐                           ┌───────────┐
            │ dashboard  │                           │ dashboard  │
            │ /admin.php│                           │/anggota.php│
            └───────────┘                           └───────────┘
                  │                                       │
                  ▼                                       ▼
┌───────────────────────────────────────────────────────────────┐
│                    AKSES MENU BERDASARKAN PERAN               │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌────────────────┬────────────────┐                         │
│  │     ADMIN      │     ANGGOTA    │                         │
│  ├────────────────┼────────────────┤                         │
│  │ ✓ Manajemen    │ ✓ Lihat Jadwal │                         │
│  │   User        │   Latihan      │                         │
│  │ ✓ Jadwal      │ ✓ Absensi      │                         │
│  │   Latihan      │ ✓ Booking      │                         │
│  │ ✓ Absensi     │   Acara        │                         │
│  │ ✓ Booking     │ ✓ Dresscode    │                         │
│  │   Acara       │ ✓ Inventaris   │                         │
│  │ ✓ Dresscode  │ ✓ Keuangan     │                         │
│  │ ✓ Inventaris │                │                         │
│  │ ✓ Keuangan   │                │                         │
│  └────────────────┴────────────────┘                         │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

> **Catatan:** Sistem hanya memiliki 2 peran: Admin dan Anggota. Tidak ada role Pembina.

---

## 4. Diagram Alur Modul-Modul Utama

### 4.1 Modul Absensi Latihan

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         MODUL ABSENSI LATIHAN                               │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                      AKSES MENAMPILKAN JADWAL                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Semua user (Admin, Anggota) dapat:                               │
│  ├── Lihat Jadwal Terbaru (1 jadwal terakhir)                              │
│  ├── Lihat Semua Jadwal (semua jadwal latihan)                             │
│  └── Lihat History Jadwal (jadwal yang sudah berlalu)                      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │      Dashboard User     │
                         │   (Akses Menu Absensi)  │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │   Pilih Tampilan:       │
                         │   - Jadwal Terbaru      │
                         │   - Semua Jadwal       │
                         │   - History Jadwal     │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │   Tampilkan Jadwal      │
                         │   Berdasarkan Pilihan   │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │   User Klik Jadwal      │
                         │   (Untuk Melihat Detail)│
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │   Tampilkan Detail      │
                         │   Jadwal (Termasuk      │
                         │   Status Absensi)       │
                         └─────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                    FITUR ABSENSI (ADMIN SAJA)                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ONLY Admin yang dapat melakukan absensi:                             │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Admin mengakses halaman absensi                                     │   │
│  │                                    │                                │   │
│  │                                    ▼                                │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Pilih Jadwal Latihan                                        │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                    │                                │   │
│  │                                    ▼                                │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Tampilkan Daftar Anggota                                   │   │   │
│  │  │  dengan Status Absensi (Hadir/Izin/Alpa/Belum Absen)       │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                    │                                │   │
│  │                                    ▼                                │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Admin dapat:                                       │   │   │
│  │  │  - Klik anggota untuk ubah status absensi                   │   │   │
│  │  │  - Pilih status: Hadir, Izin, Alpa                          │   │   │
│  │  │  - Simpan perubahan                                         │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                    │                                │   │
│  │                                    ▼                                │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Simpan ke Database (tabel absen_latihan)                   │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                    │                                │   │
│  │                                    ▼                                │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Tampilkan Pesan "Absensi Berhasil Disimpan!"              │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Admin juga dapat:                                                │
│  ├── Buat Jadwal Latihan Baru                                               │
│  │   └── Input: Tanggal, Jam, Lokasi, Materi, Catatan                      │
│  ├── Lihat Laporan Absensi Semua Anggota                                    │
│  ├── Export Data Absensi                                                    │
│  └── Kelola Status Jadwal (Rencanakan/Selesai/Dibatalkan)                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Modul Booking Acara

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         MODUL BOOKING ACARA                                 │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                         ALUR BOOKING ACARA                                  │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │     Halaman Booking     │
                         │     (Admin/Pembina)     │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │   Buat Booking     │    │ Lihat Daftar       │
              │   Baru             │    │ Booking            │
              └─────────┬──────────┘    └─────────┬──────────┘
                        │                         │
                        ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Form Booking:      │    │ Tabel Booking:     │
              │ - Nama Acara       │    │ - Semua booking    │
              │ - Nama Pemesan     │    │ - Status (Menunggu/│
              │ - No HP            │    │   Diterima/Ditolak)│
              │ - Tanggal Acara    │    └─────────┬──────────┘
              │ - Jam Mulai        │              │
              │ - Lokasi           │              ▼
              │ - Dresscode        │    ┌────────────────────┐
              │ - Keterangan       │    │ Klik Detail Booking│
              └─────────┬──────────┘    │ untuk melihat      │
                        │               │ lengkap            │
                        ▼               └─────────┬──────────┘
              ┌────────────────────┐              │
              │ Submit Booking     │              ▼
              │ (Status: Menunggu) │    ┌────────────────────┐
              └─────────┬──────────┘    │ Update Status:     │
                        │               │ - Terima           │
                        ▼               │ - Tolak            │
              ┌────────────────────┐    │ - Selesai          │
              │ Simpan ke Database │    └─────────┬──────────┘
              │ (booking_acara)    │              │
              └─────────┬──────────┘              ▼
                        │               ┌────────────────────┐
                        ▼               │ Upload Dokumentasi │
              ┌────────────────────┐    │ (Foto/Video)       │
              │ Tampilkan Pesan    │    └────────────────────┘
              │ "Booking Dikirim!"
              └────────────────────┘877561

┌─────────────────────────────────────────────────────────────────────────────┐
│                         UPLOAD DOKUMENTASI                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Setelah acara selesai, Admin/Pembina dapat mengupload dokumentasi:         │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Form Upload Dokumentasi:                                           │   │
│  │  ├── File Upload (Gambar/Video)                                     │   │
│  │  ├── Tipe File (Foto/Video)                                         │   │
│  │  ├── Keterangan                                                     │   │
│  │  └── Tombol "Upload"                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                        │
│                                    ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Simpan ke Database:                                                │   │
│  │  - tabel: dokumentasi_acara                                         │   │
│  │  - field: id_booking, file_path, keterangan                        │   │
│  │  - file disimpan di: assets/uploads/dokumentasi/                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.3 Modul Inventaris Alat

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         MODUL INVENTARIS ALAT                               │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │     Halaman Inventaris  │
                         │         Alat            │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │   Tambah Alat      │    │ Lihat Daftar       │
              │     Baru           │    │ Alat               │
              └─────────┬──────────┘    └─────────┬──────────┘
                        │                         │
                        ▼                         │
              ┌────────────────────┐              │
              │ Form Tambah Alat:  │              │
              │ - Nama Alat        │              │
              │ - Jumlah Baik      │              │
              │ - Jumlah Rusak     │              │
              │ - Keterangan       │              │
              └─────────┬──────────┘              │
                        │                         │
                        ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Simpan ke Database │    │ Tampilkan Tabel:   │
              │ (tabel alat)       │    │ - Semua alat       │
              └─────────┬──────────┘    │ - Jumlah baik/rusak│
                        │               │ - Total inventaris │
                        ▼               └────────────────────┘
              ┌────────────────────┐
              │ Tampilkan Pesan    │
              │ "Alat Ditambahkan!"│
              └────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                         KELOLA STOK ALAT                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Admin dapat melakukan update stok:                                         │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Form Update Stok:                                                  │   │
│  │  ┌────────────────┐                                                 │   │
│  │  │ Pilih Alat     │                                                 │   │
│  │  └────────────────┘                                                 │   │
│  │       │                                                             │   │
│  │       ▼                                                             │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Input Perubahan:                                             │   │   │
│  │  │  [-] Kurang Alat Rusak   [+][_] Tambah Alat Baru (Baik)     │   │   │
│  │  │  [-] Kurang Alat Baik    [+][_] Tambah Alat Rusak           │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │       │                                                             │   │
│  │       ▼                                                             │   │
│  │  ┌───────────────────────────┐                                     │   │
│  │  │ Update Database           │                                     │   │
│  │  │ jumlah_baik, jumlah_rusak │                                     │   │
│  │  └───────────────────────────┘                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  RINGKASAN STATISTIK ALAT:                                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Total Alat: 24         Baik: 20         Rusak: 4                   │   │
│  │  Kondisi: ████████████████░░░░░░░░░░░░░░░░ 83% Baik                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.4 Modul Keuangan

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           MODUL KEUANGAN                                    │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                      ALUR PENCATATAN KEUANGAN                               │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │     Halaman Keuangan    │
                         │       (Admin)           │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │   Tambah Transaksi │    │ Lihat Laporan      │
              │     Baru           │    │ Keuangan           │
              └─────────┬──────────┘    └─────────┬──────────┘
                        │                         │
                        ▼                         │
              ┌────────────────────┐              │
              │ Form Transaksi:    │              │
              │ - Tipe (Pemasukan/ │              │
              │   Pengeluaran)     │              │
              │ - Kategori         │              │
              │ - Jumlah (Rp)      │              │
              │ - Keterangan       │              │
              │ - Tanggal          │              │
              │ - Bukti Transaksi  │              │
              │   (BELUM ADA     │              │
              │    FITUR UPLOAD) │              │
              └─────────┬──────────┘              │
                        │                         │
                        ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Simpan ke Database │    │ Tampilkan:         │
              │ (tabel keuangan)   │    │ - Saldo Kas        │
              └─────────┬──────────┘    │ - Total Pemasukan  │
                        │               │ - Total Pengeluaran│
                        ▼               │ - Tabel Transaksi  │
              ┌────────────────────┐    └────────────────────┘
              │ Update Saldo Kas   │
              │ Otomatis           │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Tampilkan Pesan    │
              │ "Transaksi         │
              │ Disimpan!"         │
              └────────────────────┘

> **CATATAN PENTING:** 
> - Direktori `assets/uploads/bukti_kas/` **ADA** namun **TIDAK DIGUNAKAN**
> - Saat ini tidak ada fitur upload bukti transaksi
> - Fitur upload bukti kas perlu ditambahkan di masa depan

┌─────────────────────────────────────────────────────────────────────────────┐
│                         LAPORAN KEUANGAN                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  RINGKASAN KEUANGAN                                                  │   │
│  │  ┌─────────────────────┐  ┌─────────────────────┐                   │   │
│  │  │  TOTAL PEMASUKAN    │  │  TOTAL PENGELUARAN  │                   │   │
│  │  │  Rp 15.500.000      │  │  Rp 8.200.000       │                   │   │
│  │  └─────────────────────┘  └─────────────────────┘                   │   │
│  │                                                                      │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  SALDO KAS SAAT INI:                                         │   │   │
│  │  │  ████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░  Rp 7.300.000│   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  KATEGORI TRANSAKSI:                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  PEMASUKAN:           │  PENGELUARAN:                               │   │
│  │  ├── Kas Iuran        │  ├── Pembelian Alat                         │   │
│  │  ├── Honorarium Acara │  ├── Biaya Operasional                      │   │
│  │  ├── Donasi           │  ├── Maintenance Alat                       │   │
│  │  └── Lainnya          │  └── Lainnya                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.5 Modul Dresscode

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           MODUL DRESSCODE                                   │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                    PENGELOLAAN DATA DRESSCODE                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  AKSES: Admin & Pembina                                             │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Admin & Pembina dapat mengelola data dresscode/seragam:                    │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  FITUR:                                                             │   │
│  │  ├── Tambah Dresscode Baru                                          │   │
│  │  ├── Edit Dresscode                                                 │   │
│  │  ├── Nonaktifkan/Aktifkan Dresscode                                 │   │
│  │  └── Hapus Dresscode                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.6 Integrasi dengan Booking Acara

```
┌─────────────────────────────────────────────────────────────────────────────┐
│              DROPBOX DRESSCODE DI FORM BOOKING                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Saat Admin/Pembina membuat booking acara, field dresscode akan            │
│  menggunakan dropdown yang mengambil data dari tabel dresscode:            │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  KEUNTUNGAN:                                                        │   │
│  │  • Data dresscode konsisten dan dapat di-reuse                      │   │
│  │  • Admin cukup update di satu tempat, semua booking terpengaruh     │   │
│  │  • Histori perubahan dresscode tetap terjaga                        │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Alur Logout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            ALUR LOGOUT                                       │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │  User Klik Tombol       │
                         │  "Logout"               │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Hapus Semua Session    │
                         │  (user_id, username,    │
                         │   nama_lengkap, peran)  │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Redirect ke Halaman    │
                         │  Login                  │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Tampilkan Halaman      │
                         │  Login                  │
                         └─────────────────────────┘
```

---

## 6. Relasi Database dalam Konteks Alur Sistem

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    RELASI DATABASE & ALUR SISTEM                            │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│                              ┌─────────────┐                                │
│                              │    user     │                                │
│                              │  (PK: id)   │                                │
│                              └──────┬──────┘                                │
│                                     │                                       │
│              ┌──────────────────────┼──────────────────────┐                │
│              │                      │                      │                │
│              ▼                      ▼                      ▼                │
│      ┌──────────────┐      ┌──────────────┐      ┌──────────────┐         │
│      │   keuangan   │      │ absen_latihan│      │   jadwal_    │         │
│      │   (FK: id)   │      │   (FK: id)   │      │   latihan    │         │
│      │              │      │              │      │              │         │
│      │ - tipe       │      │ - status     │      │ - tanggal    │         │
│      │ - jumlah     │      │ - jam_absen  │      │ - jam_mulai  │         │
│      │ - tanggal    │      │              │      │ - lokasi     │         │
│      └──────────────┘      └──────┬───────┘      └──────────────┘         │
│                                   │                                        │
│                                   ▼                                        │
│                          ┌─────────────────┐                               │
│                          │ jadwal_latihan  │                               │
│                          │   (PK: id_jadwal)│                              │
│                          └─────────────────┘                               │
│                                                                             │
│      ┌─────────────────────────────────────────────────────────────────┐   │
│      │                                                                 │   │
│      │          ┌─────────────────┐                                    │   │
│      │          │  booking_acara  │                                    │   │
│      │          │  (PK: id_booking)│                                   │   │
│      │          └────────┬────────┘                                    │   │
│      │                   │                                             │   │
│      │                   ▼                                             │   │
│      │          ┌─────────────────┐                                    │   │
│      │          │dokumentasi_acara│                                   │   │
│      │          │  (FK: id_booking)│                                  │   │
│      │          └─────────────────┘                                    │   │
│      │                                                                 │   │
│      └─────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│      ┌─────────────────────────────────────────────────────────────────┐   │
│      │                                                                 │   │
│      │                    ┌─────────────┐                              │   │
│      │                    │    alat     │                              │   │
│      │                    │  (PK: id)   │                              │   │
│      │                    │             │                              │   │
│      │                    │ - nama_alat │                              │   │
│      │                    │ - jumlah    │                              │   │
│      │                    └─────────────┘                              │   │
│      │                                                                 │   │
│      │  NOTE: Tabel 'alat' MEMILIKI relasi dengan tabel 'user'     │   │
│      │       (FK: id_user -> user(id_user) ON DELETE SET NULL)                                       │   │
│      │                                                                 │   │
│      └─────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Hak Akses Peran User (Ringkasan)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    MATRIKS HAK AKSES PERAN USER                             │
└─────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────┬──────────┬──────────┐
│            FITUR                   │  ADMIN   │  ANGGOTA │
├────────────────────────────────────┼──────────┼──────────┤
│ AUTENTIKASI                        │          │          │
│ ├─ Login                          │    ✓     │    ✓     │
│ ├─ Logout                         │    ✓     │    ✓     │
│ └─ Reset Password                 │    ✓     │    ✓     │
├────────────────────────────────────┼──────────┼──────────┤
│ MANAJEMEN USER                     │          │          │
│ ├─ Lihat Semua User               │    ✓     │    ✗     │
│ ├─ Tambah User                    │    ✓     │    ✗     │
│ ├─ Edit User                      │    ✓     │    ✗     │
│ ├─ Hapus User                     │    ✓     │    ✗     │
│ └─ Kelola Peran                   │    ✓     │    ✗     │
├────────────────────────────────────┼──────────┼──────────┤
│ JADWAL LATIHAN                     │          │          │
│ ├─ Lihat Jadwal                   │    ✓     │    ✓     │
│ ├─ Buat Jadwal                    │    ✓     │    ✗     │
│ ├─ Edit Jadwal                    │    ✓     │    ✗     │
│ ├─ Hapus Jadwal                   │    ✓     │    ✗     │
│ └─ Ganti Status Jadwal            │    ✓     │    ✗     │
├────────────────────────────────────┼──────────┼──────────┤
│ ABSENSI                            │          │          │
│ ├─ Lihat Jadwal Terbaru            │    ✓     │    ✓     │
│ ├─ Lihat Semua Jadwal              │    ✓     │    ✓     │
│ ├─ Lihat History Jadwal            │    ✓     │    ✓     │
│ ├─ Lihat Detail Jadwal             │    ✓     │    ✓     │
│ ├─ Input Absensi Anggota           │    ✓     │    ✗     │
│ ├─ Lihat Absensi Semua Anggota    │    ✓     │    ✗     │
│ └─ Export Data Absensi            │    ✓     │    ✗     │
├────────────────────────────────────┼──────────┼──────────┤
│ BOOKING ACARA                      │          │          │
│ ├─ Lihat Booking                  │    ✓     │    ✓     │
│ ├─ Buat Booking                   │    ✓     │    ✗     │
│ ├─ Update Status Booking          │    ✓     │    ✗     │
│ ├─ Upload Dokumentasi             │    ✓     │    ✗     │
│ └─ Hapus Booking                  │    ✓     │    ✗     │
├────────────────────────────────────┼──────────┼──────────┤
│ INVENTARIS ALAT                    │          │          │
│ ├─ Lihat Inventaris               │    ✓     │    ✓     │
│ ├─ Tambah Alat                    │    ✓     │    ✗     │
│ ├─ Edit Stok Alat                 │    ✓     │    ✗     │
│ └─ Hapus Alat                     │    ✓     │    ✗     │
├────────────────────────────────────┼──────────┼──────────┤
│ KEUANGAN                           │          │          │
│ ├─ Lihat Laporan Keuangan         │    ✓     │    ✓     │
│ ├─ Tambah Transaksi               │    ✓     │    ✗     │
│ ├─ Edit Transaksi                 │    ✓     │    ✗     │
│ ├─ Hapus Transaksi                │    ✓     │    ✗     │
│ └─ Export Laporan                 │    ✓     │    ✗     │
└────────────────────────────────────┴──────────┴──────────┘

KETERANGAN:
✓ = Akses Diizinkan
✗ = Akses Ditolak

> **Catatan Penting:** Sistem hanya memiliki 2 peran (Admin dan Anggota). Tidak ada role Pembina.
```

> **Perubahan dari diagram asli:** Kolom "Pembina" dihapus karena sistem saat ini tidak memiliki role tersebut.

---

## 8. Flowchart Keseluruhan Sistem

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    FLOWCHART KESELURUHAN SISTEM                            │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌─────────────┐
                              │    START    │
                              └──────┬──────┘
                                     │
                                     ▼
                         ┌───────────────────────┐
                         │  Buka Aplikasi Web    │
                         │  (Browser)            │
                         └───────────┬───────────┘
                                     │
                                     ▼
                         ┌───────────────────────┐
                         │   Cek Session User    │
                         │   (Apakah sudah login?)│
                         └───────────┬───────────┘
                                     │
                    ┌────────────────┴────────────────┐
                    │                                 │
                   BELUM                              SUDAH
                    │                                 │
                    ▼                                 ▼
         ┌─────────────────────┐        ┌─────────────────────┐
         │ Tampilkan Halaman   │        │ Tampilkan Dashboard │
         │ Login               │        │ Berdasarkan Peran   │
         └──────────┬──────────┘        └──────────┬──────────┘
                    │                               │
                    ▼                               │
         ┌─────────────────────┐                   │
         │ User Submit Form    │                   │
         │ Login               │                   │
         └──────────┬──────────┘                   │
                    │                              │
                    ▼                              │
         ┌─────────────────────┐                   │
         │  Rate Limiting      │                   │
         │  (Cek IP & Count)   │                   │
         └──────────┬──────────┘                   │
                    │                              │
                    ▼                              │
         ┌─────────────────────┐                   │
         │ Diblokir? (12x gagal)│                  │
         └──────────┬──────────┘                   │
                    │                              │
                    ▼                              │
         ┌─────────────────────┐                   │
         │ Tampil Countdown    │                   │
         │ (5/10/15 menit)     │                   │
         └─────────────────────┘                   │
                    │                              │
                    ▼                              │
         ┌─────────────────────┐                   │
         │ Validasi & Verifikasi│                  │
         │ Database            │                   │
         └──────────┬──────────┘                   │
                    │                              │
         ┌──────────┴──────────┐                   │
         │                     │                   │
        VALID              INVALID                 │
         │                     │                   │
         ▼                     ▼                   │
┌─────────────────┐  ┌─────────────────┐           │
│ Reset Rate      │  │ Record Failed   │           │
│ Limit & Buat    │  │ Attempt         │           │
│ Session          │  └─────────────────┘           │
└────────┬────────┘                                  │
         │                                          │
         ▼                                          │
┌─────────────────┐                                 │
│ Dashboard       │                                 │
│ (Admin/Pembina/ │◄────────────────────────────────┘
│ Anggota)        │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│                    MENU UTAMA                           │
│  ┌─────────┬─────────┬─────────┬─────────┬─────────┐   │
│  │ Absensi │  Acara  │ Inventaris│Keuangan │  User  │   │
│  └─────────┴─────────┴─────────┴─────────┴─────────┘   │
└─────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│                    AKSI USER                             │
│                                                         │
│  • Akses Modul sesuai hak akses                         │
│  • Input/View Data                                      │
│  • Logout                                               │
│                                                         │
└─────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────┐
│                    END / LOGOUT                         │
└─────────────────────────────────────────────────────────┘
```

---

## 9. Catatan Implementasi

Dokumen diagram alur ini dibuat berdasarkan analisis struktur file aplikasi yang mencakup file-file utama seperti `config/database.php` untuk koneksi database, `auth/login.php` untuk sistem autentikasi, `dashboard/admin.php` untuk dashboard admin, serta struktur folder `modules/` yang berisi modul-modul aplikasi. Aplikasi dibangun menggunakan PHP dengan database MySQL/MariaDB dan menggunakan session untuk manajemen autentikasi pengguna. Sistem mengimplementasikan konsep Role-Based Access Control (RBAC) dengan tiga peran utama yaitu admin, pembina, dan anggota, masing-masing dengan hak akses yang berbeda terhadap fitur-fitur aplikasi.

### Rate Limiting (Keamanan Login)

Aplikasi ini dilengkapi dengan sistem rate limiting untuk mencegah brute force attack pada halaman login. Mekanisme kerja:

| Percobaan Gagal | Durasi Blokir |
|-----------------|---------------|
| 12x | 5 menit |
| 24x | 10 menit |
| 36x | 15 menit |

Sistem ini:
- ✅ Membatasi percobaan login berdasarkan IP address
- ✅ Menampilkan countdown timer real-time saat diblokir
- ✅ Otomatis mereset counter saat login berhasil
- ✅ Membersihkan data lama (>24 jam) secara otomatis

File terkait: `includes/rate_limit.php`, `logs/login_attempts.dat`

---

## 10. Referensi File Terkait

- File Database: `config/database.php`, `config/hadrahin.sql`
- File Autentikasi: `auth/login.php`, `auth/logout.php`
- File Keamanan: `includes/rate_limit.php`
- File Dashboard: `dashboard/admin.php`, `dashboard/anggota.php`, `dashboard/pembina.php`
- File Include: `includes/header.php`, `includes/auth_check.php`
- ER Diagram: `MD/ER_DIAGRAM.md`
- Rate Limiting Docs: `MD/RATE_LIMITING.md`

---

*Diagram ini dibuat berdasarkan struktur aplikasi yang ada di folder /opt/lampp/htdocs/hadrahin*


## 11. Diagram Use Case

Dokumen ini menjelaskan use case atau kasus penggunaan untuk setiap fitur dalam aplikasi sistem informasi grup hadrah, mencakup interaksi antara aktor dengan sistem untuk mencapai tujuan tertentu.

### 11.1 Definisi Aktor

Aktor adalah entitas yang berinteraksi dengan sistem. Dalam aplikasi ini terdapat tiga aktor utama yang memiliki peran dan akses yang berbeda-beda. Aktor Admin adalah pengguna dengan akses tertinggi yang dapat mengakses dan mengelola seluruh fitur sistem termasuk manajemen user, jadwal latihan, booking acara, inventaris alat, keuangan, dan dresscode. Aktor Pembina adalah pengguna dengan akses menengah yang dapat mengakses fitur-fitur terkait pelaksanaan kegiatan seperti jadwal latihan, absensi, booking acara, inventaris (hanya lihat), dan keuangan (hanya lihat laporan), namun tidak dapat mengelola data user atau transaksi keuangan. Aktor Anggota adalah pengguna dengan akses terbatas yang hanya dapat mengakses fitur-fitur untuk keperluan pribadi seperti melihat jadwal latihan, melihat booking acara, dan melihat laporan keuangan, namun tidak dapat membuat atau mengubah data sistem.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         DEFINISI AKTOR DALAM SISTEM                         │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌─────────────────┐
    │     ADMIN       │
    ├─────────────────┤
    │ • Akses penuh  │
    │ • Kelola User  │
    │ • Kelola Sistem│
    └────────┬────────┘
             │
             │ Inheritance
             ▼
    ┌─────────────────┐
    │    PEMBINA      │
    ├─────────────────┤
    │ • Akses menengah│
    │ • Kelola Jadwal│
    │ • Kelola Absensi│
    └────────┬────────┘
             │
             │ Inheritance
             ▼
    ┌─────────────────┐
    │    ANGGOTA      │
    ├─────────────────┤
    │ • Akses terbatas│
    │ • Lihat Jadwal  │
    │ • Lihat Data    │
    └─────────────────┘
```

### 11.2 Use Case Autentikasi

#### 11.2.1 Login

Use case login digunakan oleh semua aktor (Admin, Pembina, Anggota) untuk mengakses sistem. Skenario utama dimulai ketika aktor membuka halaman login dan melihat form yang meminta input username dan password. Aktor mengisi form dan menekan tombol login. Sistem kemudian memvalidasi input dan memeriksa apakah akun tersebut ada di database serta apakah statusnya aktif. Jika validasi berhasil, sistem membuat session dan mengarahkan aktor ke dashboard sesuai dengan perannya. Jika validasi gagal karena password salah, sistem menampilkan pesan error dan mengurangi kesempatan login. Jika akun tidak ditemukan, sistem menampilkan pesan bahwa username tidak ditemukan. Jika akun tidak aktif, sistem menampilkan pesan bahwa akun perlu diaktifkan oleh admin.

#### 11.2.2 Logout

Use case logout digunakan oleh semua aktor untuk keluar dari sistem. Skenario dimulai ketika aktor menekan tombol logout di menu. Sistem menghapus semua session yang aktif dan mengarahkan aktor kembali ke halaman login. Tidak ada kondisi khusus dalam use case ini karena logout selalu berhasil.

#### 11.2.3 Reset Password

Use case reset password digunakan oleh semua aktor yang lupa password. Skenario dimulai ketika aktor menekan link "Lupa Password" di halaman login. Sistem menampilkan form untuk input username atau email. Aktor mengisi dan mengirimkan form. Sistem memverifikasi bahwa akun ada dan aktif, kemudian mengirimkan link reset password ke email yang terdaftar atau menampilkan pertanyaan keamanan. Aktor mengklik link reset dan mengisi form password baru. Sistem memvalidasi kekuatan password dan menyimpannya ke database dengan enkripsi yang baru.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        USE CASE AUTENTIKASI                                 │
└─────────────────────────────────────────────────────────────────────────────┘

                           ┌─────────────────────┐
                           │     <<actor>>       │
                           │     User (All)      │
                           └──────────┬──────────┘
                                      │
                                      │
                                      │
    ┌─────────────────────────────────┼─────────────────────────────────┐
    │                                 │                                 │
    │                                 │                                 │
    │         ┌───────────────────────┼───────────────────────┐         │
    │         │                       │                       │         │
    │         │        LOGIN          │      LOGOUT           │         │
    │         │                       │                       │         │
    │         │  <<include>>          │  <<extend>>           │         │
    │         │  - Rate Limit Check   │  - Session Destroy    │         │
    │         │  - Validation         │  - Redirect           │         │
    │         │  - Session Create     │                       │         │
    │         │  - Role Redirect      │                       │         │
    │         │                       │                       │         │
    │         └───────────────────────┴───────────────────────┘         │
    │                                 │                                 │
    │                                 │                                 │
    │                                 │                                 │
    │                                 ▼                                 │
    │                        ┌─────────────────┐                        │
    │                        │  RESET PASSWORD │                        │
    │                        │                 │                        │
    │                        │  - Verify User  │                        │
    │                        │  - Send Reset   │                        │
    │                        │    Link         │                        │
    │                        │  - Update       │                        │
    │                        │    Password     │                        │
    │                        └─────────────────┘                        │
    │                                                                      │
    └──────────────────────────────────────────────────────────────────────┘
```

### 11.3 Use Case Manajemen User

Manajemen user hanya dapat diakses oleh Admin. Use case ini mencakup penambahan user baru, pengeditan data user, penghapusan user, dan pengelolaan peran user.

#### 11.3.1 Tambah User

Use case tambah user dimulai ketika Admin mengakses menu manajemen user dan memilih opsi tambah user. Sistem menampilkan form dengan kolom username, password, nama lengkap, no HP, dan peran. Admin mengisi form dan mengirimkan data. Sistem memvalidasi bahwa username belum digunakan dan semua data wajib diisi. Jika validasi berhasil, sistem menampilkan **modal konfirmasi** untuk memastikan data yang dimasukkan sudah benar. Jika Admin mengkonfirmasi, sistem menyimpan data ke database dengan status aktif default. Jika validasi gagal, sistem menampilkan pesan error dan meminta input yang benar.

#### 11.3.2 Edit User

Use case edit user dimulai ketika Admin memilih user yang akan diedit dari daftar user. Sistem menampilkan form yang sudah terisi dengan data user tersebut. Admin mengubah data yang diperlukan dan mengirimkan form. Sistem memvalidasi perubahan dan menampilkan **modal konfirmasi** untuk memastikan perubahan yang dilakukan sudah benar. Jika Admin mengkonfirmasi, sistem menyimpan perubahan ke database dengan mengupdate timestamp dan user yang melakukan modifikasi.

#### 11.3.3 Hapus User

Use case hapus user dimulai ketika Admin memilih user yang akan dihapus dari daftar user. Sistem menampilkan konfirmasi. Jika Admin mengkonfirmasi, sistem menghapus data user dari database. Relasi foreign key di tabel lain akan ditangani sesuai dengan aturan ON DELETE yang ditetapkan di database.

#### 11.3.4 Kelola Status Aktif

Use case kelola status aktif memungkinkan Admin untuk mengaktifkan atau menonaktifkan akun user. Ketika akun dinonaktifkan, user tersebut tidak dapat login ke sistem meskipun username dan password benar.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       USE CASE MANAJEMEN USER                               │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌─────────────────────┐
                              │       <<actor>>     │
                              │        ADMIN        │
                              └──────────┬──────────┘
                                         │
                                         │ akses menu user
                                         ▼
                         ┌────────────────────────────────┐
                         │      MANAJEMEN USER            │
                         └────────────────┬───────────────┘
                                          │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
                    ▼                      ▼                      ▼
         ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
         │    TAMBAH USER    │  │    EDIT USER      │  │   HAPUS USER      │
         │                   │  │                   │  │                   │
         │ - Input Data      │  │ - Select User     │  │ - Select User     │
         │ - Validate        │  │ - Update Data     │  │ - Konfirmasi      │
         │ - Modal Konfirmasi│  │ - Modal Konfirmasi│  │ - Delete from DB  │
         │ - Save to DB      │  │ - Save Changes    │  │                   │
         └───────────────────┘  └───────────────────┘  └───────────────────┘
                    │                      │                      │
                    └──────────────────────┼──────────────────────┘
                                         │
                                         ▼
                         ┌────────────────────────────────┐
                         │    KELOLA STATUS AKTIF         │
                         │                                │
                         │  - Aktifkan Akun               │
                         │  - Nonaktifkan Akun            │
                         └────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                         MODAL KONFIRMASI                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Saat Admin submit form Tambah/Edit User, tampil modal konfirmasi:         │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    KONFIRMASI DATA USER                             │   │
│  ├─────────────────────────────────────────────────────────────────────┤   │
│  │                                                                      │   │
│  │  Apakah data berikut sudah benar?                                   │   │
│  │                                                                      │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  Username:  example_user                                    │   │   │
│  │  │  Nama:      John Doe                                        │   │   │
│  │  │  No HP:     081234567890                                    │   │   │
│  │  │  Peran:     ANGGOTA                                         │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                                                      │   │
│  │              ┌─────────────────┐    ┌─────────────────┐            │   │
│  │              │    BATAL        │    │    KONFIRMASI   │            │   │
│  │              └─────────────────┘    └─────────────────┘            │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ALUR MODAL KONFIRMASI:                                                    │
│  1. Admin klik "Simpan" pada form                                          │
│  2. Tampilkan modal konfirmasi dengan preview data                         │
│  3. Admin pilih:                                                           │
│     - BATAL: Kembali ke form, data tidak tersimpan                        │
│     - KONFIRMASI: Simpan data ke database                                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 11.4 Use Case Jadwal Latihan

#### 11.4.1 Lihat Jadwal Latihan

Use case lihat jadwal latihan dapat diakses oleh semua aktor (Admin, Pembina, Anggota). Skenario dimulai ketika aktor mengakses menu jadwal latihan. Sistem menampilkan daftar jadwal latihan yang terurut berdasarkan tanggal dan waktu. Jadwal yang sudah lewat akan ditandai statusnya, sedangkan jadwal yang akan datang masih berstatus rencana. Admin dan Pembina dapat melihat semua jadwal, sementara Anggota hanya dapat melihat jadwal yang relevan.

#### 11.4.2 Buat Jadwal Latihan

Use case buat jadwal latihan dapat dilakukan oleh Admin dan Pembina. Skenario dimulai ketika Admin atau Pembina memilih opsi tambah jadwal. Sistem menampilkan form dengan kolom tanggal, jam mulai, jam selesai, lokasi, materi, dan catatan. Admin atau Pembina mengisi form dan mengirimkan. Sistem memvalidasi data dan menyimpan ke database dengan status default "Rencanakan". Sistem menampilkan pesan sukses dan memperbarui daftar jadwal.

#### 11.4.3 Edit Jadwal Latihan

Use case edit jadwal latihan dapat dilakukan oleh Admin dan Pembina. Skenario dimulai ketika Admin atau Pembina memilih jadwal yang akan diedit. Sistem menampilkan form yang sudah terisi dengan data jadwal tersebut. Admin atau Pembina mengubah data yang diperlukan dan mengirimkan form. Sistem memvalidasi dan menyimpan perubahan.

#### 11.4.4 Hapus Jadwal Latihan

Use case hapus jadwal latihan hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih jadwal yang akan dihapus. Sistem menampilkan konfirmasi. Jika dikonfirmasi, sistem menghapus jadwal dan semua data absensi yang terkait dengan jadwal tersebut karena foreign key on delete cascade.

#### 11.4.5 Ganti Status Jadwal

Use case ganti status jadwal dapat dilakukan oleh Admin dan Pembina. Status jadwal meliputi "Rencanakan" (jadwal baru dibuat), "Selesai" (latihan telah dilaksanakan), dan "Dibatalkan" (latihan dibatalkan). Perubahan status mempengaruhi tampilan di dashboard dan laporan absensi.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      USE CASE JADWAL LATIHAN                                │
└─────────────────────────────────────────────────────────────────────────────┘

              ┌─────────────────────────────────────────────────────────────┐
              │                      <<actor>>                              │
              │  ┌───────────┐  ┌───────────┐  ┌───────────┐               │
              │  │  ADMIN    │  │  PEMBINA  │  │  ANGGOTA  │               │
              │  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘               │
              └────────┼──────────────┼──────────────┼─────────────────────┘
                       │              │              │
                       │              │              │ lihat jadwal
                       │              │              ▼
                       │              │    ┌───────────────────────────┐
                       │              │    │    LIHAT JADWAL           │
                       │              │    │                           │
                       │              │    │  - Tampilkan Semua Jadwal │
                       │              │    │  - Urutkan Tanggal        │
                       │              │    │  - Tandai Status          │
                       │              │    └───────────────────────────┘
                       │              │
                       │              │
                       ▼              ▼
             ┌───────────────────────────────────────────┐
             │           BUAT JADWAL                     │
             │                                           │
             │  <<include>>                              │
             │  - Validasi Input                         │
             │  - Simpan ke Database                     │
             │  - Kirim Notifikasi (opsional)            │
             └─────────────────────┬─────────────────────┘
                                   │
                                   ▼
             ┌───────────────────────────────────────────┐
             │           EDIT JADWAL                     │
             │                                           │
             │  - Select Jadwal                          │
             │  - Update Data                            │
             │  - Simpan Perubahan                       │
             └─────────────────────┬─────────────────────┘
                                   │
             ┌─────────────────────┴─────────────────────┐
             │                                             │
             │                                             ▼
             │                         ┌───────────────────────────────────┐
             │                         │           HAPUS JADWAL            │
             │                         │        (Admin Only)               │
             │                         │                                   │
             │                         │  - Select Jadwal                  │
             │                         │  - Konfirmasi                     │
             │                         │  - Hapus (Cascade Delete)         │
             │                         └───────────────────────────────────┘
             │
             ▼
             ┌───────────────────────────────────────────┐
             │         GANTI STATUS JADWAL               │
             │                                           │
             │  - Rencanakan  →  Selesai                 │
             │  - Rencanakan  →  Dibatalkan              │
             │  - Selesai     →  (Tidak dapat diubah)    │
             └───────────────────────────────────────────┘
```

### 11.5 Use Case Absensi Latihan

#### 11.5.1 Lihat Jadwal Latihan

Use case lihat jadwal latihan dapat diakses oleh semua aktor (Admin, Pembina, Anggota). Skenario dimulai ketika aktor mengakses menu absensi. Sistem menampilkan tiga pilihan tampilan:
- **Jadwal Terbaru**: Menampilkan 1 jadwal latihan terakhir
- **Semua Jadwal**: Menampilkan semua jadwal latihan
- **History Jadwal**: Menampilkan jadwal yang sudah berlalu

#### 11.5.2 Input Absensi Anggota

Use case input absensi anggota dapat dilakukan oleh Admin dan Pembina. Skenario dimulai ketika Admin atau Pembina memilih jadwal latihan. Sistem menampilkan daftar anggota dengan status absensi saat ini (Hadir/Izin/Alpa/Belum Absen). Admin atau Pembina dapat mengubah status absensi anggota dan menyimpan perubahan. Sistem menyimpan data ke database tabel absen_latihan dan memperbarui laporan absensi.

#### 11.5.3 Lihat Laporan Absensi

Use case lihat laporan absensi dapat diakses oleh Admin dan Pembina. Admin dan Pembina dapat melihat laporan absensi semua anggota untuk setiap jadwal.

#### 11.5.4 Export Data Absensi

Use case export data absensi dapat dilakukan oleh Admin dan Pembina. Sistem menghasilkan file export dalam format CSV atau Excel yang berisi data absensi berdasarkan kriteria yang dipilih seperti range tanggal atau jadwal tertentu.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         USE CASE ABSENSI LATIHAN                            │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌────────────────────────────────────────────────────────────────────────┐
    │                         <<actor>>                                      │
    │  ┌───────────┐  ┌───────────┐  ┌───────────┐                          │
    │  │  ADMIN    │  │  PEMBINA  │  │  ANGGOTA  │                          │
    │  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘                          │
    └────────┼──────────────┼──────────────┼────────────────────────────────┘
             │              │              │
             │              │              │ akses menu absensi
             │              │              ▼
             │              │    ┌─────────────────────────────┐
             │              │    │    LIHAT JADWAL LATIHAN    │
             │              │    │                             │
             │              │    │  - Jadwal Terbaru (1)      │
             │              │    │  - Semua Jadwal            │
             │              │    │  - History Jadwal          │
             │              │    └─────────────────────────────┘
             │              │
             │              │
             ▼              ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                    INPUT ABSENSI ANGGOTA                           │
    │                    (Admin & Pembina)                                │
    │                                                                    │
    │  - Select Jadwal                                                   │
    │  - Tampilkan Daftar Anggota                                        │
    │  - Ubah Status Absensi                                             │
    │  - Simpan                                                         │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                    LAPORAN ABSENSI                                 │
    │                    (Admin & Pembina)                                │
    │                                                                    │
    │  - Lihat Semua Absensi                                             │
    │  - Filter per Jadwal                                               │
    │  - Export Data                                                     │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                    EXPORT ABSENSI                                  │
    │                    (Admin & Pembina)                                │
    │                                                                    │
    │  - Pilih Format (CSV/Excel)                                        │
    │  - Pilih Periode                                                   │
    │  - Download                                                        │
    └────────────────────────────────────────────────────────────────────┘
```

### 11.6 Use Case Booking Acara

#### 11.6.1 Buat Booking Acara

Use case buat booking acara dapat dilakukan oleh Admin dan Pembina. Skenario dimulai ketika Admin atau Pembina memilih opsi booking baru. Sistem menampilkan form dengan kolom nama acara, nama pemesan, no HP, tanggal acara, jam mulai, jam selesai, lokasi, dresscode (dropdown dari tabel dresscode), dan keterangan. Admin atau Pembina mengisi form dan mengirimkan. Sistem memvalidasi data dan menyimpan dengan status default "Menunggu". Sistem menampilkan pesan sukses bahwa booking telah dikirim dan menunggu persetujuan.

#### 11.6.2 Lihat Daftar Booking

Use case lihat daftar booking dapat diakses oleh semua aktor. Admin dan Pembina dapat melihat semua booking dengan status apapun. Anggota hanya dapat melihat booking yang sudah diterima atau selesai.

#### 11.6.3 Update Status Booking

Use case update status booking dapat dilakukan oleh Admin dan Pembina. Status booking meliputi "Menunggu" (booking baru, perlu verifikasi), "Diterima" (booking disetujui), "Ditolak" (booking ditolak), dan "Selesai" (acara telah dilaksanakan).

#### 11.6.4 Upload Dokumentasi

Use case upload dokumentasi dapat dilakukan oleh Admin dan Pembina setelah acara selesai. Skenario dimulai ketika Admin atau Pembina memilih booking yang berstatus selesai atau ingin menambahkan dokumentasi. Sistem menampilkan form upload dengan pilihan file gambar atau video. Admin atau Pembina memilih file dan mengisi keterangan. Sistem mengupload file ke folder assets/uploads/dokumentasi/ dan menyimpan path ke database tabel dokumentasi_acara.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          USE CASE BOOKING ACARA                             │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌────────────────────────────────────────────────────────────────────────┐
    │                         <<actor>>                                      │
    │  ┌───────────┐  ┌───────────┐  ┌───────────┐                          │
    │  │  ADMIN    │  │  PEMBINA  │  │  ANGGOTA  │                          │
    │  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘                          │
    └────────┼──────────────┼──────────────┼────────────────────────────────┘
             │              │              │
             │              │              │ lihat booking
             │              │              ▼
             │              │    ┌─────────────────────────────┐
             │              │    │      LIHAT BOOKING          │
             │              │    │                             │
             │              │    │  - Semua Status (Admin)     │
             │              │    │  - Accepted/Done (Anggota)  │
             │              │    └─────────────────────────────┘
             │              │
             │              │
             ▼              ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                      BUAT BOOKING ACARA                            │
    │                      (Admin & Pembina)                              │
    │                                                                    │
    │  <<include>>                                                        │
    │  - Validasi Input                                                   │
    │  - Get Dresscode dari DB                                            │
    │  - Simpan Booking (Status: Menunggu)                                │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                    UPDATE STATUS BOOKING                           │
    │                    (Admin & Pembina)                                │
    │                                                                    │
    │  - Menunggu → Diterima                                              │
    │  - Menunggu → Ditolak                                               │
    │  - Diterima → Selesai                                               │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                    UPLOAD DOKUMENTASI                              │
    │                    (Admin & Pembina)                                │
    │                                                                    │
    │  - Select Booking                                                   │
    │  - Upload File (Gambar/Video)                                       │
    │  - Simpan Path ke DB                                                │
    │  - File disimpan di:                                                │
    │    assets/uploads/dokumentasi/                                     │
    └────────────────────────────────────────────────────────────────────┘
```

### 11.7 Use Case Inventaris Alat

#### 11.7.1 Lihat Inventaris

Use case lihat inventaris dapat diakses oleh semua aktor. Admin dan Pembina dapat melihat daftar lengkap alat beserta jumlah baik dan rusaknya. Anggota hanya dapat melihat daftar alat tanpa akses edit.

#### 11.7.2 Tambah Alat

Use case tambah alat hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih opsi tambah alat. Sistem menampilkan form dengan kolom nama alat, jumlah baik, jumlah rusak, dan keterangan. Admin mengisi form dan mengirimkan. Sistem menyimpan data ke database tabel alat.

#### 11.7.3 Edit Stok Alat

Use case edit stok alat hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih alat yang akan diupdate. Sistem menampilkan form dengan kolom untuk menambah atau mengurangi jumlah baik dan jumlah rusak. Admin menyesuaikan stok dan menyimpan. Sistem mengupdate data dan memperbarui total inventaris.

#### 11.7.4 Hapus Alat

Use case hapus alat hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih alat yang akan dihapus. Sistem menampilkan konfirmasi. Jika dikonfirmasi, sistem menghapus data alat dari database.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        USE CASE INVENTARIS ALAT                             │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌────────────────────────────────────────────────────────────────────────┐
    │                         <<actor>>                                      │
    │  ┌───────────┐  ┌───────────┐  ┌───────────┐                          │
    │  │  ADMIN    │  │  PEMBINA  │  │  ANGGOTA  │                          │
    │  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘                          │
    └────────┼──────────────┼──────────────┼────────────────────────────────┘
             │              │              │
             │              │              │ lihat inventaris
             │              │              ▼
             │              │    ┌─────────────────────────────┐
             │              │    │      LIHAT INVENTARIS       │
             │              │    │                             │
             │              │    │  - Tampilkan Semua Alat     │
             │              │    │  - Jumlah Baik/Rusak        │
             │              │    │  - Total Statistics         │
             │              │    └─────────────────────────────┘
             │              │
             │              │
             ▼              ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                         TAMBAH ALAT                                 │
    │                      (Admin Only)                                   │
    │                                                                    │
    │  - Input Nama Alat                                                  │
    │  - Input Jumlah Baik                                                │
    │  - Input Jumlah Rusak                                               │
    │  - Input Keterangan                                                 │
    │  - Simpan                                                           │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                      EDIT STOK ALAT                                 │
    │                      (Admin Only)                                   │
    │                                                                    │
    │  - Select Alat                                                      │
    │  - Tambah/Kurang Stok                                               │
    │  - Update Jumlah Baik                                               │
    │  - Update Jumlah Rusak                                              │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                         HAPUS ALAT                                  │
    │                      (Admin Only)                                   │
    │                                                                    │
    │  - Select Alat                                                      │
    │  - Konfirmasi                                                       │
    │  - Delete dari Database                                             │
    └────────────────────────────────────────────────────────────────────┘
```

### 11.8 Use Case Keuangan

#### 11.8.1 Lihat Laporan Keuangan

Use case lihat laporan keuangan dapat diakses oleh semua aktor. Admin dapat melihat laporan lengkap dengan semua transaksi. Pembina dan Anggota hanya dapat melihat ringkasan keuangan tanpa detail transaksi sensitif.

#### 11.8.2 Tambah Transaksi

Use case tambah transaksi hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih opsi tambah transaksi. Sistem menampilkan form dengan kolom tipe (Pemasukan/Pengeluaran), kategori, jumlah, keterangan, tanggal, dan upload bukti transaksi (opsional). Admin mengisi form dan mengupload file jika ada. Sistem menyimpan data ke database tabel keuangan, mengupdate saldo kas secara otomatis, dan menyimpan file bukti ke folder assets/uploads/bukti_kas/.

#### 11.8.3 Edit Transaksi

Use case edit transaksi hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih transaksi yang akan diedit. Sistem menampilkan form yang terisi dengan data transaksi. Admin mengubah data yang diperlukan dan menyimpan. Sistem memperbarui data dan menyesuaikan saldo kas jika jumlah berubah.

#### 11.8.4 Hapus Transaksi

Use case hapus transaksi hanya dapat dilakukan oleh Admin. Skenario dimulai ketika Admin memilih transaksi yang akan dihapus. Sistem menampilkan konfirmasi. Jika dikonfirmasi, sistem menghapus data transaksi dan menyesuaikan saldo kas dengan mengurangi nilai transaksi yang dihapus.

#### 11.8.5 Export Laporan

Use case export laporan dapat dilakukan oleh Admin. Sistem menghasilkan file laporan keuangan dalam format CSV atau PDF yang mencakup ringkasan saldo, total pemasukan, total pengeluaran, dan daftar transaksi.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           USE CASE KEUANGAN                                 │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌────────────────────────────────────────────────────────────────────────┐
    │                         <<actor>>                                      │
    │  ┌───────────┐  ┌───────────┐  ┌───────────┐                          │
    │  │  ADMIN    │  │  PEMBINA  │  │  ANGGOTA  │                          │
    │  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘                          │
    └────────┼──────────────┼──────────────┼────────────────────────────────┘
             │              │              │
             │              │              │ lihat laporan
             │              │              ▼
             │              │    ┌─────────────────────────────┐
             │              │    │    LAPORAN KEUANGAN         │
             │              │    │                             │
             │              │    │  - Saldo Kas                │
             │              │    │  - Total Pemasukan          │
             │              │    │  - Total Pengeluaran        │
             │              │    │  - Grafik (Admin Only)      │
             │              │    └─────────────────────────────┘
             │              │
             │              │
             ▼              ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                      TAMBAH TRANSAKSI                              │
    │                      (Admin Only)                                   │
    │                                                                    │
    │  - Select Tipe (Pemasukan/Pengeluaran)                             │
    │  - Select Kategori                                                 │
    │  - Input Jumlah (Rp)                                               │
    │  - Input Keterangan                                                │
    │  - Input Tanggal                                                   │
    │  - Upload Bukti (Opsional)                                         │
    │  <<include>>                                                       │
    │  - Update Saldo Otomatis                                           │
    │  - Simpan File Bukti                                               │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  ▼
    ┌────────────────────────────────────────────────────────────────────┐
    │                      EDIT TRANSAKSI                                │
    │                      (Admin Only)                                   │
    │                                                                    │
    │  - Select Transaksi                                                │
    │  - Update Data                                                     │
    │  - Recalculate Saldo                                               │
    └─────────────────────────────┬─────────────────────────────────────┘
                                  │
             ┌────────────────────┴────────────────────┐
             │                                          │
             ▼                                          ▼
    ┌─────────────────────────────┐    ┌────────────────────────────────┐
    │      HAPUS TRANSAKSI        │    │      EXPORT LAPORAN            │
    │      (Admin Only)           │    │      (Admin Only)              │
    │                             │    │                                │
    │  - Select Transaksi         │    │  - Pilih Format (CSV/PDF)      │
    │  - Konfirmasi               │    │  - Pilih Periode               │
    │  - Delete & Adjust Saldo    │    │  - Download                    │
    └─────────────────────────────┘    └────────────────────────────────┘
```

### 11.9 Matriks Use Case per Aktor

Matriks berikut merangkum use case yang tersedia untuk setiap aktor dalam sistem.

| Use Case | Admin | Pembina | Anggota |
|----------|-------|---------|---------|
| **Autentikasi** | | | |
| Login | ✓ | ✓ | ✓ |
| Logout | ✓ | ✓ | ✓ |
| Reset Password | ✓ | ✓ | ✓ |
| **Manajemen User** | | | |
| Lihat Semua User | ✓ | ✗ | ✗ |
| Tambah User | ✓ | ✗ | ✗ |
| Edit User | ✓ | ✗ | ✗ |
| Hapus User | ✓ | ✗ | ✗ |
| Kelola Status Aktif | ✓ | ✗ | ✗ |
| **Jadwal Latihan** | | | |
| Lihat Jadwal | ✓ | ✓ | ✓ |
| Buat Jadwal | ✓ | ✓ | ✗ |
| Edit Jadwal | ✓ | ✓ | ✗ |
| Hapus Jadwal | ✓ | ✗ | ✗ |
| Ganti Status Jadwal | ✓ | ✓ | ✗ |
| **Absensi** | | | |
| Lihat Jadwal (Terbaru/Semua/History) | ✓ | ✓ | ✓ |
| Input Absensi Anggota | ✓ | ✓ | ✗ |
| Lihat Absensi Semua | ✓ | ✓ | ✗ |
| Export Absensi | ✓ | ✓ | ✗ |
| **Booking Acara** | | | |
| Lihat Booking | ✓ | ✓ | ✓ |
| Buat Booking | ✓ | ✓ | ✗ |
| Update Status | ✓ | ✓ | ✗ |
| Upload Dokumentasi | ✓ | ✓ | ✗ |
| Hapus Booking | ✓ | ✗ | ✗ |
| **Inventaris** | | | |
| Lihat Inventaris | ✓ | ✓ | ✓ |
| Tambah Alat | ✓ | ✗ | ✗ |
| Edit Stok | ✓ | ✗ | ✗ |
| Hapus Alat | ✓ | ✗ | ✗ |
| **Keuangan** | | | |
| Lihat Laporan | ✓ | ✓ | ✓ |
| Tambah Transaksi | ✓ | ✗ | ✗ |
| Edit Transaksi | ✓ | ✗ | ✗ |
| Hapus Transaksi | ✓ | ✗ | ✗ |
| Export Laporan | ✓ | ✗ | ✗ |

Keterangan: ✓ = Dapat diakses, ✗ = Tidak dapat diakses

### 11.10 Catatan Tambahan

Dokumen use case ini dibuat berdasarkan analisis struktur file aplikasi dan ER diagram yang ada. Setiap use case mendeskripsikan alur interaksi antara aktor dengan sistem untuk mencapai tujuan tertentu. Use case diagram membantu stakeholder dalam memahami fungsionalitas sistem secara keseluruhan dan memudahkan proses pengembangan serta pengujian perangkat lunak.

Use case dibuat dengan prinsip bahwa setiap use case harus memiliki nilai tambah bagi aktor dan harus merupakan unit kerja yang lengkap. Relasi antar use case menggunakan notasi standar UML seperti «include» untuk hubungan wajib dan «extend» untuk hubungan opsional dengan kondisi tertentu.

---

