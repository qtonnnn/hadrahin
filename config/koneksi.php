<?php
// config/koneksi.php

require_once __DIR__ . '/config.php';

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hadrah';

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die('Koneksi database gagal');
}
