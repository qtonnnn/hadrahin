<?php
/**
 * Export Laporan Keuangan ke Excel
 * Menghasilkan file Excel (.xls) dari data keuangan
 */

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/hadrahin';
    define('BASE_URL', $base_url);
}

// Start session dan include database
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

// Ambil parameter filter dari URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = htmlspecialchars(strip_tags($search), ENT_QUOTES, 'UTF-8');
if (strlen($search) > 100) $search = substr($search, 0, 100);

$tipe_filter = isset($_GET['tipe']) ? $_GET['tipe'] : '';
if (!in_array($tipe_filter, ['', 'pemasukan', 'pengeluaran'])) {
    $tipe_filter = '';
}

$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : '';
if (!preg_match('/^\d{4}-\d{2}$/', $bulan_filter)) {
    $bulan_filter = '';
}

// Build query dengan search dan filter
$where_clause = "1=1";
$params = [];

if ($search) {
    $where_clause .= " AND (k.keterangan LIKE ? OR k.kategori LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($tipe_filter) {
    $where_clause .= " AND k.tipe = ?";
    $params[] = $tipe_filter;
}

if ($bulan_filter) {
    $where_clause .= " AND DATE_FORMAT(k.tanggal, '%Y-%m') = ?";
    $params[] = $bulan_filter;
}

// Get all transaksi kas
$query = "SELECT k.*, u.nama_lengkap 
          FROM keuangan k 
          LEFT JOIN user u ON k.id_user = u.id_user
          WHERE $where_clause
          ORDER BY k.tanggal DESC, k.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$kas_list = $stmt->fetchAll();

// Calculate statistics
$total_pemasukan = 0;
$total_pengeluaran = 0;

foreach ($kas_list as $kas) {
    if ($kas['tipe'] === 'pemasukan') {
        $total_pemasukan += (float)$kas['jumlah'];
    } else {
        $total_pengeluaran += (float)$kas['jumlah'];
    }
}

$saldo_kas = $total_pemasukan - $total_pengeluaran;

// Format currency
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Nama bulan Indonesia
function getBulanIndonesia($bulan) {
    $bulanIndonesia = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    return $bulanIndonesia[$bulan] ?? $bulan;
}

// Generate Excel file
// Set headers untuk download file Excel
$filename = 'Laporan_Keuangan';
if ($bulan_filter) {
    $bulan = explode('-', $bulan_filter);
    $filename .= '_' . getBulanIndonesia($bulan[1]) . '_' . $bulan[0];
} else {
    $filename .= '_' . date('d-m-Y');
}
$filename .= '.xls';

// Header untuk file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Encode HTML untuk Excel
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<title>Laporan Keuangan</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; }';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th, td { border: 1px solid #000; padding: 8px; }';
echo 'th { background-color: #4472C4; color: white; }';
echo '.header-row { background-color: #D9E1F2; font-weight: bold; }';
echo '.pemasukan { color: green; }';
echo '.pengeluaran { color: red; }';
echo '.total-row { background-color: #E2EFDA; font-weight: bold; }';
echo '.saldo-row { background-color: #FFF2CC; font-weight: bold; }';
echo '.negative { color: red; }';
echo '</style>';
echo '</head><body>';

// Judul Laporan
echo '<h2 style="text-align: center;">LAPORAN KEUANGAN</h2>';
echo '<h3 style="text-align: center;">Tim Hadrah Husna Maulana</h3>';

if ($bulan_filter) {
    $bulan = explode('-', $bulan_filter);
    echo '<p style="text-align: center;">Bulan: ' . getBulanIndonesia($bulan[1]) . ' ' . $bulan[0] . '</p>';
} else {
    echo '<p style="text-align: center;">Semua Periode</p>';
}

echo '<p style="text-align: center;">Tanggal Cetak: ' . date('d/m/Y H:i:s') . '</p>';
echo '<br>';

// Ringkasan
echo '<table>';
echo '<tr class="header-row"><th colspan="2">RINGKASAN</th></tr>';
echo '<tr><td><strong>Total Pemasukan</strong></td><td class="pemasukan">' . formatRupiah($total_pemasukan) . '</td></tr>';
echo '<tr><td><strong>Total Pengeluaran</strong></td><td class="pengeluaran">' . formatRupiah($total_pengeluaran) . '</td></tr>';
$saldo_class = $saldo_kas < 0 ? 'negative' : '';
echo '<tr class="saldo-row"><td><strong>Saldo Kas</strong></td><td class="' . $saldo_class . '">' . formatRupiah($saldo_kas) . '</td></tr>';
echo '<tr><td><strong>Total Transaksi</strong></td><td>' . count($kas_list) . ' transaksi</td></tr>';
echo '</table>';
echo '<br>';

// Detail Transaksi
echo '<table>';
echo '<tr class="header-row">';
echo '<th>No</th>';
echo '<th>Tanggal</th>';
echo '<th>Tipe</th>';
echo '<th>Kategori</th>';
echo '<th>Keterangan</th>';
echo '<th>Jumlah</th>';
echo '<th>Input Oleh</th>';
echo '</tr>';

if (empty($kas_list)) {
    echo '<tr><td colspan="7" style="text-align: center;">Tidak ada data transaksi</td></tr>';
} else {
    $no = 1;
    foreach ($kas_list as $kas) {
        $tipe_class = $kas['tipe'] === 'pemasukan' ? 'pemasukan' : 'pengeluaran';
        $jumlah_formatted = $kas['tipe'] === 'pemasukan' ? '+' . formatRupiah($kas['jumlah']) : '-' . formatRupiah($kas['jumlah']);
        
        echo '<tr>';
        echo '<td>' . $no . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($kas['tanggal'])) . '</td>';
        echo '<td class="' . $tipe_class . '">' . ucfirst($kas['tipe']) . '</td>';
        echo '<td>' . htmlspecialchars($kas['kategori'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($kas['keterangan'] ?? '-') . '</td>';
        echo '<td class="' . $tipe_class . '">' . $jumlah_formatted . '</td>';
        echo '<td>' . htmlspecialchars($kas['nama_lengkap'] ?? 'System') . '</td>';
        echo '</tr>';
        $no++;
    }
}

echo '</table>';

// Footer
echo '<br><br>';
echo '<table style="width: 100%; border: none;">';
echo '<tr style="border: none;">';
echo '<td style="border: none; width: 50%; text-align: center;">';
echo '<p>Dibuat oleh</p>';
echo '<br><br><p>_______________________</p>';
echo '<p>' . htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') . '</p>';
echo '</td>';
echo '<td style="border: none; width: 50%; text-align: center;">';
echo '<p>Disetujui oleh</p>';
echo '<br><br><p>_______________________</p>';
echo '<p>Pembina</p>';
echo '</td>';
echo '</tr>';
echo '</table>';

echo '</body></html>';
exit;

