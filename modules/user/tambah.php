
<?php
require_once '../../includes/auth_check.php';

$page_title = "Tambah User";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $peran = $_POST['peran'] ?? 'anggota';
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

    $errors = [];

    // Validation
    if (empty($username)) {
        $errors[] = "Username wajib diisi!";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore!";
    }

    if (empty($password)) {
        $errors[] = "Password wajib diisi!";
    } elseif (strlen($password) < 3) {
        $errors[] = "Password minimal 3 karakter!";
    }

    if ($password !== $konfirmasi_password) {
        $errors[] = "Konfirmasi password tidak cocok!";
    }

    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap wajib diisi!";
    }

    // Validate phone number format (optional, Indonesia format)
    if (!empty($no_hp) && !preg_match('/^[0-9]{10,15}$/', $no_hp)) {
        $errors[] = "Nomor HP harus berupa angka (10-15 digit)!";
    }

    // Check username uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Username sudah digunakan!";
        }
    }

    // Check phone number uniqueness
    if (empty($errors) && !empty($no_hp)) {
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE no_hp = ?");
        $stmt->execute([$no_hp]);
        if ($stmt->fetch()) {
            $errors[] = "Nomor HP sudah digunakan oleh user lain!";
        }
    }

    // Insert user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO user (username, password, nama_lengkap, no_hp, peran, status_aktif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $nama_lengkap, $no_hp, $peran, $status_aktif]);
            
            header('Location: index.php?msg=tambah_sukes');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Gagal menambahkan user: " . $e->getMessage();
        }
    }
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
            <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i><?= $page_title ?></h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
        </div>

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

        <div class="card shadow-sm border-0" style="max-width: 600px;">
            <div class="card-body">
                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                                   placeholder="Masukkan username" required minlength="3" maxlength="50">
                        </div>
                        <div class="form-text">Minimal 3 karakter, hanya huruf, angka, dan underscore.</div>
                    </div>

                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                   value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" 
                                   placeholder="Masukkan nama lengkap" required maxlength="100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor Handphone</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="no_hp" name="no_hp" 
                                   value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" 
                                   placeholder="Masukkan nomor HP (contoh: 081234567890)" maxlength="15">
                        </div>
                        <div class="form-text">Format: 10-15 digit angka (contoh: 081234567890)</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Masukkan password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" 
                                   placeholder="Konfirmasi password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" onclick="togglePassword('konfirmasi_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="peran" class="form-label">Peran</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <select class="form-select" id="peran" name="peran">
                                <option value="anggota" <?= ($_POST['peran'] ?? '') === 'anggota' ? 'selected' : '' ?>>Anggota</option>
                                <option value="pembina" <?= ($_POST['peran'] ?? '') === 'pembina' ? 'selected' : '' ?>>Pembina</option>
                                <option value="admin" <?= ($_POST['peran'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif" 
                                   <?= isset($_POST['status_aktif']) || !isset($_POST['status_aktif']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="status_aktif">User Aktif</label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" onclick="showConfirmModal()">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Konfirmasi Data User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Apakah data berikut sudah benar?</p>
                    <div class="alert alert-light border rounded p-3">
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Username:</div>
                            <div class="col-8" id="confirmUsername">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Nama:</div>
                            <div class="col-8" id="confirmNama">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">No HP:</div>
                            <div class="col-8" id="confirmNoHp">-</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 fw-bold">Peran:</div>
                            <div class="col-8">
                                <span class="badge bg-primary" id="confirmPeran">-</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4 fw-bold">Status:</div>
                            <div class="col-8">
                                <span class="badge bg-success" id="confirmStatus">Aktif</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-text"><i class="fas fa-info-circle me-1"></i>Pastikan data sudah benar sebelum menyimpan.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm()"><i class="fas fa-check me-1"></i>Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Show confirmation modal
    function showConfirmModal() {
        const username = document.getElementById('username').value.trim() || '-';
        const namaLengkap = document.getElementById('nama_lengkap').value.trim() || '-';
        const noHp = document.getElementById('no_hp').value.trim() || 'Belum diisi';
        const peranSelect = document.getElementById('peran');
        const peranText = peranSelect.options[peranSelect.selectedIndex].text;
        const statusAktif = document.getElementById('status_aktif').checked;
        
        // Update modal content
        document.getElementById('confirmUsername').textContent = username;
        document.getElementById('confirmNama').textContent = namaLengkap;
        document.getElementById('confirmNoHp').textContent = noHp;
        document.getElementById('confirmPeran').textContent = peranText;
        document.getElementById('confirmStatus').textContent = statusAktif ? 'Aktif' : 'Tidak Aktif';
        document.getElementById('confirmStatus').className = statusAktif ? 'badge bg-success' : 'badge bg-secondary';
        
        // Show modal using Bootstrap 5
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    }

    // Submit form after confirmation
    function submitForm() {
        document.querySelector('form').submit();
    }

    // Realtime validation
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        const namaLengkapInput = document.getElementById('nama_lengkap');
        const noHpInput = document.getElementById('no_hp');
        const passwordInput = document.getElementById('password');
        const konfirmasiPasswordInput = document.getElementById('konfirmasi_password');
        const form = document.querySelector('form');

        // Helper function to show error
        function showError(input, message) {
            const parent = input.parentElement.parentElement;
            let errorDiv = parent.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block';
                parent.appendChild(errorDiv);
            }
            input.classList.add('is-invalid');
            errorDiv.textContent = message;
            return false;
        }

        // Helper function to clear error
        function clearError(input) {
            const parent = input.parentElement.parentElement;
            const errorDiv = parent.querySelector('.invalid-feedback');
            input.classList.remove('is-invalid');
            if (errorDiv) {
                errorDiv.remove();
            }
            return true;
        }

        // Username validation
        usernameInput.addEventListener('input', function() {
            const username = this.value;
            if (username.length === 0) {
                return clearError(this);
            }
            if (username.length < 3) {
                return showError(this, 'Username minimal 3 karakter!');
            }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                return showError(this, 'Username hanya boleh huruf, angka, dan underscore!');
            }
            return clearError(this);
        });

        // Username blur - check uniqueness
        usernameInput.addEventListener('blur', function() {
            const username = this.value;
            if (username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username)) {
                // Simulate AJAX check with fetch
                fetch('../../api/check_username.php?username=' + encodeURIComponent(username))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            showError(this, 'Username sudah digunakan!');
                        } else {
                            clearError(this);
                        }
                    })
                    .catch(() => {
                        // Silently fail - server-side will catch it
                    });
            }
        });

        // Nama lengkap validation
        namaLengkapInput.addEventListener('input', function() {
            const nama = this.value;
            if (nama.length === 0) {
                return clearError(this);
            }
            if (nama.length < 2) {
                return showError(this, 'Nama lengkap minimal 2 karakter!');
            }
            return clearError(this);
        });

        // Phone number format validation
        noHpInput.addEventListener('input', function() {
            const noHp = this.value;
            if (noHp.length === 0) {
                return clearError(this);
            }
            if (!/^[0-9]*$/.test(noHp)) {
                return showError(this, 'Nomor HP hanya boleh berisi angka!');
            }
            if (noHp.length > 0 && noHp.length < 10) {
                return showError(this, 'Nomor HP minimal 10 digit!');
            }
            if (noHp.length > 15) {
                // Truncate to 15 digits
                this.value = noHp.slice(0, 15);
                return showError(this, 'Nomor HP maksimal 15 digit!');
            }
            return clearError(this);
        });

        // Phone number blur - check uniqueness
        noHpInput.addEventListener('blur', function() {
            const noHp = this.value;
            if (noHp.length >= 10 && noHp.length <= 15) {
                fetch('../../api/check_no_hp.php?no_hp=' + encodeURIComponent(noHp))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            showError(this, 'Nomor HP sudah digunakan!');
                        } else {
                            clearError(this);
                        }
                    })
                    .catch(() => {
                        // Silently fail - server-side will catch it
                    });
            }
        });

        // Password validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            if (password.length === 0) {
                return clearError(this);
            }
            if (password.length < 3) {
                return showError(this, 'Password minimal 3 karakter!');
            }
            return clearError(this);
        });

        // Konfirmasi password validation (realtime)
        konfirmasiPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const konfirmasi = this.value;
            if (konfirmasi.length === 0) {
                return clearError(this);
            }
            if (password !== konfirmasi) {
                return showError(this, 'Konfirmasi password tidak cocok!');
            }
            return clearError(this);
        });

        // Password match on both fields
        passwordInput.addEventListener('input', function() {
            const konfirmasi = konfirmasiPasswordInput.value;
            if (konfirmasi.length > 0 && this.value !== konfirmasi) {
                konfirmasiPasswordInput.classList.add('is-invalid');
                const parent = konfirmasiPasswordInput.parentElement.parentElement;
                let errorDiv = parent.querySelector('.invalid-feedback');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback d-block';
                    parent.appendChild(errorDiv);
                }
                errorDiv.textContent = 'Konfirmasi password tidak cocok!';
            } else if (konfirmasi.length > 0) {
                clearError(konfirmasiPasswordInput);
            }
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = [usernameInput, namaLengkapInput, passwordInput, konfirmasiPasswordInput];
            
            requiredFields.forEach(field => {
                if (!field.value.trim() && field.hasAttribute('required')) {
                    showError(field, 'Field ini wajib diisi!');
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>

