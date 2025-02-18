<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\Shortcodes::class)]
final class ShortcodesTest extends TestCase
{
    public $Shortcode;

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
    }

    public function testProtectEmail()
    {
        /**
         * NOTE: `antispambot` comes from the stub function in ideasonpurpose/wp-test-stubs:
         * @link https://github.com/ideasonpurpose/wp-test-stubs/blob/7ed5c1eac670a956ea98ca2774766563bb28e78d/src/Fixtures/stubs.php#L386-L389
         */
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

        $expected = '';
        $actual = $this->Shortcode->protectEmail(['class' => 'no-email']);
        $this->assertStringNotContainsString('class', $actual);
        $this->assertStringNotContainsString('antispambot', $actual);
        $this->assertEquals($expected, $actual);
    }
}
