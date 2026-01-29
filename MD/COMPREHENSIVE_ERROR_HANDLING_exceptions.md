# Comprehensive Error Handling System - Documentation

## Overview

Sistem ini mengimplementasikan error handling yang komprehensif dengan menggunakan custom exceptions untuk menangani berbagai jenis error secara konsisten di seluruh aplikasi Hadrahin.

## Arsitektur

```
┌─────────────────────────────────────────────────────────────┐
│                    ErrorHandler                              │
│  - handleError()      : PHP errors (warnings, notices)      │
│  - handleException()  : Uncaught exceptions                 │
│  - handleShutdown()   : Fatal errors                        │
│  - log()              : Structured logging                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              HadrahinExceptionInterface                      │
│  - getUserMessage()   : Pesan untuk user                    │
│  - getLogMessage()    : Pesan untuk log                     │
│  - getHttpCode()      : HTTP status code                    │
└─────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ ValidationError │ │  Auth Errors    │ │  DB Errors      │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```

---

## Custom Exception Classes

### 1. ValidationException

**HTTP Code:** 400 Bad Request

**Fungsi:** Untuk error validasi input dari user.

```php
throw new ValidationException(
    $message = 'Validasi gagal',           // Untuk log
    $userMessage = 'Input tidak valid'     // Untuk user
);
```

**Kegunaan:**
- Field wajib kosong
- Format email salah
- Panjang karakter tidak sesuai
- Format tanggal tidak valid

---

### 2. DatabaseException

**HTTP Code:** 500 Internal Server Error

**Fungsi:** Untuk error yang berkaitan dengan database.

```php
throw new DatabaseException(
    $message = 'Error database',           // Untuk log
    $userMessage = 'Terjadi kesalahan database'  // Untuk user
);
```

**Kegunaan:**
- Koneksi database gagal
- Query error
- Constraint violation
- Transaction failure

---

### 3. AuthenticationException

**HTTP Code:** 401 Unauthorized

**Fungsi:** Untuk error autentikasi (login).

```php
throw new AuthenticationException(
    $message = 'Autentikasi gagal',        // Untuk log
    $userMessage = 'Username atau password salah'  // Untuk user
);
```

**Kegunaan:**
- Username tidak ditemukan
- Password salah
- Token tidak valid
- Session expired

---

### 4. AuthorizationException

**HTTP Code:** 403 Forbidden

**Fungsi:** Untuk error otorisasi (akses).

```php
throw new AuthorizationException(
    $message = 'Akses ditolak',            // Untuk log
    $userMessage = 'Anda tidak memiliki akses'  // Untuk user
);
```

**Kegunaan:**
- User tidak memiliki role yang cukup
- Akses ke resource orang lain
- Akun non-aktif
- Feature hanya untuk role tertentu

---

### 5. NotFoundException

**HTTP Code:** 404 Not Found

**Fungsi:** Untuk resource yang tidak ditemukan.

```php
throw new NotFoundException(
    $resource = 'Jadwal Latihan',          // Jenis resource
    $message = null,                       // Auto-generated
    $userMessage = 'Jadwal tidak ditemukan' // Untuk user
);
```

**Kegunaan:**
- ID tidak valid
- Data tidak ditemukan di database
- Halaman tidak ada
- File tidak ditemukan

---

### 6. ConflictException

**HTTP Code:** 409 Conflict

**Fungsi:** Untuk konflik data (duplikat).

```php
throw new ConflictException(
    $message = 'Data duplikat',            // Untuk log
    $userMessage = 'Data sudah ada'        // Untuk user
);
```

**Kegunaan:**
- Duplicate entry
- Data sudah ada sebelumnya
- Schedule conflict
- Username sudah digunakan

---

### 7. RateLimitException

**HTTP Code:** 429 Too Many Requests

**Fungsi:** Untuk membatasi jumlah request.

```php
throw new RateLimitException(
    $message = 'Terlalu banyak percobaan', // Untuk log
    $userMessage = 'Silakan tunggu sebentar'  // Untuk user
);
```

**Kegunaan:**
- Login attempt exceeded
- API rate limiting
- Form submission spam
- Brute force protection

---

## Helper Functions

### validate_required()

Memvalidasi bahwa field-field wajib diisi.

```php
/**
 * @param array $data     Data yang akan divalidasi
 * @param array $fields   Nama field yang wajib ada
 * @param array $labels   Label nama field untuk pesan error
 */
validate_required($_POST, ['tanggal', 'jam_mulai', 'lokasi'], [
    'tanggal' => 'Tanggal latihan',
    'jam_mulai' => 'Jam mulai',
    'lokasi' => 'Lokasi latihan'
]);
```

**Contoh Output Error:**
```
ValidationException: Field 'Tanggal latihan' wajib diisi
HTTP 400: Tanggal latihan wajib diisi
```

---

### validate_email()

Memvalidasi format email.

```php
/**
 * @param string $email Email yang akan divalidasi
 * @throws ValidationException Jika format email tidak valid
 */
validate_email($email);
```

---

### require_permission()

Memerlukan permission tertentu untuk akses fitur.

```php
/**
 * @param string $requiredRole Role yang diperlukan ('admin', 'pembina', 'anggota')
 * @throws AuthenticationException Jika belum login
 * @throws AuthorizationException Jika role tidak cukup
 */
require_permission('admin');      // Hanya admin
require_permission('pembina');    // Admin & pembina
require_permission('anggota');    // Semua yang login
```

**Hierarchy:** `anggota` < `pembina` < `admin`

---

### execute_transaction()

Menjalankan fungsi dalam database transaction.

```php
/**
 * @param PDO $pdo       Connection PDO
 * @param callable $callback Fungsi yang akan dieksekusi
 * @return mixed         Hasil dari callback
 * @throws DatabaseException Jika terjadi error
 */
$result = execute_transaction($pdo, function() use ($pdo, $data) {
    // Insert data
    $stmt = $pdo->prepare("INSERT INTO ... ");
    $stmt->execute($data);
    
    // Update related data
    $stmt = $pdo->prepare("UPDATE ... ");
    $stmt->execute([...]);
    
    return $lastInsertId;
});
```

**Fitur:**
- Automatic `beginTransaction()`
- Automatic `commit()` jika sukses
- Automatic `rollback()` jika error
- Wrap dalam DatabaseException

---

## ErrorHandler Class

### Metode Utama

#### handleError()

Menangani PHP errors (warnings, notices, dll).

```php
set_error_handler([ErrorHandler::class, 'handleError']);
```

**Yang ditangani:**
- E_ERROR, E_WARNING, E_PARSE
- E_NOTICE, E_CORE_ERROR
- E_USER_ERROR, E_USER_WARNING
- E_DEPRECATED

#### handleException()

Menangani uncaught exceptions.

```php
set_exception_handler([ErrorHandler::class, 'handleException']);
```

**Proses:**
1. Tentukan tipe exception
2. Ambil user message dan HTTP code
3. Log error dengan context
4. Set HTTP response code
5. Output JSON (untuk API) atau HTML (untuk web)

#### handleShutdown()

Menangani fatal errors.

```php
register_shutdown_function([ErrorHandler::class, 'handleShutdown']);
```

**Yang ditangani:**
- E_ERROR
- E_PARSE
- E_CORE_ERROR
- E_COMPILE_ERROR

---

## Contoh Penggunaan Lengkap

### Di File CRUD (modules/jadwallatihan/tambah.php)

```php
<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
require_once '../../includes/cache.php';
require_once '../../includes/error_handler.php';

// Check permission
require_permission('pembina');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        validate_required($_POST, ['tanggal', 'jam_mulai', 'lokasi'], [
            'tanggal' => 'Tanggal latihan',
            'jam_mulai' => 'Jam mulai',
            'lokasi' => 'Lokasi latihan'
        ]);
        
        $tanggal = $_POST['tanggal'];
        $lokasi = trim($_POST['lokasi']);
        
        // Validasi business logic
        if (strlen($lokasi) < 3) {
            throw new ValidationException(
                "Lokasi minimal 3 karakter",
                "Lokasi latihan minimal 3 karakter"
            );
        }
        
        // Cek duplikasi
        $stmt = $pdo->prepare("SELECT id_jadwal FROM jadwal_latihan WHERE tanggal = ? AND jam_mulai = ?");
        $stmt->execute([$tanggal, $_POST['jam_mulai']]);
        if ($stmt->fetch()) {
            throw new ConflictException(
                "Jadwal duplikat",
                "Jadwal pada tanggal dan jam tersebut sudah ada"
            );
        }
        
        // Insert dengan transaction
        execute_transaction($pdo, function() use ($pdo, $_POST, $user_id) {
            $stmt = $pdo->prepare("INSERT INTO jadwal_latihan ...");
            $stmt->execute([...]);
        });
        
        // Clear cache
        Cache::delete('jadwal_stats');
        
        header('Location: index.php?msg=success');
        exit;
        
    } catch (ValidationException $e) {
        ErrorHandler::handleException($e);
        exit;
    } catch (ConflictException $e) {
        ErrorHandler::handleException($e);
        exit;
    } catch (DatabaseException $e) {
        ErrorHandler::handleException($e);
        exit;
    } catch (Exception $e) {
        ErrorHandler::handleException($e);
        exit;
    }
}
```

---

## Logging

### Format Log

```
[2026-01-21 10:30:45] [ERROR] [ValidationException] Field 'Tanggal latihan' wajib diisi | User: 1
[2026-01-21 10:30:46] [AUTH] Password salah untuk user: admin | IP: 192.168.1.100
[2026-01-21 10:30:47] [DATABASE] Duplicate entry | Trace: #0 /var/www/...
[2026-01-21 10:30:48] [FATAL] Undefined variable in /var/www/hadrahin/... on line 42
```

### Level Log

| Level | Keterangan |
|-------|------------|
| INFO | Informasi umum |
| ERROR | Error yang ditangani |
| FATAL | Fatal error, shutdown |
| VALIDATION | Validation error |
| AUTH | Authentication event |
| AUTHZ | Authorization event |
| DATABASE | Database error |
| NOT_FOUND | Resource not found |
| CONFLICT | Conflict/dup error |
| RATE_LIMIT | Rate limit exceeded |

---

## Response Format

### API Response (JSON)

```json
{
  "success": false,
  "error": {
    "code": 400,
    "message": "Field 'Tanggal latihan' wajib diisi",
    "type": "ValidationException"
  }
}
```

### Debug Mode Response (with DEBUG_MODE = true)

```json
{
  "success": false,
  "error": {
    "code": 400,
    "message": "Field 'Tanggal latihan' wajib diisi",
    "type": "ValidationException",
    "details": {
      "file": "/var/www/hadrahin/modules/jadwallatihan/tambah.php",
      "line": 45,
      "trace": [
        "#0 {main}",
        "#1 /var/www/hadrahin/includes/error_handler.php on line 89"
      ]
    }
  }
}
```

---

## Konfigurasi

### Di config atau awal file

```php
// Enable/disable error handling
define('ERROR_HANDLING_ENABLED', true);

// Debug mode - TRUE untuk development, FALSE untuk production
define('DEBUG_MODE', true);
```

### Logging Configuration

Error handler secara otomatis akan:
- Membuat direktori `logs/` jika belum ada
- Menulis ke file `logs/php_errors.log`
- Flush buffer setelah error tinggi (ERROR, FATAL, CRITICAL)

---

## Best Practices

### 1. Selalu Gunakan Try-Catch

```php
// ❌ Salah
if ($data invalid) {
    die("Error");
}

// ✅ Benar
if ($data invalid) {
    throw new ValidationException(...);
}
```

### 2. Pesan yang Tepat

```php
// ❌ Kurang informatif
throw new Exception("Error");

// ✅ Baik
throw new ValidationException(
    "Email tidak valid: " . $email,
    "Format email tidak valid. Contoh: user@email.com"
);
```

### 3. Gunakan Helper Functions

```php
// ❌ Manual validation
if (empty($tanggal)) {
    throw new Exception("Tanggal wajib diisi");
}

// ✅ Menggunakan helper
validate_required($_POST, ['tanggal'], ['Tanggal' => 'Tanggal']);
```

### 4. Logging yang Bermakna

```php
// ❌ Terlalu sedikit info
ErrorHandler::log("Error occurred");

// ✅ Dengan context
ErrorHandler::log("Failed to update jadwal: {$e->getMessage()}", 'ERROR');
```

---

## Perbandingan: Sebelum vs Sesudah

### Sebelum

```php
// Multiple if-else, error handling tidak konsisten
if (empty($username)) {
    $errors[] = "Username wajib diisi";
} else if (strlen($username) < 3) {
    $errors[] = "Username minimal 3 karakter";
}
// ...
if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("INSERT...");
        $stmt->execute([...]);
    } catch (PDOException $e) {
        $errors[] = "Gagal menyimpan: " . $e->getMessage();
    }
}
if (!empty($errors)) {
    // Tampilkan error
}
```

### Sesudah

```php
// Clean, consistent, dengan proper exceptions
validate_required($_POST, ['username'], ['username' => 'Username']);

if (strlen($_POST['username']) < 3) {
    throw new ValidationException("Username minimal 3 karakter", "Username minimal 3 karakter");
}

execute_transaction($pdo, function() use ($pdo, $_POST) {
    $stmt = $pdo->prepare("INSERT...");
    $stmt->execute([...]);
});
```

---

## Kesimpulan

Sistem comprehensive error handling ini memberikan:

1. **Konsistensi** - Semua error ditangani dengan cara yang sama
2. **Keamanan** - Pesan error tidak mengekspos detail sensitif
3. **Maintainability** - Mudah menambah tipe error baru
4. **Debugging** - Informasi lengkap untuk developer
5. **User Experience** - Pesan yang jelas untuk user
6. **Logging** - Audit trail untuk troubleshooting

