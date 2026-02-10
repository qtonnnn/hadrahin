# Plan: Admin Dashboard Design untuk Aplikasi Hadrah

## Informasi yang Dikumpulkan

### Struktur Aplikasi
- **Framework**: PHP native dengan Bootstrap 5
- **Database**: MariaDB/MySQL dengan 8 tabel
- **3 Role**: Admin (superuser), Pembina (pengawas), Anggota (member)
- **Tema Saat Ini**: Putih dengan accent warna Bootstrap (biru, hijau, dll)

### File yang Relevan
- `dashboard/admin.php` - Dashboard admin existing
- `includes/header.php` - Header dengan navbar Bootstrap
- `includes/sidebar.php` - Sidebar navigation
- `modules/user/*.php` - Pattern form dan table untuk referensi styling
- `config/database.php` - Koneksi database

---

## Rencana Desain Dashboard Admin

### 1. Layout Baru dengan Sidebar
```
+---------------------------------------------------------------------+
|  LOGO HADRAH                                                        |
+---------------------------------------------------------------------+
| +---------------------+  +----------------------------------------+ |
| |                     |  |                                        | |
| |  DASHBOARD          |  |  HEADER: Welcome, Admin Name, Logout   | |
| |                     |  |                                        | |
| | +-----------------+ |  +----------------------------------------+ |
| | |                 | |  |                                        | |
| | |  MENU NAVIGASI  | |  |  STAT CARDS ROW                        | |
| | |                 | |  |  [Users] [Jadwal] [Booking] [Kas]      | |
| | | - Dashboard     | |  |                                        | |
| | - User            | |  +----------------------------------------+ |
| | - Jadwal Latihan  | |  |                                        | |
| | - Absensi         | |  |  CHART: User Growth (Realtime)         | |
| | - Booking Acara   | |  |                                        | |
| | - Inventaris      | |  +----------------------------------------+ |
| | - Keuangan        | |  |                                        | |
| | - Dresscode       | |  |  QUICK ACTIONS                         | |
| | - Laporan         | |  |  [Tambah User] [Buat Jadwal] [...]     | |
| |                   | |  |                                        | |
| |                   | |  +----------------------------------------+ |
| |                   | |  |                                        | |
| |                   | |  |  RECENT ACTIVITIES                     | |
| |                   | |  |  - User baru terdaftar                  | |
| |                   | |  |  - Jadwal latihan baru                  | |
| |                   | |  |  - Booking acara baru                   | |
| |                   | |  |                                        | |
| +-------------------+ |  +----------------------------------------+ |
+-----------------------+  +----------------------------------------+

```

### 2. Komponen yang Akan Dibuat

#### A. CSS Styling (assets/css/admin.css)
- Sidebar styling dengan hover effects
- Card styling (consistent dengan existing form)
- Stat cards dengan icons
- Chart containers
- Animation effects

#### B. Dashboard Admin (dashboard/admin.php)
- Query untuk statistik real-time
- Sidebar navigation
- 4 stat cards:
  - Total User (dengan breakdown per role)
  - Jadwal Latihan (bulan ini)
  - Booking Acara (aktif)
  - Saldo Kas
- Chart.js untuk user growth tracking
- Quick action buttons
- Recent activities list

#### C. API untuk Realtime Data (api/stats.php)
- Endpoint untuk mengambil statistik
- Auto-refresh setiap 30 detik

---

## File yang Akan Diedit/Dibuat

### File Baru:
1. `assets/css/admin.css` - Custom admin dashboard styles
2. `api/stats.php` - API endpoint untuk statistik real-time

### File yang Diedit:
1. `dashboard/admin.php` - Redesign dengan sidebar dan dashboard components
2. `includes/sidebar.php` - Tambahkan sidebar yang konsisten

---

## Fitur Realtime
- **User Growth Chart**: Grafik Line Chart menampilkan pertumbuhan user per bulan
- **Auto-refresh**: Stats diperbarui setiap 30 detik via JavaScript
- **Live Activity Feed**: Aktivitas terbaru dengan timestamp

---

## Tahapan Implementasi

### Langkah 1: Create admin.css
- Styling sidebar
- Stat cards styling
- Chart containers
- Responsive design

### Langkah 2: Create api/stats.php
- Endpoint untuk mengambil statistik user per bulan
- Total users, active users
- Recent activities

### Langkah 3: Redesign dashboard/admin.php
- Layout dengan sidebar
- Stat cards
- Chart.js integration
- Quick actions
- Recent activities

### Langkah 4: Update sidebar.php
- Konsisten dengan sidebar dashboard

---

## Konfirmasi

Mohon konfirmasi rencana ini sebelum implementasi dimulai.

**Estimasi Waktu**: ~1-2 jam untuk complete implementation

**Dependencies**:
- Bootstrap 5 (sudah ada via CDN)
- Font Awesome (sudah ada via CDN)
- Chart.js (perlu ditambahkan via CDN)

