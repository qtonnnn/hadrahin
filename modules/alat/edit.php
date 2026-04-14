<?php
/**
 * Edit Alat - Form Pengeditan Alat dengan sidebar layout
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Edit Alat";

$id_alat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_alat <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM alat WHERE id_alat = ?");
$stmt->execute([$id_alat]);
$alat = $stmt->fetch();

if (!$alat) {
    header('Location: index.php?msg=error');
    exit;
}

$total_alat_existing = $alat['jumlah_baik'] + $alat['jumlah_rusak'];

$stmt_pengguna = $pdo->prepare("
    SELECT ap.*, u.nama_lengkap, u.no_hp 
    FROM alat_pengguna ap 
    JOIN user u ON ap.id_user = u.id_user 
    WHERE ap.id_alat = ? 
    ORDER BY ap.status DESC, u.nama_lengkap ASC
");
$stmt_pengguna->execute([$id_alat]);
$pengguna_existing = $stmt_pengguna->fetchAll();

$stmt_user = $pdo->query("SELECT id_user, nama_lengkap, no_hp FROM user WHERE status_aktif = 1 AND peran = 'anggota' ORDER BY nama_lengkap ASC");
$user_list = $stmt_user->fetchAll();

$pengguna_ids = array_column(array_filter($pengguna_existing, function($p) {
    return $p['status'] == 'aktif';
}), 'id_user');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_alat = trim($_POST['nama_alat'] ?? '');
    $total_alat = (int)($_POST['total_alat'] ?? 1);
    $jumlah_baik = (int)($_POST['jumlah_baik'] ?? 0);
    $jumlah_rusak = (int)($_POST['jumlah_rusak'] ?? 0);
    // Ambil pengguna dari hidden input JSON
    $pengguna_json = $_POST['pengguna'] ?? '';
    $pengguna_terpilih = !empty($pengguna_json) ? json_decode($pengguna_json, true) : [];
    
    // Fallback jika pengguna_checkbox[] ada (misal form resubmit)
    if (empty($pengguna_terpilih) && isset($_POST['pengguna_checkbox'])) {
        $pengguna_terpilih = array_map('intval', $_POST['pengguna_checkbox']);
    }
    
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    $errors = [];
    
    if (empty($nama_alat)) {
        $errors[] = "Nama alat wajib diisi";
    }
    
    if ($total_alat < 1) {
        $errors[] = "Total alat minimal 1";
    }
    
    if ($jumlah_baik < 0) {
        $errors[] = "Jumlah baik tidak boleh negatif";
    }
    
    if ($jumlah_rusak < 0) {
        $errors[] = "Jumlah rusak tidak boleh negatif";
    }
    
    if (($jumlah_baik + $jumlah_rusak) > $total_alat) {
        $errors[] = "Jumlah baik + rusak tidak boleh melebihi total alat";
    }
    
    $stmt = $pdo->prepare("SELECT id_alat FROM alat WHERE nama_alat = ? AND id_alat != ?");
    $stmt->execute([$nama_alat, $id_alat]);
    if ($stmt->fetch()) {
        $errors[] = "Nama alat sudah digunakan oleh alat lain";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $user_id = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("
                UPDATE alat
                SET nama_alat = ?, jumlah_baik = ?, jumlah_rusak = ?, keterangan = ?,
                    updated_at = NOW(), user_modified = ?
                WHERE id_alat = ?
            ");
            $stmt->execute([$nama_alat, $jumlah_baik, $jumlah_rusak, $keterangan, $user_id, $id_alat]);
            
            $pengguna_terpilih = array_map('intval', $pengguna_terpilih);
            $existing_ids = array_map('intval', $pengguna_ids);
            
            $new_pengguna = array_diff($pengguna_terpilih, $existing_ids);
            if (!empty($new_pengguna)) {
                $tanggal_diberikan = date('Y-m-d');
                $stmt_pengguna = $pdo->prepare("
                    INSERT INTO alat_pengguna (id_alat, id_user, tanggal_diberikan, status, keterangan, user_record) 
                    VALUES (?, ?, ?, 'aktif', ?, ?)
                ");
                foreach ($new_pengguna as $id_user) {
                    $stmt_pengguna->execute([$id_alat, $id_user, $tanggal_diberikan, $keterangan, $user_id]);
                }
            }
            
            $removed_pengguna = array_diff($existing_ids, $pengguna_terpilih);
            if (!empty($removed_pengguna)) {
                $placeholders = implode(',', array_fill(0, count($removed_pengguna), '?'));
                $stmt = $pdo->prepare("
                    UPDATE alat_pengguna 
                    SET status = 'dikembalikan', updated_at = NOW(), user_modified = ?
                    WHERE id_alat = ? AND id_user IN ($placeholders) AND status = 'aktif'
                ");
                $params = array_merge([$user_id, $id_alat], $removed_pengguna);
                $stmt->execute($params);
            }
            
            $pdo->commit();
            header('Location: index.php?msg=edit_sukes');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <a href="index.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i><?= $page_title ?></h4>
                </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
<form method="POST" autocomplete="off" id="alatForm">
                    <input type="hidden" name="pengguna" id="penggunaSelected" value='<?= htmlspecialchars(json_encode($pengguna_terpilih ?: $pengguna_ids)) ?>'>
                    <h6 class="text-primary mb-3"><i class="fas fa-music me-1"></i> Informasi Alat</h6>
                    
                    <div class="mb-3">
                        <label for="nama_alat" class="form-label">Nama Alat <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-music"></i></span>
                            <input type="hidden" name="exclude_id" id="excludeId" value="<?= $id_alat ?>">
                            <input type="text" class="form-control" id="nama_alat" name="nama_alat" 
                                   value="<?= htmlspecialchars($_POST['nama_alat'] ?? $alat['nama_alat']) ?>" 
                                   placeholder="Nama alat" required
                                   onblur="validateAlatNameEdit()" oninput="clearAlatValidation()">
                            <span class="input-group-text" id="alatNameIcon"></span>
                        </div>
                        <small class="text-muted" id="alatNameMsg">Minimal 2 karakter, akan divalidasi saat keluar dari field</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="total_alat" class="form-label fw-bold text-primary">Total Alat <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-primary" onclick="adjustTotal(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center fw-bold" id="total_alat" 
                                       name="total_alat" value="<?= $_POST['total_alat'] ?? $total_alat_existing ?>" min="1" required
                                       onchange="validateTotal()" oninput="validateTotal()">
                                <button type="button" class="btn btn-outline-primary" onclick="adjustTotal(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-muted">Total unit alat</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="jumlah_baik" class="form-label">Jumlah Baik <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-success" onclick="adjustBaik(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center" id="jumlah_baik" 
                                       name="jumlah_baik" value="<?= htmlspecialchars($_POST['jumlah_baik'] ?? $alat['jumlah_baik']) ?>" min="0" required
                                       onchange="validateQuantity()" oninput="validateQuantity()">
                                <button type="button" class="btn btn-outline-success" onclick="adjustBaik(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-success">Tersisa: <span id="sisaBaik">-</span></small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="jumlah_rusak" class="form-label">Jumlah Rusak</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-danger" onclick="adjustRusak(-1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center" id="jumlah_rusak" 
                                       name="jumlah_rusak" value="<?= htmlspecialchars($_POST['jumlah_rusak'] ?? $alat['jumlah_rusak']) ?>" min="0"
                                       onchange="validateQuantity()" oninput="validateQuantity()">
                                <button type="button" class="btn btn-outline-danger" onclick="adjustRusak(1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small class="text-danger">Maks: <span id="maksRusak">-</span></small>
                        </div>
                    
                    <div class="alert alert-info py-2 mb-3">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Total: <strong id="summaryTotal"><?= $total_alat_existing ?></strong> | 
                            Baik: <strong id="summaryBaik" class="text-success"><?= $alat['jumlah_baik'] ?></strong> | 
                            Rusak: <strong id="summaryRusak" class="text-danger"><?= $alat['jumlah_rusak'] ?></strong> | 
                            Sisa: <strong id="summarySisa" class="text-warning"><?= $total_alat_existing - $alat['jumlah_baik'] - $alat['jumlah_rusak'] ?></strong>
                        </small>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="text-primary mb-3"><i class="fas fa-users-cog me-1"></i> Kelola Pengguna</h6>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary" onclick="openPenggunaModal()">
                            <i class="fas fa-users-cog me-1"></i> 
                            Pilih Anggota <span class="badge bg-primary ms-1" id="selectedCount"><?= count($pengguna_terpilih ?: $pengguna_ids) ?></span> aktif
                        </button>
                        <div class="form-text">Pengguna yang dihapus akan ditandai sebagai "Dikembalikan"</div>
                    
                    <div id="selectedPreview" class="d-flex flex-wrap gap-2 mb-3" style="display: none;"></div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="1"
                                          placeholder="Catatan..."><?= htmlspecialchars($_POST['keterangan'] ?? $alat['keterangan'] ?? '') ?></textarea>
                            </div>
                    </div>
                    
                    <?php if (!empty($pengguna_existing)): ?>
                        <div class="card bg-light mt-3">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="fas fa-history me-1"></i> Riwayat Pengguna</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pengguna_existing as $p): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-user text-muted me-1"></i>
                                                        <?= htmlspecialchars($p['nama_lengkap']) ?>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($p['tanggal_diberikan'])) ?></td>
                                                    <td>
                                                        <span class="badge <?= $p['status'] == 'aktif' ? 'bg-info' : 'bg-secondary' ?>">
                                                            <?= ucfirst($p['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-lg" onclick="if(validateAlatNameEdit()) { showConfirmModal(); }">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
    </div>

<!-- Modal Pilih Anggota - Revisi Desain -->
<div class="modal fade" id="penggunaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-plus me-2"></i>Pilih Anggota
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body p-0">
                <!-- Search Bar -->
                <div class="p-4 border-bottom bg-light">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" 
                               id="searchPengguna" 
                               placeholder="Cari anggota berdasarkan nama atau nomor HP..."
                               onkeyup="filterPengguna()">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Action Bar -->
                <div class="p-3 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       id="selectAllPengguna" 
                                       onchange="toggleAllPengguna()"
                                       style="width: 1.2em; height: 1.2em;">
                                <label class="form-check-label fw-semibold ms-2" for="selectAllPengguna">
                                    Pilih Semua
                                </label>
                            </div>
                            <span class="badge bg-primary ms-3" id="selectedCountBadge">0</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearAllPengguna()">
                            <i class="fas fa-times-circle me-1"></i>Hapus Semua
                        </button>
                    </div>
                </div>
                
                <!-- Member List -->
                <div class="pengguna-list-container" style="max-height: 400px; overflow-y: auto;">
                    <div class="p-3 text-center" id="loadingPengguna" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat data anggota...</p>
                    </div>
                    
                    <div id="penggunaList">
                        <?php if (empty($user_list)): ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-users-slash fa-3x text-muted"></i>
                                </div>
                                <h6 class="text-muted">Tidak ada anggota tersedia</h6>
                                <p class="text-muted small">Semua anggota sudah tidak aktif</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($user_list as $index => $user): ?>
                                <div class="pengguna-card" data-id="<?= $user['id_user'] ?>">
                                    <div class="card mb-2 border-hover">
                                        <div class="card-body py-3">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                    <input class="form-check-input pengguna-checkbox" 
                                                               type="checkbox" 
                                                               name="pengguna_checkbox[]" 
                                                               value="<?= $user['id_user'] ?>" 
                                                               id="pengguna_<?= $user['id_user'] ?>"
                                                               onchange="updatePenggunaSelection()"
                                                               style="width: 1.2em; height: 1.2em;"
                                                               <?= (in_array($user['id_user'], ($pengguna_terpilih ?: $pengguna_ids))) ? 'checked' : '' ?>>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="member-avatar bg-primary bg-opacity-10 text-primary">
                                                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="d-flex flex-column">
                                                        <h6 class="mb-1 fw-semibold text-dark">
                                                            <?= htmlspecialchars($user['nama_lengkap']) ?>
                                                        </h6>
                                                        <?php if ($user['no_hp']): ?>
                                                            <div class="d-flex align-items-center text-muted small">
                                                                <i class="fas fa-phone me-2"></i>
                                                                <span><?= htmlspecialchars($user['no_hp']) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                        <i class="fas fa-check-circle me-1"></i>Aktif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="noPenggunaFound" class="text-center py-5" style="display: none;">
                        <div class="mb-3">
                            <i class="fas fa-search fa-3x text-muted"></i>
                        </div>
                        <h6 class="text-muted mb-2">Tidak ditemukan</h6>
                        <p class="text-muted small mb-0">Tidak ada anggota yang cocok dengan pencarian</p>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer bg-light">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <div class="text-muted small">
                        <span id="totalMembers"><?= count($user_list) ?></span> anggota tersedia
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary me-2" onclick="cancelSelection()">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                        <button type="button" class="btn btn-primary" onclick="saveSelection()">
                            <i class="fas fa-check me-1"></i>Simpan Pilihan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah data berikut sudah benar?</p>
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Nama Alat:</div>
                        <div class="col-8" id="confirmNama">-</div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Total:</div>
                        <div class="col-8" id="confirmTotal">-</div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Jumlah Baik:</div>
                        <div class="col-8" id="confirmBaik">-</div>
                    <div class="row">
                        <div class="col-4 fw-bold">Jumlah Rusak:</div>
                        <div class="col-8" id="confirmRusak">-</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-4 fw-bold">Keterangan:</div>
                        <div class="col-8" id="confirmKeterangan">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">Konfirmasi</button>
            </div>
        </div>
    </div>

<script>
let alatValidationTimeout = null;
let isAlatValid = false;
let initialCheckboxState = {};

function validateAlatNameEdit() {
    const namaAlat = document.getElementById('nama_alat').value.trim();
    const excludeId = document.getElementById('excludeId').value;
    const icon = document.getElementById('alatNameIcon');
    const msg = document.getElementById('alatNameMsg');
    const input = document.getElementById('nama_alat');
    
    if (alatValidationTimeout) {
        clearTimeout(alatValidationTimeout);
    }
    
    if (namaAlat.length < 2) {
        icon.innerHTML = '<i class="fas fa-clock text-muted"></i>';
        msg.textContent = 'Minimal 2 karakter';
        msg.className = 'text-muted';
        input.classList.remove('is-valid', 'is-invalid');
        return false;
    }
    
    icon.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    alatValidationTimeout = setTimeout(function() {
        fetch(`api_check_alat.php?nama_alat=${encodeURIComponent(namaAlat)}&exclude_id=${excludeId}&mode=edit`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    icon.innerHTML = '<i class="fas fa-check text-success"></i>';
                    msg.textContent = data.message;
                    msg.className = 'text-success';
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    isAlatValid = true;
                } else {
                    icon.innerHTML = '<i class="fas fa-times text-danger"></i>';
                    msg.textContent = data.message;
                    msg.className = 'text-danger';
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    isAlatValid = false;
                }
            })
            .catch(error => {
                icon.innerHTML = '<i class="fas fa-exclamation-circle text-warning"></i>';
                msg.textContent = 'Error validasi';
                msg.className = 'text-warning';
                isAlatValid = false;
            });
    }, 300);
    
    return isAlatValid;
}

function clearAlatValidation() {
    const input = document.getElementById('nama_alat');
    input.classList.remove('is-valid', 'is-invalid');
    document.getElementById('alatNameIcon').innerHTML = '';
    document.getElementById('alatNameMsg').textContent = 'Minimal 2 karakter';
    document.getElementById('alatNameMsg').className = 'text-muted';
    isAlatValid = false;
}

function validateTotal() {
    const total = parseInt(document.getElementById('total_alat').value) || 1;
    const baik = parseInt(document.getElementById('jumlah_baik').value) || 0;
    const rusak = parseInt(document.getElementById('jumlah_rusak').value) || 0;
    
    if (total < (baik + rusak)) {
        document.getElementById('jumlah_baik').value = 0;
        document.getElementById('jumlah_rusak').value = 0;
    }
    
    validateQuantity();
}

function adjustTotal(delta) {
    const total = parseInt(document.getElementById('total_alat').value) || 1;
    const newTotal = Math.max(1, total + delta);
    document.getElementById('total_alat').value = newTotal;
    validateTotal();
}

function validateQuantity() {
    const total = parseInt(document.getElementById('total_alat').value) || 1;
    const baik = parseInt(document.getElementById('jumlah_baik').value) || 0;
    const rusak = parseInt(document.getElementById('jumlah_rusak').value) || 0;
    
    const maksRusak = total - baik;
    const sisa = total - baik - rusak;
    
    document.getElementById('sisaBaik').textContent = sisa;
    document.getElementById('maksRusak').textContent = maksRusak;
    
    document.getElementById('summaryTotal').textContent = total;
    document.getElementById('summaryBaik').textContent = baik;
    document.getElementById('summaryRusak').textContent = rusak;
    document.getElementById('summarySisa').textContent = sisa;
    
    if (baik > total) {
        document.getElementById('jumlah_baik').value = total;
    }
    if (rusak > total) {
        document.getElementById('jumlah_rusak').value = 0;
    }
    if ((baik + rusak) > total) {
        const over = (baik + rusak) - total;
        document.getElementById('jumlah_rusak').value = Math.max(0, rusak - over);
    }
    
    const newRusak = parseInt(document.getElementById('jumlah_rusak').value) || 0;
    const newSisa = total - baik - newRusak;
    document.getElementById('sisaBaik').textContent = newSisa;
    document.getElementById('summarySisa').textContent = newSisa;
}

function adjustBaik(delta) {
    const total = parseInt(document.getElementById('total_alat').value) || 1;
    const baik = parseInt(document.getElementById('jumlah_baik').value) || 0;
    const rusak = parseInt(document.getElementById('jumlah_rusak').value) || 0;
    
    let newBaik = baik + delta;
    const maksBaik = total - rusak;
    
    if (newBaik > maksBaik) newBaik = maksBaik;
    if (newBaik < 0) newBaik = 0;
    
    document.getElementById('jumlah_baik').value = newBaik;
    validateQuantity();
}

function adjustRusak(delta) {
    const total = parseInt(document.getElementById('total_alat').value) || 1;
    const baik = parseInt(document.getElementById('jumlah_baik').value) || 0;
    const rusak = parseInt(document.getElementById('jumlah_rusak').value) || 0;
    
    let newRusak = rusak + delta;
    const maksRusak = total - baik;
    
    if (newRusak > maksRusak) newRusak = maksRusak;
    if (newRusak < 0) newRusak = 0;
    
    document.getElementById('jumlah_rusak').value = newRusak;
    validateQuantity();
}

function openPenggunaModal() {
    new bootstrap.Modal(document.getElementById('penggunaModal')).show();
}

// Fungsi untuk filter anggota
function filterPengguna() {
    const search = document.getElementById('searchPengguna').value.toLowerCase();
    const cards = document.querySelectorAll('.pengguna-card');
    let found = false;
    
    // Show loading state
    const loadingEl = document.getElementById('loadingPengguna');
    loadingEl.style.display = 'block';
    document.getElementById('penggunaList').style.opacity = '0.5';
    
    setTimeout(() => {
        cards.forEach(card => {
            const name = card.querySelector('h6').textContent.toLowerCase();
            const phone = card.querySelector('.text-muted span')?.textContent.toLowerCase() || '';
            
            if (name.includes(search) || phone.includes(search)) {
                card.style.display = 'block';
                found = true;
            } else {
                card.style.display = 'none';
            }
        });
        
        document.getElementById('noPenggunaFound').style.display = found ? 'none' : 'block';
        
        // Hide loading state
        loadingEl.style.display = 'none';
        document.getElementById('penggunaList').style.opacity = '1';
    }, 300);
}

// Fungsi untuk hapus pencarian
function clearSearch() {
    document.getElementById('searchPengguna').value = '';
    filterPengguna();
}

// Fungsi untuk toggle semua anggota
function toggleAllPengguna() {
    const selectAll = document.getElementById('selectAllPengguna').checked;
    const checkboxes = document.querySelectorAll('.pengguna-checkbox');
    
    checkboxes.forEach(cb => {
        if (!cb.disabled) {
            cb.checked = selectAll;
            // Trigger change event
            cb.dispatchEvent(new Event('change'));
        }
    });
    
    updatePenggunaSelection();
}

// Fungsi untuk update seleksi
function updatePenggunaSelection() {
    const checkboxes = document.querySelectorAll('.pengguna-checkbox:checked');
    const count = checkboxes.length;
    
    // Update badge
    document.getElementById('selectedCountBadge').textContent = count;
    document.getElementById('selectedCount').textContent = count;
    
    // Update select all checkbox
    const totalCheckboxes = document.querySelectorAll('.pengguna-checkbox:not(:disabled)').length;
    document.getElementById('selectAllPengguna').checked = (count === totalCheckboxes && totalCheckboxes > 0);
    
    // Update preview
    updatePreview();
}

// Fungsi untuk hapus semua seleksi
function clearAllPengguna() {
    if (confirm('Apakah Anda yakin ingin menghapus semua pilihan anggota?')) {
        document.querySelectorAll('.pengguna-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updatePenggunaSelection();
    }
}

// Fungsi untuk simpan seleksi dan tutup modal
function saveSelection() {
    // Simpan state checkbox saat ini sebelum modal ditutup
    initialCheckboxState = {};
    const selectedIds = [];
    document.querySelectorAll('.pengguna-checkbox').forEach(cb => {
        const userId = cb.value;
        initialCheckboxState[userId] = cb.checked;
        if (cb.checked) {
            selectedIds.push(userId);
        }
    });
    
    // Simpan ke hidden input agar tersubmit dengan form
    document.getElementById('penggunaSelected').value = JSON.stringify(selectedIds);
    
    // Update preview
    updatePreview();
    // Tutup modal
    bootstrap.Modal.getInstance(document.getElementById('penggunaModal')).hide();
}

// Fungsi untuk batal dan reset seleksi
function cancelSelection() {
    // Restore checkbox ke state awal
    document.querySelectorAll('.pengguna-checkbox').forEach(cb => {
        const userId = cb.value;
        cb.checked = initialCheckboxState[userId] || false;
    });
    
    // Update display
    updatePenggunaSelection();
    clearSearch();
    
    // Tutup modal
    bootstrap.Modal.getInstance(document.getElementById('penggunaModal')).hide();
}

// Fungsi untuk simpan state awal saat modal dibuka
function saveInitialState() {
    initialCheckboxState = {};
    document.querySelectorAll('.pengguna-checkbox').forEach(cb => {
        const userId = cb.value;
        initialCheckboxState[userId] = cb.checked;
    });
}

// Fungsi untuk update preview di form utama
function updatePreview() {
    const preview = document.getElementById('selectedPreview');
    const checkboxes = document.querySelectorAll('.pengguna-checkbox:checked');
    
    if (checkboxes.length === 0) {
        preview.style.display = 'none';
        preview.innerHTML = '';
        return;
    }
    
    preview.style.display = 'flex';
    
    // Ambil maksimal 3 anggota untuk preview
    let previewHTML = '';
    const maxPreview = 3;
    
    checkboxes.forEach((cb, index) => {
        if (index < maxPreview) {
            const card = cb.closest('.pengguna-card');
            const name = card.querySelector('h6').textContent;
            const firstLetter = name.charAt(0).toUpperCase();
            
            previewHTML += `
                <div class="selected-member-preview">
                    <div class="member-avatar-small bg-primary text-white">
                        ${firstLetter}
                    </div>
                    <span class="member-name">${name}</span>
                </div>
            `;
        }
    });
    
    // Tambah badge untuk sisa anggota
    if (checkboxes.length > maxPreview) {
        const remaining = checkboxes.length - maxPreview;
        previewHTML += `
            <div class="selected-member-count">
                <span class="badge bg-secondary">+${remaining}</span>
            </div>
        `;
    }
    
    preview.innerHTML = previewHTML;
}

function showConfirmModal() {
    const nama = document.getElementById('nama_alat').value || '-';
    const total = document.getElementById('total_alat').value || '1';
    const baik = document.getElementById('jumlah_baik').value || '0';
    const rusak = document.getElementById('jumlah_rusak').value || '0';
    const keterangan = document.getElementById('keterangan').value || '-';
    
    document.getElementById('confirmNama').textContent = nama;
    document.getElementById('confirmTotal').textContent = total + ' unit';
    document.getElementById('confirmBaik').textContent = baik;
    document.getElementById('confirmRusak').textContent = rusak;
    document.getElementById('confirmKeterangan').textContent = keterangan;
    
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function submitForm() {
    document.getElementById('alatForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi checkbox berdasarkan hidden input
    try {
        const penggunaJson = document.getElementById('penggunaSelected').value;
        if (penggunaJson) {
            const selectedIds = JSON.parse(penggunaJson);
            document.querySelectorAll('.pengguna-checkbox').forEach(cb => {
                if (selectedIds.includes(cb.value)) {
                    cb.checked = true;
                }
            });
        }
    } catch (e) {
        console.error('Error initializing checkboxes:', e);
    }
    
    // Set initial count
    updatePenggunaSelection();
    validateTotal();
    validateQuantity();
    
    // Setup modal event untuk menyimpan state awal saat modal dibuka
    const modal = document.getElementById('penggunaModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            saveInitialState();
        });
    }
    
    // Add keyboard shortcuts
    const searchInput = document.getElementById('searchPengguna');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSearch();
            }
            if (e.key === 'Enter' && this.value.trim()) {
                // Highlight first result
                const firstCard = document.querySelector('.pengguna-card[style*="block"]');
                if (firstCard) {
                    firstCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});
</script>

<style>
.pengguna-list { margin: 0; padding: 0; }
.pengguna-item { padding: 0; margin: 0; border-bottom: 1px solid #e9ecef; }
.pengguna-item:last-child { border-bottom: none; }
.pengguna-row { display: flex; align-items: center; padding: 12px 15px; cursor: pointer; transition: background-color 0.15s ease; }
.pengguna-row:hover { background-color: #f8f9fa; }
.pengguna-checkbox-wrapper { flex-shrink: 0; width: 20px; margin-right: 12px; }
.pengguna-checkbox-wrapper .form-check-input { margin: 0; cursor: pointer; position: relative; top: 0; left: 0; }
.pengguna-info { display: flex; align-items: center; flex: 1; min-width: 0; }
.pengguna-avatar { width: 36px; height: 36px; background: #0d6efd; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0; }
.pengguna-details { flex: 1; min-width: 0; }
.pengguna-name { font-weight: 500; color: #212529; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pengguna-phone { font-size: 0.875em; color: #6c757d; margin-top: 2px; }
.pengguna-list::-webkit-scrollbar { width: 6px; }
.pengguna-list::-webkit-scrollbar-track { background: transparent; }
.pengguna-list::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
.pengguna-list { scrollbar-width: thin; scrollbar-color: #c1c1c1 transparent; }

/* Modal Styling */
.modal-lg {
    max-width: 800px;
}

/* Member Card Styling */
.pengguna-card .card {
    border: 1px solid #dee2e6;
    transition: all 0.2s ease;
    border-radius: 8px;
}

.pengguna-card .card:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
    transform: translateY(-1px);
}

.pengguna-card .card-body {
    padding: 0.75rem 1rem;
}

.member-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

/* Preview Styling */
.selected-member-preview {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 4px 12px;
    margin-right: 8px;
    margin-bottom: 8px;
}

.member-avatar-small {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
    margin-right: 6px;
}

.member-name {
    font-size: 0.875rem;
    color: #495057;
    white-space: nowrap;
}

.selected-member-count {
    display: flex;
    align-items: center;
    margin-left: 4px;
}

/* Scrollbar Styling */
.pengguna-list-container::-webkit-scrollbar {
    width: 8px;
}

.pengguna-list-container::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 4px;
}

.pengguna-list-container::-webkit-scrollbar-thumb {
    background: #c1c9d0;
    border-radius: 4px;
}

.pengguna-list-container::-webkit-scrollbar-thumb:hover {
    background: #a8b0b8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modal-lg {
        margin: 10px;
    }
    
    .pengguna-card .card-body {
        padding: 0.5rem;
    }
    
    .member-avatar {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>
