<?php

namespace IdeasOnPurpose\ThemeInit\Plugins;

class ACF
{
    public function __construct()
    {
        add_action('acf/init', [$this, 'injectACF']);
        // add_action('init', [$this, 'get_field_polyfill']);
    }

    /**
     * TODO: This will never work from the theme because too many hooks
     *       have already been fired. It needs to be in a plugin to work.
     */
    public function get_field_polyfill()
    {
        if (!function_exists('get_field')) {
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
