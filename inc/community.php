<?php
if (!defined('ABSPATH')) { exit; }
function dh_votes_table() { global $wpdb; return $wpdb->prefix . 'dh_votes'; }
function dh_guest_votes_table() { global $wpdb; return $wpdb->prefix . 'dh_guest_votes'; }
function dh_install_votes() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = dh_votes_table();
    dbDelta("CREATE TABLE $table (
        post_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (post_id,user_id),
        KEY user_id (user_id)
    ) " . $wpdb->get_charset_collate() . ';');
    $guest_table = dh_guest_votes_table();
    dbDelta("CREATE TABLE $guest_table (
        post_id bigint(20) unsigned NOT NULL,
        visitor_hash varchar(64) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (post_id,visitor_hash)
    ) " . $wpdb->get_charset_collate() . ';');
    update_option('dh_db_version', '2', false);
}
add_action('after_switch_theme', 'dh_install_votes');
add_action('admin_init', function () { if (get_option('dh_db_version') !== '2') { dh_install_votes(); } });
add_action('deleted_post', function ($id) { global $wpdb; if ((int) get_option('dh_db_version') >= 1) { $wpdb->delete(dh_votes_table(), ['post_id' => $id], ['%d']); } if ((int) get_option('dh_db_version') >= 2) { $wpdb->delete(dh_guest_votes_table(), ['post_id' => $id], ['%d']); } });
add_action('deleted_user', function ($id) { global $wpdb; if ((int) get_option('dh_db_version') >= 1) { $wpdb->delete(dh_votes_table(), ['user_id' => $id], ['%d']); } });
function dh_guest_identity($create = false) {
    $cookie = isset($_COOKIE['dh_visitor']) && is_string($_COOKIE['dh_visitor']) ? wp_unslash($_COOKIE['dh_visitor']) : '';
    if (preg_match('/^([a-f0-9]{64})\.([a-f0-9]{64})$/D', $cookie, $parts) && hash_equals(hash_hmac('sha256', $parts[1], wp_salt('auth')), $parts[2])) {
        return hash_hmac('sha256', 'visitor:' . $parts[1], wp_salt('auth'));
    }
    if (!$create || headers_sent()) { return ''; }
    $seed = bin2hex(random_bytes(32));
    $cookie = $seed . '.' . hash_hmac('sha256', $seed, wp_salt('auth'));
    $options = ['expires' => time() + YEAR_IN_SECONDS, 'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax'];
    if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) { $options['domain'] = COOKIE_DOMAIN; }
    if (!setcookie('dh_visitor', $cookie, $options)) { return ''; }
    $_COOKIE['dh_visitor'] = $cookie;
    return hash_hmac('sha256', 'visitor:' . $seed, wp_salt('auth'));
}
function dh_vote_same_origin($request) {
    $origin = $request->get_header('origin');
    if ($origin) {
        $expected = wp_parse_url(home_url('/')); $actual = wp_parse_url($origin);
        if (!$actual || strtolower($actual['host'] ?? '') !== strtolower($expected['host'] ?? '') || ($actual['scheme'] ?? '') !== ($expected['scheme'] ?? '') || (int) ($actual['port'] ?? (($actual['scheme'] ?? '') === 'https' ? 443 : 80)) !== (int) ($expected['port'] ?? (($expected['scheme'] ?? '') === 'https' ? 443 : 80))) {
            return new WP_Error('origin', '请在本站页面中操作。', ['status' => 403]);
        }
    }
    return strtolower((string) $request->get_header('sec-fetch-site')) === 'cross-site' ? new WP_Error('origin', '请在本站页面中操作。', ['status' => 403]) : true;
}
function dh_vote_response($data) {
    if (is_wp_error($data)) { return $data; }
    $response = rest_ensure_response($data);
    $response->header('Cache-Control', 'no-store, private, max-age=0');
    return $response;
}
function dh_vote_count($id) {
    global $wpdb;
    if (!dh_opt('votes') || (int) get_option('dh_db_version') < 1) { return 0; }
    $count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . dh_votes_table() . ' WHERE post_id = %d', $id));
    if ((int) get_option('dh_db_version') >= 2) { $count += (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . dh_guest_votes_table() . ' WHERE post_id = %d', $id)); }
    return $count;
}
function dh_has_voted($id) {
    global $wpdb;
    if (is_user_logged_in() && (int) get_option('dh_db_version') >= 1) { return (bool) $wpdb->get_var($wpdb->prepare('SELECT 1 FROM ' . dh_votes_table() . ' WHERE post_id=%d AND user_id=%d', $id, get_current_user_id())); }
    $visitor = dh_guest_identity();
    return $visitor && (int) get_option('dh_db_version') >= 2 && (bool) $wpdb->get_var($wpdb->prepare('SELECT 1 FROM ' . dh_guest_votes_table() . ' WHERE post_id=%d AND visitor_hash=%s', $id, $visitor));
}
add_action('rest_api_init', function () {
    register_rest_route('diao/v1', '/vote-session', ['methods' => 'POST', 'permission_callback' => 'dh_vote_same_origin', 'callback' => function () {
        if (!dh_opt('votes') || (int) get_option('dh_db_version') < 2) { return new WP_Error('disabled', '推荐功能尚未启用。', ['status' => 403]); }
        if (is_user_logged_in()) { return dh_vote_response(['token' => wp_create_nonce('wp_rest')]); }
        $visitor = dh_guest_identity(true);
        if (!$visitor) { return new WP_Error('cookie', '暂时无法保存推荐状态，请刷新后重试。', ['status' => 400]); }
        return dh_vote_response(['token' => wp_create_nonce('dh_guest_vote_' . $visitor)]);
    }]);
    register_rest_route('diao/v1', '/votes', ['methods' => 'GET', 'permission_callback' => 'dh_vote_same_origin', 'callback' => function ($request) {
        $raw = $request->get_param('ids');
        $ids = array_slice(array_unique(array_filter(array_map('absint', explode(',', is_string($raw) ? $raw : '')))), 0, 100);
        $states = [];
        foreach ($ids as $id) { if (get_post_status($id) === 'publish' && get_post_type($id) === 'post') { $states[$id] = ['active' => dh_has_voted($id), 'count' => dh_vote_count($id)]; } }
        $visitor = dh_guest_identity();
        return dh_vote_response(['states' => $states, 'token' => is_user_logged_in() ? wp_create_nonce('wp_rest') : ($visitor ? wp_create_nonce('dh_guest_vote_' . $visitor) : '')]);
    }]);
    register_rest_route('diao/v1', '/vote/(?P<id>\d+)', [
        'methods' => 'POST', 'permission_callback' => function ($request) {
            $origin = dh_vote_same_origin($request); if (is_wp_error($origin)) { return $origin; }
            if (is_user_logged_in()) { return true; }
            $visitor = dh_guest_identity();
            return $visitor && wp_verify_nonce($request->get_header('x-dh-token'), 'dh_guest_vote_' . $visitor) ? true : new WP_Error('token', '推荐校验已过期，请刷新后重试。', ['status' => 403]);
        },
        'args' => ['active' => ['required' => true, 'type' => 'boolean']],
        'callback' => function ($request) {
            global $wpdb;
            $id = absint($request['id']);
            if (!dh_opt('votes') || (int) get_option('dh_db_version') < 2) { return new WP_Error('disabled', '推荐功能尚未启用。', ['status' => 403]); }
            if (get_post_status($id) !== 'publish' || get_post_type($id) !== 'post') { return new WP_Error('missing', '文章不存在。', ['status' => 404]); }
            $uid = get_current_user_id(); $visitor = dh_guest_identity();
            $address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
            $rate_key = 'dh_vote_rate_' . substr(wp_hash($address, 'nonce'), 0, 32);
            $rate = get_transient($rate_key);
            if (!is_array($rate) || ($rate['until'] ?? 0) <= time()) { $rate = ['count' => 0, 'until' => time() + MINUTE_IN_SECONDS]; }
            if ($rate['count'] >= 120) { return new WP_Error('rate', '操作过于频繁，请稍后再试。', ['status' => 429]); }
            $rate['count']++; set_transient($rate_key, $rate, max(1, $rate['until'] - time()));
            $table = $uid ? dh_votes_table() : dh_guest_votes_table();
            if ($request['active']) {
                if ($uid) { $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (post_id,user_id,created_at) VALUES (%d,%d,%s)", $id, $uid, current_time('mysql', true))); }
                else { $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (post_id,visitor_hash,created_at) VALUES (%d,%s,%s)", $id, $visitor, current_time('mysql', true))); }
            } else { $wpdb->delete($table, $uid ? ['post_id' => $id, 'user_id' => $uid] : ['post_id' => $id, 'visitor_hash' => $visitor], ['%d', $uid ? '%d' : '%s']); }
            if ($wpdb->last_error) { return new WP_Error('storage', '保存失败，请稍后重试。', ['status' => 500]); }
            return dh_vote_response(['active' => dh_has_voted($id), 'count' => dh_vote_count($id)]);
        },
    ]);
});
add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query()) { return; }
    if ($query->is_home() || $query->is_archive() || $query->is_search()) { $query->set('posts_per_page', (int) dh_opt('per_page')); }
    if ($query->is_search()) { $query->set('post_type', 'post'); }
    $editorial_archive = $query->is_category() && ($term = $query->get_queried_object()) && in_array((int) $term->term_id, dh_editorial_categories(), true);
    if ($query->is_home() || $query->is_search() || ($query->is_archive() && !$editorial_archive)) {
        $query->set('category__not_in', dh_app_excluded_categories());
    }
    if (isset($_GET['saved']) && $query->is_home()) {
        $raw = sanitize_text_field(wp_unslash($_GET['ids'] ?? ''));
        $ids = array_slice(array_filter(array_map('absint', explode(',', $raw))), 0, 100);
        $query->set('post__in', $ids ?: [0]);
        $query->set('orderby', 'post__in'); $query->set('category__not_in', []);
    } elseif (isset($_GET['sort']) && sanitize_key(wp_unslash($_GET['sort'])) === 'popular' && ($query->is_home() || $query->is_category() || $query->is_tag() || $query->is_search()) && dh_opt('votes') && (int) get_option('dh_db_version') >= 1) { $query->set('dh_popular', true); }
});
add_filter('posts_clauses', function ($clauses, $query) {
    if ($query->get('dh_popular')) {
        global $wpdb; $table = dh_votes_table();
        $ranking = "SELECT post_id, COUNT(*) AS total FROM $table GROUP BY post_id";
        if ((int) get_option('dh_db_version') >= 2) { $guest_table = dh_guest_votes_table(); $ranking = "SELECT post_id, SUM(total) AS total FROM ($ranking UNION ALL SELECT post_id, COUNT(*) AS total FROM $guest_table GROUP BY post_id) dh_all_votes GROUP BY post_id"; }
        $clauses['join'] .= " LEFT JOIN ($ranking) dh_rank ON {$wpdb->posts}.ID = dh_rank.post_id ";
        $clauses['orderby'] = "COALESCE(dh_rank.total,0) DESC, {$wpdb->posts}.post_date DESC";
    }
    return $clauses;
}, 10, 2);
add_filter('template_include', function ($template) {
    if (isset($_GET['dh_submit']) && (is_home() || is_front_page())) { return get_template_directory() . '/submit.php'; }
    return $template;
});
function dh_submission_rate_key($uid = 0) {
    if ($uid > 0) { return 'dh_submit_limit_' . absint($uid); }
    $address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    return 'dh_submit_limit_guest_' . substr(wp_hash($address, 'nonce'), 0, 32);
}
function dh_submission_values($input) {
    $values = [];
    foreach (['name', 'summary', 'repo', 'website', 'details'] as $key) {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';
        $values[$key] = wp_html_excerpt($key === 'details' ? sanitize_textarea_field($value) : sanitize_text_field($value), $key === 'details' ? 5001 : 1001, '');
    }
    return $values;
}
function dh_create_submission($input, $uid = 0, $files = []) {
    if ($files) { return new WP_Error('upload_forbidden', '投稿仅接受链接和文字，不支持任何文件或图片上传。'); }
    if (!empty($input['company_website'])) { return new WP_Error('spam', '提交未通过校验，请刷新后重试。'); }
    $uid = $uid > 0 && get_userdata($uid) ? absint($uid) : 0;
    $rate_key = dh_submission_rate_key($uid);
    if (get_transient($rate_key)) { return new WP_Error('rate_limit', '请稍等 5 分钟，再推荐下一款软件。'); }
    $values = dh_submission_values($input);
    $name = sanitize_text_field($values['name']);
    $summary = sanitize_text_field($values['summary']);
    $repo = esc_url_raw($values['repo'], ['https']);
    $website = esc_url_raw($values['website'], ['http', 'https']);
    if (!$name || !$summary || strlen($name) > 240 || strlen($summary) > 900) { return new WP_Error('validation', '请填写软件名称和一句话介绍，并保持简洁。'); }
    if ($values['repo'] && (!$repo || !dh_repo_path($repo))) { return new WP_Error('validation', 'GitHub 地址格式应为 https://github.com/作者/仓库。'); }
    $parts = wp_parse_url($website);
    if ($values['website'] && (!$website || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']))) { return new WP_Error('validation', '请填写合法的 HTTP(S) 官网链接，不要包含账号或密码。'); }
    if (!$repo && !$website) { return new WP_Error('validation', '请至少填写 GitHub 仓库或官方网站。'); }
    $details = sanitize_textarea_field($values['details']);
    if (wp_html_excerpt($details, 5000, '') !== $details) { return new WP_Error('validation', '补充说明过长，请控制在 5000 字以内。'); }
    $lock = 'dh_submission_lock_' . md5($rate_key);
    $expires = (int) get_option($lock, 0);
    if ($expires && $expires < time()) { delete_option($lock); }
    if (!add_option($lock, time() + 60, '', false)) { return new WP_Error('busy', '正在保存投稿，请勿重复提交。'); }
    try {
        if (get_transient($rate_key)) { return new WP_Error('rate_limit', '请稍等 5 分钟，再推荐下一款软件。'); }
        $id = wp_insert_post(['post_type' => 'post', 'post_title' => $name, 'post_excerpt' => $summary,
            'post_content' => wpautop(esc_html($details ?: $summary)), 'post_status' => 'pending', 'post_author' => $uid], true);
        if (is_wp_error($id)) { return new WP_Error('storage', '保存失败，请稍后再试。'); }
        foreach (['name' => $name, 'summary' => $summary, 'repo' => $repo, 'website' => $website] as $key => $value) { update_post_meta($id, '_dh_' . $key, $value); }
        update_post_meta($id, '_dh_submission', 1);
        update_post_meta($id, '_dh_submission_guest', $uid ? 0 : 1);
        set_transient($rate_key, 1, 5 * MINUTE_IN_SECONDS);
        return $id;
    } finally { delete_option($lock); }
}
function dh_handle_submission() {
    check_admin_referer('dh_submit', 'dh_submit_nonce');
    $input = isset($_POST['software']) && is_array($_POST['software']) ? wp_unslash($_POST['software']) : [];
    $result = dh_create_submission($input, get_current_user_id(), $_FILES);
    if (is_wp_error($result)) {
        $token = wp_generate_password(32, false, false);
        set_transient('dh_submit_error_' . $token, ['message' => $result->get_error_message(), 'input' => dh_submission_values($input)], 5 * MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg(['dh_submit' => '1', 'submit_error' => $token], home_url('/')));
    } else { wp_safe_redirect(add_query_arg(['dh_submit' => '1', 'submitted' => '1'], home_url('/'))); }
    exit;
}
add_action('admin_post_nopriv_dh_submit', 'dh_handle_submission');
add_action('admin_post_dh_submit', 'dh_handle_submission');
