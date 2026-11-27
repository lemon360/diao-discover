<?php
if (!defined('ABSPATH')) { exit; }

function dh_adsense_publisher($value) {
    return is_string($value) && preg_match('/\Aca-pub-[0-9]{16}\z/', $value) ? $value : '';
}

function dh_adsense_unit($value) {
    return is_string($value) && preg_match('/\A[0-9]{1,20}\z/', $value) ? $value : '';
}

function dh_adsense_layout_key($value) {
    return is_string($value) && preg_match('/\A[a-zA-Z0-9_+\-]{1,160}\z/', $value) ? $value : '';
}

function dh_adsense_formats($slot) {
    $formats = ['display' => '响应式展示广告'];
    if ($slot === 'feed') { $formats['in-feed'] = '信息流广告'; }
    if (in_array($slot, ['content', 'article'], true)) { $formats['in-article'] = '文章内嵌广告'; }
    return $formats;
}

function dh_adsense_default_height($slot) {
    return in_array($slot, ['content', 'article', 'sidebar'], true) ? 180 : 90;
}

function dh_adsense_public_page() {
    return !is_admin() && !is_feed() && !is_404() && !is_search() && !is_preview()
        && !isset($_GET['dh_submit']) && !isset($_GET['dh_newsletter']) && !isset($_GET['saved'])
        && (!is_singular() || !post_password_required());
}

function dh_adsense_slot_options($slot) {
    $key = 'ad_' . $slot . '_';
    if (dh_opt($key . 'provider') !== 'adsense') { return []; }
    $publisher = dh_adsense_publisher(dh_opt('adsense_publisher'));
    $unit = dh_adsense_unit(dh_opt($key . 'unit'));
    $format = dh_opt($key . 'format') ?: 'display';
    $layout = dh_adsense_layout_key(dh_opt($key . 'layout_key'));
    if (!$publisher || !$unit || !isset(dh_adsense_formats($slot)[$format]) || ($format === 'in-feed' && !$layout)) {
        return [];
    }
    return ['publisher' => $publisher, 'unit' => $unit, 'format' => $format, 'layout' => $layout];
}

add_action('wp_head', function () {
    if (!dh_opt('ad_enabled') || !dh_adsense_public_page()) { return; }

    // Sidebar and footer are shared by public content pages. Other slots depend on the template.
    $slots = ['sidebar', 'footer'];
    if (!is_singular()) {
        $slots[] = 'feed';
        if (is_home() && !is_paged()) { $slots[] = 'top'; }
    }
    if (is_singular('post')) {
        $slots[] = 'article';
        if (dh_opt('ad_content_enabled')) { $slots[] = 'content'; }
    }
    foreach ($slots as $slot) {
        $config = dh_adsense_slot_options($slot);
        if (!$config) { continue; }
        wp_print_script_tag([
            'data-cfasync' => 'false',
            'id' => 'dh-adsense-loader',
            'async' => true,
            'src' => 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . $config['publisher'],
            'crossorigin' => 'anonymous',
        ]);
        break;
    }
}, 10);

function dh_adsense_render($slot) {
    if (!dh_adsense_public_page()) { return; }
    $config = dh_adsense_slot_options($slot);
    if (!$config) { return; }
    $height = dh_opt('ad_' . $slot . '_reserve');
    $height = $height === '' ? dh_adsense_default_height($slot) : max(0, min(600, (int) $height));
    $sidebar = $slot === 'sidebar';
    $attributes = '';
    $style = 'display:block';
    if ($config['format'] === 'in-article') {
        $style .= ';text-align:center';
        $attributes = ' data-ad-layout="in-article" data-ad-format="fluid"';
    } elseif ($config['format'] === 'in-feed') {
        $attributes = ' data-ad-format="fluid" data-ad-layout-key="' . esc_attr($config['layout']) . '"';
    } else {
        $attributes = ' data-ad-format="auto" data-full-width-responsive="' . ($sidebar ? 'false' : 'true') . '"';
    }
    ?>
    <aside class="ad-slot ad-adsense ad-<?php echo esc_attr($slot); ?>" aria-label="广告" style="--dh-ad-reserve:<?php echo esc_attr($height); ?>px">
        <?php if ($sidebar): ?><template id="dh-adsense-sidebar"><?php endif; ?>
        <ins class="adsbygoogle" style="<?php echo esc_attr($style); ?>" data-ad-client="<?php echo esc_attr($config['publisher']); ?>" data-ad-slot="<?php echo esc_attr($config['unit']); ?>"<?php echo $attributes; // Values above are fixed or escaped. ?>></ins>
        <?php if ($sidebar): ?></template><?php else:
            wp_print_inline_script_tag('(window.adsbygoogle = window.adsbygoogle || []).push({});', ['data-cfasync' => 'false']);
        endif; ?>
    </aside>
    <?php
    if ($sidebar) { add_action('wp_footer', 'dh_adsense_sidebar_init'); }
}

function dh_adsense_sidebar_init() {
    // Match the sidebar breakpoint in style.css. Hidden units stay out of the live DOM.
    wp_print_inline_script_tag(<<<'JS'
(() => {
  const template = document.getElementById('dh-adsense-sidebar');
  if (!template || !template.content) return;
  const desktop = matchMedia('(max-width: 1100px)');
  function showSidebarAd() {
    if (desktop.matches || template.parentElement.getBoundingClientRect().width <= 0) return;
    if (desktop.removeEventListener) desktop.removeEventListener('change', showSidebarAd);
    else desktop.removeListener(showSidebarAd);
    template.replaceWith(template.content.cloneNode(true));
    (window.adsbygoogle = window.adsbygoogle || []).push({});
  }
  if (desktop.addEventListener) desktop.addEventListener('change', showSidebarAd);
  else desktop.addListener(showSidebarAd);
  showSidebarAd();
})();
JS, ['id' => 'dh-adsense-sidebar-init', 'data-cfasync' => 'false']);
}
