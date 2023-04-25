<?php declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\ThemeInit\Admin\DisallowFileEdit
 */
final class DisallowFileEditTest extends TestCase
{
    public function testDISALLOW_FILE_EDITisFalse()
    {
        $DisallowFileEdit = new Admin\DisallowFileEdit();
        $DisallowFileEdit->disallowFileEdit = false;
        $DisallowFileEdit->init();

        $this->assertTrue(action_was_added('admin_notices'));
    }

    public function testDisplayHasIcon()
    {
        $DisallowFileEdit = new Admin\DisallowFileEdit();
        $DisallowFileEdit->display();

        $this->expectOutputRegex('/notice-warning/');
        $actual = $this->output();
        $this->assertStringContainsString('svg', $actual);
    }
}
