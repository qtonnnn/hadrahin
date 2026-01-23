<?php
/**
 * Dashboard Admin - Hadrah
 * Halaman dashboard untuk peran admin dengan sidebar dan statistik real-time
 */

// Start session dan include database
session_start();
require_once '../config/database.php';

// Include auth check untuk keamanan session
require_once '../includes/auth_check.php';

// Ambil data user dari database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE id_user = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil statistik untuk initial load
$stats = [];

// Total Users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active Users
$stmt = $pdo->query("SELECT COUNT(*) as active FROM user WHERE status_aktif = 1");
$stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// Users by Role
$query = "SELECT peran, COUNT(*) as count FROM user GROUP BY peran";
$stmt = $pdo->query($query);
$stats['users_by_role'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['users_by_role'][$row['peran']] = $row['count'];
}

// Jadwal Bulan Ini
$bulan_ini = date('Y-m');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jadwal_latihan WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?");
$stmt->execute([$bulan_ini]);
$stats['jadwal_bulan_ini'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Booking Aktif
$stmt = $pdo->query("SELECT COUNT(*) as total FROM booking_acara WHERE status IN ('menunggu', 'diterima')");
$stats['booking_aktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Keuangan
$stmt = $pdo->query("SELECT 
    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
FROM keuangan");
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['keuangan'] = [
    'pemasukan' => $data['pemasukan'],
    'pengeluaran' => $data['pengeluaran'],
    'saldo' => $data['pemasukan'] - $data['pengeluaran']
];

// User Growth Data (12 bulan terakhir)
$query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
          FROM user 
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month ASC
          LIMIT 12";
$stmt = $pdo->query($query);
$stats['user_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Activities
$recent_activities = [];

// Recent users
$stmt = $pdo->query("SELECT id_user, username, created_at FROM user ORDER BY created_at DESC LIMIT 3");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    $recent_activities[] = [
        'type' => 'user',
        'title' => 'User baru: ' . htmlspecialchars($u['username']),
        'icon' => 'fa-user-plus',
        'icon_bg' => 'bg-primary',
        'created_at' => $u['created_at']
    ];
}

// Recent jadwal
$stmt = $pdo->query("SELECT id_jadwal, tanggal, lokasi, created_at FROM jadwal_latihan ORDER BY created_at DESC LIMIT 2");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $j) {
    $recent_activities[] = [
        'type' => 'jadwal',
        'title' => 'Jadwal: ' . date('d/m/Y', strtotime($j['tanggal'])),
        'icon' => 'fa-calendar-plus',
        'icon_bg' => 'bg-success',
        'created_at' => $j['created_at']
    ];
}

// Recent booking
$stmt = $pdo->query("SELECT id_booking, nama_acara, status, created_at FROM booking_acara ORDER BY created_at DESC LIMIT 2");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $recent_activities[] = [
        'type' => 'booking',
        'title' => 'Booking: ' . htmlspecialchars($b['nama_acara']),
        'icon' => 'fa-calendar-check',
        'icon_bg' => 'bg-info',
        'created_at' => $b['created_at']
    ];
}

// Sort by date
usort($recent_activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activities = array_slice($recent_activities, 0, 5);

// Helper function untuk time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Hadrah App</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Admin CSS -->
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <div class="admin-layout">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <a href="<?= BASE_URL ?>/dashboard/admin.php" class="brand">
                        <i class="bi bi-house-heart-fill"></i>
                        <span>Hadrah App</span>
                    </a>
                </div>
                
                <nav class="sidebar-nav">
                    <div class="nav-section">
                        <div class="nav-section-title">Menu Utama</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/dashboard/admin.php" class="nav-link active">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/user/index.php" class="nav-link">
                                    <i class="fas fa-users"></i>
                                    <span>Manajemen User</span>
                                    <span class="badge bg-primary rounded-pill"><?= $stats['total_users'] ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/absen/index.php" class="nav-link">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Absensi</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/acara/index.php" class="nav-link">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Booking Acara</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <div class="nav-section-title">Manajemen</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-music"></i>
                                    <span>Inventaris Alat</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-wallet"></i>
                                    <span>Keuangan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tshirt"></i>
                                    <span>Dresscode</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <div class="nav-section-title">Sistem</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/user/edit.php?id=<?= $user_id ?>" class="nav-link">
                                    <i class="fas fa-user-cog"></i>
                                    <span>Profil Saya</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <main class="main-content" id="mainContent">
                <!-- Header -->
                <header class="content-header">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Hamburger Button -->
                        <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div>
                            <h1 class="mb-1">Dashboard Admin</h1>
                            <p class="text-muted mb-0 small">Kelola sistem informasi grup hadrah</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-info-dropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['username'] ?? 'A', 0, 2)) ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?= htmlspecialchars(explode(' ', $user['nama_lengkap'] ?? $_SESSION['nama'])[0]) ?></div>
                                <div class="user-role">Administrator</div>
                            </div>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/modules/user/edit.php?id=<?= $user_id ?>">
                                    <i class="fas fa-user me-2"></i> Profil Saya
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </header>
                
                <!-- Content Body -->
                <div class="content-body">
                    <!-- Welcome Card -->
                    <div class="welcome-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">Selamat Datang, <?= htmlspecialchars(explode(' ', $user['nama_lengkap'] ?? $_SESSION['nama'])[0]) ?>! 👋</h2>
                                <p class="mb-0 opacity-75">Ini adalah dashboard admin untuk mengelola seluruh sistem informasi grup hadrah.</p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <span class="badge bg-white text-primary px-3 py-2">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= date('d M Y') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Row -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-primary">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value" id="stat-total-users"><?= $stats['total_users'] ?></div>
                                <div class="stat-label">Total User</div>
                                <div class="stat-trend up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span id="stat-active-users"><?= $stats['active_users'] ?></span> aktif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-success">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-value" id="stat-jadwal"><?= $stats['jadwal_bulan_ini'] ?></div>
                                <div class="stat-label">Jadwal Latihan</div>
                                <div class="stat-trend">Bulan ini</div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-warning">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-value" id="stat-booking"><?= $stats['booking_aktif'] ?></div>
                                <div class="stat-label">Booking Aktif</div>
                                <div class="stat-trend">Menunggu/Diterima</div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card stat-info">
                                <div class="stat-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="stat-value" id="stat-saldo">Rp <?= number_format($stats['keuangan']['saldo'], 0, ',', '.') ?></div>
                                <div class="stat-label">Saldo Kas</div>
                                <div class="stat-trend">
                                    <small>Pemasukan: Rp <?= number_format($stats['keuangan']['pemasukan'], 0, ',', '.') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Content Grid -->
                    <div class="row g-4">
                        <!-- Chart Section -->
                        <div class="col-lg-8">
                            <div class="chart-card">
                                <div class="card-header">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <i class="fas fa-chart-line me-2 text-primary"></i>
                                            Pertumbuhan User
                                        </h5>
                                        <p class="card-subtitle mb-0">Grafik pendaftaran user 12 bulan terakhir</p>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                </div>
                                <div class="chart-container">
                                    <canvas id="userGrowthChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="col-lg-4">
                            <div class="chart-card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-clock me-2 text-primary"></i>
                                        Aktivitas Terbaru
                                    </h5>
                                    <p class="card-subtitle mb-0">Aktivitas sistem terkini</p>
                                </div>
                                <ul class="activity-list" id="recent-activities">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <li class="activity-item">
                                            <div class="activity-icon <?= $activity['icon_bg'] ?>">
                                                <i class="fas <?= $activity['icon'] ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?= htmlspecialchars($activity['title']) ?></div>
                                                <div class="activity-time">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= timeAgo($activity['created_at']) ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2 text-warning"></i>
                                        Aksi Cepat
                                    </h5>
                                </div>
                                <div class="p-3">
                                    <div class="quick-actions-grid">
                                        <a href="<?= BASE_URL ?>/modules/user/tambah.php" class="quick-action-btn">
                                            <i class="fas fa-user-plus text-primary"></i>
                                            <span>Tambah User</span>
                                        </a>
                                        <a href="#" class="quick-action-btn">
                                            <i class="fas fa-calendar-plus text-success"></i>
                                            <span>Buat Jadwal</span>
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/acara/index.php" class="quick-action-btn">
                                            <i class="fas fa-clipboard-check text-info"></i>
                                            <span>Cek Booking</span>
                                        </a>
                                        <a href="#" class="quick-action-btn">
                                            <i class="fas fa-file-invoice-dollar text-warning"></i>
                                            <span>Input Kas</span>
                                        </a>
                                        <a href="#" class="quick-action-btn">
                                            <i class="fas fa-boxes text-secondary"></i>
                                            <span>Stok Alat</span>
                                        </a>
                                        <a href="#" class="quick-action-btn">
                                            <i class="fas fa-tshirt text-danger"></i>
                                            <span>Dresscode</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Distribution (Role Breakdown) -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-users-cog me-2 text-primary"></i>
                                        Distribusi User
                                    </h5>
                                    <p class="card-subtitle mb-0">Berdasarkan peran</p>
                                </div>
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="roleDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="chart-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-1">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>
                                        Info Sistem
                                    </h5>
                                    <p class="card-subtitle mb-0">Ringkasan aplikasi</p>
                                </div>
                                <div class="p-3">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td><i class="fas fa-user-shield text-danger me-2"></i>Admin</td>
                                            <td class="text-end fw-bold"><?= $stats['users_by_role']['admin'] ?? 0 ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-user-tie text-warning me-2"></i>Pembina</td>
                                            <td class="text-end fw-bold"><?= $stats['users_by_role']['pembina'] ?? 0 ?></td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-user text-primary me-2"></i>Anggota</td>
                                            <td class="text-end fw-bold"><?= $stats['users_by_role']['anggota'] ?? 0 ?></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">Total User</td>
                                            <td class="text-end fw-bold"><?= $stats['total_users'] ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Sidebar Toggle Functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const hamburger = document.getElementById('hamburgerBtn');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            hamburger.classList.toggle('active');
            
            // Prevent body scroll when sidebar is open
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }

        // Close sidebar when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar.classList.contains('show')) {
                    toggleSidebar();
                }
            }
        });

        // Close sidebar when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1199.98) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const hamburger = document.getElementById('hamburgerBtn');
                
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                hamburger.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        
        // Prepare data from PHP
        const months = <?php 
            $labels = array_map(function($row) {
                $date = DateTime::createFromFormat('Y-m', $row['month']);
                return $date->format('M Y');
            }, $stats['user_growth']);
            echo json_encode($labels);
        ?>;
        
        const userCounts = <?php 
            $counts = array_map(function($row) {
                return (int)$row['count'];
            }, $stats['user_growth']);
            echo json_encode($counts);
        ?>;
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'User Baru',
                    data: userCounts,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: 11 }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Role Distribution Chart
        const roleCtx = document.getElementById('roleDistributionChart').getContext('2d');
        const roleData = <?php echo json_encode([
            'Admin' => $stats['users_by_role']['admin'] ?? 0,
            'Pembina' => $stats['users_by_role']['pembina'] ?? 0,
            'Anggota' => $stats['users_by_role']['anggota'] ?? 0
        ]); ?>;
        
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(roleData),
                datasets: [{
                    data: Object.values(roleData),
                    backgroundColor: [
                        '#dc3545',
                        '#ffc107',
                        '#0d6efd'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '65%'
            }
        });
        
        // Auto-refresh stats every 1 minute
        let refreshInterval;
        
        function refreshStats() {
            // Show loading state
            document.querySelectorAll('.stat-value').forEach(el => {
                el.style.opacity = '0.5';
            });
            
            fetch('<?= BASE_URL ?>/api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stat values
                        document.getElementById('stat-total-users').textContent = data.data.total_users;
                        document.getElementById('stat-active-users').textContent = data.data.active_users;
                        document.getElementById('stat-jadwal').textContent = data.data.jadwal_bulan_ini;
                        document.getElementById('stat-booking').textContent = data.data.booking_aktif;
                        
                        const saldo = data.data.keuangan.saldo;
                        document.getElementById('stat-saldo').textContent = 'Rp ' + saldo.toLocaleString('id-ID');
                        
                        // Update activities
                        const activitiesList = document.getElementById('recent-activities');
                        activitiesList.innerHTML = '';
                        
                        data.data.recent_activities.forEach(activity => {
                            const li = document.createElement('li');
                            li.className = 'activity-item';
                            li.innerHTML = `
                                <div class="activity-icon ${activity.icon_bg}">
                                    <i class="fas ${activity.icon}"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">${activity.title}</div>
                                    <div class="activity-time">
                                        <i class="far fa-clock me-1"></i>
                                        ${activity.time_ago}
                                    </div>
                                </div>
                            `;
                            activitiesList.appendChild(li);
                        });
                        
                        // Update user count badge in sidebar
                        const userBadge = document.querySelector('.nav-link .badge');
                        if (userBadge) {
                            userBadge.textContent = data.data.total_users;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                })
                .finally(() => {
                    // Remove loading state
                    document.querySelectorAll('.stat-value').forEach(el => {
                        el.style.opacity = '1';
                    });
                });
        }
        
        // Start auto-refresh (every 1 minute = 60000ms)
        refreshInterval = setInterval(refreshStats, 60000);
        
        // Store interval ID for potential cleanup
        window.adminRefreshInterval = refreshInterval;
    </script>
</body>
</html>

