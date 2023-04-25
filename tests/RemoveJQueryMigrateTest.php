<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use IdeasOnPurpose\ThemeInit\Extras\RemoveJQueryMigrate;
use ReflectionClass;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\ThemeInit\Extras\RemoveJQueryMigrate
 */
final class RemoveJQueryMigrateTest extends TestCase
{
    protected $RemoveJQueryMigrate;

    protected function setUp(): void
    {
        global $actions;
        $actions = [];
        $ref = new ReflectionClass(\IdeasOnPurpose\ThemeInit\Extras\RemoveJQueryMigrate::class);
        $this->RemoveJQueryMigrate = $ref->newInstanceWithoutConstructor();
    }

    public function testRemoveJQueryMigrateConstructor()
    {
        new RemoveJQueryMigrate();
        $this->assertContains(['wp_default_scripts', 'deRegister'], all_added_actions());
    }

    public function testRemoveJQueryMigrate()
    {
        global $is_admin;
        $is_admin = false;

        $jq = (object) ['deps' => ['dog', 'cat', 'jquery-migrate']];
        $actual = (object) ['registered' => ['jquery' => $jq]];
        $this->RemoveJQueryMigrate->deRegister($actual);
        $this->assertNotContains('jquery-migrate', $jq->deps);
    }

    public function testAdminRemoveJQueryMigrate()
    {
        global $is_admin;
        $is_admin = true;

        $jq = (object) ['deps' => ['dog', 'cat', 'jquery-migrate']];
        $actual = (object) ['registered' => ['jquery' => $jq]];
        $this->RemoveJQueryMigrate->deRegister($actual);
        $this->assertContains('jquery-migrate', $jq->deps);
    }
}
