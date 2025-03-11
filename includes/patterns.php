<?php
if (!defined('ABSPATH')) exit;

register_block_pattern(
    'proxy-auth/form-overlay',
    [
        'title'       => __('Proxy Auth Form Overlay'),
        'description' => __('A button that opens a login form in an overlay.'),
        'content'     => do_shortcode('[proxy_auth]')
    ]
);
