# Modul Inventaris Alat

Dokumentasi lengkap untuk modul pengelolaan inventaris alat musik pada aplikasi Hadrah.

## 📋 Fitur

- **CRUD Alat**: Tambah, lihat, edit, dan hapus alat musik
- **Assign Pengguna**: Tetapkan pengguna/anggota untuk setiap alat
- **Multi-User Assignment**: Satu alat dapat digunakan oleh beberapa anggota secara bersamaan
- **Status Tracking**: Pantau kondisi alat (Baik/Rusak) dan pengguna aktif
- **Riwayat Penggunaan**: Lacak siapa saja yang menggunakan alat dan kapan
- **Search & Filter**: Pencarian alat berdasarkan nama
- **Responsive Design**: Tampilan tabel untuk desktop, card untuk mobile
- **Floating Action Button (FAB)**: Pintasan tambah alat di pojok layar
- **Toast Notifications**: Feedback visual untuk setiap aksi pengguna
- **Real-Time Validation**: Validasi input nama alat secara real-time dengan AJAX

## 📁 Struktur File

```
modules/alat/
├── index.php              # Halaman utama - daftar inventaris alat
├── tambah.php             # Form tambah alat baru dengan modal pemilihan pengguna
├── edit.php              # Form edit alat dengan kelola pengguna
├── hapus.php             # Konfirmasi hapus alat
├── api_check_alat.php    # API endpoint untuk validasi real-time nama alat
└── README.md             # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `alat`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_alat` | INT | Primary Key (Auto Increment) |
| `nama_alat` | VARCHAR(100) | Nama alat musik |
| `jumlah_baik` | INT | Jumlah unit dalam kondisi baik |
| `jumlah_rusak` | INT | Jumlah unit dalam kondisi rusak |
| `user_record` | INT | User yang mencatat data |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |

### Tabel: `alat_pengguna`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_alat_pengguna` | INT | Primary Key (Auto Increment) |
| `id_alat` | INT | Foreign Key ke `alat` |
| `id_user` | INT | Foreign Key ke `user` |
| `tanggal_diberikan` | DATE | Tanggal alat diberikan ke pengguna |
| `status` | ENUM | `aktif` atau `dikembalikan` |
| `keterangan` | TEXT | Catatan tambahan |
| `user_record` | INT | User yang mencatat |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |

### Hubungan Antar Tabel

```
alat (1) ───────> (N) alat_pengguna (N) <────── (1) user
     id_alat              id_alat              id_user
                         id_user
```

## 🔄 Real-Time Validation

### Validasi Nama Alat

Modul ini mengimplementasikan validasi real-time untuk nama alat menggunakan AJAX:

```javascript
// Validasi saat user keluar dari field (onblur)
fetch('api_check_alat.php?nama_alat=Ketipung&mode=add')

// Response
{
    "valid": true,
    "message": "Nama alat tersedia!"
}
```

### Endpoint API

```php
// Validasi nama alat (mode=add)
GET api_check_alat.php?nama_alat=Ketipung&mode=add

// Validasi nama alat dengan pengecualian (mode=edit)
GET api_check_alat.php?nama_alat=Ketipung&exclude_id=5&mode=edit

// Response sukses
{
    "valid": true,
    "message": "Nama alat tersedia!"
}

// Response error (nama sudah ada)
{
    "valid": false,
    "message": "Nama alat sudah digunakan!"
}
```

### Jenis Validasi

| Field | Validasi | Pesan Error |
|-------|----------|-------------|
| **Nama Alat** | Minimal 2 karakter, unik | "Nama alat sudah ada" |
| **Total Alat** | Minimal 1 | "Total alat minimal 1" |
| **Jumlah Baik** | 0 s/d Total, tidak negatif | "Jumlah baik + rusak tidak boleh melebihi total" |
| **Jumlah Rusak** | 0 s/d Total, tidak negatif | "Jumlah rusak maksimal dapat dikurangi" |

## 👥 Assign Pengguna

### Fitur Multi-User Assignment

Satu alat dapat ditugaskan kepada beberapa anggota sekaligus:

1. **Tambah Alat**: Pilih anggota saat membuat alat baru
2. **Edit Alat**: Tambah atau hapus pengguna yang ditugaskan
3. **Status Pengguna**: Pengguna yang dihapus ditandai sebagai "Dikembalikan"

### Alur Assign Pengguna

```
1. Klik tombol "Pilih Anggota"
2. Modal Bootstrap terbuka dengan daftar anggota aktif
3. Centang anggota yang ingin ditugaskan
4. Klik "Simpan Pilihan"
5. Preview anggota terpilih ditampilkan di form
6. Klik "Simpan" untuk menyimpan ke database
```

### Modal Pemilihan Anggota

```html
<!-- Modal Bootstrap dengan fitur: -->
<!-- - Search/filter anggota -->
<!-- - Select All / Deselect All -->
<!-- - Preview avatar dan nama -->
<!-- - Counter badge -->
<!-- - Keyboard shortcuts (Esc, Enter) -->
```

### Tampilan di Index

```
┌─────────────┬───────┬───────┬──────────────────────────┐
│ Nama Alat   │ Baik  │ Rusak │ Pengguna (Aktif)         │
├─────────────┼───────┼───────┼──────────────────────────┤
│ Ketipung    │   2   │   0   │ [ Budi ] [ Andi ]        │
│ Rebana      │   3   │   1   │ [ Siti ]                 │
└─────────────┴───────┴───────┴──────────────────────────┘
```

## 🔔 Toast Notifications

Sistem notifikasi popup untuk feedback pengguna:

| Pesan | Tipe | Warna |
|-------|------|-------|
| Alat baru berhasil ditambahkan! | Sukses | bg-success |
| Data alat berhasil diperbarui! | Sukses | bg-success |
| Alat berhasil dihapus! | Sukses | bg-success |
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

### 1. Menambah Alat Baru
1. Klik tombol FAB (+) di pojok kanan bawah
2. Masukkan nama alat (misal: "Ketipung", "Rebana", "Terbang")
3. Atur jumlah total, baik, dan rusak menggunakan tombol +/- atau input langsung
4. Klik "Pilih Anggota" untuk menugaskan pengguna
5. (Opsional) Tambahkan keterangan
6. Klik "Simpan"
7. Konfirmasi pada modal popup

### 2. Melihat Daftar Alat
1. Halaman index menampilkan semua alat
2. Gunakan search untuk filter berdasarkan nama
3. Lihat pengguna aktif pada kolom "Pengguna (Aktif)"
4. Badge warna menunjukkan jumlah unit (hijau=baik, merah=rusak)

### 3. Mengedit Alat
1. Klik tombol Edit pada alat yang ingin diubah
2. Ubah data yang diperlukan (nama, jumlah)
3. Klik "Pilih Anggota" untuk kelola pengguna:
   - **Tambah**: Centang anggota baru
   - **Hapus**: Hapus centang anggota
4. Anggota yang dihapus akan ditandai status "Dikembalikan"
5. Riwayat pengguna lengkap ditampilkan di bawah form

### 4. Menghapus Alat
1. Klik tombol Hapus pada alat yang ingin dihapus
2. Konfirmasi penghapusan pada halaman
3. **Perhatian**: Menghapus alat juga menghapus semua data assign pengguna di `alat_pengguna`

### 5. Pencarian
- Masukkan kata kunci di search box
- Tekan Enter atau klik tombol "Cari"
- Reset untuk menampilkan semua data

## 📈 Dashboard Statistics

Modul alat menampilkan statistik dengan visual cards:

```html
<div class="row mt-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-music"></i></div>
            <div class="stat-value"><?= $total_alat ?></div>
            <div class="stat-label">Jenis Alat</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $total_baik ?></div>
            <div class="stat-label">Kondisi Baik</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value"><?= $total_rusak ?></div>
            <div class="stat-label">Kondisi Rusak</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1, #6610f2);">
            <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            <div class="stat-value"><?= $persen_baik ?>%</div>
            <div class="stat-label">Kondisi Baik</div>
        </div>
    </div>
</div>
```

| Statistik | Warna | Icon |
|-----------|-------|------|
| Jenis Alat | bg-primary | fa-music |
| Kondisi Baik | bg-success | fa-check-circle |
| Kondisi Rusak | bg-danger | fa-times-circle |
| Persentase Baik | gradient purple | fa-chart-pie |

## 👥 Hak Akses

| Peran | Tambah | Lihat | Edit | Hapus |
|-------|--------|-------|------|-------|
| Admin | ✓ | ✓ | ✓ | ✓ |
| Pembina | ✗ | ✓ | ✗ | ✗ |
| Anggota | ✗ | ✓ | ✗ | ✗ |

## 🔒 Keamanan

- **Session Validation**: `auth_check.php` di setiap halaman
- **Admin Check**: Role verification untuk tambah, edit, dan hapus
- **SQL Injection Protection**: Prepared statements di semua query
- **XSS Protection**: `htmlspecialchars()` pada semua output
- **Input Sanitization**: strip_tags, validasi format
- **Rate Limiting**: Via `includes/rate_limit.php`
- **Audit Trail**: user_record dan user_modified untuk tracking

### Validasi Input

```php
// Search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Numeric validation
$total_alat = (int)($_POST['total_alat'] ?? 1);
if ($total_alat < 1) {
    $errors[] = "Total alat minimal 1";
}
```

## 📱 Responsive Design

| Tampilan | Komponen |
|----------|----------|
| **Desktop** | Tabel dengan kolom No, Nama, Baik, Rusak, Pengguna, Aksi |
| **Mobile** | Card view dengan layout vertikal |
| **FAB** | Floating Action Button untuk tambah data |
| **Modal** | Bootstrap 5 modal yang responsive |
| **Toast** | Fixed position dengan z-index tinggi |

### Tampilan Tabel (Desktop)

- Kolom fixed width untuk alignment yang konsisten
- Badge untuk jumlah (hijau=baik, merah=rusak)
- Text truncation untuk kolom panjang
- Tooltip pada overflow
- Action buttons dengan icon dan tooltip

### Tampilan Card (Mobile)

- Layout vertikal dengan icon
- Full-width cards
- Badge warna untuk kondisi
- Tombol Edit dan Hapus full-width
- Touch-friendly buttons

## 🎨 User Interface Features

### Floating Action Button (FAB)

```html
<a href="tambah.php" class="floating-btn" title="Tambah Alat">
    <i class="fas fa-plus"></i>
</a>
```

### Preview Alat

Form dilengkapi preview real-time sebelum submit:

```html
<div class="alert alert-info py-2 mb-3">
    <small>
        Total: <strong id="summaryTotal">1</strong> | 
        Baik: <strong id="summaryBaik" class="text-success">1</strong> | 
        Rusak: <strong id="summaryRusak" class="text-danger">0</strong> | 
        Sisa: <strong id="summarySisa" class="text-warning">0</strong>
    </small>
</div>
```

### Preview Pengguna Terpilih

```html
<div id="selectedPreview" class="d-flex flex-wrap gap-2 mb-3" style="display: none;">
    <div class="selected-member-preview">
        <div class="member-avatar-small bg-primary text-white">B</div>
        <span class="member-name">Budi</span>
    </div>
    <div class="selected-member-preview">
        <div class="member-avatar-small bg-primary text-white">A</div>
        <span class="member-name">Andi</span>
    </div>
    <div class="selected-member-count">
        <span class="badge bg-secondary">+2</span>
    </div>
</div>
```

## 📦 Integrasi

Modul alat terintegrasi dengan:

| Modul | Jenis Integrasi |
|-------|-----------------|
| **Sidebar** | Menu navigasi utama |
| **User Management** | Daftar anggota aktif untuk assign |
| **Auth** | Session validation per halaman |
| **Database** | Koneksi via config/database.php |

### Query untuk Daftar Pengguna Aktif

```php
$stmt_user = $pdo->query("
    SELECT id_user, nama_lengkap, no_hp 
    FROM user 
    WHERE status_aktif = 1 AND peran = 'anggota' 
    ORDER BY nama_lengkap ASC
");
$user_list = $stmt_user->fetchAll();
```

## 🚀 Pengembangan Selanjutnya

- [x] CRUD Alat Dasar
- [x] Multi-User Assignment
- [x] Riwayat Penggunaan
- [x] Real-Time Validation (Nama Alat)
- [x] Responsive Design (Table + Card)
- [x] Toast Notifications
- [x] Modal Pemilihan Pengguna
- [ ] Upload foto alat
- [ ] QR Code/Barcode untuk identifikasi alat
- [ ] Log peminjaman alat (keluar-masuk)
- [ ] Notifikasi alat perlu perbaikan
- [ ] Kategori alat (Ketipung, Rebana, Terbang, dll)
- [ ] Export data inventaris ke PDF/Excel
- [ ] Grafik kondisi alat
- [ ] Maintenance schedule

## 🐛 Riwayat Perbaikan

### v1.1.0 - Perbaikan Assign Pengguna
- **Bug**: Checkbox pengguna di modal tidak tersimpan saat form disubmit
- **Penyebab**: Checkbox berada di luar form, modal Bootstrap tidak submit otomatis
- **Solusi**: 
  - Menambahkan hidden input di dalam form untuk menyimpan pilihan pengguna
  - Memodifikasi JavaScript untuk menyimpan ID terpilih ke hidden input
  - Mengubah nama checkbox untuk menghindari konflik dengan hidden input
- **File Diedit**: `tambah.php`, `edit.php`

### v1.0.0
- Rilis awal modul alat
- CRUD dasar alat
- Assign pengguna
- Status tracking
- Search dan filter
- Statistik dashboard

---

*Modul Inventaris Alat v1.1.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

