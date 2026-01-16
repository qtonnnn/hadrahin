<?php
// validasi.php
// Berisi fungsi validasi bentrok jadwal latihan

require_once '../../config/koneksi.php';

/**
 * Cek apakah ada jadwal latihan yang bentrok
 * @param string $tanggal Tanggal jadwal (format Y-m-d)
 * @param string $jam_mulai Jam mulai (format H:i)
 * @param string $jam_selesai Jam selesai (format H:i)
 * @param int|null $exclude_id ID jadwal yang akan dieksklusi (untuk mode edit)
 * @return bool True jika bentrok, False jika tidak bentrok
 */
function cekBentrokJadwal($tanggal, $jam_mulai, $jam_selesai, $exclude_id = null)
{
    global $koneksi;
    
    // Query untuk cek bentrok
    // Bentrok jika: tanggal sama DAN jam tumpang tindih
    // Jam tumpang tindih jika: (jam_mulai baru < jam_selesai lama) DAN (jam_selesai baru > jam_mulai lama)
    
    $sql = "SELECT id FROM jadwal_latihan 
            WHERE tanggal = '$tanggal' 
            AND (
                (jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai')
            )";
    
    // Jika mode edit, eksklusi jadwal yang sedang diedit
    if ($exclude_id !== null) {
        $sql .= " AND id != $exclude_id";
    }
    
    $result = mysqli_query($koneksi, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true; // Bentrok
    }
    
    return false; // Tidak bentrok
}

