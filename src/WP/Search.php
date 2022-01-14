<?php

namespace IdeasOnPurpose\WP;
/**
 * Search Fixes:
 *
 * 1. Short-circuit search queries <2 characters long
 * 2. Redirect query searches to /search/
 * 3. Workaround leading-dot search failures
 *
 * Instantiate this class from functions.php:
 *
 * new IdeasOnPurpose\WP\Search();
 */
class Search
{
    public function __construct()
    {
        add_filter('posts_search', [$this, 'no_short_search'], 10, 2);
        add_action('pre_get_posts', [$this, 'redirect']);
        add_action('init', [$this, 'rewrite']);
        add_filter('search_link', [$this, 'pad_dot_search']);
    }

    /**
     * Isolate calls to `exit` so we can run PHPUnit without exiting
     * All this does is die.
     *
     * @codeCoverageIgnore
     */
    public function exit()
    {
        exit();
    }
    /**
     * Short-circuit empty or < 2 character search queries and show an empty search form
     * Modified from https://wordpress.stackexchange.com/a/216734/71132
     * Short search queries which match the additional conditions are replaced with a
     * null query: ' AND 0=1 '
     *
     * @param string $search looks something like this:
     *        " AND (((wp_posts.post_title LIKE '{3a30}fa{3a30}') OR (wp_posts.post_excerpt LIKE '{3a30}fa{3a30}') OR (wp_posts.post_content LIKE '{3a30}fa{3a30}')))  AND (wp_posts.post_password = '') "
     *
     * Called from the 'posts_search' filter
     * @link https://developer.wordpress.org/reference/hooks/posts_search/
     */
    public function no_short_search($search, \WP_Query $q)
    {
        if (
            !is_admin() &&
            strlen(get_search_query()) < 2 &&
            $q->is_search() &&
            $q->is_main_query()
        ) {
            $search = ' AND 0=1 ';
        }
        return $search;
    }

    /**
     * Redirect query-string (GET) searches /search/[query] when the site
     * is using pretty permalinks.
     *
     * Called from the 'pre_get_posts' action
     */
    public function redirect()
    {
        if (is_admin()) {
            return;
        }

        // \Kint::$mode_default = \Kint::MODE_CLI;
        // error_log(@d($searchString, "sadfdsf"));
        // \Kint::$mode_default = \Kint::MODE_RICH;

        /**
         * If the 'permalink_structure' options is empty, then the site is using
         * plain query links. Only redirect if the site is using pretty permalinks.
         */
        $permlinks = get_option('permalink_structure');

        if (!empty($permlinks) && is_search() && isset($_GET['s'])) {
            $searchString = get_search_query();
            if (preg_match('/^\W*/', $searchString)) {
                $searchString = ' ' . trim($searchString);
            }
            $searchString = urlencode($searchString);
            wp_redirect(trailingslashit(home_url("/search/{$searchString}")));
            return $this->exit();
        }
    }

    /**
     * Workaround leading-dot search failures
     *   eg. http://example.com/search/.term (403 error)
     * See @link https://developer.wordpress.org/reference/functions/get_search_link/
     *
     * Called from the 'search_link' filter
     */
    public function pad_dot_search($link)
    {
        global $wp_rewrite;
        return str_replace("{$wp_rewrite->search_base}/.", "{$wp_rewrite->search_base}/+.", $link);
    }

    /**
     * Add rewrite rule for /search/ with no query
     */
    public function rewrite()
    {
        add_rewrite_rule('search/?$', 'index.php?s=', 'top');
    }
}
