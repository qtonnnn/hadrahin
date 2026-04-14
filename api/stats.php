<?php
/**
 * API Stats - Admin Dashboard Statistics
 * Endpoint untuk mengambil statistik dashboard secara real-time
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Fungsi untuk mengambil statistik user per bulan
function getUserGrowthStats($pdo) {
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
              FROM user 
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month ASC
              LIMIT 12";
    
    try {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Fungsi untuk menghitung total user
function getTotalUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Fungsi untuk menghitung user aktif
function getActiveUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as active FROM user WHERE status_aktif = 1");
        return $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Fungsi untuk menghitung user per role
function getUsersByRole($pdo) {
    $query = "SELECT peran, COUNT(*) as count FROM user GROUP BY peran";
    
    try {
        $stmt = $pdo->query($query);
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['peran']] = (int)$row['count'];
        }
        return $result;
    } catch (PDOException $e) {
        return [];
    }
}

// Fungsi untuk menghitung jadwal latihan bulan ini
function getJadwalBulanIni($pdo) {
    $bulan_ini = date('Y-m');
    $query = "SELECT COUNT(*) as total FROM jadwal_latihan 
              WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan_ini]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Fungsi untuk menghitung booking aktif
function getBookingAktif($pdo) {
    $query = "SELECT COUNT(*) as total FROM booking_acara 
              WHERE status IN ('menunggu', 'diterima')";
    
    try {
        $stmt = $pdo->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Fungsi untuk menghitung saldo kas
function getSaldoKas($pdo) {
    $query = "SELECT 
                COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
                COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
              FROM keuangan";
    
    try {
        $stmt = $pdo->query($query);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $saldo = $data['pemasukan'] - $data['pengeluaran'];
        return [
            'pemasukan' => (float)$data['pemasukan'],
            'pengeluaran' => (float)$data['pengeluaran'],
            'saldo' => (float)$saldo
        ];
    } catch (PDOException $e) {
        return ['pemasukan' => 0, 'pengeluaran' => 0, 'saldo' => 0];
    }
}

// Fungsi untuk mengambil aktivitas terbaru
function getRecentActivities($pdo, $limit = 5) {
    $activities = [];
    
    // Recent user registrations
    $query = "SELECT id_user, username, created_at, 'user' as type 
              FROM user ORDER BY created_at DESC LIMIT ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
            $activities[] = [
                'type' => 'user',
                'title' => 'User baru terdaftar: ' . htmlspecialchars($user['username']),
                'icon' => 'fa-user-plus',
                'icon_bg' => 'bg-primary',
                'created_at' => $user['created_at']
            ];
        }
    } catch (PDOException $e) {
        // Skip jika error
    }
    
    // Recent jadwal
    $query = "SELECT id_jadwal, tanggal, lokasi, created_at, 'jadwal' as type 
              FROM jadwal_latihan ORDER BY created_at DESC LIMIT ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $jadwal) {
            $activities[] = [
                'type' => 'jadwal',
                'title' => 'Jadwal latihan baru: ' . date('d/m/Y', strtotime($jadwal['tanggal'])),
                'icon' => 'fa-calendar-plus',
                'icon_bg' => 'bg-success',
                'created_at' => $jadwal['created_at']
            ];
        }
    } catch (PDOException $e) {
        // Skip jika error
    }
    
    // Recent booking
    $query = "SELECT id_booking, nama_acara, status, created_at, 'booking' as type 
              FROM booking_acara ORDER BY created_at DESC LIMIT ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $booking) {
            $status_labels = [
                'menunggu' => 'menunggu konfirmasi',
                'diterima' => 'diterima',
                'ditolak' => 'ditolak',
                'selesai' => 'selesai'
            ];
            $activities[] = [
                'type' => 'booking',
                'title' => 'Booking acara: ' . htmlspecialchars($booking['nama_acara']) . ' (' . ($status_labels[$booking['status']] ?? $booking['status']) . ')',
                'icon' => 'fa-calendar-check',
                'icon_bg' => 'bg-info',
                'created_at' => $booking['created_at']
            ];
        }
    } catch (PDOException $e) {
        // Skip jika error
    }
    
    // Sort by created_at desc dan limit
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($activities, 0, $limit);
}

// Fungsi untuk format waktu relatif
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 0) {
        return $diff->d . ' hari yang lalu';
    } elseif ($diff->h > 0) {
        return $diff->h . ' jam yang lalu';
    } elseif ($diff->i > 0) {
        return $diff->i . ' menit yang lalu';
    } else {
        return 'Baru saja';
    }
}

// Main response
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => [
        'total_users' => getTotalUsers($pdo),
        'active_users' => getActiveUsers($pdo),
        'users_by_role' => getUsersByRole($pdo),
        'jadwal_bulan_ini' => getJadwalBulanIni($pdo),
        'booking_aktif' => getBookingAktif($pdo),
        'keuangan' => getSaldoKas($pdo),
        'user_growth' => getUserGrowthStats($pdo),
        'recent_activities' => array_map(function($activity) {
            $activity['time_ago'] = timeAgo($activity['created_at']);
            return $activity;
        }, getRecentActivities($pdo))
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

