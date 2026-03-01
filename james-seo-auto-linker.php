<?php
/*
Plugin Name: James SEO Auto Linker
Version: 1.0
Author: James Colin
Description: James SEO Auto Linker inserts links on keywords automatically with optimized performance and security.
Requires at least: 5.6
Requires PHP: 7.4
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Basic PSR-4 Autoloader for the plugin.
 */
spl_autoload_register(function ($class) {
    $prefix = 'JamesSeoAutoLinker\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Bootstrap the plugin.
 */
function james_seo_auto_linker_init()
{
    return \JamesSeoAutoLinker\App::get_instance();
}

// Start the app on plugins_loaded
add_action('plugins_loaded', 'james_seo_auto_linker_init');

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, function () {
    $app = james_seo_auto_linker_init();
    $app->get_settings();
});