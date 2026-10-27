<?php

namespace IdeasOnPurpose\WP\NavMenu;

/**
 * Render a single branch of a hierarchical menu by adding `iop_branch_filter`
 * to wp_nav_menu args. The base (parent) item is removed by default.
 *
 *   false / null  Leave the menu unchanged (default when omitted)
 *   true          Descendants of the current item's top-level ancestor
 *   positive int  Descendants of that menu item ID
 *                 (an ID not in the menu returns false, no fallback)
 *   'descendants' Same as true
 *   'children'    Direct children of the current item
 *   'siblings'    Items sharing the current item's parent (current included)
 *
 * Set `iop_branch_filter_include_base` to true to also include the base item:
 * top-level ancestor for true/'descendants', matching item for a positive ID,
 * current item for 'children', or parent for 'siblings' (if present).
 * This flag defaults to false and has no effect when iop_branch_filter is disabled.
 * A base with no children can render by itself when included.
 *
 *     wp_nav_menu([
 *         'theme_location' => 'menu-header-nav',
 *         'iop_branch_filter' => true,
 *         'iop_branch_filter_include_base' => true,
 *     ]);
 *
 * Any other value returns false (no menu); with WP_DEBUG, mismatches and unknown
 * values are logged via error_log(). Items whose parent
 * was removed render as top-level items via the core walker.
 * Query logic lives in Branch; this filter is just a second entry point.
 */
class BranchFilter
{
    public $WP_DEBUG;

    public function __construct()
    {
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;
        add_filter('wp_nav_menu_objects', [$this, 'filter'], 10, 2);
    }

    /**
     * @param array $items Context-flagged menu items from wp_nav_menu
     * @param object $args wp_nav_menu args; iop_branch_filter_include_base defaults to false
     * @return array|false
     */
    public function filter($items, $args)
    {
        $selector = $args->iop_branch_filter ?? false;
        if ($selector === false || !is_array($items)) {
            return $items;
        }

        $menu = $args->theme_location ?? '' ?:
        (is_object($args->menu ?? null) ? $args->menu->slug ?? '' : (string) ($args->menu ?? ''));

        if ($selector === 'children') {
            $base = Branch::currentFromItems($items);
            $branch = Branch::childrenFromItems($items);
        } elseif ($selector === 'siblings') {
            $base = Branch::parentFromItems($items);
            $branch = Branch::siblingsFromItems($items);
        } elseif ($selector === true || $selector === 'descendants') {
            $base = Branch::baseFromItems($items, true);
            if (!$base) {
                $this->log(sprintf('Base branch for %s is undefined', (int) $selector), $menu);
                return false;
            }
            $branch = Branch::descendantsFromItems($items, true);
        } elseif (is_int($selector) && $selector > 0) {
            $base = Branch::baseFromItems($items, (int) $selector);
            if (!$base) {
                $this->log(sprintf('menu item %d not found', (int) $selector), $menu);
                return false;
            }
            $branch = Branch::descendantsFromItems($items, (int) $selector);
        } else {
            $this->log(sprintf('unknown value %s', var_export($selector, true)), $menu);
            return false;
        }

        if (!empty($args->iop_branch_filter_include_base) && $base) {
            $branch[] = $base;
        }

        // Filter the original $items so keys and menu order are preserved
        $keep = array_map(fn($i) => (int) $i->ID, $branch);
        $pruned = array_filter($items, fn($i) => in_array((int) $i->ID, $keep, true));
        return $pruned;
    }

    protected function log(string $msg, string $menu): void
    {
        if ($this->WP_DEBUG) {
            error_log(sprintf('iop_branch_filter: %s (menu: %s)', $msg, $menu ?: 'unknown'));
        }
    }
}
