<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use WP_User;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\ThemeInit\Admin\LastLogin
 */
final class LastLoginTest extends TestCase
{
    public function setUp(): void
    {
        unset($GLOBALS['user_meta']);
    }

    public function testFilterCreated()
    {
        new Admin\LastLogin();
        $this->assertContains(['wp_login', 'log_last_login'], all_added_filters());
    }

    public function testLogLastLogin()
    {
        global $user_meta;

        $LastLogin = new Admin\LastLogin();

        $User = new WP_User(25);
        $LastLogin->log_last_login('username', $User);

        $this->assertArrayHasKey($User->ID, $user_meta);
        $this->assertArrayHasKey('meta_key', $user_meta[$User->ID][0]);
        $this->assertArrayHasKey('meta_value', $user_meta[$User->ID][0]);

        $this->assertEquals($user_meta[$User->ID][0]['meta_key'], 'last_login');
        $this->assertIsInt($user_meta[$User->ID][0]['meta_value']);
    }
}
