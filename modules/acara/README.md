# Modul Booking Acara - Dokumentasi

Dokumentasi lengkap untuk modul booking acara dalam sistem Hadrahin.

## 1. Gambaran Umum

Modul **Booking Acara** berfungsi untuk mengelola pemesanan/manggung acara grup hadrah. Modul ini memungkinkan admin dan pembina untuk mencatat, mengelola, dan mendokumentasikan setiap acara yang dihadiri oleh grup.

## 2. Struktur File

```
modules/acara/
├── index.php              # Halaman utama - daftar booking dengan pagination
├── tambah.php             # Halaman form tambah booking baru (halaman terpisah)
├── edit.php               # Halaman edit booking dengan sidebar info
├── hapus.php              # Proses hapus booking (admin only)
├── update_status.php      # API untuk update status (AJAX)
├── view_ajax.php          # View detail booking (modal)
├── dokumentasi.php        # Upload & kelola dokumentasi foto/video
└── README.md              # Dokumentasi lengkap
```

## 3. Alur Sistem

### 3.1 Pembuatan Booking Baru

1. Admin/Pembina klik tombol "Tambah Booking" di halaman index
2. Sistem redirect ke halaman `tambah.php`
3. User mengisi form dengan data acara
4. Klik "Simpan Booking" → Modal konfirmasi muncul
5. Klik "Konfirmasi" → Data disimpan ke database (status: "menunggu")
6. Redirect ke index dengan pesan sukses

### 3.2 Alur Perubahan Status

```
┌────────────────────────────────────────────────────────────┐
│                    STATUS FLOW                             │
├────────────────────────────────────────────────────────────┤
│                                                            │
│   MENUNGGU ─────► DITERIMA ─────► SELESAI ─────► DOKUMEN  │
│        │              │              │                     │
│        │              │              │                     │
│        └───────► DITOLAK                              │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## 4. Database Schema

### 4.1 Tabel booking_acara

```sql
CREATE TABLE `booking_acara` (
  `id_booking` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,           -- FK ke user (penanggung jawab)
  `nama_acara` varchar(150) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `no_hp_pemesan` varchar(20) DEFAULT NULL,
  `tanggal_acara` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `id_dresscode` int(11) DEFAULT NULL,      -- FK ke dresscode
  `status` enum('menunggu','diterima','ditolak','selesai') DEFAULT 'menunggu',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_modified` int(11) DEFAULT NULL,
  `user_record` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_booking`),
  KEY `fk_booking_user` (`id_user`),
  KEY `fk_booking_dresscode` (`id_dresscode`),
  KEY `idx_status_tanggal` (`status`,`tanggal_acara`)
);
```

### 4.2 Tabel dokumentasi_acara

```sql
CREATE TABLE `dokumentasi_acara` (
  `id_dokumentasi` int(11) NOT NULL AUTO_INCREMENT,
  `id_booking` int(11) NOT NULL,            -- FK ke booking_acara
  `file_path` varchar(255) NOT NULL,        -- Path file di server
  `keterangan` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_modified` int(11) DEFAULT NULL,
  `user_record` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_dokumentasi`),
  KEY `fk_dokumentasi_booking` (`id_booking`),
  CONSTRAINT `fk_dokumentasi_booking` FOREIGN KEY (`id_booking`) 
    REFERENCES `booking_acara` (`id_booking`) ON DELETE CASCADE
);
```

## 5. Halaman dan Fitur

### 5.1 Halaman Index (index.php)

- **Floating Action Button (FAB)**: Tombol "+" melayang di pojok kanan bawah
- **Stat Cards**: 4 card menampilkan statistik booking per status
- **Filter Buttons**: Filter berdasarkan status
- **Pagination**: Navigasi halaman
- **Table View**: Tampilkan semua booking
- **Action Buttons**: View, Edit, Update Status, Hapus

### 5.2 Halaman Tambah (tambah.php)

Halaman form terpisah (bukan modal) dengan fitur:

- **Realtime Validation**: Validasi saat input (saat mengetik)
  - Nama Acara: minimal 3 karakter
  - Nama Pemesan: minimal 2 karakter
  - Tanggal: tidak boleh kurang dari hari ini
  - Jam Mulai: wajib diisi
  - Lokasi: minimal 5 karakter
  - Dresscode: **wajib dipilih** (tidak boleh kosong)
- **Form Input**: 
  - Nama Acara (*)
  - Nama Pemesan (*)
  - No HP Pemesan
  - Tanggal Acara (*)
  - Jam Mulai (*)
  - Lokasi (*)
  - Dresscode (*)
  - Keterangan
- **Modal Konfirmasi**: Konfirmasi data sebelum simpan
- **Sidebar Info**: Status booking, info pembuat, tips
- **Input Groups**: Dengan icons untuk setiap field

### 5.3 Halaman Edit (edit.php)

- **Realtime Validation**: Sama seperti halaman tambah (termasuk dresscode wajib)
- **Form Edit**: Sama seperti halaman tambah dengan field tambahan Status dropdown
- **Dropdown Status**: Ubah status booking
- **Sidebar Info**: Info booking (status, tanggal dibuat, ID)
- **Warning Card**: Catatan penting tentang batasan edit

### 5.4 Halaman Dokumentasi (dokumentasi.php)

- **Upload Form**: Upload foto/video
- **Galeri**: Grid card dokumentasi

## 6. Hak Akses per Peran

| Fitur | Admin | Pembina | Anggota |
|-------|-------|---------|---------|
| Lihat daftar booking | ✓ | ✓ | ✓ |
| Tambah booking | ✓ | ✓ | ✗ |
| Edit booking | ✓ | ✓ | ✗ |
| Update status | ✓ | ✓ | ✗ |
| Upload dokumentasi | ✓ | ✓ | ✗ |
| Hapus booking | ✓ | ✗ | ✗ |
| Hapus dokumentasi | ✓ | ✗ | ✗ |

## 7. Penggunaan

### 7.1 Menambah Booking Baru

1. Login sebagai Admin atau Pembina
2. Navigasi ke menu "Booking Acara"
3. Klik tombol "Tambah Booking"
4. Isi form dengan data acara
5. Klik "Simpan Booking"
6. Konfirmasi data di modal
7. Klik "Konfirmasi"
8. Booking akan berstatus "Menunggu"

### 7.2 Mengubah Status Booking

1. Di tabel booking, klik tombol:
   - ✓ (Hijau) untuk Terima
   - ✗ (Merah) untuk Tolak
   - ✓✓ (Biru) untuk Selesai
2. Konfirmasi perubahan status

### 7.3 Upload Dokumentasi

1. Klik tombol "Lihat Detail" pada booking
2. Klik "Lihat Dokumentasi"
3. Pilih file dan isi keterangan
4. Klik "Upload"

---

*Dokumen ini dibuat berdasarkan implementasi di folder `/opt/lampp/htdocs/hadrahin/modules/acara`*

