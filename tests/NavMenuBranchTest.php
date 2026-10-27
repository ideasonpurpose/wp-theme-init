<?php

declare(strict_types=1);

namespace IdeasOnPurpose\WP\NavMenu;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

/**
 * Menu (menu order):
 *   1 A
 *     2 A1
 *       3 A1a
 *       4 A1b
 *     5 A2
 *   6 B
 *     7 B1
 */
/** Capture error_log calls from BranchFilter */
function error_log($msg)
{
    global $nav_menu_errors;
    $nav_menu_errors[] = $msg;
}

function makeItems(): array
{
    $tree = [[1, 0], [2, 1], [3, 2], [4, 2], [5, 1], [6, 0], [7, 6]];
    return array_map(function ($n) {
        $item = new \stdClass();
        $item->ID = $n[0];
        $item->menu_item_parent = (string) $n[1];
        $item->title = "Item {$n[0]}";
        return $item;
    }, $tree);
}

function ids(array $items): array
{
    return array_values(array_map(fn($i) => (int) $i->ID, $items));
}

#[CoversClass(Branch::class)]
#[CoversClass(BranchFilter::class)]
final class NavMenuBranchTest extends TestCase
{
    protected function setUp(): void
    {
        global $nav_menu_locations, $nav_menu_items, $nav_menu_current;
        $nav_menu_locations = ['header' => 99];
        $nav_menu_items = [99 => makeItems()];
        $nav_menu_current = 3;
        global $nav_menu_errors;
        $nav_menu_errors = [];
    }

    protected function tearDown(): void
    {
        global $nav_menu_locations, $nav_menu_items, $nav_menu_current;
        $nav_menu_locations = $nav_menu_items = $nav_menu_current = null;
        global $nav_menu_errors;
        $nav_menu_errors = null;
    }

    public function testUnassignedLocation()
    {
        $this->assertNull(Branch::current('nope'));
        $this->assertNull(Branch::base('nope'));
        $this->assertSame([], Branch::descendants('nope'));
        $this->assertSame([], Branch::items('nope'));

        global $nav_menu_items;
        $nav_menu_items = [];
        $this->assertSame([], Branch::items('header'));
    }

    public function testCurrentParentAncestors()
    {
        $this->assertSame(3, Branch::current('header')->ID);
        $this->assertSame(2, Branch::parent('header')->ID);
        $this->assertSame([1, 2], ids(Branch::ancestors('header')));
    }

    public function testAncestorsExcludeCurrentInMalformedCycle()
    {
        $items = $this->flagged();
        $items[1]->menu_item_parent = '3';

        $this->assertSame([1, 2], ids(Branch::ancestorsFromItems($items)));
    }

    public function testNoCurrent()
    {
        global $nav_menu_current;
        $nav_menu_current = 0;
        $this->assertNull(Branch::current('header'));
        $this->assertNull(Branch::parent('header'));
        $this->assertSame([], Branch::ancestors('header'));
        $this->assertSame([], Branch::children('header'));
        $this->assertSame([], Branch::siblings('header'));
        $this->assertNull(Branch::base('header'));
        $this->assertSame([], Branch::descendants('header'));
    }

    public function testChildren()
    {
        global $nav_menu_current;
        $this->assertSame([], Branch::children('header'));
        $this->assertSame([3, 4], ids(Branch::children('header', 2)));
        $this->assertSame([2, 5], ids(Branch::children('header', Branch::base('header'))));
        $nav_menu_current = 2;
        $this->assertSame([3, 4], ids(Branch::children('header')));
    }

    public function testSiblingsIncludesCurrent()
    {
        $this->assertSame([3, 4], ids(Branch::siblings('header')));
    }

    public function testBase()
    {
        global $nav_menu_current;
        $this->assertSame(1, Branch::base('header')->ID);
        $this->assertSame(6, Branch::base('header', 6)->ID);
        $this->assertNull(Branch::base('header', 404));
        $this->assertNull(Branch::base('header', false));
        $nav_menu_current = 6; // top-level current is its own base
        $this->assertSame(6, Branch::base('header')->ID);
    }

    public function testDescendants()
    {
        $this->assertSame([2, 3, 4, 5], ids(Branch::descendants('header')));
        $this->assertSame([3, 4], ids(Branch::descendants('header', 2)));
        $this->assertSame([], Branch::descendants('header', 404));
    }

    /* ---------- BranchFilter ---------- */

    protected function flagged(): array
    {
        $items = makeItems();
        _wp_menu_item_classes_by_context($items);
        // wp_nav_menu passes a 1-indexed array
        return array_combine(range(1, count($items)), $items);
    }

    protected function filter($items, $arg, bool $includeBase = false)
    {
        $f = new BranchFilter();
        $f->WP_DEBUG = false;
        return $f->filter($items, (object) [
            'iop_branch_filter' => $arg,
            'iop_branch_filter_include_base' => $includeBase,
        ]);
    }

    public function testFilterRegistersHook()
    {
        global $filters;
        $filters = [];
        new BranchFilter();
        $this->assertContains(['wp_nav_menu_objects', 'filter'], all_added_filters());
    }

    public function testFilterPassthrough()
    {
        $items = $this->flagged();
        $f = new BranchFilter();
        $this->assertSame($items, $f->filter($items, (object) []));
        $this->assertSame($items, $f->filter($items, (object) ['iop_branch_filter' => false]));
        $this->assertSame($items, $f->filter($items, (object) ['iop_branch_filter_include_base' => true]));
        $this->assertSame($items, $this->filter($items, false, true));
        $this->assertSame($items, $this->filter($items, null, true));
    }

    public function testFilterTrueRemovesParentKeepsOrderAndKeys()
    {
        $items = $this->flagged();
        $out = $this->filter($items, true);
        $this->assertSame([2, 3, 4, 5], ids($out));
        $this->assertSame([2, 3, 4, 5], array_keys($out)); // original keys preserved
        $this->assertSame(ids($out), ids($this->filter($items, 'descendants')));
        $f = new BranchFilter();
        $this->assertSame($out, $f->filter($items, (object) ['iop_branch_filter' => true]));
    }

    public function testFilterInt()
    {
        $items = $this->flagged();
        $this->assertSame([7], ids(Branch::descendantsFromItems(array_values($items), 6)));
        // $this->assertFalse($this->filter($items, 6)); // single item -> false
        $this->assertSame([3, 4], ids($this->filter($items, 2)));

        // $this->assertSame([3, 4], ids($this->filter($items, '2')));
    }

    public function testFilterIncludeBaseKeepsOrderAndKeys()
    {
        $items = $this->flagged();
        foreach ([
            [true, [1, 2, 3, 4, 5]],
            ['descendants', [1, 2, 3, 4, 5]],
            [2, [2, 3, 4]],
            [3, [3]],
            ['children', [3]],
            ['siblings', [2, 3, 4]],
        ] as [$selector, $expected]) {
            $out = $this->filter($items, $selector, true);
            $this->assertSame($expected, ids($out));
            $this->assertSame($expected, array_keys($out));
        }
    }

    public function testFilterIncludeBaseForTopLevelCurrent()
    {
        global $nav_menu_current;
        $nav_menu_current = 1;
        $items = $this->flagged();
        $this->assertSame([1, 2, 5], ids($this->filter($items, 'children', true)));
        $this->assertSame([1, 6], ids($this->filter($items, 'siblings', true)));
    }

    public function testFilterIncludeBaseWithoutCurrent()
    {
        global $nav_menu_current;
        $nav_menu_current = 0;
        $items = $this->flagged();
        $this->assertSame([], $this->filter($items, 'children', true));
        $this->assertSame([], $this->filter($items, 'siblings', true));
        $this->assertFalse($this->filter($items, true, true));
        $this->assertFalse($this->filter($items, 404, true));
        $this->assertFalse($this->filter($items, 'unknown', true));
        $this->assertSame([2, 3, 4], ids($this->filter($items, 2, true)));
    }

    protected function debugFilter($arg, bool $debug = true)
    {
        $f = new BranchFilter();
        $f->WP_DEBUG = $debug;
        return $f->filter($this->flagged(), (object) [
            'iop_branch_filter' => $arg,
            'theme_location' => 'header',
        ]);
    }

    public function testMissingIdReturnsFalseAndLogs()
    {
        global $nav_menu_errors;
        $this->assertFalse($this->debugFilter(404));
        $this->assertSame(
            ['iop_branch_filter: menu item 404 not found (menu: header)'],
            $nav_menu_errors,
        );
    }

    public function testUnknownStringReturnsFalseAndLogs()
    {
        global $nav_menu_errors;
        $this->assertFalse($this->debugFilter('sibling'));
        $this->assertFalse($this->debugFilter([1]));
        $this->assertCount(2, $nav_menu_errors);
        $this->assertStringContainsString(
            "unknown value 'sibling' (menu: header)",
            $nav_menu_errors[0],
        );
    }

    public function testNoLoggingWithoutDebug()
    {
        global $nav_menu_errors;
        $this->assertFalse($this->debugFilter('sibling', false));
        $this->assertFalse($this->debugFilter(404, false));
        $this->assertSame([], $nav_menu_errors);
    }

    public function testMenuArgNamesMenu()
    {
        global $nav_menu_errors;
        $f = new BranchFilter();
        $f->WP_DEBUG = true;
        $f->filter($this->flagged(), (object) ['iop_branch_filter' => 'x', 'menu' => 'main']);
        $f->filter($this->flagged(), (object) ['iop_branch_filter' => 'x']);
        $this->assertStringEndsWith('(menu: main)', $nav_menu_errors[0]);
        $this->assertStringEndsWith('(menu: unknown)', $nav_menu_errors[1]);
    }

    public function testFilterStringSelectors()
    {
        global $nav_menu_current;
        $this->assertSame([3, 4], ids($this->filter($this->flagged(), 'siblings')));
        $nav_menu_current = 1;
        $this->assertSame([2, 5], ids($this->filter($this->flagged(), 'children')));
        $nav_menu_current = 3;
        $this->assertEmpty($this->filter($this->flagged(), 'children'));
    }

    public function testFilterNoBase()
    {
        global $nav_menu_current;
        $nav_menu_current = 0;
        $this->assertFalse($this->filter($this->flagged(), true));

        $f = new BranchFilter();
        $f->WP_DEBUG = true;
        $this->assertFalse($f->filter($this->flagged(), (object) ['iop_branch_filter' => true]));
    }
}
