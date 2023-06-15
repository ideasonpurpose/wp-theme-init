<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

use IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable;

/**
 * This class adds buttons to Admin Dashboard User Profile pages. These allow users
 * to reset (purge) all metabox order and visibiliyt from the user_meta store.
 */
class ResetMetaboxes
{
    public $action;

    public function __construct()
    {
        $this->action = 'iop_metabox_reset';
        add_action('show_user_profile', [$this, 'addForm']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action("wp_ajax_{$this->action}", [$this, 'handler']);
    }

    public function enqueueScripts($hook_suffix)
    {
        $js_path = __DIR__ . '/ResetMetaboxes/handler.js';
        $theme_path = get_template_directory();
        $js_url = get_template_directory_uri() . str_replace($theme_path, '', $js_path);

        if ($hook_suffix !== 'profile.php') {
            return;
        }
        wp_enqueue_script('iop-metabox-admin-handler', $js_url, ['jquery'], false, true);
        wp_localize_script('iop-metabox-admin-handler', 'iop_metabox_config', [
            'url' => admin_url('admin-ajax.php'),
            'action' => $this->action,
            'nonce' => wp_create_nonce($this->action),
        ]);
    }

    public function addForm()
    {
        readfile(__DIR__ . '/ResetMetaboxes/admin-form.html');
    }

    public function handler()
    {
        check_ajax_referer($this->action);
        if (!current_user_can('edit_users')) {
            wp_die('Permission denied');
        }

        $uid = get_current_user_id();

        $args = [
            'public' => true,
            'exclude_from_search' => false,
        ];

        $post_types = get_post_types($args, 'object', 'and');

        /**
         * Keys are either meta-box-order_<name> for order
         * or metaboxhidden_<name> for visibility.
         * Prepend the relevant value when updating user_meta.
         */
        $meta_keys = [
            '_' => '(empty)',
            '_dashboard' => 'Dashboard',
            '_nav-menus' => 'Nav Menus',
        ];

        foreach ($post_types as $post_type) {
            $meta_keys["_{$post_type->name}"] = $post_type->label;
        }
        // TODO: Bet we just end up rolling these into one...
        $class_name = $_POST['class_name'];
        $message = '';
        $status_code = 200; // TODO: Return other status code?
        if ($class_name === 'iop-reset-metabox-order') {
            foreach ($meta_keys as $key => $label) {
                delete_user_meta($uid, "meta-box-order{$key}");
            }
            $message = 'Metabox order has been reset.';
            $status_code = 200;
        } elseif ($class_name === 'iop-reset-metabox-visibility') {
            foreach ($meta_keys as $key => $label) {
                delete_user_meta($uid, "metaboxhidden{$key}");
            }
            $message = 'Metabox visibility has been reset.';
            $status_code = 200;
        }

        $result = [
            'selector' => ".{$class_name}",
            'message' => $message,
        ];

        wp_send_json($result, $status_code);
    }
}
