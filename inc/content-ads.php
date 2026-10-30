<?php
if (!defined('ABSPATH')) { exit; }
/** Insert at a complete top-level paragraph boundary without rewriting source HTML. */
function dh_insert_content_ad($html, $after, $ad) {
    if (!is_string($html) || $html === '' || $ad === '') { return $html; }
    $attributes = '(?:[^<>"\x27]|"[^"]*"|\x27[^\x27]*\x27)*';
    $pattern = '~<!--[\s\S]*?(?:-->|$)|<!\[CDATA\[[\s\S]*?(?:\]\]>|$)|<(script|style|pre|textarea|code|svg|math)\b' . $attributes . '>[\s\S]*?(?:</\1\s*>|$)|<\s*(/?)\s*([a-z][a-z0-9:-]*)\b' . $attributes . '>~i';
    if (!preg_match_all($pattern, $html, $tokens, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) { return $html; }
    $void = array_fill_keys(explode(' ', 'area base br col embed hr img input link meta param source track wbr'), true);
    $stack = []; $boundaries = [];
    foreach ($tokens as $token) {
        if (!isset($token[3]) || $token[3][0] === '') { continue; }
        $tag = strtolower($token[3][0]);
        if (isset($void[$tag])) { continue; }
        if ($token[2][0] === '/') {
            // Ambiguous/malformed nesting: preserve content rather than guess.
            if (!$stack || end($stack) !== $tag) { return $html; }
            array_pop($stack);
            if ($tag === 'p' && !$stack) { $boundaries[] = $token[0][1] + strlen($token[0][0]); }
        } else { $stack[] = $tag; }
    }
    if ($stack || !$boundaries) { return $html; }
    $index = min(max(1, absint($after)), count($boundaries)) - 1;
    $offset = $boundaries[$index];
    return substr($html, 0, $offset) . "\n" . $ad . "\n" . substr($html, $offset);
}
function dh_article_content() {
    if (!dh_opt('ad_enabled') || !dh_opt('ad_content_enabled') || !is_singular('post') || !in_the_loop() || !is_main_query() || is_feed() || post_password_required()) { the_content(); return; }
    ob_start(); dh_ad('content'); $ad = ob_get_clean();
    if ($ad === '') { the_content(); return; }
    ob_start(); the_content(); $html = ob_get_clean();
    echo dh_insert_content_ad($html, dh_opt('ad_content_after'), $ad); // Content is already processed by the_content; ads are trusted admin markup.
}
