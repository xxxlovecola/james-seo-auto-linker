<?php

namespace JamesSeoAutoLinker;

/**
 * Main application class to bootstrap the plugin.
 */
class App
{
    private static ?App $instance = null;
    private Settings $settings;
    private Cache $cache;
    private Processor $processor;
    private Admin $admin;
    private Gutenberg $gutenberg;

    private function __construct()
    {
        $this->settings = new Settings();
        $this->cache = new Cache();
        $this->processor = new Processor($this->settings, $this->cache);
        $this->admin = new Admin($this->settings, $this->cache);
        $this->gutenberg = new Gutenberg();

        $this->init_hooks();
    }

    public static function get_instance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks(): void
    {
        // Content filters
        // Priority 9999 ensures we run after shortcodes (11) and other content formatters
        if ($this->settings->get('post') || $this->settings->get('page')) {
            add_filter('the_content', [$this->processor, 'filter_content'], 9999);
        }
        if ($this->settings->get('comment')) {
            add_filter('comment_text', fn($text) => $this->processor->filter_content($text, true), 9999);
        }

        // Cache invalidation
        add_action('created_term', [$this->cache, 'clear_all']);
        add_action('edited_term', [$this->cache, 'clear_all']);
        add_action('delete_term', [$this->cache, 'clear_all']);
        add_action('save_post', [$this->cache, 'clear_all']);
        add_action('deleted_post', [$this->cache, 'clear_all']);

        // Components initialization
        $this->admin->init();
        $this->gutenberg->init();

        // Cron hook
        add_action('jsal_fetch_custom_keywords', [$this->processor, 'fetch_remote_keywords_cron']);

        // Translation
        add_action('init', function () {
            load_plugin_textdomain('james-seo-auto-linker', false, dirname(plugin_basename(__FILE__), 2) . '/languages/');
        });
    }

    public function get_settings(): Settings
    {
        return $this->settings;
    }
}
