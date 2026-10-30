<?php
if (!defined('ABSPATH')) { exit; }
function dh_subscribers_table() { global $wpdb; return $wpdb->prefix . 'dh_subscribers'; }
function dh_mail_queue_table() { global $wpdb; return $wpdb->prefix . 'dh_mail_queue'; }
function dh_install_newsletter() {
    global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $charset = $wpdb->get_charset_collate();
    $table = dh_subscribers_table();
    dbDelta("CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(190) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        token_hash varchar(64) NOT NULL DEFAULT '',
        token_expires bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        confirmed_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email),
        KEY status (status)
    ) $charset;");
    $queue = dh_mail_queue_table();
    dbDelta("CREATE TABLE $queue (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        subscriber_id bigint(20) unsigned NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        attempts smallint(5) unsigned NOT NULL DEFAULT 0,
        next_attempt bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY post_subscriber (post_id,subscriber_id),
        KEY due (status,next_attempt)
    ) $charset;");
    update_option('dh_mail_version', '1', false);
    dh_schedule_mail_batch();
}
add_action('after_switch_theme', 'dh_install_newsletter');
add_action('admin_init', function () { if (get_option('dh_mail_version') !== '1') { dh_install_newsletter(); } });
function dh_mail_headers() {
    $headers = ['Content-Type: text/plain; charset=UTF-8']; $email = sanitize_email(dh_opt('mail_sender_email'));
    if ($email) { $headers[] = 'From: ' . sanitize_text_field(dh_opt('mail_sender_name') ?: dh_opt('brand')) . ' <' . $email . '>'; }
    return $headers;
}
add_action('phpmailer_init', function ($mailer) {
    if (!defined('DH_SMTP_HOST') || !DH_SMTP_HOST) { return; }
    $mailer->isSMTP(); $mailer->Host = (string) DH_SMTP_HOST;
    $mailer->Port = defined('DH_SMTP_PORT') ? max(1, min(65535, (int) DH_SMTP_PORT)) : 587;
    $mailer->SMTPSecure = defined('DH_SMTP_ENCRYPTION') && in_array(DH_SMTP_ENCRYPTION, ['tls', 'ssl', ''], true) ? DH_SMTP_ENCRYPTION : 'tls';
    $mailer->SMTPAuth = defined('DH_SMTP_USER') && DH_SMTP_USER !== '';
    if ($mailer->SMTPAuth) { $mailer->Username = DH_SMTP_USER; $mailer->Password = defined('DH_SMTP_PASSWORD') ? DH_SMTP_PASSWORD : ''; }
    $mailer->Timeout = 10;
});
function dh_unsubscribe_token($subscriber) { return hash_hmac('sha256', 'unsubscribe:' . $subscriber->id . ':' . strtolower($subscriber->email), wp_salt('auth')); }
function dh_newsletter_link($id, $token, $action) { return add_query_arg(['dh_newsletter' => '1', 'mail_action' => $action, 'subscriber' => absint($id), 'token' => $token], home_url('/')); }
function dh_subscribe_request($input) {
    global $wpdb;
    if (!dh_opt('mail_enabled') || get_option('dh_mail_version') !== '1') { return new WP_Error('disabled', '邮件订阅尚未启用。'); }
    if (!empty($input['website_check'])) { return new WP_Error('spam', '订阅校验未通过，请刷新后重试。'); }
    $raw = isset($input['email']) && is_scalar($input['email']) ? trim((string) $input['email']) : '';
    $email = strtolower(sanitize_email($raw));
    if (!$email || !is_email($email) || strlen($email) > 190 || strtolower($raw) !== $email) { return new WP_Error('email', '请填写有效的邮箱地址。'); }
    if (!isset($input['consent']) || !is_scalar($input['consent']) || (string) $input['consent'] !== '1') { return new WP_Error('consent', '请先同意接收邮件并确认可随时退订。'); }
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    $source = 'dh_mail_source_' . substr(wp_hash($ip, 'nonce'), 0, 32);
    $rate = get_transient($source);
    if (!is_array($rate) || ($rate['until'] ?? 0) <= time()) { $rate = ['count' => 0, 'until' => time() + HOUR_IN_SECONDS]; }
    if ($rate['count'] >= 10) { return new WP_Error('rate', '操作过于频繁，请稍后再试。'); }
    $rate['count']++; set_transient($source, $rate, max(1, $rate['until'] - time()));
    $mail_rate = 'dh_mail_email_' . substr(wp_hash($email, 'nonce'), 0, 32);
    if (get_transient($mail_rate)) { return true; }
    $lock = 'dh_mail_email_lock_' . substr(wp_hash($email, 'nonce'), 0, 32);
    $expiry = (int) get_option($lock, 0); if ($expiry && $expiry < time()) { delete_option($lock); }
    if (!add_option($lock, time() + 60, '', false)) { return true; }
    try {
    $table = dh_subscribers_table(); $subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email=%s", $email));
    if ($subscriber && $subscriber->status === 'active') { return true; }
    $token = bin2hex(random_bytes(32)); $hash = hash('sha256', $token);
    if ($subscriber) { $id = (int) $subscriber->id; $saved = $wpdb->update($table, ['status' => 'pending', 'token_hash' => $hash, 'token_expires' => time() + DAY_IN_SECONDS], ['id' => $id]); }
    else { $saved = $wpdb->insert($table, ['email' => $email, 'status' => 'pending', 'token_hash' => $hash, 'token_expires' => time() + DAY_IN_SECONDS, 'created_at' => current_time('mysql', true)]); $id = (int) $wpdb->insert_id; }
    if ($saved === false || !$id) { return new WP_Error('storage', '暂时无法保存订阅，请稍后再试。'); }
    $link = dh_newsletter_link($id, $token, 'confirm');
    $body = "你请求订阅「" . dh_opt('brand') . "」的新文章通知。\n\n请在 24 小时内打开下面的链接，并在页面上确认订阅：\n" . $link . "\n\n确认前不会发送文章通知。如果不是你的操作，请忽略本邮件。\n网站：" . home_url('/');
    if (!wp_mail($email, '请确认订阅 · ' . dh_opt('brand'), $body, dh_mail_headers())) {
        update_option('dh_mail_last_error', '确认邮件未能交给 WordPress 发信服务，请检查 SMTP 配置。', false);
        return new WP_Error('mail', '暂时无法发送确认邮件，请稍后再试。');
    }
    set_transient($mail_rate, 1, 15 * MINUTE_IN_SECONDS);
    return true;
    } finally { delete_option($lock); }
}
function dh_newsletter_token_action($id, $token, $action, $perform = false) {
    global $wpdb; $table = dh_subscribers_table();
    $subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
    if (!$subscriber || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) { return new WP_Error('token', '链接无效或已过期，请重新订阅。'); }
    if ($action === 'confirm') {
        if (!$subscriber->token_hash || !hash_equals($subscriber->token_hash, hash('sha256', $token)) || (int) $subscriber->token_expires < time()) { return new WP_Error('token', '确认链接无效或已过期，请重新订阅。'); }
        if ($perform && $wpdb->update($table, ['status' => 'active', 'confirmed_at' => current_time('mysql', true), 'token_hash' => '', 'token_expires' => 0], ['id' => $id]) === false) { return new WP_Error('storage', '暂时无法保存，请稍后再试。'); }
    } elseif ($action === 'unsubscribe') {
        if (!hash_equals(dh_unsubscribe_token($subscriber), $token)) { return new WP_Error('token', '退订链接无效。'); }
        if ($perform && $wpdb->update($table, ['status' => 'unsubscribed', 'token_hash' => '', 'token_expires' => 0], ['id' => $id]) === false) { return new WP_Error('storage', '暂时无法保存，请稍后再试。'); }
    } else { return new WP_Error('action', '订阅操作无效。'); }
    return true;
}
function dh_handle_subscribe() {
    check_admin_referer('dh_subscribe', 'dh_subscribe_nonce');
    $input = isset($_POST['newsletter']) && is_array($_POST['newsletter']) ? wp_unslash($_POST['newsletter']) : [];
    $result = dh_subscribe_request($input);
    $code = is_wp_error($result) ? $result->get_error_code() : 'sent';
    wp_safe_redirect(add_query_arg(['dh_newsletter' => 1, 'notice' => sanitize_key($code)], home_url('/'))); exit;
}
add_action('admin_post_dh_subscribe', 'dh_handle_subscribe'); add_action('admin_post_nopriv_dh_subscribe', 'dh_handle_subscribe');
function dh_handle_newsletter_action() {
    check_admin_referer('dh_mail_action', 'dh_mail_nonce');
    $id = absint($_POST['subscriber'] ?? 0); $token = isset($_POST['token']) && is_string($_POST['token']) ? wp_unslash($_POST['token']) : '';
    $action = isset($_POST['mail_action']) && is_string($_POST['mail_action']) ? sanitize_key($_POST['mail_action']) : '';
    $result = dh_newsletter_token_action($id, $token, $action, true);
    $code = is_wp_error($result) ? $result->get_error_code() : ($action === 'confirm' ? 'confirmed' : 'unsubscribed');
    wp_safe_redirect(add_query_arg(['dh_newsletter' => 1, 'notice' => $code], home_url('/'))); exit;
}
add_action('admin_post_dh_mail_action', 'dh_handle_newsletter_action'); add_action('admin_post_nopriv_dh_mail_action', 'dh_handle_newsletter_action');
add_filter('template_include', function ($template) { return isset($_GET['dh_newsletter']) && (is_home() || is_front_page()) ? get_template_directory() . '/newsletter.php' : $template; });
add_filter('wp_headers', function ($headers) {
    if (isset($_GET['dh_newsletter'])) { $headers['Referrer-Policy'] = 'no-referrer'; }
    return $headers;
}, 100);
function dh_schedule_mail_batch($delay = 30) { if (!wp_next_scheduled('dh_mail_send_batch')) { wp_schedule_single_event(time() + max(1, $delay), 'dh_mail_send_batch'); } }
function dh_queue_post_mail($new, $old, $post) {
    if ($post->post_type !== 'post') { return; }
    // Also mark legacy articles when leaving published state, before republishing.
    if ($old === 'publish') { add_post_meta($post->ID, '_dh_mail_seen_publish', 1, true); return; }
    if ($new !== 'publish') { return; }
    $seen = get_post_meta($post->ID, '_dh_mail_seen_publish', true);
    if (!$seen) { add_post_meta($post->ID, '_dh_mail_seen_publish', 1, true); }
    if ($seen || $old === 'publish' || $post->post_password || !dh_opt('mail_enabled') || !dh_opt('mail_notify_posts') || get_option('dh_mail_version') !== '1') { return; }
    if (!dh_opt('mail_include_editorial') && array_intersect(wp_get_post_categories($post->ID), dh_editorial_categories())) { return; }
    global $wpdb; $queue = dh_mail_queue_table(); $subscribers = dh_subscribers_table();
    $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $queue (post_id,subscriber_id,status,attempts,next_attempt) SELECT %d,id,'pending',0,0 FROM $subscribers WHERE status='active'", $post->ID));
    if ($wpdb->rows_affected > 0) { dh_schedule_mail_batch(); }
}
add_action('transition_post_status', 'dh_queue_post_mail', 10, 3);
function dh_send_mail_batch() {
    if (!dh_opt('mail_enabled') || !dh_opt('mail_notify_posts') || get_option('dh_mail_version') !== '1') { return; }
    $lock = 'dh_mail_worker_lock'; $expiry = (int) get_option($lock, 0); if ($expiry && $expiry < time()) { delete_option($lock); }
    if (!add_option($lock, time() + 300, '', false)) { return; }
    global $wpdb; $queue = dh_mail_queue_table(); $subscribers = dh_subscribers_table();
    try {
        // A watchdog leaves a future run behind if PHP exits during delivery.
        dh_schedule_mail_batch(360);
        $now = time(); $limit = max(1, min(25, (int) dh_opt('mail_batch_size')));
        $wpdb->query($wpdb->prepare("UPDATE $queue SET status='pending' WHERE status='sending' AND next_attempt < %d AND attempts < 3", $now - 600));
        $wpdb->query($wpdb->prepare("UPDATE $queue SET status='failed' WHERE status='sending' AND next_attempt < %d AND attempts >= 3", $now - 600));
        $rows = $wpdb->get_results($wpdb->prepare("SELECT q.*,s.email,s.status AS subscriber_status FROM $queue q LEFT JOIN $subscribers s ON s.id=q.subscriber_id WHERE q.status='pending' AND q.next_attempt <= %d ORDER BY q.id ASC LIMIT %d", $now, $limit));
        foreach ($rows as $row) {
            $post = get_post($row->post_id);
            if (!$post || $post->post_status !== 'publish' || $post->post_password || $row->subscriber_status !== 'active') { $wpdb->update($queue, ['status' => 'cancelled'], ['id' => $row->id]); continue; }
            $attempts = (int) $row->attempts + 1;
            $wpdb->update($queue, ['status' => 'sending', 'attempts' => $attempts, 'next_attempt' => $now], ['id' => $row->id]);
            $subscriber = (object) ['id' => $row->subscriber_id, 'email' => $row->email];
            $unsubscribe = dh_newsletter_link($subscriber->id, dh_unsubscribe_token($subscriber), 'unsubscribe');
            $body = dh_title($post->ID) . "\n\n" . dh_summary($post->ID) . "\n\n阅读全文：" . get_permalink($post) . "\n\n你曾确认订阅「" . dh_opt('brand') . "」。如不再希望接收，请打开下面的链接并确认退订：\n" . $unsubscribe;
            $sent = wp_mail($subscriber->email, '[' . dh_opt('brand') . '] 新发现：' . wp_strip_all_tags(dh_title($post->ID)), $body, dh_mail_headers());
            $wpdb->update($queue, ['status' => $sent ? 'sent' : ($attempts >= 3 ? 'failed' : 'pending'), 'next_attempt' => $now + 300 * $attempts], ['id' => $row->id]);
            if (!$sent) { update_option('dh_mail_last_error', '新文章通知未能交给发信服务，请检查 SMTP。', false); }
        }
        $remaining = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue WHERE status IN ('pending','sending')");
        wp_clear_scheduled_hook('dh_mail_send_batch');
        if ($remaining) { dh_schedule_mail_batch(60); }
    } finally { delete_option($lock); }
}
add_action('dh_mail_send_batch', 'dh_send_mail_batch');
add_action('update_option_dh_options', function () { if (get_option('dh_mail_version') === '1' && dh_opt('mail_enabled') && dh_opt('mail_notify_posts')) { dh_schedule_mail_batch(); } });
add_action('switch_theme', function () { wp_clear_scheduled_hook('dh_mail_send_batch'); });
add_action('admin_menu', function () { add_theme_page('Discover 邮件订阅', 'Discover 邮件订阅', 'manage_options', 'dh-newsletter', 'dh_newsletter_admin'); });
function dh_newsletter_admin() {
    if (!current_user_can('manage_options')) { return; }
    global $wpdb; $table = dh_subscribers_table(); $queue = dh_mail_queue_table(); $page = max(1, absint($_GET['paged'] ?? 1));
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table"); $active = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='active'");
    $pending = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue WHERE status IN ('pending','sending')"); $failed = (int) $wpdb->get_var("SELECT COUNT(*) FROM $queue WHERE status='failed'");
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY id DESC LIMIT 20 OFFSET %d", ($page - 1) * 20));
    echo '<div class="wrap"><h1>邮件订阅</h1><p>已确认 ' . esc_html($active) . ' / 总记录 ' . esc_html($total) . ' · 待处理通知 ' . esc_html($pending) . ' · 发送失败 ' . esc_html($failed) . '</p>';
    echo '<p>发信：' . (defined('DH_SMTP_HOST') && DH_SMTP_HOST ? '已配置主题 SMTP 常量' : '使用 WordPress 现有发信服务') . '。通知由 WP-Cron 分批执行。测试成功不等于收件箱投递成功。</p>';
    if (get_option('dh_mail_last_error')) { echo '<div class="notice notice-warning"><p>' . esc_html(get_option('dh_mail_last_error')) . '</p></div>'; }
    echo '<p><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=dh_export_subscribers'), 'dh_export_subscribers')) . '">导出已确认订阅者 CSV</a> <a href="' . esc_url(admin_url('themes.php?page=dh-settings#dh-newsletter')) . '">订阅与发信设置</a></p><table class="widefat striped"><thead><tr><th>邮箱</th><th>状态</th><th>创建时间（UTC）</th><th>操作</th></tr></thead><tbody>';
    $labels = ['pending' => '待确认', 'active' => '已确认', 'unsubscribed' => '已退订'];
    foreach ($rows as $row) {
        echo '<tr><td>' . esc_html($row->email) . '</td><td>' . esc_html($labels[$row->status] ?? $row->status) . '</td><td>' . esc_html($row->created_at) . '</td><td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="dh_delete_subscriber"><input type="hidden" name="subscriber" value="' . esc_attr($row->id) . '">';
        wp_nonce_field('dh_delete_subscriber_' . $row->id); echo '<button class="button" type="submit" onclick="return confirm(\'删除该邮箱及其通知记录？\')">删除</button></form></td></tr>';
    }
    if (!$rows) { echo '<tr><td colspan="4">暂无订阅记录。</td></tr>'; }
    echo '</tbody></table><div class="tablenav">' . paginate_links(['base' => add_query_arg('paged', '%#%', admin_url('themes.php?page=dh-newsletter')), 'format' => '', 'current' => $page, 'total' => max(1, (int) ceil($total / 20))]) . '</div></div>';
}
add_action('admin_post_dh_export_subscribers', function () {
    if (!current_user_can('manage_options')) { wp_die('没有权限。', '', ['response' => 403]); } check_admin_referer('dh_export_subscribers');
    global $wpdb; $table = dh_subscribers_table();
    nocache_headers(); header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="subscribers.csv"');
    $file = fopen('php://output', 'w'); fputcsv($file, ['email', 'confirmed_at'], ',', '"', '');
    $last = 0; do { $rows = $wpdb->get_results($wpdb->prepare("SELECT id,email,confirmed_at FROM $table WHERE status='active' AND id>%d ORDER BY id LIMIT 500", $last)); foreach ($rows as $row) { $email = preg_match('/^[=+@-]/', $row->email) ? "'" . $row->email : $row->email; fputcsv($file, [$email, $row->confirmed_at], ',', '"', ''); $last = (int) $row->id; } } while (count($rows) === 500);
    fclose($file); exit;
});
add_action('admin_post_dh_delete_subscriber', function () {
    if (!current_user_can('manage_options')) { wp_die('没有权限。', '', ['response' => 403]); } $id = absint($_POST['subscriber'] ?? 0); check_admin_referer('dh_delete_subscriber_' . $id);
    global $wpdb; $wpdb->delete(dh_mail_queue_table(), ['subscriber_id' => $id], ['%d']); $wpdb->delete(dh_subscribers_table(), ['id' => $id], ['%d']);
    wp_safe_redirect(admin_url('themes.php?page=dh-newsletter')); exit;
});
