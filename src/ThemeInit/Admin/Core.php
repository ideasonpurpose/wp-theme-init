<?php
namespace IdeasOnPurpose\ThemeInit\Admin;

class Core
{
    /**
     * Placeholders for mocking
     */
    public $ABSPATH;
    public $WP_DEBUG = false;

    public function __construct()
    {
        $this->ABSPATH = defined('ABSPATH') ? ABSPATH : getcwd(); // WordPress always defines this
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;

        $this->deHowdyAdminBar();
        $this->iopCreditFooter();
        $this->disableRemoteBlockPatterns();
        $this->disableBlockDirectory();
        add_action('admin_init', [$this, 'debugFlushRewriteRules']);
        $this->debugFlushRewriteRules();
    }

    /**
     * De-Howdy the WordPress Admin menu
     * NOTE: Changed priority in WP v6.6.1, filter priority bumped from 25 to 9992
     * @link https://github.com/WordPress/wordpress-develop/commit/fc71dae8db2c057eab88f026b7b394ab0990ba9e
     */
    private function deHowdyAdminBar()
    {
        add_filter('admin_bar_menu', [$this, 'deHowdy'], 9992);
    }

    /**
     * IOP Design Credit
     *
     * Kinsta also applies this filter with priority 99, but they replace
     * the entire string. We need to call ours after theirs.
     */
    private function iopCreditFooter()
    {
        add_filter('admin_footer_text', [$this, 'iopCredit'], 500);
    }

    /**
     * Disable Remote Block Patterns
     * @link https://developer.wordpress.org/block-editor/reference-guides/filters/editor-filters/#block-patterns
     * TODO: Should this be in the plugin?
     */
    private function disableRemoteBlockPatterns()
    {
        add_filter('should_load_remote_block_patterns', '__return_false');
    }

    /**
     * Disable the Block Directory (suggests third-party blocks from the Block Editor)
     * @link https://developer.wordpress.org/block-editor/reference-guides/filters/editor-filters/#block-directory
     */
    private function disableBlockDirectory()
    {
        add_action('admin_init', function () {
            remove_action(
                'enqueue_block_editor_assets',
                'wp_enqueue_editor_block_directory_assets',
            );
        });
    }

    public function iopCredit($default)
    {
        if (!$default) {
            $default =
                '<span id="footer-thankyou">Thank you for creating with <a href="https://wordpress.org/">WordPress</a>.</span>';
        }
        $href = '<a href="https://www.ideasonpurpose.com">Ideas On Purpose</a>';
        $credit = sprintf(__('Design and development by %s.', 'iopwp'), $href);
        $default = preg_replace('%\.?</a>.?</span>%', '</a>.</span>', $default);
        return preg_replace('%(\.?)</span>$%', "$1 $credit</span>", $default);
    }

    public function deHowdy($wp_admin_bar)
    {
        $account_node = $wp_admin_bar->get_node('my-account');
        $account_title = str_replace('Howdy, ', '', $account_node->title);
        $wp_admin_bar->add_node([
            'id' => 'my-account',
            'title' => $account_title,
        ]);
    }

    public function debugFlushRewriteRules()
    {
        if ($this->WP_DEBUG) {
            // echo "<!-- XMLRPC_REQUEST -->" . XMLRPC_REQUEST;
            // echo "<!-- DOING_AJAX -->" . DOING_AJAX;
            // echo "<!-- IFRAME_REQUEST -->" . IFRAME_REQUEST;
            // echo "<!-- wp_is_json_request -->" . wp_is_json_request();
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
            $htaccess = file_exists($this->ABSPATH . '.htaccess');
            $htaccess_log = $htaccess ? ' including .htaccess file' : '';


            if (get_transient('flush_rewrite_log') === false && !isset($_GET['service-worker'])) {
                error_log(
                    "WP_DEBUG is true: Flushing rewrite rules{$htaccess_log}.\nRequest: {$_SERVER['REQUEST_URI']}",
                );
                set_transient('flush_rewrite_log', true, 15 * MINUTE_IN_SECONDS);
            }
            flush_rewrite_rules($htaccess);
        }
    }
}
