<?php
if (!defined('ABSPATH')) { exit; }
if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
nocache_headers();
$error = null; $input = [];
if (isset($_GET['submit_error']) && is_string($_GET['submit_error']) && preg_match('/^[A-Za-z0-9]{32}$/D', wp_unslash($_GET['submit_error']))) {
    $token = wp_unslash($_GET['submit_error']);
    $error = get_transient('dh_submit_error_' . $token);
    if (is_array($error)) { $input = $error['input'] ?? []; delete_transient('dh_submit_error_' . $token); }
}
get_header(); ?>
<div class="site-layout reading-layout"><?php get_template_part('template-parts/rail'); ?><main id="main-content" class="reading-main"><section class="submission-page"><span class="eyebrow">GOOD TOOLS DESERVE TO BE FOUND</span><h1>让好软件，被更多人发现。</h1><p class="submission-lead">无需注册或登录，直接分享你的发现。<br>仅接受软件链接和文字介绍，编辑审核通过后公开。</p>
<?php if (isset($_GET['submitted'])): ?><div class="submission-success" role="status"><?php echo dh_icon('check'); ?><h2>收到你的发现了。</h2><p>软件已进入待审核列表，编辑审核通过后才会公开展示。</p><a class="button" href="<?php echo esc_url(home_url('/')); ?>">继续发现 <?php echo dh_icon('arrow'); ?></a></div>
<?php else: ?>
<?php if (is_array($error)): ?><p class="submission-error" role="alert"><?php echo esc_html($error['message']); ?></p><?php endif; ?>
<form class="submission-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="dh_submit"><?php wp_nonce_field('dh_submit', 'dh_submit_nonce'); ?>
<div class="submission-honeypot" aria-hidden="true"><label>Company website<input name="software[company_website]" tabindex="-1" autocomplete="off"></label></div>
<?php foreach (['name' => ['软件名称 *', '例如 LocalSend', true, 'text'], 'summary' => ['一句话介绍 *', '它帮你解决了什么问题？', true, 'text'], 'repo' => ['GitHub 仓库', 'https://github.com/作者/仓库', false, 'url'], 'website' => ['官方网站', 'https://', false, 'url']] as $key => $field): ?><p><label for="submit-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label><input id="submit-<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($field[3]); ?>" name="software[<?php echo esc_attr($key); ?>]" placeholder="<?php echo esc_attr($field[1]); ?>" value="<?php echo esc_attr(isset($input[$key]) && is_scalar($input[$key]) ? $input[$key] : ''); ?>" <?php echo $field[2] ? 'required' : ''; ?> maxlength="<?php echo $key === 'name' ? '80' : '300'; ?>"></p><?php endforeach; ?>
<p class="section-caption">GitHub 仓库与官方网站至少填写一项。不支持图片、附件或富文本上传。</p><p><label for="submit-details">文字介绍</label><textarea id="submit-details" name="software[details]" rows="6" maxlength="5000" placeholder="聊聊软件的用途、亮点或使用体验。只接受文字，请勿提交账号密码等私人信息。"><?php echo esc_textarea(isset($input['details']) && is_scalar($input['details']) ? $input['details'] : ''); ?></textarea></p><p class="section-caption">每次提交后请间隔 5 分钟。所有投稿均需人工审核。</p><button class="button" type="submit">提交这份发现 <?php echo dh_icon('arrow'); ?></button></form>
<?php endif; ?></section></main><?php get_template_part('template-parts/sidebar'); ?></div><?php get_footer(); ?>
