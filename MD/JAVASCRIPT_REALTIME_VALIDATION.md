# Dokumentasi JavaScript Realtime Validation

## 📋 Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Fitur Validasi](#fitur-validasi)
3. [Struktur Kode](#struktur-kode)
4. [API Endpoints](#api-endpoints)
5. [Contoh Penggunaan](#contoh-penggunaan)
6. [Screenshot](#screenshot)

---

## Pengenalan

JavaScript Realtime Validation adalah fitur yang memberikan feedback langsung kepada pengguna saat mengisi form di modul User. Validasi ini berjalan di sisi client (browser) untuk memberikan pengalaman pengguna yang lebih baik sebelum data dikirim ke server.

### Tujuan
- Memberikan feedback langsung kepada pengguna
- Mengurangi submit form yang tidak valid
- Meningkatkan user experience
- Mengurangi beban server dari validasi yang sebenarnya bisa dicegah di client

---

## Fitur Validasi

### 1. Username Validation
```
- Minimal 3 karakter
- Hanya boleh huruf (a-z, A-Z), angka (0-9), dan underscore (_)
- Unik (tidak boleh sama dengan user lain)
```

### 2. Nama Lengkap Validation
```
- Minimal 2 karakter
- Wajib diisi
```

### 3. Password Validation
```
- Minimal 3 karakter (opsional saat edit)
- Konfirmasi password harus cocok
```

### 4. Konfirmasi Password
```
- Validasi realtime saat mengetik
- Menampilkan error jika tidak cocok
```

---

## Struktur Kode

### File yang Terlibat
```
api/
├── check_username.php           # Cek username unik (untuk tambah)
└── check_username_edit.php      # Cek username unik (untuk edit, exclude current user)

modules/user/
├── tambah.php                   # Form tambah user dengan JS validation
└── edit.php                     # Form edit user dengan JS validation
```

### Alur Kerja Validasi

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│ User mengetik   │────▶│ Input Event      │────▶│ Validasi Format │
│ pada field      │     │ (input/blur)     │     │ (regex/length)  │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                         │
                        ┌────────────────────────────────┘
                        │
                        ▼
               ┌──────────────────┐     ┌──────────────────┐
               │ Tampilkan Error  │     │ AJAX Check       │
               │ (is-invalid)     │◀────│ (username uniqueness)
               └──────────────────┘     └────────┬─────────┘
                                                 │
                                                 ▼
                                        ┌──────────────────┐
                                        │ Update UI        │
                                        │ (clear/set error)│
                                        └──────────────────┘
```

---

## API Endpoints

### 1. check_username.php

**Endpoint:** `api/check_username.php`

**Method:** GET

**Parameter:**
| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| username | string | Ya | Username yang akan dicek |

**Response Success (200):**
```json
{
    "exists": true
}
```

**Response Error (400/500):**
```json
{
    "exists": false,
    "error": "Pesan error"
}
```

**Contoh Penggunaan:**
```javascript
fetch('../../api/check_username.php?username=' + encodeURIComponent(username))
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            showError(input, 'Username sudah digunakan!');
        } else {
            clearError(input);
        }
    });
```

### 2. check_username_edit.php

**Endpoint:** `api/check_username_edit.php`

**Method:** GET

**Parameter:**
| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| username | string | Ya | Username yang akan dicek |
| exclude_id | int | Ya | ID user yang dieksklusi (user sendiri) |

**Response:** Sama seperti check_username.php

**Contoh Penggunaan:**
```javascript
fetch('../../api/check_username_edit.php?username=' + encodeURIComponent(username) + '&exclude_id=' + userId)
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            showError(input, 'Username sudah digunakan!');
        } else {
            clearError(input);
        }
    });
```

---

## Contoh Penggunaan

### Validasi Input pada Form Tambah User

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const namaLengkapInput = document.getElementById('nama_lengkap');
    const passwordInput = document.getElementById('password');
    const konfirmasiPasswordInput = document.getElementById('konfirmasi_password');
    const form = document.querySelector('form');

    // Fungsi menampilkan error
    function showError(input, message) {
        const parent = input.parentElement.parentElement;
        let errorDiv = parent.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block';
            parent.appendChild(errorDiv);
        }
        input.classList.add('is-invalid');
        errorDiv.textContent = message;
        return false;
    }

    // Fungsi menghapus error
    function clearError(input) {
        const parent = input.parentElement.parentElement;
        const errorDiv = parent.querySelector('.invalid-feedback');
        input.classList.remove('is-invalid');
        if (errorDiv) {
            errorDiv.remove();
        }
        return true;
    }

    // Validasi username saat input
    usernameInput.addEventListener('input', function() {
        const username = this.value;
        if (username.length === 0) {
            return clearError(this);
        }
        if (username.length < 3) {
            return showError(this, 'Username minimal 3 karakter!');
        }
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            return showError(this, 'Username hanya boleh huruf, angka, dan underscore!');
        }
        return clearError(this);
    });

    // Validasi username uniqueness saat blur
    usernameInput.addEventListener('blur', function() {
        const username = this.value;
        if (username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username)) {
            fetch('../../api/check_username.php?username=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(this, 'Username sudah digunakan!');
                    } else {
                        clearError(this);
                    }
                });
        }
    });

    // Validasi nama lengkap saat input
    namaLengkapInput.addEventListener('input', function() {
        const nama = this.value;
        if (nama.length === 0) {
            return clearError(this);
        }
        if (nama.length < 2) {
            return showError(this, 'Nama lengkap minimal 2 karakter!');
        }
        return clearError(this);
    });

    // Validasi password saat input
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        if (password.length === 0) {
            return clearError(this);
        }
        if (password.length < 6) {
            return showError(this, 'Password minimal 6 karakter!');
        }
        return clearError(this);
    });

    // Validasi konfirmasi password saat input
    konfirmasiPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const konfirmasi = this.value;
        if (konfirmasi.length === 0) {
            return clearError(this);
        }
        if (password !== konfirmasi) {
            return showError(this, 'Konfirmasi password tidak cocok!');
        }
        return clearError(this);
    });

    // Validasi saat submit form
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = [usernameInput, namaLengkapInput, passwordInput, konfirmasiPasswordInput];
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                showError(field, 'Field ini wajib diisi!');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
```

---

## Screenshot

### 1. Validasi Username (Format Salah)
```
┌─────────────────────────────────────────────────────────┐
│ Username: [ab__]                                        │
│          ❌ Username minimal 3 karakter!                │
└─────────────────────────────────────────────────────────┘
```

### 2. Validasi Username (Karakter Tidak Valid)
```
┌─────────────────────────────────────────────────────────┐
│ Username: [user@123!]                                   │
│          ❌ Username hanya boleh huruf, angka, dan      │
│             underscore!                                 │
└─────────────────────────────────────────────────────────┘
```

### 3. Validasi Username (Sudah Ada)
```
┌─────────────────────────────────────────────────────────┐
│ Username: [adminn]                                      │
│          ❌ Username sudah digunakan!                   │
└─────────────────────────────────────────────────────────┘
```

### 4. Validasi Berhasil
```
┌─────────────────────────────────────────────────────────┐
│ Username: [john_doe]                                    │
│          ✅ (tidak ada error)                           │
└─────────────────────────────────────────────────────────┘
```

### 5. Validasi Password
```
┌─────────────────────────────────────────────────────────┐
│ Password: [12345]                                       │
│          ❌ Password minimal 6 karakter!                │
│                                                          │
│ Konfirmasi Password: [12345]                            │
│          ✅ Konfirmasi password cocok!                  │
└─────────────────────────────────────────────────────────┘
```

---

## Catatan Penting

1. **Validasi Client-side adalah Tambahan, Bukan Pengganti**
   - Validasi JavaScript bisa dimatikan oleh user
   - Selalu lakukan validasi di server-side juga
   - JavaScript validation untuk UX, bukan keamanan

2. **Feedback yang Jelas**
   - Gunakan warna yang konsisten (merah untuk error)
   - Tampilkan pesan yang spesifik dan helpful
   - Scroll ke error pertama saat submit

3. **Performa**
   - AJAX call dilakukan saat blur (bukan saat input) untuk username uniqueness
   - Ini mengurangi jumlah request ke server

4. **Kompatibilitas**
   - Menggunakan ES6 features (const, let, arrow functions)
   - Compatible dengan browser modern (Chrome, Firefox, Edge, Safari)
   - Bootstrap 5 sudah include di project

---

## Troubleshooting

### Problem: AJAX request gagal
**Kemungkinan penyebab:**
- API endpoint tidak accessible
- CORS issue
- Server database down

**Solusi:**
- Cek console browser untuk error details
- Pastikan file check_username.php dan check_username_edit.php ada di folder api
- Validasi server-side akan tetap bekerja sebagai fallback

### Problem: Error message tidak muncul
**Kemungkinan penyebab:**
- Struktur HTML form tidak sesuai dengan JavaScript
- Elemen parent tidak memiliki struktur yang benar

**Solusi:**
- Pastikan input group menggunakan struktur:
  ```html
  <div class="input-group">
      <span class="input-group-text">...</span>
      <input type="text" class="form-control" id="username">
      <!-- Error div akan ditambahkan di sini -->
  </div>
  ```

---

## Referensi

- [Bootstrap 5 Forms Documentation](https://getbootstrap.com/docs/5.3/forms/)
- [MDN: Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [MDN: Event Reference](https://developer.mozilla.org/en-US/docs/Web/Events)

---

*Dokumentasi ini dibuat untuk project Hadrahin*
*Last updated: Januari 2026*

