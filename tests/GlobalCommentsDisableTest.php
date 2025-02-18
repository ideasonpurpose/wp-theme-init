<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;
use IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable::class)]
final class GlobalCommentsDisableTest extends TestCase
{
    public $GlobalCommentsDisable;

    protected function setUp(): void
    {
        global $actions;
        $actions = [];
        $this->GlobalCommentsDisable = $this->getMockBuilder(
            '\IdeasOnPurpose\ThemeInit\Extras\GlobalCommentsDisable'
        )
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testCommentsDisable()
    {
        global $filters;

        $filters = [];
        new GlobalCommentsDisable();

        $filters_added = [];
        foreach ($filters as $filter) {
            $filters_added[] = $filter['add'];
        }

        $this->assertCount(2, $filters_added);
        $this->assertContains('comments_open', $filters_added);
        $this->assertContains('pings_open', $filters_added);
        $this->assertEquals($filters[0]['action'], '__return_false');
        $this->assertEquals($filters[1]['action'], '__return_false');
    }

    public function testRemoveFromAdminBar()
    {
        global $actions, $is_admin_bar_showing;

        $is_admin_bar_showing = false;
        $this->GlobalCommentsDisable->removeFromAdminBar();
        $this->assertEmpty($actions);

        $is_admin_bar_showing = true;
        $this->GlobalCommentsDisable->removeFromAdminBar();
        $this->assertArrayHasKey('remove', $actions[0]);
        $this->assertEquals('admin_bar_menu', $actions[0]['remove']);

        $this->assertArrayHasKey('action', $actions[0]);
        $this->assertEquals('wp_admin_bar_comments_menu', $actions[0]['action']);

        $this->assertArrayHasKey('priority', $actions[0]);
        $this->assertGreaterThan(10, $actions[0]['priority']);
    }

    public function testRemovePostTypeSupport()
    {
        global $post_types, $post_type_support, $post_type_supports;
        $post_types = [];
        $post_type_support = [];
        $post_types = ['post', 'news', 'people'];

        $this->GlobalCommentsDisable->removePostTypeSupport();
        $this->assertTrue(true);

        $post_type_supports = true;
        $this->GlobalCommentsDisable->removePostTypeSupport();

        $removed = [];
        foreach ($post_type_support as $support) {
            $removed[$support['post_type']][] = $support['remove'];
        }

        foreach ($removed as $item) {
            $this->assertContains('comments', $item);
            $this->assertContains('trackbacks', $item);
        }
    }

    public function testRemoveFromDashboard()
    {
        global $meta_boxes;

        $this->GlobalCommentsDisable->removeFromDashboard();
        $this->assertArrayHasKey('remove', $meta_boxes[0]);
        $this->assertEquals('dashboard_recent_comments', $meta_boxes[0]['remove']);
    }

    public function testRemoveCommentsMenu()
    {
        global $menu_pages;

        $this->GlobalCommentsDisable->removeCommentsMenu();
        $this->assertArrayHasKey('remove', $menu_pages[0]);
        $this->assertEquals('edit-comments.php', $menu_pages[0]['remove']);
    }
}
