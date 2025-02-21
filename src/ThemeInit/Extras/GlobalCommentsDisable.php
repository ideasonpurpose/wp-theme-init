<?php
namespace IdeasOnPurpose\ThemeInit\Extras;

/**
 * Thanks to https://gist.github.com/mattclements/eab5ef656b2f946c4bfb
 */
class GlobalCommentsDisable
{
    public function __construct()
    {
        add_action('init', [$this, 'removeFromAdminBar']);
        add_action('admin_init', [$this, 'removePostTypeSupport']);
        add_action('admin_init', [$this, 'removeFromDashboard']);
        add_action('admin_menu', [$this, 'removeCommentsMenu']);

        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
    }

    public function removeFromAdminBar()
    {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }

    public function removePostTypeSupport()
    {
        $types = get_post_types();
        foreach ($types as $type) {
            if (post_type_supports($type, 'comments')) {
                remove_post_type_support($type, 'comments');
                remove_post_type_support($type, 'trackbacks');
            }
        }
    }

    public function removeFromDashboard()
    {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    public function removeCommentsMenu()
    {
        remove_menu_page('edit-comments.php');
    }
}
