<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;

require_once 'Fixtures/wp_stubs.php';
require_once 'Fixtures/WP_Image_Editor_Imagick.php';

/**
 * @covers \IdeasOnPurpose\ThemeInit
 * @covers \IdeasOnPurpose\ThemeInit\Debug\ShowIncludes
 * @covers \IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable
 * @covers \IdeasOnPurpose\ThemeInit\Extras\Shortcodes
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
            ->setMethodsExcept(['writeOption'])
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
}
