<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr(dh_opt('color_mode') === 'system' ? 'light' : dh_opt('color_mode')); ?>">
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<script>try{var m=localStorage.getItem('dh-theme')||<?php echo wp_json_encode(dh_opt('color_mode')); ?>;document.documentElement.dataset.theme=m==='system'?(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'):m}catch(e){}</script>
<?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#main-content">跳转到正文</a>
<header class="site-header"><div class="header-inner">
<a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(dh_opt('brand')); ?> 首页">
<?php if (has_custom_logo()): $logo = wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full'); ?><img src="<?php echo esc_url($logo[0]); ?>" alt="<?php echo esc_attr(dh_opt('brand')); ?>" class="brand-image">
<?php else: ?><span class="brand-mark" aria-hidden="true"><?php echo dh_icon('code'); ?></span><span><?php echo esc_html(dh_opt('brand')); ?><small>DISCOVER THE GOOD</small></span><?php endif; ?></a>
<nav class="desktop-nav" aria-label="主导航"><?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'primary-menu', 'fallback_cb' => 'dh_primary_fallback', 'depth' => 2]); ?></nav>
<form class="header-search" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search"><label class="sr-only" for="header-s">搜索软件</label><?php echo dh_icon('search'); ?><input id="header-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="搜索你的下一款好软件"><kbd>/</kbd></form>
<div class="header-actions"><button class="icon-button" data-theme-toggle aria-label="切换深浅色模式"><?php echo dh_icon('moon'); ?></button><a class="submit-button" href="<?php echo esc_url(dh_submit_url()); ?>"><?php echo dh_icon('spark'); ?><span>推荐好软件</span></a><button class="icon-button mobile-toggle" aria-label="打开导航" aria-controls="mobile-panel" aria-expanded="false"><?php echo dh_icon('menu'); ?></button></div>
</div></header>
<div class="mobile-panel" id="mobile-panel" hidden><nav aria-label="移动导航"><?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'primary-menu', 'menu_id' => 'mobile-primary-menu', 'fallback_cb' => 'dh_primary_fallback', 'depth' => 2]); ?></nav><details class="mobile-categories"><summary>全部软件分类</summary><div><?php foreach (dh_nav_categories() as $term): ?><a href="<?php echo esc_url(get_category_link($term)); ?>"><?php echo esc_html($term->name); ?></a><?php endforeach; ?></div></details><form action="<?php echo esc_url(home_url('/')); ?>" method="get"><label class="sr-only" for="mobile-s">搜索软件</label><input id="mobile-s" name="s" placeholder="搜索软件…"><button class="button" type="submit">搜索</button></form><?php if (dh_opt('bookmarks')): ?><a data-saved-link href="<?php echo esc_url(add_query_arg('saved', '1', home_url('/'))); ?>">我的收藏</a><?php endif; ?></div>
