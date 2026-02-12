<?php

namespace JamesSeoAutoLinker;

/**
 * Handles persistent caching using WordPress Transients.
 */
class Cache
{
    private string $prefix = 'jsal_';

    /**
     * Get a cached value.
     */
    public function get(string $key)
    {
        return get_transient($this->prefix . $key);
    }

    /**
     * Set a cached value.
     */
    public function set(string $key, $value, int $expiration = DAY_IN_SECONDS): bool
    {
        return set_transient($this->prefix . $key, $value, $expiration);
    }

    /**
     * Delete a specific cache key.
     */
    public function delete(string $key): bool
    {
        return delete_transient($this->prefix . $key);
    }

    /**
     * Clear all plugin-related transients.
     */
    public function clear_all(): void
    {
        global $wpdb;

        // Efficiently clear all jsal_ transients from the database
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->prefix . '%',
                '_transient_timeout_' . $this->prefix . '%'
            )
        );
    }
}
