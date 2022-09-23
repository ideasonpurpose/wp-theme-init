<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

if (!function_exists(__NAMESPACE__ . '\error_log')) {
    function error_log($err)
    {
        global $error_log;
        $error_log = $err;
    }
}

/**
 * @covers \IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable
 */
final class ListTableTest extends TestCase
{
    public function testTemplates()
    {
        new Admin\TemplateAudit\ListTable();

        $this->assertEquals(4, 4);
        // $this->assertContains(['admin_menu', 'addTemplateAdminMenuInit'], all_added_actions());
    }

    public function testColumnDefault()
    {
        $ListTable = new Admin\TemplateAudit\ListTable();

        $item = [
            // 'name' => 'item_name',
            'count' => 3,
            'id' => 4321,
            'file' => 'file://fake-template-file.php',
        ];

        $expected = [
            // 'name' => "<strong>{$item['name']}</strong>",
            'count' => 3,
            'id' => 4321,
            'file' => 'file://fake-template-file.php',
        ];

        foreach ($item as $key => $value) {
            # code...
            $actual = $ListTable->column_default($item, $key);
            $this->assertEquals($actual, $expected[$key]);
        }

        $item['name'] = 'Item Name';
        $actual = $ListTable->column_default($item, 'name');
        $expected = "<strong>{$item['name']}</strong>";
        $this->assertEquals($actual, $expected);

        $item['count'] = 0;
        $actual = $ListTable->column_default($item, 'count');
        $expected = '--';
        $this->assertEquals($actual, $expected);
    }

    public function testOneLiners()
    {
        $ListTable = new Admin\TemplateAudit\ListTable();
        $actual = $ListTable->get_columns();
        $this->assertArrayHasKey('file', $actual);

        $actual = $ListTable->get_hidden_columns();
        $this->assertContains('id', $actual);

        $actual = $ListTable->get_sortable_columns();
        $this->assertArrayHasKey('name', $actual);
    }

    public function testSortData()
    {
        $ListTable = new Admin\TemplateAudit\ListTable();
        $a = ['name' => 'AAA', 'count' => 1];
        $b = ['name' => 'BBB', 'count' => 10];

        $actual = $ListTable->sortData($a, $b);
        $this->assertLessThan(0, $actual);

        $_GET['orderby'] = 'count';
        $actual = $ListTable->sortData($a, $b);
        $this->assertLessThan(0, $actual);

        $_GET['order'] = 'frog'; // anything not 'asc' converts to 'desc'
        $actual = $ListTable->sortData($a, $b);
        $this->assertGreaterThan(0, $actual);

        $_GET['orderby'] = 'birds';
        $actual = $ListTable->sortData($a, $b);
        $this->assertEquals(0, $actual);
    }

    public function testData()
    {
        global $pages, $page_templates;

        $ListTable = new Admin\TemplateAudit\ListTable();

        $post = (object) ['meta_value' => 'template.php'];
        $pages = [$post, $post];
        $page_templates = ['template.php' => 'Fake Template'];

        $actual = $ListTable->data();

        $this->assertArrayHasKey('name', $actual[0]);
        $this->assertEquals(2, $actual[0]['count']);
    }

    public function testPrepareItems()
    {
        global $pages, $user_meta;

        /** @var \IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable $ListTable */
        $ListTable = $this->getMockBuilder(
            '\IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable'
        )
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['data'])
            ->addMethods(['get_pagenum', 'set_pagination_args'])
            ->getMock();

        $post = (object) ['meta_value' => 'template.php'];
        $pages = [$post, $post];

        $data = [['name' => 'Data 1', 'count' => 3], ['name' => 'Data 2', 'count' => 6]];
        $ListTable
            ->expects($this->exactly(2))
            ->method('data')
            ->willReturn($data);

        $ListTable->prepare_items();
        $this->assertCount(2, $ListTable->items);

        $user_meta = 1; // set per_page to 1

        $ListTable->prepare_items();
        $this->assertCount(1, $ListTable->items);
    }
}
