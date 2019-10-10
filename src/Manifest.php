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

    // TODO: This part sucks, there's got to be a better way of specifying baseline dependencies
    public $deps = [
        'editor' => ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'],
        'assets' => ['jquery']
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
        // $this->log = new Logger('manifest');

        $manifest_file = realpath(
            is_null($manifest_file) ? get_template_directory() . '/dist/dependency-manifest.json' : $manifest_file
        );

        if (!$manifest_file) {
            throw new \Exception("File not found: $manifest_file");
        }

        $this->manifest = json_decode(file_get_contents($manifest_file), true);

        if (!$this->manifest) {
            throw new \Exception('Unable to decode manifest (error parsing JSON)');
        }

        $this->sort_manifest();

        // $this->log->info($this->assets, false);
        // $this->log->info($this->deps, false);

        $assetCount = 0;
        foreach ($this->assets as $set) {
            $assetCount += count($set);
        }

        // Make sure the manifest isn't empty
        if ($assetCount < 1) {
            throw new \Exception('No scripts or styles found in manifest, nothing to load');
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
        // !d($this->manifest);
        foreach ($this->manifest as $entry => $assets) {
            $deps = [];

            foreach ($assets['dependencies'] as $src => $file) {
                ['extension' => $ext, 'basename' => $basename] = str_replace('~', '-', pathinfo($src));
                $assetHandle = sanitize_title(wp_get_theme()->get('Name') . "-$basename");

                $deps[] = $assetHandle;
                $this->register_scripts[$assetHandle] = $file;
            }

            foreach ($assets['files'] as $src => $file) {
                ['extension' => $ext, 'basename' => $basename] = pathinfo($src);
                $assetHandle = sanitize_title(wp_get_theme()->get('Name') . "-$basename");

                $isAdmin = stripos($entry, 'admin') === 0;
                $isEditor = stripos($entry, 'editor') === 0;
                $showInHead = stripos($src, 'head') === 0 || stripos($src, 'admin-head') === 0;

                $wpDeps = $isEditor ? $this->deps['editor'] : $this->deps['assets'];

                $asset = [
                    "entry" => $entry,
                    "file" => $file,
                    'showInHead' => $showInHead,
                    "ext" => $ext,
                    'deps' => array_merge($wpDeps, $deps),
                    'isAdmin' => $isAdmin,
                    'isEditor' => $isEditor
                ];

                if ($isAdmin) {
                    $this->assets['admin'][$assetHandle] = $asset;
                } elseif ($isEditor) {
                    $this->assets['editor'][$assetHandle] = $asset;
                } else {
                    $this->assets['wp'][$assetHandle] = $asset;
                }
            }
        }
    }

    /**
     * Register assets from the init hook
     */
    public function init_register_scripts()
    {
        foreach ($this->register_scripts as $handle => $file) {
            wp_register_script($handle, $file);
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
        foreach ($assets as $handle => $asset) {
            if (strtolower($asset['ext']) === 'js') {
                wp_enqueue_script($handle, $asset['file'], $asset['deps'], null, !$asset['showInHead']);
            }
            if (strtolower($asset['ext']) === 'css') {
                if ($asset['isEditor']) {
                    // NOTE: Leaving this here in case Gutenberg starts working this way again
                    // add_editor_style($asset['file']);
                    wp_enqueue_style($handle, $asset['file'], [], null);
                } else {
                    wp_enqueue_style($handle, $asset['file'], [], null);
                }
            }
        }
    }
}
