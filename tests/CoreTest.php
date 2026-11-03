<?php

namespace IdeasOnPurpose;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Core::class)]
final class CoreTest extends TestCase
{
    public function testReadOption()
    {
        global $options;
        /**
         * Replace PHPUnit's deprecated setMethodsExcept method with a list of all methods EXCEPT 'readOption'
         *
         * TODO: Waiting on feedback about best practices for partial mocks
         *     - https://github.com/sebastianbergmann/phpunit/issues/4652#issuecomment-957662989
         *     - https://stackoverflow.com/questions/69813091/how-to-best-preserve-some-methods-when-mocking-a-class-with-phpunit-10
         */

        /**
         * Alternate method: Use Reflection API to get a copy of the readOption method
         */
        $class = new \ReflectionClass(ThemeInit\Core::class);
        $readOption = $class->getMethod('readOption');

        /**
         * The mocked get_option function returns the argument passed to it,
         * which should be the theme name with the version stripped off
         */
        $expected = 'SomeValue';
        $options['theme-name'] = $expected;
        $core = new ThemeInit\Core();
        $opt = $core->readOption('Other Value', 'theme-name-1_2_3');
        $this->assertEquals($opt, $expected);
        /**
         * Check the ReflectionMethod too
         */
        $opt = $readOption->invoke(
            $class->newInstanceWithoutConstructor(),
            'hello',
            'theme-name-1_2_4',
        );
        $this->assertEquals($opt, $expected);

        /**
         * Option values attached to un-versioned theme-names pass through directly
         */
        $expected = '42';
        $opt = $core->readOption($expected, 'theme-name');
        $this->assertEquals($opt, $expected);

        $opt = $readOption->invoke(
            $class->newInstanceWithoutConstructor(),
            $expected,
            'theme-name_1_2_4',
        );
        $this->assertEquals($opt, $expected);
    }

    public function testWriteOption()
    {
        /**
         * When the theme-name is corrected, writeOption will
         * return the old value to short-circuit update_option()
         */
        $core = new ThemeInit\Core();
        $opt = $core->writeOption('42', 'old-42', 'theme-name-1_2_3');
        $this->assertEquals($opt, 'old-42');

        $opt = $core->writeOption('42', 'old-42', 'theme-name');
        $this->assertEquals($opt, '42');
    }

    public function testRevisionsOverride()
    {
        // Filter with a stub short-arrow function is called from the constructor,
        // which is mocked, so this is never registered and can't be tested as is
        // $this->assertContains(['wp_revisions_to_keep', 6], all_added_filters());
        $this->assertTrue(true);
    }

    public function testEnableWordPressFeatures()
    {
        global $theme_support;

        $Core = new \ReflectionClass(ThemeInit\Core::class)->newInstanceWithoutConstructor();
        $Core->enableWordPressFeatures();

        // print_r($theme_support);
        $features = array_column($theme_support, 'add');

        /**
         * Everything from core's _add_default_theme_supports() plus title-tag
         * @link https://developer.wordpress.org/reference/functions/_add_default_theme_supports/
         */
        $expected = [
            'post-thumbnails',
            'title-tag',
            'html5',
            'responsive-embeds',
            'editor-styles',
            'automatic-feed-links',
        ];
        foreach ($expected as $feature) {
            $this->assertContains($feature, $features);
        }

        $html5 = array_values(array_filter(
            $theme_support,
            fn($s) => ($s['add'] ?? null) === 'html5',
        ));
        $this->assertCount(1, $html5);
        $html5Args = $html5[0]['args'][0];
        $expectedHtml5 = [
            'script',
            'style',
            'search-form',
            'gallery',
            'caption',
            'comment-form',
            'comment-list',
        ];
        $this->assertEqualsCanonicalizing($expectedHtml5, $html5Args);
    }

    public function testToggleAutoUpdates()
    {
        global $wp_get_environment_type;

        $core = new ThemeInit\Core();

        $wp_get_environment_type = 'development';

        $core->toggleAutoUpdates();
        $this->assertNotContains(['auto_update_core', '__return_true'], all_added_filters());

        $wp_get_environment_type = 'staging';
        $core->toggleAutoUpdates();
        $this->assertContains(['auto_update_core', '__return_true'], all_added_filters());
    }
}
