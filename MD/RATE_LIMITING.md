# Rate Limiting Documentation

## Overview

Rate limiting adalah sistem proteksi untuk mencegah **brute force attack** pada halaman login. Sistem ini membatasi jumlah percobaan login dari satu IP address dalam periode waktu tertentu.

---

## Aturan Rate Limiting

| Percobaan Gagal | Durasi Blokir |
|-----------------|---------------|
| 12x | 5 menit |
| 24x | 10 menit |
| 36x | 15 menit |
| ... | ... |

**Logika:** Setiap kelipatan 12x percobaan gagal, waktu blokir bertambah 5 menit.

---

## Fitur Countdown Timer Real-Time

### Tampilan Saat Diblokir

```
┌─────────────────────────────────────┐
│         ⚠️ Akses Diblokir           │
│                                     │
│  Terlalu banyak percobaan login     │
│  yang gagal.                        │
│                                     │
│  Silakan tunggu:                    │
│                                     │
│        ┌─────────────┐              │
│        │   5:00      │  ← Timer     │
│        │   menit     │    Real-Time │
│        └─────────────┘              │
│                                     │
│  Halaman akan otomatis refresh      │
│  setelah waktu habis                │
└─────────────────────────────────────┘
```

### Fitur Timer:
- ✅ Hitungan mundur detik per detik
- ✅ Format menit:detik (contoh: 5:00)
- ✅ Form otomatis dinonaktifkan
- ✅ Auto-refresh saat waktu habis

---

## Cara Kerja

### 1. Pengeblokan Sebelum Login
```php
// Cek rate limit sebelum memproses login
$rate_limit = check_rate_limit();
if ($rate_limit['blocked']) {
    $error = $rate_limit['message'];
}
```

### 2. Pencatatan Gagal
```php
// Saat password salah
record_failed_attempt();
```

### 3. Reset Setelah Berhasil
```php
// Saat login berhasil
reset_rate_limit();
```

---

## File yang Terlibat

```
hadrahin/
├── auth/
│   └── login.php              # Halaman login
├── includes/
│   └── rate_limit.php         # Logika rate limiting
└── logs/
    ├── php_errors.log         # Error log
    └── login_attempts.dat     # Data percobaan login
```

---

## Struktur Data (`login_attempts.dat`)

```json
{
  "login_attempts_192.168.1.1": {
    "count": 12,
    "last_attempt": 1700000000,
    "blocked_until": 1700000300
  }
}
```

| Field | Deskripsi |
|-------|-----------|
| `count` | Jumlah percobaan gagal |
| `last_attempt` | Timestamp percobaan terakhir |
| `blocked_until` | Timestamp berakhirnya blokir |

---

## Pembersihan Otomatis

Data percobaan lama (lebih dari 24 jam) akan dihapus secara otomatis saat ada percobaan login baru.

---

## Testing Rate Limiting

### Scenario 1: 11x Gagal (Boleh Coba)
```bash
# Kirim 11 request POST ke login
# Result: Semua gagal, tidak diblokir
```

### Scenario 2: 12x Gagal (Diblokir)
```bash
# Kirim 12 request POST ke login
# Result: "Terlalu banyak percobaan login. Silakan coba lagi dalam 300 detik."
```

### Scenario 3: Setelah 5 Menit (Bisa Coba)
```bash
# Tunggu 5 menit
# Kirim request POST ke login lagi
# Result: Boleh mencoba lagi (counter reset ke 0)
```

---

## Keamanan Tambahan

### Yang Sudah Diimplementasikan:
- ✅ Blokir berbasis IP address
- ✅ Pembersihan data otomatis 24 jam
- ✅ Exponentional blocking time
- ✅ Logging ke file

### Yang Bisa Ditambahkan:
- [ ] CAPTCHA setelah 5x gagal
- [ ] Notifikasi ke admin saat ada serangan
- [ ] Whitelist IP tertentu
- [ ] Logging ke database untuk analisis

---

## Troubleshooting

### Rate Limit Tidak Bekerja
1. Cek apakah folder `logs/` memiliki permission tulis
2. Cek apakah file `login_attempts.dat` ada
3. Cek error log di `logs/php_errors.log`

### IP Selalu Diblokir
1. Hapus manual data di `logs/login_attempts.dat`
2. Atau tunggu 24 jam untuk pembersihan otomatis

---

## Referensi

- [OWASP Rate Limiting](https://owasp.org/www-community/attacks/Brute_force_attack)
- [PHP Session Security](https://www.php.net/manual/en/session.security.php)

