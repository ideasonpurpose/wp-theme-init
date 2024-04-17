<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

/**
 * Remove WordPress News and Site Health Status from the WordPress Admin Dashboard
 */
class CleanDashboard
{
    public function __construct()
    {
        add_action('wp_dashboard_setup', [$this, 'clean']);
    }

    public function clean()
    {
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('health_check_status', 'dashboard', 'normal');
    }
}
