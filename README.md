# Hadrahin - Sistem Manajemen Kelompok Hadrah

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892B0)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1)](https://www.mysql.com/)
[![XAMPP](https://img.shields.io/badge/XAMPP-8.0%2B-FD4C4C)](https://www.apachefriends.org/)

**Hadrahin** adalah aplikasi web berbasis PHP untuk manajemen aktivitas kelompok Hadrah Islami. Sistem ini dirancang khusus untuk mengelola jadwal latihan, acara, dresscode, keuangan, inventaris alat, dan manajemen anggota dengan fitur keamanan lengkap (rate limiting, validasi real-time, error handling).

## ✨ Fitur Utama

| Modul | Deskripsi | Fitur |
|-------|-----------|-------|
| **Dashboard** | Overview statistik real-time | User stats, jadwal bulan ini, saldo keuangan, aktivitas terbaru |
| **User Management** | Kelola anggota | Tambah/edit/hapus user, validasi username/no.HP unik |
| **Jadwal Latihan** | Manajemen jadwal | CRUD, validasi duplikasi waktu, notifikasi cron |
| **Acara** | Booking event | CRUD, galeri dokumentasi, update status, validasi konflik |
| **Dresscode** | Aturan pakaian | Upload foto, CRUD, validasi nama unik |
| **Keuangan** | Buku kas | Pemasukan/pengeluaran, export Excel, detail transaksi |
| **Alat** | Inventaris | CRUD alat/peralatan |
| **Absen Latihan** | Presensi | Absen QR/location, export laporan |
| **Playlist** | Audio Hadrah | Upload lagu & cover, galeri |
| **Auth** | Sistem login aman | Rate limiting (5x gagal=blokir 15menit), reset password |

## 🛠 Tech Stack

```
Backend: PHP 7.4+, MySQL 8.0+
Server: Apache (XAMPP)
Security: Rate limiting, bcrypt, parameterized queries, anti-cache
Frontend: HTML5, CSS3, Bootstrap 5, FontAwesome, jQuery
API: JSON RESTful endpoints (real-time validation)
Database: InnoDB, foreign keys
Files: Upload gambar/audio (max 10MB)
Cron: Notifikasi jadwal/acara
Cache: File-based caching
Logs: Error tracking, login attempts
```

## 📁 Struktur Direktori

```
hadrahin/
├── index.php              # Landing page
├── landing_page.php       # Halaman utama
├── auth/                  # Sistem autentikasi
├── api/                   # JSON API endpoints
├── config/                # Database & SQL dump
├── dashboard/             # Halaman admin/anggota
├── includes/              # Helper functions
├── modules/               # Modul fitur utama
│   ├── acara/             # Manajemen acara
│   ├── jadwallatihan/     # Jadwal latihan
│   ├── dresscode/         # Aturan pakaian
│   ├── keuangan/          # Buku kas
│   ├── alat/              # Inventaris
│   ├── user/              # Manajemen user
│   └── ...
├── assets/uploads/        # Gambar, audio, dokumen
├── logs/                  # Error logs & rate limit
├── MD/                    # Dokumentasi lengkap (diagram, analisis)
├── TODO/                  # Daftar pengembangan
└── README.md              # Dokumen ini
```

## 🚀 Instalasi & Setup

### 1. Prasyarat
- XAMPP 8.0+ (Apache + MySQL)
- PHP 7.4+ dengan PDO, mysqli
- Browser modern (Chrome/Firefox)

### 2. Download & Setup
```bash
# Clone repo
git clone https://github.com/[username]/hadrahin.git
cd hadrahin

# Copy ke XAMPP
cp -r hadrahin c:/xampp/htdocs/
```

### 3. Konfigurasi Database
1. Import `config/hadrahin.sql` ke phpMyAdmin
2. Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Kosong untuk XAMPP default
define('DB_NAME', 'hadrahin');
```

### 4. Jalankan
```bash
# Start XAMPP (Apache + MySQL)
# Buka browser: http://localhost/hadrahin
```

**Default Login:**
```
Admin: username=admin | password=admin123
Anggota: Buat user baru via dashboard
```

## 📊 Dashboard Demo

Admin Dashboard menampilkan:
```
📈 Total Users: 5 | Aktif: 4
📅 Jadwal Bulan Ini: 3
💰 Saldo Kas: Rp1.000.000
📱 Aktivitas Terbaru:
  • User baru: "anggota1" (2 menit lalu)
  • Booking acara: "Majlis 25 Jan" (1 jam lalu)
```

## 🔐 Keamanan

- **Rate Limiting**: Login gagal 5x = blokir IP 15 menit
- **API Validation**: Real-time cek duplikasi (username, jadwal, acara)
- **File Security**: `.htaccess` proteksi config/logs/cache
- **Password**: Bcrypt hashing
- **SQL**: Parameterized queries
- **Upload**: Validasi tipe/ukuran file

## 📱 Responsive Design

- Mobile-first Bootstrap 5
- Sidebar collapsible
- Form validasi real-time
- Toast notifications

## 📈 API Endpoints

Lihat `api/README.md` untuk 10+ endpoints:
- `/api/stats.php` - Dashboard stats
- `/api/check_username.php` - Validasi user
- `/api/check_jadwal.php` - Validasi jadwal
- `/api/change_password.php` - Ubah password

## 📚 Dokumentasi Lengkap

- **Diagram**: `MD/DIAGRAM_ALUR_SISTEM.md`, `MD/ER_DIAGRAM.md`
- **Analisis**: `MD/EVALUASI_ALUR_*.md`
- **Modul**: Setiap folder modules punya README.md
- **TODO**: `TODO/*.md` - Rencana pengembangan
- **Use Cases**: `USE_CASES.md`

## 🤝 Kontribusi

1. Fork repo
2. Buat branch `feature/xxx`
3. Commit changes
4. Push & buat Pull Request


## 🙏 Terima Kasih

made in muhammad fatoni || RPL SMKN 2 BANGKALAN

---

**Versi 1.0.0** | **Update: Jan 2026** | **Built with PHP & MySQL**
