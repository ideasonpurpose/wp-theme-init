<?php

namespace IdeasOnPurpose\ThemeInit\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\LoginCookieCleaner::class)]
final class LoginCookieCleanerTest extends TestCase
{
    public $cookie_json;

    public function setUp(): void
    {
        // Not real data, many digits have been changed...
        $this->cookie_json = <<<EOF
    {
        "wordpress_3052d45f1a37a28d600c5b3072e67262": "joe|1735575220|sCE8FOc5PEP67m5h5GIXdYdqeqQoxbkD65kIAwM3hBvy|fd50d5fec10e23a15dedb5f3fba18adad52bfc536512e866234bf13381585806",
        "wordpress_85542a65e5a7d6a0c51bb742ac55cb10": "joe|1735583532|o1LsS3ni2Fdl8eiy5n3r8aekSPXdcDOJRqCB5wCFIAn|8fe85e104c7d07f51d35e27c341547bd1b3161f6c55546415efa5a7ef114d555",
        "wordpress_714fa666680c35bb67e551b505e54dd4": "joe|1740153167|myX8wIP4ayiS8EFYMaIzb2OKUnY6876PtCNomBQlqga|a06585ebb3a53146404743e501ddf74e0ea5d41ecd7c0a335e566c4d78511667",
        "wordpress_8a0e5710af05c635556c47abb1f2675a": "joe|1740153487|c02cNHTSyp6tGAbUQDcqmPJf1yR6DEWE3GOBBdJ5HLp|5b1dabee0dedba75a6e541ee81281d485e45ddd161b062051f3b50a7d0847a66",
        "wordpress_658081dc3d5356e8e0f0a41413a5fa3d": "joe|1740153518|ULLnzHhKz53Hxsq1AUraN1uOBXOgkD2iDm5yLh4Ng3L|b5c876ccc401e1cbf4ff54a4c0516a724188565c41effb003d6c4585574d4751",
        "_ga": "GA1.1.571454015.1731164877",
        "_ga_15C6K86ZKK": "GS1.1.1733201145.14.0.1722201156.0.0.0",
        "gf_display_empty_fields": "true",
        "wp-settings-3": "editor=html&libraryContent=browse&hidetb=1&advImgDetails=show&posts_list_mode=list&widgets_access=off&urlbutton=custom&post_dfw=off&wplink=1&capabilities_tab=pop&imgsize=thumbnail",
        "wp-settings-time-3": "1735041767",
        "cookie-notice-consent": "1738050662",
        "wp-settings-2": "libraryContent=browse&posts_list_mode=list",
        "wp-settings-time-2": "1738272285",
        "wordpress_test_cookie": "WP Cookie check",
        "wordpress_logged_in_714fa666680c35bb67e551b505e54dd4": "joe|1740153156|myX8wIP4ayiS8EFYMaIzb2OKUnY6878PtCNomBQlqga|55e50c18865fee5b61ad58c0e3fa7344e1440b468dcb85e625d38bb155efc10a",
        "wordpress_logged_in_8a0e5710af05c635556c47abb1f2675a": "joe|1740153485|c02cNHTSyp7tGAbUQDcqmPJf1yR6DEWE3GOBBdJ5HLp|b11b8e2f52571d02eb8bab153321602f1bed1ff720772f54c4556a3ea52e3c41",
        "wordpress_logged_in_658081dc3d5356e8e0f0a41413a5fa3d": "joe|1740153528|ULLnzHhKz53Hxsq1AUraN1uOBXOgkD2iDm5yLh4Ng3L|f7fc0310752ebe83b6fee214e5dd5d4b13dfc2e0b6abfcbccf4daff554831db5",
        "wp-settings-time-12": "1735581165"
    }
EOF;
    }

    public function testDontRemoveCookies_WP_DEBUG()
    {
        $_COOKIE = json_decode($this->cookie_json, true);
        $cookieCount = count($_COOKIE);

        $ref = new \ReflectionClass(LoginCookieCleaner::class);
        $LCC = $ref->newInstanceWithoutConstructor();

        $LCC->WP_DEBUG = false;
        $LCC->remove();

        $this->assertEquals($cookieCount, count($_COOKIE));
    }

    public function testRemoveCookies()
    {
        $_COOKIE = json_decode($this->cookie_json, true);
        $cookieCount = count($_COOKIE);

        // This prevents error_log from messing up the PHPUnit console
        ini_set('error_log', sys_get_temp_dir() . '/phpunit_error.log');

        $ref = new \ReflectionClass(LoginCookieCleaner::class);
        $LCC = $ref->newInstanceWithoutConstructor();

        $LCC->WP_DEBUG = true;

        $LCC->remove();

        $this->assertLessThan($cookieCount, count($_COOKIE));

        $keys = array_keys($_COOKIE);
        $pattern = '/^(wordpress|wordpress_logged_in)_[a-f0-9]{32}$/';
        foreach ($keys as $key) {
            $this->assertDoesNotMatchRegularExpression($pattern, $key);
        }
    }
}
