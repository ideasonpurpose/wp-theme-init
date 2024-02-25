<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

/**
 * @covers \IdeasOnPurpose\WP\Search
 */
final class SearchTest extends TestCase
{
    public $Search;
    public $exitMessage;

    protected function setUp(): void
    {
        global $actions;
        $actions = [];

        $this->exitMessage = 'Exited!';
        /** @var \IdeasOnPurpose\ThemeInit $this->ThemeInit */
        $this->Search = $this->getMockBuilder('\IdeasOnPurpose\WP\Search')
            ->disableOriginalConstructor()
            ->onlyMethods(['exit'])
            ->getMock();

        $this->Search
            ->expects($this->any())
            ->method('exit')
            ->willReturn($this->exitMessage);
    }

    public function testConstructor()
    {
        new Search();

        /**
         * These checks correspond to individual tests in this file
         **/
        $this->assertContains(['wp', 'redirect'], all_added_actions());
        $this->assertContains(['init', 'rewrite'], all_added_actions());
        $this->assertContains(['search_link', 'pad_dot_search'], all_added_filters());
        $this->assertContains(['posts_search', 'no_short_search'], all_added_filters());
    }

    public function testPadDotSearch()
    {
        global $wp_rewrite;
        $wp_rewrite = new \stdClass();
        $wp_rewrite->search_base = 'plain_search_string';

        $expected = '/some_prefix/plain_search_string/+.dotfile';
        $actual = $this->Search->pad_dot_search(
            '/some_prefix/plain_search_string/.dotfile',
            '.dotfile'
        );

        $this->assertEquals($expected, $actual);
    }

    public function testNoShortSearch()
    {
        global $is_admin, $search_query, $is_search, $is_main_query;

        $is_admin = false;
        $is_search = true;
        $is_main_query = true;

        $query = new \WP_Query();

        $search_query = 'some search string';
        $actual = $this->Search->no_short_search($search_query, $query);
        $this->assertStringNotContainsString('AND 0=1', $actual);

        $search_query = 'a';
        $actual = $this->Search->no_short_search($search_query, $query);
        $this->assertStringContainsString('AND 0=1', $actual);

        $search_query = '';
        $actual = $this->Search->no_short_search($search_query, $query);
        $this->assertStringContainsString('AND 0=1', $actual);
    }

    public function testRewrite()
    {
        global $rewrite_rules;
        $rewrite_rules = [];

        $Search = new Search();
        $this->assertCount(0, $rewrite_rules);

        $Search->rewrite();
        $this->assertCount(3, $rewrite_rules);
    }

    public function testRedirect()
    {
        global $options, $is_admin, $is_search, $wp_redirect;
        $is_search = true;
        $wp_redirect = [];
        $_GET['s'] = 'search+string';

        $is_admin = false;
        $options['permalink_structure'] = '';
        $actual = $this->Search->redirect();
        $this->assertNull($actual);

        $is_admin = true;
        $options['permalink_structure'] = 'permalinks';
        $actual = $this->Search->redirect();
        $this->assertNull($actual);

        $is_admin = false;
        $actual = $this->Search->redirect();
        $this->assertStringContainsString('search', $wp_redirect[0]['location']);
        $this->assertEquals($actual, $this->exitMessage);
    }

    public function testRedirectEscapesLeadingNonWordCharacters()
    {
        global $search_query, $options, $is_search, $wp_redirect;
        $wp_redirect = [];
        $is_search = true;
        $options['permalink_structure'] = 'path';
        $_GET['s'] = true;

        $search_query = ' thing';
        $query_encoded = '/' . urlencode("{$search_query}");
        $this->Search->redirect();
        $this->assertStringContainsString($query_encoded, $wp_redirect[0]['location']);
    }

    public function testRedirectSlashPassthrough()
    {
        global $search_query, $options, $is_search, $wp_redirect;
        $wp_redirect = [];
        $is_search = true;
        $options['permalink_structure'] = 'path';
        $_GET['s'] = true;

        $search_query = '/thing';
        $this->Search->redirect();
        $this->assertEmpty($wp_redirect);
    }

    public function testRedirectLeadingDotPassthrough()
    {
        global $search_query, $options, $is_search, $wp_redirect;
        $wp_redirect = [];
        $is_search = true;
        $options['permalink_structure'] = 'path';
        $_GET['s'] = true;

        $search_query = '.thing';
        $this->Search->redirect();
        $this->assertEmpty($wp_redirect);
    }

    public function testNoRedirectMatch()
    {
        global $is_admin, $options;
        $is_admin = false;
        $options['permalink_structure'] = 'path';

        $_GET = [];
        $actual = $this->Search->redirect();
        $this->assertNull($actual);
    }
}
