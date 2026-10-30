<?php
if (!defined('ABSPATH')) { exit; }
function dh_comment_mode() { return dh_opt('comment_mode'); }
function dh_comments_hidden() { return dh_comment_mode() === 'closed' && dh_opt('comment_hide_existing') && !is_admin(); }
function dh_comment_link_visible($id) { return !dh_comments_hidden() && (comments_open($id) || get_comments_number($id) > 0); }
function dh_native_comment_request() {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'wp-comments-post.php';
}
function dh_comment_error($code, $message, $status = 400) { return new WP_Error($code, $message, dh_native_comment_request() ? $status : ['status' => $status]); }
function dh_comment_fail($error) {
    $data = $error->get_error_data(); $status = is_array($data) ? ($data['status'] ?? 400) : (int) $data;
    nocache_headers();
    if ($status === 429) { header('Retry-After: ' . max(1, (int) dh_opt('comment_interval'), 60)); }
    wp_die(esc_html($error->get_error_message()), '评论提交未通过', ['response' => $status ?: 400, 'back_link' => true]);
}
/** Cache-compatible blind-post marker; not authentication or a human-verification token. */
function dh_comment_form_marker($id) { return hash_hmac('sha256', 'dh-comment-form:' . absint($id), wp_salt('nonce')); }
add_action('comment_form', function ($id) {
    if (dh_comment_mode() !== 'guarded' || !dh_opt('comment_honeypot') || current_user_can('moderate_comments')) { return; }
    echo '<input type="hidden" name="dh_comment_form" value="' . esc_attr(dh_comment_form_marker($id)) . '"><div class="dh-comment-trap" aria-hidden="true"><label>请留空<input type="text" name="dh_comment_extra" value="" tabindex="-1" autocomplete="off"></label></div>';
});
add_filter('comment_form_default_fields', function ($fields) { if (dh_comment_mode() === 'guarded' && dh_opt('comment_hide_url')) { unset($fields['url']); } return $fields; });
add_filter('pre_comment_author_url', function ($url) { return dh_comment_mode() === 'guarded' && dh_opt('comment_hide_url') && !current_user_can('moderate_comments') ? '' : $url; });
add_filter('comments_open', function ($open) { return dh_comment_mode() === 'closed' ? false : $open; }, 999);
add_filter('pings_open', function ($open) { return dh_comment_mode() === 'closed' ? false : $open; }, 999);
add_filter('get_comments_number', function ($count) { return dh_comments_hidden() ? 0 : $count; });
add_filter('feed_links_show_comments_feed', function ($show) { return dh_comment_mode() === 'closed' ? false : $show; });
add_filter('xmlrpc_methods', function ($methods) {
    if (in_array(dh_comment_mode(), ['closed', 'guarded'], true)) { unset($methods['wp.newComment']); }
    if (dh_comment_mode() === 'closed') { foreach (array_keys($methods) as $method) { if (str_starts_with($method, 'pingback.')) { unset($methods[$method]); } } }
    return $methods;
}, 20);
add_action('template_redirect', function () {
    if (dh_comment_mode() === 'closed' && is_comment_feed()) { status_header(404); nocache_headers(); header('Content-Type: text/plain; charset=UTF-8'); echo '评论订阅已关闭。'; exit; }
}, 1);
function dh_comment_rate_key() {
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    return 'dh_comment_rate_' . substr(wp_hash($ip, 'nonce'), 0, 32);
}
function dh_comment_rate_check() {
    $key = dh_comment_rate_key(); $now = time(); $lock = $key . '_lock';
    $expires = (int) get_option($lock, 0); if ($expires && $expires < $now) { delete_option($lock); }
    if (!add_option($lock, $now + 10, '', false)) { return dh_comment_error('dh_comment_busy', '提交过于频繁，请稍后再试。', 429); }
    try {
        $state = get_transient($key);
        if (!is_array($state)) { $state = ['count' => 0, 'last' => 0, 'until' => 0]; }
        if (($state['until'] ?? 0) <= $now) { $state['count'] = 0; $state['until'] = $now + max(1, (int) dh_opt('comment_rate_window')) * MINUTE_IN_SECONDS; }
        if ($state['count'] >= (int) dh_opt('comment_rate_limit') || ($state['last'] && $now - $state['last'] < (int) dh_opt('comment_interval'))) { return dh_comment_error('dh_comment_rate', '评论提交过于频繁，请稍后再试。', 429); }
        $state['count']++; $state['last'] = $now; set_transient($key, $state, max(1, $state['until'] - $now, (int) dh_opt('comment_interval') + 1));
        return true;
    } finally { delete_option($lock); }
}
function dh_comment_validate($data) {
    if (dh_comment_mode() === 'inherit' || current_user_can('moderate_comments')) { return true; }
    if (dh_comment_mode() === 'closed') { return dh_comment_error('dh_comments_closed', '本站暂不接受新评论。', 403); }
    if (!in_array($data['comment_type'] ?? 'comment', ['', 'comment'], true)) { return true; }
    if (dh_opt('comment_limit_rate')) {
        // Core can validate twice; count one HTTP request once while rechecking its content.
        $key = dh_comment_rate_key();
        if (!array_key_exists($key, $GLOBALS['dh_comment_checked_sources'] ?? [])) { $GLOBALS['dh_comment_checked_sources'][$key] = dh_comment_rate_check(); }
        if (is_wp_error($GLOBALS['dh_comment_checked_sources'][$key])) { return $GLOBALS['dh_comment_checked_sources'][$key]; }
    }
    $id = absint($data['comment_post_ID'] ?? 0);
    if (!$id || !get_post($id) || !comments_open($id)) { return dh_comment_error('dh_comments_closed', '这篇文章暂不接受新评论。', 403); }
    if (dh_opt('comment_block_bots') && preg_match('/bot\b|spider|crawler|crawl\b|headlesschrome|python-requests|curl\//i', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) { return dh_comment_error('dh_comment_bot', '此自动提交未通过校验。', 403); }
    if (dh_native_comment_request() && dh_opt('comment_honeypot')) {
        $marker = isset($_POST['dh_comment_form']) && is_string($_POST['dh_comment_form']) ? wp_unslash($_POST['dh_comment_form']) : '';
        if (!empty($_POST['dh_comment_extra']) || !preg_match('/^[a-f0-9]{64}$/D', $marker) || !hash_equals(dh_comment_form_marker($id), $marker)) { return dh_comment_error('dh_comment_form', '评论表单校验未通过，请从文章页面重新提交。', 403); }
        $request = new WP_REST_Request('POST');
        $request->set_header('origin', (string) ($_SERVER['HTTP_ORIGIN'] ?? '')); $request->set_header('sec-fetch-site', (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        if (is_wp_error(dh_vote_same_origin($request))) { return dh_comment_error('dh_comment_origin', '请在本站文章页面提交评论。', 403); }
    }
    $text = isset($data['comment_content']) && is_string($data['comment_content']) ? $data['comment_content'] : '';
    $maximum = max(100, (int) dh_opt('comment_max_length'));
    if (strlen($text) > max(65536, $maximum * 10)) { return dh_comment_error('dh_comment_length', '评论内容过长，请精简后再提交。'); }
    $plain = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $length = function_exists('mb_strlen') ? mb_strlen($plain, 'UTF-8') : preg_match_all('/./us', $plain);
    if ($length > $maximum) { return dh_comment_error('dh_comment_length', '评论内容过长，请精简后再提交。'); }
    $link_pattern = '~<a\b[^>]*\bhref\s*=\s*(?:"[^"]*"|\x27[^\x27]*\x27|[^\s>]+)[^>]*>|(?:https?://|www\.)[^\s<>"\x27]+~i';
    if (preg_match_all($link_pattern, html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) > (int) dh_opt('comment_max_links')) { return dh_comment_error('dh_comment_links', '评论中的链接过多，请减少链接后提交。'); }
    $keyword_text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' ' . $plain . ' ' . implode(' ', array_filter([$data['comment_author'] ?? '', $data['comment_author_email'] ?? '', $data['comment_author_url'] ?? ''], 'is_scalar'));
    foreach (preg_split('/\R/u', (string) dh_opt('comment_blocked_words')) ?: [] as $word) {
        $word = trim($word);
        if ($word !== '' && stripos($keyword_text, $word) !== false) { return dh_comment_error('dh_comment_keyword', '评论内容未通过校验，请调整后再提交。'); }
    }
    return true;
}
add_action('init', function () {
    if (!dh_native_comment_request() || dh_comment_mode() === 'inherit') { return; }
    if (dh_comment_mode() === 'closed') { dh_comment_fail(dh_comment_error('dh_comments_closed', '本站暂不接受新评论。', 403)); }
    foreach (['comment', 'author', 'email', 'url', 'comment_parent', 'comment_post_ID'] as $field) { if (isset($_POST[$field]) && !is_scalar($_POST[$field])) { dh_comment_fail(dh_comment_error('dh_comment_fields', '评论字段格式无效，请重新填写。')); } }
    $result = dh_comment_validate(['comment_post_ID' => isset($_POST['comment_post_ID']) && is_scalar($_POST['comment_post_ID']) ? absint($_POST['comment_post_ID']) : 0, 'comment_content' => isset($_POST['comment']) && is_string($_POST['comment']) ? wp_unslash($_POST['comment']) : '', 'comment_author' => isset($_POST['author']) && is_string($_POST['author']) ? wp_unslash($_POST['author']) : '']);
    if (is_wp_error($result)) { dh_comment_fail($result); }
}, 20);
add_filter('pre_comment_approved', function ($approved, $data) {
    if (is_wp_error($approved)) { return $approved; }
    $result = dh_comment_validate($data); if (is_wp_error($result)) { return $result; }
    if (dh_comment_mode() === 'guarded' && dh_opt('comment_force_moderation') && !current_user_can('moderate_comments') && in_array($data['comment_type'] ?? 'comment', ['', 'comment'], true) && in_array($approved, [0, 1, '0', '1'], true)) { return 0; }
    return $approved;
}, 9999, 2);
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (dh_comment_mode() === 'inherit' || current_user_can('moderate_comments')) { return $result; }
    $route = preg_replace('~/+~', '/', $request->get_route());
    if (!preg_match('~^/wp/v2/comments(?:/|$)~i', $route)) { return $result; }
    $create = $request->get_method() === 'POST' && preg_match('~^/wp/v2/comments/?$~i', $route);
    if ((dh_comment_mode() === 'closed' && ($create || dh_opt('comment_hide_existing'))) || ($create && dh_opt('comment_block_rest'))) { return new WP_Error('dh_comments_restricted', '此评论入口暂未开放。', ['status' => 403]); }
    return $result;
}, 20, 3);
