<?php

if (!defined('JPEG_QUALITY')) {
    define('JPEG_QUALITY', 77);
}
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

function register_rest_field($type, $field, $args)
{
    global $rest_fields;
    $rest_fields[] = ['post_type' => $type, 'field' => $field];
    call_user_func($args['get_callback'], ['id' => 1]);
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

function get_template_directory()
{
    return __DIR__;
}

function wp_get_theme()
{
    return new WP_Theme();
}

/**
 * Class stubs
 */

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

class WP_Admin_Bar
{
    public function get_node($key)
    {
        return (object) ['id' => 'my-account', 'title' => 'Howdy, Stella'];
    }

    public function add_node($node)
    {
        echo $node['title'];
    }
}

class WP_Image_Editor
{
    public function generate_filename()
    {
        return 'file-optimized.jpg';
    }
    public function save()
    {
        return ['file' => 'file-optimized.jpg', 'path'];
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
$is_ = ['is_admin_bar_showing', 'is_admin', 'is_embed', 'is_user_logged_in', 'wp_is_json_request'];
foreach ($is_ as $func) {
    eval("function {$func}() { global \${$func}; return !!\${$func}; }");
}

/**
 * ACF Pro
 */
function get_fields()
{
    return ['a', 'b', 'c'];
}

function wp_upload_dir()
{
    return [
        'path' => '/Users/wp/fake/path',
        'url' => 'http://example.com/fake/path',
        'subdir' => '/fake',
        'basedir' => '/fake/path',
        'baseurl' => 'http://example.com/fake/path',
        'error' => '',
    ];
}

function wp_get_image_editor()
{
    global $wp_get_image_editor;
    return $wp_get_image_editor;
}

function update_attached_file()
{
}
