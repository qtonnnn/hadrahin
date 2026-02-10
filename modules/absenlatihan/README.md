# Modul Absensi Latihan

Dokumentasi lengkap untuk modul pengelolaan absensi latihan pada aplikasi Hadrah.

## 📋 Fitur

- **Input Absensi**: Form untuk mengabsen anggota pada jadwal tertentu
- **Status Kehadiran**: 3 pilihan status (Hadir, Izin, Alpa)
- **Bulk Actions**: Tombol cepat (Hadir Semua, Izin Semua, Alpa Semua)
- **Progress Tracking**: Progress bar real-time untuk memantau status absensi
- **Client-Side Search**: Pencarian anggota tanpa reload halaman
- **LocalStorage Persistence**: Data absensi tersimpan otomatis di browser
- **Export to CSV**: Ekspor data absensi ke format Excel/CSV
- **Advanced Filtering**: Filter berdasarkan status, jadwal, dan tanggal
- **Pagination**: Navigasi halaman untuk data banyak
- **Toast Notifications**: Feedback visual untuk setiap aksi
- **Responsive Design**: Tampilan optimal untuk desktop dan mobile
- **Auto-Update Status**: Status jadwal berubah ke "Selesai" setelah absensi disimpan
- **Confirmation Modal**: Konfirmasi sebelum menyimpan absensi
- **Unsaved Changes Warning**: Peringatan jika ada perubahan yang belum disimpan
- **Keyboard Shortcuts**: Ctrl+S untuk simpan, Esc untuk clear search

## 📁 Struktur File

```
modules/absenlatihan/
├── index.php              # Halaman utama - daftar riwayat absensi dengan filter
├── absen.php             # Form input absensi untuk jadwal tertentu
├── export.php            # Ekspor data absensi ke CSV
└── README.md             # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `absen_latihan`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_absen` | INT | Primary Key (Auto Increment) |
| `id_jadwal` | INT | Foreign Key ke `jadwal_latihan` |
| `id_user` | INT | Foreign Key ke `user` |
| `status_hadir` | ENUM | `hadir`, `izin`, atau `alpa` |
| `jam_absen` | TIMESTAMP | Waktu absen dicatat |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |
| `user_record` | INT | User yang mencatat absensi |

### Indeks Database

```sql
-- Primary Key
ADD PRIMARY KEY (`id_absen`);

-- Foreign Keys dengan CASCADE
ADD KEY `fk_absen_jadwal` (`id_jadwal`);
ADD KEY `fk_absen_user` (`id_user`);

-- Unique constraint untuk mencegah duplikasi
ADD UNIQUE KEY `uniq_user_jadwal` (`id_jadwal`,`id_user`);
```

### Hubungan Antar Tabel

```
jadwal_latihan (1) ───────> (N) absen_latihan (N) <────── (1) user
     id_jadwal                    id_jadwal              id_user
                                   id_user
```

### Constraints dengan CASCADE

```sql
-- Menghapus jadwal akan menghapus semua absensi terkait
ALTER TABLE `absen_latihan`
ADD CONSTRAINT `fk_absen_jadwal` FOREIGN KEY (`id_jadwal`) 
  REFERENCES `jadwal_latihan` (`id_jadwal`) ON DELETE CASCADE;

-- Menghapus user akan menghapus semua absensinya
ALTER TABLE `absen_latihan`
ADD CONSTRAINT `fk_absen_user` FOREIGN KEY (`id_user`) 
  REFERENCES `user` (`id_user`) ON DELETE CASCADE;
```

## 🔄 Status Kehadiran

### Jenis Status

| Status | Keterangan | Badge Color | Icon |
|--------|------------|-------------|------|
| **Hadir** | Anggota hadir latihan | bg-success | fa-check-circle |
| **Izin** | Anggota izin tidak hadir | bg-warning | fa-exclamation-triangle |
| **Alpa** | Anggota tidak hadir tanpa izin | bg-danger | fa-times-circle |

### Default Status

- Jika tidak ada status yang dipilih, default adalah `alpa`
- Validasi server-side memastikan hanya nilai yang diizinkan yang disimpan

## 🗃️ Caching System

### Cache Keys

| Cache Key | TTL | Deskripsi |
|-----------|-----|-----------|
| `absen_max_date` | 10 menit | Tanggal maksimal absensi |
| `absensi_stats` | 10 menit | Statistik jumlah per status |
| `jadwal_list` | 10 menit | Daftar jadwal untuk dropdown filter |

### Contoh Penggunaan Cache

```php
// Get stats dengan caching
$stats = Cache::remember('absensi_stats', function() use ($pdo) {
    $stmt = $pdo->query("SELECT
        SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status_hadir = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status_hadir = 'alpa' THEN 1 ELSE 0 END) as alpa
        FROM absen_latihan");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}, 600);
```

## 🔔 Toast Notifications

Sistem notifikasi popup untuk feedback pengguna:

| Pesan | Tipe | Warna |
|-------|------|-------|
| Data absensi berhasil diekspor! | Sukses | bg-success |
| Terjadi kesalahan! | Error | bg-danger |
| Anda tidak memiliki akses untuk operasi ini! | Warning | bg-warning |
| Tidak ada data absensi untuk diekspor! | Info | bg-info |

## 🔧 Cara Penggunaan

### 1. Input Absensi (Dari Halaman Jadwal)
1. Di halaman Jadwal Latihan, klik tombol "Absensi" pada jadwal tertentu
2. Sistem menampilkan form absen dengan daftar semua anggota aktif
3. Untuk setiap anggota, pilih status: Hadir, Izin, atau Alpa
4. Gunakan tombol bulk (Hadir/Izin/Alpa Semua) untuk cepat isi
5. Gunakan search untuk cari anggota tertentu
6. Progress bar menunjukkan progres absensi
7. Klik "Simpan Absensi" → Review di modal konfirmasi
8. Klik "Ya, Simpan" untuk menyimpan
9. Status jadwal otomatis berubah ke "Selesai"

### 2. Input Absensi (Langsung)
1. Akses menu "Absensi Latihan" di sidebar
2. Klik tombol "Absensi" pada jadwal yang diinginkan
3. Atau akses langsung: `modules/absenlatihan/absen.php?id={id_jadwal}`

### 3. Melihat Riwayat Absensi
1. Halaman index menampilkan semua riwayat absensi
2. Gunakan filter untuk pencarian:
   - **Search**: Cari berdasarkan nama, username, atau lokasi
   - **Status**: Filter berdasarkan Hadir/Izin/Alpa
   - **Jadwal**: Filter berdasarkan jadwal tertentu
   - **Tanggal**: Filter rentang tanggal
3. Navigasi halaman menggunakan pagination
4. Klik "Export Excel" untuk download data

### 4. Export Data
1. Di halaman index, klik tombol "Export Excel"
2. Modal konfirmasi akan muncul
3. Klik "Ya, Export" untuk download file CSV
4. File akan terdownload dengan filter yang aktif

### 5. Bulk Actions
```javascript
// Set semua anggota terlihat ke status tertentu
setAllStatus('hadir')  // Semua terlihat jadi Hadir
setAllStatus('izin')   // Semua terlihat jadi Izin
setAllStatus('alpa')   // Semua terlihat jadi Alpa
```

## 👥 Hak Akses

| Peran | Lihat Absensi | Input Absensi | Export | Hapus |
|-------|---------------|---------------|--------|-------|
| Admin | ✓ | ✓ | ✓ | ✓ |
| Pembina | ✓ | ✓ | ✓ | ✗ |
| Anggota | ✗ | ✗ | ✗ | ✗ |

**Catatan**:
- Hanya Admin dan Pembina yang dapat mengakses modul ini
- Anggota tidak memiliki akses ke modul absensi
- Hanya Admin yang dapat menghapus data absensi

## 📱 Responsive Design

| Tampilan | Komponen |
|----------|----------|
| **Desktop** | Tabel dengan kolom No, Jadwal, Anggota, Status, Waktu Absen |
| **Mobile** | Card view dengan layout vertikal |
| **Form** | Grid responsive (col-md-6, col-lg-4) untuk kartu anggota |
| **Modal** | Bootstrap 5 modal yang responsive |
| **Toast** | Fixed position dengan z-index tinggi |

### Tampilan Form Absensi (Desktop)

- 3 kolom kartu anggota per baris
- Tombol bulk actions dengan icon
- Progress bar dengan persentase

### Tampilan Form Absensi (Mobile)

- 1 kolom kartu anggota per baris
- Tombol bulk actions vertikal (stack)
- Progress bar menyesuaikan lebar layar

## 📊 Statistics Cards

Modul absensi menampilkan statistik di bagian bawah halaman:

```html
<div class="row mt-4">
    <div class="col-md-4 mb-3">
        <div class="card bg-success text-white">
            <!-- Hadir Count dengan persentase -->
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-warning text-white">
            <!-- Izin Count dengan persentase -->
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card bg-danger text-white">
            <!-- Alpa Count dengan persentase -->
        </div>
    </div>
</div>
```

| Statistik | Warna | Icon | Keterangan |
|-----------|-------|------|------------|
| Hadir | bg-success | fa-check-circle | Jumlah hadir + persentase |
| Izin | bg-warning | fa-exclamation-triangle | Jumlah izin + persentase |
| Alpa | bg-danger | fa-times-circle | Jumlah alpa + persentase |

## 🎨 User Interface Features

### Progress Tracker

```html
<div class="progress" style="height: 20px;">
    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
         role="progressbar" style="width: 0%">
        <span id="progressText" class="fw-bold">0%</span>
    </div>
</div>
```

### Badge Status

```php
<?php
$status_class = match($absen['status_hadir']) {
    'hadir' => 'bg-success',
    'izin' => 'bg-warning text-dark',
    'alpa' => 'bg-danger',
    default => 'bg-secondary'
};
?>
<span class="badge <?= $status_class ?>">
    <i class="fas <?= $status_icon ?> me-1"></i>
    <?= ucfirst($absen['status_hadir']) ?>
</span>
```

### LocalStorage Persistence

```javascript
// Simpan status ke localStorage
function saveStatusToStorage() {
    const statuses = {};
    const radios = document.querySelectorAll('.status-radio:checked');
    
    radios.forEach(radio => {
        statuses[radio.name] = radio.value;
    });
    
    localStorage.setItem('unsaved_absensi_' + currentJadwalId, JSON.stringify(statuses));
}

// Load status dari localStorage
function loadStatusFromStorage() {
    const savedStatuses = localStorage.getItem('unsaved_absensi_' + currentJadwalId);
    if (savedStatuses) {
        const statuses = JSON.parse(savedStatuses);
        // Apply statuses to radios
    }
}
```

### Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl + S | Simpan absensi |
| Esc | Clear search |

## 🔒 Keamanan

- **Session Validation**: `auth_check.php` di setiap halaman
- **Role Check**: Permission verification untuk akses modul
- **SQL Injection Protection**: Prepared statements di semua query
- **XSS Protection**: `htmlspecialchars()` pada semua output
- **Input Sanitization**: strip_tags, validasi format
- **Rate Limiting**: Via `includes/rate_limit.php`
- **Transaction Safety**: Menggunakan PDO transaction saat menyimpan absensi
- **Audit Trail**: user_record dan user_modified untuk tracking

### Validasi Input

```php
// Server-side validation: Pastikan semua anggota sudah diabsen
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user WHERE peran = 'anggota' AND status_aktif = 1");
$totalAnggota = $stmt->fetch()['total'];

$submittedCount = 0;
foreach ($anggotas as $anggota) {
    if (isset($_POST["status_{$anggota['id_user']}"])) {
        $submittedCount++;
    }
}

if ($submittedCount < $totalAnggota) {
    $error_message = "Masih ada {$missing_count} anggota yang belum diabsen!";
}
```

## 📦 Integrasi

Modul absensi terintegrasi dengan:

| Modul | Jenis Integrasi |
|-------|-----------------|
| **Jadwal Latihan** | FK ke jadwal_latihan (id_jadwal) dengan CASCADE |
| **User** | FK ke user (id_user) dengan CASCADE |
| **Sidebar** | Menu navigasi utama |
| **Auth** | Session validation per halaman |
| **Cache** | File-based caching untuk statistik |
| **Database** | Koneksi via config/database.php |

### Query untuk Join Data

```php
// Fetch absensi dengan join ke jadwal dan user
$sql = "SELECT a.*, j.tanggal, j.jam_mulai, j.lokasi, j.catatan,
               u.nama_lengkap, u.username, u.peran
        FROM absen_latihan a
        JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
        JOIN user u ON a.id_user = u.id_user
        $where_clause
        ORDER BY j.tanggal DESC, j.jam_mulai DESC, u.nama_lengkap ASC";
```

## 📥 Export Data

### Format Export (CSV)

| Kolom | Deskripsi |
|-------|-----------|
| No | Nomor urut |
| Tanggal Latihan | Format DD/MM/YYYY |
| Jam Mulai | Format HH:MM |
| Lokasi | Lokasi latihan |
| Status Jadwal | Direncanakan/Selesai/Dibatalkan |
| Nama Lengkap | Nama anggota |
| Username | Username anggota |
| No HP | Nomor HP anggota |
| Peran | Peran anggota |
| Status Absensi | Hadir/Izin/Alpa |
| Waktu Absen | Format DD/MM/YYYY HH:MM:SS |
| Catatan Jadwal | Catatan jadwal latihan |

### Contoh Export Query

```php
// Fetch all data tanpa pagination untuk export
$sql = "SELECT a.*, j.tanggal, j.jam_mulai, j.lokasi, j.catatan, j.status,
               u.nama_lengkap, u.username, u.peran, u.no_hp
        FROM absen_latihan a
        JOIN jadwal_latihan j ON a.id_jadwal = j.id_jadwal
        JOIN user u ON a.id_user = u.id_user
        $where_clause
        ORDER BY j.tanggal DESC, j.jam_mulai DESC, u.nama_lengkap ASC";
```

## 🚀 Pengembangan Selanjutnya

- [x] Input Absensi Dasar
- [x] Bulk Actions (Hadir/Izin/Alpa Semua)
- [x] Progress Tracking Real-Time
- [x] Client-Side Search
- [x] LocalStorage Persistence
- [x] Export ke CSV
- [x] Advanced Filtering
- [x] Pagination
- [x] Toast Notifications
- [x] Responsive Design
- [x] Confirmation Modal
- [x] Unsaved Changes Warning
- [x] Keyboard Shortcuts
- [ ] Edit Absensi (ubah status setelah disimpan)
- [ ] Hapus Absensi individual
- [ ] Rekap per anggota
- [ ] Grafik kehadiran
- [ ] Export ke PDF
- [ ] Persentase kehadiran per periode
- [ ] Notifikasi izin/alpa ke admin
- [ ] Absensi dengan GPS location

## 🐛 Riwayat Perbaikan

### v1.2.0 - Improved Absensi Form
- **Fitur**: Client-side search filtering tanpa page reload
- **Fitur**: Better progress tracking dengan visual feedback
- **Fitur**: LocalStorage untuk persistence data
- **Improvement**: Validasi dengan localStorage
- **Improvement**: UX dengan visual feedback yang lebih baik
- **File Diedit**: absen.php

### v1.1.0 - Export Feature
- **Fitur**: Ekspor data absensi ke CSV/Excel
- **Improvement**: Modal konfirmasi sebelum export
- **Improvement**: Filter yang aktif dipertahankan saat export
- **File Baru**: export.php

### v1.0.0
- Rilis awal modul absensi latihan
- CRUD dasar absensi
- Status management
- Pagination
- Responsive design

---

*Modul Absensi Latihan v1.2.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

