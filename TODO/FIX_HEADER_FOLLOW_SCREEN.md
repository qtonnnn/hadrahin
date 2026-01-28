# TODO: Perbaikan Header Mengikuti Layar

## Masalah
Header tidak mengikuti layar saat di-scroll.

## Penyebab
- `position: sticky` tidak berfungsi dengan baik karena ada `overflow: hidden`
- `width: 100%` tidak menghitung posisi sidebar dengan benar

## Solusi
1. ✅ Ubah `position: sticky` menjadi `position: fixed` pada `.content-header`
2. ✅ Hapus `overflow: hidden` yang mengganggu
3. ✅ Gunakan `left: var(--sidebar-width)` dan `width: calc(100% - var(--sidebar-width))`
4. ✅ Tambahkan media query untuk responsive (saat sidebar disembunyikan di mobile)

## File yang Diedit
- `assets/css/admin.css` - Perbaikan CSS header

## Status: SELESAI

