# TODO - Jadwal Latihan Module

## Cache Implementation
- [x] Create Cache utility class (`includes/cache.php`)
- [x] Add caching for statistics queries (`modules/jadwallatihan/index.php`)
- [x] Add caching for max_date query (`modules/jadwallatihan/index.php`)
- [x] Clear cache after insert (`modules/jadwallatihan/tambah.php`)
- [x] Clear cache after update (`modules/jadwallatihan/edit.php`)
- [x] Clear cache after delete (`modules/jadwallatihan/hapus.php`)
- [x] Clear cache after delete via GET (`modules/jadwallatihan/index.php`)

## Implementation Details

### Cache Class
- File-based caching
- Default TTL: 5 minutes (300 seconds)
- Cache directory: `includes/cache/`

### Cached Data
1. **Statistics** (`jadwal_stats`): Count of jadwal by status
   - TTL: 10 minutes (600 seconds)
   - Used in: `index.php` statistics cards

2. **Max Date** (`jadwal_max_date`): Latest jadwal date
   - TTL: 10 minutes (600 seconds)
   - Used in: `index.php` for default end date

### Cache Invalidation
Cache is cleared when:
- New jadwal is added
- Jadwal is updated
- Jadwal is deleted

