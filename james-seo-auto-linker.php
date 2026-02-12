<?php
/*
Plugin Name: James SEO Auto Linker
Version: 1.0
Author: James Colin
Description: James SEO Auto Linker inserts links on keywords automatically with optimized performance and security.
*/

if (!class_exists('SEOAutoLinks')):
    class SEOAutoLinks
    {
        private $db_option = 'JamesSEOAutoLinks';
        private $options;

        public function __construct()
        {
            $this->options = $this->get_options();

            // Content filters
            if ($this->options['post'] || $this->options['page']) {
                add_filter('the_content', [$this, 'filter_content'], 10);
            }
            if ($this->options['comment']) {
                add_filter('comment_text', [$this, 'filter_comment'], 10);
            }

            // Cache invalidation hooks
            add_action('create_category', [$this, 'delete_cache']);
            add_action('edit_category', [$this, 'delete_cache']);
            add_action('edit_post', [$this, 'delete_cache']);
            add_action('save_post', [$this, 'delete_cache']);

            // Admin
            add_action('admin_menu', [$this, 'admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

            // AJAX cache clear
            add_action('wp_ajax_seo_auto_linker_clear_cache', [$this, 'ajax_clear_cache']);

            load_plugin_textdomain('james-seo-auto-linker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * Main text processing function with improved matching
         */
        public function process_text($text, $is_comment = false)
        {
            global $wpdb, $post;

            // Early exits
            if (is_feed() && !$this->options['allowfeed']) {
                return $text;
            }

            if ($this->options['onlysingle'] && !(is_single() || is_page())) {
                return $text;
            }

            // Check ignored posts
            $ignored_posts = $this->explode_trim(',', $this->options['ignorepost']);
            if (is_page($ignored_posts) || is_single($ignored_posts)) {
                return $text;
            }

            // Check post type permissions
            if (!$is_comment && isset($post->post_type)) {
                if ($post->post_type === 'post' && !$this->options['post']) {
                    return $text;
                }
                if ($post->post_type === 'page' && !$this->options['page']) {
                    return $text;
                }
            }

            // Get current post info for self-linking prevention
            $current_title = '';
            $current_url = '';
            if (!$is_comment && isset($post->post_type)) {
                $prevent_self = ($post->post_type === 'page' && !$this->options['pageself']) ||
                    ($post->post_type === 'post' && !$this->options['postself']);
                if ($prevent_self) {
                    $current_title = $this->options['casesens'] ? $post->post_title : strtolower($post->post_title);
                    $current_url = trailingslashit(get_permalink($post->ID));
                }
            }

            // Settings
            $max_links = max(0, (int) $this->options['maxlinks']);
            $max_single = max(-1, (int) $this->options['maxsingle']);
            $max_single_url = max(0, (int) $this->options['maxsingleurl']);
            $min_usage = max(1, (int) $this->options['minusage']);

            $links_added = 0;
            $url_counts = [];
            $ignored_keywords = $this->explode_trim(',', $this->options['ignore']);

            // Exclude headings from linking
            if ($this->options['excludeheading'] === 'on') {
                $text = preg_replace_callback(
                    '/<h[1-6][^>]*>.*?<\/h[1-6]>/si',
                    function ($match) {
                        return preg_replace('/<a[^>]*>(.*?)<\/a>/i', '$1', $match[0]);
                    },
                    $text
                );
            }

            // 1. Improved regex patterns - Unicode aware (\p{L} matches any letter, including accented ones)
            $case_modifier = $this->options['casesens'] ? '' : 'i';
            // This boundary ensures we don't match "word" inside "swords", but allows "L'Hiver"
            $regex_template = '/(?<![\p{L}\p{N}])($name)(?![\p{L}\p{N}])/msu' . $case_modifier;
            $strpos_func = $this->options['casesens'] ? 'strpos' : 'stripos';

            $text = ' ' . $text . ' ';

            // 2. Protect existing HTML tags and links so we don't link inside them
// We replace them with placeholders, then swap them back at the end
            $placeholders = [];
            $text = preg_replace_callback(
                '#<(a|script|style|code|pre|img)[^>]*>.*?</\1>|<[^>]+>#si',
                function ($matches) use (&$placeholders) {
                    $placeholder = '{WPA_BLOCK_' . count($placeholders) . '}';
                    $placeholders[$placeholder] = $matches[0];
                    return $placeholder;
                },
                $text
            );

            // STEP 1: Collect ALL potential keywords from all sources
            $all_keywords = [];

            // Collect custom keywords
            if (!empty($this->options['customkey']) || !empty($this->options['customkey_url'])) {
                $custom_keywords = $this->parse_custom_keywords();
                foreach ($custom_keywords as $keyword => $url) {
                    $keyword_lower = $this->options['casesens'] ? $keyword : strtolower($keyword);

                    // Skip if ignored or same as current URL
                    if (in_array($keyword_lower, $ignored_keywords, true))
                        continue;
                    if (trailingslashit($url) === $current_url)
                        continue;

                    $all_keywords[] = [
                        'keyword' => $keyword,
                        'url' => $url,
                        'type' => 'custom',
                        'length' => mb_strlen($keyword),
                        'is_grouped' => $this->options['customkey_preventduplicatelink']
                    ];
                }
            }

            // Collect posts
            if ($this->options['lposts'] || $this->options['lpages']) {
                $posts = $this->get_cached_posts();
                foreach ($posts as $post_item) {
                    $is_valid = ($this->options['lposts'] && $post_item->post_type === 'post') ||
                        ($this->options['lpages'] && $post_item->post_type === 'page');

                    if (!$is_valid)
                        continue;

                    $title_check = $this->options['casesens'] ? $post_item->post_title : strtolower($post_item->post_title);

                    // Skip if same as current post or ignored
                    if ($title_check === $current_title)
                        continue;
                    if (in_array($title_check, $ignored_keywords, true))
                        continue;

                    $all_keywords[] = [
                        'keyword' => $post_item->post_title,
                        'url' => get_permalink($post_item->ID),
                        'type' => 'post',
                        'length' => mb_strlen($post_item->post_title),
                        'is_grouped' => false
                    ];
                }
            }

            // Collect categories
            if ($this->options['lcats']) {
                $categories = $this->get_cached_categories($min_usage);
                foreach ($categories as $cat) {
                    $cat_check = $this->options['casesens'] ? $cat->name : strtolower($cat->name);

                    if (in_array($cat_check, $ignored_keywords, true))
                        continue;

                    $all_keywords[] = [
                        'keyword' => $cat->name,
                        'url' => get_category_link($cat->term_id),
                        'type' => 'category',
                        'length' => mb_strlen($cat->name),
                        'is_grouped' => false
                    ];
                }
            }

            // Collect tags
            if ($this->options['ltags']) {
                $tags = $this->get_cached_tags($min_usage);
                foreach ($tags as $tag) {
                    $tag_check = $this->options['casesens'] ? $tag->name : strtolower($tag->name);

                    if (in_array($tag_check, $ignored_keywords, true))
                        continue;

                    $all_keywords[] = [
                        'keyword' => $tag->name,
                        'url' => get_tag_link($tag->term_id),
                        'type' => 'tag',
                        'length' => mb_strlen($tag->name),
                        'is_grouped' => false
                    ];
                }
            }

            // STEP 2: Sort by length (longest first) - prevents shorter keywords from breaking longer ones
            usort($all_keywords, function ($a, $b) {
                if ($b['length'] !== $a['length']) {
                    return $b['length'] - $a['length'];
                }
                // Secondary sort: custom keywords first (manually curated)
                $type_priority = ['custom' => 0, 'post' => 1, 'page' => 1, 'category' => 2, 'tag' => 3];
                return ($type_priority[$a['type']] ?? 99) - ($type_priority[$b['type']] ?? 99);
            });

            // STEP 3: Process all keywords in optimized order
            foreach ($all_keywords as $item) {
                if ($max_links && $links_added >= $max_links)
                    break;
                if ($max_single_url && ($url_counts[$item['url']] ?? 0) >= $max_single_url)
                    continue;

                // Normalize for the quick 'strpos' check
                $keyword_normalized = $this->normalize_for_search($item['keyword']);
                $text_normalized = $this->normalize_for_search($text);
                if ($strpos_func($text_normalized, $keyword_normalized) === false)
                    continue;

                // Build the "Fuzzy Quote" regex for the keyword
                $keyword_escaped = preg_quote($item['keyword'], '/');

                // Handle different types of apostrophes and their HTML entities
                // This allows L'Hiver to match L’Hiver, L'Hiver, and L&rsquo;Hiver
                $quote_regex = "(?:'|’|‘|`|&rsquo;|&lsquo;|&#8217;|&#8216;|&#039;)";
                $keyword_escaped = str_replace(["\'", "’", "‘", "`"], $quote_regex, $keyword_escaped);

                // Flexible whitespace
                $keyword_escaped = preg_replace('/\\\\\s+/', '\\s+', $keyword_escaped);

                // Optional hyphens
                $keyword_escaped = str_replace('\-', '\-?', $keyword_escaped);

                if ($item['is_grouped']) {
                    $keyword_escaped = str_replace(',', '|', $keyword_escaped);
                }

                $regex = str_replace('$name', $keyword_escaped, $regex_template);
                $replacement = '<a title="$1" href="' . esc_url($item['url']) . '">$1</a>';

                $new_text = preg_replace($regex, $replacement, $text, $max_single);

                if ($new_text !== $text) {
                    $links_added++;
                    $url_counts[$item['url']] = ($url_counts[$item['url']] ?? 0) + 1;
                    $text = $new_text;
                }
            }

            // 4. Restore the protected HTML tags
            if (!empty($placeholders)) {
                $text = str_replace(array_keys($placeholders), array_values($placeholders), $text);
            }

            return trim($text);
        }

        /**
         * Normalize text for searching (handles HTML entities, quotes, whitespace)
         */
        private function normalize_for_search($text)
        {
            // Decode HTML entities
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Normalize all apostrophes to single type
            $text = str_replace(['‘', '’', '`'], "'", $text);

            // Normalize all quotes
            $text = str_replace(['“', '”'], '"', $text);

            // Normalize all dashes to regular hyphen
            $text = str_replace(['—', '–', '−'], '-', $text);

            // Normalize whitespace (including nbsp and other unicode spaces)
            $text = preg_replace('/[\s\x{00A0}\x{202F}\x{2009}]+/u', ' ', $text);

            return $text;
        }

        /**
         * Parse custom keywords from settings
         */
        private function parse_custom_keywords()
        {
            $custom_text = $this->options['customkey'] ?? '';

            // Fetch remote keywords if configured
            if (!empty($this->options['customkey_url'])) {
                $last_fetch = (int) ($this->options['customkey_url_datetime'] ?? 0);
                $now = time();

                // Refresh every 24 hours
                if ($now - $last_fetch > 86400) {
                    $response = wp_remote_get($this->options['customkey_url'], ['timeout' => 10]);
                    if (!is_wp_error($response)) {
                        $body = wp_remote_retrieve_body($response);
                        $this->options['customkey_url_value'] = sanitize_textarea_field(strip_tags($body));
                        $this->options['customkey_url_datetime'] = $now;
                        update_option($this->db_option, $this->options);
                    }
                }

                $custom_text .= "\n" . ($this->options['customkey_url_value'] ?? '');
            }

            $keywords = [];
            $lines = explode("\n", $custom_text);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line))
                    continue;

                if ($this->options['customkey_preventduplicatelink']) {
                    // Format: "keyword1, keyword2, url" - last comma separates URL
                    $last_comma = strrpos($line, ',');
                    if ($last_comma === false)
                        continue;

                    $url = trim(substr($line, $last_comma + 1));
                    $keyword_string = trim(substr($line, 0, $last_comma));

                    if (!empty($keyword_string) && !empty($url)) {
                        $keywords[$keyword_string] = esc_url_raw($url);
                    }
                } else {
                    // Format: "keyword, url" or "kw1, kw2, kw3, url"
                    $parts = array_map('trim', explode(',', $line));
                    if (count($parts) < 2)
                        continue;

                    $url = esc_url_raw(array_pop($parts));
                    foreach ($parts as $keyword) {
                        if (!empty($keyword)) {
                            $keywords[$keyword] = $url;
                        }
                    }
                }
            }

            return $keywords;
        }

        /**
         * Get cached posts (with prepared statement)
         */
        private function get_cached_posts()
        {
            global $wpdb;

            $cache_key = 'seo-links-posts';
            $posts = wp_cache_get($cache_key, 'james-seo-auto-linker');

            if (false === $posts) {
                $query = $wpdb->prepare(
                    "SELECT post_title, ID, post_type FROM {$wpdb->posts} 
                    WHERE post_status = %s AND LENGTH(post_title) > %d 
                    ORDER BY LENGTH(post_title) DESC LIMIT %d",
                    'publish',
                    3,
                    2000
                );
                $posts = $wpdb->get_results($query);
                wp_cache_set($cache_key, $posts, 'james-seo-auto-linker', 86400);
            }

            return $posts ?: [];
        }

        /**
         * Get cached categories (with prepared statement)
         */
        private function get_cached_categories($min_usage)
        {
            global $wpdb;

            $cache_key = 'seo-links-categories-' . $min_usage;
            $categories = wp_cache_get($cache_key, 'james-seo-auto-linker');

            if (false === $categories) {
                $query = $wpdb->prepare(
                    "SELECT t.name, t.term_id 
                    FROM {$wpdb->terms} t 
                    LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                    WHERE tt.taxonomy = %s 
                    AND LENGTH(t.name) > %d 
                    AND tt.count >= %d 
                    ORDER BY LENGTH(t.name) DESC LIMIT %d",
                    'category',
                    3,
                    $min_usage,
                    2000
                );
                $categories = $wpdb->get_results($query);
                wp_cache_set($cache_key, $categories, 'james-seo-auto-linker', 86400);
            }

            return $categories ?: [];
        }

        /**
         * Get cached tags (with prepared statement)
         */
        private function get_cached_tags($min_usage)
        {
            global $wpdb;

            $cache_key = 'seo-links-tags-' . $min_usage;
            $tags = wp_cache_get($cache_key, 'james-seo-auto-linker');

            if (false === $tags) {
                $query = $wpdb->prepare(
                    "SELECT t.name, t.term_id 
                    FROM {$wpdb->terms} t 
                    LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                    WHERE tt.taxonomy = %s 
                    AND LENGTH(t.name) > %d 
                    AND tt.count >= %d 
                    ORDER BY LENGTH(t.name) DESC LIMIT %d",
                    'post_tag',
                    3,
                    $min_usage,
                    2000
                );
                $tags = $wpdb->get_results($query);
                wp_cache_set($cache_key, $tags, 'james-seo-auto-linker', 86400);
            }

            return $tags ?: [];
        }

        /**
         * Filter functions
         */
        public function filter_content($text)
        {
            return SEOAutoTextFilter($this->options, $this->process_text($text, false));
        }

        public function filter_comment($text)
        {
            return SEOAutoTextFilter($this->options, $this->process_text($text, true));
        }

        /**
         * Utility: explode and trim
         */
        private function explode_trim($separator, $text)
        {
            return array_map('trim', explode($separator, $text));
        }

        /**
         * Get options with defaults
         */
        public function get_options()
        {
            $defaults = [
                'post' => 'on',
                'postself' => '',
                'page' => 'on',
                'pageself' => '',
                'comment' => '',
                'excludeheading' => 'on',
                'lposts' => 'on',
                'lpages' => 'on',
                'lcats' => '',
                'ltags' => '',
                'ignore' => 'about',
                'ignorepost' => 'contact',
                'maxlinks' => 3,
                'maxsingle' => 1,
                'minusage' => 1,
                'customkey' => '',
                'customkey_preventduplicatelink' => false,
                'customkey_url' => '',
                'customkey_url_value' => '',
                'customkey_url_datetime' => '',
                'nofoln' => '',
                'nofolo' => '',
                'blankn' => '',
                'onlysingle' => 'on',
                'casesens' => '',
                'allowfeed' => '',
                'maxsingleurl' => '1'
            ];

            $saved = get_option($this->db_option);

            if (!empty($saved)) {
                $defaults = array_merge($defaults, $saved);
            } else {
                update_option($this->db_option, $defaults);
            }

            return $defaults;
        }

        /**
         * Installation
         */
        public function install()
        {
            $this->get_options();
        }

        /**
         * Handle admin options (with security hardening)
         */
        public function handle_options()
        {
            $options = $this->get_options();

            if (isset($_POST['submitted'])) {
                check_admin_referer('james-seo-auto-linker');

                // Sanitize all inputs
                $options['post'] = sanitize_text_field($_POST['post'] ?? '');
                $options['postself'] = sanitize_text_field($_POST['postself'] ?? '');
                $options['page'] = sanitize_text_field($_POST['page'] ?? '');
                $options['pageself'] = sanitize_text_field($_POST['pageself'] ?? '');
                $options['comment'] = sanitize_text_field($_POST['comment'] ?? '');
                $options['excludeheading'] = sanitize_text_field($_POST['excludeheading'] ?? '');
                $options['lposts'] = sanitize_text_field($_POST['lposts'] ?? '');
                $options['lpages'] = sanitize_text_field($_POST['lpages'] ?? '');
                $options['lcats'] = sanitize_text_field($_POST['lcats'] ?? '');
                $options['ltags'] = sanitize_text_field($_POST['ltags'] ?? '');
                $options['ignore'] = sanitize_text_field($_POST['ignore'] ?? '');
                $options['ignorepost'] = sanitize_text_field($_POST['ignorepost'] ?? '');
                $options['maxlinks'] = absint($_POST['maxlinks'] ?? 3);
                $options['maxsingle'] = absint($_POST['maxsingle'] ?? 1);
                $options['maxsingleurl'] = absint($_POST['maxsingleurl'] ?? 1);
                $options['minusage'] = absint($_POST['minusage'] ?? 1);
                $options['customkey'] = sanitize_textarea_field($_POST['customkey'] ?? '');
                $options['customkey_url'] = esc_url_raw($_POST['customkey_url'] ?? '');
                $options['customkey_preventduplicatelink'] = !empty($_POST['customkey_preventduplicatelink']);
                $options['nofoln'] = sanitize_text_field($_POST['nofoln'] ?? '');
                $options['nofolo'] = sanitize_text_field($_POST['nofolo'] ?? '');
                $options['blankn'] = sanitize_text_field($_POST['blankn'] ?? '');
                $options['onlysingle'] = sanitize_text_field($_POST['onlysingle'] ?? '');
                $options['casesens'] = sanitize_text_field($_POST['casesens'] ?? '');
                $options['allowfeed'] = sanitize_text_field($_POST['allowfeed'] ?? '');

                update_option($this->db_option, $options);
                $this->delete_cache(0);

                echo '<div class="updated"><p>' . esc_html__('Plugin settings saved.', 'james-seo-auto-linker') . '</p></div>';
            }

            // Prepare variables for admin template (with proper escaping)
            $action_url = esc_url($_SERVER['REQUEST_URI']);
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
            $nofoln = ($options['nofoln'] === 'on') ? 'checked' : '';
            $nofolo = ($options['nofolo'] === 'on') ? 'checked' : '';
            $blankn = ($options['blankn'] === 'on') ? 'checked' : '';
            $onlysingle = ($options['onlysingle'] === 'on') ? 'checked' : '';
            $casesens = ($options['casesens'] === 'on') ? 'checked' : '';
            $allowfeed = ($options['allowfeed'] === 'on') ? 'checked' : '';
            $nonce = wp_create_nonce('james-seo-auto-linker');

            require_once dirname(__FILE__) . '/james-seo-auto-linker-admin.php';
        }

        /**
         * Admin menu
         */
        public function admin_menu()
        {
            add_options_page(
                'James SEO Auto Linker Options',
                'James SEO Auto Linker',
                'manage_options',
                basename(__FILE__),
                [$this, 'handle_options']
            );
        }

        /**
         * Enqueue admin scripts
         */
        public function enqueue_admin_scripts($hook)
        {
            if (strpos($hook, 'james-seo-auto-linker') === false) {
                return;
            }

            wp_enqueue_script('tagsjs', plugins_url('/js/load.js', __FILE__), ['jquery'], '2.0', true);
            wp_enqueue_style('tagscss', plugins_url('/css/james-seo-auto-linker-style.css', __FILE__), [], '2.0');
            wp_enqueue_script('seopluginjs', plugins_url('/js/set-link-for-seo-plugin.js', __FILE__), ['jquery'], '2.0', true);
        }

        /**
         * AJAX: Clear cache
         */
        public function ajax_clear_cache()
        {
            check_ajax_referer('clear-cache', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Unauthorized']);
                return;
            }

            $this->delete_cache(0);
            wp_send_json_success(['message' => 'Cache cleared']);
        }

        /**
         * Delete cache
         */
        public function delete_cache($id)
        {
            // Delete all cache variations
            wp_cache_delete('seo-links-posts', 'james-seo-auto-linker');

            // Delete category and tag caches for different min_usage values
            for ($i = 1; $i <= 20; $i++) {
                wp_cache_delete('seo-links-categories-' . $i, 'james-seo-auto-linker');
                wp_cache_delete('seo-links-tags-' . $i, 'james-seo-auto-linker');
            }
        }
    }
endif;

if (class_exists('SEOAutoLinks')):
    $SEOAutoLinks = new SEOAutoLinks();
    if (isset($SEOAutoLinks)) {
        register_activation_hook(__FILE__, [$SEOAutoLinks, 'install']);
    }
endif;

require_once dirname(__FILE__) . '/james-seo-auto-linker-functions.php';
?>