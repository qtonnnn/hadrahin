<?php
// config/config.php

// timezone
date_default_timezone_set('Asia/Jakarta');

// base url aplikasi
define('BASE_URL', 'http://localhost/hadrahin');

// mulai session sekali saja
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
