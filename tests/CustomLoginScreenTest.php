<?php declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen::class)]
final class CustomLoginScreenTest extends TestCase
{
    protected function setUp(): void
    {
        global $enqueued;
        // global $actions, $filters, $enqueued, $template_directory, $template_directory_uri;
        // $actions = [];
        // $filters = [];
        $enqueued = [];
        // $template_directory = '/path/to/theme';
        // $template_directory_uri = 'https://example.com/wp-content/themes/theme';
    }

    public function testHooks()
    {
        new CustomLoginScreen();

        $this->assertContains(['login_enqueue_scripts', 'load_styles'], all_added_actions());
        $this->assertContains(['login_message', 'login_message'], all_added_filters());
        $this->assertContains(['login_footer', 'footer'], all_added_actions());
    }

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

    public function testLoginMessage()
    {
        /** @var \IdeasOnPurpose\ThemeInit\Admin $CLS */
        $CLS = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $test_message = 'Test message ';
        $fake_logo = fn() => '<img src="logo1.svg" />';

        $CLS->siteLogo = $fake_logo;
        $actual = $CLS->login_message($test_message);

        $this->assertStringContainsString($test_message, $actual);
        $this->assertStringContainsString('logo1.svg', $actual);

        $CLS->siteLogo = '<img src="logo2.svg" />';
        $actual = $CLS->login_message($test_message);

        $this->assertStringContainsString('logo2.svg', $actual);
    }

    public function testFooter()
    {
        /** @var \IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen $CLS */
        $CLS = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Admin\CustomLoginScreen')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

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

        $this->assertStringContainsString("<div id='iop-login-footer'><p>Custom Footer</p></div>", $output);

        $CLS->footer = fn()  => '<p>Callable Footer</p>';

        ob_start();
        $CLS->footer();
        $output = ob_get_clean();


        $this->assertStringContainsString("<div id='iop-login-footer'><p>Callable Footer</p></div>", $output);
    }
}
