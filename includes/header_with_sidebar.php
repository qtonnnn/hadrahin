<?php
/**
 * Header with Sidebar - For Admin Dashboard Pages
 * Includes sidebar navigation and user info dropdown with hamburger toggle
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE id_user = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Get page title from current script
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'index' => 'Dashboard',
    'tambah' => 'Tambah User',
    'edit' => 'Edit User',
    'hapus' => 'Hapus User'
];
$page_title = $page_titles[$current_page] ?? ucfirst($current_page);

// Get module name for breadcrumb
$module_name = 'Manajemen User';
if (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) {
    $module_name = 'Dashboard';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/absen/') !== false) {
    $module_name = 'Absensi';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/acara/') !== false) {
    $module_name = 'Booking Acara';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/alat/') !== false) {
    $module_name = 'Inventaris Alat';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/keuangan/') !== false) {
    $module_name = 'Keuangan';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/dresscode/') !== false) {
    $module_name = 'Dresscode';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Hadrah App</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Admin CSS -->
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
                                <a href="<?= BASE_URL ?>/dashboard/admin.php" class="nav-link <?= $current_page === 'admin' ? 'active' : '' ?>">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/user/index.php" class="nav-link <?= in_array($current_page, ['index', 'edit', 'tambah', 'hapus']) ? 'active' : '' ?>">
                                    <i class="fas fa-users"></i>
                                    <span>Manajemen User</span>
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
                                <a href="<?= BASE_URL ?>/modules/alat/index.php" class="nav-link">
                                    <i class="fas fa-music"></i>
                                    <span>Inventaris Alat</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/keuangan/index.php" class="nav-link">
                                    <i class="fas fa-wallet"></i>
                                    <span>Keuangan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/dresscode/index.php" class="nav-link">
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
                            <h1 class="mb-1"><?= $page_title ?></h1>
                            <p class="text-muted mb-0 small"><?= $module_name ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-info-dropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($current_user['username'] ?? 'A', 0, 2)) ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?= htmlspecialchars(explode(' ', $current_user['nama_lengkap'] ?? $_SESSION['nama'])[0]) ?></div>
                                <div class="user-role"><?= ucfirst($_SESSION['peran'] ?? 'User') ?></div>
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
                    </script>

