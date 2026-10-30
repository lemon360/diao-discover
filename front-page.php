<?php
if (!defined('ABSPATH')) { exit; }
// Respect static homepage content if the owner configured it in Settings > Reading.
if (get_option('show_on_front') === 'page') { require get_template_directory() . '/page.php'; }
else { require get_template_directory() . '/index.php'; }
