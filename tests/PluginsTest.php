<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
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
#[CoversClass(\IdeasOnPurpose\ThemeInit\Plugins\ACF::class)]
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

        /** @var \IdeasOnPurpose\ThemeInit\Plugins\ACF $ACF */
        $ACF = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Plugins\ACF')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $ACF->injectACF();

        $type = 'news';
        $post_types = [$type];
        $ACF->InjectACF();
        $this->assertEquals($rest_fields[0]['post_type'], $type);
    }

    public function testReturnLow()
    {
        /** @var \IdeasOnPurpose\ThemeInit\Plugins\SEOFramework $seo */
        $seo = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Plugins\SEOFramework')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

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

        /** @var \IdeasOnPurpose\ThemeInit\Plugins\SEOFramework $seo */
        $seo = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Plugins\SEOFramework')
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

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

    // public function testAcfGetFieldPolyfill()
    // {
    //     global $is_admin, $actions;
    //     // $actions = [];

    //     // This prevents error_log from messing up the PHPUnit console
    //     ini_set('error_log', sys_get_temp_dir() . '/phpunit_error.log');

    //     $reflection = new \ReflectionClass(Plugins\ACF::class);
    //     $Acf = $reflection->newInstanceWithoutConstructor();

    //     $is_admin = true;
    //     $Acf->acf_active = false;
    //     $Acf->init();

    //     $this->assertTrue(function_exists('get_field'));
    // }
}
