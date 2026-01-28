# Modul Absensi Latihan

Dokumen ini menjelaskan implementasi modul absensi latihan dalam aplikasi sistem informasi grup hadrah. Modul ini memungkinkan admin dan pembina untuk mengelola absensi anggota pada setiap jadwal latihan.

## Daftar Isi

1. [Ringkasan](#ringkasan)
2. [Fitur Utama](#fitur-utama)
3. [Struktur File](#struktur-file)
4. [Alur Kerja](#alur-kerja)
5. [Database Schema](#database-schema)
6. [Interface Pengguna](#interface-pengguna)
7. [Keamanan dan Akses](#keamanan-dan-akses)
8. [Teknologi yang Digunakan](#teknologi-yang-digunakan)

## Ringkasan

Modul absensi latihan adalah komponen penting dalam sistem manajemen grup hadrah yang memungkinkan admin dan pembina untuk mencatat kehadiran anggota pada setiap sesi latihan. Sistem ini dirancang untuk memudahkan proses absensi dengan interface yang intuitif dan user-friendly.

### Tujuan Utama

- Memfasilitasi pencatatan absensi latihan oleh admin dan pembina
- Menyediakan data absensi yang akurat dan real-time
- Memungkinkan pelaporan dan analisis kehadiran anggota
- Mendukung export data untuk keperluan administrasi

## Fitur Utama

### 1. Pencatatan Absensi

- **Interface Checkbox**: Sistem menampilkan daftar anggota dalam bentuk kartu dengan opsi radio button (Hadir/Izin/Alpa)
- **Bulk Operations**: Tombol untuk menandai semua anggota sebagai Hadir, Izin, atau Alpa sekaligus
- **Real-time Updates**: Perubahan status absensi langsung terlihat pada interface
- **Validasi Input**: Sistem memastikan semua anggota telah diberi status absensi

### 2. Manajemen Data Absensi

- **Filter dan Pencarian**: Pencarian berdasarkan nama anggota, username, atau lokasi latihan
- **Filter Status**: Filter berdasarkan status absensi (Hadir/Izin/Alpa)
- **Filter Periode**: Filter berdasarkan rentang tanggal latihan
- **Filter Jadwal**: Filter berdasarkan jadwal latihan tertentu

### 3. Pelaporan dan Export

- **Laporan Statistik**: Menampilkan persentase kehadiran secara keseluruhan
- **Export CSV**: Export data absensi dalam format CSV untuk analisis lebih lanjut
- **Data Lengkap**: Export mencakup semua informasi relevan (jadwal, anggota, status, waktu)

### 4. Integrasi Sistem

- **Terintegrasi dengan Jadwal Latihan**: Tombol absensi langsung tersedia pada halaman daftar jadwal
- **Role-based Access**: Hanya admin dan pembina yang dapat mengakses fitur absensi
- **Audit Trail**: Pencatatan user yang melakukan input absensi dan waktu perubahan

## Struktur File

```
modules/absenlatihan/
├── index.php          # Halaman utama daftar absensi
├── absen.php          # Form input absensi
└── export.php         # Export data ke CSV
```

### File Dependencies

- `includes/auth_check.php` - Validasi autentikasi user
- `config/database.php` - Koneksi database
- `includes/cache.php` - Sistem caching
- `includes/header.php` - Header dan sidebar
- `includes/footer.php` - Footer aplikasi

## Alur Kerja

### Alur Input Absensi

```
1. Admin/Pembina mengakses halaman Jadwal Latihan
2. Klik tombol "Absensi" pada jadwal yang dipilih
3. Sistem menampilkan form absensi dengan daftar anggota
4. Admin/Pembina memilih status absensi untuk setiap anggota
5. Klik "Simpan Absensi" untuk menyimpan data
6. Sistem menyimpan ke database dan redirect ke halaman jadwal
```

### Alur Melihat Data Absensi

```
1. Admin/Pembina mengakses menu "Absensi Latihan"
2. Sistem menampilkan daftar semua data absensi
3. User dapat menggunakan filter untuk mencari data spesifik
4. Klik "Export Excel" untuk download data dalam format CSV
```

## Database Schema

### Tabel absen_latihan

```sql
CREATE TABLE `absen_latihan` (
  `id_absen` int(11) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `status_hadir` enum('hadir','izin','alpa') DEFAULT 'hadir',
  `jam_absen` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_modified` int(11) DEFAULT NULL,
  `user_record` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Relasi Database

- `absen_latihan.id_jadwal` → `jadwal_latihan.id_jadwal` (Foreign Key)
- `absen_latihan.id_user` → `user.id_user` (Foreign Key)
- `absen_latihan.user_modified` → `user.id_user` (Foreign Key)
- `absen_latihan.user_record` → `user.id_user` (Foreign Key)

### Indexes

- PRIMARY KEY: `id_absen`
- UNIQUE KEY: `uniq_user_jadwal` (`id_jadwal`, `id_user`)
- KEY: `fk_absen_user` (`id_user`)
- KEY: `idx_user_status` (`id_user`, `status_hadir`)

## Interface Pengguna

### Halaman Input Absensi (absen.php)

#### Header Informasi Jadwal
- Menampilkan detail jadwal (tanggal, jam, lokasi, catatan)
- Informasi dalam format kartu yang menarik

#### Daftar Anggota
- Setiap anggota ditampilkan dalam kartu individual
- Avatar dengan inisial nama
- Radio button untuk pilihan status (Hadir/Izin/Alpa)
- Visual feedback dengan warna berbeda untuk setiap status

#### Tombol Aksi Massal
- "Hadir Semua": Menandai semua anggota sebagai hadir
- "Izin Semua": Menandai semua anggota sebagai izin
- "Alpa Semua": Menandai semua anggota sebagai alpa

#### Form Controls
- Tombol "Simpan Absensi" untuk menyimpan perubahan
- Tombol "Kembali" untuk kembali ke halaman jadwal
- Validasi JavaScript untuk memastikan semua field terisi

### Halaman Daftar Absensi (index.php)

#### Filter dan Pencarian
- Search box untuk pencarian nama/username/lokasi
- Dropdown filter status absensi
- Dropdown filter jadwal latihan
- Input tanggal mulai dan selesai

#### Tabel Data
- Menampilkan data dalam format tabel responsif
- Kolom: No, Jadwal Latihan, Anggota, Status, Waktu Absen
- Pagination untuk handling data besar
- Mobile-friendly dengan card view

#### Statistik
- Kartu statistik menampilkan jumlah Hadir, Izin, Alpa
- Persentase kehadiran
- Visual dengan progress bar

#### Export
- Tombol "Export Excel" untuk download CSV
- Export mempertahankan filter yang aktif

## Keamanan dan Akses

### Role-based Access Control

- **Admin**: Akses penuh ke semua fitur absensi
- **Pembina**: Akses penuh ke semua fitur absensi
- **Anggota**: Tidak dapat mengakses modul absensi

### Validasi Input

- **Server-side Validation**: Semua input divalidasi di server
- **SQL Injection Protection**: Menggunakan prepared statements
- **XSS Protection**: Input di-escape menggunakan htmlspecialchars
- **CSRF Protection**: Implementasi token CSRF (jika diperlukan)

### Audit Trail

- **User Tracking**: Mencatat user yang melakukan input absensi
- **Timestamp**: Waktu setiap perubahan tercatat
- **History**: Data perubahan tersimpan untuk audit

## Teknologi yang Digunakan

### Backend

- **PHP 8.2+**: Bahasa pemrograman utama
- **MySQL/MariaDB**: Database management system
- **PDO**: Database abstraction layer
- **Session Management**: PHP native sessions

### Frontend

- **HTML5**: Struktur markup
- **CSS3**: Styling dan layout
- **Bootstrap 5**: CSS framework
- **JavaScript**: Interaktivitas client-side
- **Font Awesome**: Icon library

### Libraries dan Frameworks

- **Bootstrap 5**: Responsive design
- **jQuery**: DOM manipulation (jika diperlukan)
- **Cache System**: Custom caching untuk performa

### Tools dan Utilities

- **Composer**: Dependency management (jika diperlukan)
- **Git**: Version control
- **PHP Syntax Checker**: Validasi kode

## Performa dan Optimisasi

### Caching

- **Database Query Caching**: Menggunakan sistem cache untuk query berulang
- **Statistics Caching**: Cache data statistik selama 10 menit
- **Jadwal List Caching**: Cache daftar jadwal untuk performa

### Database Optimization

- **Indexes**: Proper indexing pada kolom yang sering di-query
- **Prepared Statements**: Mencegah SQL injection dan improve performa
- **Pagination**: Limit data yang ditampilkan per halaman

### Frontend Optimization

- **Responsive Design**: Mobile-first approach
- **Lazy Loading**: Load content on demand
- **Minimal JavaScript**: Hanya JavaScript yang diperlukan

## Testing dan Quality Assurance

### Unit Testing

- **Syntax Validation**: PHP -l untuk validasi syntax
- **Database Connection**: Testing koneksi database
- **Input Validation**: Testing validasi form

### Integration Testing

- **User Authentication**: Testing role-based access
- **Database Operations**: Testing CRUD operations
- **Export Functionality**: Testing CSV generation

### User Acceptance Testing

- **Workflow Testing**: End-to-end testing alur kerja
- **UI/UX Testing**: Testing interface dan user experience
- **Cross-browser Testing**: Testing pada berbagai browser

## Maintenance dan Support

### Error Handling

- **Try-catch Blocks**: Exception handling untuk database operations
- **Error Logging**: Logging error ke file logs/php_errors.log
- **User-friendly Messages**: Pesan error yang informatif untuk user

### Monitoring

- **Access Logs**: Monitoring akses ke modul
- **Performance Monitoring**: Monitoring query performance
- **Error Monitoring**: Monitoring dan alerting untuk error

### Documentation

- **Code Comments**: Dokumentasi inline dalam kode
- **README Files**: Dokumentasi untuk developer
- **User Guides**: Panduan penggunaan untuk end-user

## Kesimpulan

Modul absensi latihan telah berhasil diimplementasikan dengan fitur-fitur yang komprehensif dan user-friendly. Sistem ini memenuhi semua kebutuhan absensi latihan grup hadrah dengan memperhatikan aspek keamanan, performa, dan user experience.

Modul ini terintegrasi penuh dengan sistem existing dan mengikuti pola arsitektur yang sudah ada, sehingga memudahkan maintenance dan pengembangan di masa depan.

---

**Versi**: 1.0
**Tanggal**: Januari 2025
**Developer**: BLACKBOXAI
**Status**: Production Ready
