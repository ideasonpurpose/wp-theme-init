<?php

namespace ideasonpurpose;

use ideasonpurpose\Extras;

class ThemeInit
{
    public function __construct()
    {
        $this->cleanWPHead();
        $this->misc();
        $this->browsersyncReload();
        new Extras\ACF();
    }

    /**
     * Remove some WP Head garbage
     * Many thanks to Soil: https://roots.io/plugins/soil/
     */
    private function cleanWPHead()
    {
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }

    /**
     * Miscellaneous stuff
     */
    private function misc()
    {
        // Hide author's name from SEO Framework block
        add_filter('sybre_waaijer_<3', '__return_false');

        // Move SEO Framework metabox below all custom fields
        add_filter('the_seo_framework_metabox_priority', function () {
            return 'low';
        });
    }

    /**
     * Browsersync reload on post save
     * Currently attempts to reload if WP_DEBUG is true
     * More info:
     * https://www.browsersync.io/docs/http-protocol
     * https://blogs.oracle.com/fatbloke/networking-in-virtualbox#NAT
     * https://superuser.com/a/310745/193584
     */
    private function browsersyncReload()
    {
        if (WP_DEBUG) {
            add_action('save_post', function () {
                $protocol = (is_ssl()) ? 'https' : 'http';
                $args = ['blocking' => false, 'sslverify' => false];
                wp_remote_get("$protocol://10.0.2.2:3000/__browser_sync__?method=reload", $args);
            });
        }
    }
}
