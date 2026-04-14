<?php
/**
 * Simple File-Based Caching Utility
 * Untuk menyimpan data yang jarang berubah
 */

class Cache {
    private static $cache_dir = __DIR__ . '/../cache/';

    /**
     * Initialize cache directory
     */
    private static function init() {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
    }

    /**
     * Get cached data
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public static function get($key) {
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));
        if (!$data || !isset($data['expires']) || time() > $data['expires']) {
            unlink($file); // Delete expired cache
            return null;
        }

        return $data['value'];
    }

    /**
     * Set cached data
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int $ttl Time to live in seconds (default 300 = 5 minutes)
     */
    public static function set($key, $value, $ttl = 300) {
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';

        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        file_put_contents($file, serialize($data));
    }

    /**
     * Delete cached data
     * @param string $key Cache key
     */
    public static function delete($key) {
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Clear all cache
     */
    public static function clear() {
        self::init();
        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get or set cache with callback
     * @param string $key Cache key
     * @param callable $callback Function to generate data if not cached
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember($key, $callback, $ttl = 300) {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }
}
?>
