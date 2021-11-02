<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * Simple spy for wp_enqueue_style to collect called arguments
 */

function wp_enqueue_script($handle, $file, $deps = [], $version, $showInHead)
{
    global $enqueued;
    $enqueued[] = [
        'handle' => $handle,
        'file' => $file,
        'deps' => $deps,
        'showInHead' => $showInHead,
    ];
}

function wp_enqueue_style($handle, $file, $deps = [], $version)
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
    }

    public function loadManifest()
    {
    }

    public function testLoadManifest()
    {
        $manifest = new Manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');
        $this->assertStringEndsWith('manifest/dependency-manifest.json', $manifest->manifest_file);
    }

    public function testLoadManifestMissing()
    {
        $Manifest = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Manifest')
            ->disableOriginalConstructor()
            ->onlyMethods(['error_handler'])
            ->getMock();

        $Manifest->expects($this->exactly(2))->method('error_handler');

        $Manifest->__construct();
        $Manifest->__construct('no-file.json');
    }

    public function testLoadManifestParseError()
    {
        $Manifest = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Manifest')
            ->disableOriginalConstructor()
            ->onlyMethods(['error_handler'])
            ->getMock();

        $Manifest->expects($this->once())->method('error_handler');

        $Manifest->__construct(__DIR__ . '/Fixtures/manifest/no-parse.txt');

        $this->expectException('Exception');
        $Manifest->load_manifest(__DIR__ . '/Fixtures/manifest/empty.json');
    }

    /**
     * @covers IdeasOnPurpose\ThemeInit\Manifest::enqueue_editor_assets
     */
    public function testEnqueueAssets()
    {
        global $enqueued;

        $manifest = new Manifest(__DIR__ . '/Fixtures/manifest/dependency-manifest.json');

        // print_r($manifest->assets);
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

    public function testErrorHandler()
    {
        global $error_log;
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Manifest')
            ->disableOriginalConstructor()
            ->setMethodsExcept(['error_handler'])
            ->getMock();

        $this->expectOutputString('');
        $err_msg = 'Test errror_message';
        $ThemeInit->error_handler($err_msg);

        $this->assertEquals($err_msg, $error_log);

        $this->expectOutputString("\n<!-- {$err_msg} --> \n\n");

        $ThemeInit->is_debug = true;
        $ThemeInit->error_handler($err_msg);
    }
}
