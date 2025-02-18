<?php

namespace ideasonpurpose\ThemeInit\Plugins;

class ACF
{
    public function __construct()
    {
        if (function_exists('get_fields')) {
            add_action('rest_api_init', [$this, 'injectACF']);
        } else {
            require_once 'acf_get_field.php';
        }

    }

    public function injectACF()
    {
        $types = get_post_types(['public' => true]);
        foreach ($types as $type) {
            register_rest_field($type, 'acf', [
                'get_callback' => function ($p) {
                    return get_fields($p['id']);
                },
            ]);
        }
    }
}
