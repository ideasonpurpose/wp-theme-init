<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\ResetMetaboxes::class)]
final class ResetMetaboxesTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testEnqueueScripts()
    {
        global $enqueued, $localized;
        $enqueued = [];

        $ResetMetaboxes = new Admin\ResetMetaboxes();
        $actual = $ResetMetaboxes->enqueueScripts('not_profile.php');
        $this->assertNull($actual);
        $this->assertCount(0, $enqueued);

        $enqueued = [];

        $known_handle = 'iop-metabox-admin-handler';
        $known_var = 'iop_metabox_config';
        $expected = 'test-action';

        $ResetMetaboxes->action = $expected;
        $ResetMetaboxes->enqueueScripts('profile.php');

        $this->assertEquals($known_handle, $enqueued[0]['handle']);
        $this->assertArrayHasKey($known_handle, $localized);

        $this->assertEquals($expected, $localized[$known_handle][$known_var]['action']);
    }

    public function testAddForm()
    {
        $ResetMetaboxes = new Admin\ResetMetaboxes();
        $ResetMetaboxes->addForm();

        $this->expectOutputRegex('/<h2>Metabox Settings/');
        $this->expectOutputRegex('/<button/');
    }

    public function testHandlerFail()
    {
        global $current_user_can;
        $ResetMetaboxes = new Admin\ResetMetaboxes();

        $this->expectExceptionMessage('wp_die');
        $current_user_can = false;
        $ResetMetaboxes->handler();
    }
    public function testHandlerPass()
    {
        global $current_user_can,
            $get_current_user_id,
            $post_types,
            $delete_user_meta,
            $wp_send_json;

        $post_types = [(object) ['name' => 'test_post_type', 'label' => 'Test Post_Type']];
        // $_POST = ['class_name' => 'iop-reset-metabox-order'];

        // d($post_types);

        $ResetMetaboxes = new Admin\ResetMetaboxes();

        $current_user_can = true;
        $get_current_user_id = 4;

        $_POST['class_name'] = 'iop-reset-metabox-order';
        $ResetMetaboxes->handler();

        $this->assertArrayHasKey('user_id', $delete_user_meta);
        $this->assertEquals("meta-box-order_{$post_types[0]->name}", $delete_user_meta['meta_key']);

        $this->assertArrayHasKey('response', $wp_send_json);
        $this->assertStringContainsString('order', $wp_send_json['response']['message']);
        $this->assertStringContainsString('reset', $wp_send_json['response']['message']);

        $_POST['class_name'] = 'iop-reset-metabox-visibility';
        $ResetMetaboxes->handler();

        $this->assertArrayHasKey('user_id', $delete_user_meta);
        $this->assertEquals("metaboxhidden_{$post_types[0]->name}", $delete_user_meta['meta_key']);

        $this->assertArrayHasKey('response', $wp_send_json);
        $this->assertStringContainsString('visibility', $wp_send_json['response']['message']);
        $this->assertStringContainsString('reset', $wp_send_json['response']['message']);
    }
}
