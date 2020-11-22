<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;

require_once 'Fixtures/wp_stubs.php';
require_once 'Fixtures/WP_Image_Editor_Imagick.php';

/**
 * @covers \IdeasOnPurpose\ThemeInit
 * @covers \IdeasOnPurpose\ThemeInit\Imagick\HQ
 */
final class ImagickHQTest extends TestCase
{
    public function testAddEditors()
    {
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addEditor'])
            ->getMock();

        /**
         * ThemeInit::addEditors is protected, so set up a Reflection
         * to make the method accessible
         */
        $method = new \ReflectionMethod($ThemeInit, 'addEditor');
        $method->setAccessible(true);

        /**
         * Our editor should have been prepended to the list of editors
         */
        $editorList = ['Fake\Editor'];
        $editors = $method->invokeArgs($ThemeInit, [$editorList]);
        $this->assertCount(2, $editors);

        /**
         * Instantiate and verify our editor
         */
        $HqEditor = new $editors[0]();
        $this->assertInstanceOf($editors[0], $HqEditor);

        /**
         * HQ::thumbnail is protected, so set up another Reflection
         * this is mostly just for coverage
         */
        $reflector = new \ReflectionClass($HqEditor);
        $method = $reflector->getmethod('thumbnail_image');
        $method->setAccessible(true);
        $this->assertTrue($method->invokeArgs($HqEditor, [1, 1]));
    }
}
