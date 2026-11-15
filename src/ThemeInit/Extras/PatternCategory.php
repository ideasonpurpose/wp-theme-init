<?php
namespace IdeasOnPurpose\ThemeInit\Extras;

use IdeasOnPurpose\ThemeInit;

/**
 * Register a Block Pattern category named for the theme and labeled with the Site Title
 */
class PatternCategory
{
    public function __construct()
    {
        add_action('init', [$this, 'register']);
    }

    public function register()
    {
        // Site Title aka blogname pre-encodes entities, decode for the editor interface
        $label = html_entity_decode(get_option('blogname'), ENT_QUOTES);
        register_block_pattern_category(ThemeInit::themeName(), ['label' => $label]);
    }
}
