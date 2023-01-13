<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\ThemeInit\Plugins\ACF
 * @covers \IdeasOnPurpose\ThemeInit\Plugins\SEOFramework
 */
final class PluginsTest extends TestCase
{
    public function testInjectACF()
    {
        global $post_types, $rest_fields;
        $post_types = [];
        $rest_fields = [];

        /** @var \IdeasOnPurpose\ThemeInit\Plugins\ACF $ACF */
        $ACF = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Plugins\ACF')
            ->disableOriginalConstructor()
            ->addMethods([])
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
            ->addMethods([])
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
            ->addMethods([])
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
}
