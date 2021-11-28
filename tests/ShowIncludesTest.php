<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\ThemeInit\Debug\ShowIncludes
 */
final class ShowIncludesTest extends TestCase
{
    protected function setUp(): void
    {
        // global $shortcodes;
        // $shortcodes = [];
        $this->ShowIncludes = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Debug\ShowIncludes')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testShowIncludes()
    {
        global $template, $template_directory;

        require 'Fixtures/templates/vendor/fake-vendor.php';
        require 'Fixtures/templates/fake-template.php';

        $expected = 'theme-placeholder.php';
        $template_directory = '/app/tests/Fixtures/templates/';
        $template = "{$template_directory}{$expected}";

        $this->expectOutputRegex('/fake plain template/');
        $this->expectOutputRegex('/fake vendor template/');
        $this->expectOutputRegex('/script/');
        $this->expectOutputRegex('/console.log/');

        $actual = $this->ShowIncludes->show();
        $this->assertCount(2, $actual['all_includes']);
        $this->assertEquals($expected, $actual['template']);
        $this->assertContains('fake-template.php', $actual['theme_includes']);
        $this->assertContains('vendor/fake-vendor.php', $actual['vendor_includes']);
        $this->assertNotContains('fake-template.php', $actual['vendor_includes']);
        $this->assertNotContains('vendor/fake-vendor.php', $actual['theme_includes']);
    }
}
