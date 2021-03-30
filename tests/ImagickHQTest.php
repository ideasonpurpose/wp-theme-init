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
    public function testAddHQImageEditors()
    {
        $ThemeInit = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit')
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addHQImageEditor'])
            ->getMock();

        /**
         * This will have a length of 2 because addHQImageEditor will prepend our
         * editor to $editorList. This function is a filter, so $editorList is a
         * WordPress supplied array of image editor names as strings.
         */
        $editorList = ['Fake\Editor'];
        $editors = $ThemeInit->addHQImageEditor($editorList);
        $this->assertCount(2, $editors);
        $this->assertContains($editorList[0], $editors);
        $this->assertContains('IdeasOnPurpose\ThemeInit\Imagick\HQ', $editors);

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
