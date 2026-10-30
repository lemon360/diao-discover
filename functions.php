<?php
/** 屌软件 Discover theme. */
if (!defined('ABSPATH')) { exit; }
define('DH_VERSION', '1.4.1');
foreach (['settings', 'colors', 'software', 'seo', 'community', 'template', 'content-ads', 'widgets', 'tools', 'newsletter', 'insights', 'html-minify'] as $module) {
    require_once get_template_directory() . '/inc/' . $module . '.php';
}
add_action('after_setup_theme', function () {
    load_theme_textdomain('diao-discover', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 80, 'width' => 280, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('editor-styles');
    add_editor_style('assets/editor.css');
    register_nav_menus(['primary' => '顶部导航', 'footer' => '页脚导航']);
    add_image_size('dh-icon', 128, 128, true);
    $GLOBALS['content_width'] = 780;
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dh-style', get_stylesheet_uri(), [], DH_VERSION);
    wp_enqueue_script('dh-app', get_theme_file_uri('assets/app.js'), [], DH_VERSION, true);
    wp_localize_script('dh-app', 'DH', [
        'rest' => esc_url_raw(rest_url('diao/v1/')), 'nonce' => wp_create_nonce('wp_rest'),
        'loggedIn' => is_user_logged_in(), 'login' => wp_login_url(get_permalink() ?: home_url('/')),
        'home' => home_url('/'), 'theme' => dh_opt('color_mode'),
        'viewPost' => dh_opt('views_enabled') && is_single() && !is_preview() && !post_password_required() && !current_user_can('edit_post', get_queried_object_id()) ? get_queried_object_id() : 0,
    ]);
    $palette = dh_color_palette(dh_opt('accent'));
    wp_add_inline_style('dh-style', ':root{--brand-accent:' . $palette['brand'] . ';--accent:' . $palette['light'] . ';--on-accent:#fffaf5}[data-theme=dark]{--accent:' . $palette['dark'] . ';--on-accent:#202820}');
    if (is_singular() && comments_open() && get_option('thread_comments')) { wp_enqueue_script('comment-reply'); }
});
add_filter('excerpt_length', function () { return 40; });
add_filter('excerpt_more', function () { return '…'; });
