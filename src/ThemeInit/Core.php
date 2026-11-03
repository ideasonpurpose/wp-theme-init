<?php

namespace IdeasOnPurpose\ThemeInit;

class Core
{
    public function __construct()
    {
        $this->toggleAutoUpdates();
        $this->setRevisionsLimit();
        $this->stripThemeVersionFromOptions();
        $this->cleanWPHead();
        add_action('after_setup_theme', [$this, 'enableWordPressFeatures']);
    }

    /**
     * Toggle WordPress auto-updates
     * All updates are disabled for 'production' environments.
     * Force auto-updates for core, plugins, themes, and translations in 'staging' environments.
     */
    public function toggleAutoUpdates()
    {
        if (wp_get_environment_type() === 'production') {
            add_filter('automatic_updater_disabled', '__return_true');
            return;
        }

        /**
         * Force auto-updates for themes, and translations in non-production environments.
         */
        if (wp_get_environment_type() === 'staging') {
            add_filter('auto_update_core', '__return_true');
            add_filter('auto_update_plugin', '__return_true');
            add_filter('auto_update_theme', '__return_true');
            add_filter('auto_update_translation', '__return_true');
        }

        // TODO: This alomost worked, but inverts the set every time the value changed.
        // /**
        //  * Plugins default to auto-update in non-production environments with the option to override.
        //  *
        //  * For plugins, invert the `auto_update_plugins` option values against the list of all plugins.
        //  * This causes all unset update values to be true, effectively setting the default to true. If the
        //  * option has never been set, WordPress returns false, so short-circuit with the list of all plugins.
        //  */
        // add_filter('site_option_auto_update_plugins', function ($value) {
        //     $all_plugins = array_keys(get_plugins());
        //     if ($value === false) {
        //         return $all_plugins;
        //     }
        //     $auto_update_plugins = array_filter($all_plugins, function ($plugin) use ($value) {
        //         return !in_array($plugin, $value);
        //     });
        //     return $auto_update_plugins;
        // });
    }

    /**
     * Enable assorted WordPress theme features
     *
     *
     * Enable all features from _add_default_theme_supports() so our hybrid themes
     * start from the same baseline as Block Themes.
     * Call remove_theme_support() from a later `after_setup_theme` action to disable.
     * Only includes features which can not be set from theme.json.
     *
     * @link https://developer.wordpress.org/reference/functions/_add_default_theme_supports/
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/
     * @link https://developer.wordpress.org/reference/functions/remove_theme_support/
     */
    public function enableWordPressFeatures()
    {
        /**
         * @link https://codex.wordpress.org/Post_Thumbnails
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#post-thumbnails
         * @link https://developer.wordpress.org/themes/classic-themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support('post-thumbnails');

        /**
         * @link https://make.wordpress.org/themes/2015/08/25/title-tag-support-now-required/
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
         * @link https://developer.wordpress.org/themes/classic-themes/basics/theme-functions/#title-tag
         */
        add_theme_support('title-tag');

        /**
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
         * @link https://developer.wordpress.org/themes/classic-themes/basics/theme-functions/
         * @link https://developer.wordpress.org/themes/classic-themes/basics/theme-functions/#theme-support-in-block-themes
         */
        add_theme_support('html5', [
            'caption',
            'comment-form',
            'comment-list',
            'gallery',
            'script',
            'search-form',
            'style',
        ]);

        /**
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/
         * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
         */
        add_theme_support('responsive-embeds');

        /**
         * Likely obsolete
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/
         * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#editor-styles
         */
        add_theme_support('editor-styles');

        /**
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#feed-links
         * @link https://developer.wordpress.org/advanced-administration/wordpress/feeds/
         */
        add_theme_support('automatic-feed-links');

        /**
         * @link https://developer.wordpress.org/reference/functions/add_post_type_support/#excerpt
         * @link https://developer.wordpress.org/reference/functions/post_type_supports/#more-information
         */
        add_post_type_support('page', 'excerpt');
    }

    /**
     * Override WP_POST_REVISIONS
     *
     * Default to 6 revisions
     * @link https://developer.wordpress.org/reference/functions/wp_revisions_to_keep/
     */
    private function setRevisionsLimit()
    {
        add_filter('wp_revisions_to_keep', fn() => 6);
    }

    /**
     * Strip version from theme name when reading/writing options
     */
    private function stripThemeVersionFromOptions()
    {
        $stylesheet = get_option('stylesheet');
        add_filter("option_theme_mods_{$stylesheet}", [$this, 'readOption'], 10, 2);
        add_filter("pre_update_option_theme_mods_{$stylesheet}", [$this, 'writeOption'], 10, 3);
    }

    private function cleanWPHead()
    {
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }

    public function readOption($val, $opt)
    {
        $optBase = preg_replace(
            '/-(?P<major>0|[1-9]\d*)(?:\.|_)(?P<minor>0|[1-9]\d*)(?:\.|_)(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/ ',
            '',
            $opt,
        );
        return $optBase === $opt ? $val : get_option($optBase);
    }

    public function writeOption($val, $oldVal, $opt)
    {
        $optBase = preg_replace(
            '/-(?P<major>0|[1-9]\d*)(?:\.|_)(?P<minor>0|[1-9]\d*)(?:\.|_)(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/ ',
            '',
            $opt,
        );
        if ($optBase !== $opt) {
            update_option($optBase, $val);
            return $oldVal;
        }
        return $val;
    }
}
