<?php

declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit\Admin;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\DisableCustomizer::class)]
final class DisableCustomizerTest extends TestCase
{
    public function testConstructorRegistersHooks()
    {
        global $actions, $filters;

        $actions = [];
        $filters = [];

        new DisableCustomizer();

        $filterHooks = array_column($filters, 'add');
        $actionHooks = array_column($actions, 'add');

        $this->assertContains('map_meta_cap', $filterHooks);
        $this->assertContains('load-customize.php', $actionHooks);
        $this->assertContains('admin_bar_menu', $actionHooks);

        $adminBarAction = array_values(array_filter(
            $actions,
            fn($a) => $a['add'] === 'admin_bar_menu',
        ));
        $this->assertEquals(9999, $adminBarAction[0]['priority']);
    }

    public function testRemoveCapability()
    {
        $disableCustomizer = new DisableCustomizer();

        $this->assertEquals(
            ['do_not_allow'],
            $disableCustomizer->removeCapability([], 'customize'),
        );
        $this->assertEquals(
            ['edit_posts'],
            $disableCustomizer->removeCapability(['edit_posts'], 'edit_posts'),
        );
    }

    public function testBlockCustomizer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('Throwing wp_die Exception');

        new DisableCustomizer()->blockCustomizer();
    }

    public function testRemoveCustomizerFromAdminBar()
    {
        $adminBar = new class {
            public $removed = [];

            public function remove_node($node)
            {
                $this->removed[] = $node;
            }
        };

        new DisableCustomizer()->removeCustomizerFromAdminBar($adminBar);

        $this->assertEquals(['customize'], $adminBar->removed);
    }
}
