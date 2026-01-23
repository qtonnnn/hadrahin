<?php
// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Kelola User";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Count total users
if ($search) {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username LIKE ? OR nama_lengkap LIKE ?");
    $search_param = "%$search%";
    $count_stmt->execute([$search_param, $search_param]);
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM user");
}
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Fetch users
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username LIKE ? OR nama_lengkap LIKE ? ORDER BY id_user DESC LIMIT $limit OFFSET $offset");
    $stmt->execute([$search_param, $search_param]);
} else {
    $stmt = $pdo->query("SELECT * FROM user ORDER BY id_user DESC LIMIT $limit OFFSET $offset");
}
$users = $stmt->fetchAll();

// Handle delete
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ?");
        $stmt->execute([$id]);
    }
    header('Location: index.php?msg=hapus_sukes');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-users me-2"></i><?= $page_title ?></h2>
        </div>

        <!-- Floating Action Button Tambah User -->
        <div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1000;">
            <a href="tambah.php" class="btn btn-success btn-lg rounded-circle shadow-lg floating-btn" 
               style="width: 65px; height: 65px; display: flex; align-items: center; justify-content: center;"
               data-bs-toggle="tooltip" data-bs-placement="left" title="Tambah User Baru">
                <i class="fas fa-plus fa-xl"></i>
            </a>
        </div>

        <style>
        .floating-btn {
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
        }
        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
        }
        .floating-btn:active {
            transform: scale(0.95);
        }
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        </style>

        <?php if (isset($_GET['msg'])): ?>
            <?php
            $toastClass = '';
            $toastMessage = '';
            
            if ($_GET['msg'] === 'tambah_sukes') {
                $toastClass = 'bg-success';
                $toastMessage = 'User baru berhasil ditambahkan!';
            } elseif ($_GET['msg'] === 'edit_sukes') {
                $toastClass = 'bg-success';
                $toastMessage = 'Data user berhasil diperbarui!';
            } elseif ($_GET['msg'] === 'hapus_sukes') {
                $toastClass = 'bg-success';
                $toastMessage = 'User berhasil dihapus!';
            } elseif ($_GET['msg'] === 'error') {
                $toastClass = 'bg-danger';
                $toastMessage = 'Terjadi kesalahan!';
            }
            
            if ($toastMessage):
            ?>
            <div class="position-fixed top-0 start-50 translate-middle-x mt-5" style="z-index: 9999">
                <div id="liveToast" class="toast align-items-center text-white <?= $toastClass ?> border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i><?= $toastMessage ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var toastEl = document.getElementById('liveToast');
                    var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                    toast.show();
                    
                    // Remove msg from URL without reload
                    setTimeout(function() {
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }, 3100);
                });
            </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="mb-4">
            <form method="GET" class="d-flex">
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari username atau nama lengkap..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <?php if ($search): ?>
                        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle bg-white rounded-3 shadow-sm">
                <thead class="table-light">
                    <tr>
                        <th width="50" class="text-center">No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>No HP</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block text-secondary"></i>
                                <?= $search ? 'Tidak ada user yang ditemukan.' : 'Belum ada data user.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $i => $user): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                <td><?= $user['no_hp'] ? htmlspecialchars($user['no_hp']) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <?php
                                    $peran_class = match($user['peran']) {
                                        'admin' => 'bg-danger',
                                        'pembina' => 'bg-warning text-dark',
                                        default => 'bg-primary'
                                    };
                                    ?>
                                    <span class="badge <?= $peran_class ?>">
                                        <i class="fas fa-user-tag me-1"></i><?= htmlspecialchars(ucfirst($user['peran'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $user['status_aktif'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $user['status_aktif'] ? '<i class="fas fa-check-circle me-1"></i>Aktif' : '<i class="fas fa-times-circle me-1"></i>Tidak Aktif' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id_user'] != $_SESSION['user_id']): ?>
                                        <a href="hapus.php?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="text-center text-muted small mt-2 mb-4">
                Menampilkan <?= count($users) ?> dari <?= $total_users ?> user
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>

