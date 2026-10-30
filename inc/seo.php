<?php
if (!defined('ABSPATH')) { exit; }
function dh_seo_active() {
    return dh_opt('seo') && !defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION') && !defined('AIOSEO_VERSION') && !defined('SEOPRESS_VERSION');
}
add_filter('pre_get_document_title', function ($title) {
    if (!dh_seo_active()) { return $title; }
    if (isset($_GET['dh_newsletter'])) { return '邮件订阅 · ' . dh_opt('brand'); }
    if (isset($_GET['dh_submit'])) { return '推荐好软件 · ' . dh_opt('brand'); }
    if (isset($_GET['saved']) && is_home()) { return '我的收藏 · ' . dh_opt('brand'); }
    if (is_singular() && dh_meta('seo_title', get_queried_object_id())) { return dh_meta('seo_title', get_queried_object_id()); }
    if (is_front_page() && !is_paged() && dh_opt('seo_title')) { return dh_opt('seo_title'); }
    return $title;
});
add_filter('wp_robots', function ($robots) {
    if (isset($_GET['dh_newsletter']) || (dh_seo_active() && (is_search() || is_404() || isset($_GET['saved']) || isset($_GET['sort']) || isset($_GET['dh_submit'])))) { $robots['noindex'] = true; unset($robots['index']); }
    return $robots;
});
add_action('wp_head', function () {
    if (!dh_seo_active() || is_404() || is_feed()) { return; }
    $id = get_queried_object_id();
    $description = dh_opt('seo_description');
    $image = dh_opt('social_image');
    if (is_singular()) {
        $description = dh_meta('seo_description', $id) ?: wp_trim_words(wp_strip_all_tags(strip_shortcodes(get_the_excerpt($id))), 80, '');
        $image = get_the_post_thumbnail_url($id, 'large') ?: $image;
        $url = wp_get_canonical_url($id) ?: get_permalink($id);
    } elseif (is_category() || is_tag()) {
        $description = wp_strip_all_tags(term_description()) ?: single_term_title('', false) . ' · ' . dh_opt('seo_description');
        $url = get_pagenum_link(max(1, get_query_var('paged')), false);
    } else { $url = get_pagenum_link(max(1, get_query_var('paged')), false); }
    if (is_wp_error($url)) { $url = home_url('/'); }
    $url = remove_query_arg(['sort', 'saved', 'ids', 'dh_submit', 'submitted', 'submit_error', 'dh_newsletter', 'mail_action', 'subscriber', 'token', 'notice'], $url);
    $description = wp_html_excerpt(wp_strip_all_tags($description), 180, '…');
    if (!is_singular()) { echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n"; }
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    $tags = ['og:locale' => str_replace('-', '_', get_bloginfo('language')), 'og:type' => is_single() ? 'article' : 'website', 'og:title' => wp_get_document_title(), 'og:description' => $description, 'og:url' => $url, 'og:site_name' => get_bloginfo('name')];
    if ($image) { $tags['og:image'] = $image; }
    foreach ($tags as $property => $value) { echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($value) . '">' . "\n"; }
    echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
    $schema = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => get_bloginfo('name'), 'url' => home_url('/')];
    if (is_single()) {
        $schema = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => get_the_title($id), 'description' => $description,
            'mainEntityOfPage' => $url, 'datePublished' => get_the_date(DATE_W3C, $id), 'dateModified' => get_the_modified_date(DATE_W3C, $id),
            'author' => ['@type' => 'Person', 'name' => get_the_author_meta('display_name', (int) get_post_field('post_author', $id)), 'url' => get_author_posts_url((int) get_post_field('post_author', $id))]];
        if ($image) { $schema['image'] = $image; }
        if (dh_meta('name', $id)) {
            $schema['about'] = ['@type' => 'SoftwareApplication', 'name' => dh_meta('name', $id)];
            if (dh_meta('platform', $id)) { $schema['about']['operatingSystem'] = dh_meta('platform', $id); }
            if (dh_meta('license', $id)) { $schema['about']['license'] = ['@type' => 'CreativeWork', 'name' => dh_meta('license', $id)]; }
        }
    }
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>' . "\n";
}, 5);
