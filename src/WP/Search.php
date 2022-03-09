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
        add_filter('get_search_query', 'trim'); // reverse the leading dot-search fop display on pages
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

        /**
         * If the 'permalink_structure' options is empty, then the site is using
         * plain query links. Only redirect if the site is using pretty permalinks.
         */
        $permalinks = get_option('permalink_structure');
        if (empty($permalinks)) {
            return;
        }

        /**
         * is_search() will always be true if $_GET['s'] is set, but $_GET['s']
         * could be empty.
         * @link https://github.com/WordPress/wordpress-develop/blob/ba943e113d3b31b121f77a2d30aebe14b047c69d/src/wp-includes/class-wp-query.php#L825-L827
         */
        if (isset($_GET['s'])) {
            $searchString = get_search_query(false);
            /**
             * Fallback to native WordPress query-string search behavior
             * for these two special cases:
             *
             *  - Search strings containing slashes
             *    Normal URL processing squashes multiple slashes, and escaped
             *    slashes (%2F) are intercepted by Apache and 404'd before
             *    PHP/WordPress ever sees the request.
             *
             *  - Leading dots
             *    Any path segment starting with a dot is rejected by Apache
             *    and returns an odd 404-ish error page. This isn't the bare
             *    Apache 404, but not the PHP 404 either.
             */
            if (strpos($searchString, '/') !== false || strpos($searchString, '.') === 0) {
                return;
            }

            $searchString = urlencode(stripslashes($searchString));

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
        add_rewrite_rule(
            'search/?(.+)(?:/page/(\d+))/?$',
            'index.php?s=$matches[1]&paged=$matches[2]',
            'top'
        );
        add_rewrite_rule('search/?((?!/).+)/?$', 'index.php?s=$matches[1]', 'top');
        add_rewrite_rule('search/?$', 'index.php?s=', 'top');
    }
}
