# Dokumentasi Perbaikan Validasi Parameter

## Tanggal: Juni 2025
## Penulis: Developer Team

---

## 1. Latar Belakang

Pada analisis awal, ditemukan beberapa celah keamanan dan validasi pada parameter input di halaman:
- `modules/jadwallatihan/index.php`
- `modules/user/index.php`

Masalah yang ditemukan:
- Parameter tidak divalidasi dengan benar
- Rentan SQL Injection dan XSS
- Tidak ada validasi format tanggal
- Tidak ada batasan panjang input

---

## 2. Perubahan yang Dilakukan

### 2.1 modules/jadwallatihan/index.php

#### a) Validasi Page Number
**Lokasi:** Baris 24-26

**Sebelum:**
```php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
```

**Sesudah:**
```php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
```

**Tujuan:** Mencegah page number negatif atau nol.

---

#### b) Validasi Search Parameter
**Lokasi:** Baris 31-34

**Sebelum:**
```php
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
```

**Sesudah:**
```php
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);
```

**Tujuan:**
- Mencegah XSS (Cross-Site Scripting)
- Membersihkan tag HTML/script berbahaya
- Membatasi panjang input untuk performance

---

#### c) Validasi Status Filter (Whitelist)
**Lokasi:** Baris 37-39

**Sebelum:**
```php
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
```

**Sesudah:**
```php
$allowed_status = ['direncanakan', 'selesai', 'dibatalkan', ''];
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_status) 
    ? $_GET['status'] : '';
```

**Tujuan:**
- Hanya nilai yang diperbolehkan yang digunakan
- Mencegah SQL Injection via status parameter

---

#### d) Validasi Format Tanggal
**Lokasi:** Baris 42-57

**Sebelum:**
```php
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : $max_date_result['max_date'];
```

**Sesudah:**
```php
// Fungsi validasi format tanggal YYYY-MM-DD
function validateDateFormat($date, $format = 'Y-m-d') {
    if (empty($date)) return false;
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Validasi tanggal_mulai
$tanggal_mulai_default = date('Y-m-01');
if (isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai'])) {
    $tanggal_mulai = validateDateFormat($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : $tanggal_mulai_default;
} else {
    $tanggal_mulai = $tanggal_mulai_default;
}

// Validasi tanggal_selesai
$tanggal_selesai_default = $max_date_result['max_date'] ?? date('Y-m-d');
if (isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai'])) {
    $tanggal_selesai = validateDateFormat($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : $tanggal_selesai_default;
} else {
    $tanggal_selesai = $tanggal_selesai_default;
}
```

**Tujuan:**
- Memastikan format tanggal YYYY-MM-DD
- Menolak tanggal invalid (contoh: 2024-13-45)
- Fallback ke default jika format salah

---

#### e) Validasi Rentang Tanggal
**Lokasi:** Baris 66-71

**Sebelum:**
```php
// Tidak ada validasi rentang
```

**Sesudah:**
```php
// Validasi rentang tanggal (tanggal_mulai tidak boleh lebih besar dari tanggal_selesai)
if ($tanggal_mulai > $tanggal_selesai) {
    $temp = $tanggal_mulai;
    $tanggal_mulai = $tanggal_selesai;
    $tanggal_selesai = $temp;
}
```

**Tujuan:**
- Memastikan tanggal_mulai <= tanggal_selesai
- Auto-correction jika user salah input
- Mencegah query dengan rentang tidak valid

---

#### f) Input HTML dengan maxlength
**Lokasi:** Baris 213

**Sebelum:**
```html
<input type="text" name="search" class="form-control" 
       placeholder="Cari lokasi atau catatan..." 
       value="<?= htmlspecialchars($search) ?>">
```

**Sesudah:**
```html
<input type="text" name="search" class="form-control" 
       placeholder="Cari lokasi atau catatan..." 
       value="<?= htmlspecialchars($search) ?>"
       maxlength="100">
```

**Tujuan:**
- Membatasi input di sisi browser
- User tidak bisa input lebih dari 100 karakter

---

### 2.2 modules/user/index.php

#### a) Validasi Search Parameter
**Lokasi:** Baris 22-25

**Sebelum:**
```php
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
```

**Sesudah:**
```php
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);
```

**Tujuan:** Sama seperti di jadwallatihan/index.php

---

#### b) Input HTML dengan maxlength
**Lokasi:** Baris 119-122

**Sebelum:**
```html
<input type="text" name="search" class="form-control" 
       placeholder="Cari username atau nama lengkap..." 
       value="<?= htmlspecialchars($search) ?>">
```

**Sesudah:**
```html
<input type="text" name="search" class="form-control" 
       placeholder="Cari username atau nama lengkap..." 
       value="<?= htmlspecialchars($search) ?>"
       maxlength="100">
```

**Tujuan:** Sama seperti di jadwallatihan/index.php

---

## 3. Ringkasan Perubahan

### Tabel Perubahan

| File | Parameter | Sebelum | Sesudah |
|------|-----------|---------|---------|
| jadwallatihan/index.php | `$page` | Tidak ada validasi < 1 | `if ($page < 1) $page = 1` |
| jadwallatihan/index.php | `$search` | Tidak disanitasi | `htmlspecialchars`, `strip_tags`, max 100 char |
| jadwallatihan/index.php | `$status_filter` | Tidak divalidasi | Whitelist validation |
| jadwallatihan/index.php | `$tanggal_mulai` | Tidak divalidasi | `validateDateFormat()` |
| jadwallatihan/index.php | `$tanggal_selesai` | Tidak divalidasi | `validateDateFormat()` |
| jadwallatihan/index.php | Rentang tanggal | Tidak dicek | Auto-swap jika tidak valid |
| jadwallatihan/index.php | Input HTML | Tanpa maxlength | `maxlength="100"` |
| user/index.php | `$search` | Tidak disanitasi | `htmlspecialchars`, `strip_tags`, max 100 char |
| user/index.php | Input HTML | Tanpa maxlength | `maxlength="100"` |

---

## 4. Manfaat

### 4.1 Keamanan

| Ancaman | Solusi | Hasil |
|---------|--------|-------|
| SQL Injection | Prepared statements + validasi | Tidak ada query injection |
| XSS Attack | `htmlspecialchars` + `strip_tags` | Script berbahaya dibersihkan |
| Invalid Status | Whitelist validation | Hanya nilai yang diizinkan |
| Invalid Date | `validateDateFormat()` | Format YYYY-MM-DD wajib dipenuhi |

### 4.2 Stabilitas

| Masalah | Solusi | Hasil |
|---------|--------|-------|
| Page negatif | Validasi page > 0 | Tidak ada query error |
| Tanggal invalid | Validasi format | Query selalu valid |
| Rentang terbalik | Auto-swap | Hasil filter konsisten |

### 4.3 Performance

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| Panjang input | Tidak terbatas | Maks 100 karakter |
| Query string | Bisa sangat panjang | Terbatas dan predictable |
| Parsing tanggal | Error jika invalid | Fallback ke default |

---

## 5. Contoh Pengujian

### 5.1 Pengujian Status Filter

```bash
# Status valid
GET /modules/jadwallatihan/index.php?status=direncanakan
# Hasil: Filter berfungsi normal

# Status tidak valid
GET /modules/jadwallatihan/index.php?status=hack123
# Hasil: Diabaikan, gunakan default (semua status)

# SQL Injection attempt
GET /modules/jadwallatihan/index.php?status=' OR '1'='1
# Hasil: Diabaikan, bukan nilai yang diizinkan
```

### 5.2 Pengujian Tanggal

```bash
# Tanggal valid
GET /modules/jadwallatihan/index.php?tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31
# Hasil: Filter berfungsi normal

# Tanggal invalid
GET /modules/jadwallatihan/index.php?tanggal_mulai=2024-13-45
# Hasil: Gunakan default (tanggal 1 bulan ini)

# Rentang terbalik
GET /modules/jadwallatihan/index.php?tanggal_mulai=2024-12-31&tanggal_selesai=2024-01-01
# Hasil: Auto-swap, tanggal_mulai=2024-01-01, tanggal_selesai=2024-12-31
```

### 5.3 Pengujian Search

```bash
# Input normal
GET /modules/jadwallatihan/index.php?search=lokasi+Latihan
# Hasil: Pencarian berfungsi

# XSS attempt
GET /modules/jadwallatihan/index.php?search=<script>alert('xss')</script>
# Hasil: Tag dibersihkan, tidak ada alert

# Input sangat panjang (>100 char)
GET /modules/jadwallatihan/index.php?search=[string 200 char]
# Hasil: Dipotong menjadi 100 char
```

---

## 6. Rekomendasi Lanjutan

1. **Centralized Validation Helper**
   - Buat file `includes/validation.php` dengan fungsi validasi reusable
   - Gunakan di seluruh aplikasi

2. **Input Sanitization Middleware**
   - Implementasi di level entry point (`index.php`)
   - Sanitasi semua `$_GET`, `$_POST`, `$_COOKIE`

3. **Logging Invalid Input**
   - Catat upaya input tidak valid untuk monitoring
   - Blokir jika ada pola serangan

4. **Rate Limiting**
   - Batasi jumlah request per IP
   - Cegah brute force attack

5. **Security Header**
   - Tambah CSP (Content Security Policy)
   - X-XSS-Protection header

---

## 7. Kesimpulan

Perbaikan validasi parameter ini meningkatkan:
1. **Keamanan** aplikasi dari SQL Injection dan XSS
2. **Stabilitas** dengan mencegah input tidak valid
3. **User Experience** dengan auto-correction dan feedback
4. **Maintainability** dengan kode yang lebih bersih dan terstruktur

Perubahan ini bersifat backward compatible dan tidak mempengaruhi fungsionalitas existing.

