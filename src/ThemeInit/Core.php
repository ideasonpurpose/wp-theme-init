<?php
namespace IdeasOnPurpose\ThemeInit;

class Core
{
    public function __construct()
    {
        $this->disableAutoUpdates();
        $this->setRevisionsLimit();
        $this->stripThemeVersionFromOptions();
        $this->cleanWPHead();
    }

    /**
     * Disable WordPress auto-updates
     */
    private function disableAutoUpdates()
    {
        add_filter('automatic_updater_disabled', '__return_true');
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
        $optBase = preg_replace('/-(?P<major>0|[1-9]\d*)(?:\.|_)(?P<minor>0|[1-9]\d*)(?:\.|_)(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/ ', '', $opt);
        return $optBase === $opt ? $val : get_option($optBase);
    }

    public function writeOption($val, $oldVal, $opt)
    {
        $optBase = preg_replace('/-(?P<major>0|[1-9]\d*)(?:\.|_)(?P<minor>0|[1-9]\d*)(?:\.|_)(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/ ', '', $opt);
        if ($optBase !== $opt) {
            update_option($optBase, $val);
            return $oldVal;
        }
        return $val;
    }
}
