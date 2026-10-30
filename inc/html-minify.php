<?php
if (!defined('ABSPATH')) { exit; }
/** Conservative HTML whitespace minification, without editing executable/raw blocks. */
function dh_minify_html($html) {
    if (!is_string($html) || $html === '') { return $html; }
    preg_match_all('~<(/?)(svg|math)\b[^>]*>~i', $html, $nested);
    $depth = 0;
    foreach ($nested[0] as $i => $tag) {
        if ($nested[1][$i] === '/') { $depth--; }
        elseif (!preg_match('~/\s*>$~', $tag) && ++$depth > 1) { return $html; }
    }
    $protected = []; $prefix = 'DH_RAW_' . substr(hash('sha256', $html), 0, 16) . '_';
    $result = preg_replace_callback('~<(script|style|pre|textarea|code|svg|math)\b[^>]*>[\s\S]*?</\1\s*>|<!--[\s\S]*?-->~i', function ($match) use (&$protected, $prefix) {
        $key = $prefix . count($protected) . '_END'; $protected[$key] = $match[0]; return $key;
    }, $html);
    if (!is_string($result)) { return $html; }
    $block = array_fill_keys(explode(' ', 'html head body title meta link base div p section header footer main nav article aside ul ol li dl dt dd table tr th td thead tbody tfoot form fieldset legend h1 h2 h3 h4 h5 h6 blockquote figure figcaption dialog hr'), true);
    $tag = '<\s*/?\s*([a-z][a-z0-9:-]*)\b(?:[^<>"\x27]|"[^"]*"|\x27[^\x27]*\x27)*>';
    $result = preg_replace_callback('~(' . $tag . ')[\t\r\n ]+(?=<\s*/?\s*([a-z][a-z0-9:-]*)\b)~i', function ($match) use ($block) {
        return $match[1] . ((isset($block[strtolower($match[2])]) || isset($block[strtolower($match[3])])) ? '' : ' ');
    }, $result);
    return is_string($result) ? strtr($result, $protected) : $html;
}
add_action('template_redirect', function () {
    if (!dh_tool_opt('html_minify') || is_admin() || is_feed() || is_trackback() || is_robots() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) { return; }
    ob_start(function ($html, $phase) {
        if (!($phase & PHP_OUTPUT_HANDLER_FINAL) || !preg_match('~^\s*(?:<!doctype\s+html|<html\b)~i', $html)) { return $html; }
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Encoding:') === 0 || (stripos($header, 'Content-Type:') === 0 && stripos($header, 'text/html') === false)) { return $html; }
        }
        $minified = dh_minify_html($html);
        if ($minified !== $html && !headers_sent()) { header_remove('Content-Length'); }
        return $minified;
    });
}, 99);
