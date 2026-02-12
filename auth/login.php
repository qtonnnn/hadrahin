<?php
/**
 * Login Page dengan Comprehensive Error Handling
 */

// Nonaktifkan display_errors untuk production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================
// ANTI-CACHE HEADERS - PENTING UNTUK KEAMANAN
// ============================================
// Headers ini mencegah browser menyimpan cache halaman login
// sehingga saat logout dan tekan back, halaman akan redirect ke login
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

// Tentukan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

// Load error handler dan custom exceptions
require_once '../includes/error_handler.php';

// Start session SEBELUM require rate_limit
session_start();

// Include rate limiting
require_once '../includes/rate_limit.php';

// Cek rate limit SEBELUM memproses login
$rate_limit = check_rate_limit();
$is_blocked = $rate_limit['blocked'];

// Jika sudah login, redirect ke halaman utama
if (isset($_SESSION['user_id'])) {
    $peran = $_SESSION['peran'];
    if ($peran === 'admin') {
        header('Location: ' . BASE_URL . '/dashboard/admin.php');
    } elseif ($peran === 'pembina') {
        header('Location: ' . BASE_URL . '/dashboard/pembina.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard/anggota.php');
    }
    exit;
}

$error = '';
$error_type = 'general'; // general, inactive, rate_limit

// Proses login jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Jika sedang diblokir, cek lagi apakah sudah melewati waktu blokir
        if ($is_blocked) {
            $check_again = check_rate_limit();
            if ($check_again['blocked']) {
                throw new RateLimitException(
                    "Terlalu banyak percobaan login gagal",
                    $check_again['message']
                );
            } else {
                $is_blocked = false;
            }
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validasi input kosong
        if (empty($username) || empty($password)) {
            throw new ValidationException(
                "Username dan password wajib diisi",
                "Username dan password harus diisi!"
            );
        }
        
        // Koneksi ke database menggunakan PDO
        require_once '../config/database.php';
        
        // Query untuk mencari user
        $query = "SELECT * FROM user WHERE username = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            // Cek apakah akun non-aktif
            if ($user['status_aktif'] == 0) {
                throw new AuthorizationException(
                    "Akun non-aktif: {$username}",
                    "Akun ini telah dinonaktifkan. Silakan hubungi administrator."
                );
            }
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Login BERHASIL - reset rate limit
                reset_rate_limit();
                
                // Set session
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['peran'] = $user['peran'];
                
                // Redirect berdasarkan peran
                $peran = $user['peran'];
                if ($peran === 'admin') {
                    header('Location: ' . BASE_URL . '/dashboard/admin.php');
                } elseif ($peran === 'pembina') {
                    header('Location: ' . BASE_URL . '/dashboard/pembina.php');
                } else {
                    header('Location: ' . BASE_URL . '/dashboard/anggota.php');
                }
                exit;
            } else {
                // Login gagal - catat percobaan
                record_failed_attempt();
                throw new AuthenticationException(
                    "Password salah untuk user: {$username}",
                    "Password yang Anda masukkan salah!"
                );
            }
        } else {
            throw new AuthenticationException(
                "User tidak ditemukan: {$username}",
                "Username tidak ditemukan!"
            );
        }
        
    } catch (RateLimitException $e) {
        $error = $e->getUserMessage();
        $error_type = 'rate_limit';
        $rate_limit_data = $rate_limit;
    } catch (ValidationException $e) {
        $error = $e->getUserMessage();
        $error_type = 'general';
    } catch (AuthenticationException $e) {
        $error = $e->getUserMessage();
        $error_type = 'general';
    } catch (AuthorizationException $e) {
        $error = $e->getUserMessage();
        $error_type = 'inactive';
    } catch (PDOException $e) {
        ErrorHandler::log("Login DB Error: " . $e->getMessage(), 'ERROR');
        $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        $error_type = 'general';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hadrah</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Fade In Animation for Container */
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        /* Simple fade in animation */
        @keyframes simpleFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Staggered animations for child elements */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
            animation: simpleFadeIn 0.5s ease-out;
        }

        .login-header h1 {
            color: #1a1a2e;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .logo-hadrah {
            font-size: 48px;
            margin-bottom: 10px;
            display: inline-block;
            animation: bounceIn 0.6s ease-out 0.1s both;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .form-group {
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInField 0.4s ease-out forwards;
        }

        .form-group:nth-of-type(1) {
            animation-delay: 0.3s;
        }

        .form-group:nth-of-type(2) {
            animation-delay: 0.5s;
        }

        @keyframes fadeInField {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a1a2e;
            box-shadow: 0 0 0 3px rgba(26, 26, 46, 0.1);
            transform: scale(1.02);
        }

        .form-group input:not(:placeholder-shown) {
            border-color: #1a1a2e;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            opacity: 0;
            animation: fadeInField 0.4s ease-out 0.7s forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(26, 26, 46, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fcc;
            animation: shake 0.5s ease-out;
        }

        .error-message.success {
            background: #efe;
            color: #060;
            border-color: #cfc;
        }

        /* Style khusus untuk akun non-aktif */
        .error-message.inactive-account {
            background: linear-gradient(135deg, #fff3cd 0%, #ffc107 100%);
            color: #856404;
            border-color: #ffc107;
        }

        .error-message.inactive-account h4 {
            margin-bottom: 8px;
            font-size: 16px;
        }

        .error-message.inactive-account p {
            margin-bottom: 8px;
        }

        .error-message.inactive-account .contact-info {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #856404;
        }

        /* Rate limit countdown styles */
        .rate-limit-message {
            background: linear-gradient(135deg, #fff3cd 0%, #ffc107 100%);
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #ffc107;
            text-align: center;
            animation: pulse 2s infinite;
        }

        .rate-limit-message h3 {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .countdown-timer {
            font-size: 32px;
            font-weight: bold;
            color: #d32f2f;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }

        .countdown-label {
            font-size: 12px;
            opacity: 0.8;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            opacity: 0;
            animation: fadeInField 0.4s ease-out 0.9s forwards;
        }

        .login-footer a {
            color: #1a1a2e;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #ffd700;
        }

        /* Change Password Button Styles - Minimalist */
        .change-password-section {
            margin-top: 16px;
            text-align: center;
            animation: fadeInField 0.4s ease-out 0.85s forwards;
            opacity: 0;
        }

        .btn-change-password {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            border-radius: 6px;
        }

        .btn-change-password:hover {
            color: #1a1a2e;
            background: rgba(26, 26, 46, 0.05);
        }

        .btn-change-password:hover .btn-arrow-minimal {
            transform: translateX(3px);
        }

        .btn-icon-minimal {
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .btn-arrow-minimal {
            font-size: 12px;
            transition: all 0.3s ease;
            color: inherit;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/img/logo.png" alt="Hadrah Logo" class="logo-hadrah" style="width: 150px; height: 150px; object-fit: contain;">
            <h1>Hadrahin</h1>
            <p>Sistem Informasi Grup Hadrah</p>
        </div>

        <?php if ($error): ?>
            <?php if ($is_blocked && isset($rate_limit['wait_seconds'])): ?>
            <div class="rate-limit-message">
                <h3>⚠️ Akses Diblokir</h3>
                <p>Terlalu banyak percobaan login yang gagal.</p>
                <p class="countdown-label">Silakan tunggu:</p>
                <div id="countdown-timer" class="countdown-timer">Loading...</div>
                <p class="countdown-label">Halaman akan otomatis refresh setelah waktu habis</p>
            </div>
            <?php elseif (strpos($error, 'telah dinonaktifkan') !== false): ?>
            <!-- Tampilan khusus untuk akun non-aktif -->
            <div class="error-message inactive-account">
                <h4>🚫 Akun Non-Aktif</h4>
                <p><?= htmlspecialchars($error) ?></p>
                <div class="contact-info">
                    <strong>Hubungi Administrator:</strong><br>
                    📧 Email: admin@hadrah.com<br>
                    📱 Silakan hubungi admin untuk mengaktifkan akun Anda
                </div>
            </div>
            <?php else: ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       placeholder="Masukkan username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Masukkan password" 
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="change-password-section">
            <button type="button" class="btn-change-password" onclick="showChangePasswordModal()">
                <i class="fas fa-key btn-icon-minimal"></i>
                <span>Ubah Password</span>
                <i class="fas fa-chevron-right btn-arrow-minimal"></i>
            </button>
        </div>

        <div class="login-footer">
            <p>Belum punya akun? Hubungi administrator.</p>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fas fa-key me-2"></i>Ubah Password</h3>
                <button type="button" class="modal-close" onclick="closeChangePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="changePasswordError" class="error-message mb-3" style="display: none;"></div>
                <div id="changePasswordSuccess" class="success-message mb-3" style="display: none; background: #efe; color: #060; padding: 12px; border-radius: 8px; border: 1px solid #cfc;"></div>
                
                <form id="changePasswordForm" onsubmit="handleChangePassword(event)">
                    <div class="form-group">
                        <label for="cp_username">Nama Akun (Username)</label>
                        <input type="text" id="cp_username" name="username" placeholder="Masukkan username akun Anda" required>
                        <small class="validation-message" id="cp_username_msg"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cp_old_password">Password Lama</label>
                        <input type="password" id="cp_old_password" name="old_password" placeholder="Masukkan password lama" required>
                        <small class="validation-message" id="cp_old_password_msg"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cp_new_password">Password Baru</label>
                        <input type="password" id="cp_new_password" name="new_password" placeholder="Masukkan password baru (min. 3 karakter)" required minlength="3">
                        <small class="validation-message" id="cp_new_password_msg"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cp_confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="cp_confirm_password" name="confirm_password" placeholder="Ulangi password baru" required>
                        <small class="validation-message" id="cp_confirm_password_msg"></small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" onclick="closeChangePasswordModal()">Batal</button>
                        <button type="submit" class="btn btn-primary flex-fill" id="cp_submit_btn">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-container {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            margin: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #1a1a2e;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-body .form-group {
            margin-bottom: 16px;
        }
        
        .modal-body .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .modal-body .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .modal-body .form-group input:focus {
            outline: none;
            border-color: #1a1a2e;
            box-shadow: 0 0 0 3px rgba(26, 26, 46, 0.1);
        }
        
        .modal-body .form-group input.valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2328a745' viewBox='0 0 20 20'%3E%3Cpath fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 18px;
            padding-right: 40px;
        }
        
        .modal-body .form-group input.invalid {
            border-color: #dc3545;
        }
        
        .validation-message {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            min-height: 18px;
            transition: all 0.3s ease;
        }
        
        .validation-message.error {
            color: #dc3545;
        }
        
        .validation-message.success {
            color: #28a745;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 26, 46, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .d-flex {
            display: flex;
        }
        
        .gap-2 {
            gap: 12px;
        }
        
        .flex-fill {
            flex: 1;
        }
        
        .success-message {
            background: #efe;
            color: #060;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #cfc;
        }
        
        .text-decoration-none {
            text-decoration: none !important;
        }
    </style>

    <script>
    // ========================================
    // REAL-TIME VALIDATION FOR CHANGE PASSWORD FORM
    // ========================================
    (function() {
        const form = document.getElementById('changePasswordForm');
        if (!form) return;

        let usernameTimeout = null;

        const fields = {
            username: {
                el: document.getElementById('cp_username'),
                msg: document.getElementById('cp_username_msg'),
                validate: function(value) {
                    if (value.length === 0) return { valid: false, message: 'Nama akun wajib diisi' };
                    if (value.length < 3) return { valid: false, message: 'Nama akun minimal 3 karakter' };
                    return { valid: true, message: 'Nama akun valid' };
                },
                checkExists: function(value, callback) {
                    const self = this;
                    if (value.length < 3) {
                        callback({ exists: null, message: 'Nama akun minimal 3 karakter' });
                        return;
                    }
                    
                    // Debounce request
                    clearTimeout(usernameTimeout);
                    self.msg.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memeriksa...';
                    
                    usernameTimeout = setTimeout(function() {
                        fetch('../api/check_username_exists.php?username=' + encodeURIComponent(value))
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Response tidak valid: ' + response.status);
                                }
                                return response.json();
                            })
                            .then(data => {
                                callback(data);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                callback({ exists: null, message: 'Gagal memverifikasi akun' });
                            });
                    }, 500);
                }
            },
            old_password: {
                el: document.getElementById('cp_old_password'),
                msg: document.getElementById('cp_old_password_msg'),
                validate: function(value) {
                    if (value.length === 0) return { valid: false, message: 'Password lama wajib diisi' };
                    if (value.length < 1) return { valid: false, message: 'Password lama tidak boleh kosong' };
                    return { valid: true, message: '' };
                }
            },
            new_password: {
                el: document.getElementById('cp_new_password'),
                msg: document.getElementById('cp_new_password_msg'),
                validate: function(value) {
                    if (value.length === 0) return { valid: false, message: 'Password baru wajib diisi' };
                    if (value.length < 3) return { valid: false, message: 'Password baru minimal 3 karakter' };
                    if (value.length > 50) return { valid: false, message: 'Password baru maksimal 50 karakter' };
                    return { valid: true, message: 'Password baru valid' };
                }
            },
            confirm_password: {
                el: document.getElementById('cp_confirm_password'),
                msg: document.getElementById('cp_confirm_password_msg'),
                validate: function(value) {
                    const newPassword = document.getElementById('cp_new_password').value;
                    if (value.length === 0) return { valid: false, message: 'Konfirmasi password wajib diisi' };
                    if (value !== newPassword) return { valid: false, message: 'Konfirmasi password tidak cocok' };
                    return { valid: true, message: 'Password cocok' };
                }
            }
        };

        // Add real-time validation to each field
        Object.keys(fields).forEach(function(fieldName) {
            const field = fields[fieldName];
            if (!field.el) return;

            field.el.addEventListener('input', function() {
                const result = field.validate(this.value);
                
                // Remove previous classes
                field.el.classList.remove('valid', 'invalid');
                field.msg.classList.remove('error', 'success');

                // Handle username exists check
                if (fieldName === 'username' && result.valid) {
                    field.checkExists(this.value, function(checkResult) {
                        // Reset styles
                        field.el.classList.remove('valid', 'invalid');
                        field.msg.classList.remove('error', 'success');
                        
                        // Get message safely
                        const message = checkResult && checkResult.message ? checkResult.message : 'Akun tidak ditemukan';
                        const exists = checkResult && checkResult.exists === true;
                        
                        field.msg.textContent = message;
                        
                        if (exists) {
                            field.el.classList.add('valid');
                            field.msg.classList.add('success');
                        } else {
                            field.el.classList.add('invalid');
                            field.msg.classList.add('error');
                        }
                    });
                    return;
                }

                // Handle normal fields
                if (this.value.length > 0) {
                    if (result.valid) {
                        field.el.classList.add('valid');
                        field.msg.classList.add('success');
                    } else {
                        field.el.classList.add('invalid');
                        field.msg.classList.add('error');
                    }
                }
                
                field.msg.textContent = result.message;
            });

            // Also validate on blur
            field.el.addEventListener('blur', function() {
                if (this.value.length > 0) {
                    const result = field.validate(this.value);
                    
                    field.el.classList.remove('valid', 'invalid');
                    field.msg.classList.remove('error', 'success');
                    
                    // For username, check exists on blur
                    if (fieldName === 'username' && result.valid) {
                        field.checkExists(this.value, function(checkResult) {
                            // Reset styles
                            field.el.classList.remove('valid', 'invalid');
                            field.msg.classList.remove('error', 'success');
                            
                            // Get message safely
                            const message = checkResult && checkResult.message ? checkResult.message : 'Akun tidak ditemukan';
                            const exists = checkResult && checkResult.exists === true;
                            
                            field.msg.textContent = message;
                            
                            if (exists) {
                                field.el.classList.add('valid');
                                field.msg.classList.add('success');
                            } else {
                                field.el.classList.add('invalid');
                                field.msg.classList.add('error');
                            }
                        });
                        return;
                    }

                    if (result.valid) {
                        field.el.classList.add('valid');
                        field.msg.classList.add('success');
                    } else {
                        field.el.classList.add('invalid');
                        field.msg.classList.add('error');
                    }
                }
            });
        });

        // Cross-field validation for confirm password when new password changes
        const newPasswordEl = document.getElementById('cp_new_password');
        const confirmPasswordEl = document.getElementById('cp_confirm_password');
        const confirmPasswordMsg = document.getElementById('cp_confirm_password_msg');

        if (newPasswordEl && confirmPasswordEl) {
            newPasswordEl.addEventListener('input', function() {
                if (confirmPasswordEl.value.length > 0) {
                    const result = fields.confirm_password.validate(confirmPasswordEl.value);
                    confirmPasswordMsg.textContent = result.message;
                    
                    confirmPasswordEl.classList.remove('valid', 'invalid');
                    confirmPasswordMsg.classList.remove('error', 'success');
                    
                    if (result.valid) {
                        confirmPasswordEl.classList.add('valid');
                        confirmPasswordMsg.classList.add('success');
                    } else {
                        confirmPasswordEl.classList.add('invalid');
                        confirmPasswordMsg.classList.add('error');
                    }
                }
            });
        }
    })();

    // Change Password Modal Functions
    function showChangePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // Clear form
        document.getElementById('changePasswordForm').reset();
        document.getElementById('changePasswordError').style.display = 'none';
        document.getElementById('changePasswordSuccess').style.display = 'none';
    }
    
    function closeChangePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modal on overlay click
    document.getElementById('changePasswordModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeChangePasswordModal();
        }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeChangePasswordModal();
        }
    });
    
    // Handle Change Password Form Submit
    function handleChangePassword(event) {
        event.preventDefault();
        
        const errorEl = document.getElementById('changePasswordError');
        const successEl = document.getElementById('changePasswordSuccess');
        const submitBtn = document.getElementById('cp_submit_btn');
        
        // Hide previous messages
        errorEl.style.display = 'none';
        successEl.style.display = 'none';
        
        // Get form data
        const formData = new FormData(document.getElementById('changePasswordForm'));
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memproses...';
        
        // Send AJAX request
        fetch('../api/change_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Response tidak valid: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan';
            
            if (data.success) {
                successEl.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Password berhasil diubah!');
                successEl.style.display = 'block';
                // Clear form after success
                setTimeout(function() {
                    closeChangePasswordModal();
                }, 2000);
            } else {
                errorEl.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                errorEl.style.display = 'block';
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan';
            errorEl.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan. Silakan coba lagi.';
            errorEl.style.display = 'block';
            console.error('Error:', error);
        });
    }
    </script>

    <script>
    <?php if ($is_blocked && isset($rate_limit['wait_seconds'])): ?>
    // Countdown timer for rate limit
    (function() {
        var remainingSeconds = <?= intval($rate_limit['wait_seconds']) ?>;
        var countdownElement = document.getElementById('countdown-timer');
        
        function updateCountdown() {
            if (remainingSeconds <= 0) {
                // Refresh page to check if block is lifted
                location.reload();
                return;
            }
            
            var minutes = Math.floor(remainingSeconds / 60);
            var seconds = remainingSeconds % 60;
            
            if (minutes > 0) {
                countdownElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds + ' menit';
            } else {
                countdownElement.textContent = seconds + ' detik';
            }
            
            remainingSeconds--;
            
            // Update every second
            setTimeout(updateCountdown, 1000);
        }
        
        // Start countdown
        updateCountdown();
        
        // Disable form while waiting
        var form = document.querySelector('form');
        var inputs = form.querySelectorAll('input');
        var button = form.querySelector('button');
        
        inputs.forEach(function(input) {
            input.disabled = true;
        });
        button.disabled = true;
        button.textContent = 'Tunggu sebentar...';
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
    })();
    <?php endif; ?>
    
    // ========================================
    // ANTI-BACK BUTTON SCRIPT
    // ========================================
    // Mencegah user kembali ke halaman cached setelah logout
    // dengan menghapus history dan mengunci navigasi
    (function() {
        // Replace current history entry dengan halaman login
        // sehingga back button tidak bisa kembali ke halaman sebelumnya
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Prevent back navigation using popstate event
        window.addEventListener('popstate', function(event) {
            // Setiap kali user tekan back, replace state lagi
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            // Redirect ke halaman login jika terdeteksi upaya back
            window.location.href = '<?= BASE_URL ?>/auth/login.php<?= isset($_GET['logout']) ? '?logout=success' : '' ?>';
        });
        
        // Additional protection: Clear any cached pages inbfcache
        window.onpageshow = function(event) {
            if (event.persisted) {
                // Page was loaded from bfcache (back-forward cache)
                window.location.reload();
            }
        };
        
        // Prevent keyboard shortcuts for back navigation
        document.addEventListener('keydown', function(e) {
            // Block Alt+Left Arrow (browser back)
            if (e.altKey && e.key === 'ArrowLeft') {
                e.preventDefault();
                window.location.href = '<?= BASE_URL ?>/auth/login.php<?= isset($_GET['logout']) ? '?logout=success' : '' ?>';
            }
        });
    })();
    </script>
</body>
</html>

