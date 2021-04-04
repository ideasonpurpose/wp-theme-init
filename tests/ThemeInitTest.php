<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use WP_Admin_Bar;

require_once 'Fixtures/wp_stubs.php';
require_once 'Fixtures/WP_Image_Editor_Imagick.php';

/**
 * @covers \IdeasOnPurpose\ThemeInit
 * @covers \IdeasOnPurpose\ThemeInit\Debug\ShowIncludes
 * @covers \IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable
 * @covers \IdeasOnPurpose\ThemeInit\Extras\Shortcodes
 * @covers \IdeasOnPurpose\ThemeInit\Media
 * @covers \IdeasOnPurpose\ThemeInit\Plugins\ACF
 * @covers \IdeasOnPurpose\ThemeInit\Plugins\SEOFramework
 */
final class ThemeInitTest extends TestCase
{
    public function testExisting()
    {
        $ThemeInit = new ThemeInit();
        $this->assertTrue($ThemeInit->is_debug);

        $ThemeInit->is_debug = false;
        $this->assertFalse($ThemeInit->is_debug);
        $this->expectOutputRegex('/console\.log.*PHP Includes/');
    }

    public function testReadOption()
    {
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->setMethodsExcept(['readOption'])
            ->getMock();

        /**
         * The mocked get_option function returns the argument passed to it,
         * which should be the theme name with the version stripped off
         */
        $opt = $ThemeInit->readOption('42', 'theme-name-1_2_3');
        $this->assertEquals($opt, 'theme-name');

        /**
         * Options without versions pass through directly
         */
        $opt = $ThemeInit->readOption('42', 'theme-name');
        $this->assertEquals($opt, '42');
    }

    public function testWriteOption()
    {
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        /**
         * When the theme-name is corrected, writeOption will
         * return the old value to short-circuit update_option()
         */
        $opt = $ThemeInit->writeOption('42', 'old-42', 'theme-name-1_2_3');
        $this->assertEquals($opt, 'old-42');

        $opt = $ThemeInit->writeOption('42', 'old-42', 'theme-name');
        $this->assertEquals($opt, '42');
    }

    public function testIOPCredit()
    {
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        $credit = $ThemeInit->iopCredit('<span>Text</span>');
        $this->assertStringContainsString('Design and development', $credit);
        $this->assertStringContainsString('ideasonpurpose.com', $credit);
        $this->assertStringContainsString('Ideas On Purpose', $credit);
    }

    public function testDeHowdy()
    {
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        $ThemeInit->deHowdy(new WP_Admin_Bar());
        $this->expectOutputRegex('/^(?!Howdy ).*/');
        // $this->assertStringNotContainsString($this->getActualOutput(), 'Howdy');
    }

    public function testInjectACF()
    {
        global $post_types, $rest_fields;
        $ACF = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Plugins\ACF')
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        $ACF->injectACF();

        $type = 'news';
        $post_types = [$type];
        $ACF->InjectACF();
        $this->assertEquals($rest_fields[0]['post_type'], $type);
    }
}
