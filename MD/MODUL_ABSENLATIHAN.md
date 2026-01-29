# Modul Absensi Latihan

Dokumentasi lengkap untuk modul absensi latihan pada aplikasi Hadrah.

## Daftar Isi

1. [Deskripsi Modul](#deskripsi-modul)
2. [Struktur File](#struktur-file)
3. [Fitur dan Fungsi](#fitur-dan-fungsi)
4. [Alur Sistem](#alur-sistem)
5. [Database](#database)
6. [Hak Akses](#hak-akses)
7. [Validasi](#validasi)
8. [API dan Endpoint](#api-dan-endpoint)
9. [Catatan Pengembangan](#catatan-pengembangan)

---

## Deskripsi Modul

Modul Absensi Latihan digunakan untuk mengelola kehadiran anggota pada setiap jadwal latihan. Modul ini memungkinkan admin dan pembina untuk:

- Melihat riwayat absensi latihan
- Menginput absensi anggota untuk jadwal tertentu
- Mengekspor data absensi ke format Excel/CSV
- Melihat statistik kehadiran (Hadir, Izin, Alpa)

**Akses Terbatas:** Hanya Admin dan Pembina yang dapat mengakses modul ini.

---

## Struktur File

```
modules/absenlatihan/
├── index.php      # Halaman utama - daftar riwayat absensi
├── absen.php      # Form input absensi untuk jadwal tertentu
├── export.php     # Ekspor data absensi ke CSV
└── MD/MODUL_ABSENLATIHAN.md  # Dokumentasi modul ini
```

---

## Fitur dan Fungsi

### 1. Halaman Utama (index.php)

Halaman utama menampilkan daftar seluruh riwayat absensi dengan fitur:

| Fitur | Deskripsi |
|-------|-----------|
| **Pencarian** | Cari berdasarkan nama anggota, username, atau lokasi |
| **Filter Status** | Filter berdasarkan status: Hadir, Izin, Alpa |
| **Filter Jadwal** | Filter berdasarkan jadwal latihan tertentu |
| **Filter Tanggal** | Filter berdasarkan rentang tanggal |
| **Pagination** | Navigasi halaman (15 data per halaman) |
| **Ekspor** | Unduh data dalam format CSV/Excel |

#### Statistik Cards

Halaman utama menampilkan 3 kartu statistik:

- **Hadir**: Jumlah anggota yang hadir
- **Izin**: Jumlah anggota yang izin
- **Alpa**: Jumlah anggota yang tidak hadir tanpa izin

### 2. Form Input Absensi (absen.php)

Form untuk menginput absensi anggota pada jadwal tertentu.

**Fitur Utama:**
- Menampilkan daftar semua anggota (peran: anggota)
- 3 pilihan status: Hadir, Izin, Alpa
- Tombol cepat: "Hadir Semua", "Izin Semua", "Alpa Semua"
- Validasi: Semua anggota harus memiliki status sebelum menyimpan
- Auto-update status jadwal menjadi "selesai" setelah absensi disimpan

#### Validasi Input

```javascript
// Sebelum menyimpan, sistem memeriksa:
// 1. Apakah semua anggota sudah memiliki status?
// 2. Jika ada yang belum terisi, tampilkan error modal
// 3. Jika semua terisi, tampilkan konfirmasi modal
```

### 3. Ekspor Data (export.php)

Fitur untuk mengunduh data absensi dalam format CSV.

**Kolom yang diekspor:**
1. No
2. Tanggal Latihan
3. Jam Mulai
4. Lokasi
5. Status Jadwal
6. Nama Anggota
7. Username
8. No HP
9. Peran
10. Status Absensi
11. Waktu Absen
12. Catatan Jadwal

---

## Alur Sistem

### Alur Input Absensi

```
1. Admin/Pembina mengakses halaman Jadwal Latihan
2. Klik tombol "Absensi" pada jadwal tertentu
3. Sistem menampilkan form absen dengan daftar anggota
4. Admin/Pembina memilih status untuk setiap anggota
5. Klik tombol "Simpan Absensi"
6. Sistem validasi:
   - Jika ada anggota belum terisi → tampil error
   - Jika semua terisi → tampil konfirmasi
7. Klik "Ya, Simpan" untuk konfirmasi
8. Sistem menyimpan data absensi
9. Status jadwal diubah menjadi "selesai"
10. Redirect ke halaman jadwal dengan pesan sukses
```

---

## Database

### Tabel: absen_latihan

```sql
CREATE TABLE `absen_latihan` (
  `id_absen` int(11) NOT NULL AUTO_INCREMENT,
  `id_jadwal` int(11) NOT NULL COMMENT 'ID Jadwal Latihan',
  `id_user` int(11) NOT NULL COMMENT 'ID User/Anggota',
  `status_hadir` enum('hadir','izin','alpa') DEFAULT 'hadir',
  `jam_absen` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_modified` int(11) DEFAULT NULL COMMENT 'User yang terakhir mengubah',
  `user_record` int(11) DEFAULT NULL COMMENT 'User yang mencatat absensi',
  PRIMARY KEY (`id_absen`),
  KEY `fk_absen_jadwal` (`id_jadwal`),
  KEY `fk_absen_user` (`id_user`),
  CONSTRAINT `fk_absen_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_latihan` (`id_jadwal`) ON DELETE CASCADE,
  CONSTRAINT `fk_absen_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## Hak Akses

### Matriks Akses per Peran

| Fitur | Admin | Pembina | Anggota |
|-------|-------|---------|---------|
| Lihat Daftar Absensi | Ya | Ya | Tidak |
| Input Absensi | Ya | Ya | Tidak |
| Ekspor Data | Ya | Ya | Tidak |
| Hapus Absensi | Ya | Tidak | Tidak |

### Kode Pengamanan

```php
// Di setiap file, cek permission di awal
$user_peran = $_SESSION['peran'] ?? 'anggota';

if (!in_array($user_peran, ['admin', 'pembina'])) {
    header('Location: ../dashboard/' . $user_peran . '.php?msg=access_denied');
    exit;
}
```

---

## Validasi

### 1. Validasi Input Absensi

**Aturan:**
- Setiap anggota harus memiliki status (Hadir/Izin/Alpa)
- Status harus salah satu dari nilai yang diizinkan

**Implementasi:**

```php
// Validasi status
$allowed_status = ['hadir', 'izin', 'alpa'];
$status = isset($_POST[$status_key]) ? $_POST[$status_key] : 'alpa';

if (!in_array($status, $allowed_status)) {
    $status = 'alpa'; // Default jika invalid
}
```

### 2. Validasi Parameter GET

```php
// Validasi ID jadwal harus integer positif
$id_jadwal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_jadwal <= 0) {
    header('Location: index.php?msg=error');
    exit;
}
```

---

## API dan Endpoint

### Endpoint yang Digunakan

| File | Method | Deskripsi |
|------|--------|-----------|
| `index.php` | GET | Menampilkan daftar absensi |
| `absen.php` | GET/POST | Form input absensi |
| `export.php` | GET | Ekspor data ke CSV |

### Parameter Query (index.php)

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `page` | int | Nomor halaman (default: 1) |
| `search` | string | Kata kunci pencarian |
| `status` | string | Filter status (hadir/izin/alpa) |
| `jadwal` | int | Filter berdasarkan ID jadwal |
| `tanggal_mulai` | date | Filter tanggal mulai (YYYY-MM-DD) |
| `tanggal_selesai` | date | Filter tanggal selesai (YYYY-MM-DD) |

### Contoh URL

```
# Halaman 2 dengan pencarian
/modules/absenlatihan/index.php?page=2&search=ahmad

# Filter berdasarkan status
/modules/absenlatihan/index.php?status=hadir

# Filter berdasarkan periode
/modules/absenlatihan/index.php?tanggal_mulai=2024-01-01&tanggal_selesai=2024-01-31

# Ekspor dengan filter
/modules/absenlatihan/export.php?status=hadir&tanggal_mulai=2024-01-01
```

---

## Catatan Pengembangan

### 1. Fitur yang Perlu Ditambahkan

- [ ] Integrasi dengan dashboard pembina
- [ ] Laporan absensi per periode dengan grafik
- [ ] Rekap absensi per anggota
- [ ] Export ke format PDF

### 2. Pertimbangan Keamanan

1. **SQL Injection**: Gunakan prepared statements untuk semua query
2. **XSS**: Sanitize output dengan `htmlspecialchars()`
3. **CSRF Protection**: Pertimbangkan untuk menambahkan token CSRF
4. **Rate Limiting**: Batasi akses ke endpoint export

### 3. Optimasi Performa

1. **Caching**: Query stats menggunakan cache (10 menit)
2. **Pagination**: Batasi data yang ditampilkan per halaman
3. **Index Database**: Pastikan index pada kolom yang sering difilter

### 4. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0 | 2024-01-21 | Versi awal dengan fitur dasar |
| 1.1 | 2024-01-21 | Penambahan validasi modal konfirmasi |
| 1.2 | 2024-01-21 | Auto-update status jadwal ke "selesai" |

---

## Troubleshooting

### Masalah Umum

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| Modal tidak bisa ditutup | Backdrop tidak terhapus | Bersihkan manual modal backdrop |
| Data tidak tampil | Cache belum diupdate | Tunggu 10 menit atau clear cache |
| Error 500 saat save | Transaction gagal | Check koneksi database |
| Export gagal | Timeout | Kurangi data yang diekspor |

### Cara Debug

1. Check file log: `logs/php_errors.log`
2. Enable display_errors di development
3. Test query langsung di phpMyAdmin

---

## Kesimpulan

Modul Absensi Latihan merupakan komponen penting dalam sistem informasi grup hadrah. Modul ini menyediakan fitur lengkap untuk mengelola kehadiran anggota dengan:

- **Kemudahan Penggunaan**: Interface yang intuitif dengan modal konfirmasi
- **Keamanan**: Validasi dan proteksi akses yang ketat
- **Fleksibilitas**: Filter dan pencarian yang lengkap
- **Keandalan**: Error handling dan logging yang baik

Dengan modul ini, admin dan pembina dapat dengan mudah mengelola absensi latihan secara efisien dan terstruktur.

