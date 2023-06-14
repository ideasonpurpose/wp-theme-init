<?php
namespace IdeasOnPurpose\ThemeInit\Plugins;

class EnableMediaReplace
{
    public function __construct()
    {
        // Hide the upsell stuff
        add_filter('emr/upsell', '__return_false');

        // Hide the remove-background stuff
        add_filter('emr/feature/background', '__return_false');
    }
}
