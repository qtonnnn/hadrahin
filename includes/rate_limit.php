<?php
/**
 * File: includes/rate_limit.php
 * Fungsi: Rate limiting untuk mencegah brute force attack
 */

// Rate limit: 12x gagal = blokir 5 menit
// Setiap kelipatan 12, tambahkan 5 menit blokir

function get_rate_limit_key() {
    return 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
}

function check_rate_limit() {
    $key = get_rate_limit_key();
    $attempts_file = __DIR__ . '/../logs/login_attempts.dat';
    
    $data = [];
    if (file_exists($attempts_file)) {
        $content = file_get_contents($attempts_file);
        $data = json_decode($content, true) ?: [];
    }
    
    $now = time();
    
    // Bersihkan data lama (lebih dari 24 jam)
    foreach ($data as $ip => $record) {
        if (isset($record['last_attempt']) && ($now - $record['last_attempt'] > 86400)) {
            unset($data[$ip]);
        }
    }
    
    if (isset($data[$key])) {
        $record = $data[$key];
        
        // Jika sedang diblokir
        if (!empty($record['blocked_until']) && $now < $record['blocked_until']) {
            $remaining = $record['blocked_until'] - $now;
            file_put_contents($attempts_file, json_encode($data), LOCK_EX);
            return [
                'blocked' => true,
                'wait_seconds' => $remaining,
                'message' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$remaining} detik."
            ];
        }
    }
    
    file_put_contents($attempts_file, json_encode($data), LOCK_EX);
    return ['blocked' => false];
}

function record_failed_attempt() {
    $key = get_rate_limit_key();
    $attempts_file = __DIR__ . '/../logs/login_attempts.dat';
    
    $data = [];
    if (file_exists($attempts_file)) {
        $data = json_decode(file_get_contents($attempts_file), true) ?: [];
    }
    
    $now = time();
    
    if (!isset($data[$key])) {
        $data[$key] = [
            'count' => 0,
            'last_attempt' => $now,
            'blocked_until' => null
        ];
    }
    
    $data[$key]['count']++;
    $data[$key]['last_attempt'] = $now;
    
    // Aturan: 12x gagal = blokir 5 menit
    // Setiap kelipatan 12, tambahkan 5 menit blokir
    if ($data[$key]['count'] % 12 === 0) {
        $block_minutes = 5 * ($data[$key]['count'] / 12);
        $data[$key]['blocked_until'] = $now + ($block_minutes * 60);
    }
    
    file_put_contents($attempts_file, json_encode($data), LOCK_EX);
}

function reset_rate_limit() {
    $key = get_rate_limit_key();
    $attempts_file = __DIR__ . '/../logs/login_attempts.dat';
    
    if (file_exists($attempts_file)) {
        $data = json_decode(file_get_contents($attempts_file), true) ?: [];
        if (isset($data[$key])) {
            unset($data[$key]);
            file_put_contents($attempts_file, json_encode($data), LOCK_EX);
        }
    }
}

