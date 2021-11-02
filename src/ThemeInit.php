<?php
namespace IdeasOnPurpose;

class ThemeInit
{
    /**
     * A placeholder for WP_DEBUG which can be mocked
     */
    public $is_debug = false;

    public function __construct($options = [])
    {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;

        $defaults = ['showIncludes' => true, 'enableComments' => false];
        $options = array_merge($defaults, $options);

        $this->cleanWPHead();
        $this->init();
        $this->debugFlushRewriteRules();
        /**
         * `browsersyncReload` was disabled 2019-11-06, see note in method
         */
        // $this->browsersyncReload();

        new ThemeInit\Extras\Shortcodes();

        if (function_exists('get_fields')) {
            new ThemeInit\Plugins\ACF();
        }
        new ThemeInit\Plugins\SEOFramework();

        if ($options['showIncludes'] !== false) {
            new ThemeInit\Debug\ShowIncludes();
        }

        if ($options['enableComments'] === false) {
            new ThemeInit\Extras\GlobalCommentsDisable();
        }

        /**
         * De-Howdy the WordPress Admin menu
         */
        add_filter('admin_bar_menu', [$this, 'deHowdy'], 25);

        /**
         * IOP Design Credit
         */
        add_filter('admin_footer_text', [$this, 'iopCredit'], 100);

        /**
         * Strip version from theme name when reading/writing options
         */
        add_filter('option_theme_mods_' . get_option('stylesheet'), [$this, 'readOption'], 10, 2);
        add_filter(
            'pre_update_option_theme_mods_' . get_option('stylesheet'),
            [$this, 'writeOption'],
            10,
            3
        );
        /**
         * Set JPEG_Quality
         * Add Imagick\HQ scaling
         * Compress all newly added images
         */
        new ThemeInit\Media();

        /**
         * Add Post State Labels to WP Admin
         */
        new ThemeInit\Admin\PostStates();

        // TODO: Is this too permissive? Reason not to disable unless WP_ENV == 'development'?
        \Kint::$enabled_mode = false;
        // if (defined('WP_ENV') && WP_ENV !== 'development') {
        if ($this->is_debug) {
            \Kint::$enabled_mode = true;
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
        // remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        // remove_action('wp_head', 'wp_oembed_add_discovery_links');
        // remove_action('wp_head', 'wp_oembed_add_host_js');
        // remove_action('wp_head', 'wp_shortlink_wp_head', 10);
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
        /**
         * Disable Plugin and Theme editors
         */
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }

        /**
         * Dump total execution time into the page
         */
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action(
                'shutdown',
                function () {
                    /**
                     * Need to be sure we don't dump this into a JSON response or other structured data request
                     *
                     * TODO: Check code from wp-includes/admin-bar.php for skipping AJAX, JSON, etc.
                     *       https://github.com/WordPress/WordPress/blob/42d52ce08099f9fae82a1977da0237b32c863e94/wp-includes/admin-bar.php#L1179-L1181
                     *
                     *      if ( defined( 'XMLRPC_REQUEST' ) || defined( 'DOING_AJAX' ) || defined( 'IFRAME_REQUEST' ) || wp_is_json_request() ) {
                     *
                     */
                    // if (wp_doing_ajax()) {
                    //     return;
                    // }
                    // error_log('SHUTDOWN');
                    // error_log(print_r($_SERVER, true));
                    $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
                    $msg = sprintf('Total processing time: %0.4f seconds', $time);
                    // echo "\n<!--\n\n$msg\n -->";
                    // printf('<script>console.log("%%c⏱", "font-weight: bold;", "%s");</script>', $msg);
                },
                9999
            );
        }
    }

    /**
     * Insert design credit into admin footer
     */
    public function iopCredit($default)
    {
        $credit =
            'Design and development by <a href="https://www.ideasonpurpose.com">Ideas On Purpose</a>.';
        return preg_replace('%</span>$%', " $credit</span>", $default);
    }

    /**
     * Remove "Howdy" from the WordPress admin bar
     */
    public function deHowdy($wp_admin_bar)
    {
        $account_node = $wp_admin_bar->get_node('my-account');
        $account_title = str_replace('Howdy, ', '', $account_node->title);
        $wp_admin_bar->add_node([
            'id' => 'my-account',
            'title' => $account_title,
        ]);
    }

    /**
     * Webpack/Browsersync reload on post save
     * Currently attempts to reload if WP_DEBUG is true
     * More info:
     * https://www.browsersync.io/docs/http-protocol
     * https://blogs.oracle.com/fatbloke/networking-in-virtualbox#NAT
     * https://superuser.com/a/310745/193584
     *
     * TODO: This was disabled from init() on 2019-11-06 for a few reasons:
     *       1. Since _everything_ is going through the devserver proxy,
     *          saving a post in admin will trigger a reload of the page
     *          being authored. This breaks the default workflow and causes
     *          pops up a number of "Reload site?" alerts.
     *
     *       2. Trying to reach the 10.0.2.2 Vagrant external IP from Docker
     *          was causing a blocking DNS stall for 10 seconds per request.
     *          This made the backend nearly unusable.
     */
    private function browsersyncReload()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('save_post', function () {
                $args = ['blocking' => false, 'sslverify' => false];
                // Sloppy, but there's no assurance we're actually serving over ssl
                // This hits both possible endpoints and ignores replies, one of these should work
                wp_remote_get('http://10.0.2.2:3000/__browser_sync__?method=reload', $args);
                wp_remote_get('https://10.0.2.2:3000/__browser_sync__?method=reload', $args);

                /**
                 * /webpack/reload is specific to ideasonpurpose/docker-build
                 */
                wp_remote_get('http://host.docker.internal:8080/webpack/reload', $args);
            });
        }
    }

    /**
     * Used to auto-update permalinks in development so we don't have to keep
     * the permalinks admin page open.  /wp-admin/options-permalink.php
     *
     * https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
     */
    private function debugFlushRewriteRules()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_init', function () {
                /*
                 * This code is adapted from wp-includes/admin-bar.php for skipping AJAX, JSON, etc.
                 *       https://github.com/WordPress/WordPress/blob/42d52ce08099f9fae82a1977da0237b32c863e94/wp-includes/admin-bar.php#L1179-L1181
                 */
                if (
                    defined('XMLRPC_REQUEST') ||
                    defined('DOING_AJAX') ||
                    defined('IFRAME_REQUEST') ||
                    wp_is_json_request() ||
                    is_embed() ||
                    !is_admin()
                ) {
                    return false;
                }

                $htaccess = file_exists(ABSPATH . '.htaccess');
                $htaccess_log = $htaccess ? '' : ' including .htaccess file';
                error_log(
                    "WP_DEBUG is true: Flushing rewrite rules{$htaccess_log}.\nRequest: {$_SERVER['REQUEST_URI']}"
                );

                flush_rewrite_rules(!$htaccess);
            });
        }
    }

    /**
     * Strip version numbers from theme names when reading/writing options
     *
     * Our build pipeline outputs versioned themes in directories which look
     * something like `{theme-name}-{semver}` where the semver string has dots
     * replaced with underscores (workaround for some WP oddity I've forgotten)
     * A theme directory might look something like this:
     *      `iop-theme-2_3_11`
     * Indicating the theme basename is `iop-theme` and the version is `2.3.11`
     *
     * WordPress stores some options, especially menu assignments, under a key
     * derived from the theme directory. The problem is that updating the theme
     * using snapshots means the option-name changes and the new theme can't find
     * the old settings. The solution here is to strip the version number from the
     * option name when writing, then request the version-less option on read.
     *
     * If we someday decide to use plain version-less theme folders, these filters
     * and methods can be removed.
     *
     * Both filters are called from options.php:
     * @link https://github.com/WordPress/WordPress/blob/48f35e42fc790a62d85d2a6e104550fa5a1019b9/wp-includes/option.php#L166-L179
     * @link https://github.com/WordPress/WordPress/blob/48f35e42fc790a62d85d2a6e104550fa5a1019b9/wp-includes/option.php#L373-L384
     *
     * The regex is a minor modification of the officially-sanctioned semver regex
     * Modifications include:
     *   - Pattern starts with a leading hyphen to match our naming convention
     *   - Pattern matches for `(?:\.|_)` instead of just `\.`
     *   - Drops the `g` Global and `m` multiline flags
     * @link https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
     * @link https://regex101.com/r/Ly7O1x/3/
     */
    public $semverRegex = '/-(?P<major>0|[1-9]\d*)(?:\.|_)(?P<minor>0|[1-9]\d*)(?:\.|_)(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    public function readOption($val, $opt)
    {
        $optBase = preg_replace($this->semverRegex, '', $opt);

        // if $optBase and $opt match, getting the option will nest infinitely
        return $optBase === $opt ? $val : get_option($optBase);
    }

    public function writeOption($val, $oldVal, $opt)
    {
        $optBase = preg_replace($this->semverRegex, '', $opt);
        /**
         * Because this filter is triggered _from inside_ update_option(),
         * calling update_option() again with the same inputs would cause
         * WordPress to nest infinitely and crash the server.
         *
         * We must check that $optBase and $opt are different before we can
         * update the value attached to the corrected option name.
         */
        if ($optBase !== $opt) {
            update_option($optBase, $val);
            /**
             * Returning $oldVal short-circuits the original update_option()
             * call. Since we've already updated the value under the modified
             * name, there's no need to write an extra wp_options entry which
             * will never be used.
             */
            return $oldVal;
        }
        return $val;
    }
}
