# API Endpoints

Dokumentasi lengkap untuk semua endpoint API di aplikasi Hadrahin.

---

## 📁 Struktur File

```
api/
├── change_password.php       # Endpoint ubah password
├── check_acara.php           # Validasi booking acara
├── check_dresscode.php       # Validasi nama dresscode
├── check_dresscode_edit.php  # Validasi dresscode (edit mode)
├── check_jadwal.php          # Validasi jadwal latihan
├── check_no_hp.php          # Validasi nomor HP
├── check_username.php        # Validasi username
├── check_username_edit.php   # Validasi username (edit mode)
├── check_username_exists.php # Cek username ada/tidak
└── stats.php                # Statistik dashboard admin
```

---

## 🔐 Autentikasi

### require_once '../includes/auth_check.php';

Beberapa endpoint memerlukan autentikasi. File ini memastikan:
- User sudah login
- Session valid
- Redirect ke login jika belum authenticated

### Endpoint Tanpa Autentikasi
- `change_password.php` - Tidak memerlukan session (validasi dengan password lama)
- `check_username.php` - Validasi publik
- `check_username_exists.php` - Validasi publik
- `check_no_hp.php` - Validasi publik

---

## 📋 Detail Endpoint

### 1. change_password.php

**Endpoint:** `api/change_password.php`

**Method:** POST

**Deskripsi:** Mengubah password user (non-admin)

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| username | string | Ya | Nama akun |
| old_password | string | Ya | Password lama |
| new_password | string | Ya | Password baru (min 3 karakter) |
| confirm_password | string | Ya | Konfirmasi password baru |

**Response:**
```json
// Success
{
    "success": true,
    "message": "Password berhasil diubah!"
}

// Error
{
    "success": false,
    "message": "Pesan error"
}
```

**Fitur:**
- Verifikasi password lama
- Hash password baru dengan bcrypt
- Validasi kecocokan password
- Admin tidak dapat menggunakan endpoint ini

**Catatan:** Endpoint ini TIDAK memerlukan autentikasi session karena menggunakan password lama sebagai verifikasi.

---

### 2. check_acara.php

**Endpoint:** `api/check_acara.php`

**Method:** POST

**Deskripsi:** Validasi duplikasi booking acara

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| tanggal | string | Ya | Tanggal acara (YYYY-MM-DD) |
| jam_mulai | string | Ya | Jam mulai (HH:MM:SS) |
| exclude_id | int | Tidak | ID booking yang dikecualikan (untuk edit) |

**Response:**
```json
// Success
{
    "success": true,
    "exists": true,
    "message": "Booking acara pada tanggal dan jam tersebut sudah ada!"
}

// Available
{
    "success": true,
    "exists": false,
    "message": "Booking tersedia"
}
```

**Kegunaan:** Mencegah booking acara pada waktu yang sama

---

### 3. check_dresscode.php

**Endpoint:** `api/check_dresscode.php`

**Method:** GET

**Deskripsi:** Validasi unique nama dresscode (tambah mode)

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| nama_pakaian | string | Ya | Nama pakaian yang divalidasi |

**Response:**
```json
// Exists
{
    "success": true,
    "exists": true,
    "message": "Nama pakaian sudah digunakan!"
}

// Available
{
    "success": true,
    "exists": false,
    "message": ""
}

// Error
{
    "success": false,
    "message": "Nama pakaian tidak boleh kosong"
}
```

---

### 4. check_dresscode_edit.php

**Endpoint:** `api/check_dresscode_edit.php`

**Method:** GET

**Deskripsi:** Validasi unique nama dresscode (edit mode)

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| nama_pakaian | string | Ya | Nama pakaian yang divalidasi |
| exclude_id | int | Ya | ID dresscode yang dikecualikan |

**Response:** Sama seperti `check_dresscode.php`

---

### 5. check_jadwal.php

**Endpoint:** `api/check_jadwal.php`

**Method:** POST

**Deskripsi:** Validasi duplikasi jadwal latihan

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| tanggal | string | Ya | Tanggal latihan (YYYY-MM-DD) |
| jam_mulai | string | Ya | Jam mulai (HH:MM:SS) |
| exclude_id | int | Tidak | ID jadwal yang dikecualikan (untuk edit) |

**Response:**
```json
// Duplicate
{
    "success": true,
    "exists": true,
    "message": "Jadwal latihan pada tanggal dan jam tersebut sudah ada!"
}

// Available
{
    "success": true,
    "exists": false,
    "message": "Jadwal tersedia"
}
```

---

### 6. check_no_hp.php

**Endpoint:** `api/check_no_hp.php`

**Method:** GET

**Deskripsi:** Validasi format dan uniqueness nomor HP

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| no_hp | string | Ya | Nomor HP (10-15 digit) |
| exclude_id | int | Tidak | ID user yang dikecualikan (untuk edit) |

**Validasi Format:** `/^[0-9]{10,15}$/`

**Response:**
```json
// Exists
{
    "exists": true
}

// Available
{
    "exists": false
}

// Error
{
    "exists": false,
    "error": "Pesan error"
}
```

---

### 7. check_username.php

**Endpoint:** `api/check_username.php`

**Method:** GET

**Deskripsi:** Validasi basic username exists

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| username | string | Ya | Username yang divalidasi |

**Response:**
```json
// Exists
{
    "exists": true
}

// Not found
{
    "exists": false
}
```

**Catatan:** Versi dasar, tanpa filtering admin

---

### 8. check_username_edit.php

**Endpoint:** `api/check_username_edit.php`

**Method:** GET

**Deskripsi:** Validasi username untuk mode edit

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| username | string | Ya | Username yang divalidasi |
| exclude_id | int | Tidak | ID user yang dikecualikan |

**Response:** Sama seperti `check_username.php`

---

### 9. check_username_exists.php

**Endpoint:** `api/check_username_exists.php`

**Method:** GET

**Deskripsi:** Validasi username untuk ubah password

**Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| username | string | Ya | Username yang divalidasi |

**Fitur Khusus:**
- Menemukan user berdasarkan username
- Memfilter role admin (tidak bisa ubah password sendiri)
- Mengembalikan nama lengkap jika ditemukan

**Response:**
```json
// Found (non-admin)
{
    "exists": true,
    "message": "Akun ditemukan: Nama Lengkap"
}

// Not found / Admin
{
    "exists": false,
    "message": "Akun tidak ditemukan"
}
```

---

### 10. stats.php

**Endpoint:** `api/stats.php`

**Method:** GET (memerlukan autentikasi)

**Deskripsi:** Mengambil statistik dashboard admin secara real-time

**Auth:** Memerlukan session login

**Response:**
```json
{
    "success": true,
    "timestamp": "2026-01-21 03:30:00",
    "data": {
        "total_users": 5,
        "active_users": 4,
        "users_by_role": {
            "admin": 1,
            "anggota": 4
        },
        "jadwal_bulan_ini": 3,
        "booking_aktif": 2,
        "keuangan": {
            "pemasukan": 1500000,
            "pengeluaran": 500000,
            "saldo": 1000000
        },
        "user_growth": [
            {"month": "2025-01", "count": 1},
            {"month": "2026-01", "count": 4}
        ],
        "recent_activities": [
            {
                "type": "user",
                "title": "User baru terdaftar: username",
                "icon": "fa-user-plus",
                "icon_bg": "bg-primary",
                "created_at": "2026-01-21 03:00:00",
                "time_ago": "30 menit yang lalu"
            }
        ]
    }
}
```

**Data yang Dikembalikan:**
- Total user & user aktif
- User berdasarkan role (admin/anggota)
- Jadwal latihan bulan ini
- Booking acara aktif
- Saldo kas (pemasukan - pengeluaran)
- Pertumbuhan user per bulan
- Aktivitas terbaru

---

## 🔒 Keamanan

### Anti-Cache Headers
```php
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0);
```

### Input Validation
- Trim whitespace
- Validasi format (regex)
- Validasi range (angka)
- Parameterized queries (SQL injection prevention)

### Response Headers
```php
header('Content-Type: application/json');
```

---

## ⚠️ Error Handling

### Format Error Response
```json
{
    "success": false,
    "message": "Pesan error",
    "error": "Detail error (opsional)"
}
```

### Jenis Error
| Kode | Deskripsi |
|------|-----------|
| 400 | Bad Request - Input tidak valid |
| 401 | Unauthorized - Autentikasi gagal |
| 404 | Not Found - Data tidak ditemukan |
| 500 | Server Error - Error sistem |

---

## 📱 Penggunaan di Frontend

### Fetch API Example
```javascript
// Check username
fetch('api/check_username.php?username=adminn')
    .then(res => res.json())
    .then(data => {
        if (data.exists) {
            console.log('Username sudah ada');
        }
    });

// Check duplicate acara
fetch('api/check_acara.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'tanggal=2026-01-25&jam_mulai=10:00:00'
})
    .then(res => res.json())
    .then(data => {
        if (data.exists) {
            alert(data.message);
        }
    });

// Get stats
fetch('api/stats.php')
    .then(res => res.json())
    .then(data => {
        console.log('Total Users:', data.data.total_users);
    });
```

### AJAX with jQuery
```javascript
// Check username
$.get('api/check_username.php', {username: 'adminn'}, function(data) {
    if (data.exists) {
        $('#username-error').text('Username sudah ada');
    }
}, 'json');
```

---

## 🔗 Endpoint di Modul Lain

### modules/keuangan/api_check_keuangan.php

**Lokasi:** `modules/keuangan/api_check_keuangan.php`

**Deskripsi:** Validasi transaksi keuangan

**Actions:**
| Action | Deskripsi |
|--------|-----------|
| `validate_jumlah` | Validasi jumlah transaksi |
| `validate_kategori` | Validasi kategori transaksi |
| `validate_tanggal` | Validasi tanggal transaksi |
| `check_duplicate` | Cek duplikasi transaksi |
| `validate_all` | Validasi lengkap form |

**Parameters (GET):**
```
?action=validate_jumlah&jumlah=100.000
?action=validate_kategori&kategori=Iuran Anggota&tipe=pemasukan
?action=validate_tanggal&tanggal=2026-01-21
?action=check_duplicate&mode=add&tipe=pemasukan&jumlah=100000&tanggal=2026-01-21
```

---

## 📊 Rate Limiting

Beberapa endpoint memiliki proteksi rate limiting melalui:
- Session-based limiting
- IP-based limiting (di handle oleh `includes/rate_limit.php`)

**Catatan:** Rate limiting lebih fokus di `auth/login.php` daripada endpoint API.

---

## 🧪 Testing

### Using curl

```bash
# Check username
curl "http://localhost/hadrahin/api/check_username.php?username=adminn"

# Check no hp
curl "http://localhost/hadrahin/api/check_no_hp.php?no_hp=081234567890"

# Check dresscode
curl "http://localhost/hadrahin/api/check_dresscode.php?nama_pakaian=Baju%20Batik"

# Get stats (requires session)
curl "http://localhost/hadrahin/api/stats.php"
```

---

## ❓ FAQ

**Q: Bagaimana cara menggunakan endpoint ini?**
A: Endpoint digunakan oleh form HTML melalui AJAX/Fetch untuk validasi real-time.

**Q: Apakah perlu autentikasi untuk semua endpoint?**
A: Tidak. Hanya `stats.php`, `check_acara.php`, `check_jadwal.php`, dan `api_check_keuangan.php` yang memerlukan autentikasi.

**Q: Bagaimana cara menambahkan endpoint baru?**
A: Buat file baru di folder `api/`, gunakan template yang sama dengan endpoint lain, dan include `auth_check.php` jika memerlukan autentikasi.

**Q: Apa bedanya check_username.php dengan check_username_exists.php?**
A: `check_username_exists.php` memiliki filtering khusus untuk role admin (tidak bisa ubah password sendiri), sedangkan `check_username.php` adalah versi basic.

---

## 📋 Changelog

| Versi | Perubahan |
|-------|-----------|
| 1.0.0 | Inisial API endpoints |
| 1.1.0 | Menambahkan stats.php |
| 1.2.0 | Menambahkan validasi real-time untuk keuangan |
| 1.3.0 | Perbaikan error handling |
| 1.4.0 | Menambahkan check_username_edit.php |

