# Modul Jadwal Latihan

Dokumentasi lengkap untuk modul pengelolaan jadwal latihan di aplikasi Hadrahin.

## Lokasi Folder

```
modules/jadwallatihan/
├── index.php      # Daftar jadwal latihan
├── add.php        # Form tambah jadwal + proses insert
├── edit.php       # Form edit jadwal + proses update
├── hapus.php      # Proses hapus jadwal
└── validasi.php   # Fungsi validasi bentrok jadwal
```

## Struktur Tabel jadwal_latihan

```sql
CREATE TABLE jadwal_latihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    lokasi VARCHAR(200) NOT NULL,
    status ENUM('aktif', 'selesai') NOT NULL DEFAULT 'aktif',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id_user)
);
```

## Aturan Akses

| Peran | Akses |
|-------|-------|
| Admin | ✅ Full access (view, add, edit, delete) |
| Pembina | ✅ Full access (view, add, edit, delete) |
| Anggota | ❌ Tidak boleh akses |

Semua file menggunakan:
```php
cekLogin();              // Wajib login
cekRole(['admin','pembina']); // Admin & pembina saja
```

## Fitur Each File

### 1. validasi.php (Fungsi Validasi)

**Fungsi:** Mencegah jadwal latihan yang bentrok

**Fungsi utama:**
```php
cekBentrokJadwal($tanggal, $jam_mulai, $jam_selesai, $exclude_id = null)
```

**Parameter:**
- `$tanggal` - Tanggal jadwal (format Y-m-d)
- `$jam_mulai` - Jam mulai (format H:i)
- `$jam_selesai` - Jam selesai (format H:i)
- `$exclude_id` - ID jadwal yang dieksklusi (untuk mode edit, opsional)

**Return value:**
- `true` - Jika jadwal bentrok
- `false` - Jika jadwal tidak bentrok

**Logika Bentrok:**
```sql
SELECT id FROM jadwal_latihan 
WHERE tanggal = '$tanggal'
AND (
    (jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai')
)
```

Jadwal dianggap bentrok jika:
- Tanggal sama
- DAN jam tumpang tindih (overlap)
  - Jam mulai baru < Jam selesai lama
  - DAN Jam selesai baru > Jam mulai lama

**Contoh:**
- Jadwal A: 08:00 - 10:00
- Jadwal B: 09:00 - 11:00 → **BENTROK** (overlap 09:00-10:00)
- Jadwal C: 10:00 - 12:00 → **TIDAK BENTROK** (tepat bersambung)

---

### 2. index.php (Daftar Jadwal)

**Fungsi:** Menampilkan semua jadwal latihan dalam tabel

**URL:** `modules/jadwallatihan/index.php`

**Kolom yang ditampilkan:**
- No (urutan)
- Tanggal (format d-m-Y)
- Jam (format HH:MM - HH:MM)
- Lokasi
- Status (Aktif/Selesai)
- Pembuat (nama user yang buat jadwal)
- Aksi (Edit/Hapus)

**Query JOIN untuk menampilkan nama pembuat:**
```php
SELECT j.*, u.nama_user as pembuat 
FROM jadwal_latihan j
LEFT JOIN user u ON j.created_by = u.id_user
ORDER BY j.tanggal DESC, j.jam_mulai DESC
```

**Fitur:**
- Link ke form tambah jadwal
- Link kembali ke dashboard
- Notifikasi pesan (via GET parameter)
- Pesan jika belum ada jadwal

**Sorting:**
- Descending by tanggal (terbaru di atas)
- Descending by jam_mulai

---

### 3. add.php (Tambah Jadwal)

**Fungsi:** Form untuk menambahkan jadwal latihan baru

**URL:** `modules/jadwallatihan/add.php`

**Field input:**
1. Tanggal (*) - date picker
2. Jam Mulai (*) - time picker
3. Jam Selesai (*) - time picker
4. Lokasi (*) - text input

**Status default:** `aktif` (otomatis)

**created_by:** Diambil dari `$_SESSION['user_id']`

**Validasi:**
| Field | Aturan |
|-------|--------|
| Tanggal | Wajib diisi |
| Jam Mulai | Wajib diisi |
| Jam Selesai | Wajib diisi |
| Lokasi | Wajib diisi |
| Jam | Jam selesai > jam mulai |

**Validasi Bentrok:**
```php
if (cekBentrokJadwal($tanggal, $jam_mulai, $jam_selesai)) {
    $errors[] = "Jadwal bentrok dengan jadwal yang sudah ada";
}
```

**Proses Insert:**
```php
$sql = "INSERT INTO jadwal_latihan 
        (tanggal, jam_mulai, jam_selesai, lokasi, status, created_by) 
        VALUES ('$tanggal', '$jam_mulai', '$jam_selesai', '$lokasi', 'aktif', '$created_by')";
```

**Redirect:**
- Sukses: `Location: index.php?msg=Jadwal+berhasil+ditambahkan`
- Error: Tampilkan pesan error

---

### 4. edit.php (Edit Jadwal)

**Fungsi:** Form untuk mengedit jadwal latihan yang sudah ada

**URL:** `modules/jadwallatihan/edit.php?id=123`

**Ambil data berdasarkan ID:**
```php
$id = (int)$_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM jadwal_latihan WHERE id = $id");
$jadwal = mysqli_fetch_assoc($query);
```

**Field input:**
1. Tanggal (*) - date picker
2. Jam Mulai (*) - time picker
3. Jam Selesai (*) - time picker
4. Lokasi (*) - text input
5. Status (*) - dropdown (Aktif/Selesai)

**Validasi:**
| Field | Aturan |
|-------|--------|
| Tanggal | Wajib diisi |
| Jam Mulai | Wajib diisi |
| Jam Selesai | Wajib diisi |
| Lokasi | Wajib diisi |
| Jam | Jam selesai > jam mulai |

**Validasi Bentrok (dengan exclude):**
```php
// Exclude ID yang sedang diedit
if (cekBentrokJadwal($tanggal, $jam_mulai, $jam_selesai, $id)) {
    $errors[] = "Jadwal bentrok dengan jadwal yang sudah ada";
}
```

**Proses Update:**
```php
$sql = "UPDATE jadwal_latihan 
        SET tanggal = '$tanggal', 
            jam_mulai = '$jam_mulai', 
            jam_selesai = '$jam_selesai', 
            lokasi = '$lokasi', 
            status = '$status' 
        WHERE id = $id";
```

**Redirect:**
- Sukses: `Location: index.php?msg=Jadwal+berhasil+diupdate`
- Error: Tampilkan pesan error

---

### 5. hapus.php (Hapus Jadwal)

**Fungsi:** Menghapus jadwal latihan dari database

**URL:** `modules/jadwallatihan/hapus.php?id=123`

**Validasi ID:**
```php
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?msg=ID+tidak+valid');
    exit;
}
$id = (int)$_GET['id'];
```

**Proses Delete:**
```php
$sql = "DELETE FROM jadwal_latihan WHERE id = $id";

if (mysqli_query($koneksi, $sql)) {
    header('Location: index.php?msg=Jadwal+berhasil+dihapus');
    exit;
} else {
    header('Location: index.php?msg=Gagal+menghapus+jadwal');
    exit;
}
```

**Catatan:**
- Tidak ada proteksi khusus (admin & pembina boleh hapus)
- Tidak ada "self-delete" protection karena jadwal bukan user

---

## Keamanan

### 1. SQL Injection Prevention

Semua input user dibersihkan dengan:
```php
mysqli_real_escape_string($koneksi, $_POST['field']);
```

### 2. XSS Prevention

Output ditampilkan dengan:
```php
htmlspecialchars($string);
```

### 3. Session Validation

Semua halaman memeriksa:
```php
cekLogin();              // Apakah user sudah login?
cekRole(['admin','pembina']); // Apakah user admin atau pembina?
```

### 4. Type Casting

ID dari URL selalu dikonversi ke integer:
```php
$id = (int)$_GET['id'];
```

---

## Validasi Bentrok - Detail

### Skenario Bentrok

| Jadwal Lama | Jadwal Baru | Bentrok? | Alasan |
|-------------|-------------|----------|--------|
| 08:00-10:00 | 09:00-11:00 | ✅ YA | Overlap 09:00-10:00 |
| 08:00-10:00 | 10:00-12:00 | ❌ TIDAK | Tepat bersambung |
| 08:00-10:00 | 07:00-08:00 | ❌ TIDAK | Tepat bersambung |
| 08:00-10:00 | 07:00-09:00 | ✅ YA | Overlap 08:00-09:00 |
| 08:00-10:00 | 11:00-13:00 | ❌ TIDAK | Tidak overlap |

### Mode Edit

Pada mode edit, jadwal yang sedang diedit **tidak termasuk** dalam pengecekan bentrok.

**Contoh:**
- Jadwal A: ID=1, 08:00-10:00
- User ingin edit Jadwal A menjadi 09:00-11:00
- Hasil: **TIDAK BENTROK** (karena exclude ID=1)

---

## Pesan Notifikasi

| Pesan | Keterangan |
|-------|------------|
| Jadwal berhasil ditambahkan | Insert berhasil |
| Jadwal berhasil diupdate | Update berhasil |
| Jadwal berhasil dihapus | Delete berhasil |
| ID tidak valid | Parameter ID kosong/tidak ada |
| Jadwal tidak ditemukan | ID tidak ditemukan di database |
| Jadwal bentrok... | Validasi bentrok gagal |
| Jam selesai harus lebih besar dari jam mulai | Validasi jam gagal |

---

## Cara Penggunaan

### 1. Akses Modul Jadwal Latihan

1. Login sebagai admin atau pembina
2. Akses menu jadwal latihan di sidebar
3. Atau langsung akses: `http://localhost/hadrahin/modules/jadwallatihan/index.php`

### 2. Menambah Jadwal Baru

1. Klik tombol "[+] Tambah Jadwal"
2. Isi data jadwal:
   - Tanggal: Pilih tanggal latihan
   - Jam Mulai: Pilih jam mulai
   - Jam Selesai: Pilih jam selesai
   - Lokasi: Tulis lokasi latihan
3. Klik "Simpan"
4. Sistem akan cek bentrok:
   - Jika bentrok: Tampilkan error
   - Jika tidak bentrok: Jadwal ditambahkan

### 3. Mengedit Jadwal

1. Klik tombol "Edit" pada jadwal yang ingin diubah
2. Ubah data yang diperlukan
3. Klik "Simpan"
4. Sistem akan cek bentrok (exclude jadwal ini)
5. Perubahan langsung生效

### 4. Menghapus Jadwal

1. Klik tombol "Hapus" pada jadwal yang ingin dihapus
2. Konfirmasi pada popup
3. Jadwal akan dihapus dari database

---

## Contoh Skenario

### Skenario 1: Latihan Biasa

**Input:**
- Tanggal: 2024-01-15
- Jam: 08:00 - 10:00
- Lokasi: Masjid Al-Hasanah

**Hasil:** Jadwal dibuat dengan status "aktif"

---

### Skenario 2: Validasi Bentrok

**Input:**
- Tanggal: 2024-01-15
- Jam: 08:00 - 10:00
- Lokasi: Masjid Al-Hasanah

**Sistem cek:** Apakah ada jadwal lain pada 2024-01-15 dengan jam overlap?

**Jika ada jadwal 09:00-11:00:**
- Hasil: **ERROR** - "Jadwal bentrok dengan jadwal yang sudah ada"

**Jika ada jadwal 10:00-12:00:**
- Hasil: **BERHASIL** - Jadwal ditambahkan

---

### Skenario 3: Edit dengan Pindah Jam

**Data awal:**
- ID: 1
- Jam: 08:00 - 10:00

**User edit:**
- Jam: 11:00 - 13:00

**Sistem cek:**
- Cari jadwal lain pada tanggal yang sama
- Exclude ID=1
- Jika tidak ada yang bentrok: Update berhasil

---

## Troubleshooting

### Error: "Jadwal bentrok dengan jadwal yang sudah ada"

**Penyebab:** Ada jadwal lain pada tanggal dan jam yang overlap

**Solusi:**
1. Pilih jam yang berbeda (tidak overlap)
2. Atau pilih tanggal yang berbeda

### Error: "Jam selesai harus lebih besar dari jam mulai"

**Penyebab:** Jam selesai <= Jam mulai

**Solusi:**
1. Ubah jam selesai menjadi lebih besar dari jam mulai

### Error: "ID tidak valid"

**Penyebab:** Parameter ID kosong di URL

**Solusi:**
1. Pastikan URL memiliki parameter ID yang valid
2. Contoh: `edit.php?id=123`

### Error: "Jadwal tidak ditemukan"

**Penyebab:** ID tidak ada di database

**Solusi:**
1. Refresh halaman
2. Coba jadwal lain

### Data tidak muncul di tabel

**Penyebab:**
1. Belum ada jadwal
2. Query error
3. Koneksi database gagal

**Solusi:**
1. Cek koneksi database
2. Cek error log
3. Tambah jadwal baru

---

## Catatan Pengembangan

### 1. Session Variable yang Digunakan

```php
$_SESSION['user_id']  // ID user yang login (untuk created_by)
```

### 2. Format Tanggal dan Waktu

- **Input HTML:** `type="date"` dan `type="time"`
- **Database:** `DATE` dan `TIME`
- **Tampilan:** `d-m-Y` untuk tanggal, `H:i` untuk jam

### 3. Status Jadwal

| Status | Keterangan |
|--------|------------|
| aktif | Jadwal masih berlaku/belum dilaksanakan |
| selesai | Jadwal sudah dilaksanakan |

### 4. Join dengan User

Untuk menampilkan nama pembuat jadwal:
```sql
SELECT j.*, u.nama_user as pembuat 
FROM jadwal_latihan j
LEFT JOIN user u ON j.created_by = u.id_user
```

---

## TODO / Pengembangan Lanjutan

- [ ] Tambah fitur export jadwal ke Kalender (ICS)
- [ ] Tambah notifikasi email ke anggota
- [ ] Tambah pagination jika jadwal > 100
- [ ] Tambah filter berdasarkan bulan/tahun
- [ ] Tambah statistik jumlah latihan per bulan
- [ ] Tambah fitur duplicate jadwal (copy jadwal ke tanggal lain)
- [ ] Tambah validasi maksimal latihan per hari
- [ ] Tambah integrasi dengan Google Calendar
- [ ] Tambah tampilan kalender (calendar view)
- [ ] Tambah fitur reminder/ pengingat

---

## Best Practices

1. **Selalu gunakan validasi di server** - Jangan hanya mengandalkan JavaScript
2. **Cek bentrok sebelum insert/update** - Mencegah data duplikat
3. **Gunakan prepared statements** - Untuk query yang lebih aman
4. **Logging** - Catat aktivitas admin/pembina
5. **Backup database** - Sebelum hapus data penting

