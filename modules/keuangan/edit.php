<?php
/**
 * Edit Transaksi Kas - Form Pengeditan dengan sidebar layout dan real-time validation
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$page_title = "Edit Transaksi";

// Check if user is admin
if ($_SESSION['peran'] !== 'admin') {
    header('Location: index.php?msg=error');
    exit;
}

$id_kas = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kas <= 0) {
    header('Location: index.php?msg=error');
    exit;
}

// Get kas data
$stmt = $pdo->prepare("SELECT * FROM keuangan WHERE id_kas = ?");
$stmt->execute([$id_kas]);
$kas = $stmt->fetch();

if (!$kas) {
    header('Location: index.php?msg=error');
    exit;
}

// Kategori predefined
$kategori_options = [
    'pemasukan' => [
        'Iuran Anggota' => 'Iuran Anggota',
        'Honor Acara' => 'Honor Acara',
        'Donasi' => 'Donasi',
        'Lainnya' => 'Lainnya'
    ],
    'pengeluaran' => [
        'Konsumsi' => 'Konsumsi',
        'Servis Alat' => 'Servis Alat',
        'Seragam' => 'Seragam',
        'Alat Musik' => 'Alat Musik',
        'Transportasi' => 'Transportasi',
        'Lainnya' => 'Lainnya'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe = $_POST['tipe'] ?? '';
    $kategori = trim($_POST['kategori'] ?? '');
    $jumlah = str_replace(['.', ','], ['', '.'], $_POST['jumlah'] ?? '0');
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    $errors = [];
    
    // Validasi tipe
    if (!in_array($tipe, ['pemasukan', 'pengeluaran'])) {
        $errors[] = "Tipe transaksi tidak valid";
    }
    
    // Validasi kategori
    if (empty($kategori)) {
        $errors[] = "Kategori wajib diisi";
    }
    
    // Validasi jumlah
    if (empty($jumlah) || (float)$jumlah <= 0) {
        $errors[] = "Jumlah harus lebih dari 0";
    }
    
    if (!is_numeric($jumlah)) {
        $errors[] = "Jumlah harus berupa angka";
    }
    
    if ((float)$jumlah > 999999999999) {
        $errors[] = "Jumlah terlalu besar";
    }
    
    // Validasi tanggal
    if (empty($tanggal)) {
        $errors[] = "Tanggal wajib diisi";
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $errors[] = "Format tanggal tidak valid";
    }
    
    if (strtotime($tanggal) > strtotime('today')) {
        $errors[] = "Tanggal tidak boleh di masa depan";
    }
    
    if (strtotime($tanggal) < strtotime('-5 years')) {
        $errors[] = "Tanggal terlalu lama (maksimal 5 tahun yang lalu)";
    }
    
    if (empty($errors)) {
        try {
            $user_id = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("
                UPDATE keuangan 
                SET tipe = ?, kategori = ?, jumlah = ?, tanggal = ?, keterangan = ?, 
                    updated_at = NOW(), user_modified = ?
                WHERE id_kas = ?
            ");
            $stmt->execute([$tipe, $kategori, (float)$jumlah, $tanggal, $keterangan ?: null, $user_id, $id_kas]);
            
            header('Location: index.php?msg=edit_sukes');
            exit;
            
        } catch (Exception $e) {
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
                
                <form method="POST" autocomplete="off" id="kasForm">
                    <input type="hidden" name="exclude_id" id="excludeId" value="<?= $id_kas ?>">
                    
                    <h6 class="text-primary mb-3"><i class="fas fa-money-bill-wave me-1"></i> Informasi Transaksi</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe" id="tipePemasukan" 
                                       value="pemasukan" <?= ($_POST['tipe'] ?? $kas['tipe']) === 'pemasukan' ? 'checked' : '' ?>
                                       onclick="updateKategoriOptions();">
                                <label class="form-check-label" for="tipePemasukan">
                                    <span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Pemasukan</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipe" id="tipePengeluaran" 
                                       value="pengeluaran" <?= ($_POST['tipe'] ?? $kas['tipe']) === 'pengeluaran' ? 'checked' : '' ?>
                                       onclick="updateKategoriOptions();">
                                <label class="form-check-label" for="tipePengeluaran">
                                    <span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Pengeluaran</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <select class="form-select" id="kategori" name="kategori" required
                                        onchange="validateKategori()" onblur="validateKategori()">
                                    <option value="">Pilih Kategori</option>
                                    <?php
                                    // Tampilkan kategori berdasarkan tipe yang dipilih
                                    $current_tipe = $_POST['tipe'] ?? $kas['tipe'];
                                    if ($current_tipe && isset($kategori_options[$current_tipe])) {
                                        foreach ($kategori_options[$current_tipe] as $value => $label):
                                        ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= ($_POST['kategori'] ?? $kas['kategori']) === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach;
                                    }
                                    ?>
                                </select>
                                <span class="input-group-text" id="kategoriIcon"></span>
                            </div>
                            <small class="text-muted" id="kategoriMsg">Pilih tipe transaksi terlebih dahulu</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="jumlah" class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="jumlah" name="jumlah" 
                                       value="<?= htmlspecialchars(number_format($_POST['jumlah'] ?? $kas['jumlah'], 0, ',', '.')) ?>" 
                                       placeholder="0" required
                                       onkeyup="formatRupiahInput(this); clearJumlahValidation();"
                                       onblur="validateJumlah()"
                                       oninput="clearJumlahValidation()">
                                <span class="input-group-text" id="jumlahIcon"></span>
                            </div>
                            <small class="text-muted" id="jumlahMsg">Minimal Rp 1</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                       value="<?= htmlspecialchars($_POST['tanggal'] ?? $kas['tanggal']) ?>" required
                                       onchange="validateTanggal()" onblur="validateTanggal()"
                                       oninput="clearTanggalValidation()">
                                <span class="input-group-text" id="tanggalIcon"></span>
                            </div>
                            <small class="text-muted" id="tanggalMsg">Format: YYYY-MM-DD</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                      placeholder="Detail transaksi..."><?= htmlspecialchars($_POST['keterangan'] ?? $kas['keterangan'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Info Card -->
                    <div class="card bg-light mb-4">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-plus me-1"></i>Dibuat: 
                                        <?= date('d/m/Y H:i', strtotime($kas['created_at'])) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-check me-1"></i>Terakhir Diubah: 
                                        <?= date('d/m/Y H:i', strtotime($kas['updated_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Preview Konfirmasi -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-eye me-1"></i> Preview Transaksi</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td width="120">Tipe</td>
                                            <td><span id="previewTipe" class="badge bg-secondary">-</span></td>
                                        </tr>
                                        <tr>
                                            <td>Kategori</td>
                                            <td><strong id="previewKategori">-</strong></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td width="120">Jumlah</td>
                                            <td><strong id="previewJumlah" class="text-primary">Rp 0</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal</td>
                                            <td><strong id="previewTanggal">-</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-primary btn-lg" onclick="if(validateAllFields()) { showConfirmModal(); }">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Perubahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Apakah perubahan berikut sudah benar?</p>
                <div class="alert alert-light border rounded p-3">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Tipe:</div>
                        <div class="col-8" id="confirmTipe">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Kategori:</div>
                        <div class="col-8" id="confirmKategori">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Jumlah:</div>
                        <div class="col-8" id="confirmJumlah" class="fw-bold">-</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Tanggal:</div>
                        <div class="col-8" id="confirmTanggal">-</div>
                    </div>
                    <div class="row">
                        <div class="col-4 fw-bold">Keterangan:</div>
                        <div class="col-8" id="confirmKeterangan">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
const kategoriOptions = <?= json_encode($kategori_options) ?>;
let kategoriValidationTimeout = null;
let jumlahValidationTimeout = null;
let tanggalValidationTimeout = null;
let isKategoriValid = false;
let isJumlahValid = false;
let isTanggalValid = false;

// Debug function
function logDebug(message) {
    console.log('[Kategori Debug] ' + message);
}

function updateKategoriOptions() {
    logDebug('updateKategoriOptions dipanggil');
    
    const tipe = document.querySelector('input[name="tipe"]:checked');
    logDebug('Tipe dipilih: ' + (tipe ? tipe.value : 'null'));
    
    const kategoriSelect = document.getElementById('kategori');
    if (!kategoriSelect) {
        logDebug('ERROR: Element kategori tidak ditemukan!');
        return;
    }
    
    // Clear existing options
    kategoriSelect.innerHTML = '<option value="">Pilih Kategori</option>';
    
    if (tipe && kategoriOptions[tipe.value]) {
        logDebug('Mengisi kategori untuk tipe: ' + tipe.value);
        kategoriOptions[tipe.value].forEach((label, value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            kategoriSelect.appendChild(option);
        });
        logDebug('Jumlah opsi ditambahkan: ' + kategoriSelect.options.length);
    } else {
        logDebug('Tidak ada kategori untuk tipe: ' + (tipe ? tipe.value : 'undefined'));
    }
    
    // Reset validation
    isKategoriValid = false;
    updateKategoriValidationUI(false, 'Pilih kategori');
    updatePreview();
}

function validateKategori() {
    const kategoriSelect = document.getElementById('kategori');
    const icon = document.getElementById('kategoriIcon');
    const msg = document.getElementById('kategoriMsg');
    const input = document.getElementById('kategori');
    
    const kategori = kategoriSelect.value.trim();
    const tipe = document.querySelector('input[name="tipe"]:checked')?.value;
    const excludeId = document.getElementById('excludeId').value;
    
    if (kategoriValidationTimeout) {
        clearTimeout(kategoriValidationTimeout);
    }
    
    if (!kategori) {
        updateKategoriValidationUI(false, 'Kategori wajib dipilih!');
        return false;
    }
    
    icon.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    kategoriValidationTimeout = setTimeout(function() {
        fetch(`api_check_keuangan.php?action=validate_kategori&kategori=${encodeURIComponent(kategori)}&tipe=${tipe || ''}`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    updateKategoriValidationUI(true, data.message);
                    isKategoriValid = true;
                } else {
                    updateKategoriValidationUI(false, data.message);
                    isKategoriValid = false;
                }
            })
            .catch(error => {
                updateKategoriValidationUI(true, 'Kategori valid!');
                isKategoriValid = true;
            });
    }, 300);
    
    return isKategoriValid;
}

function updateKategoriValidationUI(isValid, message) {
    const icon = document.getElementById('kategoriIcon');
    const msg = document.getElementById('kategoriMsg');
    const input = document.getElementById('kategori');
    
    if (isValid) {
        icon.innerHTML = '<i class="fas fa-check text-success"></i>';
        msg.textContent = message;
        msg.className = 'text-success';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else {
        icon.innerHTML = '<i class="fas fa-times text-danger"></i>';
        msg.textContent = message;
        msg.className = 'text-danger';
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    }
}

function formatRupiahInput(input) {
    let value = input.value.replace(/\D/g, '');
    if (value) {
        value = parseInt(value).toLocaleString('id-ID');
    }
    input.value = value;
    
    const jumlahValue = parseInt(input.value.replace(/\./g, '')) || 0;
    const previewJumlah = document.getElementById('previewJumlah');
    const confirmJumlah = document.getElementById('confirmJumlah');
    
    const formatted = 'Rp ' + jumlahValue.toLocaleString('id-ID');
    previewJumlah.textContent = formatted;
    confirmJumlah.textContent = formatted;
    
    const tipe = document.querySelector('input[name="tipe"]:checked')?.value;
    if (tipe === 'pemasukan') {
        previewJumlah.className = 'text-success';
        confirmJumlah.className = 'text-success';
    } else if (tipe === 'pengeluaran') {
        previewJumlah.className = 'text-danger';
        confirmJumlah.className = 'text-danger';
    }
}

function clearJumlahValidation() {
    const input = document.getElementById('jumlah');
    input.classList.remove('is-valid', 'is-invalid');
    document.getElementById('jumlahIcon').innerHTML = '';
    document.getElementById('jumlahMsg').textContent = 'Minimal Rp 1';
    document.getElementById('jumlahMsg').className = 'text-muted';
    isJumlahValid = false;
}

function validateJumlah() {
    const jumlahInput = document.getElementById('jumlah');
    const icon = document.getElementById('jumlahIcon');
    const msg = document.getElementById('jumlahMsg');
    const input = document.getElementById('jumlah');
    
    let value = jumlahInput.value.replace(/\./g, '');
    value = value.replace(/,/g, '.');
    
    if (jumlahValidationTimeout) {
        clearTimeout(jumlahValidationTimeout);
    }
    
    if (!value || parseFloat(value) <= 0) {
        updateJumlahValidationUI(false, 'Jumlah harus lebih dari 0!');
        return false;
    }
    
    icon.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    jumlahValidationTimeout = setTimeout(function() {
        fetch(`api_check_keuangan.php?action=validate_jumlah&jumlah=${encodeURIComponent(jumlahInput.value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    updateJumlahValidationUI(true, data.message);
                    isJumlahValid = true;
                } else {
                    updateJumlahValidationUI(false, data.message);
                    isJumlahValid = false;
                }
            })
            .catch(error => {
                const numValue = parseFloat(value) || 0;
                if (numValue > 0 && numValue <= 999999999999) {
                    updateJumlahValidationUI(true, 'Jumlah valid!');
                    isJumlahValid = true;
                } else {
                    updateJumlahValidationUI(false, 'Jumlah tidak valid!');
                    isJumlahValid = false;
                }
            });
    }, 300);
    
    return isJumlahValid;
}

function updateJumlahValidationUI(isValid, message) {
    const icon = document.getElementById('jumlahIcon');
    const msg = document.getElementById('jumlahMsg');
    const input = document.getElementById('jumlah');
    
    if (isValid) {
        icon.innerHTML = '<i class="fas fa-check text-success"></i>';
        msg.textContent = message;
        msg.className = 'text-success';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else {
        icon.innerHTML = '<i class="fas fa-times text-danger"></i>';
        msg.textContent = message;
        msg.className = 'text-danger';
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    }
}

function clearTanggalValidation() {
    const input = document.getElementById('tanggal');
    input.classList.remove('is-valid', 'is-invalid');
    document.getElementById('tanggalIcon').innerHTML = '';
    document.getElementById('tanggalMsg').textContent = 'Format: YYYY-MM-DD';
    document.getElementById('tanggalMsg').className = 'text-muted';
    isTanggalValid = false;
}

function validateTanggal() {
    const tanggalInput = document.getElementById('tanggal');
    const icon = document.getElementById('tanggalIcon');
    const msg = document.getElementById('tanggalMsg');
    const input = document.getElementById('tanggal');
    const tanggal = tanggalInput.value;
    
    if (tanggalValidationTimeout) {
        clearTimeout(tanggalValidationTimeout);
    }
    
    if (!tanggal) {
        updateTanggalValidationUI(false, 'Tanggal wajib diisi!');
        return false;
    }
    
    if (!/^\d{4}-\d{2}-\d{2}$/.test(tanggal)) {
        updateTanggalValidationUI(false, 'Format tanggal tidak valid (YYYY-MM-DD)!');
        return false;
    }
    
    icon.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    tanggalValidationTimeout = setTimeout(function() {
        fetch(`api_check_keuangan.php?action=validate_tanggal&tanggal=${encodeURIComponent(tanggal)}`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    updateTanggalValidationUI(true, data.message);
                    isTanggalValid = true;
                } else {
                    updateTanggalValidationUI(false, data.message);
                    isTanggalValid = false;
                }
            })
            .catch(error => {
                updateTanggalValidationUI(true, 'Tanggal valid!');
                isTanggalValid = true;
            });
    }, 300);
    
    return isTanggalValid;
}

function updateTanggalValidationUI(isValid, message) {
    const icon = document.getElementById('tanggalIcon');
    const msg = document.getElementById('tanggalMsg');
    const input = document.getElementById('tanggal');
    
    if (isValid) {
        icon.innerHTML = '<i class="fas fa-check text-success"></i>';
        msg.textContent = message;
        msg.className = 'text-success';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    } else {
        icon.innerHTML = '<i class="fas fa-times text-danger"></i>';
        msg.textContent = message;
        msg.className = 'text-danger';
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
    }
}

function validateAllFields() {
    const tipe = document.querySelector('input[name="tipe"]:checked');
    
    if (!tipe) {
        alert('Pilih tipe transaksi terlebih dahulu!');
        return false;
    }
    
    const kategoriValid = validateKategori();
    const jumlahValid = validateJumlah();
    const tanggalValid = validateTanggal();
    
    setTimeout(() => {
        if (isKategoriValid && isJumlahValid && isTanggalValid) {
            updatePreview();
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        } else {
            if (!isKategoriValid) {
                document.getElementById('kategori').focus();
            } else if (!isJumlahValid) {
                document.getElementById('jumlah').focus();
            } else if (!isTanggalValid) {
                document.getElementById('tanggal').focus();
            }
        }
    }, 500);
    
    return false;
}

function updatePreview() {
    const tipe = document.querySelector('input[name="tipe"]:checked')?.value;
    const kategoriSelect = document.getElementById('kategori');
    const tanggal = document.getElementById('tanggal').value;
    const jumlah = document.getElementById('jumlah').value || '0';
    const keterangan = document.getElementById('keterangan').value || '-';
    
    const previewTipe = document.getElementById('previewTipe');
    const previewKategori = document.getElementById('previewKategori');
    const previewTanggal = document.getElementById('previewTanggal');
    
    if (tipe === 'pemasukan') {
        previewTipe.textContent = 'Pemasukan';
        previewTipe.className = 'badge bg-success';
    } else if (tipe === 'pengeluaran') {
        previewTipe.textContent = 'Pengeluaran';
        previewTipe.className = 'badge bg-danger';
    } else {
        previewTipe.textContent = '-';
        previewTipe.className = 'badge bg-secondary';
    }
    
    previewKategori.textContent = kategoriSelect.value || '-';
    
    if (tanggal) {
        const dateObj = new Date(tanggal);
        previewTanggal.textContent = dateObj.toLocaleDateString('id-ID', { 
            day: '2-digit', 
            month: 'long', 
            year: 'numeric' 
        });
    } else {
        previewTanggal.textContent = '-';
    }
    
    const jumlahValue = parseInt(jumlah.replace(/\./g, '')) || 0;
    const previewJumlah = document.getElementById('previewJumlah');
    const formatted = 'Rp ' + jumlahValue.toLocaleString('id-ID');
    previewJumlah.textContent = formatted;
    
    if (tipe === 'pemasukan') {
        previewJumlah.className = 'text-success';
    } else if (tipe === 'pengeluaran') {
        previewJumlah.className = 'text-danger';
    }
    
    const confirmTipe = document.getElementById('confirmTipe');
    const confirmKategori = document.getElementById('confirmKategori');
    const confirmTanggal = document.getElementById('confirmTanggal');
    const confirmJumlah = document.getElementById('confirmJumlah');
    const confirmKeterangan = document.getElementById('confirmKeterangan');
    
    confirmTipe.innerHTML = previewTipe.outerHTML;
    confirmKategori.textContent = kategoriSelect.value || '-';
    confirmTanggal.textContent = previewTanggal.textContent;
    confirmJumlah.textContent = formatted;
    confirmJumlah.className = previewJumlah.className + ' fw-bold';
    confirmKeterangan.textContent = keterangan;
}

function showConfirmModal() {
    updatePreview();
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function submitForm() {
    document.getElementById('kasForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="tipe"]').forEach(radio => {
        radio.addEventListener('change', updatePreview);
    });
    
    document.getElementById('kategori').addEventListener('change', updatePreview);
    document.getElementById('tanggal').addEventListener('change', updatePreview);
    document.getElementById('jumlah').addEventListener('input', formatRupiahInput);
    document.getElementById('keterangan').addEventListener('input', updatePreview);
    
    updatePreview();
});
</script>

<?php include '../../includes/footer.php'; ?>

