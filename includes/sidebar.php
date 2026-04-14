-- Active: 1771335006776@@127.0.0.1@3306@hadrahin
<?php
/**
 * Sidebar Component - Navigation Sidebar
 * Dipisahkan dari header untuk modularitas kode
 */

// Sidebar sudah di-include di header_with_sidebar.php, tidak perlu logika tambahan
// Variabel yang diharapkan: $current_page, $current_path, $current_user, $user_id, $is_user_module, $is_jadwallatihan_module, $is_absenlatihan_module, $is_profil_page
?>

<!-- Sidebar CSS -->
<style>
/* ==========================================================================
   Sidebar Styles
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

.sidebar {
    width: 260px;
    background: #ffffff;
    border-right: 1px solid #dee2e6;
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
    border-bottom: 1px solid #dee2e6;
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
    color: #6c757d;
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
    color: #212529;
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-link i {
    width: 1.25rem;
    text-align: center;
    font-size: 1.1rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: #f8f9fa;
}

.nav-link:hover i {
    color: #0d6efd;
}

.nav-link.active {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.nav-link.active i {
    color: #0d6efd;
}

.nav-link .badge {
    margin-left: auto;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
}

/* Responsive - Sidebar on mobile */
@media (max-width: 1199.98px) {
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

    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 2rem;
    }

    .nav-section-title {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
    }

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
</style>

            <!-- Sidebar -->
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
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
                                <a href="<?= BASE_URL ?>/modules/user/index.php" class="nav-link <?= $is_user_module && !$is_profil_page ? 'active' : '' ?>">
                                    <i class="fas fa-users"></i>
                                    <span>Manajemen User</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/jadwallatihan/index.php" class="nav-link <?= $is_jadwallatihan_module ? 'active' : '' ?>">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Jadwal Latihan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/absenlatihan/index.php" class="nav-link <?= isset($is_absenlatihan_module) && $is_absenlatihan_module ? 'active' : '' ?>">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Absensi Latihan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/acara/index.php" class="nav-link <?= isset($is_acara_module) && $is_acara_module ? 'active' : '' ?>">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>BookingAcara</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <div class="nav-section-title">Manajemen</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/alat/index.php" class="nav-link <?= isset($is_alat_module) && $is_alat_module ? 'active' : '' ?>">
                                    <i class="fas fa-music"></i>
                                    <span>Inventaris Alat</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/keuangan/index.php" class="nav-link <?= isset($is_keuangan_module) && $is_keuangan_module ? 'active' : '' ?>">
                                    <i class="fas fa-wallet"></i>
                                    <span>Keuangan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/modules/dresscode/index.php" class="nav-link <?= isset($is_dresscode_module) && $is_dresscode_module ? 'active' : '' ?>">
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
                                <a href="<?= BASE_URL ?>/modules/user/edit.php?id=<?= $user_id ?>" class="nav-link <?= $is_profil_page ? 'active' : '' ?>">
                                    <i class="fas fa-user-cog"></i>
                                    <span>Profil Admin</span>
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

