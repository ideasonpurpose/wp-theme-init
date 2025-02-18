<?php declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\DisallowFileEdit::class)]
final class DisallowFileEditTest extends TestCase
{
    public function testBlockEdit()
    {
        $DisallowFileEdit = new Admin\DisallowFileEdit();

        $cap = 'edit_themes';
        $expected = ['do_not_allow'];
        $actual = $DisallowFileEdit->blockEdit([$cap], $cap);

        $this->assertNotEquals($actual, [$cap]);
        $this->assertEquals($actual, $expected);
    }

    public function testDontBlockOthers()
    {
        $DisallowFileEdit = new Admin\DisallowFileEdit();

        $cap = 'not_edit_themes';
        $expected = [$cap];
        $actual = $DisallowFileEdit->blockEdit([$cap], $cap);

        $this->assertEquals($actual, $expected);
    }
}
