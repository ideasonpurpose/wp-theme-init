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
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\Core::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\DisallowFileEdit::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\LastLogin::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\LoginCookieCleaner::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\PostStates::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\ResetMetaboxes::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\TemplateAudit::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Core::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Debug\ShowIncludes::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\RemoveJQueryMigrate::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\Shortcodes::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Media::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\ACF::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\EnableMediaReplace::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\SEOFramework::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\TwoFactor::class)]
final class ThemeInitTest extends TestCase
{
    // const ABSPATH = '';
    public $ThemeInit;

    protected function setUp(): void
    {
        global $error_log, $meta_boxes, $transients;
        $error_log = '';
        $meta_boxes = [];
        $transients = [];

        $ref = new \ReflectionClass('\IdeasOnPurpose\ThemeInit');
        $this->ThemeInit = $ref->newInstanceWithoutConstructor();
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
            all_removed_actions(),
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
        $adminCore = new ThemeInit\Admin\Core();
        $credit = $adminCore->iopCredit($default);
        $this->assertStringContainsString('WordPress</a>. Design and development', $credit);
        $this->assertStringContainsString('ideasonpurpose.com', $credit);
        $this->assertStringContainsString('Ideas On Purpose', $credit);
        $this->assertStringContainsString('.</span>', $credit);

        $credit = $adminCore->iopCredit($kinsta);
        $this->assertStringContainsString('Kinsta</a>. Design and development', $credit);
        $this->assertStringContainsString('ideasonpurpose.com', $credit);
        $this->assertStringContainsString('Ideas On Purpose', $credit);
        $this->assertStringContainsString('.</span>', $credit);

        $credit = $adminCore->iopCredit('');
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
        $adminCore = new ThemeInit\Admin\Core();
        $adminCore->deHowdy($AdminBar);
        $actual = $AdminBar->get_node('my-account')->title;
        $this->assertEquals($name, $actual);
        $this->assertStringNotContainsString($actual, 'Howdy');
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
