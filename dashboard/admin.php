<?php
/**
 * Dashboard Admin - Hadrah
 * Halaman dashboard untuk peran admin dengan sidebar dan statistik real-time
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

// Start session dan include database
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/error_handler.php';
ErrorHandler::init();
require_once __DIR__ . '/../includes/auth_check.php';

// Ambil data user dari database
$user_id = $_SESSION['user_id'] ?? null;
if (empty($user_id)) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}
$query = "SELECT * FROM user WHERE id_user = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Set page title untuk header
$page_title = "Dashboard Admin";
$module_name = "Dashboard";

// Ambil statistik untuk initial load
$stats = [];

// Total Users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Check if there's a training schedule today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM jadwal_latihan WHERE tanggal = ? AND status = 'direncanakan' ORDER BY jam_mulai ASC");
$stmt->execute([$today]);
$jadwal_hari_ini = $stmt->fetchAll();
$stats['has_jadwal_hari_ini'] = !empty($jadwal_hari_ini);
$stats['jadwal_hari_ini_list'] = $jadwal_hari_ini;

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

// Absensi Statistik
$stmt = $pdo->query("SELECT
    COUNT(*) as total_absensi,
    SUM(CASE WHEN status_hadir = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN status_hadir = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN status_hadir = 'alpa' THEN 1 ELSE 0 END) as alpa
FROM absen_latihan");
$absensi_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['absensi'] = [
    'total' => $absensi_stats['total_absensi'] ?? 0,
    'hadir' => $absensi_stats['hadir'] ?? 0,
    'izin' => $absensi_stats['izin'] ?? 0,
    'alpa' => $absensi_stats['alpa'] ?? 0
];

// ============================================
// KEUANGAN STATISTICS
// ============================================
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

// Bulan ini
$bulan_ini_start = date('Y-m-01');
$bulan_ini_end = date('Y-m-t');
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
    COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
FROM keuangan WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$bulan_ini_start, $bulan_ini_end]);
$data_bulan_ini = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['keuangan']['bulan_ini_pemasukan'] = $data_bulan_ini['pemasukan'];
$stats['keuangan']['bulan_ini_pengeluaran'] = $data_bulan_ini['pengeluaran'];
$stats['keuangan']['bulan_ini_saldo'] = $data_bulan_ini['pemasukan'] - $data_bulan_ini['pengeluaran'];

// Data Tren 6 Bulan Terakhir (untuk chart)
$tren_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-{$i} months"));
    $bulan_awal = $bulan . '-01';
    $bulan_akhir = date('Y-m-t', strtotime($bulan));
    
    $stmt = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as pengeluaran
    FROM keuangan WHERE tanggal BETWEEN ? AND ?");
    $stmt->execute([$bulan_awal, $bulan_akhir]);
    $data_tren = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tren_data[] = [
        'bulan' => $bulan,
        'label' => date('M y', strtotime($bulan)),
        'pemasukan' => (float)$data_tren['pemasukan'],
        'pengeluaran' => (float)$data_tren['pengeluaran'],
        'saldo' => (float)$data_tren['pemasukan'] - (float)$data_tren['pengeluaran']
    ];
}
$stats['keuangan']['tren_6_bulan'] = $tren_data;

// ============================================
// DRESSCODE STATISTICS
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) as total FROM dresscode");
$stats['dresscode']['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$stmt = $pdo->query("SELECT COUNT(*) as aktif FROM dresscode WHERE status = 'aktif'");
$stats['dresscode']['aktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['aktif'];
$stmt = $pdo->query("SELECT COUNT(*) as nonaktif FROM dresscode WHERE status = 'nonaktif'");
$stats['dresscode']['nonaktif'] = $stmt->fetch(PDO::FETCH_ASSOC)['nonaktif'];

// ============================================
// ALAT (INVENTORY) STATISTICS
// ============================================
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    COALESCE(SUM(jumlah_baik), 0) as total_baik,
    COALESCE(SUM(jumlah_rusak), 0) as total_rusak
FROM alat");
$alat_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['alat'] = [
    'total' => $alat_stats['total'],
    'total_baik' => $alat_stats['total_baik'],
    'total_rusak' => $alat_stats['total_rusak'],
    'total_semua' => $alat_stats['total_baik'] + $alat_stats['total_rusak']
];

// ============================================
// RECENT ACTIVITIES
// ============================================
$recent_activities = [];

// Recent users
$stmt = $pdo->query("SELECT id_user, username, created_at FROM user ORDER BY created_at DESC LIMIT 3");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    $recent_activities[] = [
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
        'title' => 'Jadwal: ' . date('d/m/Y', strtotime($j['tanggal'])) . ' - ' . htmlspecialchars($j['lokasi']),
        'icon' => 'fa-calendar-plus',
        'icon_bg' => 'bg-success',
        'created_at' => $j['created_at']
    ];
}

// Recent booking
$stmt = $pdo->query("SELECT id_booking, nama_acara, status, created_at FROM booking_acara ORDER BY created_at DESC LIMIT 2");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $recent_activities[] = [
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

// Include header dengan sidebar (like other pages)
include __DIR__ . '/../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Welcome Card -->
<div class="welcome-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="mb-2">Selamat Datang, <?= htmlspecialchars(explode(' ', ($user['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Admin'))[0]) ?>! 👋</h2>
            <p class="mb-0 opacity-75">Kelola seluruh sistem informasi grup hadrah dengan mudah dan efisien.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="d-flex flex-column align-items-md-end gap-2">
                <span class="badge bg-white text-primary px-3 py-2">
                    <i class="fas fa-clock me-1"></i>
                    <?= date('d M Y') ?>
                </span>
                <?php if ($stats['has_jadwal_hari_ini']): ?>
                    <?php foreach ($stats['jadwal_hari_ini_list'] as $jadwal): ?>
                        <a href="<?= BASE_URL ?>/modules/absenlatihan/absen.php?id=<?= $jadwal['id_jadwal'] ?>" class="btn btn-warning btn-sm fw-bold text-dark">
                            <i class="fas fa-clipboard-check me-1"></i>
                            ABSEN - <?= htmlspecialchars($jadwal['lokasi']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Total User</div>
            <div class="stat-trend up">
                <i class="fas fa-arrow-up"></i>
                <?= $stats['active_users'] ?> aktif
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-value"><?= $stats['jadwal_bulan_ini'] ?></div>
            <div class="stat-label">Jadwal Latihan</div>
            <div class="stat-trend">Bulan ini</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-value"><?= $stats['booking_aktif'] ?></div>
            <div class="stat-label">Booking Aktif</div>
            <div class="stat-trend">Menunggu / Diterima</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-value">Rp <?= number_format($stats['keuangan']['saldo'], 0, ',', '.') ?></div>
            <div class="stat-label">Saldo Kas</div>
            <div class="stat-trend">
                <small>Pemasukan: Rp <?= number_format($stats['keuangan']['pemasukan'], 0, ',', '.') ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <!-- Absensi Summary -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Statistik Absensi
                </h5>
            </div>
            <div class="card-body">
                <div class="summary-item">
                    <span class="summary-label">Total Absensi</span>
                    <span class="summary-value"><?= $stats['absensi']['total'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Hadir</span>
                    <span class="summary-value text-success"><?= $stats['absensi']['hadir'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Izin</span>
                    <span class="summary-value text-warning"><?= $stats['absensi']['izin'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Alpa</span>
                    <span class="summary-value text-danger"><?= $stats['absensi']['alpa'] ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dresscode Summary -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-purple text-white py-3" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tshirt me-2"></i>
                    Statistik Dresscode
                </h5>
            </div>
            <div class="card-body">
                <div class="summary-item">
                    <span class="summary-label">Total Dresscode</span>
                    <span class="summary-value"><?= $stats['dresscode']['total'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Aktif</span>
                    <span class="summary-value text-success"><?= $stats['dresscode']['aktif'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Nonaktif</span>
                    <span class="summary-value text-secondary"><?= $stats['dresscode']['nonaktif'] ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alat Summary -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-orange text-white py-3" style="background: linear-gradient(135deg, #fd7e14, #e55c00);">
                <h5 class="card-title mb-0">
                    <i class="fas fa-music me-2"></i>
                    Statistik Alat
                </h5>
            </div>
            <div class="card-body">
                <div class="summary-item">
                    <span class="summary-label">Jenis Alat</span>
                    <span class="summary-value"><?= $stats['alat']['total'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Kondisi Baik</span>
                    <span class="summary-value text-success"><?= $stats['alat']['total_baik'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Kondisi Rusak</span>
                    <span class="summary-value text-danger"><?= $stats['alat']['total_rusak'] ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Unit</span>
                    <span class="summary-value"><?= $stats['alat']['total_semua'] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Activities -->
<div class="row g-4 mb-4">
    <!-- Keuangan Chart -->
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="card-header">
                <div>
                    <h5 class="card-title mb-1">
                        <i class="fas fa-chart-line me-2 text-success"></i>
                        Tren Keuangan
                    </h5>
                    <p class="card-subtitle mb-0">6 bulan terakhir</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="keuanganChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <div class="card-header">
                <div>
                    <h5 class="card-title mb-1">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        Aktivitas Terbaru
                    </h5>
                    <p class="card-subtitle mb-0">5 aktivitas terakhir</p>
                </div>
            </div>
            <ul class="activity-list">
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
<div class="row">
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
                    <a href="<?= BASE_URL ?>/modules/jadwallatihan/tambah.php" class="quick-action-btn">
                        <i class="fas fa-calendar-plus text-success"></i>
                        <span>Buat Jadwal</span>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/keuangan/tambah.php" class="quick-action-btn">
                        <i class="fas fa-file-invoice-dollar text-warning"></i>
                        <span>Input Kas</span>
                    </a>
                    <a href="<?= BASE_URL ?>/modules/alat/tambah.php" class="quick-action-btn">
                        <i class="fas fa-box text-secondary"></i>
                        <span>Tambah Alat</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.summary-value {
    font-weight: 600;
}
</style>

<script>
    // Keuangan Chart
    const keuanganCtx = document.getElementById('keuanganChart').getContext('2d');
    const trenData = <?php echo json_encode($stats['keuangan']['tren_6_bulan']); ?>;
    
    new Chart(keuanganCtx, {
        type: 'line',
        data: {
            labels: trenData.map(item => item.label),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: trenData.map(item => item.pemasukan),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Pengeluaran',
                    data: trenData.map(item => item.pengeluaran),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
