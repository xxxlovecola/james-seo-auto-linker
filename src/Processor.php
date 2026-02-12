<?php

namespace JamesSeoAutoLinker;

/**
 * Handles the core text parsing and keyword replacement logic.
 */
class Processor
{
    private Settings $settings;
    private Cache $cache;

    public function __construct(Settings $settings, Cache $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;
    }

    /**
     * Entry point for content filtering.
     */
    public function filter_content(string $text, bool $is_comment = false): string
    {
        // Check if auto-linking is disabled for this specific post (Gutenberg toggle)
        if (!$is_comment) {
            $post_id = get_the_ID();
            if ($post_id && get_post_meta($post_id, '_james_seo_auto_linker_disabled', true) === '1') {
                return $text;
            }
        }

        return $this->process_text($text, $is_comment);
    }

    /**
     * Main text processing function.
     */
    private function process_text(string $text, bool $is_comment = false): string
    {
        global $post;

        // Early exits
        if (is_feed() && !$this->settings->get('allowfeed')) {
            return $text;
        }

        if ($this->settings->get('onlysingle') && !(is_single() || is_page())) {
            return $text;
        }

        // Check ignored posts
        $ignored_posts = $this->explode_trim(',', (string) $this->settings->get('ignorepost', ''));
        if (is_page($ignored_posts) || is_single($ignored_posts)) {
            return $text;
        }

        // Check post type permissions
        if (!$is_comment && isset($post->post_type)) {
            if ($post->post_type === 'post' && !$this->settings->get('post')) {
                return $text;
            }
            if ($post->post_type === 'page' && !$this->settings->get('page')) {
                return $text;
            }
        }

        // Get current post info for self-linking prevention
        $current_title = '';
        $current_url = '';
        if (!$is_comment && isset($post->post_type)) {
            $prevent_self = ($post->post_type === 'page' && !$this->settings->get('pageself')) ||
                ($post->post_type === 'post' && !$this->settings->get('postself'));
            if ($prevent_self) {
                $current_title = $this->settings->get('casesens') ? $post->post_title : strtolower($post->post_title);
                $current_url = trailingslashit(get_permalink($post->ID));
            }
        }

        // Settings
        $max_links = max(0, (int) $this->settings->get('maxlinks', 3));
        $max_single = max(-1, (int) $this->settings->get('maxsingle', 1));
        $max_single_url = max(0, (int) $this->settings->get('maxsingleurl', 1));
        $min_usage = max(1, (int) $this->settings->get('minusage', 1));

        $links_added = 0;
        $url_counts = [];
        $ignored_keywords = $this->explode_trim(',', (string) $this->settings->get('ignore', ''));

        // Exclude headings from linking
        if ($this->settings->get('excludeheading') === 'on') {
            $text = preg_replace_callback(
                '/<h[1-6][^>]*>.*?<\/h[1-6]>/si',
                fn($match) => preg_replace('/<a[^>]*>(.*?)<\/a>/i', '$1', $match[0]),
                $text
            );
        }

        $case_modifier = $this->settings->get('casesens') ? '' : 'i';
        $regex_template = '/(?<![\p{L}\p{N}])($name)(?![\p{L}\p{N}])/msu' . $case_modifier;
        $strpos_func = $this->settings->get('casesens') ? 'strpos' : 'stripos';

        $text = ' ' . $text . ' ';

        // Protect existing HTML tags and links
        $placeholders = [];
        $text = preg_replace_callback(
            '#<(a|script|style|code|pre|img)[^>]*>.*?</\1>|<[^>]+>#si',
            function ($matches) use (&$placeholders) {
                $placeholder = '{JSAL_BLOCK_' . count($placeholders) . '}';
                $placeholders[$placeholder] = $matches[0];
                return $placeholder;
            },
            $text
        );

        $all_keywords = $this->collect_all_keywords($current_url, $current_title, $ignored_keywords, $min_usage);

        // Process all keywords in optimized order
        foreach ($all_keywords as $item) {
            if ($max_links && $links_added >= $max_links)
                break;
            if ($max_single_url && ($url_counts[$item['url']] ?? 0) >= $max_single_url)
                continue;

            $keyword_normalized = $this->normalize_for_search($item['keyword']);
            $text_normalized = $this->normalize_for_search($text);
            if ($strpos_func($text_normalized, $keyword_normalized) === false)
                continue;

            $keyword_escaped = preg_quote($item['keyword'], '/');
            $quote_regex = "(?:'|’|‘|`|&rsquo;|&lsquo;|&#8217;|&#8216;|&#039;)";
            $keyword_escaped = str_replace(["\'", "’", "‘", "`"], $quote_regex, $keyword_escaped);
            $keyword_escaped = preg_replace('/\\\\\s+/', '\\s+', $keyword_escaped);
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
                $text = (string) $new_text;
            }
        }

        // Restore protected HTML tags
        if (!empty($placeholders)) {
            $text = str_replace(array_keys($placeholders), array_values($placeholders), $text);
        }

        // SEO Filter for external links (from functions.php logic)
        $text = $this->apply_seo_filter($text);

        return trim($text);
    }

    private function collect_all_keywords(string $current_url, string $current_title, array $ignored_keywords, int $min_usage): array
    {
        $all_keywords = [];

        // 1. Custom Keywords
        $custom_keywords = $this->parse_custom_keywords();
        foreach ($custom_keywords as $keyword => $url) {
            $keyword_lower = $this->settings->get('casesens') ? $keyword : strtolower($keyword);
            if (in_array($keyword_lower, $ignored_keywords, true))
                continue;
            if (trailingslashit($url) === $current_url)
                continue;

            $all_keywords[] = [
                'keyword' => $keyword,
                'url' => $url,
                'type' => 'custom',
                'length' => mb_strlen($keyword),
                'is_grouped' => $this->settings->get('customkey_preventduplicatelink')
            ];
        }

        // 2. Posts & Pages
        if ($this->settings->get('lposts') || $this->settings->get('lpages')) {
            $posts = $this->get_cached_data('posts', fn() => $this->fetch_posts());
            foreach ($posts as $post_item) {
                $is_valid = ($this->settings->get('lposts') && $post_item->post_type === 'post') ||
                    ($this->settings->get('lpages') && $post_item->post_type === 'page');
                if (!$is_valid)
                    continue;

                $title_check = $this->settings->get('casesens') ? $post_item->post_title : strtolower($post_item->post_title);
                if ($title_check === $current_title || in_array($title_check, $ignored_keywords, true))
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

        // 3. Taxonomies
        foreach (['category' => 'lcats', 'post_tag' => 'ltags'] as $tax => $setting) {
            if ($this->settings->get($setting)) {
                $terms = $this->get_cached_data("{$tax}_{$min_usage}", fn() => $this->fetch_terms($tax, $min_usage));
                foreach ($terms as $term) {
                    $term_check = $this->settings->get('casesens') ? $term->name : strtolower($term->name);
                    if (in_array($term_check, $ignored_keywords, true))
                        continue;

                    $all_keywords[] = [
                        'keyword' => $term->name,
                        'url' => ($tax === 'category') ? get_category_link($term->term_id) : get_tag_link($term->term_id),
                        'type' => $tax,
                        'length' => mb_strlen($term->name),
                        'is_grouped' => false
                    ];
                }
            }
        }

        // Sort by length (longest first)
        usort($all_keywords, function ($a, $b) {
            if ($b['length'] !== $a['length'])
                return $b['length'] - $a['length'];
            $type_priority = ['custom' => 0, 'post' => 1, 'page' => 1, 'category' => 2, 'post_tag' => 3];
            return ($type_priority[$a['type']] ?? 99) - ($type_priority[$b['type']] ?? 99);
        });

        return $all_keywords;
    }

    private function get_cached_data(string $key, callable $fetcher)
    {
        $data = $this->cache->get($key);
        if (false === $data) {
            $data = $fetcher();
            $this->cache->set($key, $data);
        }
        return $data;
    }

    private function fetch_posts(): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT post_title, ID, post_type FROM {$wpdb->posts} WHERE post_status = %s AND LENGTH(post_title) > %d ORDER BY LENGTH(post_title) DESC LIMIT %d",
            'publish',
            3,
            2000
        )) ?: [];
    }

    private function fetch_terms(string $tax, int $min_usage): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.name, t.term_id FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND LENGTH(t.name) > %d AND tt.count >= %d ORDER BY LENGTH(t.name) DESC LIMIT %d",
            $tax,
            3,
            $min_usage,
            2000
        )) ?: [];
    }

    private function parse_custom_keywords(): array
    {
        $text = (string) $this->settings->get('customkey', '');

        // Remote keywords
        if ($url = $this->settings->get('customkey_url')) {
            $last_fetch = (int) $this->settings->get('customkey_url_datetime', 0);
            if (time() - $last_fetch > DAY_IN_SECONDS) {
                $response = wp_remote_get($url, ['timeout' => 10]);
                if (!is_wp_error($response)) {
                    $body = sanitize_textarea_field(strip_tags(wp_remote_retrieve_body($response)));
                    $this->settings->update([
                        'customkey_url_value' => $body,
                        'customkey_url_datetime' => time()
                    ]);
                }
            }
            $text .= "\n" . (string) $this->settings->get('customkey_url_value', '');
        }

        $keywords = [];
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            if ($this->settings->get('customkey_preventduplicatelink')) {
                $last_comma = strrpos($line, ',');
                if ($last_comma === false)
                    continue;
                $url = trim(substr($line, $last_comma + 1));
                $keyword = trim(substr($line, 0, $last_comma));
                if (!empty($keyword) && !empty($url))
                    $keywords[$keyword] = esc_url_raw($url);
            } else {
                $parts = array_map('trim', explode(',', $line));
                if (count($parts) < 2)
                    continue;
                $url = esc_url_raw(array_pop($parts));
                foreach ($parts as $kw)
                    if (!empty($kw))
                        $keywords[$kw] = $url;
            }
        }
        return $keywords;
    }

    private function apply_seo_filter(string $text): string
    {
        if (!$this->settings->get('nofolo'))
            return $text;

        $site_domain = preg_replace('/^www\./i', '', parse_url(get_bloginfo('wpurl'), PHP_URL_HOST) ?: '');

        return (string) preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])(.*?)\2([^>]*)>/i',
            function ($matches) use ($site_domain) {
                $url = $matches[3];
                if ((strpos($url, '://') === false && strpos($url, '//') !== 0) || empty($site_domain))
                    return $matches[0];

                $url_domain = preg_replace('/^www\./i', '', (string) parse_url($url, PHP_URL_HOST));
                if (strcasecmp($url_domain, $site_domain) === 0)
                    return $matches[0];

                $attrs = trim($matches[1] . ' ' . $matches[4]);
                if (preg_match('/\brel=(["\'])(.*?)\1/i', $attrs, $rel_match)) {
                    if (stripos($rel_match[2], 'nofollow') === false) {
                        $new_rel = trim($rel_match[2] . ' nofollow');
                        $attrs = preg_replace('/\brel=(["\']).*?\1/i', 'rel="' . $new_rel . '"', $attrs, 1);
                    }
                } else {
                    $attrs .= ' rel="nofollow"';
                }
                return '<a ' . trim($attrs) . ' href="' . $url . '">';
            },
            $text
        );
    }

    private function normalize_for_search(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(['‘', '’', '`'], "'", $text);
        $text = str_replace(['“', '”'], '"', $text);
        $text = str_replace(['—', '–', '−'], '-', $text);
        return (string) preg_replace('/[\s\x{00A0}\x{202F}\x{2009}]+/u', ' ', $text);
    }

    private function explode_trim(string $separator, string $text): array
    {
        return array_filter(array_map('trim', explode($separator, $text)));
    }
}
