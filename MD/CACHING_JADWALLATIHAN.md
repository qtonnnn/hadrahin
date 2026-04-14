# Dokumentasi Implementasi Caching - Modul Jadwal Latihan

## Pendahuluan

Dokumen ini menjelaskan implementasi caching untuk modul Jadwal Latihan yang bertujuan untuk meningkatkan performance aplikasi dengan mengurangi query database yang berulang untuk data yang jarang berubah.

## Latar Belakang

Berdasarkan evaluasi modul jadwallatihan, ditemukan bahwa:

1. **Query Berulang**: Statistik jadwal dihitung setiap kali halaman dimuat
2. **Data Stabil**: Data statistik jarang berubah sehingga cocok untuk di-cache
3. **Ineisiensi**: 3 query terpisah untuk menghitung jumlah status (direncanakan, selesai, dibatalkan)

## Arsitektur Caching

### 1. Cache Utility Class

**Lokasi**: `includes/cache.php`

```php
class Cache {
    // Metode utama:
    public static function get($key)        // Ambil data dari cache
    public static function set($key, $value, $ttl = 300)  // Simpan ke cache
    public static function delete($key)     // Hapus cache tertentu
    public static function clear()          // Hapus semua cache
    public static function remember($key, $callback, $ttl = 300)  // Get or set
}
```

### 2. Tipe Caching

- **Tipe**: File-based caching
- **Lokasi Directory**: `includes/cache/`
- **Format File**: Serialized PHP array dengan md5 hash
- **Struktur Data Cache**:
```php
[
    'value'   => mixed,     // Data yang di-cache
    'expires' => int        // Timestamp expiry
]
```

### 3. Konfigurasi TTL (Time-To-Live)

| Jenis Data | TTL | Alasan |
|------------|-----|--------|
| Statistik Jadwal | 10 menit (600s) | Data jarang berubah |
| Tanggal Maksimal | 10 menit (600s) | Update saat jadwal baru |
| Default | 5 menit (300s) | Standar untuk data dinamis |

## Data yang Di-cache

### 1. Statistik Jadwal (`jadwal_stats`)

**Query Sebelum Caching**:
```php
// 3 query terpisah
$stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_latihan WHERE status = 'direncanakan'");
$rencana_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_latihan WHERE status = 'selesai'");
$selesai_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM jadwal_latihan WHERE status = 'dibatalkan'");
$batal_count = $stmt->fetchColumn();
```

**Query Setelah Caching**:
```php
// 1 query dengan aggregation + caching
$stats = Cache::remember('jadwal_stats', function() use ($pdo) {
    $stmt = $pdo->query("SELECT
        SUM(CASE WHEN status = 'direncanakan' THEN 1 ELSE 0 END) as direncanakan,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan
        FROM jadwal_latihan");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}, 600);

$rencana_count = $stats['direncanakan'] ?? 0;
$selesai_count = $stats['selesai'] ?? 0;
$batal_count = $stats['dibatalkan'] ?? 0;
```

**Keuntungan**:
- Query berkurang dari 3 menjadi 1
- Hasil di-cache selama 10 menit
- Mengurangi beban database

### 2. Tanggal Maksimal (`jadwal_max_date`)

**Query Sebelum Caching**:
```php
$stmt = $pdo->query("SELECT MAX(tanggal) as max_date FROM jadwal_latihan");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

**Query Setelah Caching**:
```php
$max_date_result = Cache::remember('jadwal_max_date', function() use ($pdo) {
    $stmt = $pdo->query("SELECT MAX(tanggal) as max_date FROM jadwal_latihan");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}, 600);
```

## Strategi Invalidasi Cache

### 1. Invalidasi Pada Perubahan Data

Cache diinvalidasi secara otomatis saat terjadi perubahan data:

| Operasi | File | Kode |
|---------|------|------|
| Tambah Jadwal | `tambah.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |
| Edit Jadwal | `edit.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |
| Hapus Jadwal | `hapus.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |
| Hapus via GET | `index.php` | `Cache::delete('jadwal_stats'); Cache::delete('jadwal_max_date');` |

### 2. Alasan Invalidasi

- **Tambah Jadwal**: Data baru akan mempengaruhi statistik dan tanggal maksimal
- **Edit Jadwal**: Perubahan status mempengaruhi statistik
- **Hapus Jadwal**: Data yang dihapus mempengaruhi kedua metric

## Diagram Alur Caching

```
┌─────────────────┐
│  Permintaan     │
│  Halaman        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Cek Cache      │ ── Cache Ada? ──► Tampilkan Data Cache
│  (Cache::get)   │
└────────┬────────┘
         │ Cache Tidak Ada
         ▼
┌─────────────────┐
│  Eksekusi Query │
│  Database       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Simpan ke      │
│  Cache          │ ◄──────────────────
│  (Cache::set)   │     TTL: 10 menit
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Tampilkan      │
│  Data           │
└─────────────────┘
```

## Perbandingan Performance

### Sebelum Implementasi Caching

| Metrik | Nilai |
|--------|-------|
| Query per load (statistik) | 3 |
| Query per load (max date) | 1 |
| Total query per load | 4 |
| Waktu response | Tinggi |

### Setelah Implementasi Caching

| Metrik | Nilai |
|--------|-------|
| Query per load (cached) | 0 |
| Query per load (first hit) | 1 |
| Total query per load (cached) | 1 |
| Waktu response (cached) | Sangat rendah |
| Waktu response (first hit) | Sedang |

## File yang Berubah

| File | Perubahan |
|------|-----------|
| `includes/cache.php` | **Baru** - Utility class untuk caching |
| `modules/jadwallatihan/index.php` | - Import cache.php<br>- Caching untuk statistik<br>- Caching untuk max_date |
| `modules/jadwallatihan/tambah.php` | - Import cache.php<br>- Invalidasi cache setelah insert |
| `modules/jadwallatihan/edit.php` | - Import cache.php<br>- Invalidasi cache setelah update |
| `modules/jadwallatihan/hapus.php` | - Import cache.php<br>- Invalidasi cache setelah delete |

## Cara Kerja

### 1. Pertama Kali Akses (Cache Miss)

```
1. User mengakses halaman jadwallatihan
2. Cache::remember() cek cache 'jadwal_stats'
3. Cache tidak ditemukan (cache miss)
4. Eksekusi query database
5. Simpan hasil ke cache dengan TTL 10 menit
6. Tampilkan data ke user
```

### 2. Akses Berikutnya (Cache Hit)

```
1. User mengakses halaman jadwallatihan
2. Cache::remember() cek cache 'jadwal_stats'
3. Cache ditemukan (cache hit)
4. Langsung ambil data dari cache
5. Tampilkan data ke user (tanpa query database)
```

### 3. Setelah Perubahan Data

```
1. Admin menambah/mengubah/menghapus jadwal
2. Cache::delete() hapus cache 'jadwal_stats' dan 'jadwal_max_date'
3. User berikutnya akan mendapat cache miss
4. Query database dieksekusi ulang
5. Cache di-update dengan data baru
```

## Monitoring dan Maintenance

### 1. Lokasi Cache

```
includes/cache/
├── [md5_hash].cache  # File cache
└── ...
```

### 2. Manual Cache Clear

Jika diperlukan clearing manual cache:

```php
// Hapus cache tertentu
Cache::delete('jadwal_stats');
Cache::delete('jadwal_max_date');

// Hapus semua cache
Cache::clear();
```

### 3. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Cache tidak update | Cek invalidasi cache di setiap operasi CRUD |
| Cache file menumpuk | Cache auto-expire berdasarkan TTL |
| Cache directory penuh | TTL memastikan cache tidak menumpuk |

## Kesimpulan

Implementasi caching pada modul Jadwal Latihan memberikan:

1. **Performance**: Mengurangi query database dari 4 menjadi 1 per halaman
2. **Efisiensi**: Data yang jarang berubah di-cache selama 10 menit
3. **Konsistensi**: Cache diinvalidasi saat data berubah
4. **Scalability**: Mengurangi beban database untuk concurrent users

Rekomendasi selanjutnya:
- Implementasikan monitoring cache hit/miss ratio
- Pertimbangkan Redis/memcached untuk production dengan traffic tinggi
- Tambahkan cache warming untuk data yang sering diakses

