<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

// $stub = new Text\TextCase()->

// if (!function_exists(__NAMESPACE__ . '\error_log')) {
//     function error_log($err)
//     {
//         global $error_log;
//         $error_log = $err;
//     }
// }

/**
 * @covers \IdeasOnPurpose\WP\Search
 */
final class SearchTest extends TestCase
{
    public function testCoverage()
    {
        $Search = new Search();

        $Search->rewrite();

        $this->assertTrue(true);
    }

    public function testPadDotSearch() {
        global $wp_rewrite;
        $wp_rewrite = new \stdClass();
        $wp_rewrite->search_base = 'plain_search_string';


        $Search = new Search();
        $expected = '/some_prefix/plain_search_string/+.dotfile';
        $actual = $Search->pad_dot_search('/some_prefix/plain_search_string/.dotfile', '.dotfile');

        $this->assertEquals($expected, $actual);

    }
}
