<?php declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

// Not sure where the request for Core is coming from
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\Core::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen::class)]
final class CustomLoginScreenTest extends TestCase
{
    protected function setUp(): void
    {
        global $enqueued, $actions;
        $enqueued = [];
        $actions = [];
    }

    public function testHooks()
    {
        new CustomLoginScreen();

        $this->assertContains(['login_enqueue_scripts', 'load_styles'], all_added_actions());
        $this->assertContains(['login_message', 'login_message'], all_added_filters());
        $this->assertContains(['login_footer', 'footer'], all_added_actions());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testLoadStyles()
    {
        global $enqueued;

        /** @var \IdeasOnPurpose\ThemeInit\Admin $CLS */
        $CLS = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $CLS->load_styles();

        $this->assertCount(1, $enqueued);
        $this->assertStringContainsString('CustomLoginScreen', $enqueued[0]['src']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testLoginMessage()
    {
        /** @var \IdeasOnPurpose\ThemeInit\Admin $CLS */
        $CLS = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $test_message = 'Test message ';
        $fake_logo = fn() => '<img src="logo1.svg" />';

        $CLS->byline = null; // keep for compatibility, though not used
        // $CLS->footer = null; // keep for compatibility, though not used

        $CLS->siteLogo = $fake_logo;
        $actual = $CLS->login_message($test_message);

        $this->assertStringContainsString($test_message, $actual);
        $this->assertStringContainsString('logo1.svg', $actual);

        $CLS->siteLogo = '<img src="logo2.svg" />';
        $actual = $CLS->login_message($test_message);

        $this->assertStringContainsString('logo2.svg', $actual);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFooter()
    {
        /** @var \IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen $CLS */
        $CLS = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $CLS->footer = null; // keep for compatibility, though not used

        // Test with default footer (no custom footer set)
        ob_start();
        $CLS->footer();
        $output = ob_get_clean();

        $this->assertStringContainsString("<div id='iop-login-footer'>", $output);
        $this->assertStringContainsString('Ideas on Purpose', $output);

        // Test with custom string footer
        $CLS->footer = '<p>Custom Footer</p>';
        ob_start();
        $CLS->footer();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            "<div id='iop-login-footer'><p>Custom Footer</p></div>",
            $output,
        );

        $CLS->footer = fn() => '<p>Callable Footer</p>';

        ob_start();
        $CLS->footer();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            "<div id='iop-login-footer'><p>Callable Footer</p></div>",
            $output,
        );
    }
}
