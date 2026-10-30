<?php
/** Reversible theme-level performance and security controls. */
if (!defined('ABSPATH')) { exit; }
function dh_tool_groups() {
    return [
        'security' => ['安全防护', [
            'disable_xmlrpc' => ['关闭 XML-RPC', 'checkbox', 1, '阻止 xmlrpc.php 的调用；使用 Jetpack、WordPress 手机客户端或 XML-RPC 远程发布时请关闭本项。REST 发布不受影响。'],
            'disable_pingbacks' => ['关闭 Pingback / Trackback', 'checkbox', 1, '阻止新的引用通知与对外 Pingback，保留普通评论及已有评论。'],
            'protect_users' => ['禁止匿名读取 REST 用户列表', 'checkbox', 1, '限制 wp/v2/users，保留登录用户及编辑器权限校验；不能代替完整的账户安全防护。'],
            'protect_batch' => ['禁止匿名 REST 批量请求', 'checkbox', 1, '限制 batch/v1；登录后的批量编辑和主题推荐接口保留。'],
            'limit_login' => ['启用登录失败来源限流', 'checkbox', 1, '按服务器 REMOTE_ADDR 的摘要计数，不信任任意转发头。反代服务器需正确配置真实来源；共享网络可能共用额度。'],
            'login_attempts' => ['允许连续登录失败次数', 'number', 5, '达到次数后，在下方时间窗口结束前暂停该来源的登录尝试。', [3, 20]],
            'login_window' => ['登录失败时间窗口（分钟）', 'number', 15, '登录成功清除当前来源的失败记录；只影响登录校验，不会注销已有会话。', [5, 60]],
            'generic_login_errors' => ['隐藏登录时的账号存在性提示', 'checkbox', 1, '统一错误账号和密码的提示；保留限流、双重验证等其他错误信息。'],
            'disable_file_editor' => ['禁用后台主题 / 插件代码编辑', 'checkbox', 1, '使用 DISALLOW_FILE_EDIT，下一请求生效；已有 wp-config.php 配置优先。主题上传、插件更新不受影响。'],
            'security_headers' => ['添加基础安全响应头', 'checkbox', 1, '补充 nosniff 和 strict-origin-when-cross-origin；尊重已有响应头，不强制 CSP，避免阻断广告和第三方资源。'],
            'disable_app_passwords' => ['禁用应用程序密码', 'checkbox', 0, '可选：会影响依赖应用程序密码的 REST 发布、自动化和外部客户端。已有密码不删除，关闭本项可恢复。'],
        ]],
        'performance' => ['性能与功能清理', [
            'html_minify' => ['压缩前台 HTML 源码', 'checkbox', 0, '保守清理标签间的空白，保留脚本、样式、代码块、文本域及注释。不是 HTTP gzip；如与插件布局冲突可关闭并清缓存。'],
            'disable_emojis' => ['移除 Emoji 兼容脚本与样式', 'checkbox', 1, '保留浏览器原生 Emoji 显示。'],
            'clean_head' => ['清理页面头部冗余标签', 'checkbox', 1, '移除生成器版本号、RSD、WLW、短链接和 REST 发现标签；不会关闭 REST API，也不代表漏洞已被修复。'],
            'disable_autoembeds' => ['关闭正文自动嵌入解析', 'checkbox', 0, '可选：纯 URL 自动转换为视频/网站嵌入的行为会改变，已有 iframe HTML 保留。'],
            'disable_oembed' => ['关闭本站 oEmbed 发现与嵌入脚本', 'checkbox', 0, '可选：其他站点将无法通过原生 oEmbed 预览本站文章；不关闭其他 REST 路由。'],
            'disable_feeds' => ['关闭 RSS / Atom Feed', 'checkbox', 0, '可选：订阅将不可用；主题的 RSS 入口会一并隐藏。不能据此阻止网页采集。'],
            'guest_dashicons' => ['访客页面不加载 Dashicons', 'checkbox', 0, '可选：使用该字体图标的插件可能受影响；管理员工具栏保留。'],
            'remove_jquery_migrate' => ['访客前台移除 jQuery Migrate', 'checkbox', 0, '可选：旧插件可能依赖它；不删除 jQuery，不影响后台。'],
            'limit_revisions' => ['限制后续文章修订数量', 'checkbox', 0, '启用后在后续保存文章时应用数量限制，不执行批量清理。'],
            'revision_count' => ['每篇保留修订数量（0 为停用修订）', 'number', 20, '保留一些修订便于编辑回退；0 不会停止自动保存。', [0, 100]],
            'slow_heartbeat' => ['降低 Heartbeat 轮询频率', 'checkbox', 0, '可选：调整轮询间隔，不关闭文章锁与编辑通信。'],
            'heartbeat_interval' => ['Heartbeat 间隔（秒）', 'number', 60, '后台编辑器可按核心规则调整实际间隔。', [15, 120]],
            'disable_texturize' => ['不自动替换正文的智能引号', 'checkbox', 0, '可选：停止 wptexturize 的字符格式转换。'],
            'disable_capital_p' => ['不自动修正 WordPress 的大小写', 'checkbox', 0, '可选：按原文显示，不执行 capital_P_dangit。'],
        ]],
        'editor' => ['编辑器与后台', [
            'classic_editor' => ['文章 / 页面使用经典编辑器', 'checkbox', 0, '可选：已有区块正文保留，但编辑方式改变；不全局修改插件的其他内容类型。'],
            'classic_widgets' => ['小工具使用经典管理界面', 'checkbox', 0, '可选：侧栏小工具内容保留，仅切换管理方式。'],
            'hide_dashboard_news' => ['移除仪表盘的 WordPress 活动与新闻', 'checkbox', 0, '仅减少后台展示，不影响更新检查和系统通知。'],
        ]],
    ];
}
function dh_tool_opt($key) {
    $saved = get_option('dh_tools', []);
    if (isset($saved[$key])) { return $saved[$key]; }
    foreach (dh_tool_groups() as $group) { if (isset($group[1][$key])) { return $group[1][$key][2]; } }
    return 0;
}
function dh_sanitize_tools($input) {
    $input = is_array($input) ? $input : []; $clean = [];
    foreach (dh_tool_groups() as $group) {
        foreach ($group[1] as $key => $field) {
            $value = isset($input[$key]) && is_scalar($input[$key]) ? $input[$key] : '';
            $clean[$key] = $field[1] === 'checkbox' ? (empty($value) ? 0 : 1) : max($field[4][0], min($field[4][1], is_numeric($value) ? (int) $value : $field[2]));
        }
    }
    return $clean;
}
add_action('admin_init', function () { register_setting('dh_tools_settings', 'dh_tools', ['sanitize_callback' => 'dh_sanitize_tools']); });
add_action('admin_menu', function () { add_theme_page('Discover 优化与安全', 'Discover 优化与安全', 'manage_options', 'dh-tools', 'dh_tools_page'); });
function dh_tools_page() {
    if (!current_user_can('manage_options')) { return; }
    ?>
    <div class="wrap dh-tools"><style>.dh-tools{max-width:1100px}.dh-tools h1{font-weight:700}.dh-tools .tools-intro{padding:20px 24px;margin:20px 0;background:#eef3ed;border:1px solid #d8e2d6;border-radius:10px;color:#3b5141}.dh-tools fieldset{padding:12px 25px 20px;background:white;border:1px solid #d9ded7;border-radius:10px;margin:24px 0}.dh-tools legend{font-size:20px;font-weight:600;padding:0 8px}.dh-tools .form-table th{width:300px}.dh-tools label{font-weight:500}.dh-tools .description{font-size:12px;line-height:1.9;max-width:650px;margin-top:8px}.dh-tools nav{display:flex;gap:10px;margin:22px 0}.dh-tools nav a{background:white;border:1px solid #ddd;padding:9px 15px;border-radius:7px;text-decoration:none}.dh-tools .submit{position:sticky;bottom:0;padding:16px;background:#f0f0f1ed;border-top:1px solid #ddd}</style>
    <h1>优化与安全</h1><?php settings_errors(); ?>
    <div class="tools-intro"><strong>减少不必要的负担，收紧常见入口。</strong><p>基础防护默认开启；可能影响插件、客户端或内容展示的选项按需启用。自动更新沿用 WordPress 原策略，不会被本主题关闭。</p><p>主题层防护随主题停用而停止，不能替代核心与插件更新、备份及服务器/WAF 防护。</p></div>
    <nav><?php foreach (dh_tool_groups() as $id => $group): ?><a href="#tools-<?php echo esc_attr($id); ?>"><?php echo esc_html($group[0]); ?></a><?php endforeach; ?></nav>
    <form method="post" action="options.php"><?php settings_fields('dh_tools_settings'); ?>
    <?php foreach (dh_tool_groups() as $id => $group): ?><fieldset id="tools-<?php echo esc_attr($id); ?>"><legend><?php echo esc_html($group[0]); ?></legend><table class="form-table" role="presentation">
    <?php foreach ($group[1] as $key => $field): ?><tr><th scope="row"><label for="tools-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label></th><td><?php if ($field[1] === 'checkbox'): ?><input type="checkbox" id="tools-<?php echo esc_attr($key); ?>" aria-label="<?php echo esc_attr($field[0]); ?>" name="dh_tools[<?php echo esc_attr($key); ?>]" value="1" <?php checked(dh_tool_opt($key), 1); ?>><?php else: ?><input type="number" id="tools-<?php echo esc_attr($key); ?>" name="dh_tools[<?php echo esc_attr($key); ?>]" min="<?php echo esc_attr($field[4][0]); ?>" max="<?php echo esc_attr($field[4][1]); ?>" value="<?php echo esc_attr(dh_tool_opt($key)); ?>"><?php endif; ?><p class="description"><?php echo esc_html($field[3]); ?></p></td></tr><?php endforeach; ?></table></fieldset><?php endforeach; ?>
    <?php submit_button('保存优化与安全设置'); ?></form></div>
    <?php
}
if (dh_tool_opt('disable_file_editor') && !defined('DISALLOW_FILE_EDIT')) { define('DISALLOW_FILE_EDIT', true); }
add_action('after_setup_theme', function () {
    if (dh_tool_opt('disable_xmlrpc') && defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        // Core's XML-RPC wp_die handler has no server object at this early stage.
        status_header(403); nocache_headers(); header('Content-Type: text/plain; charset=UTF-8');
        echo 'XML-RPC 已由站点管理员关闭。'; exit;
    }
}, 1);
add_filter('xmlrpc_enabled', function ($enabled) { return dh_tool_opt('disable_xmlrpc') ? false : $enabled; });
add_filter('xmlrpc_methods', function ($methods) {
    if (dh_tool_opt('disable_xmlrpc')) { return []; }
    if (dh_tool_opt('disable_pingbacks')) { foreach (array_keys($methods) as $method) { if (str_starts_with($method, 'pingback.')) { unset($methods[$method]); } } }
    return $methods;
});
add_filter('pings_open', function ($open) { return dh_tool_opt('disable_pingbacks') ? false : $open; });
add_filter('pre_option_default_ping_status', function ($value) { return dh_tool_opt('disable_pingbacks') ? 'closed' : $value; });
add_filter('pre_option_default_pingback_flag', function ($value) { return dh_tool_opt('disable_pingbacks') ? 0 : $value; });
add_action('pre_ping', function (&$links) { if (dh_tool_opt('disable_pingbacks')) { $links = []; } });
add_filter('pre_comment_approved', function ($approved, $data) { return dh_tool_opt('disable_pingbacks') && in_array($data['comment_type'] ?? '', ['pingback', 'trackback'], true) ? new WP_Error('pings_disabled', '引用通知已关闭。', 403) : $approved; }, 10, 2);
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (is_user_logged_in()) { return $result; }
    $route = preg_replace('~/+~', '/', $request->get_route());
    if ((dh_tool_opt('protect_users') && preg_match('~^/wp/v2/users(?:/|$)~i', $route)) || (dh_tool_opt('protect_batch') && preg_match('~^/batch(?:/v[0-9]+)?(?:/|$)~i', $route))) {
        return new WP_Error('dh_rest_restricted', '此接口不向匿名访客开放。', ['status' => 403]);
    }
    return $result;
}, 10, 3);
add_filter('wp_is_application_passwords_available', function ($available) { return dh_tool_opt('disable_app_passwords') ? false : $available; });
function dh_login_rate_key() {
    $address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    return 'dh_login_' . substr(wp_hash($address, 'nonce'), 0, 32);
}
function dh_login_rate_state() {
    $state = get_transient(dh_login_rate_key());
    return is_array($state) && ($state['until'] ?? 0) > time() ? $state : ['count' => 0, 'until' => time() + (int) dh_tool_opt('login_window') * MINUTE_IN_SECONDS];
}
add_filter('authenticate', function ($user, $username, $password) {
    if (!dh_tool_opt('limit_login') || !$username || !$password) { return $user; }
    $state = dh_login_rate_state();
    return $state['count'] >= (int) dh_tool_opt('login_attempts') ? new WP_Error('dh_login_rate', '登录尝试过于频繁，请约 ' . max(1, (int) ceil(($state['until'] - time()) / 60)) . ' 分钟后再试。') : $user;
}, 99999, 3);
add_action('wp_login_failed', function ($username, $error) {
    if (!dh_tool_opt('limit_login') || !$username || !is_wp_error($error) || !array_intersect($error->get_error_codes(), ['invalid_username', 'invalid_email', 'incorrect_password', 'authentication_failed'])) { return; }
    $state = dh_login_rate_state(); $state['count']++;
    set_transient(dh_login_rate_key(), $state, max(1, $state['until'] - time()));
}, 10, 2);
add_action('wp_login', function () { delete_transient(dh_login_rate_key()); });
add_filter('wp_login_errors', function ($errors) {
    if (!dh_tool_opt('generic_login_errors')) { return $errors; }
    $codes = array_intersect($errors->get_error_codes(), ['invalid_username', 'invalid_email', 'incorrect_password']);
    if ($codes) { foreach ($codes as $code) { $errors->remove($code); } $errors->add('dh_login_invalid', '登录信息有误，请检查账号和密码。'); }
    return $errors;
});
add_filter('wp_headers', function ($headers) {
    $keys = array_change_key_case($headers, CASE_LOWER);
    if (dh_tool_opt('disable_pingbacks') || dh_tool_opt('disable_xmlrpc')) { foreach (array_keys($headers) as $key) { if (strtolower($key) === 'x-pingback') { unset($headers[$key]); } } }
    if (dh_tool_opt('security_headers')) {
        if (!isset($keys['x-content-type-options'])) { $headers['X-Content-Type-Options'] = 'nosniff'; }
        if (!isset($keys['referrer-policy'])) { $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin'; }
    }
    return $headers;
}, 20);
function dh_feed_available() { return !dh_tool_opt('disable_feeds'); }
function dh_block_feed() { if (!dh_feed_available()) { wp_die('本站未提供 RSS / Atom 订阅。', 'Feed disabled', ['response' => 404]); } }
foreach (['rdf', 'rss', 'rss2', 'atom'] as $feed) { add_action('do_feed_' . $feed, 'dh_block_feed', 1); }
add_action('init', function () {
    if (dh_tool_opt('disable_emojis')) {
        remove_action('wp_head', 'print_emoji_detection_script', 7); remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles'); remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles'); remove_action('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji'); remove_filter('comment_text_rss', 'wp_staticize_emoji'); remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }
    if (dh_tool_opt('clean_head')) { foreach (['wp_generator', 'rsd_link', 'wlwmanifest_link', 'wp_shortlink_wp_head', 'rest_output_link_wp_head'] as $callback) { remove_action('wp_head', $callback, 10); } remove_action('template_redirect', 'wp_shortlink_header', 11); remove_action('template_redirect', 'rest_output_link_header', 11); }
    if (dh_tool_opt('disable_autoembeds')) { global $wp_embed; remove_filter('the_content', [$wp_embed, 'run_shortcode'], 8); remove_filter('the_content', [$wp_embed, 'autoembed'], 8); }
    if (dh_tool_opt('disable_oembed')) { remove_action('wp_head', 'wp_oembed_add_discovery_links'); remove_action('wp_head', 'wp_oembed_add_host_js'); remove_action('rest_api_init', 'wp_oembed_register_route'); }
    if (!dh_feed_available()) { remove_action('wp_head', 'feed_links', 2); remove_action('wp_head', 'feed_links_extra', 3); }
    if (dh_tool_opt('disable_texturize')) { add_filter('run_wptexturize', '__return_false'); }
    if (dh_tool_opt('disable_capital_p')) { remove_filter('the_title', 'capital_P_dangit', 11); remove_filter('the_content', 'capital_P_dangit', 11); remove_filter('comment_text', 'capital_P_dangit', 31); }
});
add_filter('tiny_mce_plugins', function ($plugins) { return dh_tool_opt('disable_emojis') && is_array($plugins) ? array_values(array_diff($plugins, ['wpemoji'])) : $plugins; });
add_action('wp_enqueue_scripts', function () {
    if (dh_tool_opt('guest_dashicons') && !is_user_logged_in()) { wp_dequeue_style('dashicons'); }
    if (dh_tool_opt('remove_jquery_migrate') && !is_admin()) { $scripts = wp_scripts(); if (isset($scripts->registered['jquery'])) { $scripts->registered['jquery']->deps = array_values(array_diff($scripts->registered['jquery']->deps, ['jquery-migrate'])); } }
}, 100);
add_filter('wp_revisions_to_keep', function ($count, $post) { return dh_tool_opt('limit_revisions') && in_array($post->post_type, ['post', 'page'], true) ? (int) dh_tool_opt('revision_count') : $count; }, 10, 2);
add_filter('heartbeat_settings', function ($settings) { if (dh_tool_opt('slow_heartbeat')) { $settings['interval'] = (int) dh_tool_opt('heartbeat_interval'); } return $settings; });
add_filter('use_block_editor_for_post_type', function ($use, $type) { return dh_tool_opt('classic_editor') && in_array($type, ['post', 'page'], true) ? false : $use; }, 10, 2);
add_filter('use_widgets_block_editor', function ($use) { return dh_tool_opt('classic_widgets') ? false : $use; });
add_action('wp_dashboard_setup', function () { if (dh_tool_opt('hide_dashboard_news')) { remove_meta_box('dashboard_primary', 'dashboard', 'side'); } });
