<?php

/**
 * A bunch of placeholder WordPress functions in the global namespace
 */

/**
 * Stub a bunch of miscellaneous WordPress global functions
 */
function add_action($hook, $action, $priority = 10)
{
    global $actions;
    $actions[] = ['add' => $hook, 'action' => $action, 'priority' => $priority];
    call_user_func($action);
}

function remove_action($hook, $action, $priority = 10)
{
    global $actions;
    $actions[] = ['remove' => $hook, 'action' => $action, 'priority' => $priority];
}

function add_filter()
{
}

function remove_filter()
{
}

function shortcode_exists()
{
}

function add_shortcode($code, $function)
{
}

function get_option($name)
{
    return $name;
}

function update_option($opt, $val)
{
}

function get_post_types()
{
    global $post_types;
    return (array) $post_types;
}

function sanitize_title($title)
{
    return $title;
}

function remove_meta_box()
{
}

function remove_menu_page()
{
}

function wp_get_theme()
{
    return new WP_Theme();
}

function get_template_directory()
{
    return __DIR__;
}

class WP_Theme
{
    public function __construct($Name = 'test-theme')
    {
        $this->Name = $Name;
    }

    public function get($key)
    {
        return $this->{$key};
    }
}

/**
 * All WordPress is_{$name} test functions are mocked using the same pattern:
 * They return the value of a global with the same name, allowing them
 * to be easily toggled in tests.
 *
 * To toggle any function, set a value like this:
 *    global $is_admin;
 *    $is_admin = true;
 *
 * To add additional functions, add their names to the $is_ array
 */

$is_ = ['is_admin_bar_showing', 'is_embed', 'is_admin', 'is_user_logged_in', 'wp_is_json_request'];
foreach ($is_ as $func) {
    eval("function {$func}() { global \${$func}; return !!\${$func}; }");
}
