<?php

namespace IdeasOnPurpose\WP\NavMenu;

/**
 * Stateless query helpers over a nav menu tree.
 *
 * Every public helper takes a theme location key (as registered with
 * register_nav_menus) and returns menu item WP_Post objects in menu order.
 * Unassigned locations return null or [].
 *
 * Current-item matching is NOT reimplemented: items are run through core's
 * _wp_menu_item_classes_by_context(), exactly as wp_nav_menu() does, and the
 * resulting `current`, `current_item_parent` and `current_item_ancestor`
 * flags are used. If the same object appears more than once in a menu, the
 * first flagged item (in menu order) wins.
 *
 * Each helper has a `*FromItems()` twin which operates on an already-flagged
 * item array (e.g. inside a `wp_nav_menu_objects` filter).
 *
 * Template usage:
 *
 *     use IdeasOnPurpose\WP\NavMenu\Branch;
 *
 *     $root = Branch::base('menu-header-nav');
 *     $menu = wp_nav_menu([
 *         'theme_location' => 'menu-header-nav',
 *         'iop_branch_filter' => true,
 *         'echo' => false,
 *     ]);
 *     if ($root && $menu) {
 *         printf('<h2>%s</h2>%s', esc_html($root->title), $menu);
 *     }
 */
class Branch
{
    /**
     * Load and context-flag the items for a theme location.
     * @return array Menu item WP_Post objects, [] if the location is unassigned
     */
    public static function items(string $location): array
    {
        $locations = get_nav_menu_locations();
        if (empty($locations[$location])) {
            return [];
        }
        $items = wp_get_nav_menu_items($locations[$location]);
        if (!$items) {
            return [];
        }
        _wp_menu_item_classes_by_context($items);
        return array_values($items);
    }

    public static function current(string $location): ?object
    {
        return self::currentFromItems(self::items($location));
    }

    public static function parent(string $location): ?object
    {
        return self::parentFromItems(self::items($location));
    }

    public static function ancestors(string $location): array
    {
        return self::ancestorsFromItems(self::items($location));
    }

    /**
     * @param object|int|null $item Menu item or menu item ID, defaults to current
     */
    public static function children(string $location, $item = null): array
    {
        return self::childrenFromItems(self::items($location), $item);
    }

    public static function siblings(string $location): array
    {
        return self::siblingsFromItems(self::items($location));
    }

    /**
     * @param bool|int $base true: top-level ancestor of current, positive int: menu item ID
     */
    public static function descendants(string $location, $base = true): array
    {
        return self::descendantsFromItems(self::items($location), $base);
    }

    /**
     * @param bool|int $base true: top-level ancestor of current, positive int: menu item ID
     */
    public static function base(string $location, $base = true): ?object
    {
        return self::baseFromItems(self::items($location), $base);
    }

    /* ---------- Item-array twins (items must already be context-flagged) ---------- */

    public static function currentFromItems(array $items): ?object
    {
        foreach ($items as $item) {
            if (!empty($item->current)) {
                return $item;
            }
        }
        return null;
    }

    public static function parentFromItems(array $items): ?object
    {
        $current = self::currentFromItems($items);
        return $current ? self::find($items, (int) $current->menu_item_parent) : null;
    }

    /**
     * Path from the top-level item down to the parent of current.
     */
    public static function ancestorsFromItems(array $items): array
    {
        $path = [];
        $node = self::currentFromItems($items);
        $seen = $node ? [$node->ID => true] : [];
        while ($node && ($node = self::find($items, (int) $node->menu_item_parent))) {
            if (isset($seen[$node->ID])) {
                break; // guard against malformed cycles
            }
            $seen[$node->ID] = true;
            array_unshift($path, $node);
        }
        return $path;
    }

    /**
     * @param object|int|null $item Menu item or menu item ID, defaults to current
     */
    public static function childrenFromItems(array $items, $item = null): array
    {
        $item = $item ?? self::currentFromItems($items);
        if ($item === null) {
            return [];
        }
        $id = is_object($item) ? (int) $item->ID : (int) $item;
        return array_values(
            array_filter($items, fn($i) => (int) $i->menu_item_parent === $id)
        );
    }

    /**
     * Items sharing current's parent, current included.
     */
    public static function siblingsFromItems(array $items): array
    {
        $current = self::currentFromItems($items);
        if (!$current) {
            return [];
        }
        $parent = (int) $current->menu_item_parent;
        return array_values(
            array_filter($items, fn($i) => (int) $i->menu_item_parent === $parent)
        );
    }

    /**
     * Everything under base at any depth, base excluded, in menu order.
     * @param bool|int $base true or a positive menu item ID
     */
    public static function descendantsFromItems(array $items, $base = true): array
    {
        $root = self::baseFromItems($items, $base);
        if (!$root) {
            return [];
        }
        $ids = [];
        $queue = [(int) $root->ID];
        while ($queue) {
            $parent = array_shift($queue);
            foreach ($items as $item) {
                $id = (int) $item->ID;
                if ((int) $item->menu_item_parent === $parent && !isset($ids[$id])) {
                    $ids[$id] = true;
                    $queue[] = $id;
                }
            }
        }
        unset($ids[(int) $root->ID]);
        return array_values(array_filter($items, fn($i) => isset($ids[(int) $i->ID])));
    }

    /**
     * true: top-level ancestor of current (or current itself if top-level).
     * Positive int: that menu item, if it's in the menu. Otherwise null.
     * @param bool|int $base
     */
    public static function baseFromItems(array $items, $base = true): ?object
    {
        if (is_int($base) && $base > 0) {
            return self::find($items, $base);
        }
        if ($base !== true) {
            return null;
        }
        foreach ($items as $item) {
            if (
                (int) $item->menu_item_parent === 0 &&
                (!empty($item->current) || !empty($item->current_item_ancestor))
            ) {
                return $item;
            }
        }
        return null;
    }

    protected static function find(array $items, int $id): ?object
    {
        if ($id === 0) {
            return null;
        }
        foreach ($items as $item) {
            if ((int) $item->ID === $id) {
                return $item;
            }
        }
        return null;
    }
}
