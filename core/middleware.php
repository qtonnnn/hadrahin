<?php
// core/middleware.php

require_once __DIR__ . '/../config/config.php';

// cek sudah login atau belum
function cekLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// cek role user
function cekRole($roles = [])
{
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
}
