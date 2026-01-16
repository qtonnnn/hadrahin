# Penjelasan Folder Config

Folder `config` merupakan direktori yang berisi semua file konfigurasi sistem aplikasi Hadrahin. Folder ini sangat penting karena mengatur koneksi database, pengaturan dasar aplikasi, dan struktur database.

## Struktur Folder Config

```
config/
├── config.php      # Konfigurasi utama aplikasi
├── koneksi.php     # Koneksi ke database MySQL
└── hadrah.sql      # Struktur database (DDL)
```

---

## 1. File config.php

**Lokasi:** `config/config.php`

**Fungsi:** File ini berisi konfigurasi dasar yang digunakan di seluruh aplikasi.

### Isi File:

```php
<?php
// config/config.php

// timezone
date_default_timezone_set('Asia/Jakarta');

// base url aplikasi
define('BASE_URL', 'http://localhost/hadrahin');

// mulai session sekali saja
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### Penjelasan Setiap Bagian:

#### a) Pengaturan Timezone

```php
date_default_timezone_set('Asia/Jakarta');
```

**Penjelasan:**
- Mengatur zona waktu default untuk semua fungsi waktu di PHP
- `Asia/Jakarta` berarti waktu GMT+7 (WIB - Waktu Indonesia Barat)
- Penting untuk konsistensi waktu di seluruh aplikasi
- Mencegah kesalahan pada operasi tanggal dan waktu

**Contoh Penggunaan:**
- `date('Y-m-d H:i:s')` akan menghasilkan waktu sesuai WIB
- `time()` akan menghasilkan timestamp dalam zona waktu Jakarta

#### b) Base URL Aplikasi

```php
define('BASE_URL', 'http://localhost/hadrahin');
```

**Penjelasan:**
- `define()` digunakan untuk membuat konstanta yang tidak dapat diubah
- `BASE_URL` menyimpan URL dasar aplikasi
- Berguna untuk:
  - Membuat path absolut ke file CSS/JS/gambar
  - Mengalihkan halaman
  - Membuat link yang valid di semua halaman

**Contoh Penggunaan:**
```php
// Menghubungkan ke file CSS
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

// Mengalihkan halaman
header('Location: ' . BASE_URL . '/dashboard/admin.php');

// Membuat URL untuk link
<a href="<?= BASE_URL ?>/modules/user/index.php">Manajemen User</a>
```

**Catatan Penting:**
- Ubah `'http://localhost/hadrahin'` sesuai dengan URL development Anda
- Untuk production, ubah menjadi URL domain Anda, contoh:
  ```php
  define('BASE_URL', 'https://hadrahin.domain-anda.com');
  ```

#### c) Session Management

```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

**Penjelasan:**
- `session_status()` memeriksa status session saat ini
- `PHP_SESSION_NONE` berarti session belum dimulai
- `session_start()` memulai session baru atau melanjutkan yang ada
- Kondisi `if` memastikan session hanya dimulai sekali

**Mengapa Penting?**
- Session digunakan untuk menyimpan data login user
- Mencegah error "headers already sent"
- Menjamin session berjalan dengan optimal

**Data yang Disimpan di Session:**
- `$_SESSION['id_user']` - ID user yang login
- `$_SESSION['nama_user']` - Nama user
- `$_SESSION['peran']` - Peran user (admin/pembina/anggota)
- `$_SESSION['login']` - Status login (true/false)

---

## 2. File koneksi.php

**Lokasi:** `config/koneksi.php`

**Fungsi:** Mengatur koneksi ke database MySQL/MariaDB menggunakan MySQLi extension.

### Isi File:

```php
<?php
// config/koneksi.php

require_once __DIR__ . '/config.php';

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hadrah';

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die('Koneksi database gagal');
}
```

### Penjelasan Setiap Bagian:

#### a) Import Config

```php
require_once __DIR__ . '/config.php';
```

**Penjelasan:**
- `require_once` memastikan file config.php hanya di-include sekali
- `__DIR__` adalah magic constant yang mengembalikan direktori file saat ini
- Bertujuan agar konstanta `BASE_URL` dan pengaturan timezone tersedia

#### b) Konfigurasi Database

```php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hadrah';
```

**Penjelasan:**

| Variabel | Nilai | Fungsi |
|----------|-------|--------|
| `$host` | `'localhost'` | Alamat server database (IP atau hostname) |
| `$user` | `'root'` | Username untuk koneksi database |
| `$pass` | `''` | Password untuk username tersebut (kosong untuk XAMPP default) |
| `$db` | `'hadrah'` | Nama database yang akan digunakan |

**Catatan Keamanan:**
- Untuk production, GUNAKAN user dengan privileges terbatas
- Jangan gunakan user `root` dengan password kosong di production
- Simpan kredensial di file `.env` untuk production

**Contoh Perubahan untuk Production:**
```php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_NAME');
```

#### c) Koneksi Database

```php
$koneksi = mysqli_connect($host, $user, $pass, $db);
```

**Penjelasan:**
- `mysqli_connect()` membuat koneksi ke MySQL server
- Mengembalikan object koneksi jika berhasil
- Disimpan dalam variabel `$koneksi` untuk digunakan di file lain

**Pentingnya Menggunakan MySQLi:**
- Mendukung prepared statements (mencegah SQL injection)
- Mendukung object-oriented dan procedural programming
- Kompatibel dengan PHP 7 dan 8
- Lebih aman daripada mysql extension yang sudah deprecated

#### d) Error Handling

```php
if (!$koneksi) {
    die('Koneksi database gagal');
}
```

**Penjelasan:**
- Jika koneksi gagal, `mysqli_connect()` mengembalikan `false`
- `die()` menghentikan eksekusi script dan menampilkan pesan error
- Di production, pertimbangkan untuk menampilkan pesan yang lebih user-friendly

**Alternatif untuk Production:**
```php
if (!$koneksi) {
    error_log('Koneksi database gagal: ' . mysqli_connect_error());
    header('Location: error.php?code=500');
    exit();
}
```

### Cara Menggunakan di File Lain:

```php
<?php
require_once 'config/koneksi.php';

// Contoh query
$query = "SELECT * FROM user WHERE id_user = 1";
$result = mysqli_query($koneksi, $query);

if ($result) {
    $data = mysqli_fetch_assoc($result);
    echo $data['nama_user'];
}

// Menutup koneksi (opsional, PHP会自动 menutup)
mysqli_close($koneksi);
```

---

## 3. File hadrah.sql

**Lokasi:** `config/hadrah.sql`

**Fungsi:** File SQL dump yang berisi struktur lengkap database aplikasi.

### Informasi File:

| Attribute | Value |
|-----------|-------|
| PHPMyAdmin Version | 5.2.1 |
| Server Version | 10.4.32-MariaDB |
| PHP Version | 8.2.12 |
| Charset | utf8mb4 |
| Collation | utf8mb4_general_ci |

---

## Struktur Database

### 3.1 Tabel User

**Nama Tabel:** `user`

**Fungsi:** Menyimpan data semua pengguna sistem (admin, pembina, anggota).

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_user` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik user |
| `nama_user` | VARCHAR(100) | NOT NULL | Nama lengkap user |
| `no_hp` | VARCHAR(20) | NULL | Nomor HP |
| `alamat` | TEXT | NULL | Alamat lengkap |
| `peran` | ENUM | DEFAULT 'anggota' | Peran: admin/pembina/anggota |
| `status_aktif` | TINYINT(1) | DEFAULT 1 | 1=aktif, 0=tidak aktif |
| `tanggal_gabung` | DATE | NULL | Tanggal gabung |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `user_modified` | VARCHAR(50) | NULL | User terakhir modify |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |
| `updated_at` | TIMESTAMP | DEFAULT current_timestamp() + ON UPDATE | Waktu update |

**Peran User:**

| Peran | Keterangan | Hak Akses |
|-------|------------|-----------|
| `admin` | Administrator sistem | Akses penuh ke semua fitur |
| `pembina` | Pembina organisasi | Kelola jadwal, absensi, booking |
| `anggota` | Anggota biasa | Lihat jadwal, absen diri sendiri |

---

### 3.2 Tabel Jadwal Latihan

**Nama Tabel:** `jadwal_latihan`

**Fungsi:** Menyimpan jadwal latihan rutin organisasi.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_jadwal` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik jadwal |
| `tanggal` | DATE | NOT NULL | Tanggal latihan |
| `jam_mulai` | TIME | NOT NULL | Jam mulai latihan |
| `jam_selesai` | TIME | NULL | Jam selesai latihan |
| `lokasi` | VARCHAR(150) | NOT NULL | Tempat latihan |
| `status` | ENUM | DEFAULT 'direncanakan' | Status: direncanakan/selesai/dibatalkan |
| `catatan` | TEXT | NULL | Catatan tambahan |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `user_modified` | VARCHAR(50) | NULL | User terakhir modify |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |
| `updated_at` | TIMESTAMP | DEFAULT current_timestamp() + ON UPDATE | Waktu update |

---

### 3.3 Tabel Absen Latihan

**Nama Tabel:** `absen_latihan`

**Fungsi:** Mencatat kehadiran anggota pada setiap jadwal latihan.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_absen` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik absen |
| `id_jadwal` | INT(11) | NOT NULL, FOREIGN KEY | Referensi ke jadwal_latihan |
| `id_user` | INT(11) | NOT NULL, FOREIGN KEY | Referensi ke user |
| `status_hadir` | ENUM | DEFAULT 'hadir' | hadir/izin/alpa |
| `catatan` | TEXT | NULL | Catatan absensi |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `user_modified` | VARCHAR(50) | NULL | User terakhir modify |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |
| `updated_at` | TIMESTAMP | DEFAULT current_timestamp() + ON UPDATE | Waktu update |

**Constraints:**
```sql
UNIQUE KEY `uniq_user_jadwal` (`id_jadwal`, `id_user`)
```
- Mencegah user yang sama absen dua kali di jadwal yang sama

**Foreign Keys:**
```sql
FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_latihan` (`id_jadwal`) ON DELETE CASCADE
FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
```

---

### 3.4 Tabel History Latihan

**Nama Tabel:** `history_latihan`

**Fungsi:** Menyimpan rekap/history latihan yang sudah berlangsung.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_history_latihan` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik |
| `id_jadwal` | INT(11) | NOT NULL, FOREIGN KEY | Referensi ke jadwal_latihan |
| `tanggal` | DATE | NOT NULL | Tanggal latihan |
| `lokasi` | VARCHAR(150) | NOT NULL | Lokasi latihan |
| `total_hadir` | INT(11) | DEFAULT 0 | Jumlah yang hadir |
| `total_izin` | INT(11) | DEFAULT 0 | Jumlah izin |
| `total_alpa` | INT(11) | DEFAULT 0 | Jumlah alpa |
| `catatan` | TEXT | NULL | Catatan latihan |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |

---

### 3.5 Tabel Booking Acara

**Nama Tabel:** `booking_acara`

**Fungsi:** Menyimpan data pemesanan acara dari pihak eksternal.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_booking` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik booking |
| `nama_acara` | VARCHAR(150) | NOT NULL | Nama acara |
| `nama_pemesan` | VARCHAR(100) | NOT NULL | Nama pemesan |
| `no_hp_pemesan` | VARCHAR(20) | NULL | HP pemesan |
| `tanggal_acara` | DATE | NOT NULL | Tanggal pelaksanaan |
| `jam_mulai` | TIME | NULL | Jam mulai |
| `lokasi` | VARCHAR(150) | NOT NULL | Lokasi acara |
| `dresscode` | VARCHAR(150) | NULL | Dresscode acara |
| `keterangan` | TEXT | NULL | Keterangan tambahan |
| `status` | ENUM | DEFAULT 'menunggu' | Status: menunggu/diterima/ditolak/selesai |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `user_modified` | VARCHAR(50) | NULL | User terakhir modify |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |
| `updated_at` | TIMESTAMP | DEFAULT current_timestamp() + ON UPDATE | Waktu update |

**Status Booking:**

| Status | Keterangan |
|--------|------------|
| `menunggu` | Booking baru, menunggu persetujuan |
| `diterima` | Booking diterima |
| `ditolak` | Booking ditolak |
| `selesai` | Acara telah berlangsung |

---

### 3.6 Tabel History Acara

**Nama Tabel:** `history_acara`

**Fungsi:** Menyimpan history acara yang sudah dilaksanakan.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_history_acara` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik |
| `id_booking` | INT(11) | NOT NULL, FOREIGN KEY | Referensi ke booking_acara |
| `nama_acara` | VARCHAR(150) | NOT NULL | Nama acara |
| `tanggal_acara` | DATE | NOT NULL | Tanggal acara |
| `lokasi` | VARCHAR(150) | NOT NULL | Lokasi acara |
| `dresscode` | VARCHAR(150) | NULL | Dresscode |
| `catatan` | TEXT | NULL | Catatan |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |

---

### 3.7 Tabel Dokumentasi Acara

**Nama Tabel:** `dokumentasi_acara`

**Fungsi:** Menyimpan file dokumentasi foto/video acara.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_dokumentasi` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik |
| `id_history_acara` | INT(11) | NOT NULL, FOREIGN KEY | Referensi ke history_acara |
| `file_path` | VARCHAR(255) | NOT NULL | Path file di server |
| `keterangan` | VARCHAR(150) | NULL | Keterangan foto/video |
| `user_record` | VARCHAR(50) | NULL | User yang mengupload |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |

---

### 3.8 Tabel Alat

**Nama Tabel:** `alat`

**Fungsi:** Mengelola inventaris alat-alat organisasi.

**Struktur:**

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id_alat` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik alat |
| `nama_alat` | VARCHAR(100) | NOT NULL | Nama alat |
| `jumlah` | INT(11) | DEFAULT 1 | Jumlah unit |
| `kondisi` | ENUM | DEFAULT 'baik' | Kondisi: baik/rusak/servis |
| `catatan` | TEXT | NULL | Catatan tambahan |
| `user_record` | VARCHAR(50) | NULL | User yang mencatat |
| `user_modified` | VARCHAR(50) | NULL | User terakhir modify |
| `created_at` | TIMESTAMP | DEFAULT current_timestamp() | Waktu created |
| `updated_at` | TIMESTAMP | DEFAULT current_timestamp() + ON UPDATE | Waktu update |

**Kondisi Alat:**

| Kondisi | Keterangan |
|---------|------------|
| `baik` | Alat dalam kondisi baik |
| `rusak` | Alat rusak, tidak bisa digunakan |
| `servis` | Alat sedang dalam perbaikan |

---

## Hubungan Antar Tabel (ERD)

```
┌─────────────┐       ┌──────────────────┐       ┌────────────────┐
│    user     │◄──────│  absen_latihan   │──────►│ jadwal_latihan │
└─────────────┘       └──────────────────┘       └────────────────┘
      │                                                │
      │                                                ▼
      │                                        ┌────────────────┐
      │                                        │history_latihan │
      │                                        └────────────────┘
      │
      │
      ▼
┌─────────────┐       ┌──────────────────┐       ┌────────────────┐
│    alat     │       │   booking_acara  │──────►│ history_acara  │
└─────────────┘       └──────────────────┘       └────────────────┘
                                                        │
                                                        ▼
                                                ┌────────────────┐
                                                │dokumentasi_acara│
                                                └────────────────┘
```

---

## Cara Import Database

### Melalui phpMyAdmin:

1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Buat database baru dengan nama `hadrah`
3. Pilih database tersebut
4. Klik tab "Import"
5. Pilih file `config/hadrah.sql`
6. Klik "Go" untuk import

### Melalui Command Line:

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE hadrah CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Import file SQL
mysql -u root -p hadrah < config/hadrah.sql
```

---

## Penggunaan di Aplikasi

### Cara Menginclude Config di File PHP:

```php
<?php
// Include koneksi database
require_once 'config/koneksi.php';

// Gunakan BASE_URL
echo BASE_URL; // Output: http://localhost/hadrahin

// Gunakan koneksi database
$query = "SELECT * FROM user WHERE peran = 'admin'";
$result = mysqli_query($koneksi, $query);
?>
```

### Best Practices:

1. **Selalu gunakan `require_once`** untuk mencegah multiple include
2. **Tutup koneksi** jika tidak lagi diperlukan: `mysqli_close($koneksi)`
3. **Gunakan prepared statements** untuk query dengan parameter
4. **Handle error** dengan baik menggunakan try-catch atau if-else

---

## Troubleshooting

### Error: "Koneksi database gagal"

**Penyebab:**
- MySQL server tidak berjalan
- Kredensial database salah
- Database belum dibuat

**Solusi:**
1. Pastikan XAMPP Control Panel > MySQL berjalan
2. Periksa kredensial di `koneksi.php`
3. Import file `hadrah.sql` melalui phpMyAdmin

### Error: "Table doesn't exist"

**Penyebab:**
- Database belum diimport
- Nama database salah

**Solusi:**
1. Buka phpMyAdmin
2. Pastikan database `hadrah` ada
3. Import file `config/hadrah.sql`

---

## Referensi

- [PHP mysqli_connect()](https://www.php.net/manual/en/function.mysqli-connect.php)
- [PHP date_default_timezone_set()](https://www.php.net/manual/en/function.date-default-timezone-set.php)
- [MySQL FOREIGN KEY](https://dev.mysql.com/doc/refman/8.0/en/create-table-foreign-keys.html)
- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)

---

## Kesimpulan

Folder `config` adalah fondasi aplikasi yang berisi:

| File | Fungsi |
|------|--------|
| `config.php` | Konfigurasi dasar (timezone, base URL, session) |
| `koneksi.php` | Koneksi ke database MySQL |
| `hadrah.sql` | Struktur lengkap database dengan 8 tabel |

Dengan memahami struktur dan fungsi masing-masing file, Anda dapat:
- Mengembangkan fitur baru dengan mudah
- Melakukan troubleshooting masalah database
- Mengoptimasi performa aplikasi
- Mengamankan koneksi database

Pastikan untuk selalu backup file `hadrah.sql` sebelum melakukan perubahan struktur database!
