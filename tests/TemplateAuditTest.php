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
 * @covers \IdeasOnPurpose\ThemeInit\Admin\TemplateAudit
 */
final class TemplateAuditTest extends TestCase
{
    public function testTemplates()
    {
        global $actions, $filters;
        $Audit = new Admin\TemplateAudit();

        // d($actions, $filters, );
        $this->assertContains(['admin_menu', 'addTemplateAdminMenu'], all_added_actions());
    }

    public function testListTableClass()
    {
        new Admin\TemplateAudit\ListTable();

        $this->assertEqual(1, 4);
    }
}
