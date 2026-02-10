# Modul Manajemen User

Dokumentasi lengkap untuk modul pengelolaan user/anggota pada aplikasi Hadrah.

## 📋 Fitur

- **CRUD User**: Tambah, lihat, edit, dan hapus user
- **Multi-Role Management**: Mendukung 3 peran (Admin, Pembina, Anggota)
- **Status Management**: Aktif/Non-aktif user
- **Pagination**: Navigasi halaman untuk data banyak
- **Search & Filter**: Pencarian berdasarkan username atau nama lengkap
- **Real-Time Validation**: Validasi input username dan no_hp secara real-time dengan AJAX
- **Password Security**: Password di-hash menggunakan bcrypt (`password_hash`)
- **Toast Notifications**: Feedback visual untuk setiap aksi pengguna
- **Responsive Design**: Tampilan tabel untuk desktop, card untuk mobile
- **Floating Action Button (FAB)**: Pintasan tambah user di pojok layar
- **Confirmation Modal**: Konfirmasi sebelum submit atau hapus
- **Admin Protection**: Proteksi admin terakhir tidak bisa dinonaktifkan
- **Self-Delete Protection**: Tidak bisa menghapus diri sendiri
- **Audit Trail**: user_record dan user_modified untuk tracking

## 📁 Struktur File

```
modules/user/
├── index.php              # Halaman utama - daftar user dengan pagination & search
├── tambah.php             # Form tambah user baru dengan validasi real-time
├── edit.php               # Form edit user dengan kelola password
├── hapus.php              # Konfirmasi hapus user
├── api_check_username.php # API endpoint untuk validasi real-time username
├── api_check_no_hp.php   # API endpoint untuk validasi real-time no_hp
└── README.md             # Dokumentasi ini
```

## 📊 Struktur Database

### Tabel: `user`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id_user` | INT | Primary Key (Auto Increment) |
| `username` | VARCHAR(50) | Username unik untuk login |
| `password` | VARCHAR(255) | Password di-hash dengan bcrypt |
| `nama_lengkap` | VARCHAR(100) | Nama lengkap user |
| `no_hp` | VARCHAR(20) | Nomor HP (wajib untuk pembina/anggota) |
| `peran` | ENUM | `admin`, `pembina`, atau `anggota` |
| `status_aktif` | TINYINT | 1 = Aktif, 0 = Tidak Aktif |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diubah |
| `user_modified` | INT | User yang terakhir mengubah |
| `user_record` | INT | User yang mencatat |

### Indeks Database

```sql
-- Primary Key
ADD PRIMARY KEY (`id_user`);

-- Unique constraint untuk username
ADD UNIQUE KEY `username` (`username`);

-- Index untuk performa query filter peran & status
ADD KEY `idx_peran_aktif` (`peran`,`status_aktif`);
```

### Hubungan Antar Tabel

```
user (1) ───────> (N) absen_latihan (id_user)
     │                  │
     ├──> (N) alat_pengguna (id_user)
     │
     ├──> (N) booking_acara (id_user)
     │
     └──> (N) keuangan (id_user)
```

## 🔄 Real-Time Validation

### Validasi Username

Modul ini mengimplementasikan validasi real-time untuk username menggunakan AJAX:

```javascript
// Validasi saat user keluar dari field (onblur)
fetch('../../api/check_username.php?username=admin')

// Response
{
    "exists": true,
    "message": "Username sudah digunakan!"
}
```

### Validasi Nomor HP

```javascript
// Validasi saat user keluar dari field (onblur)
fetch('../../api/check_no_hp.php?no_hp=081234567890')

// Response
{
    "exists": false,
    "message": "Nomor HP tersedia!"
}
```

### Endpoint API

```php
// Validasi username (mode=add)
GET api/check_username.php?username=admin

// Validasi username dengan pengecualian (mode=edit)
GET api/check_username_edit.php?username=admin&exclude_id=5

// Validasi nomor HP (mode=add)
GET api/check_no_hp.php?no_hp=081234567890

// Validasi nomor HP dengan pengecualian (mode=edit)
GET api/check_no_hp.php?no_hp=081234567890&exclude_id=5

// Response sukses
{
    "exists": false,
    "message": "Username tersedia!"
}

// Response error (sudah ada)
{
    "exists": true,
    "message": "Username sudah digunakan!"
}
```

### Jenis Validasi

| Field | Validasi | Pesan Error |
|-------|----------|-------------|
| **Username** | Minimal 3 karakter, alphanumeric + underscore, unik | "Username sudah digunakan!" |
| **Password** | Minimal 3 karakter | "Password minimal 3 karakter!" |
| **Konfirmasi Password** | Harus sama dengan password | "Konfirmasi password tidak cocok!" |
| **Nama Lengkap** | Minimal 2 karakter | "Nama lengkap minimal 2 karakter!" |
| **No HP** | 10-15 digit angka, unik, wajib untuk pembina/anggota | "Nomor HP sudah digunakan!" |

## 🔔 Toast Notifications

Sistem notifikasi popup untuk feedback pengguna:

| Pesan | Tipe | Warna |
|-------|------|-------|
| User baru berhasil ditambahkan! | Sukses | bg-success |
| Data user berhasil diperbarui! | Sukses | bg-success |
| User berhasil dihapus! | Sukses | bg-success |
| Terjadi kesalahan! | Error | bg-danger |
| Tidak dapat menghapus diri sendiri! | Warning | bg-warning |

### Contoh Penggunaan

```php
// Redirect dengan parameter pesan
header('Location: index.php?msg=tambah_sukes');

// atau
header('Location: index.php?msg=edit_sukes');
header('Location: index.php?msg=hapus_sukes');
header('Location: index.php?msg=error');
```

## 🔐 Keamanan Password

### Password Hashing

Modul menggunakan `password_hash()` dengan algoritma bcrypt:

```php
// Hash password saat creating/updating
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Verifikasi password saat login
if (password_verify($input_password, $stored_hash)) {
    // Password cocok
}
```

### Proteksi Admin Terakhir

Sistem mencegah menonaktifkan admin terakhir:

```php
// Hitung jumlah admin aktif
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE peran = 'admin' AND status_aktif = 1");
$stmt->execute();
$activeAdminCount = $stmt->fetchColumn();

// Cek apakah ini admin terakhir
$isOnlyAdmin = ($user['peran'] === 'admin' && $user['status_aktif'] == 1 && $activeAdminCount <= 1);

// Disable checkbox jika admin terakhir
<input type="checkbox" <?= $isOnlyAdmin ? 'disabled' : '' ?>>
```

### Proteksi Hapus Diri Sendiri

```php
// Validasi: Jangan hapus diri sendiri
if ($id == $_SESSION['user_id']) {
    header('Location: index.php?msg=gagal&reason=self');
    exit;
}
```

## 🔧 Cara Penggunaan

### 1. Menambah User Baru
1. Klik tombol FAB (+) di pojok kanan bawah
2. Masukkan username (minimal 3 karakter, alphanumeric + underscore)
3. Masukkan nama lengkap
4. Masukkan nomor HP (wajib untuk Pembina/Anggota)
5. Masukkan password dan konfirmasi password
6. Pilih peran (Admin/Pembina/Anggota)
7. Toggle status aktif/non-aktif
8. Klik "Simpan" → Review di modal konfirmasi
9. Klik "Konfirmasi" untuk menyimpan

### 2. Melihat Daftar User
1. Halaman index menampilkan semua user
2. Gunakan search untuk filter berdasarkan username/nama lengkap
3. Navigasi halaman menggunakan pagination
4. Lihat peran dan status pada badge warna
5. Status badge: Hijau=Aktif, Abu=Tidak Aktif

### 3. Mengedit User
1. Klik tombol Edit pada user yang ingin diubah
2. Ubah data yang diperlukan (username, nama, no_hp, peran)
3. Untuk ubah password:isi password baru (opsional)
4. Toggle status aktif/non-aktif
5. Klik "Simpan Perubahan" → Review di modal
6. **Perhatian**: Admin terakhir tidak bisa dinonaktifkan

### 4. Menghapus User
1. Klik tombol Hapus pada user yang ingin dihapus
2. Konfirmasi penghapusan pada halaman
3. **Perhatian**: 
   - Tidak bisa menghapus diri sendiri
   - Menghapus user juga menghapus data terkait di tabel lain (dengan CASCADE)

### 5. Pencarian
- Masukkan kata kunci di search box
- Tekan Enter atau klik tombol "Cari"
- Reset untuk menampilkan semua data

## 👥 Hak Akses

| Peran | Tambah | Lihat | Edit | Hapus |
|-------|--------|-------|------|-------|
| Admin | ✓ | ✓ | ✓ | ✓ |
| Pembina | ✗ | ✓ | ✗ | ✗ |
| Anggota | ✗ | ✗ | ✗ | ✗ |

**Catatan**: 
- Pembina dan Anggota hanya bisa melihat daftar user
- Admin adalah satu-satunya yang bisa menambah, edit, dan hapus user
- Tidak bisa menghapus admin terakhir
- Tidak bisa menonaktifkan admin terakhir

## 📱 Responsive Design

| Tampilan | Komponen |
|----------|----------|
| **Desktop** | Tabel dengan kolom No, Username, Nama, No HP, Peran, Status, Aksi |
| **Mobile** | Card view dengan layout vertikal |
| **FAB** | Floating Action Button untuk tambah data |
| **Modal** | Bootstrap 5 modal yang responsive |
| **Toast** | Fixed position dengan z-index tinggi |

### Tampilan Tabel (Desktop)

- Avatar inisial dari username (huruf pertama uppercase,背景 warna primary)
- Badge peran: Admin=Merah, Pembina=Kuning, Anggota=Biru
- Badge status: Aktif=Hijau, Tidak Aktif=Abu
- Action buttons dengan icon dan tooltip
- Tooltip pada overflow text

### Tampilan Card (Mobile)

- Layout vertikal dengan avatar besar
- Badge peran dan status
- Tombol Edit dan Hapus full-width
- Touch-friendly buttons
- Header dengan avatar dan info user

## 🎨 User Interface Features

### Floating Action Button (FAB)

```html
<a href="tambah.php" class="floating-btn" title="Tambah User">
    <i class="fas fa-plus"></i>
</a>
```

### Avatar Inisial

```php
// Menampilkan avatar dari huruf pertama username
<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
     style="width: 32px; height: 32px; font-size: 14px;">
    <?= strtoupper(substr($user['username'], 0, 1)) ?>
</div>
```

### Badge Peran

```php
<?php
$peran_class = match($user['peran']) {
    'admin' => 'bg-danger',
    'pembina' => 'bg-warning text-dark',
    default => 'bg-primary'
};
?>
<span class="badge <?= $peran_class ?>">
    <i class="fas fa-user-tag me-1"></i><?= htmlspecialchars(ucfirst($user['peran'])) ?>
</span>
```

### Preview Form

Form dilengkapi preview real-time sebelum submit:

```html
<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal">
    <div class="modal-body">
        <div class="alert alert-light border rounded p-3">
            <div class="row mb-2">
                <div class="col-4 fw-bold">Username:</div>
                <div class="col-8" id="confirmUsername">-</div>
            </div>
            <div class="row mb-2">
                <div class="col-4 fw-bold">Nama:</div>
                <div class="col-8" id="confirmNama">-</div>
            </div>
            <!-- ... more fields -->
        </div>
    </div>
</div>
```

## 🔒 Keamanan

- **Session Validation**: `auth_check.php` di setiap halaman
- **Admin Check**: Role verification untuk tambah, edit, dan hapus
- **SQL Injection Protection**: Prepared statements di semua query
- **XSS Protection**: `htmlspecialchars()` pada semua output
- **Password Hashing**: bcrypt dengan `password_hash()`
- **Rate Limiting**: Via `includes/rate_limit.php`
- **Audit Trail**: user_record dan user_modified untuk tracking
- **Self-Protection**: Tidak bisa hapus/nonaktifkan diri sendiri
- **Last Admin Protection**: Admin terakhir tidak bisa dinonaktifkan

### Validasi Input

```php
// Search input sanitization
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

// ID validation
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Username validation
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore!";
}

// Phone number validation
if (!preg_match('/^[0-9]{10,15}$/', $no_hp)) {
    $errors[] = "Nomor HP harus berupa angka (10-15 digit)!";
}
```

## 📦 Integrasi

Modul user terintegrasi dengan:

| Modul | Jenis Integrasi |
|-------|-----------------|
| **Auth** | Session validation per halaman, password hashing |
| **Sidebar** | Menu navigasi utama |
| **Absen Latihan** | FK ke user (id_user) |
| **Alat Pengguna** | FK ke user (id_user) |
| **Booking Acara** | FK ke user (id_user) - penanggung jawab |
| **Keuangan** | FK ke user (id_user) - pencatat |
| **Database** | Koneksi via config/database.php |

### Query untuk Referential Integrity

```php
// Saat menghapus user, data terkait akan dihapus (CASCADE) atau diset NULL
// Tergantung constraint yang didefinisikan di database

// Contoh: absen_latihan menggunakan ON DELETE CASCADE
ALTER TABLE `absen_latihan`
ADD CONSTRAINT `fk_absen_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;
```

## 📈 Dashboard Statistics

Modul user tidak menampilkan statistik khusus, namun informasi user terintegrasi dengan:
- Sidebar menampilkan nama dan peran user login
- Dashboard admin menampilkan ringkasan aktivitas
- Profile user di header

## 🚀 Pengembangan Selanjutnya

- [x] CRUD User Dasar
- [x] Multi-Role Management (Admin, Pembina, Anggota)
- [x] Status Management (Aktif/Nonaktif)
- [x] Pagination
- [x] Search & Filter
- [x] Real-Time Validation (Username & No HP)
- [x] Password Hashing (bcrypt)
- [x] Toast Notifications
- [x] Responsive Design (Table + Card)
- [x] Confirmation Modal
- [x] Admin Protection
- [x] Self-Delete Protection
- [ ] Import user dari CSV/Excel
- [ ] Export user ke PDF/Excel
- [ ] Reset password oleh admin
- [ ] Aktivasi user via email
- [ ] Two-Factor Authentication (2FA)
- [ ] Login history/audit log
- [ ] User profile page
- [ ] Change password sendiri
- [ ] Gravatar integration untuk avatar

## 🐛 Riwayat Perbaikan

### v1.1.0 - Perbaikan Validasi Real-Time
- **Fitur**: Menambahkan validasi username real-time dengan AJAX
- **Fitur**: Menambahkan validasi nomor HP real-time dengan AJAX
- **Endpoint API**: api_check_username.php, api_check_username_edit.php, api_check_no_hp.php
- **Improvement**: Menambahkan feedback visual saat validasi
- **File Diedit**: tambah.php, edit.php

### v1.0.0
- Rilis awal modul user
- CRUD dasar user
- Multi-role management
- Status tracking
- Search dan filter
- Pagination
- Responsive design

---

*Modul Manajemen User v1.1.0 - Dibuat sesuai dengan desain dan pola yang konsisten dengan modul lainnya dalam aplikasi Hadrah.*

