<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use PhpParser\Node\Expr\Cast\Object_;

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
        // global $actions, $filters;
        $Audit = new Admin\TemplateAudit();

        // d($actions, $filters, );
        $this->assertContains(['admin_menu', 'addTemplateAdminMenu'], all_added_actions());
    }

    // public function testListTableClass()
    // {
    //     new Admin\TemplateAudit\ListTable();

    //     $this->assertEquals(4, 4);
    // }

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
        // global $post_meta, $page_templates;

        $Audit = new Admin\TemplateAudit();
        // $post_meta = 'template.php';
        // $actual = 'Template Title';
        // $page_templates = [
        //     $post_meta => $actual,
        // ];

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

        //     // $this->expectOutputString('');

        $Audit->renderColumns('template', 1);
        $this->expectOutputRegex('/<strong>/');

        $actual = $this->getActualOutput();
        d($actual);
        //     $this->assertStringContainsString($actual, $title);
    }

    public function testTemplateAdminPageScreenOption()
    {
        global $screen_option, $current_screen;
        $id = 123;
        $current_screen = (object) ['id' => $id];
        $Audit = new Admin\TemplateAudit();

        $Audit->id = 0;
        $Audit->templateAdminPageScreenOptions();
        $this->assertNull($screen_option);

        $Audit->id = $id;
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

    public function testAddTemplateAdminMenu()
    {
        $Audit = new Admin\TemplateAudit();
        $Audit->addTemplateAdminMenu();
        $this->assertContains(
            ['load-appearance_page_iop-template-audit', 'templateAdminPageScreenOptions'],
            all_added_actions()
        );
    }
}
