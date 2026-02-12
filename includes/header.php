<?php
/**
 * Header with Sidebar - For Admin Dashboard Pages
 * Includes sidebar navigation and user info dropdown with hamburger toggle
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config for BASE_URL and $pdo
require_once __DIR__ . '/../config/database.php';

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

// Get current page and module info for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['PHP_SELF'];

// Determine if we're in a specific module
$is_user_module = strpos($current_path, '/modules/user/') !== false;
$is_jadwallatihan_module = strpos($current_path, '/modules/jadwallatihan/') !== false;
$is_absenlatihan_module = strpos($current_path, '/modules/absenlatihan/') !== false;
$is_dresscode_module = strpos($current_path, '/modules/dresscode/') !== false;
$is_keuangan_module = strpos($current_path, '/modules/keuangan/') !== false;
$is_alat_module = strpos($current_path, '/modules/alat/') !== false;
$is_acara_module = strpos($current_path, '/modules/acara/') !== false;
$is_profil_page = $current_page === 'edit' && $is_user_module && isset($_GET['id']) && $_GET['id'] == $user_id;

$page_titles = [
    'index' => 'Dashboard',
    'tambah' => 'Tambah',
    'edit' => 'Edit',
    'hapus' => 'Hapus'
];

// Module-specific titles
if (strpos($current_path, '/modules/user/') !== false) {
    $module_page_titles = [
        'index' => 'Manajemen User',
        'tambah' => 'Tambah User',
        'edit' => 'Edit User',
        'hapus' => 'Hapus User'
    ];
} elseif (strpos($current_path, '/modules/jadwallatihan/') !== false) {
    $module_page_titles = [
        'index' => 'Jadwal Latihan',
        'tambah' => 'Tambah Jadwal',
        'edit' => 'Edit Jadwal',
        'hapus' => 'Hapus Jadwal'
    ];
} elseif (strpos($current_path, '/modules/absenlatihan/') !== false) {
    $module_page_titles = [
        'index' => 'Absensi Latihan',
        'absen' => 'Absen Latihan',
        'export' => 'Export Absensi'
    ];
} elseif (strpos($current_path, '/modules/acara/') !== false) {
    $module_page_titles = [
        'index' => 'Booking Acara',
        'tambah' => 'Tambah Booking',
        'edit' => 'Edit Booking',
        'hapus' => 'Hapus Booking',
        'dokumentasi' => 'Dokumentasi',
        'update_status' => 'Update Status'
    ];
} elseif (strpos($current_path, '/modules/alat/') !== false) {
    $module_page_titles = [
        'index' => 'Inventaris Alat',
        'tambah' => 'Tambah Alat',
        'edit' => 'Edit Alat',
        'hapus' => 'Hapus Alat'
    ];
} elseif (strpos($current_path, '/modules/keuangan/') !== false) {
    $module_page_titles = [
        'index' => 'Keuangan',
        'tambah' => 'Tambah Transaksi',
        'edit' => 'Edit Transaksi',
        'hapus' => 'Hapus Transaksi'
    ];
} elseif (strpos($current_path, '/modules/dresscode/') !== false) {
    $module_page_titles = [
        'index' => 'Dresscode',
        'tambah' => 'Tambah Dresscode',
        'edit' => 'Edit Dresscode',
        'hapus' => 'Hapus Dresscode'
    ];
} else {
    $module_page_titles = [];
}

if (isset($module_page_titles[$current_page])) {
    $page_title = $module_page_titles[$current_page];
} else {
    $page_title = $page_titles[$current_page] ?? ucfirst($current_page);
}

// Get module name for breadcrumb
$module_name = 'Manajemen User';
if (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) {
    $module_name = 'Dashboard';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/absen/') !== false) {
    $module_name = 'Absensi';
} elseif (strpos($_SERVER['PHP_SELF'], '/modules/jadwallatihan/') !== false) {
    $module_name = 'Jadwal Latihan';
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
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Immediate redirect if not logged in (prevents cached page flash) -->
    <script>
    (function() {
        var checkSession = function() {
            fetch('<?= BASE_URL ?>/auth/check_session.php')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (!data.logged_in) {
                        window.location.href = '<?= BASE_URL ?>/auth/login.php?session_expired=1';
                    }
                })
                .catch(function() {
                    // On error, redirect for safety
                    window.location.href = '<?= BASE_URL ?>/auth/login.php?session_expired=1';
                });
        };
        
        // Check immediately
        checkSession();
        
        // Then check periodically
        setInterval(checkSession, 3000);
    })();
    </script>
    
    <title><?= $page_title ?> - Hadrah App</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="../assets/img/logo.png" type="image/png">
    <!-- Admin CSS - Inline Styles -->
    <style>
/* ==========================================================================
   Admin Dashboard Styles - Hadrah App
   ========================================================================== */

/* Global Box Sizing */
*, *::before, *::after {
    box-sizing: border-box;
}

html, body {
    overflow-x: hidden;
    max-width: 100%;
    margin: 0;
    padding: 0;
}

body {
    padding-top: 0 !important;
}

/* CSS Variables */
:root {
    --sidebar-width: 260px;
    --sidebar-bg: #ffffff;
    --sidebar-hover: #f8f9fa;
    --sidebar-active: #e9ecef;
    --text-primary: #212529;
    --text-secondary: #6c757d;
    --primary-color: #0d6efd;
    --success-color: #198754;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #0dcaf0;
    --border-color: #dee2e6;
    --shadow-sm: 0 .125rem .25rem rgba(0,0,0,.075);
    --shadow-md: 0 .5rem 1rem rgba(0,0,0,.15);
    --shadow-lg: 0 1rem 3rem rgba(0,0,0,.175);
    --transition: all 0.3s ease;
}

/* ==========================================================================
   Layout
   ========================================================================== */

.admin-wrapper {
    min-height: 100vh;
    background-color: #f5f6fa;
    overflow-x: hidden;
    max-width: 100vw;
    width: 100%;
}

.admin-layout {
    display: flex;
    min-height: 100vh;
}

/* ==========================================================================
   Hamburger Menu Button
   ========================================================================== */

.hamburger-btn {
    display: none;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: var(--transition);
}

.hamburger-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.hamburger-btn i {
    font-size: 1.25rem;
    color: #fff;
    transition: var(--transition);
}

.hamburger-btn.active {
    background: rgba(255, 255, 255, 0.25);
}

.hamburger-btn.active i::before {
    content: '\f00d';
}

/* ==========================================================================
   Sidebar Overlay
   ========================================================================== */

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

/* ==========================================================================
   Sidebar Styles
   ========================================================================== */

.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border-color);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
    transition: transform 0.3s ease, -webkit-transform 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar.collapsed {
    transform: translateX(-100%);
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
}

.sidebar-header .brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.25rem;
}

.sidebar-header .brand i {
    font-size: 1.5rem;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-section {
    margin-bottom: 1.5rem;
}

.nav-section-title {
    padding: 0.5rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.nav-item {
    margin: 0.25rem 0.75rem;
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    border-radius: 0.5rem;
    transition: var(--transition);
    font-weight: 500;
}

.nav-link i {
    width: 1.25rem;
    text-align: center;
    font-size: 1.1rem;
    color: var(--text-secondary);
    transition: var(--transition);
}

.nav-link:hover {
    background: var(--sidebar-hover);
}

.nav-link:hover i {
    color: var(--primary-color);
}

.nav-link.active {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
}

.nav-link.active i {
    color: var(--primary-color);
}

.nav-link .badge {
    margin-left: auto;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
}

/* ==========================================================================
   Main Content Area
   ========================================================================== */

.main-content {
    flex: 1;    
    margin-left: var(--sidebar-width);
    margin-top: 0;
    padding: 70px 0 0 0;
    width: calc(100% - var(--sidebar-width));
    max-width: 100%;
    overflow-x: hidden;
    transition: margin-left 0.3s ease;
}

.main-content.expanded {
    margin-left: 0;
}

.content-header {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    width: calc(100% - var(--sidebar-width));
    z-index: 1000;
    color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: left 0.3s ease, width 0.3s ease;
}

/* Responsive - ketika sidebar disembunyikan */
@media (max-width: 1199.98px) {
    .content-header {
        left: 0;
        width: 100%;
    }
    
    .main-content {
        margin-left: 0;
    }
}

.content-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #fff;
}

.content-header p {
    color: rgba(255, 255, 255, 0.8);
}

.content-body {
    padding: 2rem;
}

/* ==========================================================================
   Stat Cards
   ========================================================================== */

.stat-card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary-color);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-card.stat-primary::before { background: var(--primary-color); }
.stat-card.stat-success::before { background: var(--success-color); }
.stat-card.stat-warning::before { background: var(--warning-color); }
.stat-card.stat-info::before { background: var(--info-color); }
.stat-card.stat-danger::before { background: var(--danger-color); }

.stat-card .stat-icon {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-card.stat-primary .stat-icon {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
}

.stat-card.stat-success .stat-icon {
    background: rgba(25, 135, 84, 0.1);
    color: var(--success-color);
}

.stat-card.stat-warning .stat-icon {
    background: rgba(255, 193, 7, 0.1);
    color: #b38600;
}

.stat-card.stat-info .stat-icon {
    background: rgba(13, 202, 240, 0.1);
    color: #0aa2c0;
}

.stat-card.stat-danger .stat-icon {
    background: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.stat-card .stat-trend {
    font-size: 0.75rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stat-card .stat-trend.up {
    color: var(--success-color);
}

.stat-card .stat-trend.down {
    color: var(--danger-color);
}

/* ==========================================================================
   Charts Section
   ========================================================================== */

.chart-card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.chart-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.chart-card .card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.chart-card .card-subtitle {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.chart-container {
    position: relative;
    height: 300px;
}

/* ==========================================================================
   Quick Actions
   ========================================================================== */

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    text-decoration: none;
    color: var(--text-primary);
    transition: var(--transition);
    gap: 0.75rem;
}

.quick-action-btn:hover {
    border-color: var(--primary-color);
    background: rgba(13, 110, 253, 0.02);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.quick-action-btn i {
    font-size: 2rem;
    color: var(--primary-color);
}

.quick-action-btn span {
    font-weight: 500;
    font-size: 0.875rem;
    text-align: center;
}

/* ==========================================================================
   Recent Activities
   ========================================================================== */

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.activity-icon.bg-primary {
    background: rgba(13, 110, 253, 0.1);
    color: var(--primary-color);
}

.activity-icon.bg-success {
    background: rgba(25, 135, 84, 0.1);
    color: var(--success-color);
}

.activity-icon.bg-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #b38600;
}

.activity-icon.bg-info {
    background: rgba(13, 202, 240, 0.1);
    color: #0aa2c0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

/* ==========================================================================
   User Info Section
   ========================================================================== */

.user-info-dropdown {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 0.5rem;
    color: #fff;
    cursor: pointer;
    transition: var(--transition);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-info-dropdown:hover {
    background: rgba(255, 255, 255, 0.25);
}

.user-avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.user-details {
    text-align: left;
}

.user-name {
    font-weight: 600;
    font-size: 0.875rem;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* ==========================================================================
   Welcome Card
   ========================================================================== */

.welcome-card {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: #fff;
    padding: 2rem;
    border-radius: 0.75rem;
    margin-bottom: 2rem;
}

.welcome-card h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.welcome-card p {
    opacity: 0.9;
    margin: 0;
}

/* ==========================================================================
   Loading States
   ========================================================================== */

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 0.5rem;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-text {
    height: 1rem;
    margin-bottom: 0.5rem;
}

.skeleton-title {
    height: 1.5rem;
    width: 60%;
    margin-bottom: 1rem;
}

/* ==========================================================================
   Floating Action Button
   ========================================================================== */

.floating-btn {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);
    z-index: 1000;
    transition: all 0.3s ease;
    text-decoration: none;
}

.floating-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.5);
    color: #fff;
}

.floating-btn:active {
    transform: translateY(0) scale(0.98);
}

@media (max-width: 575.98px) {
    .floating-btn {
        bottom: 1.5rem;
        right: 1.5rem;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }

    /* User Card List - Mobile View */
    .user-card-list {
        padding: 0;
    }

    .user-card {
        background: #fff;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }

    .user-card:last-child {
        border-bottom: none;
    }

    .user-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .user-card-info {
        flex: 1;
        min-width: 0;
    }

    .user-card-info h5 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-info small {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-body {
        margin-bottom: 0.75rem;
    }

    .user-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 0;
        font-size: 0.875rem;
    }

    .user-card-footer {
        display: flex;
        gap: 0.5rem;
    }

    .user-card-footer .btn {
        flex: 1;
        justify-content: center;
    }
}

/* ==========================================================================
   Responsive Design
   ========================================================================== */

@media (max-width: 1199.98px) {
    /* Show hamburger button on tablet and mobile */
    .hamburger-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Fix: Sidebar harus di atas header */
    .sidebar {
        transform: translateX(-100%);
        height: 100vh;
        top: 0;
        display: flex;
        flex-direction: column;
        z-index: 1100;
    }

    .sidebar.show {
        transform: translateX(0);
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    }

    /* Make sidebar nav scrollable */
    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 2rem;
    }

    /* Fix: Gap pada mobile */
    .main-content {
        margin-left: 0;
        margin-top: 0;
        padding-top: 60px;
    }

    /* Fix: Header harus full width */
    .content-header {
        padding: 1rem 1.5rem;
        left: 0;
        width: 100%;
        z-index: 1050;
    }

    .content-body {
        padding: 1.5rem;
    }

    /* Sidebar brand on mobile - keep text visible */
    .sidebar-header {
        padding: 1.25rem 1.5rem;
        padding-top: max(1.25rem, env(safe-area-inset-top, 1.25rem));
    }

    .sidebar-header .brand {
        justify-content: flex-start;
        gap: 0.75rem;
    }

    .sidebar-header .brand span {
        display: inline !important;
    }

    .sidebar-header .brand i {
        font-size: 1.5rem;
    }

    /* Show nav section titles on mobile */
    .nav-section-title {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
    }

    /* Nav links on mobile - show icon and text */
    .nav-link {
        padding: 0.75rem 1rem;
        justify-content: flex-start;
    }

    .nav-link span {
        display: inline !important;
        font-size: 0.9rem;
    }

    .nav-link i {
        width: 1.5rem;
        font-size: 1.1rem;
    }

    .nav-item {
        margin: 0.25rem 0.75rem;
    }
}

@media (max-width: 991.98px) {
    .stat-card {
        margin-bottom: 1rem;
    }

    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767.98px) {
    /* Fix: Gap lebih kecil untuk mobile */
    .main-content {
        padding: 56px 0 0 0;
    }
    
    @media (max-width: 1199.98px) {
        .main-content {
            margin-top: 0;
        }
    }
    
    /* Fix: Header lebih pendek untuk mobile */
    .content-header {
        padding: 0.75rem 1rem;
        height: 56px;
        display: flex;
        align-items: center;
        flex-direction: row;
        gap: 1rem;
    }
    
    .content-header h1 {
        font-size: 1.1rem;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60%;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }

    .content-body {
        padding: 1rem;
    }

    .user-info-dropdown {
        padding: 0.4rem 0.75rem;
    }

    .user-details {
        display: none;
    }

    .welcome-card {
        padding: 1.5rem;
    }

    .welcome-card h2 {
        font-size: 1.25rem;
    }
}

@media (max-width: 575.98px) {
    /* Fix: Header lebih kompak */
    .content-header {
        padding: 0.5rem 0.75rem;
        height: 52px;
    }
    
    .main-content {
        padding-top: 52px;
    }
    
    .content-header h1 {
        font-size: 1rem;
        max-width: 50%;
    }
    
    .hamburger-btn {
        padding: 0.35rem 0.5rem;
        margin-right: 0.5rem;
    }
    
    .hamburger-btn i {
        font-size: 1rem;
    }
    
    .sidebar-header {
        padding: 1rem;
        padding-top: max(1rem, env(safe-area-inset-top, 1rem));
    }
    
    .stat-card .stat-value {
        font-size: 1.5rem;
    }

    .stat-card .stat-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }

    .chart-card {
        padding: 1rem;
    }

    .chart-card .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .chart-container {
        height: 250px;
    }

    .welcome-card {
        padding: 1.25rem;
    }

    .welcome-card h2 {
        font-size: 1.1rem;
    }

    .welcome-card p {
        font-size: 0.9rem;
    }

    /* User Index Page Responsive */
    .page-header-actions {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 1rem !important;
    }

    .page-header-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .search-form .input-group {
        flex-wrap: wrap !important;
        max-width: 100% !important;
    }

    .search-form .input-group-text {
        display: none;
    }

    .search-form .form-control {
        border-radius: 0.375rem !important;
        margin-bottom: 0.5rem;
    }

    .search-form .btn {
        width: 100%;
        border-radius: 0.375rem !important;
    }

    .search-form .btn-outline-secondary {
        display: none;
    }
    
    .floating-btn {
        bottom: 1.5rem;
        right: 1.5rem;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }

    /* User Card List - Mobile View */
    .user-card-list {
        padding: 0;
    }

    .user-card {
        background: #fff;
        border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }

    .user-card:last-child {
        border-bottom: none;
    }

    .user-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .user-card-info {
        flex: 1;
        min-width: 0;
    }

    .user-card-info h5 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-info small {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-card-body {
        margin-bottom: 0.75rem;
    }

    .user-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 0;
        font-size: 0.875rem;
    }

    .user-card-footer {
        display: flex;
        gap: 0.5rem;
    }

    .user-card-footer .btn {
        flex: 1;
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="admin-wrapper">
        <div class="admin-layout">
            <!-- Sidebar -->
            <?php include_once __DIR__ . '/sidebar.php'; ?>
            
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

