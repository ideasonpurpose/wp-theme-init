<?php
namespace ideasonpurpose\ThemeInit;

use ideasonpurpose\ThemeInit\Logger;

class Manifest
{
    public $register_scripts = [];

    public $assets = [
        'wp' => [],
        'admin' => [],
        'editor' => []
    ];

    // TODO: This part sucks, there's got to be a better way of specifying dependencies
    public $deps = [
        'editor' => ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'],
        'assets' => ['jquery']
    ];

    /**
     * The manifest file is expected to live here:
     *      get_template_directory() . '/dist/manifest.json'
     */
    public function __construct($manifest_file = null)
    {
        $this->log = new Logger('manifest');

        $manifest_file = realpath(
            is_null($manifest_file) ? get_template_directory() . '/dist/manifest.json' : $manifest_file
        );

        if (!$manifest_file) {
            throw new \Exception('File not found: manifest.json');
        }

        $this->manifest = json_decode(file_get_contents($manifest_file), true);

        if (!$this->manifest) {
            throw new \Exception('Unable to decode manifest.json');
        }

        $this->sort_manifest();

        $this->log->info($this->assets, false);
        $this->log->info($this->deps, false);

        $assetCount = 0;
        foreach ($this->assets as $set) {
            $assetCount += count($set);
        }

        // Make sure the manifest isn't empty
        if ($assetCount < 1) {
            throw new \Exception('No scripts or styles found in manifest.json, nothing to load');
        }

        add_action('init', [$this, 'init_register_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_wp_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
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
        foreach ($this->manifest as $src => $file) {
            ['extension' => $ext, 'basename' => $basename] = pathinfo($src);

            /**
             * Skip everything except js and css assets
             */
            if (!in_array($ext, ['js', 'css'])) {
                continue;
            }

            $assetHandle = sanitize_title(wp_get_theme()->get('Name') . "-$basename");

            $showInHead = stripos($src, 'head') === 0 || stripos($src, 'admin-head') === 0;

            if (stripos($src, 'vendor') === 0) {
                // error_log('matched vendor');
                $this->register_scripts[$assetHandle] = $file;
                $this->deps['assets'][] = $assetHandle;
            } elseif (stripos($src, 'admin') === 0) {
                // error_log('matched admin');
                $this->assets['admin'][$assetHandle] = ["file" => $file, 'showInHead' => $showInHead, "ext" => $ext];
            } elseif (stripos($src, 'editor') === 0) {
                // error_log('matched editor');
                $this->assets['editor'][$assetHandle] = ["file" => $file, 'showInHead' => $showInHead, "ext" => $ext];
            } else {
                // error_log('no match, general stuff');
                $this->assets['wp'][$assetHandle] = ["file" => $file, 'showInHead' => $showInHead, "ext" => $ext];
            }
        }
    }

    /**
     * Register assets from the init hook
     */
    public function init_register_scripts()
    {
        foreach ($this->register_scripts as $handle => $file) {
            /**
             * Filter this handle or dependencies will recurse into oblivion
             */
            $cleanDeps = array_filter($this->deps['assets'], function ($h) use ($handle) {
                return $h !== $handle;
            });
            wp_register_script($handle, $file, $cleanDeps);
        }
    }

    /**
     * Enqueue general assets
     */
    public function enqueue_wp_assets()
    {
        $this->enqueue_webpack_assets($this->assets['wp'], $this->deps['assets']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets()
    {
        $this->enqueue_webpack_assets($this->assets['admin'], $this->deps['assets']);
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets()
    {
        $this->enqueue_webpack_assets($this->assets['editor'], $this->deps['editor']);
    }

    /**
     * Actual asset enqueuing function, handles subsets from above functions
     * Separates scripts and styles as well as appears-in-head
     */
    public function enqueue_webpack_assets($assets, $deps = [])
    {
        foreach ($assets as $handle => $asset) {
            if (strtolower($asset['ext']) === 'js') {
                wp_enqueue_script($handle, $asset['file'], $deps, null, !$asset['showInHead']);
                // error_log("JS: enqueued $handle as $asset[file]");
            }
            if (strtolower($asset['ext']) === 'css') {
                wp_enqueue_style($handle, $asset['file'], [], null);
                // error_log("CSS: enqueued $handle as $asset[file]");
            }
        }
    }
}
