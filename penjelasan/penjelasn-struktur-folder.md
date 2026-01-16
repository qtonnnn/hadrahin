
Aku jelaskan **struktur + fungsi tiap folder**, 

---

# STRUKTUR FOLDER PHP NATIVE

*(untuk Sistem Manajemen Tim Hadrah)*

```
hadrah-app/
│
├── config/
│   ├── database.php
│   ├── config.php
|   ├── hadrah.sql
│
├── core/
│   ├── auth.php
│   ├── middleware.php
│   ├── helper.php
|
|
├── dashboard/
|   ├── admin.php
|   ├── pembina.php
|   ├── anggota.php
│
├── modules/
│   ├── user/
│   │   ├── index.php
│   │   ├── tambah.php
│   │   ├── edit.php
│   │   ├── proses.php
│   │
│   ├── jadwal_latihan/
│   │   ├── index.php
│   │   ├── tambah.php
│   │   ├── edit.php
│   │   ├── detail.php
│   │   ├── proses.php
│   │
│   ├── absen_latihan/
│   │   ├── index.php
│   │   ├── input.php
│   │   ├── proses.php
│   │
│   ├── alat/
│   │   ├── index.php
│   │   ├── tambah.php
│   │   ├── edit.php
│   │   ├── proses.php
│   │
│   ├── booking_acara/
│   │   ├── index.php
│   │   ├── tambah.php
│   │   ├── edit.php
│   │   ├── detail.php
│   │   ├── proses.php
│   │
│   ├── history/
│   │   ├── history_latihan.php
│   │   ├── history_acara.php
│   │   ├── detail_acara.php
│   │
│   ├── dokumentasi/
│   │   ├── upload.php
│   │   ├── index.php
│   │   ├── hapus.php
│
├── uploads/
│   └── acara/
│       └── 2026/
│           └── 01/
│               ├── acara_001.jpg
│               └── acara_002.jpg
│
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
│
├── templates/
│   ├── header.php
│   ├── sidebar.php
│   ├── footer.php
│
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── proses_login.php
│
├── index.php
└── .htaccess
```

---

## PENJELASAN TIAP BAGIAN (INI PENTING)

### 1️⃣ `config/`

**Otak konfigurasi**

* `database.php`
  koneksi MySQL (host, user, password, db)
* `config.php`
  setting global (nama app, timezone, base_url)

➡️ Jangan taruh koneksi DB di tiap file. Itu dosa klasik.

---

### 2️⃣ `core/`

**Logika inti yang dipakai di mana-mana**

* `auth.php`
  cek login, session
* `middleware.php`
  cek role: admin / pembina / anggota
* `helper.php`
  fungsi kecil (format tanggal, alert, dll)

Ini bikin kode kamu **tidak copy-paste neraka**.

---

### 3️⃣ `modules/`

**Jantung aplikasi**
Satu folder = satu tabel / fitur utama

Contoh:

* `modules/user/` → tabel `user`
* `modules/jadwal_latihan/` → tabel `jadwal_latihan`
* `modules/absen_latihan/` → tabel `absen_latihan`
* `modules/alat/` → tabel `alat`
* `modules/booking_acara/` → tabel `booking_acara`

Pola file:

* `index.php` → list data
* `tambah.php` → form tambah
* `edit.php` → form edit
* `detail.php` → detail (kalau perlu)
* `proses.php` → INSERT / UPDATE / DELETE

Ini **CRUD rapi**, bukan CRUD brutal.

---

### 4️⃣ `modules/history/`

**Read-only zone**

* tidak ada tambah/edit manual
* hanya tampilan laporan
* data masuk otomatis dari proses selesai

Ini penting secara konsep. History ≠ CRUD.

---

### 5️⃣ `modules/dokumentasi/`

**Upload & kelola foto acara**

* `upload.php` → upload gambar
* `index.php` → list galeri per acara
* `hapus.php` → hapus foto

Relasi ke `history_acara`, bukan `booking_acara`.

---

### 6️⃣ `uploads/`

**Tempat file fisik**

* jangan taruh PHP di sini
* hanya gambar / file
* struktur berdasarkan tahun/bulan



---

### 7️⃣ `templates/`

**Layout konsisten**

* `header.php`
* `sidebar.php`
* `footer.php`

Semua halaman include ini.


---

### 8️⃣ `auth/`

**Login system**

* login
* logout
* proses login

Pisah dari `modules/user` supaya:

* keamanan lebih jelas
* tidak tercampur CRUD user

---

### 9️⃣ `index.php`

**animasi-pembuka-web**

---

### 🔟 `.htaccess`

* proteksi folder
* redirect
* disable akses langsung ke file sensitif

---

