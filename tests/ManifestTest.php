<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

/**
 * @covers IdeasOnPurpose\ThemeInit\Manifest
 */
final class ManifestTest extends TestCase
{
    public $Manifest;
    public $ManifestErrorHandler;

    protected function setUp(): void
    {
        /** @var \IdeasOnPurpose\ThemeInit $this->Manifest */
        $this->Manifest = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Manifest')
            ->disableOriginalConstructor()
            ->onlyMethods(['error_handler'])
            ->getMock();

        /** @var \IdeasOnPurpose\ThemeInit $this->Manifest */
        $this->ManifestErrorHandler = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Manifest')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        global $theme_root;
        $theme_root = __DIR__ . '/wp-content/themes';
    }

    public function testLoadManifest()
    {
        // $manifest = new Manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');
        $this->Manifest->ABSPATH = __DIR__;
        $this->Manifest->load_manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');

        $this->assertStringEndsWith(
            'manifest/dependency-manifest.json',
            $this->Manifest->manifest_file
        );
    }

    public function testLoadManifestMissing()
    {
        $this->Manifest->expects($this->exactly(2))->method('error_handler');
        $this->Manifest->__construct();
        $this->Manifest->__construct('no-file.json');
    }

    public function testLoadManifestParseError()
    {
        $this->Manifest->expects($this->once())->method('error_handler');
        $this->Manifest->__construct(__DIR__ . '/Fixtures/manifest/no-parse.txt');

        $this->expectException('Exception');
        $this->Manifest->load_manifest(__DIR__ . '/Fixtures/manifest/empty.json');
    }

    public function testEnqueueAssets()
    {
        global $enqueued;
        global $is_admin;

        $is_admin = false;

        $this->Manifest->ABSPATH = __DIR__;
        $this->Manifest->load_manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');

        $enqueued = [];
        $this->Manifest->enqueue_wp_assets();
        $this->assertCount(2, $enqueued);

        $enqueued = [];
        $this->Manifest->enqueue_admin_assets();
        $this->assertCount(2, $enqueued);

        $enqueued = [];
        $this->Manifest->enqueue_editor_scripts();
        $this->assertCount(1, $enqueued);

        $enqueued = [];
        $this->Manifest->enqueue_editor_styles();
        $this->assertCount(0, $enqueued);

        $enqueued = [];
        $is_admin = true;
        $this->Manifest->enqueue_editor_styles();
        $this->assertCount(1, $enqueued);
    }

    public function testIncludeDependenciesFromAssetFiles()
    {
        global $enqueued;
        global $is_admin;

        $this->Manifest->ABSPATH = __DIR__;
        $this->Manifest->load_manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');

        $enqueued = [];
        $this->Manifest->enqueue_wp_assets();
        $this->assertCount(3, $enqueued[1]['deps']);
        $this->assertContains('jquery', $enqueued[1]['deps']);
        $this->assertNotContains('react', $enqueued[1]['deps']);
        $this->assertNotContains('lodash', $enqueued[1]['deps']);

        $enqueued = [];
        $this->Manifest->enqueue_admin_assets();
        $this->assertCount(1, $enqueued[1]['deps']);
        $this->assertNotContains('jquery', $enqueued[1]['deps']);
        $this->assertNotContains('react', $enqueued[1]['deps']);
        $this->assertNotContains('lodash', $enqueued[1]['deps']);

        // $enqueued = [];
        // $is_admin = false;
        // $this->Manifest->enqueue_editor_scripts();
        // d($enqueued);
        // $this->assertEmpty($enqueued);

        $enqueued = [];
        // $is_admin = true;
        $this->Manifest->enqueue_editor_scripts();
        $this->assertCount(6, $enqueued[0]['deps']);
        $this->assertContains('jquery', $enqueued[0]['deps']);
        $this->assertContains('react', $enqueued[0]['deps']);
        $this->assertNotContains('lodash', $enqueued[0]['deps']);

        $enqueued = [];
        $is_admin = false;
        $this->Manifest->enqueue_editor_styles();
        $this->assertEmpty($enqueued);

        $enqueued = [];
        $is_admin = true;
        $this->Manifest->enqueue_editor_styles();
        $this->assertCount(0, $enqueued[0]['deps']);
    }

    public function testErrorHandler()
    {
        global $error_log;
        $error_log = '';

        $err_msg = 'Test error_message: no WP_DEBUG';

        $this->ManifestErrorHandler->WP_DEBUG = true;
        $this->ManifestErrorHandler->error_handler($err_msg);

        $this->assertEquals($err_msg, $error_log);
    }

    public function testErrorHandlerDebug()
    {
        global $error_log;
        global $actions;
        $actions = [];

        $error_log = '';
        $err_msg = 'Test error_message: WP_DEBUG';
        $err_regex = '/\s+<!--\s*' . $err_msg . '\s+-->\s+/';
        $this->expectOutputRegex($err_regex);

        $this->ManifestErrorHandler->WP_DEBUG = true;
        $this->ManifestErrorHandler->error_handler($err_msg);
        $this->assertEquals($err_msg, $error_log);
        $this->assertTrue(action_was_added('wp_head'));
        $added = all_added_actions();
        d($added, $actions);
    }

    public function testInitRegisterAssets()
    {
        global $styles;
        global $scripts;

        $scripts = [];
        $styles = [];
        $this->Manifest->ABSPATH = __DIR__;
        $this->Manifest->load_manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');
        $this->Manifest->init_register_assets();

        $this->assertCount(3, $scripts);
        $this->assertCount(1, $styles);
    }

    public function testScriptTypeModuleFilter()
    {
        $this->Manifest->js_handles = ['dog'];
        $actual = $this->Manifest->script_type_module('<tag>', 'dog', 'source');
        $this->assertStringStartsWith("<script type='module'", $actual);

        $actual = $this->Manifest->script_type_module('<tag>', 'cat', 'source');
        $this->assertEquals('<tag>', $actual);
    }
}
