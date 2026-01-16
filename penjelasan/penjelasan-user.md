# Modul User Management

Dokumentasi lengkap untuk modul pengelolaan user di aplikasi Hadrahin.

## Lokasi Folder

```
modules/user/
├── index.php      # Daftar user
├── tambah.php     # Form tambah user + proses insert
├── edit.php       # Form edit user + proses update
├── hapus.php      # Proses hapus user
└── proses.php     # Proses AJAX (opsional)
```

## Struktur Tabel user

```sql
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    peran ENUM('admin', 'pembina', 'anggota') NOT NULL DEFAULT 'anggota',
    status_aktif ENUM('Y', 'N') NOT NULL DEFAULT 'Y',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Aturan Akses

| Peran | Akses |
|-------|-------|
| Admin | ✅ Full access (view, add, edit, delete) |
| Pembina | ❌ Tidak boleh akses |
| Anggota | ❌ Tidak boleh akses |

Semua file menggunakan:
```php
cekLogin();           // Wajib login
cekRole(['admin']);   // Hanya admin
```

## Fitur Each File

### 1. index.php (Daftar User)

**Fungsi:** Menampilkan semua user dalam bentuk tabel

**Kolom yang ditampilkan:**
- No (urutan)
- Username
- Nama Lengkap
- Peran (Admin/Pembina/Anggota)
- Status (Aktif/Tidak Aktif)
- Aksi (Edit/Hapus)

**Fitur:**
- Jika user adalah admin sendiri, tidak bisa menghapus dirinya sendiri
- Tampilkan alert jika login berhasil

**Query yang digunakan:**
```php
SELECT * FROM user ORDER BY id_user DESC
```

---

### 2. tambah.php (Tambah User)

**Fungsi:** Form untuk menambahkan user baru + proses simpan

**Field input:**
1. Username (*) - wajib diisi, unique
2. Nama Lengkap (*) - wajib diisi
3. Password (*) - wajib diisi, min 3 karakter
4. Peran (*) - dropdown (Admin/Pembina/Anggota)

**Validasi:**
- Semua field wajib diisi
- Username harus unique (cek di database)
- Password minimal 3 karakter

**Proses Insert:**
```php
$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
$peran = $_POST['peran'];

$sql = "INSERT INTO user (username, password, nama_lengkap, peran) 
        VALUES ('$username', '$password', '$nama_lengkap', '$peran')";
```

**Redirect:**
- Jika berhasil: `Location: index.php?msg=User+berhasil+ditambahkan`
- Jika error: Tampilkan pesan error

---

### 3. edit.php (Edit User)

**Fungsi:** Form untuk mengedit data user + proses update

**URL:** `edit.php?id=123`

**Field input:**
1. Username (*) - wajib diisi, unique (exclude id sendiri)
2. Nama Lengkap (*) - wajib diisi
3. Password - opsional (kosong = tidak diubah)
4. Peran (*) - dropdown
5. Status Aktif - checkbox

**Ambil data berdasarkan ID:**
```php
$id = (int)$_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM user WHERE id_user = $id");
$user = mysqli_fetch_assoc($query);
```

**Validasi:**
- Username unique, exclude user yang sedang diedit
- Jika password diisi, minimal 3 karakter

**Proses Update:**
```php
// Update basic data
$sql = "UPDATE user SET 
        username = '$username',
        nama_lengkap = '$nama_lengkap',
        peran = '$peran',
        status_aktif = '$status' 
        WHERE id_user = $id";

// Jika password diisi, update juga password
if (!empty($_POST['password'])) {
    $sql = "UPDATE user SET password = '$password' WHERE id_user = $id";
}
```

**Redirect:**
- Jika berhasil: `Location: index.php?msg=User+berhasil+diupdate`

---

### 4. hapus.php (Hapus User)

**Fungsi:** Menghapus user berdasarkan ID

**URL:** `hapus.php?id=123`

**Proteksi:**
- Admin tidak bisa menghapus dirinya sendiri
- Konfirmasi sebelum menghapus (via JavaScript confirm)

**Proses Delete:**
```php
$id = (int)$_GET['id'];

// Self-protection: admin tidak bisa hapus dirinya sendiri
if ($id == $_SESSION['user_id']) {
    header('Location: index.php?msg=Anda+tidak+bisa+menghapus+diri+sendiri');
    exit;
}

$sql = "DELETE FROM user WHERE id_user = $id";
```

**Redirect:**
- Jika berhasil: `Location: index.php?msg=User+berhasil+dihapus`
- Jika gagal: `Location: index.php?msg=Gagal+menghapus+user`

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
cekLogin();      // Apakah user sudah login?
cekRole(['admin']); // Apakah user adalah admin?
```

### 4. Password Security

Password disimpan dalam format hash:
```php
password_hash($password, PASSWORD_DEFAULT);
```

Verifikasi saat login:
```php
password_verify($password, $hashed_password);
```

---

## Validasi Form

### Tambah User

| Field | Aturan |
|-------|--------|
| Username | Wajib, unique, 3-50 karakter |
| Nama Lengkap | Wajib, 2-100 karakter |
| Password | Wajib, minimal 3 karakter |
| Peran | Wajib, salah satu: admin/pembina/anggota |

### Edit User

| Field | Aturan |
|-------|--------|
| Username | Wajib, unique (exclude id sendiri) |
| Nama Lengkap | Wajib |
| Password | Opsional, jika diisi minimal 3 karakter |
| Peran | Wajib |
| Status | Wajib |

---

## Pesan Notifikasi

| Pesan | Keterangan |
|-------|------------|
| User berhasil ditambahkan | Insert berhasil |
| User berhasil diupdate | Update berhasil |
| User berhasil dihapus | Delete berhasil |
| Username sudah digunakan | Unique constraint violation |
| Password salah | Login gagal |
| Username tidak ditemukan | Login gagal |
| Anda tidak bisa menghapus diri sendiri | Self-delete protection |

---

## Cara Penggunaan

### 1. Akses Modul User

1. Login sebagai admin
2. Klik "Kelola User" di dashboard admin
3. Atau langsung akses: `http://localhost/hadrahin/modules/user/index.php`

### 2. Menambah User Baru

1. Klik tombol "[+] Tambah User"
2. Isi data user (username, nama lengkap, password, peran)
3. Klik "Simpan"
4. User baru akan muncul di tabel

### 3. Mengedit User

1. Klik tombol "Edit" pada user yang ingin diubah
2. Ubah data yang diperlukan
3. Klik "Simpan"
4. Perubahan akan langsung生效

### 4. Menghapus User

1. Klik tombol "Hapus" pada user yang ingin dihapus
2. Konfirmasi pada popup
3. User akan dihapus dari sistem

---

## Troubleshooting

### Error: "Username sudah digunakan"
**Penyebab:** Username sudah ada di database
**Solusi:** Gunakan username yang berbeda

### Error: "Anda tidak bisa menghapus diri sendiri"
**Penyebab:** Admin mencoba menghapus akunnya sendiri
**Solusi:** Login dengan akun admin lain untuk menghapus

### Error: "SQLSTATE[23000]"
**Penyebab:** Unique constraint violation (username duplicate)
**Solusi:** Gunakan username yang belum ada

### Halaman kosong setelah submit
**Penyebab:** Error pada query atau koneksi
**Solusi:** Cek error log atau aktifkan display_errors

---

## Catatan Pengembangan

1. **Password Hashing:** Menggunakan `password_hash()` dan `password_verify()` - Jangan ubah ke plain text

2. **Session Variable:** 
   - `$_SESSION['user_id']` - ID user yang login
   - `$_SESSION['nama']` - Nama lengkap user
   - `$_SESSION['role']` - Peran user (admin/pembina/anggota)
   - `$_SESSION['login']` - Flag login berhasil

3. **Validasi di Client-side:** Bisa ditambahkan JavaScript untuk validasi realtime

4. **Pagination:** Untuk jumlah user > 100, tambahkan pagination

5. **Export/Import:** Jika dibutuhkan, bisa ditambahkan fitur export ke Excel/CSV

---

## TODO / Pengembangan Lanjutan

- [ ] Tambah pagination jika user > 100
- [ ] Tambah fitur search/filter
- [ ] Tambah export ke Excel
- [ ] Tambah log aktivitas user
- [ ] Tambah limit login attempt
- [ ] Tambah forgot password
- [ ] Tambah profile user

