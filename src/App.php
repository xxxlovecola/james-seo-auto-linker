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
        if ($this->settings->get('post') || $this->settings->get('page')) {
            add_filter('the_content', [$this->processor, 'filter_content'], 10);
        }
        if ($this->settings->get('comment')) {
            add_filter('comment_text', fn($text) => $this->processor->filter_content($text, true), 10);
        }

        // Cache invalidation
        add_action('create_category', [$this->cache, 'clear_all']);
        add_action('edit_category', [$this->cache, 'clear_all']);
        add_action('edit_post', [$this->cache, 'clear_all']);
        add_action('save_post', [$this->cache, 'clear_all']);

        // Components initialization
        $this->admin->init();
        $this->gutenberg->init();

        // Translation
        add_action('init', function () {
            load_plugin_textdomain('james-seo-auto-linker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        });
    }

    public function get_settings(): Settings
    {
        return $this->settings;
    }
}
