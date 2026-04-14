<?php
/**
 * Update Status Booking Acara
 * API untuk mengubah status booking melalui AJAX
 */

require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Set header untuk JSON
header('Content-Type: application/json');

// Check permission (Admin only)
if ($_SESSION['peran'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid']);
    exit;
}

// Get input
$id_booking = $_POST['id_booking'] ?? 0;
$status = $_POST['status'] ?? '';

// Validate input
if (empty($id_booking) || !is_numeric($id_booking)) {
    echo json_encode(['success' => false, 'message' => 'ID booking tidak valid']);
    exit;
}

$valid_statuses = ['menunggu', 'diterima', 'ditolak', 'selesai'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
    exit;
}

// Check if booking exists
try {
    $stmt = $pdo->prepare("SELECT id_booking, status FROM booking_acara WHERE id_booking = ?");
    $stmt->execute([$id_booking]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan']);
        exit;
    }

    // Check status transition validation
    $current_status = $booking['status'];
    
    // Validate status transition
    if ($current_status == 'selesai') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah status booking yang sudah selesai']);
        exit;
    }

    if ($current_status == 'ditolak' && $status != 'menunggu') {
        echo json_encode(['success' => false, 'message' => 'Booking yang ditolak hanya dapat diubah ke menunggu']);
        exit;
    }

    // Validate event date for 'selesai' status
    if ($status == 'selesai') {
        $stmt = $pdo->prepare("SELECT tanggal_acara FROM booking_acara WHERE id_booking = ?");
        $stmt->execute([$id_booking]);
        $event_date = $stmt->fetchColumn();
        
        $event_timestamp = strtotime($event_date);
        $today_timestamp = strtotime(date('Y-m-d'));
        
        if ($event_timestamp > $today_timestamp) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menandai selesai - Acara belum berlangsung']);
            exit;
        }
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Update status
try {
    $stmt = $pdo->prepare("UPDATE booking_acara 
                           SET status = ?, updated_at = NOW(), user_modified = ? 
                           WHERE id_booking = ?");
    $stmt->execute([$status, $_SESSION['user_id'], $id_booking]);

    // Log activity
    error_log("Status booking diubah: ID=$id_booking, Status=$status, User=" . $_SESSION['user_id']);

    echo json_encode([
        'success' => true, 
        'message' => 'Status berhasil diubah',
        'new_status' => $status
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Error updating booking status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status: ' . $e->getMessage()]);
    exit;
}

