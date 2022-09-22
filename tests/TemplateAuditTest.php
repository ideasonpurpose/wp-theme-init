<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use PhpParser\Node\Expr\Cast\Object_;
use WP_Screen;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

/**
 * @covers \IdeasOnPurpose\ThemeInit\Admin\TemplateAudit
 */
final class TemplateAuditTest extends TestCase
{
    public function testTemplates()
    {
        new Admin\TemplateAudit();

        $this->assertContains(['admin_menu', 'addTemplateAdminMenuInit'], all_added_actions());
    }

    public function testAddCol()
    {
        $Audit = new Admin\TemplateAudit();
        $cols = ['col' => 'Column', 'date' => 'Date'];
        $actual = $Audit->addColumns($cols);
        $this->assertArrayHasKey('col', $actual);
        $this->assertArrayHasKey('date', $actual);
    }

    public function testRenderColumns_null()
    {
        global $post_meta, $page_templates;

        $post_meta = null;
        $page_templates = null;

        $Audit = new Admin\TemplateAudit();
        $Audit->renderColumns('template', 1);

        $this->expectOutputRegex('/span aria-hidden/');
    }

    public function testRenderColumns_name()
    {
        global $post_meta, $page_templates;

        $Audit = new Admin\TemplateAudit();
        $post_meta = 'template.php';
        $title = 'Template Title';
        $page_templates = [
            $post_meta => $title,
        ];

        $Audit->renderColumns('template', 1);
        $this->expectOutputRegex('/<strong>/');

        $actual = $this->getActualOutput();
        $this->assertStringContainsString($title, $actual);
    }

    public function testTemplateAdminPageScreenOptions()
    {
        global $screen_option, $current_screen;
        $id = 123;
        $current_screen = (object) ['id' => $id];
        $Audit = new Admin\TemplateAudit();

        $Audit->submenu_id = 0;
        $Audit->templateAdminPageScreenOptions();
        $this->assertNull($screen_option);

        $Audit->submenu_id = $id;
        $Audit->templateAdminPageScreenOptions();
        $this->assertArrayHasKey('per_page', $screen_option);
    }

    public function testSetOption()
    {
        $Audit = new Admin\TemplateAudit();
        $expected = 'Test Value';
        $actual = $Audit->setOption('status', 'no-option', $expected);
        $this->assertNull($actual);

        $actual = $Audit->setOption('status', $Audit->option_per_page, $expected);
        $this->assertEquals($actual, $expected);
    }

    public function testAddTemplateAdminMenuInit()
    {
        $Audit = new Admin\TemplateAudit();
        $Audit->addTemplateAdminMenuInit();
        $this->assertContains(
            ['load-appearance_page_iop-template-audit', 'templateAdminPageScreenOptions'],
            all_added_actions()
        );
    }

    public function testTemplateAdminPage()
    {
        global $wp_get_theme;
        /** @var \IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable $ListTable */
        $ListTable = $this->getMockBuilder(
            '\IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable'
        )
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['prepare_items'])
            ->addMethods(['display'])
            ->getMock();

        $ListTable->expects($this->once())->method('prepare_items');
        $ListTable->expects($this->once())->method('display');

        $expected = 'Theme Name';
        $wp_get_theme = new \WP_Theme($expected);

        $Audit = new Admin\TemplateAudit();
        $Audit->ListTable = $ListTable;
        $Audit->theme_name = $expected;
        $Audit->templateAdminPage();
        $this->expectOutputRegex('/div/');

        $actual = $this->getActualOutput();
        $this->assertStringContainsString($expected, $actual);
    }
}
