<?php

declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Analytics::class)]
final class AnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        global $is_user_logged_in, $wp_get_current_user;
        $is_user_logged_in = false;
        $wp_get_current_user = null;
    }

    public function testInjectsOnWpHead(): void
    {
        global $actions;
        $actions = [];

        new Analytics(['gtag' => 'G-AAA']);

        $this->assertContains(['wp_head', 'inject'], all_added_actions());
    }

    public function testProductionOutput(): void
    {
        $html = $this->render(
            new Analytics(
                ['gtag' => 'G-PROD', 'cloudflare' => 'token-prod'],
                dev: ['gtag' => 'G-DEV', 'cloudflare' => 'token-dev'],
            ),
            debug: false,
        );

        $this->assertSame(1, substr_count($html, 'googletagmanager.com/gtag/js'));
        $this->assertStringContainsString('id=G-PROD', $html);
        $this->assertStringContainsString("gtag('js', new Date());", $html);
        $this->assertStringContainsString("gtag('config', 'G-PROD');", $html);
        $this->assertStringContainsString('token-prod', $html);
        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('G-DEV', $html);
        $this->assertStringNotContainsString('token-dev', $html);
    }

    public function testDevReplacesProduction(): void
    {
        $html = $this->render(
            new Analytics(
                ['gtag' => 'G-PROD', 'cloudflare' => 'token-prod'],
                dev: ['gtag' => 'G-DEV'],
            ),
            debug: true,
        );

        $this->assertStringContainsString("gtag('config', 'G-DEV');", $html);
        $this->assertStringNotContainsString('G-PROD', $html);
        $this->assertStringNotContainsString('token-prod', $html);
        $this->assertStringNotContainsString('cloudflareinsights', $html);
    }

    public function testEmptyDevProducesNoOutput(): void
    {
        $html = $this->render(
            new Analytics(['gtag' => 'G-PROD', 'cloudflare' => 'token-prod'], dev: []),
            debug: true,
        );

        $this->assertSame('', $html);
    }

    public function testOmittedDevProducesNoOutput(): void
    {
        $html = $this->render(
            new Analytics(['gtag' => 'G-PROD', 'cloudflare' => 'token-prod']),
            debug: true,
        );

        $this->assertSame('', $html);
    }

    public function testLoggedInSuppression(): void
    {
        global $is_user_logged_in, $wp_get_current_user;
        $is_user_logged_in = true;
        $wp_get_current_user = new \WP_User(1, 'editor');

        $html = $this->render(
            new Analytics(
                ['gtag' => ['G-AAA', 'G-BBB'], 'cloudflare' => 'token'],
                dev: ['gtag' => 'G-DEV'],
            ),
            debug: false,
        );

        $this->assertStringContainsString("User 'editor' is logged in. Analytics snippet suppressed.", $html);
        $this->assertStringNotContainsString('gtag/js', $html);
        $this->assertStringNotContainsString("gtag('config'", $html);
        $this->assertStringNotContainsString('cloudflareinsights', $html);
    }

    public function testTwoGtagIdsLoadOneScript(): void
    {
        $html = $this->render(new Analytics(['gtag' => ['G-AAA', 'G-BBB']]), debug: false);

        $this->assertSame(1, substr_count($html, '<script async src="https://www.googletagmanager.com/gtag/js'));
        $this->assertStringContainsString('id=G-AAA', $html);
        $this->assertStringNotContainsString('id=G-BBB', $html);
        $this->assertSame(2, substr_count($html, "gtag('config'"));
        $this->assertStringContainsString("gtag('config', 'G-AAA');", $html);
        $this->assertStringContainsString("gtag('config', 'G-BBB');", $html);
    }

    public function testCloudflareOutput(): void
    {
        $html = $this->render(new Analytics(['cloudflare' => 'token']), debug: false);

        $this->assertStringContainsString(
            "<script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{\"token\": \"token\"}'></script>",
            $html,
        );
        $this->assertStringNotContainsString('gtag', $html);
    }

    public function testVideoTrackingWithGtag(): void
    {
        global $filters_applied;
        $filters_applied = [];

        $html = $this->render(new Analytics(['gtag' => 'G-AAA']), debug: false);

        $this->assertStringContainsString('<!-- HTML5 video tracking -->', $html);
        $this->assertStringContainsString("gtag('event', name", $html);
        $this->assertContains(
            'iop_analytics_video_tracking',
            array_column($filters_applied, 'hook'),
        );
    }

    public function testNoVideoTrackingForCloudflareOnly(): void
    {
        $html = $this->render(new Analytics(['cloudflare' => 'token']), debug: false);

        $this->assertStringNotContainsString('video tracking', $html);
    }

    public function testNoVideoTrackingWhenLoggedIn(): void
    {
        global $is_user_logged_in, $wp_get_current_user;
        $is_user_logged_in = true;
        $wp_get_current_user = new \WP_User(1, 'editor');

        $html = $this->render(new Analytics(['gtag' => 'G-AAA']), debug: false);

        $this->assertStringNotContainsString('video tracking', $html);
    }

    private function render(Analytics $analytics, bool $debug): string
    {
        $analytics->WP_DEBUG = $debug;
        ob_start();
        $analytics->inject();
        return ob_get_clean();
    }
}
