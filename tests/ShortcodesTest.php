<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\ThemeInit\Extras\Shortcodes
 */
final class ShortcodesTest extends TestCase
{
    protected function setUp(): void
    {
        global $shortcodes;
        $shortcodes = [];
        $this->Shortcode = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Extras\Shortcodes')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testAddShortcodes()
    {
        global $shortcodes;

        $this->Shortcode->codes = ['stella' => 'one', 'frog' => 'two', 'orange' => 'three'];
        $this->Shortcode->addShortcodes();

        $this->assertCount(3, $shortcodes);
        $this->assertEquals(array_keys($this->Shortcode->codes), $shortcodes);
        // $this->assertContains('IdeasOnPurpose\ThemeInit\Media\Imagick\HQ', $editors);
    }

    public function testProtectEmail()
    {
        $expected = 'not email';
        $actual = $this->Shortcode->protectEmail([], $expected);
        $this->assertEquals($expected, $actual);

        $expected = '';
        $actual = $this->Shortcode->protectEmail(['user@example.com'], $expected);
        $this->assertStringContainsString('antispambot', $actual);
        $this->assertStringNotContainsString('class', $actual);

        $expected = '';
        $actual = $this->Shortcode->protectEmail(
            ['user@example.com', 'class' => 'lizard'],
            $expected
        );
        $this->assertStringContainsString('antispambot', $actual);
        $this->assertStringContainsString('lizard', $actual);

        $expected = 'content goes here';
        $actual = $this->Shortcode->protectEmail(['user@example.com'], $expected);
        $this->assertStringContainsString($expected, $actual);
    }
}
