<?php
namespace ideasonpurpose\ThemeInit\Extras;

/**
 * Thanks to https://gist.github.com/mattclements/eab5ef656b2f946c4bfb
 *
 */
class GlobalCommentsDisable
{
    public function __construct()
    {
        add_action('init', function () {
            if (is_admin_bar_showing()) {
                remove_action(
                    'admin_bar_menu',
                    'wp_admin_bar_comments_menu',
                    60
                );
            }
        });

        add_action('admin_init', [$this, 'postTypeSupport']);

        // Remove comments page in menu
        add_action('admin_menu', function () {
            remove_menu_page('edit-comments.php');
        });

        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
    }

    public function postTypeSupport()
    {
        $types = get_post_types();
        foreach ($types as $type) {
            if (post_type_supports($type, 'comments')) {
                remove_post_type_support($type, 'comments');
                remove_post_type_support($type, 'trackbacks');
            }
        }
        // Remove comments metabox from dashboard
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }
}
