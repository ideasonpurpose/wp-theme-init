<?php

namespace ideasonpurpose\ThemeInit\Extras;

class RemoveJQueryMigrate
{
    public function __construct()
    {
        add_action('wp_default_scripts', [$this, 'deRegister']);
    }

    public function deRegister($wp_scripts)
    {
        if (!is_admin() && array_key_exists('jquery', $wp_scripts->registered)) {
            $newDeps = array_diff($wp_scripts->registered['jquery']->deps, ['jquery-migrate']);
            $wp_scripts->registered['jquery']->deps = $newDeps;
        }
    }
}
