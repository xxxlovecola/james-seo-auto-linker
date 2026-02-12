<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="james-seo-auto-linker-admin">
        <form name="SEOAutoLinks" action="<?php echo esc_url($action_url); ?>" method="post" id="seoautoform">
            <?php wp_nonce_field('james-seo-auto-linker', '_wpnonce'); ?>
            <input type="hidden" name="submitted" value="1" />
            
            <!-- Introduction -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('About James SEO Auto Linker', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <p><strong><?php esc_html_e('James SEO Auto Linker', 'james-seo-auto-linker'); ?></strong> <?php esc_html_e('is a modern, high-performance plugin designed to automate your internal linking strategy.', 'james-seo-auto-linker'); ?></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><strong><?php esc_html_e('Gutenberg Ready:', 'james-seo-auto-linker'); ?></strong> <?php esc_html_e('Disable auto-linking on individual posts via the sidebar toggle.', 'james-seo-auto-linker'); ?></li>
                        <li><strong><?php esc_html_e('Persistent Caching:', 'james-seo-auto-linker'); ?></strong> <?php esc_html_e('Uses WordPress Transients to ensure lightning-fast performance across your site.', 'james-seo-auto-linker'); ?></li>
                        <li><strong><?php esc_html_e('Modular Architecture:', 'james-seo-auto-linker'); ?></strong> <?php esc_html_e('Built with clean, namespaced PHP code for maximum reliability.', 'james-seo-auto-linker'); ?></li>
                    </ul>
                    <p style="margin-top: 15px;"><?php printf(
                        esc_html__('Found a bug or have a suggestion? %sVisit our support forum%s.', 'james-seo-auto-linker'),
                        '<a href="https://wordpress.org/support/plugin/james-seo-auto-linker" target="_blank" rel="noopener">',
                        '</a>'
                    ); ?></p>
                </div>
            </div>

            <!-- Custom Keywords -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Custom Keywords', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <p class="description" style="margin-top: 0;">
                        <?php esc_html_e('Manually add keywords to automatically link. Use comma to separate keywords and add target URL at the end. Use a new line for each URL and set of keywords.', 'james-seo-auto-linker'); ?>
                    </p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="customkey"><?php esc_html_e('Keywords & Links', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <textarea 
                                        name="customkey" 
                                        id="customkey" 
                                        rows="10" 
                                        cols="90" 
                                        class="large-text code"
                                        aria-describedby="customkey-description"
                                    ><?php echo esc_textarea($customkey); ?></textarea>
                                    <p class="description" id="customkey-description">
                                        <strong><?php esc_html_e('Examples:', 'james-seo-auto-linker'); ?></strong><br>
                                        <code>google webmaster, https://www.google.com/webmasters/</code><br>
                                        <code>wiki, wikipedia, https://wikipedia.org</code>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Grouped Keywords', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <fieldset>
                                        <label for="customkey_preventduplicatelink">
                                            <input 
                                                type="checkbox" 
                                                name="customkey_preventduplicatelink" 
                                                id="customkey_preventduplicatelink" 
                                                value="1"
                                                <?php checked($customkey_preventduplicatelink, 'checked'); ?>
                                            />
                                            <?php esc_html_e('Prevent duplicate links for grouped keywords', 'james-seo-auto-linker'); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e('Only link the first matching keyword found in text when multiple keywords point to the same URL.', 'james-seo-auto-linker'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="customkey_url"><?php esc_html_e('Load from URL', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="url" 
                                        name="customkey_url" 
                                        id="customkey_url" 
                                        class="regular-text code" 
                                        value="<?php echo esc_url($customkey_url); ?>"
                                        placeholder="https://example.com/keywords.txt"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Load custom keywords from a remote URL. This appends to the list above. Updated daily.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Internal Links Processing -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Where to Process Links', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <p class="description" style="margin-top: 0;">
                        <?php esc_html_e('Choose where James SEO Auto Linker should automatically create links.', 'james-seo-auto-linker'); ?>
                    </p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Posts', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <fieldset>
                                        <label for="post">
                                            <input 
                                                type="checkbox" 
                                                name="post" 
                                                id="post" 
                                                value="on"
                                                <?php checked($post, 'checked'); ?>
                                            />
                                            <strong><?php esc_html_e('Enable auto-linking in posts', 'james-seo-auto-linker'); ?></strong>
                                        </label>
                                        <br>
                                        <label for="postself">
                                            <input 
                                                type="checkbox" 
                                                name="postself" 
                                                id="postself" 
                                                value="on"
                                                <?php checked($postself, 'checked'); ?>
                                            />
                                            <?php esc_html_e('Allow posts to link to themselves', 'james-seo-auto-linker'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Pages', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <fieldset>
                                        <label for="page">
                                            <input 
                                                type="checkbox" 
                                                name="page" 
                                                id="page" 
                                                value="on"
                                                <?php checked($page, 'checked'); ?>
                                            />
                                            <strong><?php esc_html_e('Enable auto-linking in pages', 'james-seo-auto-linker'); ?></strong>
                                        </label>
                                        <br>
                                        <label for="pageself">
                                            <input 
                                                type="checkbox" 
                                                name="pageself" 
                                                id="pageself" 
                                                value="on"
                                                <?php checked($pageself, 'checked'); ?>
                                            />
                                            <?php esc_html_e('Allow pages to link to themselves', 'james-seo-auto-linker'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Comments', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="comment">
                                        <input 
                                            type="checkbox" 
                                            name="comment" 
                                            id="comment" 
                                            value="on"
                                            <?php checked($comment, 'checked'); ?>
                                        />
                                        <strong><?php esc_html_e('Enable auto-linking in comments', 'james-seo-auto-linker'); ?></strong>
                                    </label>
                                    <p class="description">
                                        <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                        <?php esc_html_e('May impact performance on posts with many comments.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('RSS Feeds', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="allowfeed">
                                        <input 
                                            type="checkbox" 
                                            name="allowfeed" 
                                            id="allowfeed" 
                                            value="on"
                                            <?php checked($allowfeed, 'checked'); ?>
                                        />
                                        <strong><?php esc_html_e('Enable auto-linking in RSS feeds', 'james-seo-auto-linker'); ?></strong>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Embed links in all posts in your RSS feed according to other settings.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Link Limits -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Link Limits', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <p class="description" style="margin-top: 0;">
                        <?php esc_html_e('Control the maximum number of links to prevent over-optimization.', 'james-seo-auto-linker'); ?>
                    </p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="maxlinks"><?php esc_html_e('Max Total Links', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        name="maxlinks" 
                                        id="maxlinks" 
                                        min="0" 
                                        max="100" 
                                        class="small-text" 
                                        value="<?php echo esc_attr($maxlinks); ?>"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Maximum number of different links per post. Set to 0 for unlimited.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="maxsingle"><?php esc_html_e('Max Per Keyword', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        name="maxsingle" 
                                        id="maxsingle" 
                                        min="0" 
                                        max="100" 
                                        class="small-text" 
                                        value="<?php echo esc_attr($maxsingle); ?>"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Maximum links created for the same keyword. Set to 0 for unlimited.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="maxsingleurl"><?php esc_html_e('Max Same URLs', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        name="maxsingleurl" 
                                        id="maxsingleurl" 
                                        min="0" 
                                        max="100" 
                                        class="small-text" 
                                        value="<?php echo esc_attr($maxsingleurl); ?>"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Limit links to the same URL. Works when "Max Per Keyword" is set to 1. Set to 0 for unlimited.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Case Sensitivity', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="casesens">
                                        <input 
                                            type="checkbox" 
                                            name="casesens" 
                                            id="casesens" 
                                            value="on"
                                            <?php checked($casesens, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Enable case-sensitive matching', 'james-seo-auto-linker'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('When enabled, "WordPress" and "wordpress" will be treated as different keywords.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Exclusions -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Exclusions', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <p class="description" style="margin-top: 0;">
                        <?php esc_html_e('Prevent linking in specific locations or for specific keywords.', 'james-seo-auto-linker'); ?>
                    </p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Headings', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="excludeheading">
                                        <input 
                                            type="checkbox" 
                                            name="excludeheading" 
                                            id="excludeheading" 
                                            value="on"
                                            <?php checked($excludeheading, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Exclude links in headings (H1-H6)', 'james-seo-auto-linker'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Recommended for better SEO and readability.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ignorepost"><?php esc_html_e('Ignore Posts/Pages', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <div class="tags">
                                        <input 
                                            id="ignorepost" 
                                            type="text" 
                                            name="ignorepost" 
                                            class="large-text" 
                                            value="<?php echo esc_attr($ignorepost); ?>" 
                                            placeholder="<?php esc_attr_e('Add post/page ID, slug, or name', 'james-seo-auto-linker'); ?>"
                                            aria-describedby="ignorepost-description"
                                        />
                                    </div>
                                    <p class="description" id="ignorepost-description">
                                        <?php esc_html_e('Prevent automatic linking on specific posts or pages. Separate by comma (ID, slug, or name).', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ignore"><?php esc_html_e('Ignore Keywords', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <div class="tags">
                                        <input 
                                            id="ignore" 
                                            type="text" 
                                            name="ignore" 
                                            class="large-text" 
                                            value="<?php echo esc_attr($ignore); ?>" 
                                            placeholder="<?php esc_attr_e('Add keyword to ignore', 'james-seo-auto-linker'); ?>"
                                            aria-describedby="ignore-description"
                                        />
                                    </div>
                                    <p class="description" id="ignore-description">
                                        <?php esc_html_e('Words or phrases to exclude from automatic linking. Separate by comma.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Single Posts/Pages Only', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="onlysingle">
                                        <input 
                                            type="checkbox" 
                                            name="onlysingle" 
                                            id="onlysingle" 
                                            value="on"
                                            <?php checked($onlysingle, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Only process on individual post/page views', 'james-seo-auto-linker'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Reduces database load by skipping home page, archives, and category pages.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Link Targets -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Link Targets', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <p class="description" style="margin-top: 0;">
                        <?php esc_html_e('Choose what types of content James SEO Auto Linker should link to. Matching is based on post/page title or category/tag name.', 'james-seo-auto-linker'); ?>
                    </p>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Posts', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="lposts">
                                        <input 
                                            type="checkbox" 
                                            name="lposts" 
                                            id="lposts" 
                                            value="on"
                                            <?php checked($lposts, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Link to posts when their title matches keywords', 'james-seo-auto-linker'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Pages', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="lpages">
                                        <input 
                                            type="checkbox" 
                                            name="lpages" 
                                            id="lpages" 
                                            value="on"
                                            <?php checked($lpages, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Link to pages when their title matches keywords', 'james-seo-auto-linker'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Categories', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="lcats">
                                        <input 
                                            type="checkbox" 
                                            name="lcats" 
                                            id="lcats" 
                                            value="on"
                                            <?php checked($lcats, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Link to categories when their name matches keywords', 'james-seo-auto-linker'); ?>
                                    </label>
                                    <p class="description">
                                        <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                        <?php esc_html_e('May impact performance on sites with many categories.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Tags', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="ltags">
                                        <input 
                                            type="checkbox" 
                                            name="ltags" 
                                            id="ltags" 
                                            value="on"
                                            <?php checked($ltags, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Link to tags when their name matches keywords', 'james-seo-auto-linker'); ?>
                                    </label>
                                    <p class="description">
                                        <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                        <?php esc_html_e('May impact performance on sites with many tags.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="minusage"><?php esc_html_e('Min Category/Tag Usage', 'james-seo-auto-linker'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        name="minusage" 
                                        id="minusage" 
                                        min="1" 
                                        max="100" 
                                        class="small-text" 
                                        value="<?php echo esc_attr($minusage); ?>"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Only link to categories and tags used this many times or more.', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('External Links', 'james-seo-auto-linker'); ?></th>
                                <td>
                                    <label for="nofolo">
                                        <input 
                                            type="checkbox" 
                                            name="nofolo" 
                                            id="nofolo" 
                                            value="on"
                                            <?php checked($nofolo, 'checked'); ?>
                                        />
                                        <?php esc_html_e('Add nofollow to external links', 'james-seo-auto-linker'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Adds rel="nofollow" attribute to all external links (links not pointing to your domain).', 'james-seo-auto-linker'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit Button -->
            <p class="submit">
                <?php submit_button(
                    __('Save All Settings', 'james-seo-auto-linker'),
                    'primary',
                    'submit',
                    false
                ); ?>
                <button type="button" class="button" id="clear-cache-button">
                    <?php esc_html_e('Clear Link Cache', 'james-seo-auto-linker'); ?>
                </button>
            </p>

            <!-- Footer -->
            <div class="postbox" style="margin-top: 20px;">
                <div class="inside" style="text-align: center; padding: 20px;">
                    <p style="margin: 0;">
                        <?php printf(
                            esc_html__('Running James SEO Auto Linker v%s', 'james-seo-auto-linker'),
                            '<strong>1.0</strong>'
                        ); ?>
                        &bull;
                        <?php printf(
                            esc_html__('Like what we do? %sBuy us a drink%s 🍺', 'james-seo-auto-linker'),
                            '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5LRFCEJLZQW7A" target="_blank" rel="noopener">',
                            '</a>'
                        ); ?>
                    </p>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php esc_html_e('Frequently Asked Questions', 'james-seo-auto-linker'); ?></h2>
                </div>
                <div class="inside">
                    <div class="faq-item">
                        <h4 style="margin-bottom: 5px;"><?php esc_html_e('Does this plugin affect site performance?', 'james-seo-auto-linker'); ?></h4>
                        <p style="margin-top: 0;"><?php esc_html_e('No, James SEO Auto Linker uses a smart Transient caching system to ensure your site stays fast by minimizing database queries.', 'james-seo-auto-linker'); ?></p>
                    </div>
                    <div class="faq-item">
                        <h4 style="margin-bottom: 5px;"><?php esc_html_e('Can I link to external websites?', 'james-seo-auto-linker'); ?></h4>
                        <p style="margin-top: 0;"><?php esc_html_e('Yes! In the "Custom Keywords" section, you can specify any URL, including external sites.', 'james-seo-auto-linker'); ?></p>
                    </div>
                    <div class="faq-item">
                        <h4 style="margin-bottom: 5px;"><?php esc_html_e('Can I prevent certain words from being linked?', 'james-seo-auto-linker'); ?></h4>
                        <p style="margin-top: 0;"><?php esc_html_e('Yes, you can add words or phrases to the "Ignore Keywords" list under Exclusions to exclude them.', 'james-seo-auto-linker'); ?></p>
                    </div>
                    <div class="faq-item">
                        <h4 style="margin-bottom: 5px;"><?php esc_html_e('How do I disable auto-linking for a single post?', 'james-seo-auto-linker'); ?></h4>
                        <p style="margin-top: 0;"><?php esc_html_e('When editing a post or page in Gutenberg, look for the "SEO Auto Linker" panel in the sidebar to find the disable toggle.', 'james-seo-auto-linker'); ?></p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Clear cache button
    $('#clear-cache-button').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php echo esc_js(__('Are you sure you want to clear the link cache?', 'james-seo-auto-linker')); ?>')) {
            $.post(ajaxurl, {
                action: 'seo_auto_linker_clear_cache',
                nonce: '<?php echo esc_js(wp_create_nonce('clear-cache')); ?>'
            }, function(response) {
                alert('<?php echo esc_js(__('Cache cleared successfully!', 'james-seo-auto-linker')); ?>');
            });
        }
    });
    
    // Show save confirmation
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('settings-updated') === 'true') {
        // WordPress already shows this, but we can enhance it
    }
});
</script>

<style>
.james-seo-auto-linker-admin .postbox {
    margin-bottom: 20px;
}
.james-seo-auto-linker-admin .postbox-header {
    border-bottom: 1px solid #ccd0d4;
}
.james-seo-auto-linker-admin .inside {
    padding: 15px;
}
.james-seo-auto-linker-admin .form-table th {
    width: 220px;
    padding: 15px 10px 15px 0;
}
.james-seo-auto-linker-admin .form-table td {
    padding: 15px 10px;
}
.james-seo-auto-linker-admin fieldset label {
    display: block;
    margin: 5px 0;
}
.james-seo-auto-linker-admin .description {
    margin-top: 5px;
}
</style>