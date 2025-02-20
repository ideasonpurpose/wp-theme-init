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
            if (preg_match('/^(wordpress_|wordpress_logged_in_)[a-f0-9]{32}$/', $key)) {
                // Remove the cookie from PHP's cookie array
                unset($_COOKIE[$key]);

                // Reset the cookie with no value and a 1 hour-ago timestamp
                setcookie($key, '', time() - 3600, '/');
                $stale_cookies++;
            }
        }

        if ($stale_cookies > 0) {
            $msg = sprintf(
                'IdeasOnPurpose\\wp-theme-init removed %d invalid `wordpress_` cookie%s',
                $stale_cookies,
                $stale_cookies == 1 ? '' : 's'
            );
            error_log($msg);
        }
    }
}
