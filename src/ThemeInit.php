<?php
namespace IdeasOnPurpose;

class ThemeInit
{
    /**
     * Placeholders for mocking
     */
    public $ABSPATH;
    public $WP_DEBUG = false;

    public function __construct($options = [])
    {
        $this->ABSPATH = defined('ABSPATH') ? ABSPATH : getcwd(); // WordPress always defines this
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;

        $defaults = [
            'showIncludes' => true,
            'enableComments' => false,
            'jQueryMigrate' => true,
            'enforceMFA' => true, // Disable to enforce MFA per-user
        ];
        $options = array_merge($defaults, $options);

        /**
         * Global core defaults (revisions, auto-updates, head cleanup)
         */
        new ThemeInit\Core();

        /**
         * Admin core defaults (menu, footer, block editor)
         */
        new ThemeInit\Admin\Core();

        /**
         * Sets JPEG_QUALITY
         * Add Imagick\HQ scaling
         * Compress all newly added images
         */
        new ThemeInit\Media();

        /**
         * Add Post State Labels to WP Admin
         *  - Includes "404 Page" label
         */
        new ThemeInit\Admin\PostStates();

        /**
         * Add the Template Audit column and wp-admin page
         */
        new ThemeInit\Admin\TemplateAudit();

        /**
         * Attempts to set the DISALLOW_FILE_EDIT constant to true (disabling the Theme File Editor)
         * or displays a notice when the values is explicitly set to false.
         */
        new ThemeInit\Admin\DisallowFileEdit();

        /**
         * Log time of last_login for all users
         */
        new ThemeInit\Admin\LastLogin();

        /**
         * Clear stale wordpress_logged_in cookies
         */
        new ThemeInit\Admin\LoginCookieCleaner();

        /**
         * Add Metabox Reset buttons to Admin User Profiles
         */
        new ThemeInit\Admin\ResetMetaboxes();

        /**
         * Clean up the wp-admin dashboard
         */
        new ThemeInit\Admin\CleanDashboard();

        /**
         * Common plugin tweaks
         */
        new ThemeInit\Plugins\ACF();
        new ThemeInit\Plugins\EnableMediaReplace();
        new ThemeInit\Plugins\SEOFramework();
        new ThemeInit\Plugins\TwoFactor(!!$options['enforceMFA']);

        if ($options['showIncludes'] !== false) {
            new ThemeInit\Debug\ShowIncludes();
        }

        if ($options['enableComments'] === false) {
            new ThemeInit\Extras\GlobalCommentsDisable();
        }

        /**
         * TODO: EXPERIMENTAL
         * Provide a switch to remove jquery-migrate
         */
        if ($options['jQueryMigrate'] === false) {
            new ThemeInit\Extras\RemoveJQueryMigrate();
        }
        new ThemeInit\Extras\Shortcodes();

        // TODO: Is this too permissive? Reason not to disable unless WP_ENV == 'development'?
        if (class_exists('Kint')) {
            /** @disregard P1014 "undefined property '$enabled_mode'" **/
            \Kint::$enabled_mode = false;
            if ($this->WP_DEBUG) {
                /** @disregard P1014 "undefined property '$enabled_mode'" **/
                \Kint::$enabled_mode = true;
            }
        }
        // @codeCoverageIgnoreEnd

        /**
         * Load IOP common i18n text domain 'iopwp'
         *
         * TODO: Namespace collision?
         */
        // new WP\I18n();
    }

    /**
     * Should be called as late as possible, either shutdown or something right before shutdown
     * need to check that it's not breaking JSON or other API-ish responses by appending
     * a blob of arbitrary text to the content.
     *
     * @codeCoverageIgnore
     *
     */
    public function totalExecutionTime()
    {
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

        // $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        // $msg = sprintf('Total processing time: %0.4f seconds', $time);
        // echo "\n<!--\n\n$msg\n -->";
        // printf('<script>console.log("%%c⏱", "font-weight: bold;", "%s");</script>', $msg);
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
     *
     * @codeCoverageIgnore
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
}
