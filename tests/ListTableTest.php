<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Admin\TemplateAudit\ListTable::class)]
final class ListTableTest extends TestCase
{
    public function setUp(): void
    {
        unset($GLOBALS['user_meta']);
    }

    public function testColumnDefault()
    {
        $ListTable = new Admin\TemplateAudit\ListTable();

        $item = [
            'count' => 3,
            'id' => 4321,
            'file' => 'file://fake-template-file.php',
        ];

        $expected = [
            'count' => 3,
            'id' => 4321,
            'file' => 'file://fake-template-file.php',
        ];

        foreach ($item as $key => $value) {
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
        $reflection = new \ReflectionClass(Admin\TemplateAudit\ListTable::class);
        $ListTable = $reflection->newInstanceWithoutConstructor();

        $ListTable->prepare_items();

        $this->assertObjectHasProperty('items', $ListTable);
        $this->assertObjectHasProperty('_column_headers', $ListTable);
    }
}
