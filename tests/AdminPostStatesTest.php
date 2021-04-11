<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;

require_once 'Fixtures/wp_stubs.php';

/**
 * @covers \IdeasOnPurpose\ThemeInit\Admin\PostStates
 */
final class AdminPostStatesTest extends TestCase
{
    public function setUp(): void
    {
        global $filters;

        $filters = [];
        /**
         * add_404_state is a filter which expects an array of states
         * and a $Post object, the only accessed property of Post is ID
         */

        $this->post = (object) ['ID' => 123];
    }

    /**
     * silly, but, coverage...
     */
public  function testFilterCreated() {
global $filters;
 $admin= new Admin\PostStates();
 $lastFilter = array_pop($filters);
 $this->assertArrayHasKey('add', $lastFilter, 'display_post_states');


}

    public function test404State()
    {
        global $post_meta;

        $Admin = new Admin\PostStates();

        $post_meta = '404.php';
        $states = $Admin->add_404_state([], $this->post);
        $this->assertArrayHasKey(404, $states, '404 Page');

        $post_meta = 'index.php';
        $states = $Admin->add_404_state([], $this->post);
        $this->assertArrayNotHasKey('404', $states);
    }
}
