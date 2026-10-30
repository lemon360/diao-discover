<?php
if (!defined('ABSPATH')) { exit; }
function dh_color_rgb($color) {
    $color = ltrim(sanitize_hex_color($color) ?: '#b4553d', '#');
    if (strlen($color) === 3) { $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2]; }
    return [hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2))];
}
function dh_color_luminance($color) {
    $channels = array_map(function ($value) { $value /= 255; return $value <= 0.04045 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4); }, dh_color_rgb($color));
    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}
function dh_color_contrast($first, $second) {
    $a = dh_color_luminance($first); $b = dh_color_luminance($second);
    return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
}
function dh_color_mix($color, $target, $amount) {
    $start = dh_color_rgb($color); $end = dh_color_rgb($target); $amount = max(0, min(1, $amount));
    return sprintf('#%02x%02x%02x', ...array_map(function ($a, $b) use ($amount) { return (int) round($a + ($b - $a) * $amount); }, $start, $end));
}
function dh_readable_accent($color, $backgrounds, $target) {
    for ($step = 0; $step <= 100; $step++) {
        $candidate = dh_color_mix($color, $target, $step / 100);
        $ratios = array_map(function ($background) use ($candidate) { return dh_color_contrast($candidate, $background); }, $backgrounds);
        if (min($ratios) >= 4.8) { return $candidate; }
    }
    return $target;
}
function dh_color_palette($brand) {
    $brand = sanitize_hex_color($brand) ?: '#b4553d';
    if (strtolower($brand) === '#ef603d') { $brand = '#b4553d'; }
    return [
        'brand' => $brand,
        'light' => dh_readable_accent($brand, ['#f2f3ee', '#fafbf7', '#e8ece5', '#f2e7dc', '#e7ece4'], '#303c34'),
        'dark' => dh_readable_accent(dh_color_mix($brand, '#f5dec5', 0.45), ['#232b26', '#2b352e', '#354137', '#433c30', '#303e33'], '#e0e8dd'),
    ];
}
