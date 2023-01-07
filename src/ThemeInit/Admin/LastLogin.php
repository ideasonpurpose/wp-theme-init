<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

use DateTimeZone;
use DateTimeImmutable;

/**
 * Store a timestamp of the last_login time to user_meta
 *
 * NOTE: WordPress convention uses snake_case for meta_key values
 * but kebab-case for column IDs. So this file uses both last_login
 * and last-login.
 */
class LastLogin
{
    public function __construct()
    {
        add_filter('wp_login', [$this, 'log_last_login'], 10, 2);
        add_filter('manage_users_columns', [$this, 'add_column']);
        add_filter('manage_users_custom_column', [$this, 'column_content'], 10, 3);
        add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
    }

    /**
     * Record the current time as last_login to user_meta upon successful login
     *
     * @link https://developer.wordpress.org/reference/functions/update_user_meta/
     */
    public function log_last_login(string $user_login, \WP_User $user)
    {
        update_user_meta($user->ID, 'last_login', time());
    }

    /**
     * Add the Last Login column.
     * The Users table is only visible to site administrators, so
     * no need to worry about restricting access by role.
     */
    public function add_column($cols)
    {
        $newCols = [];
        foreach ($cols as $key => $val) {
            if ($key == 'posts') {
                $newCols['last-login'] = 'Last Login';
            }
            $newCols[$key] = $val;
        }
        return $newCols;
    }

    /**
     * Content for the last-login column
     */
    public function column_content($output, $column_name, $user_id)
    {
        if ($column_name == 'last-login') {
            $last = intval(get_user_meta($user_id, 'last_login', true));
            if ($last == 0) {
                return '--';
            }

            $dateTime = new DateTimeImmutable("@{$last}");
            $timezone = new DateTimeZone(wp_timezone_string());
            $dateTime = $dateTime->setTimezone($timezone);
            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $date = sprintf(
                '%s at %s',
                $dateTime->format($date_format),
                $dateTime->format($time_format)
            );

            return sprintf('<span title="%s">%s ago</span>', $date, human_time_diff($last));
        }
        return $output;
    }

    /**
     * Set the width of the inserted last-login column to 10%
     */
    public function admin_styles()
    {
        $css = '.fixed .column-last-login { width: 10%; }';
        wp_add_inline_style('list-tables', $css);
    }
}
