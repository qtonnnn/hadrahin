# Diagram Alur Modul Booking Acara

Dokumentasi diagram alur untuk modul booking acara dalam sistem Hadrahin.

## 1. Arsitektur Modul

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      ARSITEKTUR MODUL ACARA                                 │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌─────────────────┐
                              │  User (Admin/   │
                              │   Pembina)      │
                              └────────┬────────┘
                                       │
                                       ▼
                              ┌─────────────────┐
                              │  modules/acara/ │
                              │     index.php   │
                              │  (Daftar Booking│
                              └────────┬────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │                  │                  │
                    ▼                  ▼                  ▼
           ┌──────────────── ┌──────────────── ┌────────────────┐
           │   TAMBAH        │    EDIT         │  DOKUMENTASI   │
           │   (tambah.php)  │   (edit.php)    │ (dokumentasi.php)
           └──────────────── └──────────────── └────────────────┘
                    │                  │                  │
                    ▼                  ▼                  ▼
           ┌──────────────── ┌──────────────── ┌────────────────┐
           │   DATABASE      │   DATABASE      │   FILE SYSTEM  │
           │ booking_acara   │ booking_acara   │ assets/uploads/│
           └──────────────── └──────────────── │ dokumentasi/   │
                                             └────────────────┘
```

## 2. Alur Pembuatan Booking Baru

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ALUR PEMBUATAN BOOKING BARU                              │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │  Admin/Pembina Akses    │
                         │  Halaman Booking        │
                         │  (modules/acara/)       │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Klik Tombol            │
                         │  "Tambah Booking"       │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Tampilkan Modal Form   │
                         │  Booking Acara:         │
                         │  ┌─────────────────────────────────┐│
                         │  │ Nama Acara (*)       [____]     ││
                         │  │ Nama Pemesan (*)     [____]     ││
                         │  │ No HP Pemesan        [____]     ││
                         │  │ Tanggal Acara (*)    [____]     ││
                         │  │ Jam Mulai (*)        [__:__]    ││
                         │  │ Lokasi (*)           [____]     ││
                         │  │ Dresscode            [▼]        ││
                         │  │ Keterangan           [____]     ││
                         │  └─────────────────────────────────┘│
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  User Isi & Submit Form │
                         │  (Validasi Input)       │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Validasi Server-side:  │
                         │  ✓ Field wajib terisi   │
                         │  ✓ Format tanggal valid │
                         │  ✓ Format HP valid      │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                        VALID                   INVALID
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Simpan ke Database │    │ Tampilkan Error    │
              │ Status: 'menunggu' │    │ di Form            │
              └─────────┬──────────┘    └────────────────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Redirect ke Index  │
              │ ?success=created   │
              └────────────────────┘
```

## 3. Alur Perubahan Status

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ALUR PERUBAHAN STATUS BOOKING                            │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │  Admin/Pembina Lihat    │
                         │  Daftar Booking         │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Klik Button Aksi pada   │
                         │  Row Booking Tertentu    │
                         │  [👁️] [✏️] [✓] [✗] [✓✓] │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Konfirmasi (JS Alert)  │
                         │  "Ubah status booking?" │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                         YA                        TIDAK
                          │                         │
                          ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ AJAX POST ke       │    │ Batal, Kembali    │
              │ update_status.php  │    │ ke Daftar         │
              └─────────┬──────────┘    └────────────────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Validasi:          │
              │ - Permission check │
              │ - Status transition│
              │   validation       │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Update Database    │
              │ SET status = ?     │
              │ WHERE id_booking   │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ JSON Response:     │
              │ {                  │
              │   "success": true, │
              │   "message": "..." │
              │ }                  │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Reload Table (JS)  │
              └────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                    STATUS TRANSITION MATRIX                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Current Status → Allowed Next Status                                      │
│  ────────────────────────────────────────────────────────                  │
│                                                                             │
│  MENUNGGU   ──────────► DITERIMA  (Siap pelaksanaan)                       │
│      │                   │                                                    │
│      │                   ▼                                                    │
│      │           SELESAI (Setelah acara)                                    │
│      │                                                                    │
│      │                                                                    │
│      └───────────────► DITOLAK (Ditolak)                                   │
│                                                                             │
│  DITERIMA   ──────────► SELESAI (Setelah acara)                            │
│                                                                             │
│  DITOLAK    ────────✗─► (Hanya bisa ke MENUNGGU untuk revisi)              │
│                                                                             │
│  SELESAI    ────────✗─► (Terminal state, tidak bisa diubah)                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 4. Alur Upload Dokumentasi

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ALUR UPLOAD DOKUMENTASI ACARA                            │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────────────┐
                         │  Admin/Pembina Klik     │
                         │  "Lihat Dokumentasi"    │
                         │  atau Link di Index     │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Akses Halaman          │
                         │  dokumentasi.php?id=    │
                         │  {id_booking}           │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Tampilkan:             │
                         │  - Info Booking         │
                         │  - Form Upload          │
                         │  - Galeri Dokumentasi   │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  User Pilih File:       │
                         │  - Image (jpg/png/gif)  │
                         │  - Video (mp4/mov)      │
                         │  - Max: 50MB            │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Submit Form Upload     │
                         │  (multipart/form-data)  │
                         └────────────┬────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │  Validasi File:         │
                         │  - Tipe MIME            │
                         │  - Ukuran File          │
                         │  - Ekstensi             │
                         └────────────┬────────────┘
                                      │
                         ┌────────────┴────────────┐
                         │                         │
                        VALID                   INVALID
                         │                         │
                         ▼                         ▼
              ┌────────────────────┐    ┌────────────────────┐
              │ Generate Filename  │    │ Tampilkan Error    │
              │ acara_{id}_{time}. │    │ "File tidak valid" │
              │ {ext}               │    └────────────────────┘
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Move File ke        │
              │ assets/uploads/     │
              │ dokumentasi/        │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Insert ke Database │
              │ tabel: dokumen-    │
              │ tasi_acara         │
              │ (FK: id_booking)   │
              └─────────┬──────────┘
                        │
                        ▼
              ┌────────────────────┐
              │ Refresh Galeri     │
              │ Tampilkan File     │
              │ Baru               │
              └────────────────────┘
```

## 5. Diagram Relasi Database

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    RELASI DATABASE MODUL ACARA                              │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌──────────────────────────────────────────────────────────────────────────┐
    │                                                                          │
    │    ┌─────────────────┐                              ┌─────────────────┐  │
    │    │      user       │                              │    dresscode    │  │
    │    ├─────────────────┤                              ├─────────────────┤  │
    │    │ id_user (PK)    │                              │ id_dresscode    │  │
    │    │ username        │                              │ (PK)            │  │
    │    │ nama_lengkap    │                              │ nama_pakaian    │  │
    │    │ peran           │                              │ deskripsi       │  │
    │    └────────┬────────┘                              │ warna           │  │
    │             │                                       └────────┬────────┘  │
    │             │                                                │           │
    │             │ id_user                                        │ id_dress  │
    │             │ (FK)                                           │ (FK)      │
    │             ▼                                                ▼           │
    │    ┌────────────────────────────────────────────────────────────────┐   │
    │    │                    booking_acara                              │   │
    │    ├────────────────────────────────────────────────────────────────┤   │
    │    │ id_booking (PK)      │ int(11) AUTO_INCREMENT                │   │
    │    │ id_user              │ int(11) FK → user.id_user             │   │
    │    │ nama_acara           │ varchar(150) NOT NULL                  │   │
    │    │ nama_pemesan         │ varchar(100) NOT NULL                  │   │
    │    │ no_hp_pemesan        │ varchar(20)                            │   │
    │    │ tanggal_acara        │ date NOT NULL                          │   │
    │    │ jam_mulai            │ time NOT NULL                          │   │
    │    │ lokasi               │ varchar(150) NOT NULL                  │   │
    │    │ id_dresscode         │ int(11) FK → dresscode.id_dresscode   │   │
    │    │ status               │ ENUM: menunggu, diterima, ditolak,     │   │
    │    │                      │        selesai                         │   │
    │    │ created_at           │ timestamp                              │   │
    │    │ updated_at           │ timestamp                              │   │
    │    │ user_modified        │ int(11)                                │   │
    │    │ user_record          │ int(11)                                │   │
    │    └─────────────────────┼────────────────────────────────────────┘   │
    │                          │                                              │
    │                          │ id_booking (FK)                             │
    │                          │ ON DELETE CASCADE                           │
    │                          ▼                                              │
    │    ┌────────────────────────────────────────────────────────────────┐   │
    │    │                 dokumentasi_acara                              │   │
    │    ├────────────────────────────────────────────────────────────────┤   │
    │    │ id_dokumentasi (PK)  │ int(11) AUTO_INCREMENT                 │   │
    │    │ id_booking           │ int(11) FK → booking_acara.id_booking  │   │
    │    │ file_path            │ varchar(255) NOT NULL                  │   │
    │    │ keterangan           │ varchar(150)                           │   │
    │    │ created_at           │ timestamp                              │   │
    │    │ updated_at           │ timestamp                              │   │
    │    │ user_modified        │ int(11)                                │   │
    │    │ user_record          │ int(11)                                │   │
    │    └────────────────────────────────────────────────────────────────┘   │
    │                                                                          │
    └──────────────────────────────────────────────────────────────────────────┘
```

## 6. Hak Akses per Peran

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    MATRIKS HAK AKSES MODUL ACARA                            │
└─────────────────────────────────────────────────────────────────────────────┘

┌────────────────────┬──────────┬──────────┬──────────┐
│      FITUR         │  ADMIN   │  PEMBINA │  ANGGOTA │
├────────────────────┼──────────┼──────────┼──────────┤
│ Lihat Daftar       │    ✓     │    ✓     │    ✓     │
│ Booking            │          │          │          │
├────────────────────┼──────────┼──────────┼──────────┤
│ Tambah Booking     │    ✓     │    ✓     │    ✗     │
├────────────────────┼──────────┼──────────┼──────────┤
│ Edit Booking       │    ✓     │    ✓     │    ✗     │
├────────────────────┼──────────┼──────────┼──────────┤
│ Hapus Booking      │    ✓     │    ✗     │    ✗     │
├────────────────────┼──────────┼──────────┼──────────┤
│ Update Status      │    ✓     │    ✓     │    ✗     │
├────────────────────┼──────────┼──────────┼──────────┤
│ Upload Dokumentasi │    ✓     │    ✓     │    ✗     │
├────────────────────┼──────────┼──────────┼──────────┤
│ Hapus Dokumentasi  │    ✓     │    ✗     │    ✗     │
└────────────────────┴──────────┴──────────┴──────────┘

KETERANGAN:
✓ = Diizinkan
✗ = Ditolak
```

## 7. Alur End-to-End Lengkap

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ALUR END-TO-END MODUL ACARA                              │
└─────────────────────────────────────────────────────────────────────────────┘

   ╔═══════════════╗         ╔═══════════════╗         ╔═══════════════╗
   ║   1. BUAT     ║         ║  2. VERIFIKASI║         ║ 3. PELAKSANAAN║
   ║   BOOKING     ║────────►║   STATUS      ║────────►║    ACARA      ║
   ╚═══════════════╝         ╚═══════════════╝         ╚═══════════════╝
           │                         │                         │
           ▼                         ▼                         ▼
   ┌───────────────┐         ┌───────────────┐         ┌───────────────┐
   │ - Isi Form    │         │ - Review      │         │ - Pelaksanaan │
   │ - Submit      │         │   booking     │         │   acara       │
   │ - Status:     │         │ - Terima/     │         │ - Dokumentasi │
   │   MENUNGGU    │         │   Tolak       │         │   (foto/video)│
   └───────────────┘         └───────────────┘         └───────┬───────┘
                                                               │
                                                               ▼
                                               ╔═══════════════════════════╗
                                               ║     4. DOKUMENTASI         ║
                                               ║                           ║
                                               ║  - Upload foto/video      ║
                                               ║  - Simpan ke database     ║
                                               ║  - Tampilkan di galeri    ║
                                               ╚═══════════════════════════╝

   RINGKASAN FLOW:
   ┌────────────────────────────────────────────────────────────────────────┐
   │                                                                        │
   │   [INPUT] ──► [VALIDASI] ──► [SIMPAN] ──► [STATUS: MENUNGGU]         │
   │                                                                        │
   │                                    │                                   │
   │                                    ▼                                   │
   │   [DOKUMENTASI] ◄────────── [SELESAI] ◄─── [DITERIMA] ◄──── [VALID]  │
   │   (foto/video)         (acak)     (acak)          │                   │
   │                                    │             │                   │
   │                                    ▼             │                   │
   │                              [DITOLAK] ──────────┘                   │
   │                                (tolak)                                │
   │                                                                        │
   └────────────────────────────────────────────────────────────────────────┘
```

## 8. Integrasi dengan Dashboard

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    INTEGRASI DENGAN DASHBOARD                               │
└─────────────────────────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────────────────────┐
                    │           API: api/stats.php                │
                    └────────────────────┬────────────────────────┘
                                         │
                    ┌────────────────────┴────────────────────┐
                    │                                         │
                    ▼                                         ▼
           ┌────────────────────────┐         ┌────────────────────────┐
           │   getBookingAktif()    │         │  getRecentActivities() │
           │                        │         │                        │
           │   Query:               │         │   Query:               │
           │   SELECT COUNT(*)      │         │   SELECT ...           │
           │   FROM booking_acara  │         │   FROM booking_acara   │
           │   WHERE status IN     │         │   ORDER BY created_at  │
           │   ('menunggu',        │         │   LIMIT 5              │
           │    'diterima')        │         │                        │
           └───────────┬──────────┘         └───────────┬────────────┘
                       │                                │
                       ▼                                ▼
           ┌─────────────────────────────────────────────────────────┐
           │                  DASHBOARD STATISTICS                   │
           │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
           │  │  Booking    │  │  Aktivitas  │  │  Grafik     │    │
           │  │  Aktif: 5   │  │  Terbaru    │  │  Tren       │    │
           │  └─────────────┘  └─────────────┘  └─────────────┘    │
           └─────────────────────────────────────────────────────────┘
```

## 9. Catatan Teknis

### 9.1 Keamanan

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    IMPLEMENTASI KEAMANAN                                    │
└─────────────────────────────────────────────────────────────────────────────┘

1. SQL Injection Prevention
   ┌─────────────────────────────────────────────────────────────────────┐
   │  // Menggunakan Prepared Statements                                  │
   │  $stmt = $pdo->prepare("SELECT * FROM booking_acara WHERE id_booking = ?");  │
   │  $stmt->execute([$id_booking]);                                     │
   └─────────────────────────────────────────────────────────────────────┘

2. XSS Protection
   ┌─────────────────────────────────────────────────────────────────────┐
   │  // Menggunakan htmlspecialchars() untuk output                     │
   │  echo htmlspecialchars($booking['nama_acara']);                    │
   └─────────────────────────────────────────────────────────────────────┘

3. Session-based Authentication
   ┌─────────────────────────────────────────────────────────────────────┐
   │  // Cek autentikasi di setiap halaman                               │
   │  require_once '../../includes/auth_check.php';                     │
   └─────────────────────────────────────────────────────────────────────┘

4. Role-based Access Control
   ┌─────────────────────────────────────────────────────────────────────┐
   │  // Cek peran user untuk akses fitur                                │
   │  if ($_SESSION['peran'] != 'admin' && $_SESSION['peran'] != 'pembina') {  │
   │      header('Location: index.php?error=permission_denied');         │
   │      exit;                                                          │
   │  }                                                                  │
   └─────────────────────────────────────────────────────────────────────┘

5. File Upload Validation
   ┌─────────────────────────────────────────────────────────────────────┐
   │  // Validasi tipe dan ukuran file                                   │
   │  $allowed_types = ['image/jpeg', 'image/png', 'video/mp4'];        │
   │  $max_size = 50 * 1024 * 1024; // 50MB                             │
   └─────────────────────────────────────────────────────────────────────┘
```

### 9.2 Audit Trail

Setiap tabel memiliki field untuk tracking perubahan:

```sql
-- Contoh: Field audit trail di tabel booking_acara
created_at     -- Timestamp pembuatan record
updated_at     -- Timestamp terakhir diupdate
user_modified  -- ID user yang terakhir mengubah
user_record    -- ID user yang membuat record
```

---

*Dokumen ini dibuat berdasarkan implementasi modul acara di `/opt/lampp/htdocs/hadrahin/modules/acara/`*

