# Modul Dresscode

Dokumentasi lengkap untuk modul pengelolaan dresscode/pakaian pada aplikasi Hadrah.

## 📋 Fitur

- **CRUD Dresscode**: Tambah, lihat, edit, dan hapus dresscode
- **Status Management**: Kelola status dresscode (Aktif/Nonaktif)
- **Color Support**: Simpan informasi warna dengan kalkulasi brightness otomatis
- **Pagination**: Navigasi halaman untuk data banyak
- **Search & Filter**: Pencarian berdasarkan nama pakaian, deskripsi, atau warna
- **Status Filter**: Filter berdasarkan status (Aktif/Nonaktif/Semua)
- **Real-Time Validation**: Validasi input nama pakaian secara real-time dengan AJAX
- **Toast Notifications**: Feedback visual untuk setiap aksi pengguna
- **Responsive Design**: Tampilan tabel untuk desktop, card untuk mobile
- **Floating Action Button (FAB)**: Pintasan tambah dresscode di pojok layar
- **Confirmation Modal**: Konfirmasi sebelum submit atau hapus
- **Audit Trail**: user_record dan user_modified untuk tracking

## 📁 Struktur File

```
modules/dresscode/
├── index.php              # Halaman utama - daftar dresscode dengan pagination & filter
├── tambah.php             # Form tambah dresscode baru dengan validasi real-time
├── edit.php               # Form edit dresscode
├── hapus.php              # Konfirmasi hapus dresscode
├── api_check_dresscode.php     # API endpoint untuk validasi real-time nama pakaian
├── api_check_dresscode_edit.php # API endpoint untuk validasi real-time (edit mode)
└── README.md              # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `dresscode`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_dresscode` | INT | Primary Key (Auto Increment) |
| `nama_pakaian` | VARCHAR(100) | Nama pakaian yang unik |
| `deskripsi` | TEXT | Deskripsi detail pakaian |
| `warna` | VARCHAR(50) | Informasi warna (nama atau hex) |
| `status` | ENUM | `aktif` atau `nonaktif` |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |
| `user_record` | INT | User yang mencatat |

### Hubungan Antar Tabel

```
dresscode (1) ───────> (N) booking_acara (id_dresscode)
     id_dresscode
```

### Constraint dengan SET NULL

```sql
-- Saat dresscode dihapus, id_dresscode di booking_acara akan diset NULL
ALTER TABLE `booking_acara`
ADD CONSTRAINT `fk_booking_dresscode` FOREIGN KEY (`id_dresscode`) 
  REFERENCES `dresscode` (`id_dresscode`) ON DELETE SET NULL;
```

## 🔄 Real-Time Validation

### Validasi Nama Pakaian

Modul ini mengimplementasikan validasi real-time untuk nama pakaian menggunakan AJAX:

```javascript
// Validasi saat user keluar dari field (onblur)
fetch('../../api/check_dresscode.php?nama_pakaian=Kemeja+Putih')

// Response
{
    "success": true,
    "exists": true,
    "message": "Nama pakaian sudah digunakan!"
}
```

### Endpoint API

```php
// Validasi nama pakaian (mode=add)
GET api/check_dresscode.php?nama_pakaian=Kemeja Putih

// Validasi nama pakaian dengan pengecualian (mode=edit)
GET api/check_dresscode_edit.php?nama_pakaian=Kemeja Putih&exclude_id=5

// Response sukses (nama tersedia)
{
    "success": true,
    "exists": false,
    "message": ""
}

// Response error (nama sudah ada)
{
    "success": true,
    "exists": true,
    "message": "Nama pakaian sudah digunakan!"
}
```

### Jenis Validasi

| Field | Validasi | Pesan Error |
|-------|----------|-------------|
| **Nama Pakaian** | Wajib diisi, maksimal 100 karakter, unik | "Nama pakaian sudah digunakan!" |
| **Deskripsi** | Opsional | - |
| **Warna** | Opsional, maksimal 50 karakter | - |
| **Status** | Wajib dipilih (aktif/nonaktif) | - |

## 🎨 Color Support

### Kalkulasi Brightness Otomatis

Sistem secara otomatis menghitung brightness warna untuk menentukan warna teks:

```php
<?php
// Convert hex ke RGB
$hex = ltrim($warna, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));

// Kalkulasi brightness menggunakan formula YIQ
$brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

// Tentukan warna teks
$text_color = $brightness > 128 ? '#000000' : '#ffffff';
?>

<!-- Badge dengan warna dinamis -->
<span class="badge" 
      style="background-color: <?= $warna ?>; color: <?= $text_color ?>; border: 1px solid <?= $warna ?>;">
    <i class="fas fa-palette me-1"></i><?= htmlspecialchars($dc['warna']) ?>
</span>
```

### Contoh Warna

| Input Warna | Background | Text Color |
|-------------|------------|------------|
| `#FFFFFF` (Putih) | `#FFFFFF` | `#000000` (hitam) |
| `#000000` (Hitam) | `#000000` | `#ffffff` (putih) |
| `#FF0000` (Merah) | `#FF0000` | `#ffffff` (putih) |
| `#00FF00` (Hijau) | `#00FF00` | `#000000` (hitam) |

## 🔔 Toast Notifications

Sistem notifikasi popup untuk feedback pengguna:

| Pesan | Tipe | Warna |
|-------|------|-------|
| Dresscode baru berhasil ditambahkan! | Sukses | bg-success |
| Data dresscode berhasil diperbarui! | Sukses | bg-success |
| Dresscode berhasil dihapus! | Sukses | bg-success |
| Terjadi kesalahan! | Error | bg-danger |

### Contoh Penggunaan

```php
// Redirect dengan parameter pesan
header('Location: index.php?msg=tambah_sukes');

// atau
header('Location: index.php?msg=edit_sukes');
header('Location: index.php?msg=hapus_sukes');
header('Location: index.php?msg=error');
```

## 🔧 Cara Penggunaan

### 1. Menambah Dresscode Baru
1. Klik tombol FAB (+) di pojok kanan bawah
2. Masukkan nama pakaian (maksimal 100 karakter, unik)
3. (Opsional) Masukkan deskripsi pakaian
4. (Opsional) Masukkan informasi warna
5. Pilih status (Aktif/Nonaktif)
6. Klik "Simpan" → Review di modal konfirmasi
7. Klik "Konfirmasi" untuk menyimpan

### 2. Melihat Daftar Dresscode
1. Halaman index menampilkan semua dresscode
2. Gunakan search untuk filter berdasarkan nama/deskripsi/warna
3. Filter berdasarkan status menggunakan dropdown
4. Navigasi halaman menggunakan pagination
5. Lihat status pada badge warna (Hijau=Aktif, Abu=Nonaktif)
6. Lihat warna pada badge dengan warna background dinamis

### 3. Mengedit Dresscode
1. Klik tombol Edit pada dresscode yang ingin diubah
2. Ubah data yang diperlukan (nama, deskripsi, warna, status)
3. **Perhatian**: Jika mengubah nama, sistem akan memvalidasi keunikan
4. Klik "Simpan Perubahan" → Review di modal

### 4. Menghapus Dresscode
1. Klik tombol Hapus pada dresscode yang ingin dihapus
2. Konfirmasi penghapusan pada halaman
3. **Perhatian**: 
   - Data dresscode dihapus permanen (hard delete)
   - Booking acara yang menggunakan dresscode ini akan memiliki id_dresscode = NULL
   - Tindakan ini tidak dapat dibatalkan

### 5. Pencarian dan Filter
- **Search**: Masukkan kata kunci untuk filter nama/deskripsi/warna
- **Status Filter**: Pilih status (Semua/Aktif/Nonaktif)
- Klik "Filter" untuk menerapkan filter
- Klik "Reset" untuk清除 filter

## 👥 Hak Akses

| Peran | Tambah | Lihat | Edit | Hapus |
|-------|--------|-------|------|-------|
| Admin | ✓ | ✓ | ✓ | ✓ |
| Pembina | ✓ | ✓ | ✓ | ✗ |
| Anggota | ✗ | ✓ | ✗ | ✗ |

**Catatan**: 
- Anggota hanya bisa melihat daftar dresscode
- Hanya Admin yang bisa menghapus dresscode
- Pembina dan Admin bisa menambah dan edit dresscode

## 📱 Responsive Design

| Tampilan | Komponen |
|----------|----------|
| **Desktop** | Tabel dengan kolom No, Nama, Deskripsi, Warna, Status, Dibuat, Aksi |
| **Mobile** | Card view dengan layout vertikal |
| **FAB** | Floating Action Button untuk tambah data |
| **Modal** | Bootstrap 5 modal yang responsive |
| **Toast** | Fixed position dengan z-index tinggi |

### Tampilan Tabel (Desktop)

- Icon pakaian (tshirt) dengan background sukses
- Badge warna dengan warna background dinamis
- Badge status: Hijau=Aktif, Abu=Nonaktif
- Action buttons dengan icon dan tooltip
- Format tanggal DD/MM/YYYY

### Tampilan Card (Mobile)

- Layout vertikal dengan avatar icon pakaian besar
- Badge status dan warna
- Tombol Edit dan Hapus full-width
- Touch-friendly buttons
- Info lengkap (nama, deskripsi, warna, tanggal)

## 📊 Statistics Cards

Modul dresscode menampilkan statistik di bagian bawah halaman:

```html
<div class="row mt-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-tshirt"></i></div>
            <div class="stat-value"><?= $count_aktif ?></div>
            <div class="stat-label">Dresscode Aktif</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-secondary">
            <div class="stat-icon"><i class="fas fa-eye-slash"></i></div>
            <div class="stat-value"><?= $count_nonaktif ?></div>
            <div class="stat-label">Dresscode Nonaktif</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-list"></i></div>
            <div class="stat-value"><?= $total_dresscode ?></div>
            <div class="stat-label">Total Dresscode</div>
        </div>
    </div>
</div>
```

| Statistik | Warna | Icon | Keterangan |
|-----------|-------|------|------------|
| Aktif | bg-success | fa-tshirt | Dresscode yang dapat digunakan |
| Nonaktif | bg-secondary | fa-eye-slash | Dresscode yang dinonaktifkan |
| Total | bg-primary | fa-list | Semua dresscode |

## 🎨 User Interface Features

### Floating Action Button (FAB)

```html
<a href="tambah.php" class="floating-btn" title="Tambah Dresscode">
    <i class="fas fa-plus"></i>
</a>
```

### Avatar Icon Pakaian

```php
<div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
     style="width: 40px; height: 40px; font-size: 16px;">
    <i class="fas fa-tshirt"></i>
</div>
```

### Badge Warna Dinamis

```php
<?php
// Kalkulasi brightness untuk warna teks
$brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
$text_color = $brightness > 128 ? '#000000' : '#ffffff';
?>
<span class="badge" 
      style="background-color: <?= $warna ?>; color: <?= $text_color ?>;">
    <i class="fas fa-palette me-1"></i><?= htmlspecialchars($dc['warna']) ?>
</span>
```

### Preview Form

Form dilengkapi preview real-time sebelum submit:

```html
<!-- Modal Konfirmasi -->
<div class="modal-body">
    <div class="alert alert-light border rounded p-3">
        <div class="row mb-2">
            <div class="col-4 fw-bold">Nama Pakaian:</div>
            <div class="col-8" id="confirmNamaPakaian">-</div>
        </div>
        <div class="row mb-2">
            <div class="col-4 fw-bold">Deskripsi:</div>
            <div class="col-8" id="confirmDeskripsi">-</div>
        </div>
        <div class="row mb-2">
            <div class="col-4 fw-bold">Warna:</div>
            <div class="col-8" id="confirmWarna">-</div>
        </div>
        <div class="row">
            <div class="col-4 fw-bold">Status:</div>
            <div class="col-8"><span class="badge bg-success" id="confirmStatus">Aktif</span></div>
        </div>
    </div>
</div>
```

## 🔒 Keamanan

- **Session Validation**: `auth_check.php` di setiap halaman
- **Admin Check**: Role verification untuk hapus
- **SQL Injection Protection**: Prepared statements di semua query
- **XSS Protection**: `htmlspecialchars()` pada semua output
- **Input Sanitization**: strip_tags, validasi format
- **Rate Limiting**: Via `includes/rate_limit.php`
- **Audit Trail**: user_record dan user_modified untuk tracking
- **Soft Delete via Status**: Menggunakan status aktif/nonaktif alih-alih hard delete

### Validasi Input

```php
// Search input sanitization
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Status filter whitelist
$allowed_status = ['aktif', 'nonaktif', ''];
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_status) 
    ? $_GET['status'] : '';

// ID validation
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Input validation
if (strlen($nama_pakaian) > 100) {
    $errors[] = "Nama pakaian maksimal 100 karakter!";
}
```

## 📦 Integrasi

Modul dresscode terintegrasi dengan:

| Modul | Jenis Integrasi |
|-------|-----------------|
| **Booking Acara** | FK ke dresscode (id_dresscode) dengan SET NULL |
| **Sidebar** | Menu navigasi utama |
| **Auth** | Session validation per halaman |
| **Database** | Koneksi via config/database.php |
| **API** | Validasi real-time via check_dresscode.php |

### Query untuk Statistik

```php
// Count dresscode aktif
$count_aktif = $pdo->query("SELECT COUNT(*) FROM dresscode WHERE status = 'aktif'")->fetchColumn();

// Count dresscode nonaktif
$count_nonaktif = $pdo->query("SELECT COUNT(*) FROM dresscode WHERE status = 'nonaktif'")->fetchColumn();
```

## 🚀 Pengembangan Selanjutnya

- [x] CRUD Dresscode Dasar
- [x] Status Management (Aktif/Nonaktif)
- [x] Color Support dengan Brightness Kalkulasi
- [x] Pagination
- [x] Search & Filter
- [x] Status Filter
- [x] Real-Time Validation (Nama Pakaian)
- [x] Toast Notifications
- [x] Responsive Design (Table + Card)
- [x] Confirmation Modal
- [ ] Upload foto pakaian
- [ ] Preset warna (color picker)
- [ ] Kategori dresscode (muslimah, ikat kepala, dll)
- [ ] Export ke PDF/Excel
- [ ] Riwayat perubahan dresscode
- [ ] Multiple warna per dresscode
- [ ] QR Code untuk setiap dresscode

## 🐛 Riwayat Perbaikan

### v1.1.0 - Real-Time Validation
- **Fitur**: Menambahkan validasi nama pakaian real-time dengan AJAX
- **Endpoint API**: check_dresscode.php, check_dresscode_edit.php
- **Improvement**: Menambahkan feedback visual saat validasi
- **File Diedit**: tambah.php, edit.php

### v1.0.0
- Rilis awal modul dresscode
- CRUD dasar dresscode
- Status management
- Color support
- Search dan filter
- Pagination
- Responsive design

---

*Modul Dresscode v1.1.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

