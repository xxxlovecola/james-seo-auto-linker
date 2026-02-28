<?php

namespace JamesSeoAutoLinker;

/**
 * Handles persistent caching using WordPress Transients.
 */
class Cache
{
    private string $prefix = 'jsal_';

    private function get_version(): string
    {
        return (string) get_option('jsal_cache_version', '1');
    }

    /**
     * Get a cached value.
     */
    public function get(string $key)
    {
        return get_transient($this->prefix . $this->get_version() . '_' . $key);
    }

    /**
     * Set a cached value.
     */
    public function set(string $key, $value, int $expiration = DAY_IN_SECONDS): bool
    {
        return set_transient($this->prefix . $this->get_version() . '_' . $key, $value, $expiration);
    }

    /**
     * Delete a specific cache key.
     */
    public function delete(string $key): bool
    {
        return delete_transient($this->prefix . $this->get_version() . '_' . $key);
    }

    /**
     * Clear all plugin-related transients.
     */
    public function clear_all(): void
    {
        $current_version = (int) get_option('jsal_cache_version', 1);
        update_option('jsal_cache_version', $current_version + 1);

        // Optionally, clean up database transients if object cache is not in use
        if (!function_exists('wp_using_ext_object_cache') || !wp_using_ext_object_cache()) {
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    '_transient_' . $this->prefix . '%',
                    '_transient_timeout_' . $this->prefix . '%'
                )
            );
        }
    }
}
