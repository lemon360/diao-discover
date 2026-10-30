<?php if (!defined('ABSPATH')) { exit; } $id = get_the_ID(); $data = dh_meta('github'); $category = get_the_category(); ?>
<article <?php post_class('product-row'); ?> data-post-id="<?php echo esc_attr($id); ?>">
<a class="product-icon-link" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php dh_product_icon(); ?></a>
<div class="product-content"><div class="product-heading"><h2><a href="<?php the_permalink(); ?>"><?php echo esc_html(dh_title()); ?></a></h2><?php if (dh_meta('badge')): ?><span class="mini-badge"><?php echo esc_html(dh_meta('badge')); ?></span><?php endif; ?></div>
<p class="product-summary"><?php echo esc_html(dh_summary()); ?></p>
<div class="product-meta"><?php if ($category): ?><a href="<?php echo esc_url(get_category_link($category[0])); ?>"><?php echo esc_html($category[0]->name); ?></a><?php endif; ?>
<?php if (is_array($data) && isset($data['stars'])): ?><span class="star-meta" title="GitHub Star 缓存于 <?php echo esc_attr(wp_date('Y-m-d', $data['time'])); ?>"><?php echo dh_icon('star'); ?><?php echo esc_html(dh_number($data['stars'])); ?></span><?php endif; ?>
<?php if (dh_meta('platform')): ?><span class="platform-meta"><?php echo esc_html(dh_meta('platform')); ?></span><?php endif; dh_view_badge($id); ?>
<a class="comment-meta" href="<?php echo esc_url(get_comments_link()); ?>"><?php echo dh_icon('comment'); ?><?php echo esc_html(get_comments_number()); ?></a><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date('m-d')); ?></time></div></div>
<div class="row-actions"><?php dh_bookmark_button($id); dh_vote_button($id); ?></div></article>
