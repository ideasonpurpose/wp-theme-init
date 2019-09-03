<?php
namespace ideasonpurpose\ThemeInit;

use ideasonpurpose\ThemeInit\Logger;

class Manifest
{
    public $assets = false;
    /**
     * The manifest file is expected to live here: get_template_directory() . '/dist/manifest.json'
     */
    public function __construct($manifest_file = null)
    {
        $this->log = new Logger('manifest');

        $manifest_file = realpath(
            is_null($manifest_file) ? get_template_directory() . '/dist/manifest.json' : $manifest_file
        );

        if (!$manifest_file) {
            return $this->log->error('File not found: manifest.json');
        }

        $this->manifest = json_decode(file_get_contents($manifest_file), true);

        if (!$this->manifest) {
            return $this->log->error('Unable to decode manifest.json');
        }

        $this->assets = array_filter(
            $this->manifest,
            function ($src) {
                ['extension' => $ext] = pathinfo($src);
                return in_array($ext, ['js', 'css']);
            },
            ARRAY_FILTER_USE_KEY
        );

        // TODO:  Make sure ithe manifest isn't empty
        if (count($this->assets) < 1) {
            return $this->log->error('No scripts or styles found in manifest.json, nothing to load');
        }

        add_action('init', [$this, 'enqueue_webpack_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_webpack_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_webpack_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_webpack_assets']);
    }

    /**
     * Enqueue scripts and styles from the Webpack Manifest file
     */
    public function enqueue_webpack_assets()
    {
        error_log(json_encode($this->assets, JSON_PRETTY_PRINT));
        error_log('current filter: ' . current_filter());

        $initHook = current_filter() === 'init';
        $adminHook = current_filter() === 'admin_enqueue_scripts';
        $editorHook = current_filter() === 'enqueue_block_editor_assets';
        $wpHook = current_filter() === 'wp_enqueue_scripts';

        
        $deps = [];
        // $hasVendorBundle = false;
        if (array_key_exists('vendor.js', $this->assets)) {
            error_log('we have a vendor!');
            error_log(print_r($deps, true));
            
            
            // $hasVendorBundle = true;
            // wp_enqueue_script('vendor-bundle', $this->assets['vendor.js']); //, [], null, !$showInHead);
            if ($initHook) {
                wp_register_script('vendor_bundle', $this->assets['vendor.js']);
            }
            array_push($deps, 'vendor_bundle');
            unset($this->assets['vendor.js']);
            error_log(print_r($deps, true));
            $this->deps = $deps;
        }
        
        error_log(print_r($this->assets, true));
        error_log(print_r($deps, true));
        foreach ($this->assets as $src => $file) {
            ['extension' => $ext, 'basename' => $base] = pathinfo($src);





            // TODO: This isn't working yet. The editor Assets are being loaded into the page and there's no vendor bundle...






            /**
             * Assets will be enqueued from the `wp_enqueue_scripts` hook unless...
             *  - 'admin' and 'admin-head' prefixed entrypoints will be enqueued from the `admin_enqueue_scripts` hook
             *  - 'editor' prefixed entrypoints will be enqueued from the `enqueue_block_editor_assets` hook
             *  Additionally, entrypoints prefixed with `head` or `admin-head` will be enqueued in the document head.
             */
            $isAdminAsset = stripos($src, 'admin') === 0;
            $isEditorAsset = stripos($src, 'editor') === 0;
            $showInHead = stripos($src, 'head') === 0 || stripos($src, 'admin-head') === 0;

            // TODO: This logic feels clumsy...
            if (($adminHook && $isAdminAsset) || ($editorHook && $isEditorAsset) || $wpHook) {
                // ($wpHook && !$isAdminAsset && !$isEditorAsset)
                $assetBaseName = sanitize_title(wp_get_theme() . "-$base");
                // TODO: Document this?
                // TODO: Move this up, merge with $deps... Vendor bundle stuff should happen after this
                error_log('hi!');
                error_log(print_r($deps, true));
                
                $preReqs = !$isEditorAsset ? ['jquery'] : ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'];
                $preReqs = array_merge($preReqs, $this->deps);
                
                error_log(print_r($preReqs, true));

                // if ($hasVendorBundle) {
                //     array_push($preReqs, 'vendor-bundle');
                // }
                if (strtolower($ext) === 'js') {
                    wp_enqueue_script($assetBaseName, $file, $preReqs, null, !$showInHead);
                    error_log(
                        "JS: enqueued $base ($assetBaseName) as $file from hook: " .
                            current_filter() .
                            " with " .
                            json_encode($preReqs)
                    );
                }
                if (strtolower($ext) === 'css') {
                    wp_enqueue_style($assetBaseName, $file, [], null);
                    error_log("CSS: enqueued $base ($assetBaseName) as $file from hook: " . current_filter() . "");
                }
            }
        }
    }

}
