<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * Simple spy for wp_enqueue_style to collect called arguments
 */

function wp_enqueue_script($handle, $file, $deps = [], $version = false, $showInHead = false)
{
    global $enqueued;
    $enqueued[] = [
        'handle' => $handle,
        'file' => $file,
        'deps' => $deps,
        'showInHead' => $showInHead,
    ];
}

function wp_enqueue_style($handle, $file, $deps = [], $version = false)
{
    global $enqueued;
    $enqueued[] = ['handle' => $handle, 'file' => $file, 'deps' => $deps];
}

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

function add_action($hook, $func)
{
    call_user_func($func);
}

function wp_register_script($handle, $src, $deps, $ver, $in_footer)
{
    global $scripts;
    $scripts[] = ['handle' => $handle, 'src' => $src, 'ver' => $ver, 'in_footer' => $in_footer];
}

function wp_register_style($handle, $src, $deps, $ver, $media = '')
{
    global $styles;
    $scripts[] = ['handle' => $handle, 'src' => $src, 'ver' => $ver, 'media' => $media];
}

/**
 * @covers IdeasOnPurpose\ThemeInit\Manifest
 */
final class ManifestTest extends TestCase
{
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

        global $template_directory;
        $template_directory = __DIR__;
    }

    public function testLoadManifest()
    {
        $manifest = new Manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');
        $this->assertStringEndsWith('manifest/dependency-manifest.json', $manifest->manifest_file);
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

    /**
     * @covers IdeasOnPurpose\ThemeInit\Manifest::enqueue_editor_assets
     */
    public function testEnqueueAssets()
    {
        global $enqueued;

        $manifest = new Manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');

        $enqueued = [];
        $manifest->enqueue_wp_assets();
        $this->assertCount(2, $enqueued);

        $enqueued = [];
        $manifest->enqueue_admin_assets();
        $this->assertCount(2, $enqueued);

        $enqueued = [];
        $manifest->enqueue_editor_assets();
        $this->assertCount(2, $enqueued);
    }

    public function testIncludeDependenciesFromAssetFiles()
    {
        global $enqueued;

        $manifest = new Manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');

        $enqueued = [];
        $manifest->enqueue_wp_assets();
        $this->assertCount(3, $enqueued[1]['deps']);
        $this->assertContains('jquery', $enqueued[1]['deps']);
        $this->assertNotContains('react', $enqueued[1]['deps']);
        $this->assertNotContains('lodash', $enqueued[1]['deps']);

        $enqueued = [];
        $manifest->enqueue_admin_assets();
        $this->assertCount(1, $enqueued[1]['deps']);
        $this->assertNotContains('jquery', $enqueued[1]['deps']);
        $this->assertNotContains('react', $enqueued[1]['deps']);
        $this->assertNotContains('lodash', $enqueued[1]['deps']);

        $enqueued = [];
        $manifest->enqueue_editor_assets();
        $this->assertCount(6, $enqueued[1]['deps']);
        $this->assertContains('jquery', $enqueued[1]['deps']);
        $this->assertContains('react', $enqueued[1]['deps']);
        $this->assertNotContains('lodash', $enqueued[1]['deps']);
    }

    public function testErrorHandler()
    {
        global $error_log;
        $error_log = '';

        $this->ManifestErrorHandler->is_debug = false;

        $err_msg = 'Test error_message';
        $this->ManifestErrorHandler->error_handler($err_msg);

        $this->expectOutputString('');
        $this->assertEquals($err_msg, $error_log);
    }

    public function testErrorHandlerDebug()
    {
        global $error_log;
        $error_log = '';

        $this->ManifestErrorHandler->is_debug = true;

        $err_msg = 'Test error_message';
        $this->ManifestErrorHandler->error_handler($err_msg);

        $this->assertEquals($err_msg, $error_log);

        $this->expectOutputString("\n<!-- {$err_msg} --> \n\n");
    }
}
