# Modul Jadwal Latihan

Dokumentasi lengkap untuk modul pengelolaan jadwal latihan pada aplikasi Hadrah.

## 📋 Fitur

- **CRUD Jadwal**: Tambah, lihat, edit, dan hapus jadwal latihan
- **Status Management**: Kelola status jadwal (Direncanakan, Selesai, Dibatalkan)
- **Auto-Cancel**: Jadwal yang tanggalnya sudah terlewatkan secara otomatis diubah ke status "Dibatalkan"
- **Pagination**: Navigasi halaman untuk data banyak
- **Search & Filter**: Pencarian berdasarkan lokasi atau catatan
- **Date Range Filter**: Filter jadwal berdasarkan rentang tanggal
- **Status Filter**: Filter berdasarkan status (Semua, Direncanakan, Selesai, Dibatalkan)
- **Real-Time Validation**: Validasi input dan deteksi jadwal duplikat secara real-time dengan AJAX
- **Caching**: Implementasi file-based caching untuk meningkatkan performa
- **Toast Notifications**: Feedback visual untuk setiap aksi pengguna
- **Responsive Design**: Tampilan tabel untuk desktop, card untuk mobile
- **Floating Action Button (FAB)**: Pintasan tambah jadwal di pojok layar
- **Confirmation Modal**: Konfirmasi sebelum submit atau hapus
- **Auto-Submit Form**: Form filter otomatis submit saat ada perubahan
- **CASCADE Delete**: Penghapusan jadwal juga menghapus data absensi terkait
- **Audit Trail**: user_record dan user_modified untuk tracking

## 📁 Struktur File

```
modules/jadwallatihan/
├── index.php              # Halaman utama - daftar jadwal dengan pagination & filter
├── tambah.php             # Form tambah jadwal baru dengan validasi real-time
├── edit.php               # Form edit jadwal dengan kelola status
├── hapus.php              # Konfirmasi hapus jadwal
├── api_check_jadwal.php   # API endpoint untuk validasi real-time jadwal duplikat
└── README.md             # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `jadwal_latihan`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_jadwal` | INT | Primary Key (Auto Increment) |
| `tanggal` | DATE | Tanggal latihan |
| `jam_mulai` | TIME | Jam mulai latihan |
| `lokasi` | VARCHAR(150) | Lokasi latihan |
| `status` | ENUM | `direncanakan`, `selesai`, atau `dibatalkan` |
| `catatan` | TEXT | Catatan tambahan |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |
| `user_record` | INT | User yang mencatat |

### Indeks Database

```sql
-- Index untuk performa query filter tanggal dan status
ADD KEY `idx_tanggal_status` (`tanggal`,`status`);
```

### Hubungan Antar Tabel

```
jadwal_latihan (1) ───────> (N) absen_latihan (id_jadwal)
     id_jadwal
```

### Constraint dengan CASCADE

```sql
-- Penghapusan jadwal akan menghapus semua absensi terkait
ALTER TABLE `absen_latihan`
ADD CONSTRAINT `fk_absen_jadwal` FOREIGN KEY (`id_jadwal`) 
  REFERENCES `jadwal_latihan` (`id_jadwal`) ON DELETE CASCADE;
```

## 🔄 Auto-Cancel Feature

### Cara Kerja

Sistem secara otomatis membatalkan jadwal yang tanggalnya sudah terlewatkan:

```php
// Di index.php - auto-update schedules yang sudah lewat
$stmt = $pdo->prepare("UPDATE jadwal_latihan 
                       SET status = 'dibatalkan', 
                           catatan = CONCAT(COALESCE(catatan, ''), 
                           ' [Otomatis dibatalkan: tanggal sudah terlewatkan]'),
                           updated_at = NOW()
                       WHERE status = 'direncanakan' 
                       AND tanggal < CURDATE()");
$stmt->execute();
```

### Trigger Auto-Cancel

- Berjalan otomatis saat halaman dimuat
- Hanya mempengaruhi jadwal dengan status "Direncanakan"
- Menambahkan catatan otomatis pada jadwal yang dibatalkan
- Menghapus cache setelah pembatalan

## 🔄 Real-Time Validation

### Validasi Jadwal Duplikat

Modul ini mengimplementasikan validasi real-time untuk mencegah jadwal duplikat:

```javascript
// Validasi saat user keluar dari field jam (onblur)
fetch('../api/check_jadwal.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'tanggal=2024-01-15&jam_mulai=19:00'
})

// Response
{
    "success": true,
    "exists": true,
    "message": "Jadwal latihan pada tanggal dan jam tersebut sudah ada!"
}
```

### Endpoint API

```php
// Validasi jadwal (mode=add)
POST api/check_jadwal.php
Body: tanggal=2024-01-15&jam_mulai=19:00

// Validasi jadwal dengan pengecualian (mode=edit)
POST api/check_jadwal.php
Body: tanggal=2024-01-15&jam_mulai=19:00&exclude_id=5

// Response sukses (jadwal tersedia)
{
    "success": true,
    "exists": false,
    "message": "Jadwal tersedia"
}

// Response error (jadwal duplikat)
{
    "success": true,
    "exists": true,
    "message": "Jadwal latihan pada tanggal dan jam tersebut sudah ada!"
}
```

### Jenis Validasi

| Field | Validasi | Pesan Error |
|-------|----------|-------------|
| **Tanggal** | Tidak boleh kurang dari hari ini, wajib diisi | "Tanggal tidak boleh kurang dari hari ini" |
| **Jam** | Wajib diisi | "Jam mulai wajib diisi" |
| **Lokasi** | 3-150 karakter, wajib diisi | "Lokasi minimal/maximal karakter" |
| **Catatan** | Maksimal 500 karakter | "Catatan maksimal 500 karakter" |
| **Duplikasi** | Tidak boleh ada jadwal sama tanggal & jam | "Jadwal sudah ada!" |

## 🗃️ Caching System

### Arsitektur Caching

Modul menggunakan file-based caching untuk meningkatkan performa:

```php
// Lokasi cache directory
includes/cache/

// Struktur file cache
├── [md5_hash].cache  # File cache dengan data ter-serialize
└── ...
```

### Cache Utility Class

```php
class Cache {
    // Metode utama
    public static function get($key)                    // Ambil data dari cache
    public static function set($key, $value, $ttl)      // Simpan ke cache
    public static function delete($key)                  // Hapus cache tertentu
    public static function remember($key, $callback, $ttl) // Get or set pattern
}
```

### Data yang Di-cache

| Cache Key | TTL | Deskripsi |
|-----------|-----|-----------|
| `jadwal_stats` | 10 menit | Statistik jumlah jadwal per status |
| `jadwal_max_date` | 10 menit | Tanggal maksimal untuk default filter |

### Contoh Penggunaan

```php
// Cache statistics dengan auto-refresh
$stats = Cache::remember('jadwal_stats', function() use ($pdo) {
    $stmt = $pdo->query("SELECT
        SUM(CASE WHEN status = 'direncanakan' THEN 1 ELSE 0 END) as direncanakan,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
        FROM jadwal_latihan");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}, 600);
```

### Invalidasi Cache

Cache diinvalidasi secara otomatis saat terjadi perubahan data:

| Operasi | File | Kode |
|---------|------|------|
| Tambah Jadwal | `tambah.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |
| Edit Jadwal | `edit.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |
| Hapus Jadwal | `hapus.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |
| Auto-Cancel | `index.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |

## 🔔 Toast Notifications

Sistem notifikasi popup untuk feedback pengguna:

| Pesan | Tipe | Warna |
|-------|------|-------|
| Jadwal latihan berhasil ditambahkan! | Sukses | bg-success |
| Data jadwal latihan berhasil diperbarui! | Sukses | bg-success |
| Jadwal latihan berhasil dihapus! | Sukses | bg-success |
| Status jadwal berhasil diperbarui! | Sukses | bg-success |
| Terjadi kesalahan! | Error | bg-danger |
| Anda tidak memiliki akses untuk operasi ini! | Warning | bg-warning |

## 🔧 Cara Penggunaan

### 1. Menambah Jadwal Latihan
1. Klik tombol FAB (+) di pojok kanan bawah
2. Masukkan tanggal latihan (minimal hari ini)
3. Masukkan jam mulai latihan
4. Masukkan lokasi latihan (minimal 3 karakter)
5. (Opsional) Tambahkan catatan
6. Klik "Simpan" → Review di modal konfirmasi
7. Klik "Konfirmasi" untuk menyimpan

### 2. Melihat Daftar Jadwal
1. Halaman index menampilkan semua jadwal
2. Gunakan search untuk filter berdasarkan lokasi/catatan
3. Filter berdasarkan status menggunakan dropdown
4. Filter berdasarkan rentang tanggal
5. Navigasi halaman menggunakan pagination
6. Lihat status pada badge warna (Kuning=Direncanakan, Hijau=Selesai, Merah=Dibatalkan)

### 3. Mengedit Jadwal
1. Klik tombol Edit pada jadwal yang ingin diubah
2. Ubah data yang diperlukan (tanggal, jam, lokasi, catatan)
3. Ubah status jika diperlukan:
   - **Direncanakan**: Jadwal upcoming
   - **Selesai**: Jadwal sudah berlangsung
   - **Dibatalkan**: Jadwal dibatalkan
4. Klik "Simpan Perubahan" → Review di modal

### 4. Menghapus Jadwal (Admin Only)
1. Klik tombol Hapus pada jadwal yang ingin dihapus
2. Konfirmasi penghapusan pada halaman
3. **Peringatan**: 
   - Menghapus jadwal juga menghapus data absensi terkait (CASCADE)
   - Tindakan ini tidak dapat dibatalkan

### 5. Auto-Cancel Jadwal
- Sistem otomatis membatalkan jadwal yang tanggalnya sudah terlewatkan
- Berjalan saat halaman index dimuat
- Status berubah dari "Direncanakan" → "Dibatalkan"
- Catatan otomatis ditambahkan: "[Otomatis dibatalkan: tanggal sudah terlewatkan]"

### 6. Pencarian dan Filter
- **Search**: Masukkan kata kunci untuk filter lokasi/catatan
- **Status Filter**: Pilih status (Semua/Direncanakan/Selesai/Dibatalkan)
- **Date Range**: Pilih tanggal mulai dan selesai
- Form auto-submit saat ada perubahan pada filter

## 👥 Hak Akses

| Peran | Tambah | Lihat | Edit | Hapus | Absensi |
|-------|--------|-------|------|-------|---------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Pembina | ✓ | ✓ | ✓ | ✗ | ✓ |
| Anggota | ✗ | ✓ | ✗ | ✗ | ✗ |

**Catatan**: 
- Anggota hanya bisa melihat jadwal (tidak bisa tambah/edit/hapus)
- Hanya Admin yang bisa menghapus jadwal
- Pembina dan Admin bisa mengubah status jadwal
- Absensi hanya bisa diambil untuk jadwal dengan status "Direncanakan"

## 📱 Responsive Design

| Tampilan | Komponen |
|----------|----------|
| **Desktop** | Tabel dengan kolom No, Tanggal, Jam, Lokasi, Catatan, Status, Aksi |
| **Mobile** | Card view dengan layout vertikal |
| **FAB** | Floating Action Button untuk tambah data (admin/pembina only) |
| **Modal** | Bootstrap 5 modal yang responsive |
| **Toast** | Fixed position dengan z-index tinggi |

### Tampilan Tabel (Desktop)

- Avatar tanggal dengan format kalender (tgl + bulan)
- Badge status: Kuning=Direncanakan, Hijau=Selesai, Merah=Dibatalkan
- Action buttons dengan icon dan tooltip
- Tooltip pada kolom catatan (jika terlalu panjang)
- Tombol absensi hanya untuk jadwal "Direncanakan"

### Tampilan Card (Mobile)

- Layout vertikal dengan avatar tanggal besar
- Badge status
- Tombol Edit, Absensi, dan Hapus full-width
- Touch-friendly buttons
- Info lengkap (tanggal, jam, lokasi, catatan)

## 📊 Statistics Cards

Modul menampilkan statistik jadwal di bagian bawah halaman:

```html
<div class="row mt-4">
    <div class="col-md-4 mb-3">
        <div class="card bg-warning text-dark">
            <!-- Direncanakan Count -->
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-success text-white">
            <!-- Selesai Count -->
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-danger text-white">
            <!-- Dibatalkan Count -->
        </div>
    </div>
</div>
```

## 🎨 User Interface Features

### Floating Action Button (FAB)

```html
<?php if (in_array($user_peran, ['admin', 'pembina'])): ?>
    <a href="tambah.php" class="floating-btn" title="Tambah Jadwal">
        <i class="fas fa-plus"></i>
    </a>
<?php endif; ?>
```

### Avatar Tanggal

```php
<?php
$tanggal = new DateTime($jadwal['tanggal']);
?>
<div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" 
     style="width: 40px; height: 40px; font-size: 12px;">
    <?= $tanggal->format('d') ?>
    <span class="d-block" style="font-size: 9px;"><?= $tanggal->format('M') ?></span>
</div>
```

### Badge Status

```php
<?php
$status_class = match($jadwal['status']) {
    'selesai' => 'bg-success',
    'dibatalkan' => 'bg-danger',
    default => 'bg-warning text-dark'
};
?>
<span class="badge <?= $status_class ?>">
    <?= ucfirst($jadwal['status']) ?>
</span>
```

### Preview Form

Form dilengkapi preview real-time sebelum submit:

```html
<!-- Modal Konfirmasi -->
<div class="modal-body">
    <div class="alert alert-light border rounded p-3">
        <div class="row mb-2">
            <div class="col-4 fw-bold">Tanggal:</div>
            <div class="col-8" id="confirmTanggal">-</div>
        </div>
        <div class="row mb-2">
            <div class="col-4 fw-bold">Jam:</div>
            <div class="col-8" id="confirmJam">-</div>
        </div>
        <!-- ... more fields -->
    </div>
</div>
```

## 🔒 Keamanan

- **Session Validation**: `auth_check.php` di setiap halaman
- **Role Check**: Permission verification untuk tambah, edit, dan hapus
- **SQL Injection Protection**: Prepared statements di semua query
- **XSS Protection**: `htmlspecialchars()` pada semua output
- **Input Sanitization**: strip_tags, htmlspecialchars, validasi format
- **Rate Limiting**: Via `includes/rate_limit.php`
- **Audit Trail**: user_record dan user_modified untuk tracking
- **CASCADE Protection**: Constraint pada database untuk referential integrity

### Validasi Input

```php
// Search input sanitization
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// ID validation
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Date format validation
function validateDateFormat($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Status whitelist validation
$allowed_status = ['direncanakan', 'selesai', 'dibatalkan', ''];
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_status) 
    ? $_GET['status'] : '';
```

## 📦 Integrasi

Modul jadwal latihan terintegrasi dengan:

| Modul | Jenis Integrasi |
|-------|-----------------|
| **Absen Latihan** | FK ke jadwal_latihan (id_jadwal) dengan CASCADE |
| **Sidebar** | Menu navigasi utama |
| **Auth** | Session validation per halaman |
| **Cache** | File-based caching untuk statistik |
| **Database** | Koneksi via config/database.php |

### Query untuk Statistik Jadwal

```php
// Statistik dengan aggregation (cached)
$stmt = $pdo->query("SELECT
    SUM(CASE WHEN status = 'direncanakan' THEN 1 ELSE 0 END) as direncanakan,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
    FROM jadwal_latihan");
```

## 📈 Dashboard Statistics

Modul jadwal latihan menampilkan statistik dengan visual cards:

| Statistik | Warna | Icon | Keterangan |
|-----------|-------|------|------------|
| Direncanakan | bg-warning | fa-calendar-alt | Jadwal upcoming |
| Selesai | bg-success | fa-check-circle | Jadwal sudah berlangsung |
| Dibatalkan | bg-danger | fa-times-circle | Jadwal dibatalkan |

## 🚀 Pengembangan Selanjutnya

- [x] CRUD Jadwal Dasar
- [x] Status Management (Direncanakan, Selesai, Dibatalkan)
- [x] Auto-Cancel Jadwal Terlewatkan
- [x] Pagination
- [x] Search & Filter
- [x] Date Range Filter
- [x] Real-Time Validation (Duplikat Jadwal)
- [x] Caching System
- [x] Toast Notifications
- [x] Responsive Design (Table + Card)
- [x] Confirmation Modal
- [x] Auto-Submit Form
- [ ] Calendar View
- [ ] Recurring Schedules (jadwal berulang)
- [ ] Export ke PDF/Excel
- [ ] Email/SMS Reminder
- [ ] Bulk Operations
- [ ] Riwayat Perubahan Jadwal
- [ ] Multi-day Schedule Support
- [ ] Lokasi Favorit (autocomplete)

## 🐛 Riwayat Perbaikan

### v1.2.0 - Implementasi Caching
- **Fitur**: Menambahkan file-based caching untuk statistik jadwal
- **Improvement**: Mengurangi query database dari 4 menjadi 1 per halaman
- **Cache Keys**: jadwal_stats (10 menit), jadwal_max_date (10 menit)
- **File Baru**: includes/cache.php
- **File Diedit**: index.php, tambah.php, edit.php, hapus.php

### v1.1.0 - Auto-Cancel Feature
- **Fitur**: Otomatis membatalkan jadwal yang tanggalnya sudah terlewatkan
- **Improvement**: Menambahkan catatan otomatis pada jadwal yang dibatalkan
- **Improvement**: Cache invalidation setelah auto-cancel
- **File Diedit**: index.php

### v1.0.0
- Rilis awal modul jadwal latihan
- CRUD dasar jadwal
- Status management
- Search dan filter
- Pagination
- Responsive design

---

*Modul Jadwal Latihan v1.2.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

