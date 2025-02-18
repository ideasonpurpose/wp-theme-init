<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Media::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Media\Imagick\HQ::class)]
final class NamespaceDeprecationTest extends TestCase
{
    public function testStub()
    {
        $this->assertTrue(true);
    }
}
