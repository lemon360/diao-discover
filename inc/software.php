<?php
if (!defined('ABSPATH')) { exit; }
function dh_software_fields() {
    return ['name' => '软件短名称', 'summary' => '一句话介绍', 'repo' => 'GitHub 仓库 URL', 'website' => '官方网站 URL',
        'download' => '官方下载 URL', 'platform' => '平台（例如 Windows, macOS, Linux）', 'license' => '许可证（如 MIT；留空不标开源）',
        'version' => '版本号', 'badge' => '特色标签（如 编辑推荐）', 'seo_title' => '自定义 SEO 标题', 'seo_description' => '自定义 SEO 描述'];
}
function dh_meta($key, $id = 0) { return get_post_meta($id ?: get_the_ID(), '_dh_' . $key, true); }
function dh_repo_path($url) {
    $parts = wp_parse_url($url);
    if (!is_array($parts) || strtolower($parts['host'] ?? '') !== 'github.com' || ($parts['scheme'] ?? '') !== 'https' || isset($parts['port']) || isset($parts['user'])) { return ''; }
    if (!preg_match('~^/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)/?$~D', $parts['path'] ?? '', $m)) { return ''; }
    if (in_array($m[1], ['.', '..'], true) || in_array($m[2], ['.', '..'], true)) { return ''; }
    return $m[1] . '/' . preg_replace('/\.git$/', '', $m[2]);
}
function dh_repo($id = 0) {
    $id = $id ?: get_the_ID();
    $explicit = dh_meta('repo', $id);
    if ($explicit) { return dh_repo_path($explicit) ? $explicit : ''; }
    // Reading an old article never writes metadata or calls GitHub.
    $post = get_post($id);
    if ($post && preg_match('~href=[\x22\x27](https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+)[/\x22\x27?#]~', $post->post_content, $m)) { return dh_repo_path($m[1]) ? $m[1] : ''; }
    return '';
}
add_action('add_meta_boxes', function () { add_meta_box('dh-software', 'Discover · 软件信息与 SEO', 'dh_software_box', 'post', 'normal', 'high'); });
function dh_software_box($post) {
    wp_nonce_field('dh_save_software', 'dh_software_nonce');
    echo '<p>旧文章无需补填即可正常展示。软件信息可按需添加；未填写协议不会自动标为开源。</p><table class="form-table">';
    foreach (dh_software_fields() as $key => $label) {
        echo '<tr><th><label for="dh-meta-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input class="widefat" id="dh-meta-' . esc_attr($key) . '" name="dh_meta[' . esc_attr($key) . ']" type="' . (in_array($key, ['repo', 'website', 'download'], true) ? 'url' : 'text') . '" value="' . esc_attr(dh_meta($key, $post->ID)) . '"></td></tr>';
    }
    echo '</table>';
    $data = dh_meta('github', $post->ID);
    if (is_array($data)) { echo '<p>已缓存 GitHub 数据：★ ' . esc_html(number_format_i18n($data['stars'] ?? 0)) . ' · ' . esc_html($data['language'] ?? '') . ' · ' . esc_html(wp_date('Y-m-d H:i', $data['time'] ?? 0)) . '</p>'; }
    if (dh_opt('github_sync') && dh_repo($post->ID)) {
        $url = wp_nonce_url(admin_url('admin-post.php?action=dh_sync_github&post_id=' . $post->ID), 'dh_sync_' . $post->ID);
        echo '<p><a class="button" href="' . esc_url($url) . '">同步 GitHub 数据</a> 保存文章后点击；成功数据缓存 1 小时，访问者浏览不会请求 API。</p>';
    }
}
add_action('save_post_post', function ($id) {
    if (!isset($_POST['dh_software_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dh_software_nonce'])), 'dh_save_software') || !current_user_can('edit_post', $id) || wp_is_post_autosave($id) || wp_is_post_revision($id)) { return; }
    $input = isset($_POST['dh_meta']) && is_array($_POST['dh_meta']) ? wp_unslash($_POST['dh_meta']) : [];
    foreach (dh_software_fields() as $key => $label) {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';
        $value = in_array($key, ['repo', 'website', 'download'], true) ? esc_url_raw($value, ['http', 'https']) : sanitize_text_field($value);
        if ($key === 'repo' && $value && !dh_repo_path($value)) { $value = ''; }
        if ($key === 'repo' && $value !== dh_meta('repo', $id)) { delete_post_meta($id, '_dh_github'); }
        update_post_meta($id, '_dh_' . $key, $value);
    }
});
function dh_sync_github($id) {
    $path = dh_repo_path(dh_repo($id));
    if (!$path) { return new WP_Error('repo', '请先填写合法的 HTTPS GitHub 仓库地址。'); }
    $cached = dh_meta('github', $id);
    if (is_array($cached) && ($cached['path'] ?? '') === $path && time() - ($cached['time'] ?? 0) < HOUR_IN_SECONDS) { return $cached; }
    if (get_transient('dh_gh_fail_' . md5($path))) { return new WP_Error('cooldown', '上次请求失败，请 5 分钟后再试。'); }
    $response = wp_remote_get('https://api.github.com/repos/' . $path, ['timeout' => 8, 'redirection' => 0, 'limit_response_size' => 131072, 'headers' => ['Accept' => 'application/vnd.github+json', 'User-Agent' => 'Diao-Discover/' . DH_VERSION]]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        set_transient('dh_gh_fail_' . md5($path), 1, 5 * MINUTE_IN_SECONDS);
        return new WP_Error('github', 'GitHub 请求失败、仓库不存在或达到限额；已保留原有数据。');
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body) || !isset($body['stargazers_count'])) { return new WP_Error('github', 'GitHub 返回数据无效。'); }
    $data = ['path' => $path, 'stars' => absint($body['stargazers_count']), 'forks' => absint($body['forks_count'] ?? 0), 'language' => sanitize_text_field($body['language'] ?? ''), 'license' => sanitize_text_field($body['license']['spdx_id'] ?? ''), 'time' => time()];
    update_post_meta($id, '_dh_github', $data);
    if (!dh_meta('license', $id) && !empty($data['license']) && $data['license'] !== 'NOASSERTION') { update_post_meta($id, '_dh_license', $data['license']); }
    return $data;
}
add_action('admin_post_dh_sync_github', function () {
    $id = absint($_GET['post_id'] ?? 0);
    if (!dh_opt('github_sync') || !current_user_can('edit_post', $id) || get_post_type($id) !== 'post') { wp_die('没有权限。', '', ['response' => 403]); }
    check_admin_referer('dh_sync_' . $id);
    $result = dh_sync_github($id);
    set_transient('dh_gh_notice_' . get_current_user_id(), is_wp_error($result) ? $result->get_error_message() : 'GitHub 数据同步完成。', MINUTE_IN_SECONDS);
    wp_safe_redirect(get_edit_post_link($id, 'raw')); exit;
});
add_action('admin_notices', function () {
    $key = 'dh_gh_notice_' . get_current_user_id(); $message = get_transient($key);
    if ($message) { delete_transient($key); echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($message) . '</p></div>'; }
});
