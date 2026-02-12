<?php

namespace JamesSeoAutoLinker;

/**
 * Handles the administrative interface and settings.
 */
class Admin
{
    private Settings $settings;
    private Cache $cache;

    public function __construct(Settings $settings, Cache $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;
    }

    public function init(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_seo_auto_linker_clear_cache', [$this, 'ajax_clear_cache']);
    }

    public function add_menu_page(): void
    {
        add_options_page(
            __('James SEO Auto Linker Options', 'james-seo-auto-linker'),
            __('James SEO Auto Linker', 'james-seo-auto-linker'),
            'manage_options',
            'james-seo-auto-linker',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page(): void
    {
        if (isset($_POST['submitted'])) {
            $this->handle_save();
        }

        $options = $this->settings->get();
        $action_url = admin_url('options-general.php?page=james-seo-auto-linker');

        // Map names for the legacy template
        $post = ($options['post'] === 'on') ? 'checked' : '';
        $postself = ($options['postself'] === 'on') ? 'checked' : '';
        $page = ($options['page'] === 'on') ? 'checked' : '';
        $pageself = ($options['pageself'] === 'on') ? 'checked' : '';
        $comment = ($options['comment'] === 'on') ? 'checked' : '';
        $excludeheading = ($options['excludeheading'] === 'on') ? 'checked' : '';
        $lposts = ($options['lposts'] === 'on') ? 'checked' : '';
        $lpages = ($options['lpages'] === 'on') ? 'checked' : '';
        $lcats = ($options['lcats'] === 'on') ? 'checked' : '';
        $ltags = ($options['ltags'] === 'on') ? 'checked' : '';
        $ignore = esc_attr($options['ignore']);
        $ignorepost = esc_attr($options['ignorepost']);
        $maxlinks = (int) $options['maxlinks'];
        $maxsingle = (int) $options['maxsingle'];
        $maxsingleurl = (int) $options['maxsingleurl'];
        $minusage = (int) $options['minusage'];
        $customkey = esc_textarea($options['customkey']);
        $customkey_url = esc_url($options['customkey_url']);
        $customkey_preventduplicatelink = $options['customkey_preventduplicatelink'] ? 'checked' : '';
        $nofolo = ($options['nofolo'] === 'on') ? 'checked' : '';
        $onlysingle = ($options['onlysingle'] === 'on') ? 'checked' : '';
        $casesens = ($options['casesens'] === 'on') ? 'checked' : '';
        $allowfeed = ($options['allowfeed'] === 'on') ? 'checked' : '';

        require_once dirname(__DIR__) . '/james-seo-auto-linker-admin.php';
    }

    private function handle_save(): void
    {
        check_admin_referer('james-seo-auto-linker');

        $this->settings->update([
            'post' => sanitize_text_field($_POST['post'] ?? ''),
            'postself' => sanitize_text_field($_POST['postself'] ?? ''),
            'page' => sanitize_text_field($_POST['page'] ?? ''),
            'pageself' => sanitize_text_field($_POST['pageself'] ?? ''),
            'comment' => sanitize_text_field($_POST['comment'] ?? ''),
            'excludeheading' => sanitize_text_field($_POST['excludeheading'] ?? ''),
            'lposts' => sanitize_text_field($_POST['lposts'] ?? ''),
            'lpages' => sanitize_text_field($_POST['lpages'] ?? ''),
            'lcats' => sanitize_text_field($_POST['lcats'] ?? ''),
            'ltags' => sanitize_text_field($_POST['ltags'] ?? ''),
            'ignore' => sanitize_text_field($_POST['ignore'] ?? ''),
            'ignorepost' => sanitize_text_field($_POST['ignorepost'] ?? ''),
            'maxlinks' => absint($_POST['maxlinks'] ?? 3),
            'maxsingle' => absint($_POST['maxsingle'] ?? 1),
            'maxsingleurl' => absint($_POST['maxsingleurl'] ?? 1),
            'minusage' => absint($_POST['minusage'] ?? 1),
            'customkey' => sanitize_textarea_field($_POST['customkey'] ?? ''),
            'customkey_url' => esc_url_raw($_POST['customkey_url'] ?? ''),
            'customkey_preventduplicatelink' => !empty($_POST['customkey_preventduplicatelink']),
            'nofolo' => sanitize_text_field($_POST['nofolo'] ?? ''),
            'onlysingle' => sanitize_text_field($_POST['onlysingle'] ?? ''),
            'casesens' => sanitize_text_field($_POST['casesens'] ?? ''),
            'allowfeed' => sanitize_text_field($_POST['allowfeed'] ?? ''),
        ]);

        $this->cache->clear_all();

        echo '<div class="updated"><p>' . esc_html__('Plugin settings saved.', 'james-seo-auto-linker') . '</p></div>';
    }

    public function enqueue_assets($hook): void
    {
        if (strpos($hook, 'james-seo-auto-linker') === false)
            return;

        wp_enqueue_script('tagsjs', plugins_url('/js/load.js', dirname(__DIR__, 1) . '/james-seo-auto-linker.php'), ['jquery'], '2.0', true);
        wp_enqueue_style('tagscss', plugins_url('/css/james-seo-auto-linker-style.css', dirname(__DIR__, 1) . '/james-seo-auto-linker.php'), [], '2.0');
        wp_enqueue_script('seopluginjs', plugins_url('/js/set-link-for-seo-plugin.js', dirname(__DIR__, 1) . '/james-seo-auto-linker.php'), ['jquery'], '2.0', true);
    }

    public function ajax_clear_cache(): void
    {
        check_ajax_referer('clear-cache', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }

        $this->cache->clear_all();
        wp_send_json_success(['message' => 'Cache cleared']);
    }
}
