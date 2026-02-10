<?php
/**
 * Error Handler Terpusat untuk Aplikasi Hadrahin
 * Comprehensive Error Handling System
 */

require_once __DIR__ . '/exceptions.php';

// Konfigurasi error handling
define('ERROR_HANDLING_ENABLED', true);
define('DEBUG_MODE', false); // Ubah ke false di production - false untuk hindari 500 error

/**
 * Class utama untuk menangani semua error dan exception
 */
class ErrorHandler {
    private static bool $initialized = false;
    private static array $logBuffer = [];
    
    /**
     * Inisialisasi error handler
     */
    public static function init(): void {
        if (self::$initialized) return;
        
        self::$initialized = true;
        
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Register shutdown function untuk fatal error
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors (warnings, notices, dll)
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false; // Error diabaikan dengan @
        }
        
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        
        $type = $errorTypes[$errno] ?? 'UNKNOWN';
        $message = "[{$type}] {$errstr} in {$errfile} on line {$errline}";
        
        self::log($message, 'ERROR');
        
        // JANGAN lempar exception di handleError - ini menyebabkan 500 loop
        // Exception hanya dilempar dari handleException untuk uncaught exceptions
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $e): void {
        // Cek jika ini adalah exception dari error handler sendiri untuk menghindari infinite loop
        $exceptionFile = $e->getFile();
        
        // Jika exception berasal dari error handler, gunakan response minimal
        if (strpos($exceptionFile, 'error_handler.php') !== false) {
            http_response_code(500);
            echo "Terjadi kesalahan sistem. Silakan hubungi administrator.";
            return;
        }
        
        // Tentukan response berdasarkan tipe exception
        if ($e instanceof HadrahinExceptionInterface) {
            $userMessage = $e->getUserMessage();
            $httpCode = $e->getHttpCode();
            $logMessage = $e->getLogMessage();
        } else {
            $userMessage = 'Terjadi kesalahan yang tidak terduga';
            $httpCode = 500;
            $logMessage = "[UNHANDLED] {$e->getMessage()} | Trace: " . $e->getTraceAsString();
        }
        
        // Log error
        self::log($logMessage, get_class($e));
        
        // Set HTTP response code
        http_response_code($httpCode);
        
        // Prepare response
        $response = [
            'success' => false,
            'error' => [
                'code' => $httpCode,
                'message' => $userMessage,
                'type' => get_class($e)
            ]
        ];
        
        // Output response
        if (self::isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode($response, JSON_PRETTY_PRINT);
        } else {
            self::renderErrorPage($httpCode, $userMessage, $e);
        }
    }
    
    /**
     * Handle fatal errors dan shutdown
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log("[FATAL] {$error['message']} in {$error['file']} on line {$error['line']}", 'FATAL');
            
            http_response_code(500);
            
            if (self::isApiRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 500,
                        'message' => 'Terjadi kesalahan fatal'
                    ]
                ]);
            } else {
                self::renderErrorPage(500, 'Terjadi kesalahan fatal', null);
            }
        }
        
        // Flush log buffer
        self::flushLog();
    }
    
    /**
     * Log pesan error
     */
    public static function log(string $message, string $level = 'INFO'): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        self::$logBuffer[] = $logEntry;
        
        // Langsung tulis ke file jika error level tinggi
        if (in_array($level, ['ERROR', 'FATAL', 'CRITICAL'])) {
            self::flushLog();
        }
    }
    
    /**
     * Flush buffer log ke file
     */
    private static function flushLog(): void {
        if (empty(self::$logBuffer)) return;
        
        $logFile = defined('LOG_FILE') ? LOG_FILE : __DIR__ . '/../logs/php_errors.log';
        $logDir = dirname($logFile);
        
        // Buat direktori jika tidak ada
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, implode('', self::$logBuffer), FILE_APPEND | LOCK_EX);
        self::$logBuffer = [];
    }
    
    /**
     * Cek apakah request adalah API request
     */
    private static function isApiRequest(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false;
    }
    
    /**
     * Render halaman error untuk web
     */
    private static function renderErrorPage(int $code, string $message, ?Throwable $e): void {
        // Minimal HTML response jika semua gagal
        http_response_code($code);
        
        $errorTitles = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            409 => 'Conflict',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable'
        ];
        
        $title = $errorTitles[$code] ?? 'Error';
        
        echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Error {$code} - {$title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 48px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
            margin-bottom: 8px;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 16px;
        }
        .error-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }
        .btn-secondary:hover { background: #e5e5e5; }
    </style>
</head>
<body>
    <div class='error-container'>
        <div class='error-code'>{$code}</div>
        <h1 class='error-title'>{$title}</h1>
        <p class='error-message'>{$message}</p>
        <div class='error-actions'>
            <a href='javascript:history.back()' class='btn btn-secondary'>Kembali</a>
        </div>
    </div>
</body>
</html>";
    }
}

/**
 * Helper function untuk throw exception dengan cepat
 */
function validate_required(array $data, array $required, array $labels = []): void {
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $label = $labels[$field] ?? $field;
            throw new ValidationException("Field '{$label}' wajib diisi", "Field '{$label}' wajib diisi");
        }
    }
}

/**
 * Helper function untuk validasi data
 */
function validate_email(string $email): void {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('Email tidak valid', 'Format email tidak valid');
    }
}

/**
 * Helper function untuk cek otorisasi
 */
function require_permission(string $requiredRole): void {
    $userRole = $_SESSION['peran'] ?? 'guest';
    
    if ($userRole === 'guest') {
        throw new AuthenticationException('Silakan login terlebih dahulu', 'Silakan login untuk mengakses fitur ini');
    }
    
    $permissions = [
        'anggota' => 1,
        'pembina' => 2,
        'admin' => 3
    ];
    
    if (($permissions[$userRole] ?? 0) < ($permissions[$requiredRole] ?? 0)) {
        throw new AuthorizationException("Role '{$requiredRole}' diperlukan", 'Anda tidak memiliki akses untuk operasi ini');
    }
}

/**
 * Helper function untuk transaksi database dengan error handling
 */
function execute_transaction(PDO $pdo, callable $callback) {
    try {
        $pdo->beginTransaction();
        $result = $callback();
        $pdo->commit();
        return $result;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw new DatabaseException(
            "Database error: {$e->getMessage()}",
            'Terjadi kesalahan saat memproses data'
        );
    }
}

// Inisialisasi error handler
if (ERROR_HANDLING_ENABLED) {
    ErrorHandler::init();
}

