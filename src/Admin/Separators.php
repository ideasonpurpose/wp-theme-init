<?php
namespace ideasonpurpose\ThemeInit\Admin;

/**
 * Simple class to add separators to the main WordPress admin left-sidebar
 * Initialize the class with a list (array or multiple arguments) of numbers
 * representing the menu index locations where separators should appear.
 * Separators will appear after the provided index.
 */
class Separators
{
    public function __construct()
    {
        $this->seps = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator(func_get_args())
        );
        add_action('admin_menu', [$this, 'addSeparators']);
    }

    public function addSeparators()
    {
        global $menu;
        foreach ($this->seps as $pos) {
            $pos = floatval($pos) + count($menu) / 1000;
            $menu["$pos"] = [
                0 => '',
                1 => 'read',
                2 => "separator-$pos",
                3 => '',
                4 => 'wp-menu-separator'
            ];
        }
        ksort($menu);
        // error_log(print_r($menu, true));
    }
}
