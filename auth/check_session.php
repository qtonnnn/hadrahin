<?php
/**
 * API: Check Session Status
 * Digunakan oleh JavaScript untuk mengecek apakah user masih login
 */

// Start session
session_start();

// Return JSON response
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? null,
        'peran' => $_SESSION['peran'] ?? null
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}

