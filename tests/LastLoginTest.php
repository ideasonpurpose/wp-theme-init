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

    public function testAddColumn()
    {
        $LastLogin = new Admin\LastLogin();
        $cols = ['cb' => 'checkbox', 'posts' => 'Posts'];
        $actual = $LastLogin->add_column($cols);

        $this->assertArrayHasKey('last-login', $actual);
        $this->assertArrayHasKey('posts', $actual);
        $this->assertArrayHasKey('cb', $actual);
    }

    public function testColumnContent()
    {
        global $user_meta, $options, $human_time_diff;
        $options['date_format'] = 'F j, Y';
        $options['time_format'] = 'H:i:s';

        $human_time_diff = '37 mins';

        $timezone = new \DateTimeZone('America/New_York');
        $dateTime = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            '2023-01-25 23:45:56',
            $timezone
        );
        $user_meta = $dateTime->format('U');

        $LastLogin = new Admin\LastLogin();
        $actual = $LastLogin->column_content('output', 'last-login', 123);

        $this->assertStringContainsString('<span', $actual);
        $this->assertStringContainsString('37 mins ago', $actual);
        $this->assertStringContainsString('2023', $actual);
        $this->assertStringContainsString('January 25, 2023 at 23:45:56', $actual);
        $this->assertStringContainsString('45', $actual);

        $expected = 'output';
        $actual = $LastLogin->column_content($expected, 'not-a-column', 123);
        $this->assertEquals('output', $actual);

        $user_meta = 0;
        $actual = $LastLogin->column_content('output', 'last-login', 123);
        $this->assertEquals('--', $actual);
    }

    public function testAdminStyles()
    {
        global $inline_styles;
        $LastLogin = new Admin\LastLogin();

        $LastLogin->admin_styles();
        $actual = end($inline_styles);
        $this->assertArrayHasKey('handle', $actual);
        $this->assertStringContainsString('.column-last-login', $actual['data']);
    }
}
