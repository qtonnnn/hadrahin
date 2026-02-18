# Modul Booking Acara - Dokumentasi

Dokumentasi lengkap untuk modul booking acara dalam sistem Hadrahin.

## 1. Gambaran Umum

Modul **Booking Acara** berfungsi untuk mengelola pemesanan/manggung acara grup hadrah. Modul ini memungkinkan admin untuk mencatat, mengelola, dan mendokumentasikan setiap acara yang dihadiri oleh grup.

### Fitur Utama

- **CRUD Booking**: Tambah, lihat, edit, dan hapus booking acara
- **Status Management**: Kelola status booking (Menunggu, Diterima, Ditolak, Selesai)
- **Dokumentasi**: Upload dan kelola foto/video dokumentasi acara
- **Real-Time Validation**: Validasi input saat mengisi form
- **Duplicate Detection**: Mencegah booking duplikat pada waktu yang sama
- **Toast Notifications**: Feedback visual untuk setiap aksi
- **Responsive Design**: Tampilan optimal untuk desktop dan mobile
- **Audit Trail**: Pencatatan siapa yang membuat/mengubah booking

## 2. Struktur File

```
modules/acara/
├── index.php              # Halaman utama - daftar booking dengan pagination & filter
├── tambah.php             # Form tambah booking baru dengan validasi real-time
├── edit.php               # Form edit booking dengan sidebar info
├── hapus.php              # Konfirmasi hapus booking (admin only)
├── update_status.php      # API untuk update status via AJAX
├── view_ajax.php          # Endpoint AJAX untuk modal detail booking
├── dokumentasi.php        # Upload & kelola dokumentasi foto/video
└── README.md              # Dokumentasi lengkap
```

## 3. Alur Sistem

### 3.1 Pembuatan Booking Baru

1. Admin/Pembina klik tombol FAB (+) di pojok kanan bawah
2. Sistem redirect ke halaman `tambah.php`
3. User mengisi form dengan data acara
4. Validasi real-time berjalan saat input
5. Klik "Simpan Booking" → Modal konfirmasi muncul
6. Klik "Konfirmasi" → Data disimpan ke database (status: "menunggu")
7. Redirect ke index dengan toast notification sukses

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

### 3.3 Status Description

| Status | Keterangan | Aksi yang Tersedia |
|--------|------------|-------------------|
| **Menunggu** | Booking baru, menunggu konfirmasi | Edit, Terima, Tolak |
| **Diterima** | Booking dikonfirmasi | Edit, Selesai*, Tolak |
| **Ditolak** | Booking ditolak | Edit, Kembali ke Menunggu |
| **Selesai** | Acara telah berlangsung | Lihat Detail, Dokumentasi |

*Status "Selesai" hanya dapat dipilih jika tanggal acara <= hari ini

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

### 4.3 Indeks untuk Performa

```sql
-- Index untuk filter cepat
KEY `idx_status_tanggal` (`status`,`tanggal_acara`)

-- Query yang dioptimalkan
SELECT * FROM booking_acara 
WHERE status = ? AND tanggal_acara BETWEEN ? AND ?
ORDER BY created_at DESC
LIMIT 10 OFFSET 0
```

## 5. Halaman dan Fitur

### 5.1 Halaman Index (index.php)

**Fitur Utama:**

| Fitur | Keterangan |
|-------|------------|
| **FAB** | Floating Action Button (+) untuk tambah booking |
| **Stat Cards** | 4 card menampilkan statistik booking per status |
| **Filter Buttons** | Dropdown filter berdasarkan status |
| **Date Range** | Filter berdasarkan periode tanggal |
| **Search** | Pencarian by nama acara, pemesan, lokasi |
| **Pagination** | Navigasi halaman (default 10 item/halaman) |
| **Table View** | Tampilan tabel untuk desktop |
| **Card View** | Tampilan card untuk mobile |

**Action Buttons:**

```javascript
// AJAX Status Update
function updateStatus(id, status) {
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id_booking=${id}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

// View Detail Modal
function viewBooking(id) {
    fetch(`view_ajax.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('viewContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        });
}
```

**Status Configuration:**

```javascript
const statusConfig = {
    'diterima': {
        title: 'Terima Booking',
        btnClass: 'btn-success',
        icon: 'fa-check-circle'
    },
    'ditolak': {
        title: 'Tolak Booking',
        btnClass: 'btn-danger',
        icon: 'fa-times-circle'
    },
    'selesai': {
        title: 'Selesai Booking',
        btnClass: 'btn-primary',
        icon: 'fa-check-double'
    }
};
```

### 5.2 Halaman Tambah (tambah.php)

**Form Fields:**

| Field | Tipe | Validasi | Required |
|-------|------|----------|----------|
| Nama Acara | text | 3-150 karakter | ✓ |
| Nama Pemesan | text | minimal 2 karakter | ✓ |
| No HP Pemesan | tel | format HP Indonesia | ✗ |
| Tanggal Acara | date | >= hari ini | ✓ |
| Jam Mulai | time | format 24 jam | ✓ |
| Lokasi | text | 5-150 karakter | ✓ |
| Dresscode | select | dari dresscode aktif | ✓ |
| Penanggung Jawab | select | admin/pembina aktif | ✓ |
| Keterangan | textarea | opsional | ✗ |

**Validasi Real-Time:**

```javascript
// Contoh validasi saat input
inputs.nama_acara.addEventListener('input', function() {
    if (this.value.length < 3) {
        showError(this, 'Nama acara minimal 3 karakter!');
    } else {
        clearError(this);
    }
});

// Check duplicate booking
function checkDuplicateAcara() {
    fetch('../api/check_acara.php', {
        method: 'POST',
        body: `tanggal=${tanggal}&jam_mulai=${jam}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            showError(this, 'Booking pada waktu tersebut sudah ada!');
        }
    });
}
```

**Confirmation Modal:**

```html
<div class="modal fade" id="confirmModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <!-- Preview data booking -->
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Nama Acara:</div>
                        <div class="col-8" id="confirmNamaAcara">-</div>
                    </div>
                    <!-- ... more fields -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="submitForm()">
                    <i class="fas fa-check me-1"></i>Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>
```

### 5.3 Halaman Edit (edit.php)

**Fitur Tambahan:**

- Dropdown Status untuk ubah status booking
- Info card dengan timestamp (created_at, updated_at)
- Warning card tentang batasan edit
- Duplicate check dengan exclude current ID

**Status Transition Validation:**

```php
// Validasi transisi status
if ($current_status == 'selesai') {
    echo json_encode(['success' => false, 
        'message' => 'Tidak dapat mengubah status booking yang sudah selesai']);
    exit;
}

if ($current_status == 'ditolak' && $status != 'menunggu') {
    echo json_encode(['success' => false, 
        'message' => 'Booking yang ditolak hanya dapat diubah ke menunggu']);
    exit;
}

// Validasi tanggal untuk status 'selesai'
if ($status == 'selesai') {
    $stmt = $pdo->prepare("SELECT tanggal_acara FROM booking_acara WHERE id_booking = ?");
    $stmt->execute([$id_booking]);
    $event_date = $stmt->fetchColumn();
    
    if (strtotime($event_date) > strtotime('today')) {
        echo json_encode(['success' => false, 
            'message' => 'Tidak dapat menandai selesai - Acara belum berlangsung']);
        exit;
    }
}
```

### 5.4 Halaman Hapus (hapus.php)

**Fitur:**

- Warning alert tentang penghapusan permanen
- Informasi lengkap booking yang akan dihapus
- Alert tentang data terkait (dokumentasi)
- Konfirmasi dengan tombol "Hapus Permanen"
- Only Admin dapat menghapus

### 5.5 Halaman Dokumentasi (dokumentasi.php)

**Fitur Upload:**

| Aspek | Spesifikasi |
|-------|-------------|
| Tipe File | JPG, PNG, GIF, MP4 |
| Ukuran Maksimal | 50MB |
| Folder Upload | `assets/uploads/dokumentasi/` |
| Naming Convention | `acara_{id}_{timestamp}.{ext}` |

**Upload Form:**

```php
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
$max_size = 50 * 1024 * 1024; // 50MB

if (!in_array($file['type'], $allowed_types)) {
    $upload_error = 'Tipe file tidak diizinkan.';
} elseif ($file['size'] > $max_size) {
    $upload_error = 'File terlalu besar. Maksimal 50MB.';
}
```

**Dokumentasi Grid:**

```html
<div class="row g-3">
    <?php foreach ($dokumentasi as $doc): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <?php if ($is_video): ?>
                    <video controls class="card-img-top">
                        <source src="<?= $doc['file_path'] ?>" type="video/<?= $ext ?>">
                    </video>
                <?php else: ?>
                    <a href="<?= $doc['file_path'] ?>" target="_blank">
                        <img src="<?= $doc['file_path'] ?>" class="card-img-top">
                    </a>
                <?php endif; ?>
                <div class="card-body">
                    <p class="card-text"><?= htmlspecialchars($doc['keterangan'] ?? '-') ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

### 5.6 API Update Status (update_status.php)

**Endpoint:**

```http
POST modules/acara/update_status.php

Parameters:
- id_booking: integer (required)
- status: string (required, values: 'menunggu'|'diterima'|'ditolak'|'selesai')

Response:
{
    "success": true,
    "message": "Status berhasil diubah",
    "new_status": "diterima"
}
```

### 5.7 AJAX View Detail (view_ajax.php)

**Endpoint:**

```http
GET modules/acara/view_ajax.php?id={id_booking}

Response: HTML table dengan detail booking
```

**Data yang Ditampilkan:**

| Field | Keterangan |
|-------|------------|
| Nama Acara | Judul booking |
| Status | Badge dengan warna status |
| Nama Pemesan | Orang yang memesan |
| No HP | Kontak pemesan |
| Tanggal | Format DD F Y |
| Jam | Format HH:ii WIB |
| Lokasi | Alamat lengkap |
| Dresscode | Nama pakaian |
| Penanggung Jawab | User yang bertanggung jawab |
| Keterangan | Catatan tambahan |
| Timestamp | Dibuat/Diubah |

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
| Hapus booking (dengan cascade) | ✓ | ✗ | ✗ |

## 7. Penggunaan

### 7.1 Menambah Booking Baru

1. Login sebagai Admin
2. Navigasi ke menu "Booking Acara"
3. Klik tombol FAB (+) di pojok kanan bawah
4. Isi form dengan data acara
5. Sistem akan memvalidasi secara real-time
6. Klik "Simpan Booking"
7. Review data di modal konfirmasi
8. Klik "Konfirmasi"
9. Booking akan berstatus "Menunggu"

### 7.2 Mengubah Status Booking

**Via Button di Tabel:**

1. Di tabel booking, klik tombol:
   - ✓ (Hijau) untuk Terima
   - ✗ (Merah) untuk Tolak
   - ✓✓ (Biru) untuk Selesai (hanya jika acara sudah berlangsung)
2. Modal konfirmasi akan muncul
3. Klik "Ya, Konfirmasi"

**Aturan Transisi Status:**

```
Menunggu → Diterima (oke acara dikonfirmasi)
Menunggu → Ditolak (tidak jadi)
Diterima → Selesai (acara sudah berlangsung)
Diterima → Ditolak (membatalkan)
Ditolak → Menunggu (pengajuan ulang)
```

### 7.3 Upload Dokumentasi

1. Klik tombol "Lihat Detail" pada booking
2. Klik tombol "Lihat Dokumentasi"
3. Pilih file (gambar/video)
4. Tambahkan keterangan (opsional)
5. Klik "Upload"
6. Dokumentasi akan muncul di galeri

### 7.4 Pencarian dan Filter

**Filter yang Tersedia:**

- **Status**: Menunggu, Diterima, Ditolak, Selesai
- **Periode Tanggal**: Tanggal mulai s/d tanggal akhir
- **Pencarian**: Nama acara, nama pemesan, lokasi

**Contoh URL dengan Filter:**

```
?status=diterima&start_date=2024-01-01&end_date=2024-01-31&search=melahirkan
```

## 8. Keamanan

### 8.1 Validasi Input

```php
// Sanitasi search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// Validasi format tanggal
if (!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = '';
}

// Validasi ID
$id_booking = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_booking <= 0) {
    header('Location: index.php?msg=error');
    exit;
}
```

### 8.2 SQL Injection Protection

```php
// Prepared statements untuk semua query
$stmt = $pdo->prepare("SELECT * FROM booking_acara WHERE id_booking = ?");
$stmt->execute([$id_booking]);

// Dynamic query dengan whitelist validation
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['menunggu', 'diterima', 'ditolak', 'selesai', 'all'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}
```

### 8.3 XSS Protection

```php
// htmlspecialchars untuk semua output
<?= htmlspecialchars($booking['nama_acara']) ?>

// nl2br untuk textarea
<?= nl2br(htmlspecialchars($booking['keterangan'] ?? '-')) ?>
```

### 8.4 Access Control

```php
// Check permission di setiap halaman
if ($_SESSION['peran'] != 'admin' && $_SESSION['peran'] != 'pembina') {
    header('Location: index.php?error=permission_denied');
    exit;
}

// Admin only untuk operasi sensitif
if ($_SESSION['peran'] !== 'admin') {
    header('Location: index.php?msg=access_denied');
    exit;
}
```

## 9. UI/UX Features

### 9.1 Toast Notifications

```php
// Redirect dengan parameter success
header('Location: index.php?success=booking_created');

// atau
header('Location: index.php?success=booking_updated');
header('Location: index.php?success=booking_deleted');
```

**Jenis Notifikasi:**

| Parameter | Tipe | Warna |
|-----------|------|-------|
| `booking_created` | Sukses | bg-success |
| `booking_updated` | Sukses | bg-success |
| `booking_deleted` | Sukses | bg-success |
| `error` | Error | bg-danger |

### 9.2 Floating Action Button

```html
<a href="tambah.php" class="floating-btn" title="Tambah Booking">
    <i class="fas fa-plus"></i>
</a>
```

**Styling:**

```css
.floating-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}
```

### 9.3 Responsive Design

**Breakpoints:**

| Viewport | Tampilan |
|----------|----------|
| `d-md-block` | Tabel untuk desktop |
| `d-md-none` | Card untuk mobile |
| `col-md-*` | 2 kolom untuk medium+ screen |
| `col-6` | 2 kolom untuk mobile |

### 9.4 Statistik Cards

```html
<div class="row mt-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clock fa-2x me-3"></i>
                    <div>
                        <div class="h4 mb-0"><?= $stats['menunggu'] ?? 0 ?></div>
                        <small>Menunggu</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ... more cards -->
</div>
```

## 10. Integrasi

### 10.1 Dengan Modul Dresscode

```php
// Get dresscode aktif untuk dropdown
$stmt = $pdo->query("SELECT id_dresscode, nama_pakaian FROM dresscode WHERE status = 'aktif' ORDER BY nama_pakaian");
$dresscodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Di form
<select name="id_dresscode">
    <?php foreach ($dresscodes as $dc): ?>
        <option value="<?= $dc['id_dresscode'] ?>">
            <?= htmlspecialchars($dc['nama_pakaian']) ?>
        </option>
    <?php endforeach; ?>
</select>
```

### 10.2 Dengan Modul User

```php
// Get penanggung jawab (admin & pembina)
$stmt = $pdo->query("
    SELECT id_user, nama_lengkap, peran 
    FROM user 
    WHERE peran IN ('admin', 'pembina') AND status_aktif = 1 
    ORDER BY nama_lengkap
");
$penanggung_jawab = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### 10.3 Dengan Modul Keuangan (Honor Acara)

Booking dengan kategori "Honor Acara" dapat diintegrasikan dengan modul keuangan untuk pencatatan pendapatan.

## 11. Pengembangan Selanjutnya

- [x] CRUD Booking Dasar
- [x] Status Management
- [x] Dokumentasi Upload
- [x] Real-Time Validation
- [x] Responsive Design
- [x] Toast Notifications
- [x] Duplicate Detection
- [x] AJAX Status Update
- [ ] Export ke PDF/Excel
- [ ] Calendar View
- [ ] Email Notification
- [ ] WhatsApp Integration
- [ ] Recurring Events
- [ ] Multiple Venue Support
- [ ] Budget Planning

## 12. Changelog

### v1.2.0
- Menambahkan dokumentasi.php untuk upload foto/video
- Menambahkan view_ajax.php untuk modal detail
- Menambahkan update_status.php API
- Menambahkan toast notifications
- Menambahkan duplicate booking detection
- Menambahkan client-side validation
- Perbaikan responsive design mobile card view
- Penambahan audit trail display di modal

### v1.1.0
- Penambahan FAB (Floating Action Button)
- Penambahan statistics cards
- Penambahan pagination
- Penambahan date range filter
- Penambahan confirmation modal
- Perbaikan aksesibilitas

### v1.0.0
- Rilis awal modul booking acara
- CRUD dasar booking
- Status management
- Integrasi dresscode
- Basic responsive design

---

*Dokumen ini dibuat berdasarkan implementasi di folder `/opt/lampp/htdocs/hadrahin/modules/acara`*

*Modul Booking Acara v1.2.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

