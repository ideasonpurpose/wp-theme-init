<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

/**
 * Override any DISALLOW_FILE_EDIT constant values to prevent the Theme File Editor /wp-admin/theme-editor.php
 * from appearing in the WP Admin backend.
 */
class DisallowFileEdit
{
    public function __construct()
    {
        add_filter('map_meta_cap', [$this, 'blockEdit'], 10, 2);
    }

    public function blockEdit($caps, $cap)
    {
        return $cap !== 'edit_themes' ? $caps : ['do_not_allow'];
    }
}
