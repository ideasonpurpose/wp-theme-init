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
 * @covers WP_List_Table
 */
final class TemplateAuditTest extends TestCase
{

    public function testTemplates()
    {
        $this->assertEquals(1, 1);

    }
}
