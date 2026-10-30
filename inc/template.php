<?php
if (!defined('ABSPATH')) { exit; }
function dh_icon($name, $class = '') {
    $paths = [
        'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>', 'external' => '<path d="M14 4h6v6m0-6L10 14m0-9H5a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1v-5"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>',
        'bookmark' => '<path d="M6 4h12v17l-6-4-6 4z"/>', 'up' => '<path d="m7 14 5-6 5 6z"/>',
        'grid' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'flame' => '<path d="M13 3c2 6-3 7-1 10 2 0 3-2 3-4 4 3 5 6 3 9-3 6-13 4-13-2 0-5 5-5 8-13z"/>',
        'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>',
        'code' => '<path d="m8 6-6 6 6 6m8-12 6 6-6 6m-2-14-4 16"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>',
        'phone' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>',
        'book' => '<path d="M12 5c-4-3-8-2-9-1v15c4-2 7-1 9 1 2-2 5-3 9-1V4c-4-2-7-1-9 1zm0 0v15"/>',
        'moon' => '<path d="M20 14a9 9 0 0 1-10-10A9 9 0 1 0 20 14z"/>', 'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>', 'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m9 10 6-4m-6 8 6 4"/>',
        'comment' => '<path d="M21 11a8 8 0 0 1-8 8H6l-4 3V11a9 9 0 0 1 19 0z"/>',
        'github' => '<path d="M9 19c-4 1-4-2-6-2m12 5v-4a3.5 3.5 0 0 0-1-2.7c3-.4 6-1.4 6-6.3a5 5 0 0 0-1.5-3.5A5 5 0 0 0 18.4 2S17.2 1.6 15 3a13 13 0 0 0-6 0C6.8 1.6 5.6 2 5.6 2a5 5 0 0 0-.1 3.5A5 5 0 0 0 4 9c0 4.9 3 5.9 6 6.3A3.5 3.5 0 0 0 9 18v4"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>', 'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
    ];
    return '<svg class="icon ' . esc_attr($class) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['grid']) . '</svg>';
}
function dh_title($id = 0) { $id = $id ?: get_the_ID(); return dh_meta('name', $id) ?: get_the_title($id); }
function dh_summary($id = 0) { $id = $id ?: get_the_ID(); return dh_meta('summary', $id) ?: wp_html_excerpt(wp_strip_all_tags(strip_shortcodes(get_the_excerpt($id))), 95, '…'); }
function dh_number($number) { return $number >= 1000 ? rtrim(rtrim(number_format($number / 1000, 1), '0'), '.') . 'k' : number_format_i18n($number); }
function dh_submit_url() { return dh_opt('contact_url') ?: add_query_arg('dh_submit', '1', home_url('/')); }
function dh_newsletter_url() { return add_query_arg('dh_newsletter', '1', home_url('/')); }
function dh_manifesto_url() {
    switch (dh_opt('manifesto_target')) {
        case 'newsletter': return dh_opt('mail_enabled') ? dh_newsletter_url() : '';
        case 'rss': return dh_feed_available() ? get_feed_link() : '';
        case 'custom': return dh_opt('manifesto_url');
        default: return '';
    }
}
function dh_product_icon($id = 0, $size = 'dh-icon') {
    $id = $id ?: get_the_ID();
    $colors = ['#e9e7fd', '#e4f1e7', '#fff0dc', '#e3edff', '#ffe8e5', '#e3f4f0'];
    echo '<span class="product-icon" style="--tile:' . esc_attr($colors[$id % count($colors)]) . '">';
    if (has_post_thumbnail($id)) { echo get_the_post_thumbnail($id, $size, ['loading' => 'lazy']); }
    else { echo esc_html(function_exists('mb_substr') ? mb_substr(dh_title($id), 0, 1) : wp_html_excerpt(dh_title($id), 1)); }
    echo '</span>';
}
function dh_expand_categories($ids) {
    $expanded = array_filter(array_map('absint', $ids));
    foreach ($expanded as $id) { $children = get_term_children($id, 'category'); if (!is_wp_error($children)) { $expanded = array_merge($expanded, $children); } }
    return array_values(array_unique(array_map('absint', $expanded)));
}
function dh_editorial_categories() {
    $ids = array_filter(array_map('absint', explode(',', dh_opt('editorial_categories'))));
    if (!$ids) {
        foreach (['tutorial', 'tutorials', 'jiao', 'news', '资讯', '教程', '教程资讯', '教程/资讯'] as $value) {
            $term = get_category_by_slug($value) ?: get_term_by('name', $value, 'category');
            if ($term) { $ids[] = $term->term_id; }
        }
    }
    return dh_expand_categories($ids);
}
function dh_app_excluded_categories() {
    return dh_expand_categories(array_merge(dh_editorial_categories(), array_filter(array_map('absint', explode(',', dh_opt('excluded_categories'))))));
}
function dh_categories() {
    $excluded = dh_app_excluded_categories();
    return get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => $excluded]);
}
function dh_nav_categories() {
    $items = [];
    $excluded = dh_app_excluded_categories();
    foreach (['ai', 'efficiency', 'tools', 'code', 'graphics', 'video', 'office', 'download', 'mobile', 'tv', 'online', 'plugins'] as $slug) {
        $term = get_category_by_slug($slug); if ($term && !in_array((int) $term->term_id, $excluded, true)) { $items[] = $term; }
    }
    if (!$items) { $items = array_slice(dh_categories(), 0, 12); }
    return $items;
}
function dh_vote_button($id) {
    if (!dh_opt('votes')) { return; }
    $active = dh_has_voted($id);
    echo '<button class="vote-button ' . ($active ? 'is-active' : '') . '" data-vote="' . esc_attr($id) . '" aria-label="推荐 ' . esc_attr(dh_title($id)) . '" aria-pressed="' . ($active ? 'true' : 'false') . '">' . dh_icon('up') . '<span>' . esc_html(dh_number(dh_vote_count($id))) . '</span></button>';
}
function dh_bookmark_button($id, $label = false) {
    if (dh_opt('bookmarks')) { echo '<button class="' . ($label ? 'button secondary' : 'icon-button bookmark-button') . '" data-bookmark="' . esc_attr($id) . '" aria-label="收藏 ' . esc_attr(dh_title($id)) . '" aria-pressed="false">' . dh_icon('bookmark') . ($label ? '<span>收藏</span>' : '') . '</button>'; }
}
function dh_ad($slot) {
    if (!dh_opt('ad_enabled')) { return; }
    $html = dh_opt('ad_' . $slot . '_html'); $image = dh_opt('ad_' . $slot . '_image'); $url = dh_opt('ad_' . $slot . '_url'); $title = dh_opt('ad_' . $slot . '_title');
    if (!$html && (!$image || !$url)) { return; }
    echo '<aside class="ad-slot ad-' . esc_attr($slot) . '" aria-label="广告"><span class="ad-label">赞助推广</span>';
    // Trusted executable markup can only be saved by administrators with unfiltered_html.
    if ($html) { echo $html; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    else { echo '<a href="' . esc_url($url) . '" target="_blank" rel="sponsored noopener nofollow"><img loading="lazy" src="' . esc_url($image) . '" alt="' . esc_attr($title ?: '赞助推广') . '"></a>'; }
    echo '</aside>';
}
function dh_primary_fallback() {
    echo '<ul class="primary-menu"><li><a class="' . (is_home() ? 'active' : '') . '" href="' . esc_url(home_url('/')) . '">发现</a></li>';
    foreach (['pc' => '电脑软件', 'mobile' => '手机软件', 'online' => '在线工具', 'tutorial' => '教程资讯'] as $slug => $label) {
        $term = get_category_by_slug($slug); if ($term) { echo '<li><a href="' . esc_url(get_category_link($term)) . '">' . esc_html($label) . '</a></li>'; }
    }
    echo '</ul>';
}
