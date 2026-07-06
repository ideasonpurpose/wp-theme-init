<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use IdeasOnPurpose\WP\Test;

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

    public function testTwoFactorDisableForNonProduction()
    {
        global $wp_get_environment_type;
        $reflection = new \ReflectionClass(Plugins\TwoFactor::class);
        $TwoFactor = $reflection->newInstanceWithoutConstructor();
        $this->expectErrorLog();

        $enabled_providers = ['Two_Factor_Email'];
        $expected = ['Two_Factor_Dummy'];

        $wp_get_environment_type = 'development';
        $actual = $TwoFactor->disableForNonProduction($enabled_providers);
        $this->assertEquals($expected, $actual);

        $wp_get_environment_type = 'production';
        $actual = $TwoFactor->disableForNonProduction($enabled_providers);
        $this->assertEquals($enabled_providers, $actual);

        $wp_get_environment_type = '';
        $actual = $TwoFactor->disableForNonProduction($enabled_providers);
        $this->assertEquals($expected, $actual);
    }

    public function testTwoFactorTokenEmail()
    {
        global $filters;
        $filters = [];

        $reflection = new \ReflectionClass(Plugins\TwoFactor::class);
        $TwoFactor = $reflection->newInstanceWithoutConstructor();

        $tokenFirst3 = '123';
        $tokenLast3 = '456';
        $token = $tokenFirst3 . $tokenLast3;
        $message = "Your token is {$token}.";

        $actual = $TwoFactor->tokenEmail($message, $token, 1);
        $this->assertStringContainsString($tokenFirst3, $actual);
        $this->assertStringContainsString($tokenLast3, $actual);

        $this->assertContains(['wp_mail', 'rewriteSubject'], \all_added_filters());
        $this->assertContains(['wp_mail_content_type', 'text/html'], \all_added_filters());
        $filterNames = array_column($filters, 'add');
        $this->assertContains('wp_mail_from_name', $filterNames);
    }

    public function testRewriteSubject()
    {
        $reflection = new \ReflectionClass(Plugins\TwoFactor::class);
        $TwoFactor = $reflection->newInstanceWithoutConstructor();
        $TwoFactor->token = '987654';
        $actual = $TwoFactor->rewriteSubject(['subject' => 'No token in this subject line']);
        $this->assertStringContainsString($TwoFactor->token, $actual['subject']);
    }
}
