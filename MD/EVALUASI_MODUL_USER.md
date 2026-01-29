# Kritik dan Saran untuk Modul User

Dokumen ini berisi evaluasi kritis dan rekomendasi perbaikan untuk modul manajemen user pada sistem Hadrahin. Analisis dilakukan berdasarkan pemeriksaan kode sumber, struktur database, dan alur kerja sistem.

---

## 1. Keamanan (Security)

### 1.1 Kekuatan yang Ada

Sistem telah mengimplementasikan beberapa fitur keamanan dasar yang baik:

- **Password Hashing**: Menggunakan `password_hash()` dengan algoritma `PASSWORD_DEFAULT` (Bcrypt), yang merupakan standar industri.
- **SQL Injection Prevention**: Menggunakan prepared statements dengan PDO untuk semua query database.
- **XSS Protection**: Menggunakan `htmlspecialchars()` untuk output data user-generated.
- **Session-based Authentication**: Menggunakan `auth_check.php` untuk memverifikasi setiap halaman yang memerlukan autentikasi.
- **Self-delete Prevention**: Mencegah user menghapus akunnya sendiri.
- **Single Admin Protection**: Mencegah penonaktifan akun admin terakhir.
- **Input Validation**: Melakukan validasi format input (username, no_hp, password) di sisi server.

### 1.2 Kelemahan dan Rekomendasi

#### 1.2.1 Validasi Password Lemah

**Masalah:**  
Validasi password hanya membutuhkan minimal 3 karakter, yang sangat rentan terhadap serangan brute force dan dictionary attack.

```php
// Kode saat ini
if (strlen($password) < 3) {
    $errors[] = "Password minimal 3 karakter!";
}
```

**Dampak:**  
- Password pendek mudah ditebak
- Tidak memenuhi standar keamanan modern
- Rentan terhadap serangan brute force

**Rekomendasi:**  
```php
// Rekomendasi perbaikan
$passwordRequirements = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_number' => true,
    'require_special_char' => true
];

$errors = [];
if (strlen($password) < $passwordRequirements['min_length']) {
    $errors[] = "Password minimal {$passwordRequirements['min_length']} karakter!";
}
if ($passwordRequirements['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
    $errors[] = "Password harus mengandung huruf besar!";
}
if ($passwordRequirements['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
    $errors[] = "Password harus mengandung huruf kecil!";
}
if ($passwordRequirements['require_number'] && !preg_match('/[0-9]/', $password)) {
    $errors[] = "Password harus mengandung angka!";
}
if ($passwordRequirements['require_special_char'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    $errors[] = "Password harus mengandung karakter khusus!";
}
```

**Prioritas:** Tinggi

---

#### 1.2.2 Tidak Ada Rate Limiting pada API Validasi

**Masalah:**  
API validasi (`check_username.php`, `check_no_hp.php`) tidak memiliki rate limiting, memungkinkan attacker untuk melakukan brute force enumeration.

```php
// Kode saat ini - tidak ada rate limiting
$stmt = $db->prepare("SELECT id_user FROM user WHERE username = ?");
$stmt->execute([$username]);
```

**Dampak:**  
- Attacker dapat enumerate semua username yang terdaftar
- Attacker dapat menemukan nomor HP yang sudah terdaftar
- Tidak ada perlindungan terhadap serangan brute force

**Rekomendasi:**  
Implementasikan rate limiting di setiap API validation:

```php
<?php
// check_username.php dengan rate limiting
require_once '../config/database.php';
require_once '../includes/rate_limit.php';

// Cek rate limit (maksimal 5 request per menit per IP)
if (!checkRateLimit('username_check', 5, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Terlalu banyak permintaan. Coba lagi nanti.']);
    exit;
}

// ... rest of the code
```

**Prioritas:** Tinggi

---

#### 1.2.3 Tidak Ada Password History/History Check

**Masalah:**  
Sistem tidak menyimpan history password, sehingga user dapat menggunakan password yang sama berulang kali setelah perubahan.

**Dampak:**  
- User dapat siklus kembali ke password lama yang mungkin sudah compromise
- Tidak ada audit trail untuk keamanan password

**Rekomendasi:**  
Tambahkan tabel `password_history` dan проверка saat password diubah:

```sql
CREATE TABLE `password_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_user` int(11) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `fk_password_history_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Prioritas:** Sedang

---

#### 1.2.4 Tidak Ada Two-Factor Authentication (2FA)

**Masalah:**  
Sistem hanya menggunakan username/password untuk autentikasi tanpa lapisan keamanan tambahan.

**Dampak:**  
- Jika password terekspos, akun dapat diakses
- Tidak ada perlindungan terhadap phishing yang berhasil

**Rekomendasi:**  
Implementasikan 2FA berbasis TOTP (Time-based One-Time Password):

```php
// Install library: composer require spekkike/google-authenticator
use OTP\GoogleAuthenticator;

$secret = GoogleAuthenticator::generateSecret();
$qrCodeUrl = GoogleAuthenticator::getQRCodeUrl(
    $username,
    $secret,
    'Hadrahin System'
);

// Simpan secret ke database (encrypted)
// Saat login, minta kode 6 digit dari user
```

**Prioritas:** Sedang

---

#### 1.2.5 Session Security yang Bisa Ditingkatkan

**Masalah:**  
Konfigurasi session belum optimal untuk keamanan maksimal.

**Rekomendasi:**  
Tambahkan konfigurasi session yang lebih ketat di `auth_check.php` atau `config/database.php`:

```php
// Session security configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 menit

// Regenerate session ID setelah login berhasil
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
```

**Prioritas:** Sedang

---

#### 1.2.6 Tidak Ada Log untuk Aktivitas User Sensitif

**Masalah:**  
Tidak ada logging untuk aktivitas sensitif seperti:
- Gagal login
- Perubahan password
- Perubahan peran user
- Aktivitas delete user

**Dampak:**  
- Sulit mendeteksi aktivitas mencurigakan
- Tidak ada audit trail untuk investigasi

**Rekomendasi:**  
Implementasikan sistem logging:

```php
// Fungsi logging
function logActivity($pdo, $idUser, $action, $details, $ip) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_log 
        (id_user, action, details, ip_address, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$idUser, $action, $details, $ip]);
}

// Penggunaan
logActivity($pdo, $_SESSION['user_id'], 'USER_DELETE', 
    "Deleted user ID: $id", $_SERVER['REMOTE_ADDR']);
```

```sql
CREATE TABLE `activity_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_user` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `details` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `fk_activity_user` (`id_user`),
    KEY `idx_action_created` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Prioritas:** Sedang

---

## 2. Kualitas Kode (Code Quality)

### 2.1 Inkonsistensi Penulisan Kode

**Masalah:**  
Terdapat inkonsistensi dalam penulisan kode di berbagai file:

1. Penggunaan `$db` vs `$pdo` untuk koneksi database
2. Variabel `$confirm_password` vs `$konfirmasi_password`
3.使用了印尼语和英语混合的注释

**Contoh:**
```php
// Di hapus.php menggunakan $db
$stmt = $db->prepare("SELECT * FROM user WHERE id_user = ?");

// Di tambah.php dan edit.php menggunakan $pdo
$stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
```

**Rekomendasi:**  
Standarisasi penulisan kode:

```php
// Gunakan satu konvensi命名
// Sebaiknya gunakan $pdo sebagai standar (lebih deskriptif untuk PDO)

// Untuk password confirmation, pilih satu format
// Rekomendasi: gunakan $confirm_password (bahasa Inggris standar)
```

**Prioritas:** Sedang

---

### 2.2 Duplicate Code pada Validasi Form

**Masalah:**  
Logika validasi yang sama diulang di `tambah.php` dan `edit.php`, serta juga di JavaScript.

**Contoh yang重复:**
```php
// Validasi username di tambah.php
if (empty($username)) {
    $errors[] = "Username wajib diisi!";
} elseif (strlen($username) < 3) {
    $errors[] = "Username minimal 3 karakter!";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore!";
}
```

**Rekomendasi:**  
Buat file validasi terpisah:

```php
// includes/user_validation.php
class UserValidator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function validateUsername($username, $excludeId = null) {
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username wajib diisi!";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username minimal 3 karakter!";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore!";
        } else {
            // Check uniqueness
            $stmt = $excludeId 
                ? $this->pdo->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?")
                : $this->pdo->prepare("SELECT id_user FROM user WHERE username = ?");
            $params = $excludeId ? [$username, $excludeId] : [$username];
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $errors[] = "Username sudah digunakan!";
            }
        }
        
        return $errors;
    }
    
    // ... method validation lainnya
}
```

**Prioritas:** Sedang

---

### 2.3 JavaScript Tidak Terpisah dari HTML

**Masalah:**  
JavaScript tercampur langsung dengan HTML, menyebabkan:
- Sulit maintenance
- Tidak dapat di-cache terpisah
- Tidak mengikuti best practice

**Rekomendasi:**  
Pindahkan JavaScript ke file terpisah:

```php
<!-- Di file PHP -->
<script src="../../assets/js/user-form-validation.js"></script>
```

```javascript
// assets/js/user-form-validation.js
document.addEventListener('DOMContentLoaded', function() {
    // Semua logika validation di sini
    initUsernameValidation();
    initPasswordValidation();
    initFormSubmission();
});
```

**Prioritas:** Sedang

---

### 2.4 Error Handling yang Tidak Konsisten

**Masalah:**  
Penanganan error tidak konsisten antara file:

```php
// Di tambah.php
catch (PDOException $e) {
    $errors[] = "Gagal menambahkan user: " . $e->getMessage();
}

// Seharusnya tidak menampilkan detail error ke user
```

**Rekomendasi:**  
Gunakan error handling terpusat:

```php
// includes/error_handler.php
function handleDatabaseError($e, $userMessage = "Terjadi kesalahan sistem") {
    // Log error lengkap untuk developer
    error_log("Database Error: " . $e->getMessage() . " in " . $e->getFile());
    
    // Tampilkan pesan generik ke user
    return $userMessage;
}

// Penggunaan
try {
    // database operation
} catch (PDOException $e) {
    $errors[] = handleDatabaseError($e, "Gagal menambahkan user");
}
```

**Prioritas:** Sedang

---

### 2.5 Tidak Ada CSRF Protection

**Masalah:**  
Form tidak memiliki CSRF token untuk mencegah Cross-Site Request Forgery.

**Rekomendasi:**  
Tambahkan CSRF token:

```php
// Di form
session_start();
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <!-- form fields -->
</form>

// Di proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }
    // proses form
}
```

**Prioritas:** Tinggi

---

## 3. Pengalaman Pengguna (UX/UI)

### 3.1 Toast Notification yang Tidak Konsisten

**Masalah:**  
Implementasi toast notification berbeda antar halaman dan tidak menggunakan komponen yang konsisten.

**Rekomendasi:**  
Buat fungsi utility untuk toast:

```php
// includes/toast_helper.php
function showToast($message, $type = 'success', $delay = 3000) {
    $classMap = [
        'success' => 'bg-success',
        'error' => 'bg-danger',
        'warning' => 'bg-warning',
        'info' => 'bg-info'
    ];
    
    $iconMap = [
        'success' => 'fa-check-circle',
        'error' => 'fa-times-circle',
        'warning' => 'fa-exclamation-circle',
        'info' => 'fa-info-circle'
    ];
    
    $class = $classMap[$type] ?? 'bg-primary';
    $icon = $iconMap[$type] ?? 'fa-info-circle';
    
    echo "
    <div class='toast-container position-fixed top-0 start-50 translate-middle-x mt-5' style='z-index: 9999'>
        <div class='toast align-items-center text-white $class border-0' role='alert'>
            <div class='d-flex'>
                <div class='toast-body'>
                    <i class='fas $icon me-2'></i>$message
                </div>
                <button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast'></button>
            </div>
        </div>
    </div>
    <script>
        new bootstrap.Toast(document.querySelector('.toast'), { delay: $delay }).show();
    </script>
    ";
}
```

**Prioritas:** Rendah

---

### 3.2 Konfirmasi Delete yang Tidak Ada Validasi Tambahan

**Masalah:**  
Halaman hapus.php langsung menampilkan konfirmasi tanpa meminta password atau konfirmasi ulang.

**Dampak:**  
- User bisa menghapus user lain secara tidak sengaja
- Tidak ada verifikasi identitas untuk operasi sensitif

**Rekomendasi:**  
Tambahkan password confirmation sebelum delete:

```php
// Di hapus.php
<form method="POST">
    <div class="mb-3">
        <label for="confirm_password" class="form-label">
            Masukkan password Anda untuk konfirmasi
        </label>
        <input type="password" class="form-control" id="confirm_password" 
               name="confirm_password" required>
    </div>
    <button type="submit" class="btn btn-danger btn-lg">
        <i class="fas fa-trash me-2"></i>Ya, Hapus User
    </button>
</form>

// Di proses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi password user yang sedang login
    $stmt = $pdo->prepare("SELECT password FROM user WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
    
    if (!password_verify($_POST['confirm_password'], $currentUser['password'])) {
        $error = "Password yang Anda masukkan salah!";
    }
    // ... proceed dengan delete
}
```

**Prioritas:** Tinggi

---

### 3.3 Feedback Real-time yang bisa Ditingkatkan

**Masalah:**  
Validasi real-time di JavaScript tidak selalu memberikan feedback visual yang jelas.

**Rekomendasi:**  
Tambahkan indikator visual yang lebih baik:

```javascript
// Tambahkan indicator loading saat check uniqueness
usernameInput.addEventListener('blur', function() {
    const originalValue = this.value;
    this.classList.add('is-loading'); // Tambahkan spinner
    
    fetch('../../api/check_username.php?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            this.classList.remove('is-loading');
            if (data.exists) {
                showError(this, 'Username sudah digunakan!');
            } else {
                showSuccess(this, 'Username tersedia!');
            }
        })
        .catch(() => {
            this.classList.remove('is-loading');
        });
});
```

```css
/* assets/css/validation.css */
.is-loading {
    background-image: url('../img/spinner.gif');
    background-repeat: no-repeat;
    background-position: right 10px center;
}

.is-valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.3-.4 1-.5 1.4.2l1.4 1.5c.5.5.2 1.4-.5 1.5l-3.6 3.6c-.3.3-.8.3-1.1 0z'/%3e%3c/svg%3e");
}

.is-invalid {
    border-color: #dc3545;
}
```

**Prioritas:** Sedang

---

### 3.4 Pagination Tidak Ada "Go to Page"

**Masalah:**  
Jika ada banyak user, navigasi pagination hanya menyediakan next/prev dan angka, tidak ada input untuk langsung ke halaman tertentu.

**Rekomendasi:**  
Tambahkan input page number:

```php
<!-- Di index.php - bagian pagination -->
<div class="d-flex align-items-center justify-content-between">
    <form method="GET" class="d-flex align-items-center">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        <label class="me-2">Ke halaman:</label>
        <input type="number" name="page" class="form-control" 
               style="width: 80px" min="1" max="<?= $total_pages ?>" 
               value="<?= $page ?>">
        <button type="submit" class="btn btn-outline-secondary ms-2">Go</button>
    </form>
    
    <div class="text-muted">
        Menampilkan <?= $offset + 1 ?>-<?= min($offset + $limit, $total_users) ?> 
        dari <?= $total_users ?> user
    </div>
</div>
```

**Prioritas:** Rendah

---

### 3.5 Tidak Ada Bulk Action

**Masalah:**  
Tidak ada opsi untuk bulk action (delete multiple, change status) yang mengurangi efisiensi admin.

**Rekomendasi:**  
Tambahkan checkbox dan bulk actions:

```php
<!-- Di index.php - tambahkan kolom checkbox -->
<table class="table">
    <thead>
        <tr>
            <th width="40">
                <input type="checkbox" id="selectAll" onclick="toggleAll()">
            </th>
            <!-- kolom lain -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td>
                <input type="checkbox" name="selected_users[]" 
                       value="<?= $user['id_user'] ?>">
            </td>
            <!-- kolom lain -->
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Bulk actions toolbar -->
<div class="bulk-actions-bar" id="bulkActions" style="display: none;">
    <div class="card">
        <div class="card-body py-2">
            <span class="me-3" id="selectedCount">0 user dipilih</span>
            <button class="btn btn-sm btn-outline-warning" onclick="bulkChangeStatus(1)">
                <i class="fas fa-check me-1"></i>Aktifkan
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="bulkChangeStatus(0)">
                <i class="fas fa-times me-1"></i>Nonaktifkan
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                <i class="fas fa-trash me-1"></i>Hapus
            </button>
        </div>
    </div>
</div>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkActionsUI();
}

function updateBulkActionsUI() {
    const selected = document.querySelectorAll('input[name="selected_users[]"]:checked').length;
    document.getElementById('selectedCount').textContent = selected + ' user dipilih';
    document.getElementById('bulkActions').style.display = selected > 0 ? 'block' : 'none';
}
</script>
```

**Prioritas:** Sedang

---

## 4. Desain Database

### 4.1 Unique Constraint pada no_hp

**Masalah:**  
Kolom `no_hp` tidak memiliki unique constraint di database level, hanya dicek di aplikasi.

```sql
-- Struktur saat ini
`no_hp` varchar(20) DEFAULT NULL,
-- Tidak ada UNIQUE KEY untuk no_hp
```

**Dampak:**  
- Jika ada bug di aplikasi, duplicate phone numbers bisa terjadi
- Lebih lambat karena tidak ada index untuk uniqueness check

**Rekomendasi:**  
Tambahkan unique constraint:

```sql
ALTER TABLE `user` ADD UNIQUE KEY `unique_no_hp` (`no_hp`);
```

**Prioritas:** Sedang

---

### 4.2 Tidak Ada Soft Delete

**Masalah:**  
Penghapusan user menggunakan hard delete (permanen), yang dapat menyebabkan:
- Kehilangan data historis
- orphan records di tabel lain yang FK-nya tidak cascade

**Dampak:**  
- Data terkait di tabel lain menjadi orphan (walaupun ada FK constraint, behavior bisa tidak diinginkan)
- Tidak ada kemampuan untuk restore user yang terhapus

**Rekomendasi:**  
Implementasikan soft delete:

```sql
ALTER TABLE `user` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `status_aktif`;

-- Query untuk get users (exclude deleted)
SELECT * FROM user WHERE deleted_at IS NULL;

// Query delete menjadi:
UPDATE user SET deleted_at = NOW() WHERE id_user = ?;

// Query untuk restore:
UPDATE user SET deleted_at = NULL WHERE id_user = ?;
```

**Prioritas:** Tinggi

---

### 4.3 Tidak Ada Password Reset Token

**Masalah:**  
Tidak ada mekanisme untuk reset password jika user lupa, hanya ada halaman `reset.php` yang tidak lengkap.

**Dampak:**  
- Jika user lupa password, admin harus reset manual
- Tidak ada self-service password recovery

**Rekomendasi:**  
Tambahkan tabel untuk password reset tokens:

```sql
CREATE TABLE `password_resets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(100) NOT NULL,
    `token` varchar(100) NOT NULL,
    `expires_at` timestamp NOT NULL,
    `used_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_email_expires` (`email`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```php
// Logic untuk password reset
function generatePasswordResetToken($email, $pdo) {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (email, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, password_hash($token, PASSWORD_DEFAULT), $expiresAt]);
    
    // Kirim email dengan link reset
    $resetLink = BASE_URL . "/auth/reset.php?token=" . $token . "&email=" . urlencode($email);
    sendEmail($email, "Reset Password", "Klik link berikut untuk reset password: $resetLink");
}
```

**Prioritas:** Tinggi

---

### 4.4 Tidak Ada Profile Picture

**Masalah:**  
User tidak dapat mengupload foto profil, hanya menggunakan inisial username.

**Rekomendasi:**  
Tambahkan kolom profile_picture:

```sql
ALTER TABLE `user` ADD COLUMN `profile_picture` varchar(255) DEFAULT NULL AFTER `no_hp`;
```

**Prioritas:** Rendah

---

### 4.5 Tidak Ada Audit Columns yang Konsisten

**Masalah:**  
Tabel user memiliki `user_modified` dan `user_record`, tetapi tidak selalu digunakan dengan benar.

**Rekomendasi:**  
Buat trigger untuk otomatis mengisi audit columns:

```sql
DELIMITER //

CREATE TRIGGER before_user_update
BEFORE UPDATE ON user
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
    SET NEW.user_modified = IFNULL(NEW.user_modified, NEW.user_record);
END //

CREATE TRIGGER before_user_insert
BEFORE INSERT ON user
FOR EACH ROW
BEGIN
    SET NEW.created_at = NOW();
    SET NEW.updated_at = NOW();
END //

DELIMITER ;
```

**Prioritas:** Sedang

---

## 5. Performa

### 5.1 Query Tidak Optimal di Pagination

**Masalah:**  
Query dengan LIMIT dan OFFSET dapat menjadi lambat untuk data dalam jumlah besar:

```php
// Query saat ini - menggunakan string interpolation untuk LIMIT/OFFSET
$stmt = $pdo->query("SELECT * FROM user ORDER BY id_user DESC LIMIT $limit OFFSET $offset");
```

**Dampak:**  
- OFFSET besar menyebabkan full table scan
- Performa menurun signifikan untuk halaman akhir

**Rekomendasi:**  
Gunakan keyset pagination (cursor-based) untuk data dalam jumlah besar:

```php
// Alternatif 1: Cursor-based pagination
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$stmt = $pdo->prepare("
    SELECT * FROM user 
    WHERE id_user < ? 
    ORDER BY id_user DESC 
    LIMIT ?
");
$stmt->execute([$lastId, $limit]);

// Alternatif 2: Jika tetap menggunakan OFFSET, pastikan ada index
// ALTER TABLE user ADD INDEX idx_id_user (id_user);
```

**Prioritas:** Sedang

---

### 5.2 Tidak Ada Caching

**Masalah:**  
Tidak ada caching untuk data yang sering diakses seperti daftar user.

**Dampak:**  
- Setiap halaman load melakukan query database
- Load server meningkat untuk traffic tinggi

**Rekomendasi:**  
Implementasikan caching sederhana:

```php
// Di index.php
$cacheKey = 'users_list_' . md5($search . $page . $limit);
$cacheFile = '../../cache/' . $cacheKey . '.cache';
$cacheExpiry = 300; // 5 menit

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheExpiry) {
    $users = unserialize(file_get_contents($cacheFile));
} else {
    // Query database
    $users = $stmt->fetchAll();
    
    // Simpan ke cache
    if (!is_dir('../../cache')) {
        mkdir('../../cache', 0755, true);
    }
    file_put_contents($cacheFile, serialize($users));
}
```

**Prioritas:** Rendah

---

### 5.3 Validasi API yang Berlebihan

**Masalah:**  
API validation (`check_username.php`, `check_no_hp.php`) tidak melakukan caching hasil, menyebabkan multiple requests ke database.

**Rekomendasi:**  
Tambahkan caching untuk hasil validasi:

```php
// check_username.php
$cacheKey = 'username_exists_' . md5($username);
$cachedResult = apcu_fetch($cacheKey);

if ($cachedResult !== false) {
    echo json_encode(['exists' => $cachedResult]);
    exit;
}

// ... query database
// Simpan hasil
apcu_store($cacheKey, $exists, 60); // Cache selama 1 menit
```

**Prioritas:** Rendah

---

## 6. Maintainability

### 6.1 Tidak Ada Unit Tests

**Masalah:**  
Tidak ada testing framework atau unit tests untuk memverifikasi fungsi krusial.

**Rekomendasi:**  
Implementasikan PHPUnit tests:

```php
// tests/UserValidationTest.php
<?php
use PHPUnit\Framework\TestCase;

class UserValidationTest extends TestCase {
    private $pdo;
    
    protected function setUp(): void {
        // Setup test database
    }
    
    public function testUsernameValidation(): void {
        $validator = new UserValidator($this->pdo);
        
        // Test empty username
        $errors = $validator->validateUsername('');
        $this->assertContains('Username wajib diisi!', $errors);
        
        // Test valid username
        $errors = $validator->validateUsername('valid_user123');
        $this->assertEmpty($errors);
    }
    
    public function testPasswordStrength(): void {
        $validator = new UserValidator($this->pdo);
        
        // Test weak password
        $errors = $validator->validatePassword('abc');
        $this->assertNotEmpty($errors);
        
        // Test strong password
        $errors = $validator->validatePassword('SecureP@ss123!');
        $this->assertEmpty($errors);
    }
}
```

**Prioritas:** Sedang

---

### 6.2 Tidak Ada Documentation

**Masalah:**  
Tidak ada dokumentasi teknis untuk modul user.

**Rekomendasi:**  
Buat dokumentasi teknis:

```markdown
# Dokumentasi Modul User

## Arsitektur

### Database Schema
![User Schema](diagrams/user_schema.png)

### Alur CRUD
1. **Create** - `tambah.php`
   - Validasi input (server & client)
   - Check uniqueness (AJAX)
   - Hash password
   - Insert ke database
   - Return JSON response

## API Endpoints

### POST /api/check_username.php
| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| username | string | Ya | Username untuk dicek |

### POST /api/check_no_hp.php
| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| no_hp | string | Ya | Nomor HP untuk dicek |
| exclude_id | int | Tidak | ID user yang dieksklusikan |

## Security Considerations

1. Semua input divalidasi dan disanitasi
2. Password di-hash menggunakan Bcrypt
3. SQL injection dicegah dengan prepared statements
4. XSS dicegah dengan htmlspecialchars()
```

**Prioritas:** Sedang

---

### 6.3 Komentar yang Tidak Konsisten

**Masalah:**  
Komentar menggunakan campuran bahasa Indonesia dan Inggris.

**Rekomendasi:**  
Gunakan satu bahasa (disarankan Inggris untuk konsistensi dengan komunitas):

```php
// Sebelum (beragam)
$errors[] = "Username wajib diisi!"; // Indonesian
$errors[] = "Password minimal 3 karakter!"; // Indonesian
// Validasi username - campur

// Sesudah (konsisten)
// All comments in English
$errors[] = "Username is required!";
$errors[] = "Password must be at least 3 characters!";
```

**Prioritas:** Rendah

---

## 7. Ringkasan Prioritas

### Prioritas Tinggi (Harus Segera Diperbaiki)

| Issue | Estimasi Effort | Dampak |
|-------|-----------------|--------|
| CSRF Protection | Medium | Security |
| Rate Limiting API | Small | Security |
| Validasi Password Lemah | Small | Security |
| Konfirmasi Password untuk Delete | Small | UX/Security |
| Soft Delete | Medium | Data Integrity |
| Password Reset Mechanism | Medium | UX/Security |

### Prioritas Sedang (Harus Diperbaiki)

| Issue | Estimasi Effort | Dampak |
|-------|-----------------|--------|
| Code Duplication (Validation) | Medium | Maintainability |
| Error Handling Konsisten | Small | Maintainability |
| Password History | Medium | Security |
| Bulk Actions | Medium | UX |
| Unique Constraint no_hp | Small | Data Integrity |
| Audit Log | Medium | Security |
| Session Security | Small | Security |

### Prioritas Rendah (Nice to Have)

| Issue | Estimasi Effort | Dampak |
|-------|-----------------|--------|
| Toast Helper | Small | UX |
| Page Input di Pagination | Small | UX |
| Caching | Medium | Performance |
| Profile Picture | Medium | UX |
| 2FA | Large | Security |
| Unit Tests | Large | Maintainability |
| Documentation | Medium | Maintainability |

---

## 8. Kesimpulan

Modul user pada sistem Hadrahin sudah memiliki fondasi yang baik dengan implementasi dasar keamanan seperti password hashing dan SQL injection prevention. Namun, ada beberapa area kritis yang perlu ditingkatkan terutama pada:

1. **Keamanan**: Password lemah, tidak ada CSRF protection, dan rate limiting
2. **Data Integrity**: Tidak ada soft delete dan unique constraint
3. **User Experience**: Konfirmasi delete yang lemah dan tidak ada bulk actions
4. **Maintainability**: Duplicate code dan error handling tidak konsisten

Rekomendasi utama adalah segera mengimplementasikan CSRF protection, rate limiting, dan meningkatkan validasi password sebagai langkah quick wins untuk keamanan. Setelah itu, fokus pada soft delete dan password reset mechanism untuk meningkatkan reliability sistem.

---

*Dokumen ini dibuat sebagai bagian dari evaluasi modul user. Rekomendasi dapat diimplementasikan secara bertahap sesuai dengan prioritas dan resources yang tersedia.*

