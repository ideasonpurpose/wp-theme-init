<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use IdeasOnPurpose\WP\Test;
use PHPUnit\Framework\Attributes\UsesFunction;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        Test\Stubs::error_log($err);
    }
}

/**
 * Run in separate process to so get_field doesn't leak in from other tests
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\ACF::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\TwoFactor::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\SEOFramework::class)]
final class PluginsTest extends TestCase
{
    public function setUp(): void
    {
        /**
         * Reset globals before each test
         */
        global $post_types,
            $rest_fields,
            $actions,
            $wp_query,
            $is_archive,
            $has_post_thumbnail,
            $wp_get_attachment_image_src;

        unset($post_types);
        unset($rest_fields);
        unset($actions);
        unset($wp_query);
        unset($is_archive);
        unset($has_post_thumbnail);
        unset($wp_get_attachment_image_src);
    }
    public function testInjectACF()
    {
        global $post_types, $rest_fields;
        $post_types = [];
        $rest_fields = [];

        $ref = new \ReflectionClass(Plugins\ACF::class);
        $ACF = $ref->newInstanceWithoutConstructor();

        $ACF->injectACF();

        $type = 'news';
        $post_types = [$type];
        $ACF->InjectACF();
        $this->assertEquals($rest_fields[0]['post_type'], $type);
    }

    public function testReturnLow()
    {
        $ref = new \ReflectionClass(Plugins\SEOFramework::class);
        $seo = $ref->newInstanceWithoutConstructor();

        $expected = 'low';
        $actual = $seo->returnLow();
        $this->assertEquals($expected, $actual);
    }

    public function testUseFeaturedImage()
    {
        global $wp_query, $is_archive, $has_post_thumbnail, $wp_get_attachment_image_src;
        $is_archive = true;
        $wp_query = new \stdClass();
        $post = new \stdClass();
        $post->ID = 1;
        $wp_query->posts = [$post];

        $ref = new \ReflectionClass(Plugins\SEOFramework::class);
        $seo = $ref->newInstanceWithoutConstructor();

        $expected = ['image' => 'fake/image.jpg'];
        $wp_get_attachment_image_src = ['global/image/placeholder.png'];

        $has_post_thumbnail = true;
        $actual = $seo->useFeaturedImage($expected);
        $this->assertEquals($wp_get_attachment_image_src[0], $actual['image']);

        $has_post_thumbnail = false;
        $actual = $seo->useFeaturedImage($expected);
        $this->assertEquals($expected['image'], $actual['image']);
    }

    public function testAcfLoadGetFieldPolyfill()
    {
        $reflection = new \ReflectionClass(Plugins\ACF::class);
        $Acf = $reflection->newInstanceWithoutConstructor();

        $this->assertFalse(function_exists('get_field'));

        $Acf->get_field_polyfill();
        $this->assertTrue(function_exists('get_field'));
    }

    public function testTwoFactorEnforceEmailMFA()
    {
        $reflection = new \ReflectionClass(Plugins\TwoFactor::class);
        $TwoFactor = $reflection->newInstanceWithoutConstructor();

        $expected = ['Two_Factor_Email'];

        $actual = $TwoFactor->enforceEmail(null);
        $this->assertEquals($expected, $actual);

        $actual = $TwoFactor->enforceEmail([]);
        $this->assertEquals($expected, $actual);

        $existing = ['dog'];
        $actual = $TwoFactor->enforceEmail($existing);
        $this->assertEquals($existing, $actual);
    }

    public function testTwoFactorDisableForDev()
    {
        global $wp_get_environment_type;
        $reflection = new \ReflectionClass(Plugins\TwoFactor::class);
        $TwoFactor = $reflection->newInstanceWithoutConstructor();
        $this->expectErrorLog();

        $enabled_providers = ['Two_Factor_Email'];

        $wp_get_environment_type = 'development';
        $actual = $TwoFactor->disableForDev($enabled_providers);
        $this->assertEquals(['Two_Factor_Dummy'], $actual);

        $wp_get_environment_type = 'production';
        $actual = $TwoFactor->disableForDev($enabled_providers);
        $this->assertEquals($enabled_providers, $actual);

        $wp_get_environment_type = '';
        $actual = $TwoFactor->disableForDev($enabled_providers);
        $this->assertEquals($enabled_providers, $actual);
    }
}
