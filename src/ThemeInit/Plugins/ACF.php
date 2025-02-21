<?php

namespace IdeasOnPurpose\ThemeInit\Plugins;

class ACF
{
    /**
     * Lightweight dependency-injection-ish pattern for testing
     * Skip the constructor in tests and flip the var to hit both
     * pathways of init().
     */
    public $acf_active;

    public function __construct()
    {
        $this->acf_active = function_exists('get_fields');
        $this->init();
    }

    public function init()
    {
        if ($this->acf_active) {
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
