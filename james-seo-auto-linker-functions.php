<?php
/**
 * Character obfuscation functions
 */
function SEOAutoInSpecChar($str) {
    $chars = str_split($str);
    return implode('<!---->', $chars);
}

function SEOAutoReSpecChar($str) {
    $str = str_replace('<!---->', '', $str);
    return stripslashes($str);
}

/**
 * Add nofollow to external links
 * Properly handles HTTP, HTTPS, and protocol-relative URLs
 */
function SEOAutoTextFilter($options, $result) {
    // Only process if nofollow option is enabled
    if (empty($options['nofolo'])) {
        return $result;
    }
    
    // Get the site's domain (without protocol)
    $site_url = get_bloginfo('wpurl');
    $parsed = parse_url($site_url);
    $site_domain = isset($parsed['host']) ? $parsed['host'] : '';
    
    // Normalize domain (remove www. for comparison)
    $site_domain_clean = preg_replace('/^www\./i', '', $site_domain);
    
    // Process all anchor tags
    $result = preg_replace_callback(
        '/<a\s+([^>]*?)href=(["\'])(.*?)\2([^>]*)>/i',
        function($matches) use ($site_domain_clean) {
            $before_href = $matches[1];
            $quote = $matches[2];
            $url = $matches[3];
            $after_href = $matches[4];
            
            // Skip if it's a relative URL (internal by definition)
            if (strpos($url, '://') === false && strpos($url, '//') !== 0) {
                return $matches[0]; // Return unchanged
            }
            
            // Parse the URL to get the host
            $url_parsed = parse_url($url);
            
            // If no host found, it's relative - skip
            if (empty($url_parsed['host'])) {
                return $matches[0];
            }
            
            // Normalize the URL's domain
            $url_domain = preg_replace('/^www\./i', '', $url_parsed['host']);
            
            // Check if it's the same domain
            if (strcasecmp($url_domain, $site_domain_clean) === 0) {
                return $matches[0]; // Internal link, return unchanged
            }
            
            // It's external - add nofollow
            $all_attrs = trim($before_href . ' ' . $after_href);
            
            // Check if rel attribute already exists
            if (preg_match('/\brel=(["\'])(.*?)\1/i', $all_attrs, $rel_match)) {
                $rel_value = $rel_match[2];
                
                // Add nofollow if not already present
                if (stripos($rel_value, 'nofollow') === false) {
                    $new_rel = trim($rel_value . ' nofollow');
                    $all_attrs = preg_replace(
                        '/\brel=(["\']).*?\1/i',
                        'rel="' . $new_rel . '"',
                        $all_attrs,
                        1
                    );
                }
            } else {
                // No rel attribute exists, add it
                $all_attrs = trim($all_attrs . ' rel="nofollow"');
            }
            
            return '<a ' . $all_attrs . ' href=' . $quote . $url . $quote . '>';
        },
        $result
    );
    
    return $result;
}
?>