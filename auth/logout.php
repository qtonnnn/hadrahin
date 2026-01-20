<?php
/**
 * File: auth/logout.php
 * Fungsi: Logout yang aman dan menghapus semua session/cookie
 */

// Start session dulu untuk akses $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Method aman untuk logout
function secure_logout() {
    // 1. Hapus semua data session dari array $_SESSION
    $_SESSION = array();

    // 2. Hapus cookie session jika ada
    if (isset($_COOKIE[session_name()])) {
        // Ambil parameter cookie
        $params = session_get_cookie_params();
        
        // Hapus cookie dengan waktu lalu
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params['path'], 
            $params['domain'],
            $params['secure'], 
            $params['httponly']
        );
    }

    // 3. Destroy session sepenuhnya
    session_destroy();

    // 4. Hapus session storage di browser (jika ada)
    if (isset($_COOKIE['PHPSESSID'])) {
        setcookie('PHPSESSID', '', time() - 42000, '/');
    }

    // 5. Redirect ke login dengan pesan
    header('Location: login.php?logout=success');
    exit;
}

// Eksekusi logout
secure_logout();

