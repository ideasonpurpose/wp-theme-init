<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

/**
 * Store a timestamp of the last_login time to user_meta
 */
class LastLogin
{
    public function __construct()
    {
        add_filter('wp_login', [$this, 'log_last_login'], 10, 2);
    }

    /**
     * Record the current time as last_login to user_meta upon successful login
     *
     * @link https://developer.wordpress.org/reference/functions/update_user_meta/
     */
    function log_last_login(string $user_login, \WP_User $user)
    {
        update_user_meta($user->ID, 'last_login', time());
    }
}
