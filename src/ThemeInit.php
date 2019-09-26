<?php
namespace ideasonpurpose;

class ThemeInit
{
    public function __construct($options = [])
    {
        $defaults = ['showIncludes' => true, 'enableComments' => false];
        $options = array_merge($defaults, $options);

        $this->cleanWPHead();
        $this->init();
        $this->browsersyncReload();
        new ThemeInit\Extras\Shortcodes();
        new ThemeInit\Extras\ACF();
        new ThemeInit\Plugins\SEOFramework();

        if ($options['showIncludes'] !== false) {
            new ThemeInit\Debug\ShowIncludes();
        }

        if ($options['enableComments'] === false) {
            new ThemeInit\Extras\GlobalCommentsDisable();
        }

        if (WP_ENV !== 'development') {
            \Kint::$enabled_mode = false;
        }
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
    private function init()
    {
        // IOP Design Credit
        add_filter('admin_footer_text', function ($default) {
            $credit = 'Design and development by <a href="https://www.ideasonpurpose.com">Ideas On Purpose</a>.';
            return preg_replace('%</span>$%', " $credit</span>", $default);
        });

        // De-Howdy the Admin menu
        add_filter(
            'admin_bar_menu',
            function ($wp_admin_bar) {
                $account_node = $wp_admin_bar->get_node('my-account');
                $account_title = str_replace('Howdy, ', '', $account_node->title);
                $wp_admin_bar->add_node([
                    'id' => 'my-account',
                    'title' => $account_title
                ]);
            },
            25
        );

        /**
         * Dump total execution time into the page
         */
        if (WP_DEBUG) {
            add_action(
                'shutdown',
                function () {
                    /**
                     * Need to be sure we don't dump this into a JSON response or other structured data request
                     */
                    // if (wp_doing_ajax()) {
                    //     return;
                    // }
                    // error_log('SHUTDOWN');
                    // error_log(print_r($_SERVER, true));
                    $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
                    $msg = sprintf("Total processing time: %0.4f seconds", $time);
                    // echo "\n<!--\n\n$msg\n -->";
                    // printf('<script>console.log("%%c⏱", "font-weight: bold;", "%s");</script>', $msg);
                },
                9999
            );
        }
    }

    /**
     * Browsersync reload on post save
     * Currently attempts to reload if WP_DEBUG is true
     * More info:
     * https://www.browsersync.io/docs/http-protocol
     * https://blogs.oracle.com/fatbloke/networking-in-virtualbox#NAT
     * https://superuser.com/a/310745/193584
     *
     * TODO: Update for Webpack DevServer too
     */
    private function browsersyncReload()
    {
        if (WP_DEBUG) {
            add_action('save_post', function () {
                $args = ['blocking' => false, 'sslverify' => false];
                // Sloppy, but there's no assurance we're actually serving over ssl
                // This hits both possible endpoints and ignores replies, one of these should work
                wp_remote_get("http://10.0.2.2:3000/__browser_sync__?method=reload", $args);
                wp_remote_get("https://10.0.2.2:3000/__browser_sync__?method=reload", $args);
            });
        }
    }

    /**
     * Used to auto-update permalinks in development so we don't have to keep
     * the permalinks admin panel open.  /wp-admin/options-permalink.php
     *
     * https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
     */
    private function debugFlushRewriteRules()
    {
        if (WP_DEBUG) {
            add_action('shutdown', function () {
                error_log('WP_DEBUG is true, flushing rewrite rules.');
                flush_rewrite_rules();
            });
        }
    }
}
