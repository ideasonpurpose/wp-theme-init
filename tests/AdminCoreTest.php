<?php

namespace IdeasOnPurpose\ThemeInit\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        Test\Stubs::error_log($err);
    }
}

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\Core::class)]
final class AdminCoreTest extends TestCase
{
    protected function beforeEach(): void
    {
        global $error_log, $transients;
        $error_log = '';
        $transients = [];
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

        $adminCore = new Core();
        $adminCore->WP_DEBUG = true;
        $adminCore->ABSPATH = __DIR__ . '/Fixtures/htaccess/';

        $get_transient['flush_rewrite_log'] = false;

        $is_admin = true;
        $is_embed = false;

        $adminCore->debugFlushRewriteRules();

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
        $adminCore = new Core();
        $adminCore->WP_DEBUG = true;
        $adminCore->ABSPATH = __DIR__ . '/Fixtures/manifest/';

        $adminCore->debugFlushRewriteRules();

        $this->assertFalse(file_exists($adminCore->ABSPATH . '.htaccess'));
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
        $adminCore = new Core();
        $adminCore->WP_DEBUG = true;
        $expected = $adminCore->debugFlushRewriteRules();
        $this->assertFalse($expected);
    }
}
