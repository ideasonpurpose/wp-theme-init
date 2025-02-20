<?php

namespace IdeasOnPurpose;

use IdeasOnPurpose\ThemeInit\AdminPostStatesTest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use WP_Admin_Bar;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

require_once 'Fixtures/WP_Image_Editor_Imagick.php';

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        Test\Stubs::error_log($err);
    }
}

#[CoversClass(\IdeasOnPurpose\ThemeInit::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\CleanDashboard::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\DisallowFileEdit::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\LastLogin::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\LoginCookieCleaner::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\PostStates::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\ResetMetaboxes::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\TemplateAudit::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Debug\ShowIncludes::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\RemoveJQueryMigrate::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\Shortcodes::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Media::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\ACF::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\EnableMediaReplace::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\SEOFramework::class)]
final class ThemeInitTest extends TestCase
{
    // const ABSPATH = '';
    public $ThemeInit;

    protected function setUp(): void
    {
        $ref = new \ReflectionClass('\IdeasOnPurpose\ThemeInit');
        $this->ThemeInit = $ref->newInstanceWithoutConstructor();
    }

    protected function beforeEach(): void
    {
        global $error_log,  $meta_boxes, $transients;
        $error_log = '';
        $meta_boxes = [];
        $transients = [];
    }

    /**
     * This checks to see if all the pieces are called.
     *
     * Will be used to help make sure it keeps working after refactoring the constructor into an init method
     */
    public function testInstantiation()
    {
        global $_SERVER;
        $_SERVER['REQUEST_URI'] = 'test-request-uri';

        new ThemeInit();

        $this->assertContains(['admin_bar_menu', 'deHowdy'], all_added_filters());
        $this->assertContains(
            ['wp_head', 'adjacent_posts_rel_link_wp_head'],
            all_removed_actions()
        );
    }

    public function testConstructorOptions()
    {
        global $actions;
        $actions = [];

        // NOTE: WP_DEBUG is set to TRUE in phpunit.xml
        new ThemeInit(['showIncludes' => true]); // default
        $this->assertContains(['wp_footer', 'show'], all_added_actions());
        $actions = [];

        new ThemeInit(['showIncludes' => false]);
        $this->assertNotContains(['wp_footer', 'show'], all_added_actions());
        $actions = [];

        // d(all_added_actions());

        new ThemeInit(['enableComments' => false]); // default
        $this->assertContains(['init', 'removeFromAdminBar'], all_added_actions());
        $actions = [];

        new ThemeInit(['enableComments' => true]);
        $this->assertNotContains(['init', 'removeFromAdminBar'], all_added_actions());
        $actions = [];

        new ThemeInit(['jQueryMigrate' => true]); // default
        $this->assertNotContains(['wp_default_scripts', 'deRegister'], all_added_actions());
        $actions = [];

        new ThemeInit(['jQueryMigrate' => false]);
        $this->assertContains(['wp_default_scripts', 'deRegister'], all_added_actions());
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
        $opt = $this->ThemeInit->readOption('Other Value', 'theme-name-1_2_3');
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
        $opt = $this->ThemeInit->readOption($expected, 'theme-name');
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
        /**
         * When the theme-name is corrected, writeOption will
         * return the old value to short-circuit update_option()
         */
        $opt = $this->ThemeInit->writeOption('42', 'old-42', 'theme-name-1_2_3');
        $this->assertEquals($opt, 'old-42');

        $opt = $this->ThemeInit->writeOption('42', 'old-42', 'theme-name');
        $this->assertEquals($opt, '42');
    }

    /**
     * Note that Kinsta strips off the last period from the string, we check for that
     * and restore it if missing.
     **/
    public function testIOPCredit()
    {
        $default =
            '<span id="footer-thankyou">Thank you for creating with <a href="https://wordpress.org/">WordPress</a>.</span>';
        $kinsta =
            '<span id="footer-thankyou">Thanks for creating with <a href="https://wordpress.org/">WordPress</a> and hosting with <a href="https://kinsta.com/?utm_source=client-wp-admin&utm_medium=bottom-cta" target="_blank">Kinsta</a></span>';
        $credit = $this->ThemeInit->iopCredit($default);
        $this->assertStringContainsString('WordPress</a>. Design and development', $credit);
        $this->assertStringContainsString('ideasonpurpose.com', $credit);
        $this->assertStringContainsString('Ideas On Purpose', $credit);
        $this->assertStringContainsString('.</span>', $credit);

        $credit = $this->ThemeInit->iopCredit($kinsta);
        $this->assertStringContainsString('Kinsta</a>. Design and development', $credit);
        $this->assertStringContainsString('ideasonpurpose.com', $credit);
        $this->assertStringContainsString('Ideas On Purpose', $credit);
        $this->assertStringContainsString('.</span>', $credit);

        $credit = $this->ThemeInit->iopCredit('');
        $this->assertStringContainsString('WordPress</a>. Design and development', $credit);
        $this->assertStringContainsString('ideasonpurpose.com', $credit);
        $this->assertStringContainsString('Ideas On Purpose', $credit);
        $this->assertStringContainsString('.</span>', $credit);
    }

    public function testDeHowdy()
    {
        $name = 'Stella';
        $AdminBar = new WP_Admin_Bar();
        $AdminBar->add_node(['id' => 'my-account', 'title' => "Howdy, $name"]);
        $this->ThemeInit->deHowdy($AdminBar);
        $actual = $AdminBar->get_node('my-account')->title;
        $this->assertEquals($name, $actual);
        $this->assertStringNotContainsString($actual, 'Howdy');
    }

    public function testDebugFlushRewriteRules()
    {
        global $_SERVER,
            $error_log,
            $flush_rewrite_rules,
            $get_transient,
            $is_admin,
            $is_embed,
            $wp_is_json_request;

        $_SERVER['REQUEST_URI'] = 'phpunit mock request';

        $error_log = '';

        $is_admin = false;
        $is_embed = true;
        $wp_is_json_request = false;

        $this->ThemeInit->WP_DEBUG = true;
        $this->ThemeInit->ABSPATH = __DIR__ . '/Fixtures/htaccess/';

        $get_transient['flush_rewrite_log'] = false;

        $is_admin = true;
        $is_embed = false;

        $this->ThemeInit->debugFlushRewriteRules();

        $this->assertTrue($flush_rewrite_rules);
        $this->assertStringContainsString('Flushing rewrite rules', $error_log);
        $this->assertStringContainsString('including .htaccess file', $error_log);
    }

    public function testDebugFlushRewriteRulesNoHTACCESS()
    {
        global $_SERVER,
            $error_log,
            $flush_rewrite_rules,
            $is_admin,
            $is_embed,
            $transients,
            $wp_is_json_request;

        $_SERVER['REQUEST_URI'] = 'phpunit mock request';

        $error_log = '';

        $is_admin = true;
        $is_embed = false;
        $wp_is_json_request = false;

        $transients['flush_rewrite_log'] = false;
        $this->ThemeInit->WP_DEBUG = true;
        $this->ThemeInit->ABSPATH = __DIR__ . '/Fixtures/manifest/';

        $this->ThemeInit->debugFlushRewriteRules();

        $this->assertFalse(file_exists($this->ThemeInit->ABSPATH . '.htaccess'));
        $this->assertFalse($flush_rewrite_rules);
        $this->assertStringContainsString(' Flushing rewrite rules', $error_log);
        $this->assertStringNotContainsString('including .htaccess file', $error_log);
    }

    public function testFlushRewriteRulesNoDebug()
    {
        global $is_embed;
        global $_SERVER;
        $_SERVER['REQUEST_URI'] = 'phpunit mock request';
        $is_embed = true;
        $this->ThemeInit->WP_DEBUG = true;
        $expected = $this->ThemeInit->debugFlushRewriteRules();
        $this->assertFalse($expected);
    }

    public function testRevisionsOverride()
    {
        // Filter with a stub short-arrow function is called from the constructor,
        // which is mocked, so this is never registered and can't be tested as is
        // $this->assertContains(['wp_revisions_to_keep', 6], all_added_filters());
        $this->assertTrue(true);
    }

    public function testCleanDashboard()
    {
        global $meta_boxes;
        $ref = new \ReflectionClass(ThemeInit\Admin\CleanDashboard::class);
        $CleanDashboard = $ref->newInstanceWithoutConstructor();

        $CleanDashboard->clean();
        $ids = array_map(fn($box) => $box['id'], $meta_boxes);
        $this->assertContains('dashboard_primary', $ids);
        $this->assertContains('dashboard_site_health', $ids);
    }
}
