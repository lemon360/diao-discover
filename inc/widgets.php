<?php
if (!defined('ABSPATH')) { exit; }
/** Editorial content lives in its own sidebar widget, outside software discovery. */
class DH_Editorial_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('dh_editorial', 'Discover · 教程与资讯', ['description' => '独立展示教程、资讯或所选分类文章；可设置标题、分类、数量、排序与日期。']);
    }
    public function widget($args, $instance) {
        $ids = !empty($instance['categories']) ? dh_expand_categories((array) $instance['categories']) : dh_editorial_categories();
        if (!$ids) { return; }
        $query = new WP_Query(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => max(1, min(10, absint($instance['number'] ?? 5))),
            'category__in' => $ids, 'post__not_in' => is_single() ? [get_queried_object_id()] : [], 'ignore_sticky_posts' => true, 'no_found_rows' => true,
            'orderby' => ($instance['order'] ?? '') === 'comments' ? 'comment_count' : 'date', 'order' => 'DESC']);
        if (!$query->have_posts()) { return; }
        echo $args['before_widget']; // Trusted markup from register_sidebar / theme.
        $title = apply_filters('widget_title', $instance['title'] ?? '教程与资讯', $instance, $this->id_base);
        if ($title !== '') { echo $args['before_title'] . esc_html($title) . $args['after_title']; }
        echo '<ul class="editorial-list">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
            if (!empty($instance['show_date'])) { echo '<time datetime="' . esc_attr(get_the_date(DATE_W3C)) . '">' . esc_html(get_the_date('Y-m-d')) . '</time>'; }
            echo '</li>';
        }
        echo '</ul>';
        if (count($ids) === 1) { echo '<a class="editorial-more" href="' . esc_url(get_category_link($ids[0])) . '">查看全部 →</a>'; }
        echo $args['after_widget'];
        wp_reset_postdata();
    }
    public function form($instance) {
        $title = $instance['title'] ?? '教程与资讯';
        $selected = array_map('absint', (array) ($instance['categories'] ?? []));
        ?>
        <p><label for="<?php echo esc_attr($this->get_field_id('title')); ?>">标题</label><input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" value="<?php echo esc_attr($title); ?>"></p>
        <p>选择分类（可多选；不选时使用主题设置中识别的教程/资讯分类，含子分类）：</p>
        <div style="max-height:200px;overflow:auto;border:1px solid #ddd;padding:8px">
        <?php foreach (get_categories(['hide_empty' => false]) as $term): ?><label style="display:block;margin:5px 0"><input type="checkbox" name="<?php echo esc_attr($this->get_field_name('categories')); ?>[]" value="<?php echo esc_attr($term->term_id); ?>" <?php checked(in_array((int) $term->term_id, $selected, true)); ?>> <?php echo esc_html($term->name); ?></label><?php endforeach; ?></div>
        <p><label for="<?php echo esc_attr($this->get_field_id('number')); ?>">文章数量（1–10）</label> <input type="number" min="1" max="10" id="<?php echo esc_attr($this->get_field_id('number')); ?>" name="<?php echo esc_attr($this->get_field_name('number')); ?>" value="<?php echo esc_attr($instance['number'] ?? 5); ?>"></p>
        <p><label for="<?php echo esc_attr($this->get_field_id('order')); ?>">排序</label> <select id="<?php echo esc_attr($this->get_field_id('order')); ?>" name="<?php echo esc_attr($this->get_field_name('order')); ?>"><option value="date" <?php selected($instance['order'] ?? 'date', 'date'); ?>>最新发布</option><option value="comments" <?php selected($instance['order'] ?? '', 'comments'); ?>>评论最多</option></select></p>
        <p><label><input type="checkbox" name="<?php echo esc_attr($this->get_field_name('show_date')); ?>" value="1" <?php checked(!empty($instance['show_date'])); ?>> 显示日期</label></p>
        <?php
    }
    public function update($new, $old) {
        $ids = array_slice(array_unique(array_filter(array_map('absint', (array) ($new['categories'] ?? [])))), 0, 100);
        return ['title' => sanitize_text_field(is_scalar($new['title'] ?? '') ? ($new['title'] ?? '') : ''), 'categories' => $ids,
            'number' => max(1, min(10, absint($new['number'] ?? 5))), 'order' => ($new['order'] ?? '') === 'comments' ? 'comments' : 'date', 'show_date' => empty($new['show_date']) ? 0 : 1];
    }
}
add_action('widgets_init', function () {
    register_sidebar(['name' => 'Discover 右侧边栏', 'id' => 'dh-right-sidebar', 'description' => '放置教程/资讯、文字等小工具，可自由排序。添加小工具后替代默认教程资讯栏。',
        'before_widget' => '<section id="%1$s" class="sidebar-section dh-widget %2$s">', 'after_widget' => '</section>',
        'before_title' => '<div class="section-heading"><h2>', 'after_title' => '</h2></div>']);
    register_widget('DH_Editorial_Widget');
});
function dh_sidebar_widgets() {
    if (is_active_sidebar('dh-right-sidebar')) { dynamic_sidebar('dh-right-sidebar'); }
    elseif (dh_opt('editorial_widget_default')) {
        the_widget('DH_Editorial_Widget', ['title' => '教程与资讯', 'number' => 5, 'show_date' => 1], ['before_widget' => '<section class="sidebar-section dh-widget widget_dh_editorial">', 'after_widget' => '</section>', 'before_title' => '<div class="section-heading"><h2>', 'after_title' => '</h2></div>']);
    }
}
