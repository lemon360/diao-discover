<?php
if (!defined('ABSPATH')) { exit; }
function dh_stats_table() { global $wpdb; return $wpdb->prefix . 'dh_post_stats'; }
function dh_install_stats() {
    global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $table = dh_stats_table();
    dbDelta("CREATE TABLE $table (
        post_id bigint(20) unsigned NOT NULL,
        views bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (post_id)
    ) " . $wpdb->get_charset_collate() . ';');
    update_option('dh_stats_version', '1', false);
}
add_action('after_switch_theme', 'dh_install_stats');
add_action('admin_init', function () { if (get_option('dh_stats_version') !== '1') { dh_install_stats(); } });
function dh_view_counts($ids) {
    global $wpdb;
    $ids = array_slice(array_values(array_unique(array_filter(array_map('absint', $ids)))), 0, 100);
    if (!$ids) { return []; } $counts = array_fill_keys($ids, 0);
    if (get_option('dh_stats_version') !== '1') { return $counts; }
    $slots = implode(',', array_fill(0, count($ids), '%d'));
    $rows = $wpdb->get_results($wpdb->prepare('SELECT post_id, views FROM ' . dh_stats_table() . " WHERE post_id IN ($slots)", ...$ids));
    foreach ($rows as $row) { $counts[(int) $row->post_id] = (int) $row->views; }
    return $counts;
}
function dh_view_count($id) {
    if (!isset($GLOBALS['dh_view_cache'][$id])) { $GLOBALS['dh_view_cache'] = ($GLOBALS['dh_view_cache'] ?? []) + dh_view_counts([$id]); }
    return (int) ($GLOBALS['dh_view_cache'][$id] ?? 0);
}
add_filter('the_posts', function ($posts) {
    if (dh_opt('views_enabled') && $posts) { $GLOBALS['dh_view_cache'] = ($GLOBALS['dh_view_cache'] ?? []) + dh_view_counts(wp_list_pluck($posts, 'ID')); }
    return $posts;
});
function dh_view_badge($id) {
    if (dh_opt('views_enabled') && dh_opt('views_visible')) { echo '<span class="view-meta" title="浏览量估算">' . dh_icon('monitor') . '<span data-view-count="' . esc_attr($id) . '">' . esc_html(dh_number(dh_view_count($id))) . '</span> 次浏览</span>'; }
}
function dh_record_view($id) {
    global $wpdb; $post = get_post($id);
    if (!dh_opt('views_enabled') || get_option('dh_stats_version') !== '1') { return new WP_Error('disabled', '浏览统计尚未启用。', ['status' => 403]); }
    if (!$post || $post->post_type !== 'post' || $post->post_status !== 'publish' || post_password_required($post)) { return new WP_Error('missing', '文章不可用。', ['status' => 404]); }
    $agent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    if (current_user_can('edit_post', $id) || preg_match('/bot|crawl|spider|preview/i', $agent)) { return ['count' => dh_view_counts([$id])[$id], 'counted' => false]; }
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    $key = 'dh_view_' . substr(wp_hash($ip . '|' . $agent . '|' . $id, 'nonce'), 0, 32);
    if (get_transient($key)) { return ['count' => dh_view_counts([$id])[$id], 'counted' => false]; }
    $lock = 'dh_view_lock_' . substr($key, 8);
    $expiry = (int) get_option($lock, 0); if ($expiry && $expiry < time()) { delete_option($lock); }
    if (!add_option($lock, time() + 30, '', false)) { return ['count' => dh_view_counts([$id])[$id], 'counted' => false]; }
    try {
        if (get_transient($key)) { return ['count' => dh_view_counts([$id])[$id], 'counted' => false]; }
        $table = dh_stats_table();
        $result = $wpdb->query($wpdb->prepare("INSERT INTO $table (post_id, views) VALUES (%d,1) ON DUPLICATE KEY UPDATE views=views+1", $id));
        if ($result === false) { return new WP_Error('storage', '浏览统计暂不可用。', ['status' => 500]); }
        set_transient($key, 1, 30 * MINUTE_IN_SECONDS);
        unset($GLOBALS['dh_view_cache'][$id]);
        return ['count' => dh_view_counts([$id])[$id], 'counted' => true];
    } finally { delete_option($lock); }
}
add_action('rest_api_init', function () {
    register_rest_route('diao/v1', '/views', ['methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function ($request) {
        $raw = $request->get_param('ids'); $ids = array_slice(array_filter(array_map('absint', explode(',', is_string($raw) ? $raw : ''))), 0, 100);
        $ids = array_values(array_filter($ids, function ($id) { return get_post_status($id) === 'publish' && get_post_type($id) === 'post'; }));
        return dh_vote_response(['counts' => dh_view_counts($ids)]);
    }]);
    register_rest_route('diao/v1', '/view/(?P<id>\d+)', ['methods' => 'POST', 'permission_callback' => 'dh_vote_same_origin', 'callback' => function ($request) { return dh_vote_response(dh_record_view(absint($request['id']))); }]);
});
add_action('deleted_post', function ($id) { global $wpdb; if (get_option('dh_stats_version') === '1') { $wpdb->delete(dh_stats_table(), ['post_id' => $id], ['%d']); } });
add_filter('manage_post_posts_columns', function ($columns) { $columns['dh_views'] = '浏览量'; return $columns; });
add_action('manage_post_posts_custom_column', function ($column, $id) { if ($column === 'dh_views') { echo esc_html(number_format_i18n(dh_view_count($id))); } }, 10, 2);
function dh_analytics_output($position) {
    static $printed = false;
    if ($printed || !dh_opt('analytics_enabled') || dh_opt('analytics_position') !== $position || !dh_opt('analytics_code') || isset($_GET['dh_newsletter']) || is_admin() || is_feed() || is_preview() || (is_user_logged_in() && !dh_opt('analytics_logged_in'))) { return; }
    $printed = true;
    echo "\n<!-- Discover analytics -->\n" . dh_opt('analytics_code') . "\n"; // Trusted administrator script, guarded by unfiltered_html on save.
}
add_action('wp_head', function () { dh_analytics_output('head'); }, 100);
add_action('wp_footer', function () { dh_analytics_output('footer'); }, 30);
