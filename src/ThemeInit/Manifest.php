<?php

namespace IdeasOnPurpose\ThemeInit;

class Manifest
{
    /**
     * Placeholders for mocking
     */
    public $ABSPATH;
    public $WP_DEBUG = false;

    public $register_scripts = [];
    public $register_styles = [];

    public $js_handles;

    public $assets = [
        'wp' => [],
        'admin' => [],
        'editor' => [],
    ];

    /**
     * The manifest file is expected to live here:
     *      get_template_directory() . '/dist/dependency-manifest.json'
     *
     * It is also expected that all files referenced in the manifest are either relative to the manifest file or absolute paths.
     *
     * NOTE: This now uses our DependencyManifestPlugin which generates a tree of entrypoints and their dependencies
     * See here: https://github.com/ideasonpurpose/docker-build/blob/master/lib/DependencyManifestPlugin.js
     */
    public function __construct($manifest_file = null)
    {
        $this->ABSPATH = defined('ABSPATH') ? ABSPATH : '/'; // WordPress always defines this
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;

        // NEED TO MAN UYALLY LOAD THE MANIFEST, NOT RUN THE constructor

        $this->load_manifest($manifest_file);

        add_action('init', [$this, 'init_register_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_wp_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);

        /**
         * Treat all scripts as modules
         */
        add_filter('script_loader_tag', [$this, 'script_type_module'], 10, 3);
    }

    /**
     * Logs errors. Also writes to page if WP_DEBUG is true
     *
     * @param  string $msg
     * @return void
     */
    public function error_handler($msg)
    {
        // d('debug', $this->WP_DEBUG, $msg);
        if ($this->WP_DEBUG) {
            // d('hi');
            add_action('wp_head', function () use ($msg) {
                echo "\n<!-- $msg --> \n\n";
            });
        }
        error_log($msg);
    }

    /**
     * Load the Manifest from a filepath
     */
    public $manifest_file;
    public $manifest;
    public function load_manifest($manifest_file)
    {
        $this->manifest_file = realpath(
            is_null($manifest_file)
                ? get_template_directory() . '/dist/dependency-manifest.json' // TODO: get_template_directory is theme-dependent
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
            $asset_versions = [$entry => substr(sha1_file($this->manifest_file), 0, 12)];
            $this->js_handles = [];
            $handle = basename(dirname(dirname($this->manifest_file))); // TODO: hack. do better

            foreach ($assets['dependencies'] as $src => $file) {
                ['extension' => $ext, 'filename' => $filename] = str_replace(
                    '~',
                    '-',
                    pathinfo($src)
                );
                $asset_handle = sanitize_title("{$handle}-{$filename}");

                if (strtolower($ext) === 'js') {
                    $jsDeps[] = $asset_handle;
                    $this->register_scripts[$asset_handle] = $file;
                    $this->js_handles[] = $asset_handle;
                }
                if (strtolower($ext) === 'css') {
                    $cssDeps[] = $asset_handle;
                    $this->register_styles[$asset_handle] = $file;
                }
            }

            /**
             * Note: this is dependent on the $entry value being used as the base of $files keys
             * TODO: In development, *.asset.php files are being generated for dependencies as well,
             *       but they're always empty. Those should probably be checked as well, just in case.
             */
            // d("{$entry}.php", $assets['files'], array_key_exists("{$entry}.php", $assets['files']));
            if (array_key_exists("{$entry}.php", $assets['files'])) {
                $asset_filepath = realpath($this->ABSPATH . $assets['files']["{$entry}.php"]);

                if (file_exists($asset_filepath)) {
                    $asset_php = require $asset_filepath;
                    if ($asset_php['dependencies']) {
                        $jsDeps = array_merge($jsDeps, $asset_php['dependencies']);
                    }
                    if ($asset_php['version']) {
                        $asset_versions[$entry] = $asset_php['version'];
                    }
                }
            }

            // d($this->ABSPATH, $asset_versions);

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
                $asset_handle = sanitize_title("$handle-$filename");

                $isAdmin = stripos($entry, 'admin') === 0;
                $isEditor = stripos($entry, 'editor') === 0;
                $showInHead = stripos($src, 'head') === 0 || stripos($src, 'admin-head') === 0;

                if ($ext === 'js') {
                    $this->js_handles[] = $asset_handle;
                }

                $asset = [
                    'handle' => $asset_handle,
                    'entry' => $entry,
                    'file' => $file,
                    'showInHead' => $showInHead,
                    'ext' => $ext,
                    'deps_js' => $jsDeps,
                    'deps_css' => $cssDeps,
                    'isAdmin' => $isAdmin,
                    'isEditor' => $isEditor,
                    'version' => $asset_versions[$entry],
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
        $this->js_handles = array_unique($this->js_handles);
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
                    $asset['version'],
                    !$asset['showInHead']
                );
            }
            if (strtolower($asset['ext']) === 'css') {
                if ($asset['isEditor']) {
                    // NOTE: Leaving this here in case Gutenberg starts working this way again
                    // add_editor_style($asset['file']);
                    wp_enqueue_style(
                        $asset['handle'],
                        $asset['file'],
                        $asset['deps_css'],
                        $asset['version']
                    );
                } else {
                    wp_enqueue_style(
                        $asset['handle'],
                        $asset['file'],
                        $asset['deps_css'],
                        $asset['version']
                    );
                }
            }
        }
    }

    /**
     * Add `type="module"` to script tags for JS assets in this the dependency manifest
     * @param mixed $tag
     * @param mixed $handle
     * @param mixed $src
     * @return mixed
     */
    public function script_type_module($tag, $handle, $src)
    {
        if (in_array($handle, $this->js_handles)) {
            $new_tag = sprintf(
                "<script type='module' src='%s' id='%s'></script>",
                esc_url($src),
                $handle
            );
            return $new_tag;
        }
        return $tag;
    }
}
