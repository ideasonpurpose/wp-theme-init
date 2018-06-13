<?php
namespace ideasonpurpose\ThemeInit\Extras;

class ACF
{
    public function __construct()
    {
        add_filter('rest_api_init', [$this, 'injectACF']);
    }

    public function injectACF($response)
    {
        $types = get_post_types(['public' => true]);
        foreach ($types as $type) {
            register_rest_field($type, 'acf', [
                'get_callback' =>
                    function ($p) {
                        return get_fields($p['id']);
                    }
            ]);
        }
    }
}
