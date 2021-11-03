<?php

namespace IdeasOnPurpose\ThemeInit;
/**
 * Search Fixes:
 *
 * 1. Short-circuit search queries <2 characters long
 * 2. Redirect query searches to /search/
 * 3. Workaround leading-dot search failures
 *
 * Instantiate this class from functions.php:
 *
 * new IdeasOnPurpose\ThemeInit\Search();
 */
class Search
{
    public function __construct()
    {
        add_filter('posts_search', [$this, 'no_short_search'], 10, 2);
        add_action('pre_get_posts', [$this, 'redirect']);
        add_action('init', [$this, 'rewrite']);
        add_filter('search_link', [$this, 'pad_dot_search'], 10, 2);
    }

    /**
     * Short-circuit empty or <3 character search queries and show an empty search form
     * Modified from https://wordpress.stackexchange.com/a/216734/71132
     *
     * Called from the 'posts_search' filter
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
        /**
         * If the 'permalink_structure' options is empty, then the site is using
         * plain query links. Only redirect if the site is using pretty permalinks.
         */
        $permlinks = get_option('permalink_structure');

        if (!empty($permlinks)) {
            if (!is_admin() && is_search() && isset($_GET['s'])) {
                $searchString = urlencode(get_search_query());
                wp_redirect(trailingslashit(home_url("/search/{$searchString}")));
                exit();
            }
        }
    }

    /**
     * Workaround leading-dot search failures
     *   eg. http://example.com/search/.term (403 error)
     * See @link https://developer.wordpress.org/reference/functions/get_search_link/
     *
     * Called from the 'search_link' filter
     */
    public function pad_dot_search($link, $search)
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
