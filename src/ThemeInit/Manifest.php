<?php

namespace IdeasOnPurpose\ThemeInit;

class Manifest
{
    public $register_scripts = [];
    public $register_styles = [];

    /**
     * A placeholder for WP_DEBUG which can be mocked
     */
    public $is_debug = false;

    public $assets = [
        'wp' => [],
        'admin' => [],
        'editor' => [],
    ];

    /**
     * TODO: This part sucks, there's got to be a better way of specifying baseline dependencies
     *
     * See WordPress dependency-extraction-webpack-plugin
     * @link https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dependency-extraction-webpack-plugin/
     */

    public $deps = [
        'editor' => ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'],
        'assets' => ['jquery'],
    ];

    /**
     * The manifest file is expected to live here:
     *      get_template_directory() . '/dist/manifest.json'
     *
     * NOTE: This now uses our DependencyManifestPlugin which generates a tree of entrypoints and their dependencies
     * See here: https://github.com/ideasonpurpose/docker-build/blob/master/lib/DependencyManifestPlugin.js
     */
    public function __construct($manifest_file = null)
    {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;

        $this->load_manifest($manifest_file);

        add_action('init', [$this, 'init_register_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_wp_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    /**
     * Logs errors. Also writes to page if WP_DEBUG is true
     *
     * @param  string $msg
     * @return void
     */
    public function error_handler($msg)
    {
        if ($this->is_debug) {
            add_action('wp_head', function () use ($msg) {
                echo "\n<!-- $msg --> \n\n";
            });
        }
        error_log($msg);
    }

    /**
     * Load the Manifest from a filepath
     */
    public function load_manifest($manifest_file)
    {
        $this->manifest_file = realpath(
            is_null($manifest_file)
                ? get_template_directory() . '/dist/dependency-manifest.json'
                : $manifest_file
        );

        if (!$this->manifest_file) {
            $this->error_handler("ERROR: File not found: $manifest_file");
            return;
        }

        $this->manifest = json_decode(file_get_contents($this->manifest_file), true);

        if (!$this->manifest) {
            $this->error_handler('ERROR: Unable to decode manifest (error parsing JSON)');
            return;
        }

        $this->sort_manifest();

        $assetCount = 0;
        foreach ($this->assets as $set) {
            $assetCount += count($set);
        }

        /**
         * Make sure the manifest isn't empty
         * TODO: Why does this throw an Exception instead of using error_handler?
         */
        if ($assetCount < 1) {
            throw new \Exception('No scripts or styles found in manifest, nothing to load');
        }
    }
    /**
     * Process the manifest, determine pre-requisites and dependencies
     * This should only run once per request
     *
     * populate the following:
     *      - register_scripts (vendor, dependencies, etc. Will registered in init, then called by name)
     *      - assets
     *          - wp
     *          - admin
     *          - editor
     */
    public function sort_manifest()
    {
        foreach ($this->manifest as $entry => $assets) {
            $jsDeps = [];
            $cssDeps = [];
            $themeName = get_stylesheet();

            foreach ($assets['dependencies'] as $src => $file) {
                ['extension' => $ext, 'filename' => $filename] = str_replace(
                    '~',
                    '-',
                    pathinfo($src)
                );
                $assetHandle = sanitize_title("$themeName-$filename");

                if (strtolower($ext) === 'js') {
                    $jsDeps[] = $assetHandle;
                    $this->register_scripts[$assetHandle] = $file;
                }
                if (strtolower($ext) === 'css') {
                    $cssDeps[] = $assetHandle;
                    $this->register_styles[$assetHandle] = $file;
                }
            }

            foreach ($assets['files'] as $src => $file) {
                /**
                 * Note: PHP's pathinfo returns the filename without its extension
                 *       So theme `iop` with an entrypoint file named `menu.js` would
                 *       enqueue the script as `iop-menu` (no extension). Remember
                 *       also that WordPress will append the file type when injecting
                 *       the tag, which will be `id="iop-menu-js"`
                 *
                 * @link https://www.php.net/manual/en/function.pathinfo.php
                 * @see  /wp-includes/class.wp-scripts.php#L394
                 * @link https://github.com/WordPress/WordPress/blob/70e7eec1751a6aca14b4853c10e0d961e2baddf1/wp-includes/class.wp-scripts.php#L394
                 */
                ['extension' => $ext, 'filename' => $filename] = pathinfo($src);
                $assetHandle = sanitize_title("$themeName-$filename");

                $isAdmin = stripos($entry, 'admin') === 0;
                $isEditor = stripos($entry, 'editor') === 0;
                $showInHead = stripos($src, 'head') === 0 || stripos($src, 'admin-head') === 0;

                $wpDeps = $isEditor ? $this->deps['editor'] : $this->deps['assets'];

                $asset = [
                    'handle' => $assetHandle,
                    'entry' => $entry,
                    'file' => $file,
                    'showInHead' => $showInHead,
                    'ext' => $ext,
                    'deps_js' => array_merge($wpDeps, $jsDeps),
                    'deps_css' => $cssDeps,
                    'isAdmin' => $isAdmin,
                    'isEditor' => $isEditor,
                ];

                if ($isAdmin) {
                    $this->assets['admin'][] = $asset;
                } elseif ($isEditor) {
                    $this->assets['editor'][] = $asset;
                } else {
                    $this->assets['wp'][] = $asset;
                }
            }
        }
    }

    /**
     * Register assets from the init hook
     */
    public function init_register_assets()
    {
        // TODO: Do scripts which are dependencies of head scripts appear in the head?
        foreach ($this->register_scripts as $handle => $file) {
            wp_register_script($handle, $file, [], null, true);
        }
        foreach ($this->register_styles as $handle => $file) {
            wp_register_style($handle, $file, [], null);
        }
    }

    /**
     * Enqueue general assets
     */
    public function enqueue_wp_assets()
    {
        $this->enqueue_webpack_assets($this->assets['wp']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets()
    {
        $this->enqueue_webpack_assets($this->assets['admin']);
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets()
    {
        $this->enqueue_webpack_assets($this->assets['editor']);
    }

    /**
     * Actual asset enqueuing function, handles subsets from above functions
     * Separates scripts and styles as well as appears-in-head
     */
    public function enqueue_webpack_assets($assets)
    {
        foreach ($assets as $asset) {
            if (strtolower($asset['ext']) === 'js') {
                wp_enqueue_script(
                    $asset['handle'],
                    $asset['file'],
                    $asset['deps_js'],
                    null,
                    !$asset['showInHead']
                );
            }
            if (strtolower($asset['ext']) === 'css') {
                if ($asset['isEditor']) {
                    // NOTE: Leaving this here in case Gutenberg starts working this way again
                    // add_editor_style($asset['file']);
                    wp_enqueue_style($asset['handle'], $asset['file'], $asset['deps_css'], null);
                } else {
                    wp_enqueue_style($asset['handle'], $asset['file'], $asset['deps_css'], null);
                }
            }
        }
    }
}
