<?php
if (!defined('ABSPATH')) { exit; }
function dh_defaults() {
    return [
        'brand' => '屌软件', 'tagline' => '好软件，值得被发现。', 'accent' => '#b4553d', 'color_mode' => 'system',
        'hero_title' => '发现好软件，打开新可能。', 'hero_text' => '精选免费与开源软件，让每一个灵感，都有趁手的工具。',
        'announcement' => '保持好奇，探索开源的无限可能', 'per_page' => 12, 'excluded_categories' => '',
        'featured_ids' => '', 'editorial_categories' => '', 'editorial_widget_default' => 1, 'github_sync' => 1, 'votes' => 1, 'bookmarks' => 1, 'share' => 1,
        'seo' => 1, 'seo_title' => '', 'seo_description' => '屌软件：发现值得使用的免费软件、GitHub 开源项目、实用工具与软件教程。',
        'social_image' => '', 'ad_enabled' => 0, 'ad_interval' => 4, 'ad_content_enabled' => 1, 'ad_content_after' => 3,
        'footer_text' => '发现、分享，让好软件被更多人看见。', 'icp' => '', 'icp_url' => 'https://beian.miit.gov.cn/',
        'toolbox_url' => 'https://tool.nihendiao.com/', 'contact_url' => '',
        'sidebar_manifesto' => 1, 'sidebar_community' => 1, 'share_caption' => '好软件，值得分享。',
        'manifesto_kicker' => 'A LITTLE BETTER, EVERY DAY', 'manifesto_title' => "好工具，\n让生活简单一点。", 'manifesto_text' => "不用昂贵，也可以很好用。\n发现那些认真解决问题的软件。",
        'manifesto_button' => '订阅最新发现', 'manifesto_target' => 'newsletter', 'manifesto_url' => '',
        'community_title' => '开源，让好想法走得更远。', 'community_text' => "如果一个项目帮到了你，\n也别忘了给作者一颗 Star。", 'community_button' => '去 GitHub 探索', 'community_url' => 'https://github.com/',
        'mail_enabled' => 1, 'mail_title' => '好软件，送到你的邮箱。', 'mail_description' => '订阅最新软件发现。确认邮箱后接收新文章通知，随时可以退订。',
        'mail_notify_posts' => 1, 'mail_include_editorial' => 0, 'mail_sender_name' => '', 'mail_sender_email' => '', 'mail_batch_size' => 5,
        'analytics_enabled' => 0, 'analytics_position' => 'footer', 'analytics_code' => '', 'analytics_logged_in' => 0,
        'views_enabled' => 1, 'views_visible' => 1,
        'comment_mode' => 'inherit', 'comment_hide_existing' => 1, 'comment_honeypot' => 1, 'comment_force_moderation' => 1,
        'comment_hide_url' => 1, 'comment_block_bots' => 1, 'comment_block_rest' => 1, 'comment_limit_rate' => 1,
        'comment_rate_limit' => 5, 'comment_rate_window' => 10, 'comment_interval' => 60, 'comment_max_links' => 1,
        'comment_max_length' => 2000, 'comment_blocked_words' => '',
    ];
}
function dh_opt($key) { $options = get_option('dh_options', []); return $options[$key] ?? (dh_defaults()[$key] ?? ''); }
add_action('after_switch_theme', function () {
    if (get_option('dh_options', false) !== false) { return; }
    $options = dh_defaults();
    $tutorial = get_category_by_slug('tutorial');
    if ($tutorial) { $options['excluded_categories'] = (string) $tutorial->term_id; }
    update_option('dh_options', $options);
});
function dh_setting_groups() {
    return [
        'general' => ['品牌与外观', [
            'brand' => ['站点品牌', 'text'], 'tagline' => ['品牌短句', 'text'], 'accent' => ['强调色', 'color'],
            'color_mode' => ['默认色彩模式', 'select', ['system' => '跟随系统', 'light' => '浅色', 'dark' => '深色']],
            'announcement' => ['顶部公告（留空隐藏）', 'text'], 'toolbox_url' => ['工具箱链接（留空隐藏）', 'url'],
        ]],
        'home' => ['首页与功能', [
            'hero_title' => ['首页标题', 'text'], 'hero_text' => ['首页介绍', 'textarea'], 'per_page' => ['每页文章数（6–36）', 'number'],
            'featured_ids' => ['首页精选文章 ID（英文逗号分隔，最多 3 个）', 'text'],
            'excluded_categories' => ['发现列表排除分类 ID（教程/资讯等，含子分类）', 'text'],
            'editorial_categories' => ['教程/资讯分类 ID（逗号分隔；留空自动识别，含子分类）', 'text'],
            'editorial_widget_default' => ['未配置侧栏小工具时，显示默认教程资讯栏', 'checkbox'],
            'github_sync' => ['启用编辑页 GitHub 同步', 'checkbox'], 'votes' => ['启用推荐（游客可直接点击）', 'checkbox'],
            'bookmarks' => ['启用本机收藏', 'checkbox'], 'share' => ['启用分享按钮', 'checkbox'],
        ]],
        'sidebar' => ['侧栏与文案', [
            'sidebar_manifesto' => ['显示右侧「好工具，让生活简单一点」介绍卡片', 'checkbox'],
            'manifesto_kicker' => ['介绍卡片 · 顶部小字（留空隐藏）', 'text'], 'manifesto_title' => ['介绍卡片 · 标题（支持换行）', 'textarea'],
            'manifesto_text' => ['介绍卡片 · 正文（支持换行）', 'textarea'], 'manifesto_button' => ['介绍卡片 · 按钮文字', 'text'],
            'manifesto_target' => ['介绍卡片 · 按钮功能', 'select', ['newsletter' => '邮件订阅', 'rss' => 'RSS 订阅', 'custom' => '自定义链接', 'none' => '隐藏按钮']],
            'manifesto_url' => ['介绍卡片 · 自定义链接 URL', 'url'],
            'sidebar_community' => ['显示右侧「开源，让好想法走得更远」GitHub 卡片', 'checkbox'],
            'community_title' => ['开源卡片 · 标题', 'text'], 'community_text' => ['开源卡片 · 正文（支持换行）', 'textarea'],
            'community_button' => ['开源卡片 · 按钮文字', 'text'], 'community_url' => ['开源卡片 · 按钮链接', 'url'],
            'share_caption' => ['文章末尾分享引导文字（留空隐藏文字）', 'text'],
        ]],
        'newsletter' => ['邮件订阅', [
            'mail_enabled' => ['开启邮箱订阅入口', 'checkbox'], 'mail_title' => ['订阅页标题', 'text'], 'mail_description' => ['订阅说明', 'textarea'],
            'mail_notify_posts' => ['新文章首次发布时通知已确认订阅者', 'checkbox'], 'mail_include_editorial' => ['新文章通知包含教程/资讯', 'checkbox'],
            'mail_sender_name' => ['邮件发件人名称（留空沿用 WordPress）', 'text'], 'mail_sender_email' => ['发件人邮箱（留空沿用 WordPress/SMTP）', 'email'],
            'mail_batch_size' => ['每批发送数量（1–25）', 'number'],
        ]],
        'analytics' => ['统计与浏览', [
            'analytics_enabled' => ['启用自定义统计代码', 'checkbox'], 'analytics_position' => ['统计代码位置', 'select', ['footer' => '页脚', 'head' => '页面头部']],
            'analytics_code' => ['统计代码（完整 script / noscript 代码）', 'code'], 'analytics_logged_in' => ['登录用户也输出统计代码', 'checkbox'],
            'views_enabled' => ['启用文章浏览量统计（估算，30 分钟去重）', 'checkbox'], 'views_visible' => ['前台显示浏览量', 'checkbox'],
        ]],
        'comments' => ['评论防护', [
            'comment_mode' => ['评论模式', 'select', ['inherit' => '沿用 WordPress 原有设置', 'closed' => '彻底关闭前台评论（推荐当前不开评论的网站）', 'guarded' => '开启评论防护（仍遵循原有评论开关）']],
            'comment_hide_existing' => ['关闭模式下隐藏已有评论和评论入口（不删除记录）', 'checkbox'],
            'comment_force_moderation' => ['防护模式：普通评论全部先审核', 'checkbox'],
            'comment_honeypot' => ['防护模式：启用蜜罐和评论表单校验', 'checkbox'],
            'comment_block_bots' => ['防护模式：拒绝常见爬虫标识的评论提交', 'checkbox'],
            'comment_block_rest' => ['防护模式：禁止普通用户通过 REST 新增评论', 'checkbox'],
            'comment_hide_url' => ['防护模式：移除评论者的网站字段', 'checkbox'],
            'comment_limit_rate' => ['防护模式：按网络来源限制评论提交频率', 'checkbox'],
            'comment_rate_limit' => ['每个来源在时间窗口内最多提交次数', 'number', [1, 30]],
            'comment_rate_window' => ['提交次数统计窗口（分钟）', 'number', [1, 60]],
            'comment_interval' => ['同一来源两次提交最小间隔（秒；0 为不限制间隔）', 'number', [0, 600]],
            'comment_max_links' => ['评论正文最多链接数（0 为不允许链接）', 'number', [0, 10]],
            'comment_max_length' => ['评论正文最大字数', 'number', [100, 20000]],
            'comment_blocked_words' => ['拒绝的关键词（每行一个，最多 100 个）', 'textarea'],
        ]],
        'seo' => ['SEO 与社交', [
            'seo' => ['启用内置 SEO（检测到常见 SEO 插件时自动停用）', 'checkbox'],
            'seo_title' => ['首页 SEO 标题（留空使用站点标题）', 'text'], 'seo_description' => ['首页描述', 'textarea'],
            'social_image' => ['默认社交分享图 URL', 'url'],
        ]],
        'ads' => ['广告管理', ['ad_enabled' => ['启用所有已配置广告', 'checkbox'], 'ad_interval' => ['列表广告插入位置（第几条之后）', 'number'], 'ad_content_enabled' => ['启用文章正文中广告', 'checkbox'], 'ad_content_after' => ['正文中广告位置（第几个完整段落之后，1–36）', 'number']]],
        'footer' => ['页脚与联系', [
            'footer_text' => ['页脚文字', 'textarea'], 'icp' => ['ICP备案号（留空隐藏）', 'text'],
            'icp_url' => ['备案查询地址', 'url'], 'contact_url' => ['自定义投稿页面 URL（留空使用内置审核投稿）', 'url'],
        ]],
    ];
}
function dh_ad_slots() { return ['top' => '首页顶部', 'feed' => '发现列表中', 'sidebar' => '右侧栏', 'content' => '文章正文中', 'article' => '文章正文后', 'footer' => '全站页脚']; }
function dh_setting_number_bounds($key, $field) { return $field[2] ?? [$key === 'per_page' ? 6 : 1, $key === 'mail_batch_size' ? 25 : 36]; }
function dh_sanitize_options($input) {
    $input = is_array($input) ? $input : [];
    $clean = [];
    foreach (dh_setting_groups() as $group) {
        foreach ($group[1] as $key => $field) {
            $value = isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';
            switch ($field[1]) {
                case 'checkbox': $clean[$key] = empty($value) ? 0 : 1; break;
                case 'url': $clean[$key] = esc_url_raw($value, ['http', 'https']); break;
                case 'textarea': $clean[$key] = sanitize_textarea_field($value); break;
                case 'email': $clean[$key] = sanitize_email($value); break;
                case 'code': $old = get_option('dh_options', []); $clean[$key] = current_user_can('unfiltered_html') && array_key_exists($key, $input) ? $value : ($old[$key] ?? ''); break;
                case 'color': $clean[$key] = sanitize_hex_color($value) ?: '#b4553d'; break;
                case 'select': $clean[$key] = array_key_exists($value, $field[2]) ? $value : array_key_first($field[2]); break;
                case 'number': $bounds = dh_setting_number_bounds($key, $field); $number = isset($field[2]) ? (is_numeric($value) ? (int) $value : (dh_defaults()[$key] ?? $bounds[0])) : absint($value); $clean[$key] = max($bounds[0], min($bounds[1], $number)); break;
                default: $clean[$key] = sanitize_text_field($value);
            }
        }
    }
    $words = preg_split('/\R/u', $clean['comment_blocked_words']) ?: [];
    $clean['comment_blocked_words'] = implode("\n", array_slice(array_values(array_unique(array_filter(array_map(function ($word) { return wp_html_excerpt(trim($word), 100, ''); }, $words)))), 0, 100));
    foreach (['featured_ids', 'excluded_categories', 'editorial_categories'] as $key) {
        $ids = array_filter(array_map('absint', explode(',', $clean[$key])));
        $clean[$key] = implode(',', array_slice(array_unique($ids), 0, $key === 'featured_ids' ? 3 : 100));
    }
    foreach (dh_ad_slots() as $slot => $label) {
        foreach (['title', 'image', 'url', 'html'] as $type) {
            $key = 'ad_' . $slot . '_' . $type;
            $value = isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';
            if ($type === 'html') {
                $old = get_option('dh_options', []);
                $clean[$key] = current_user_can('unfiltered_html') ? $value : ($old[$key] ?? '');
            } else { $clean[$key] = in_array($type, ['image', 'url'], true) ? esc_url_raw($value, ['http', 'https']) : sanitize_text_field($value); }
        }
    }
    return $clean;
}
add_action('admin_init', function () { register_setting('dh_settings', 'dh_options', ['sanitize_callback' => 'dh_sanitize_options']); });
add_action('admin_menu', function () { add_theme_page('Discover 主题设置', 'Discover 设置', 'manage_options', 'dh-settings', 'dh_settings_page'); });
function dh_settings_page() {
    if (!current_user_can('manage_options')) { return; }
    $groups = dh_setting_groups();
    ?>
    <div class="wrap dh-admin"><style>
    .dh-admin{max-width:1120px}.dh-admin-hero{padding:28px 32px;background:#20252c;color:white;border-radius:12px;margin:24px 0}.dh-admin-hero h1{color:white;font-size:30px;font-weight:700}.dh-admin-hero p{color:#c5cbd3}.dh-admin-nav{display:flex;gap:8px;flex-wrap:wrap;margin:20px 0}.dh-admin-nav a{padding:10px 16px;background:white;border:1px solid #ddd;border-radius:7px;text-decoration:none}.dh-admin fieldset{background:white;padding:18px 28px;margin:16px 0;border:1px solid #e1e3e5;border-radius:10px}.dh-admin legend{font-size:18px;font-weight:600;padding:8px}.dh-admin textarea{width:100%;max-width:700px;min-height:85px}.dh-admin .form-table th{width:310px}.dh-admin input[type=text],.dh-admin input[type=url]{width:100%;max-width:700px}.dh-admin .submit{position:sticky;bottom:0;background:#f0f0f1ed;padding:16px;border-top:1px solid #ddd}.dh-admin small{color:#777}
    </style>
    <div class="dh-admin-hero"><h1>屌软件 <span style="color:#ff997e">/ Discover</span></h1><p>轻量、专注、开箱即用。让每一个好软件被发现。</p></div>
    <?php settings_errors(); ?>
    <nav class="dh-admin-nav"><?php foreach ($groups as $id => $group) { echo '<a href="#dh-' . esc_attr($id) . '">' . esc_html($group[0]) . '</a>'; } ?></nav>
    <p>Logo 与站点图标：<a href="<?php echo esc_url(admin_url('customize.php')); ?>">外观 → 自定义</a>；导航：<a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">外观 → 菜单</a>。软件信息在文章编辑页填写。<a href="<?php echo esc_url(home_url('/wp-sitemap.xml')); ?>" target="_blank" rel="noopener">查看站点地图 ↗</a></p>
    <form method="post" action="options.php"><?php settings_fields('dh_settings'); ?>
    <?php foreach ($groups as $id => $group): ?>
    <fieldset id="dh-<?php echo esc_attr($id); ?>"><legend><?php echo esc_html($group[0]); ?></legend><table class="form-table" role="presentation">
    <?php foreach ($group[1] as $key => $field): $value = dh_opt($key); ?>
    <tr><th scope="row"><label for="dh-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label></th><td>
    <?php if (in_array($field[1], ['textarea', 'code'], true)): ?><textarea id="dh-<?php echo esc_attr($key); ?>" class="<?php echo $field[1] === 'code' ? 'code' : ''; ?>" name="dh_options[<?php echo esc_attr($key); ?>]" <?php if ($field[1] === 'code') { disabled(!current_user_can('unfiltered_html')); } ?>><?php echo esc_textarea($value); ?></textarea>
    <?php elseif ($field[1] === 'checkbox'): ?><label><input type="checkbox" id="dh-<?php echo esc_attr($key); ?>" name="dh_options[<?php echo esc_attr($key); ?>]" value="1" <?php checked($value, 1); ?>> 启用</label>
    <?php elseif ($field[1] === 'select'): ?><select id="dh-<?php echo esc_attr($key); ?>" name="dh_options[<?php echo esc_attr($key); ?>]"><?php foreach ($field[2] as $v => $label) { echo '<option value="' . esc_attr($v) . '" ' . selected($v, $value, false) . '>' . esc_html($label) . '</option>'; } ?></select>
    <?php else: ?><input type="<?php echo esc_attr($field[1]); ?>" id="dh-<?php echo esc_attr($key); ?>" name="dh_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" <?php if ($field[1] === 'number') { $bounds = dh_setting_number_bounds($key, $field); echo 'min="' . esc_attr($bounds[0]) . '" max="' . esc_attr($bounds[1]) . '"'; } ?>><?php endif; ?>
    </td></tr><?php endforeach; ?></table>
    <?php if ($id === 'newsletter'): ?><p>双重确认：先发确认邮件，点击邮件链接并确认后才会接收新文章通知；每封通知带退订入口。使用 WordPress 发信服务，可在 wp-config.php 配置 DH_SMTP_* 常量接入 SMTP。<a href="<?php echo esc_url(admin_url('themes.php?page=dh-newsletter')); ?>">管理订阅者与查看队列</a>。新通知通过 WP-Cron 分批发送。</p><?php endif; ?>
    <?php if ($id === 'analytics'): ?><p>可粘贴百度、Google Analytics、Umami 等完整代码；仅具备 unfiltered_html 权限的管理员可保存脚本。关闭开关保留代码。浏览量独立统计，不依赖第三方服务。</p><?php endif; ?>
    <?php if ($id === 'comments'): ?><p>目前不开评论时请选择「彻底关闭前台评论」，同时拦截直接提交、REST 新增和评论订阅入口；已有记录仍可在后台管理。防护模式不会替你开启文章评论；蜜罐、链接、字数、关键词和频率检查不通过的提交会直接拒绝，不写入垃圾评论库。管理员审核与后台回复保留。</p><p>限流按服务器 REMOTE_ADDR 的摘要计数，不信任任意转发头；反向代理需正确配置真实来源，共享网络可能共用额度。仅限制留言，不屏蔽搜索引擎正常抓取。高频访问、采集及流量攻击还需在服务器/CDN/WAF 设置限流。修改模式后清理页面缓存。</p><?php endif; ?>
    <?php if ($id === 'seo'): ?><p class="description">包含 canonical、描述、Open Graph、Twitter Card、结构化数据；沿用 WordPress 核心 sitemap。搜索、收藏与筛选页不参与索引。Yoast、Rank Math、AIOSEO、SEOPress 存在时自动让位。</p><?php endif; ?>
    <?php if ($id === 'ads'): ?><p>可填图片广告或 HTML 代码；HTML 优先。仅有 unfiltered_html 权限的管理员可保存执行脚本，代码由你信任的广告服务提供。未配置广告不占空间。正文中广告默认显示在第 3 个完整独立段落后；段落不足时放在最后一个独立段落后。列表、引用、代码块等嵌套内容不计入，正文没有适合的段落时不插入。正文后的原有广告位独立保留。</p>
    <?php foreach (dh_ad_slots() as $slot => $label): ?><h3><?php echo esc_html($label); ?></h3><table class="form-table" role="presentation"><?php foreach (['title' => '广告文案 / 图片替代文字', 'image' => '广告图片 URL', 'url' => '广告目标链接', 'html' => '广告 HTML / 脚本'] as $type => $name): $key = 'ad_' . $slot . '_' . $type; ?><tr><th><label for="dh-<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></label></th><td><?php if ($type === 'html'): ?><textarea id="dh-<?php echo esc_attr($key); ?>" name="dh_options[<?php echo esc_attr($key); ?>]" <?php disabled(!current_user_can('unfiltered_html')); ?>><?php echo esc_textarea(dh_opt($key)); ?></textarea><?php else: ?><input id="dh-<?php echo esc_attr($key); ?>" type="<?php echo in_array($type, ['image', 'url'], true) ? 'url' : 'text'; ?>" name="dh_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(dh_opt($key)); ?>"><?php endif; ?></td></tr><?php endforeach; ?></table><?php endforeach; ?>
    <?php endif; ?></fieldset><?php endforeach; ?>
    <?php submit_button('保存主题设置'); ?></form></div>
    <?php
}
