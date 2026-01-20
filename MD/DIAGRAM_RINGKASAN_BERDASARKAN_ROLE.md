# Diagram Ringkasan Alur Sistem Aplikasi Hadrah Berdasarkan Role

---

## 1. Arsitektur Role-Based Access Control (RBAC)

```
                          +-------------------+
                          |      DATABASE     |
                          |      (MySQL)      |
                          +---------+---------+
                                    |
                          +---------+---------+
                          |                   |
                          v                   v
                +-----------------+   +-----------------+
                |    WEB APP      |   |    SESSION      |
                |   (PHP/HTML)    |   |   MANAGEMENT    |
                +--------+--------+   +-----------------+
                         |
         +---------------+---------------+
         |               |               |
         v               v               v
  +-------------+ +-------------+ +-------------+
  |    ADMIN    | |   PEMBINA   | |   ANGGOTA   |
  |  (Super)    | | (Pengawas)  | |  (User)     |
  +-------------+ +-------------+ +-------------+
```

---

## 2. Alur Login & Redirect Berdasarkan Role

```
                    +-------------------+
                    |   BUKA HALAMAN    |
                    |      LOGIN        |
                    +---------+---------+
                             |
                             v
                    +-------------------+
                    |   INPUT USERNAME  |
                    |   & PASSWORD      |
                    +---------+---------+
                             |
                             v
                    +-------------------+
                    |   VALIDASI DB     |
                    +---------+---------+
                             |
              +--------------+--------------+
              |                             |
             VALID                        INVALID
              |                             |
              v                             v
    +-------------------+        +-------------------+
    |   CEK ROLE USER   |        |  TAMPILKAN ERROR  |
    +--------+----------+        +-------------------+
             |
   +---------+---------+
   |                   |
   v                   v
+-------------+   +-------------+
|   ADMIN     |   |   PEMBINA   |
| Redirect ke |   | Redirect ke |
| dashboard/  |   | dashboard/  |
| admin.php   |   | pembina.php |
+-------------+   +-------------+
        |                   |
        +---------+---------+
                  |
                  v
         +-------------+
         |   ANGGOTA   |
         | Redirect ke |
         | dashboard/  |
         | anggota.php |
         +-------------+
```

---

## 3. Dashboard Per Role

### 3.1 Dashboard Admin

```
+---------------------------------------------------------------------+
|                    ADMIN (HADRAH APP)                               |
|                     halo, [Nama Admin] | [LOGOUT]                   |
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
|                         MENU UTAMA                                  |
|                                                                       |
|  +-------------+  +-------------+  +-------------+                   |
|  |  MANAJEMEN  |  |   JADWAL    |  |   BOOKING   |                   |
|  |    USER     |  |  LATIHAN    |  |    ACARA    |                   |
|  |             |  |             |  |             |                   |
|  | - Lihat     |  | - Buat      |  | - Lihat     |                   |
|  | - Tambah    |  | - Edit      |  | - Terima    |                   |
|  | - Edit      |  | - Hapus     |  | - Tolak     |                   |
|  | - Hapus     |  | - Status    |  | - Dokumen   |                   |
|  +-------------+  +-------------+  +-------------+                   |
|                                                                       |
|  +-------------+  +-------------+                                     |
|  | INVENTARIS  |  |  KEUANGAN   |                                     |
|  |    ALAT     |  |    (KAS)    |                                     |
|  | - Lihat     |  | - Lihat     |                                     |
|  | - Tambah    |  | - Tambah    |                                     |
|  | - Edit Stok |  | - Edit      |                                     |
|  | - Hapus     |  | - Hapus     |                                     |
|  +-------------+  +-------------+                                     |
+---------------------------------------------------------------------+
```

### 3.2 Dashboard Pembina

```
+---------------------------------------------------------------------+
|                    PEMBINA (HADRAH APP)                             |
|                     halo, [Nama Pembina] | [LOGOUT]                 |
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
|                         MENU UTAMA                                  |
|                                                                       |
|  +-------------+  +-------------+  +-------------+                   |
|  |   JADWAL    |  |   ABSENSI   |  |   BOOKING   |                   |
|  |  LATIHAN    |  |  LATIHAN    |  |    ACARA    |                   |
|  |             |  |             |  |             |                   |
|  | - Buat      |  | - Input     |  | - Lihat     |                   |
|  | - Edit      |  | - Lihat     |  | - Status    |                   |
|  | - Status    |  | - Laporan   |  | - Dokumen   |                   |
|  +-------------+  +-------------+  +-------------+                   |
|                                                                       |
|  +-------------+                                                    |
|  | INVENTARIS  |                                                   |
|  |    ALAT     |                                                   |
|  | - Lihat     |                                                   |
|  | - Update    |                                                   |
|  +-------------+                                                    |
+---------------------------------------------------------------------+
```

### 3.3 Dashboard Anggota

```
+---------------------------------------------------------------------+
|                    ANGGOTA (HADRAH APP)                             |
|                     halo, [Nama Anggota] | [LOGOUT]                 |
+---------------------------------------------------------------------+

+---------------------------------------------------------------------+
|                         MENU UTAMA                                  |
|                                                                       |
|  +-------------+  +-------------+  +-------------+                   |
|  |   JADWAL    |  |  RIWAYAT    |  |   JADWAL    |                   |
|  |  LATIHAN    |  |   ABSENSI   |  |    ACARA    |                   |
|  |             |  |             |  |             |                   |
|  | - Lihat     |  | - Lihat     |  | - Lihat     |                   |
|  | - Absen     |  | - Statistik |  | - Detail    |                   |
|  +-------------+  +-------------+  +-------------+                   |
|                                                                       |
|  +-------------+                                                    |
|  |   KONTAK    |                                                    |
|  | - Hubungi   |                                                    |
|  |   Pembina   |                                                    |
|  +-------------+                                                    |
+---------------------------------------------------------------------+
```

---

## 4. Matriks Hak Akses Per Role

```
+------------------+----------+----------+----------+
|      FITUR       |  ADMIN   |  PEMBINA |  ANGGOTA |
+------------------+----------+----------+----------+
|  AUTENTIKASI     |          |          |          |
|  - Login         |    OK    |    OK    |    OK    |
|  - Logout        |    OK    |    OK    |    OK    |
|  - Reset Pass    |    OK    |    OK    |    OK    |
|                  |          |          |          |
|  USER MGMT       |          |          |          |
|  - Lihat User    |    OK    |    NO    |    NO    |
|  - Tambah User   |    OK    |    NO    |    NO    |
|  - Edit User     |    OK    |    NO    |    NO    |
|  - Hapus User    |    OK    |    NO    |    NO    |
|  - Kelola Peran  |    OK    |    NO    |    NO    |
|                  |          |          |          |
|  JADWAL          |          |          |          |
|  - Lihat         |    OK    |    OK    |    OK    |
|  - Buat          |    OK    |    OK    |    NO    |
|  - Edit          |    OK    |    OK    |    NO    |
|  - Hapus         |    OK    |    NO    |    NO    |
|  - Status        |    OK    |    OK    |    NO    |
|                  |          |          |          |
|  ABSENSI         |          |          |          |
|  - Absen Diri    |    OK    |    OK    |    OK    |
|  - Lihat Sendiri |    OK    |    OK    |    OK    |
|  - Lihat Semua   |    OK    |    OK    |    NO    |
|  - Input Absensi |    OK    |    OK    |    NO    |
|                  |          |          |          |
|  BOOKING         |          |          |          |
|  - Lihat         |    OK    |    OK    |    OK    |
|  - Buat          |    OK    |    OK    |    NO    |
|  - Status        |    OK    |    OK    |    NO    |
|  - Dokumentasi   |    OK    |    OK    |    NO    |
|  - Hapus         |    OK    |    NO    |    NO    |
|                  |          |          |          |
|  INVENTARIS      |          |          |          |
|  - Lihat         |    OK    |    OK    |    OK    |
|  - Tambah        |    OK    |    NO    |    NO    |
|  - Edit Stok     |    OK    |    OK    |    NO    |
|  - Hapus         |    OK    |    NO    |    NO    |
|                  |          |          |          |
|  KEUANGAN        |          |          |          |
|  - Lihat         |    OK    |    OK    |    OK    |
|  - Tambah        |    OK    |    NO    |    NO    |
|  - Edit          |    OK    |    NO    |    NO    |
|  - Hapus         |    OK    |    NO    |    NO    |
+------------------+----------+----------+----------+

OK = Akses Diizinkan  |  NO = Akses Ditolak
```

---

## 5. Summary

```
+---------------------------------------------------------------------+
|                    RINGKASAN HAK AKSES PER ROLE                     |
+---------------------------------------------------------------------+
|                                                                     |
|  ADMIN:                                                             |
|  -> "Super User" dengan akses penuh ke semua fitur dan modul        |
|  -> Dapat mengelola user, keuangan, inventaris, jadwal, booking     |
|                                                                     |
|  PEMBINA:                                                          |
|  -> "Pengawas" dengan akses untuk mengelola operasional harian      |
|  -> Dapat membuat/edit jadwal, input absensi, update status booking |
|  -> Tidak dapat mengelola user dan keuangan                         |
|                                                                     |
|  ANGGOTA:                                                          |
|  -> "User Biasa" dengan akses terbatas untuk melihat informasi      |
|  -> Hanya dapat absen diri sendiri dan melihat data read-only       |
|  -> Tidak dapat mengubah/menghapus data apapun                      |
|                                                                     |
+---------------------------------------------------------------------+
```

---

*Diagram ini dibuat berdasarkan struktur file aplikasi di /opt/lampp/htdocs/hadrahin*

