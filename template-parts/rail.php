<?php if (!defined('ABSPATH')) { exit; } ?>
<aside class="left-rail" aria-label="发现导航"><div class="rail-sticky">
<p class="rail-label">你的发现之旅</p>
<nav class="rail-nav"><a class="<?php echo is_home() && !isset($_GET['saved']) ? 'selected' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>"><?php echo dh_icon('grid'); ?>发现广场<span class="nav-dot"></span></a>
<?php if (dh_opt('votes')): ?><a href="<?php echo esc_url(add_query_arg('sort', 'popular', home_url('/'))); ?>"><?php echo dh_icon('flame'); ?>人气推荐</a><?php endif; ?>
<?php if (dh_opt('bookmarks')): ?><a data-saved-link class="<?php echo isset($_GET['saved']) ? 'selected' : ''; ?>" href="<?php echo esc_url(add_query_arg('saved', '1', home_url('/'))); ?>"><?php echo dh_icon('bookmark'); ?>我的收藏<span class="saved-count">0</span></a><?php endif; ?></nav>
<div class="rail-divider"></div><p class="rail-label">按兴趣探索</p>
<nav class="rail-nav category-nav"><?php $icons = ['ai' => 'spark', 'efficiency' => 'flame', 'tools' => 'monitor', 'code' => 'code', 'mobile' => 'phone', 'online' => 'globe', 'tutorial' => 'book']; foreach (dh_nav_categories() as $term): ?><a class="<?php echo is_category($term->term_id) ? 'selected' : ''; ?>" href="<?php echo esc_url(get_category_link($term)); ?>"><?php echo dh_icon($icons[$term->slug] ?? 'grid'); ?><span><?php echo esc_html($term->name); ?></span></a><?php endforeach; ?></nav>
<?php if (dh_opt('toolbox_url')): ?><a class="rail-toolbox" href="<?php echo esc_url(dh_opt('toolbox_url')); ?>" target="_blank" rel="noopener"><?php echo dh_icon('code'); ?><span>实用工具箱<small>灵感之外，也要效率</small></span><?php echo dh_icon('external'); ?></a><?php endif; ?>
<p class="rail-bottom"><?php echo esc_html(dh_opt('tagline')); ?><br><span>Made for the curious.</span></p></div></aside>
