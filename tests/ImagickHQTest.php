<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

require_once 'Fixtures/WP_Image_Editor_Imagick.php';

/**
 * @covers \IdeasOnPurpose\ThemeInit\Media
 * @covers \IdeasOnPurpose\ThemeInit\Media\Imagick\HQ
 */
final class ImagickHQTest extends TestCase
{
    public function testAddHQImageEditors()
    {
        $Media = $this->getMockBuilder('\IdeasOnPurpose\ThemeInit\Media')
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        /**
         * This will have a length of 2 because addHQImageEditor will prepend our
         * editor to $editorList. This function is a filter, so $editorList is a
         * WordPress supplied array of image editor names as strings.
         */
        $editorList = ['Fake\Editor'];
        $editors = $Media->addHQImageEditor($editorList);
        $this->assertCount(2, $editors);
        $this->assertContains($editorList[0], $editors);
        $this->assertContains('IdeasOnPurpose\ThemeInit\Media\Imagick\HQ', $editors);

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
        $method = $reflector->getMethod('thumbnail_image');
        $method->setAccessible(true);
        $this->assertTrue($method->invokeArgs($HqEditor, [1, 1]));
    }
}
