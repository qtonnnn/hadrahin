<?php
/**
 * Sidebar Component - Navigation Sidebar
 * Dipisahkan dari header untuk modularitas kode
 */

// Sidebar sudah di-include di header_with_sidebar.php, tidak perlu logika tambahan
// Variabel yang diharapkan: $current_page, $current_path, $current_user, $user_id, $is_user_module, $is_jadwallatihan_module, $is_absenlatihan_module, $is_profil_page
?>
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
                                <a href="<?= BASE_URL ?>/modules/user/edit.php?id=<?= $user_id ?>" class="nav-link <?= $is_profil_page ? 'active' : '' ?>">
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

