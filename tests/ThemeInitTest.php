<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use WP_Admin_Bar;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
require_once 'Fixtures/WP_Image_Editor_Imagick.php';

/**
 * @covers \IdeasOnPurpose\ThemeInit
 * @covers \IdeasOnPurpose\ThemeInit\Admin\PostStates
 * @covers \IdeasOnPurpose\ThemeInit\Debug\ShowIncludes
 * @covers \IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable
 * @covers \IdeasOnPurpose\ThemeInit\Extras\Shortcodes
 * @covers \IdeasOnPurpose\ThemeInit\Media
 * @covers \IdeasOnPurpose\ThemeInit\Plugins\ACF
 * @covers \IdeasOnPurpose\ThemeInit\Plugins\SEOFramework
 */
final class ThemeInitTest extends TestCase
{
    protected function setUp(): void
    {
    }

    /**
     * This checks to see if all the pieces are called.
     *
     * Will be used to help make sure it keeps working after refactoring the constructor into an init method
     */
    public function testInstantiation()
    {
        $ThemeInit = new ThemeInit();

        $this->assertContains(['admin_bar_menu', 'deHowdy'], all_added_filters());
        $this->assertContains(
            ['wp_head', 'adjacent_posts_rel_link_wp_head'],
            all_removed_actions()
        );
    }

    public function testReadOption()
    {
        global $options;
        /**
         * Replace PHPUnit's deprecated setMethodsExcept method with a list of all methods EXCEPT 'readOption'
         *
         * TODO: Waiting on feedback about best practices for partial mocks
         *     - https://github.com/sebastianbergmann/phpunit/issues/4652#issuecomment-957662989
         *     - https://stackoverflow.com/questions/69813091/how-to-best-preserve-some-methods-when-mocking-a-class-with-phpunit-10
         */

        $methods = get_class_methods(ThemeInit::class);
        $methods = array_diff($methods, ['readOption']);

        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();

        /**
         * Alternate method: Use Reflection API to get a copy of the readOption method
         */
        $class = new \ReflectionClass(ThemeInit::class);
        $readOption = $class->getMethod('readOption');

        /**
         * The mocked get_option function returns the argument passed to it,
         * which should be the theme name with the version stripped off
         */
        $expected = 'SomeValue';
        $options['theme-name'] = $expected;
        $opt = $ThemeInit->readOption('Other Value', 'theme-name-1_2_3');
        $this->assertEquals($opt, $expected);
        /**
         * Check the ReflectionMethod too
         */
        $opt = $readOption->invoke(
            $class->newInstanceWithoutConstructor(),
            'hello',
            'theme-name-1_2_4'
        );
        $this->assertEquals($opt, $expected);

        /**
         * Option values attached to un-versioned theme-names pass through directly
         */
        $expected = '42';
        $opt = $ThemeInit->readOption($expected, 'theme-name');
        $this->assertEquals($opt, $expected);

        $opt = $readOption->invoke(
            $class->newInstanceWithoutConstructor(),
            $expected,
            'theme-name_1_2_4'
        );
        $this->assertEquals($opt, $expected);
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

    public function testDebugFlushRewriteRules()
    {
        global $is_admin, $flush_rewrite_rules;
        $is_admin = false;

        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $ThemeInit->is_debug = true;

        $actual = $ThemeInit->debugFlushRewriteRules();
        $this->assertFalse($actual);

        $abspath = __DIR__ . '/Fixtures/htaccess/';
        if (!defined('ABSPATH')) {
            define('ABSPATH', $abspath);
        }

        $is_admin = true;
        $expected = false;
        $actual = $ThemeInit->debugFlushRewriteRules();
        $this->assertEquals($expected, $flush_rewrite_rules);
    }
}
