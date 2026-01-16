# Penjelasan File .htaccess untuk Aplikasi Web Hadrahin

## Apa itu .htaccess?

`.htaccess` (Hypertext Access) adalah file konfigurasi yang digunakan oleh server web Apache untuk mengatur perilaku website pada tingkat direktori. File ini memungkinkan Anda untuk:
- Mengatur akses dan keamanan
- Membuat URL yang lebih bersih (clean URLs)
- Mengaktifkan fitur kompresi
- Mengatur cache browser
- Mengalihkan halaman (redirect)
- Dan banyak lagi

---

## Struktur dan Penjelasan Setiap Bagian

### 1. Aktifkan Rewrite Engine

```apache
RewriteEngine On
RewriteBase /hadrahin/
```

**Penjelasan:**
- `RewriteEngine On`: Mengaktifkan modul rewrite untuk manipulasi URL
- `RewriteBase /hadrahin/`: Menentukan base URL untuk rewrite rules. Ubah `/hadrahin/` sesuai dengan direktori instalasi Anda

### 2. URL Routing Rules

```apache
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [L,R=301]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [L,QSA]
```

**Penjelasan:**
- **Baris 1-2**: Menghapus trailing slash dari URL untuk menghindari duplicate content (contoh: `about/` → `about`)
- **Baris 4-5**: Jika file atau direktori tidak ditemukan, URL akan diteruskan ke `index.php` dengan parameter `url`
  - `%{REQUEST_FILENAME}`: Path file yang diminta
  - `!-d`: Jika BUKAN direktori
  - `!-f`: Jika BUKAN file
  - `[L]`: Last rule (berhenti memproses rules lainnya)
  - `[R=301]`: Redirect permanent (301 = permanent redirect)
  - `[QSA]`: Query String Append (menambahkan query string yang ada)

**Contoh:**
- URL: `dashboard/admin` → `index.php?url=dashboard/admin`
- URL: `modules/user/edit.php?id=5` → Langsung diakses (karena file ada)

### 3. Keamanan - Block Akses File Sensitif

#### Block File Dot (.)
```apache
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

**Penjelasan:**
- Mencegah akses ke semua file yang dimulai dengan titik (contoh: `.htaccess`, `.env`, `.gitignore`)
- `Order allow,deny`: Urutan evaluasi
- `Deny from all`: Tolak semua akses

#### Block File Konfigurasi
```apache
<FilesMatch "\.(env|sql|ini|log|md|txt|yml|yaml|json|xml)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

**Penjelasan:**
- Mencegah akses langsung ke file konfigurasi dan sensitif
- Ekstensi yang diblokir:
  - `.env`: File environment variables
  - `.sql`: File dump database
  - `.ini`: File konfigurasi
  - `.log`: File log
  - `.md`: File markdown
  - `.json`: File JSON
  - `.xml`: File XML

### 4. Security Headers

```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self';"
```

**Penjelasan Setiap Header:**

| Header | Fungsi |
|--------|--------|
| `X-Frame-Options` | Mencegah website ditanam dalam iframe (clickjacking protection) |
| `X-XSS-Protection` | Mengaktifkan filter XSS browser |
| `X-Content-Type-Options` | Mencegah browser menebak tipe file (MIME sniffing) |
| `Referrer-Policy` | Mengatur bagaimana referrer dikirim |
| `Content-Security-Policy` | Kebijakan keamanan untuk sumber daya |

**Penjelasan CSP:**
- `default-src 'self'`: Default hanya dari domain sendiri
- `script-src 'self' 'unsafe-inline' 'unsafe-eval'`: Izinkan script inline dan eval
- `style-src 'self' 'unsafe-inline'`: Izinkan CSS inline
- `img-src 'self' data:`: Izinkan gambar dari domain sendiri dan data URI
- `font-src 'self' data:`: Izinkan font dari domain sendiri dan data URI
- `connect-src 'self'`: Izinkan koneksi AJAX ke domain sendiri

### 5. Pengaturan PHP

```apache
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 128M
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

**Penjelasan:**

| Directive | Nilai | Fungsi |
|-----------|-------|--------|
| `max_execution_time` | 300 detik | Maksimal waktu eksekusi script |
| `max_input_time` | 300 detik | Maksimal waktu untuk parsing input |
| `memory_limit` | 128M | Maksimal memory yang bisa digunakan |
| `upload_max_filesize` | 10M | Maksimal ukuran file upload |
| `post_max_size` | 10M | Maksimal ukuran data POST |

### 6. Cache Control Headers

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 month"
    
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
    ExpiresByType text/javascript "access plus 1 week"
    
    ExpiresByType audio/mpeg "access plus 1 month"
    ExpiresByType video/mp4 "access plus 1 month"
    ExpiresByType video/ogg "access plus 1 month"
    
    ExpiresByType application/pdf "access plus 1 day"
    ExpiresByType text/plain "access plus 1 day"
</IfModule>
```

**Penjelasan:**
- `mod_expires`: Modul Apache untuk mengatur cache browser
- `ExpiresActive On`: Mengaktifkan expires headers
- `ExpiresByType`: Mengatur expiry time berdasarkan tipe MIME

**Durasi Cache:**
- Gambar: 1 bulan
- CSS/JavaScript: 1 minggu
- Audio/Video: 1 bulan
- File lain: 1 hari

### 7. Gzip Compression

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
    AddOutputFilterByType DEFLATE application/x-font
    AddOutputFilterByType DEFLATE application/x-font-opentype
    AddOutputFilterByType DEFLATE application/x-font-otf
    AddOutputFilterByType DEFLATE application/x-font-truetype
    AddOutputFilterByType DEFLATE application/x-font-ttf
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE font/opentype
    AddOutputFilterByType DEFLATE font/otf
    AddOutputFilterByType DEFLATE font/ttf
    AddOutputFilterByType DEFLATE image/svg+xml
    AddOutputFilterByType DEFLATE image/x-icon
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/xml
</IfModule>
```

**Penjelasan:**
- `mod_deflate`: Modul Apache untuk kompresi
- `AddOutputFilterByType`: Menambahkan filter kompresi untuk tipe MIME tertentu
- **Manfaat**: Mengurangi ukuran file hingga 70%, mempercepat loading website

### 8. Custom Error Pages

```apache
ErrorDocument 400 /error.php?code=400
ErrorDocument 401 /error.php?code=401
ErrorDocument 403 /error.php?code=403
ErrorDocument 404 /error.php?code=404
ErrorDocument 500 /error.php?code=500
```

**Penjelasan:**
- Menampilkan halaman error kustom daripada default Apache
- Kode Error:
  - 400: Bad Request
  - 401: Unauthorized
  - 403: Forbidden
  - 404: Not Found
  - 500: Internal Server Error

### 9. Disable Directory Browsing

```apache
Options -Indexes
```

**Penjelasan:**
- Mencegah pengunjung melihat isi direktori jika tidak ada file index
- Penting untuk keamanan (tidak menampilkan struktur file)

### 10. Hide Server Signature

```apache
ServerSignature Off
```

**Penjelasan:**
- Menyembunyikan informasi server dari error pages
- Mengurangi informasi yang bisa digunakan attacker

---

## Cara Penggunaan

### 1. Untuk Local Development (XAMPP/WAMP)

Jika aplikasi diakses melalui:
```
http://localhost/hadrahin/
```

Biarkan `RewriteBase /hadrahin/` seperti adanya.

### 2. Untuk Production (Shared Hosting)

Jika di root domain:
```
https://domain-anda.com/
```

Ubah menjadi:
```apache
RewriteBase /
```

### 3. Untuk Subdomain

Jika di subdomain:
```
https://app.domain-anda.com/
```

Ubah menjadi:
```apache
RewriteBase /
```

---

## Troubleshooting

### Jika 500 Internal Server Error

1. Pastikan `mod_rewrite` aktif di Apache
2. Periksa syntax `.htaccess` dengan `apachectl configtest`
3. Cek file `error.log` Apache untuk detail error

### Cara Aktifkan mod_rewrite (XAMPP)

1. Buka file `httpd.conf`
2. Cari dan hilangkan comment dari:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Cari `<Directory "C:/xampp/htdocs">`
4. Pastikan `AllowOverride All` ada di dalamnya
5. Restart Apache

### Jika Gzip Tidak Berfungsi

1. Pastikan `mod_deflate` aktif
2. Aktifkan dengan command:
   ```bash
   a2enmod deflate
   ```

---

## Cara Testing

### 1. Test URL Routing

Buka URL:
```
http://localhost/hadrahin/dashboard/admin
```

Seharusnya menampilkan halaman admin (jika login).

### 2. Test Security Headers

Buka Developer Tools → Network → Headers, pastikan semua security headers muncul.

### 3. Test Gzip Compression

Buka https://developers.google.com/speed/pagespeed/insights/ atau https://gtmetrix.com/

### 4. Test Cache Headers

Buka Developer Tools → Network, cek response headers untuk file gambar/CSS/JS.

---

## Penyesuaian untuk Aplikasi Anda

### Jika Menggunakan CDN

Ubah CSP header untuk mengizinkan CDN:
```apache
Header always set Content-Security-Policy "default-src 'self' https://cdn.domain.com; script-src 'self' https://cdn.domain.com; style-src 'self' https://cdn.domain.com; img-src 'self' https://cdn.domain.com data:;"
```

### Jika Menggunakan Google Fonts

```apache
Header always set Content-Security-Policy "font-src 'self' https://fonts.gstatic.com;"
```

### Jika Memerlukan AJAX ke API Eksternal

```apache
Header always set Content-Security-Policy "connect-src 'self' https://api.domain lain.com;"
```

---

## Referensi

- [Apache mod_rewrite Documentation](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html)
- [MDN Security Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers)
- [mod_deflate Documentation](https://httpd.apache.org/docs/2.4/mod/mod_deflate.html)
- [mod_expires Documentation](https://httpd.apache.org/docs/2.4/mod/mod_expires.html)

---

## Kesimpulan

File `.htaccess` ini menyediakan:
- ✅ Keamanan yang kuat
- ✅ Performa optimal dengan cache dan gzip
- ✅ URL yang bersih dan SEO-friendly
- ✅ Konfigurasi PHP yang sesuai
- ✅ Error handling yang baik

Sesuaikan sesuai kebutuhan spesifik aplikasi Anda!

