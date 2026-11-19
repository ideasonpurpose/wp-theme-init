<?php

declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit\Extras;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\register_block_pattern_category')) {
    function register_block_pattern_category($name, $props)
    {
        global $pattern_categories;
        $pattern_categories[$name] = $props;
    }
}

#[CoversClass(\IdeasOnPurpose\ThemeInit\Extras\PatternCategory::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit::class)]
final class PatternCategoryTest extends TestCase
{
    public function testConstructorRegistersHook()
    {
        global $actions;
        $actions = [];

        new PatternCategory();

        $this->assertContains('init', array_column($actions, 'add'));
    }

    public function testRegister()
    {
        global $options, $pattern_categories, $stylesheet;

        $options = ['blogname' => 'Joe&#039;s Books &amp; Bar'];
        $pattern_categories = [];
        $stylesheet = 'iop-therabbit-1.0.5';

        new PatternCategory()->register();

        $this->assertEquals(
            ['iop-therabbit' => ['label' => "Joe's Books & Bar"]],
            $pattern_categories,
        );

        $stylesheet = null;
    }
}
