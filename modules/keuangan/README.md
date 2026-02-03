# Modul Keuangan

Dokumentasi lengkap untuk modul pengelolaan keuangan/kas pada aplikasi Hadrah.

## 📋 Fitur

- **CRUD Transaksi**: Tambah, lihat, edit, dan hapus transaksi kas
- **Real-Time Validation**: Validasi input secara real-time dengan AJAX ke server
- **Kategori Transaksi**: Kategori untuk pemasukan dan pengeluaran
- **Filter & Pencarian**: Filter berdasarkan tipe, bulan, dan pencarian keterangan
- **Laporan Saldo**: Otomatis menghitung total pemasukan, pengeluaran, dan saldo kas
- **Audit Trail**: Mencatat siapa yang mencatat dan mengubah transaksi

## 📁 Struktur File

```
modules/keuangan/
├── index.php              # Halaman utama - daftar transaksi
├── tambah.php             # Form tambah transaksi baru dengan real-time validation
├── edit.php              # Form edit transaksi dengan real-time validation
├── hapus.php             # Konfirmasi hapus transaksi
├── api_check_keuangan.php # API endpoint untuk validasi real-time
└── README.md             # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `keuangan`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_kas` | INT | Primary Key (Auto Increment) |
| `id_user` | INT | Foreign Key ke `user` |
| `tipe` | ENUM | `pemasukan` atau `pengeluaran` |
| `jumlah` | DECIMAL(12,2) | Jumlah uang |
| `kategori` | VARCHAR(50) | Jenis transaksi |
| `keterangan` | TEXT | Detail transaksi |
| `tanggal` | DATE | Tanggal transaksi |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang mengubah |
| `user_record` | INT | User yang mencatat |

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
| **Duplikasi** | Check transaksi serupa | `check_duplicate` |

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
- Honor Acara
- Donasi
- Lainnya

### Pengeluaran
- Konsumsi
- Servis Alat
- Seragam
- Alat Musik
- Transportasi
- Lainnya

## 🔧 Cara Penggunaan

### 1. Menambah Transaksi
1. Klik tombol (+) di pojok kanan bawah
2. Pilih tipe transaksi (Pemasukan/Pengeluaran)
3. Pilih kategori
4. Masukkan jumlah (otomatis format Rupiah)
5. Pilih tanggal transaksi
6. Tambahkan keterangan (opsional)
7. Validasi real-time akan berjalan saat Anda keluar dari setiap field
8. Klik "Simpan Transaksi"

### 2. Mengedit Transaksi
1. Klik tombol Edit pada transaksi yang ingin diubah
2. Ubah data yang diperlukan
3. Validasi akan berjalan otomatis
4. Klik "Simpan Perubahan"

### 3. Menghapus Transaksi
1. Klik tombol Hapus pada transaksi yang ingin dihapus
2. Konfirmasi penghapusan
3. Transaksi akan dihapus permanen

### 4. Filter & Pencarian
- **Pencarian**: Cari berdasarkan keterangan atau kategori
- **Filter Tipe**: Tampilkan hanya pemasukan atau pengeluaran
- **Filter Bulan**: Tampilkan transaksi berdasarkan bulan

## 📈 Dashboard Statistics

Modul keuangan menampilkan statistik:
- **Total Pemasukan**: Akumulasi semua pemasukan
- **Total Pengeluaran**: Akumulasi semua pengeluaran
- **Saldo Kas**: Total pemasukan - Total pengeluaran
- **Jumlah Transaksi**: Total record transaksi

## 👥 Hak Akses

| Peran | Akses |
|-------|-------|
| Admin | Full access (CRUD) |
| Pembina | View only |
| Anggota | View only |

## 🔒 Keamanan

- Validasi input di server-side
- Prepared statements untuk mencegah SQL Injection
- XSS protection dengan `htmlspecialchars()`
- Hak akses admin untuk edit dan hapus
- Audit trail (user_record, user_modified)
- Validasi real-time via API dengan validasi fallback

## 📱 Responsive Design

- Tampilan tabel untuk desktop
- Tampilan card untuk mobile
- Floating Action Button (FAB) untuk menambah data
- Sidebar yang dapat disembunyikan di mobile

## 📦 Integrasi

Modul keuangan terintegrasi dengan:
- **Sidebar**: Akses dari menu utama
- **Dashboard Admin**: Menampilkan ringkasan keuangan
- **API Stats**: `api/stats.php` - `getSaldoKas()`

## 🚀 Pengembangan Selanjutnya

- [x] Export laporan ke PDF/Excel
- [ ] Upload bukti transaksi (foto nota)
- [ ] Grafik visualisasi keuangan
- [ ] Laporan per periode (bulanan/tahunan)
- [ ] Notifikasi saldo minimum
- [ ] Multi-kas ( kas utama, kas operasional, dll)

---

*Modul Keuangan v1.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

