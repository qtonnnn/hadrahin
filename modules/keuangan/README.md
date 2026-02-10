# Modul Keuangan

Dokumentasi lengkap untuk modul pengelolaan keuangan/kas pada aplikasi Hadrah.

## 📋 Fitur

- **CRUD Transaksi**: Tambah, lihat, edit, dan hapus transaksi kas
- **Real-Time Validation**: Validasi input secara real-time dengan AJAX ke server
- **Detail Modal**: Popup detail transaksi lengkap dengan AJAX
- **Toast Notifications**: Feedback visual untuk setiap aksi pengguna
- **Honor Acara Integration**: Integrasi dengan modul acara untuk kategori honor
- **Floating Action Button (FAB)**: Pintasan tambah transaksi di pojok layar
- **Filter & Pencarian**: Filter berdasarkan tipe, bulan, dan pencarian keterangan
- **Laporan Saldo**: Otomatis menghitung total pemasukan, pengeluaran, dan saldo kas
- **Audit Trail**: Mencatat siapa yang mencatat dan mengubah transaksi dengan timestamp lengkap
- **Responsive Design**: Tampilan tabel untuk desktop, card untuk mobile

## 📁 Struktur File

```
modules/keuangan/
├── index.php              # Halaman utama - daftar transaksi dengan statistik
├── tambah.php             # Form tambah transaksi dengan real-time validation & modal konfirmasi
├── edit.php              # Form edit transaksi (admin only) dengan real-time validation
├── hapus.php             # Konfirmasi hapus transaksi (admin only)
├── view_detail_ajax.php   # Endpoint AJAX untuk modal detail transaksi
├── api_check_keuangan.php # API endpoint untuk validasi real-time
└── README.md             # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `keuangan`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_kas` | INT | Primary Key (Auto Increment) |
| `id_user` | INT | Foreign Key ke `user` |
| `id_acara` | INT | Foreign Key ke `booking_acara` (nullable, untuk Honor Acara) |
| `tipe` | ENUM | `pemasukan` atau `pengeluaran` |
| `jumlah` | DECIMAL(12,2) | Jumlah uang |
| `kategori` | VARCHAR(50) | Jenis transaksi |
| `keterangan` | TEXT | Detail transaksi |
| `tanggal` | DATE | Tanggal transaksi |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |
| `user_record` | INT | User yang mencatat transaksi |

### Tabel Pendukung: `booking_acara`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_booking` | INT | Primary Key |
| `nama_acara` | VARCHAR(100) | Nama acara |
| `tanggal_acara` | DATE | Tanggal pelaksanaan |
| `lokasi` | VARCHAR(255) | Lokasi acara |
| `status` | ENUM | Status acara |

## 🔄 Real-Time Validation

### Fitur Validasi Real-Time

Modul keuangan mengimplementasikan validasi real-time menggunakan AJAX:

```javascript
// Validasi saat user keluar dari field (onblur)
fetch('api_check_keuangan.php?action=validate_jumlah&jumlah=100000')

// Response
{
    "success": true,
    "valid": true,
    "message": "Jumlah valid!"
}
```

### Jenis Validasi

| Field | Validasi | Endpoint |
|-------|----------|----------|
| **Jumlah** | > 0, maksimal Rp 999.999.999.999 | `validate_jumlah` |
| **Kategori** | Wajib dipilih, sesuai tipe | `validate_kategori` |
| **Tanggal** | Format YYYY-MM-DD, tidak masa depan, max 5 tahun | `validate_tanggal` |
| **Duplikasi** | Check transaksi serupa (mode=add/edit) | `check_duplicate` |

### Endpoint API

```php
// Validasi jumlah
GET api_check_keuangan.php?action=validate_jumlah&jumlah=100000

// Validasi kategori
GET api_check_keuangan.php?action=validate_kategori&kategori=Iuran%20Anggota&tipe=pemasukan

// Validasi tanggal
GET api_check_keuangan.php?action=validate_tanggal&tanggal=2026-01-21

// Check duplikasi (mode=add/edit)
GET api_check_keuangan.php?action=check_duplicate&mode=add&tipe=pemasukan&jumlah=100000&tanggal=2026-01-21&kategori=Iuran%20Anggota

// Validasi lengkap (POST)
POST api_check_keuangan.php
action=validate_all&tipe=pemasukan&kategori=Iuran%20Anggota&jumlah=100000&tanggal=2026-01-21
```

### Feedback Visual

```
┌─────────────────────────────────────────────────────────────┐
│  Jumlah (Rp) *                                              │
│  ┌─────────┐  ✓                                              │
│  │ Rp 100.000 │  Jumlah valid!                               │
│  └─────────┘                                                 │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Jumlah (Rp) *                                              │
│  ┌─────────┐  ✗                                              │
│  │ 0       │  Jumlah harus lebih dari 0!                    │
│  └─────────┘                                                 │
└─────────────────────────────────────────────────────────────┘
```

## 📝 Kategori Transaksi

### Pemasukan
- Iuran Anggota
- Honor Acara (terintegrasi dengan modul acara)
- Donasi
- Lainnya

### Pengeluaran
- Konsumsi
- Servis Alat
- Seragam
- Alat Musik
- Transportasi
- Lainnya

## 🎫 Detail Transaksi Modal

### Fitur Detail Modal

Transaksi dapat dilihat secara detail melalui modal popup yang dimuat via AJAX:

```javascript
// Memuat detail transaksi
function viewDetail(id) {
    fetch(`view_detail_ajax.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
}
```

### Informasi yang Ditampilkan

| Field | Keterangan |
|-------|------------|
| ID Transaksi | Format: #000001 |
| Tipe | Badge pemasukan/pengeluaran |
| Kategori | Jenis transaksi |
| Jumlah | Format Rupiah dengan warna (hijau/merah) |
| Tanggal | Format DD/MM/YYYY |
| Keterangan | Detail transaksi (jika ada) |
| Dicatat Oleh | Nama user yang mencatat |
| Dicatat Pada | Timestamp pembuatan |
| Diubah Oleh | Nama user terakhir yang mengubah (jika ada) |
| Diubah Pada | Timestamp perubahan (jika ada) |

### Integrasi Acara

Untuk kategori **Honor Acara**, modal menampilkan informasi tambahan:

| Field | Keterangan |
|-------|------------|
| Nama Acara | Nama acara dari booking_acara |
| Tanggal Acara | Tanggal pelaksanaan |
| Lokasi | Lokasi acara |

## 🔔 Toast Notifications

Sistem notifikasi popup untuk feedback pengguna:

| Pesan | Tipe | Warna |
|-------|------|-------|
| Transaksi berhasil ditambahkan! | Sukses | bg-success |
| Transaksi berhasil diperbarui! | Sukses | bg-success |
| Transaksi berhasil dihapus! | Sukses | bg-success |
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

### 1. Menambah Transaksi
1. Klik tombol FAB (+) di pojok kanan bawah
2. Pilih tipe transaksi (Pemasukan/Pengeluaran)
3. Pilih kategori
4. **Untuk Honor Acara**: Pilih acara dari dropdown (hanya menampilkan acara ber-status "Selesai")
5. Masukkan jumlah (otomatis format Rupiah)
6. Pilih tanggal transaksi
7. Tambahkan keterangan (opsional)
8. Validasi real-time akan berjalan saat Anda keluar dari setiap field
9. Klik "Simpan Transaksi"
10. Konfirmasi pada modal popup

### 2. Melihat Detail Transaksi
1. Klik tombol "Eye" (👁️) pada tabel/card transaksi
2. Modal popup akan menampilkan informasi lengkap
3. Termasuk audit trail (siapa mencatat/mengubah)

### 3. Mengedit Transaksi
1. Klik tombol Edit pada transaksi yang ingin diubah
2. Ubah data yang diperlukan
3. Validasi akan berjalan otomatis
4. Klik "Simpan Perubahan"
5. Konfirmasi pada modal popup

### 4. Menghapus Transaksi
1. Klik tombol Hapus pada transaksi yang ingin dihapus
2. Konfirmasi penghapusan pada halaman
3. Transaksi akan dihapus permanen

### 5. Filter & Pencarian
- **Pencarian**: Cari berdasarkan keterangan atau kategori
- **Filter Tipe**: Tampilkan hanya pemasukan atau pengeluaran
- **Filter Bulan**: Tampilkan transaksi berdasarkan bulan (format YYYY-MM)

### 6. Auto-Clear Filter
Filter akan otomatis dihapus dari URL setelah 5 detik untuk pengalaman pengguna yang lebih bersih.

## 📈 Dashboard Statistics

Modul keuangan menampilkan statistik dengan visual cards:

```html
<div class="row mt-4">
    <div class="col-md-4 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-value">Rp 1.000.000</div>
            <div class="stat-label">Total Pemasukan</div>
        </div>
    </div>
    <div class="col-md-4 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-value">Rp 500.000</div>
            <div class="stat-label">Total Pengeluaran</div>
        </div>
    </div>
    <div class="col-md-4 col-12 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-value">Rp 500.000</div>
            <div class="stat-label">Saldo Kas</div>
        </div>
    </div>
</div>
```

| Statistik | Warna | Icon |
|-----------|-------|------|
| Total Pemasukan | bg-success | fa-arrow-down |
| Total Pengeluaran | bg-danger | fa-arrow-up |
| Saldo Kas Positif | bg-primary | fa-wallet |
| Saldo Kas Negatif | bg-warning | fa-exclamation-triangle |

## 👥 Hak Akses

| Peran | Tambah | Lihat | Edit | Hapus |
|-------|--------|-------|------|-------|
| Admin | ✓ | ✓ | ✓ | ✓ |
| Pembina | ✗ | ✓ | ✗ | ✗ |
| Anggota | ✗ | ✓ | ✗ | ✗ |

## 🔒 Keamanan

- **Session Validation**: `auth_check.php` di setiap halaman
- **Admin Check**: Role verification untuk edit dan hapus
- **SQL Injection Protection**: Prepared statements di semua query
- **XSS Protection**: `htmlspecialchars()` pada semua output
- **Input Sanitization**: strip_tags, validasi format regex
- **Rate Limiting**: Via `includes/rate_limit.php`
- **Audit Trail**: user_record dan user_modified untuk tracking

### Validasi Input

```php
// Search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Filter validation
if (!in_array($tipe_filter, ['', 'pemasukan', 'pengeluaran'])) {
    $tipe_filter = '';
}

// Date format validation
if (!preg_match('/^\d{4}-\d{2}$/', $bulan_filter)) {
    $bulan_filter = '';
}
```

## 📱 Responsive Design

| Tampilan | Komponen |
|----------|----------|
| **Desktop** | Tabel dengan scroll horizontal, toolbar horizontal |
| **Mobile** | Card view, toolbar dengan stacking |
| **FAB** | Floating Action Button untuk tambah data |
| **Modal** | Bootstrap 5 modal yang responsive |
| **Toast** | Fixed position dengan z-index tinggi |

### Tampilan Tabel (Desktop)

- Kolom fixed width untuk alignment yang konsisten
- Badge untuk tipe transaksi
- Text truncation untuk kolom panjang
- Tooltip pada overflow
- Action buttons dengan tooltip

### Tampilan Card (Mobile)

- Layout vertikal dengan icon
- Full-width cards
- Touch-friendly buttons
- Indikator warna untuk tipe

## 🎨 User Interface Features

### Floating Action Button (FAB)

```html
<a href="tambah.php" class="floating-btn" title="Tambah Transaksi">
    <i class="fas fa-plus"></i>
</a>
```

### Preview Transaksi

Form dilengkapi preview real-time sebelum submit:

```html
<div class="card bg-light mb-4">
    <div class="card-body">
        <h6 class="mb-3"><i class="fas fa-eye me-1"></i> Preview Transaksi</h6>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td width="120">Tipe</td><td><span id="previewTipe" class="badge bg-secondary">-</span></td></tr>
                    <tr><td>Kategori</td><td><strong id="previewKategori">-</strong></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td width="120">Jumlah</td><td><strong id="previewJumlah" class="text-primary">Rp 0</strong></td></tr>
                    <tr><td>Tanggal</td><td><strong id="previewTanggal">-</strong></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
```

## 📦 Integrasi

Modul keuangan terintegrasi dengan:

| Modul | Jenis Integrasi |
|-------|-----------------|
| **Sidebar** | Menu navigasi utama |
| **Dashboard Admin** | Menampilkan ringkasan keuangan via `api/stats.php` |
| **Booking Acara** | Referensi untuk kategori Honor Acara |
| **User Management** | Tracking user yang mencatat/mengubah |

### API Stats Integration

```php
// Dari api/stats.php
function getSaldoKas() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as total_pemasukan,
            COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran
        FROM keuangan
    ");
    return $stmt->fetch();
}
```

## 🚀 Pengembangan Selanjutnya

- [x] CRUD Transaksi Dasar
- [x] Real-Time Validation
- [x] Detail Modal dengan AJAX
- [x] Toast Notifications
- [x] Honor Acara Integration
- [x] Responsive Design (Table + Card)
- [x] Audit Trail
- [ ] Export laporan ke PDF/Excel
- [ ] Upload bukti transaksi (foto nota)
- [ ] Grafik visualisasi keuangan
- [ ] Laporan per periode (bulanan/tahunan)
- [ ] Notifikasi saldo minimum
- [ ] Multi-kas (kas utama, kas operasional, dll)
- [ ] Kategori dinamis (user dapat menambah kategori baru)

## 📄 Changelog

### v1.1.0
- Menambahkan view_detail_ajax.php untuk modal detail transaksi
- Menambahkan integrasi Honor Acara dengan booking_acara
- Menambahkan toast notifications untuk feedback
- Menambahkan FAB (Floating Action Button)
- Menambahkan client-side fallback validation
- Menambahkan preview transaksi sebelum submit
- Menambahkan modal konfirmasi sebelum save
- Perbaikan responsive design untuk mobile card view

### v1.0.0
- Rilis awal modul keuangan
- CRUD dasar transaksi
- Real-time validation
- Filter dan pencarian
- Statistik dashboard

---

*Modul Keuangan v1.1.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

