<?php

namespace IdeasOnPurpose\ThemeInit\Admin;

class LoginCookieCleaner
{
    public $WP_DEBUG;

    public function __construct()
    {
        // bridge WP_DEBUG for testing
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;

        add_action('wp_login', [$this, 'remove']);
    }

    public function remove()
    {
        if (!$this->WP_DEBUG) {
            return;
        }

        $stale_cookies = 0;
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, 'wordpress_logged_in') !== false) {
                unset($_COOKIE[$key]);
                setcookie($key, '', -1, '/');
                $stale_cookies++;
            }
        }

        if ($stale_cookies > 0) {
            $msg = sprintf(
                'IdeasOnPurpose\\wp-theme-init removed %d invalid `wordpress_logged_in` cookie%s',
                $stale_cookies,
                $stale_cookies == 1 ? '' : 's'
            );
            error_log($msg);
        }
    }
}
