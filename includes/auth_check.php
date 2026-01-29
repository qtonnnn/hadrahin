<?php
/**
 * File: includes/auth_check.php
 * Fungsi: Cek login dan management session yang aman
 * Include di halaman yang memerlukan login
 */

// Konfigurasi error yang aman untuk production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    // Set session lifetime (1 jam)
    ini_set('session.gc_maxlifetime', 3600);
    session_set_cookie_params(3600);
    session_start();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

/**
 * Fungsi: Cek apakah user sudah login
 * Jika belum, redirect ke halaman login
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: auth/login.php');
        exit;
    }
}

/**
 * Fungsi: Regenerate session ID untuk keamanan
 * Mencegah session fixation attack
 */
function regenerate_session() {
    if (!isset($_SESSION['created'])) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        // Regenerate setiap 30 menit
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Fungsi: Cek session timeout
 * Jika tidak aktif selama 1 jam, logout otomatis
 */
function check_session_timeout() {
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        $timeout = 3630; // 1 jam dalam detik
        $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
        
        if ($elapsed > $timeout) {
            // Session expired
            session_unset();
            session_destroy();
            header('Location: auth/login.php?error=session_expired');
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();
}

/**
 * Fungsi: Validasi session fingerprint
 * Cek IP dan User Agent untuk keamanan tambahan
 */
function validate_session_fingerprint() {
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = generate_fingerprint();
    } elseif ($_SESSION['fingerprint'] !== generate_fingerprint()) {
        // Fingerprint tidak cocok - mungkin cookie dicuri
        session_unset();
        session_destroy();
        header('Location: auth/login.php?error=security_issue');
        exit;
    }
}

/**
 * Fungsi: Generate fingerprint dari browser user
 * PERBAIKAN: User-Agent tidak digunakan langsung karena bisa berubah saat
 * DevTools switching device atau browser update. Gunakan hanya IP + user_id
 * serta timestamp untuk menjaga session tetap stabil saat perubahan viewport.
 */
function generate_fingerprint() {
    // Hanya gunakan IP dan user_id (stabil terhadap viewport change)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    
    // Tambahkan salt dari konfigurasi untuk keamanan
    $salt = defined('FINGERPRINT_SALT') ? FINGERPRINT_SALT : 'hadrah_default_salt_2024';
    
    return hash('sha256', $ip . $user_id . $salt);
}

/**
 * Fungsi: Generate lightweight fingerprint yang stabil
 * Cocok untuk mobile/desktop switching tanpa kehilangan session
 */
function generate_lightweight_fingerprint() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    
    return hash('sha256', $ip . $user_id);
}

/**
 * Fungsi: Cek peran user
 * Jika peran tidak sesuai, redirect
 */
function require_role($allowed_roles) {
    require_login();
    
    if (!in_array($_SESSION['peran'], $allowed_roles)) {
        // Redirect ke dashboard masing-masing
        if ($_SESSION['peran'] === 'admin') {
            header('Location: ../dashboard/admin.php');
        } elseif ($_SESSION['peran'] === 'pembina') {
            header('Location: ../dashboard/pembina.php');
        } else {
            header('Location: ../dashboard/anggota.php');
        }
        exit;
    }
}

/**
 * Fungsi: Set user session setelah login berhasil
 */
function set_user_session($user) {
    // Regenerate ID sebelum set data session
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['peran'] = $user['peran'];
    $_SESSION['created'] = time();
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['fingerprint'] = generate_fingerprint();
}

/**
 * Fungsi: Logout yang aman
 */
function secure_logout() {
    // Hapus semua data session
    $_SESSION = array();
    
    // Hapus cookie session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect ke login
    header('Location: auth/login.php?logout=success');
    exit;
}

// ============================================
// PENGUNAAN DI HALAMAN YANG MEMERLUKAN LOGIN
// ============================================

// Contoh penggunaan di dashboard/admin.php:
// require_login();
// regenerate_session();
// check_session_timeout();
// validate_session_fingerprint();

// ============================================
// AUTO-INCLUDE SETELAH SESSION START
// ============================================

// Jika user sudah login, jalankan semua cek keamanan
if (isset($_SESSION['user_id'])) {
    regenerate_session();
    check_session_timeout();
    validate_session_fingerprint();
}

