<?php
// Konfigurasi error yang aman untuk production
// Matikan display_errors untuk keamanan, gunakan logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../logs/php_errors.log');

session_start();

// Include rate limiting
require_once '../includes/rate_limit.php';

// Cek rate limit SEBELUM memproses login
$rate_limit = check_rate_limit();
$is_blocked = $rate_limit['blocked'];

// Jika sudah login, redirect ke halaman utama
if (isset($_SESSION['user_id'])) {
    $peran = $_SESSION['peran'];
    if ($peran === 'admin') {
        header('Location: ../dashboard/admin.php');
    } elseif ($peran === 'pembina') {
        header('Location: ../dashboard/pembina.php');
    } else {
        header('Location: ../dashboard/anggota.php');
    }
    exit;
}

$error = '';

// Proses login jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika sedang diblokir, cek lagi apakah sudah melewati waktu blokir
    if ($is_blocked) {
        // Cek rate limit lagi untuk memastikan masih diblokir
        $check_again = check_rate_limit();
        if ($check_again['blocked']) {
            $error = $check_again['message'];
            // Tampilkan halaman dengan pesan error, JANGAN proses login
        } else {
            // Blokir sudah habis, lanjutkan proses login
            $is_blocked = false;
        }
    }
    
    if (!$is_blocked && empty($error)) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi!';
        } else {
            // Koneksi ke database menggunakan PDO
            require_once '../config/database.php';

            try {
                // Query untuk mencari user
                $query = "SELECT * FROM user WHERE username = ? AND status_aktif = 1";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$username]);

if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();

// Verifikasi password
                if (password_verify($password, $user['password'])) {
                    // Login BERHASIL - reset rate limit
                    reset_rate_limit();
                    
                    // Login berhasil
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['peran'] = $user['peran'];

                    // Redirect berdasarkan peran
                    $peran = $user['peran'];
                    if ($peran === 'admin') {
                        header('Location: ../dashboard/admin.php');
                    } elseif ($peran === 'pembina') {
                        header('Location: ../dashboard/pembina.php');
                    } else {
                        header('Location: ../dashboard/anggota.php');
                    }
                    exit;
                } else {
                    // Login gagal - catat percobaan
                    record_failed_attempt();
                    $error = 'Password yang Anda masukkan salah!';
                }
            } else {
                $error = 'Username tidak ditemukan atau akun tidak aktif!';
            }
        } catch (PDOException $e) {
            // Log error detail, tampilkan pesan generik ke user
            error_log("Login DB Error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hadrah</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Fade In Animation for Container */
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        /* Simple fade in animation */
        @keyframes simpleFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Staggered animations for child elements */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
            animation: simpleFadeIn 0.5s ease-out;
        }

        .login-header h1 {
            color: #1a1a2e;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .logo-hadrah {
            font-size: 48px;
            margin-bottom: 10px;
            display: inline-block;
            animation: bounceIn 0.6s ease-out 0.1s both;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .form-group {
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInField 0.4s ease-out forwards;
        }

        .form-group:nth-of-type(1) {
            animation-delay: 0.3s;
        }

        .form-group:nth-of-type(2) {
            animation-delay: 0.5s;
        }

        @keyframes fadeInField {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a1a2e;
            box-shadow: 0 0 0 3px rgba(26, 26, 46, 0.1);
            transform: scale(1.02);
        }

        .form-group input:not(:placeholder-shown) {
            border-color: #1a1a2e;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            opacity: 0;
            animation: fadeInField 0.4s ease-out 0.7s forwards;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(26, 26, 46, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fcc;
            animation: shake 0.5s ease-out;
        }

        .error-message.success {
            background: #efe;
            color: #060;
            border-color: #cfc;
        }

        /* Rate limit countdown styles */
        .rate-limit-message {
            background: linear-gradient(135deg, #fff3cd 0%, #ffc107 100%);
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #ffc107;
            text-align: center;
            animation: pulse 2s infinite;
        }

        .rate-limit-message h3 {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .countdown-timer {
            font-size: 32px;
            font-weight: bold;
            color: #d32f2f;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }

        .countdown-label {
            font-size: 12px;
            opacity: 0.8;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            opacity: 0;
            animation: fadeInField 0.4s ease-out 0.9s forwards;
        }

        .login-footer a {
            color: #1a1a2e;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #ffd700;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-hadrah">🥁</div>
            <h1>Hadrahin</h1>
            <p>Sistem Informasi Grup Hadrah</p>
        </div>

        <?php if ($error): ?>
            <?php if ($is_blocked && isset($rate_limit['wait_seconds'])): ?>
            <div class="rate-limit-message">
                <h3>⚠️ Akses Diblokir</h3>
                <p>Terlalu banyak percobaan login yang gagal.</p>
                <p class="countdown-label">Silakan tunggu:</p>
                <div id="countdown-timer" class="countdown-timer">Loading...</div>
                <p class="countdown-label">Halaman akan otomatis refresh setelah waktu habis</p>
            </div>
            <?php else: ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       placeholder="Masukkan username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Masukkan password" 
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="login-footer">
            <p>Belum punya akun? Hubungi administrator.</p>
        </div>
    </div>

    <script>
    <?php if ($is_blocked && isset($rate_limit['wait_seconds'])): ?>
    // Countdown timer for rate limit
    (function() {
        var remainingSeconds = <?= intval($rate_limit['wait_seconds']) ?>;
        var countdownElement = document.getElementById('countdown-timer');
        
        function updateCountdown() {
            if (remainingSeconds <= 0) {
                // Refresh page to check if block is lifted
                location.reload();
                return;
            }
            
            var minutes = Math.floor(remainingSeconds / 60);
            var seconds = remainingSeconds % 60;
            
            if (minutes > 0) {
                countdownElement.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds + ' menit';
            } else {
                countdownElement.textContent = seconds + ' detik';
            }
            
            remainingSeconds--;
            
            // Update every second
            setTimeout(updateCountdown, 1000);
        }
        
        // Start countdown
        updateCountdown();
        
        // Disable form while waiting
        var form = document.querySelector('form');
        var inputs = form.querySelectorAll('input');
        var button = form.querySelector('button');
        
        inputs.forEach(function(input) {
            input.disabled = true;
        });
        button.disabled = true;
        button.textContent = 'Tunggu sebentar...';
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
    })();
    <?php endif; ?>
    </script>
</body>
</html>

