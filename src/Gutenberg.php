<?php

namespace JamesSeoAutoLinker;

/**
 * Handles Gutenberg (Block Editor) integration.
 */
class Gutenberg
{
    public function init(): void
    {
        add_action('init', [$this, 'register_meta']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
    }

    /**
     * Register meta for disabling auto-links on specific posts.
     */
    public function register_meta(): void
    {
        register_post_meta('', '_james_seo_auto_linker_disabled', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            }
        ]);
    }

    /**
     * Enqueue JS for the Gutenberg sidebar toggle.
     */
    public function enqueue_block_editor_assets(): void
    {
        wp_enqueue_script(
            'jsal-gutenberg-toggle',
            plugins_url('/js/gutenberg-toggle.js', dirname(__DIR__, 1) . '/james-seo-auto-linker.php'),
            ['wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-plugins', 'wp-i18n'],
            '1.0',
            true
        );
    }
}
