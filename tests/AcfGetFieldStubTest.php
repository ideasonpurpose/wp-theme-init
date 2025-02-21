<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();


/**
 * Run in separate process to so get_field doesn't leak in from other tests
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\ACF::class)]
#[CoversFunction('get_field')]
final class AcfGetFieldStubTest extends TestCase
{
    public function testAcfGetFieldPolyfill()
    {
        global $is_admin, $actions;
        $actions = [];
        $is_admin = true;

        $this->assertFalse(function_exists('get_field'));

        require_once 'src/ThemeInit/Plugins/acf_get_field.php';

        // Prevent error_log from messing up the PHPUnit console
        ini_set('error_log', sys_get_temp_dir() . '/phpunit_error.log');

        $this->expectOutputRegex('/get_field/');

        $this->assertTrue(function_exists('get_field'));

        $expected = 'get_field test string';

        get_field($expected, 123);
        $actions[0]['action']();

        $this->assertTrue(action_was_added('admin_notices'));
    }
}
