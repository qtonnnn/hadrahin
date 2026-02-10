<?php
/**
 * Custom Exceptions untuk Aplikasi Hadrahin
 * Comprehensive Error Handling System
 */

// Base Exception Interface
interface HadrahinExceptionInterface {
    public function getUserMessage(): string;
    public function getLogMessage(): string;
    public function getHttpCode(): int;
}

/**
 * ValidationException - Untuk error validasi input
 */
class ValidationException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $message = 'Validasi gagal', string $userMessage = null) {
        parent::__construct($message, 400);
        $this->userMessage = $userMessage ?? $message;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[VALIDATION] {$this->message} | User: " . ($_SESSION['user_id'] ?? 'guest');
    }
    
    public function getHttpCode(): int {
        return 400;
    }
}

/**
 * DatabaseException - Untuk error database
 */
class DatabaseException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $message = 'Error database', string $userMessage = 'Terjadi kesalahan database') {
        parent::__construct($message, 500);
        $this->userMessage = $userMessage;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[DATABASE] {$this->message} | Trace: " . substr($this->getTraceAsString(), 0, 200);
    }
    
    public function getHttpCode(): int {
        return 500;
    }
}

/**
 * AuthenticationException - Untuk error autentikasi (login)
 */
class AuthenticationException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $message = 'Autentikasi gagal', string $userMessage = 'Username atau password salah') {
        parent::__construct($message, 401);
        $this->userMessage = $userMessage;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[AUTH] {$this->message} | IP: {$_SERVER['REMOTE_ADDR']}";
    }
    
    public function getHttpCode(): int {
        return 401;
    }
}

/**
 * AuthorizationException - Untuk error otorisasi (akses)
 */
class AuthorizationException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $message = 'Akses ditolak', string $userMessage = 'Anda tidak memiliki akses untuk operasi ini') {
        parent::__construct($message, 403);
        $this->userMessage = $userMessage;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[AUTHZ] {$this->message} | User: " . ($_SESSION['user_id'] ?? 'guest') . " | Role: " . ($_SESSION['peran'] ?? 'none');
    }
    
    public function getHttpCode(): int {
        return 403;
    }
}

/**
 * NotFoundException - Untuk resource tidak ditemukan
 */
class NotFoundException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $resource = 'Data', string $message = null, string $userMessage = null) {
        $msg = $message ?? "{$resource} tidak ditemukan";
        $userMsg = $userMessage ?? "{$resource} yang dicari tidak ditemukan";
        parent::__construct($msg, 404);
        $this->userMessage = $userMsg;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[NOT_FOUND] {$this->message} | URI: {$_SERVER['REQUEST_URI']}";
    }
    
    public function getHttpCode(): int {
        return 404;
    }
}

/**
 * ConflictException - Untuk konflik data (duplikat, dll)
 */
class ConflictException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $message = 'Konflik data', string $userMessage = 'Data sudah ada atau bermasalah') {
        parent::__construct($message, 409);
        $this->userMessage = $userMessage;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[CONFLICT] {$this->message}";
    }
    
    public function getHttpCode(): int {
        return 409;
    }
}

/**
 * RateLimitException - Untuk rate limiting
 */
class RateLimitException extends Exception implements HadrahinExceptionInterface {
    private string $userMessage;
    
    public function __construct(string $message = 'Terlalu banyak percobaan', string $userMessage = 'Silakan tunggu sebelum mencoba lagi') {
        parent::__construct($message, 429);
        $this->userMessage = $userMessage;
    }
    
    public function getUserMessage(): string {
        return $this->userMessage;
    }
    
    public function getLogMessage(): string {
        return "[RATE_LIMIT] {$this->message} | IP: {$_SERVER['REMOTE_ADDR']}";
    }
    
    public function getHttpCode(): int {
        return 429;
    }
}

