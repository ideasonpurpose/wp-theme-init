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
 * This is kind of ugly, because PHP makes it harder to mock global functions
 * $filesize_mock is a global which points to a stubbed object defined
 * in the test. That should have a `filesize` method which can be defined to
 * return different values using something like:
 *      $filesize_mock = $this->createStub();
 *      $filesize_mock
 *          ->method('filesize')
 *          ->will($this->onConsecutiveCalls(2, 3, 5, 7));
 */
function filesize()
{
    global $filesize_mock;
    return $filesize_mock ? $filesize_mock->filesize() : false;
}

function unlink()
{
    global $unlink_mock;
    $unlink_mock->unlink();
}

function is_wp_error()
{
    global $is_wp_error;
    return $is_wp_error->error();
}

/**
 * @covers \IdeasOnPurpose\ThemeInit\Media
 * @covers \IdeasOnPurpose\ThemeInit\Media\Imagick\HQ
 */
final class MediaCompressImagesTest extends TestCase
{
    /**
     * @var Media
     */
    private $Media;

    /**
     * @var WP_Image_Editor
     */
    private $Editor;

    /**
     *
     * @var Array
     */
    private $metadata;

    protected function setUp(): void
    {
        global $is_wp_error, $filesize_mock, $unlink_mock;

        $ref = new \ReflectionClass('\IdeasOnPurpose\ThemeInit\Media');
        $this->Media = $ref->newInstanceWithoutConstructor();

        $this->Editor = $this->createStub('WP_Image_Editor');

        $this->metadata = [
            'width' => 200,
            'height' => 300,
            'file' => 'path/to/fake-file.jpg',
            'sizes' => [],
            'image_meta' => [],
        ];

        /**
         * is_wp_error() is called twice, creating a mock lets us
         * return different values from that call so we can test
         * states between those calls.
         * TODO: should be a stub
         */
        $is_wp_error = $this->getMockBuilder('\StdClass')
            ->disableOriginalConstructor()
            ->addMethods(['error'])
            ->getMock();

        /**
         * This is used to override return values from the
         * built-in `filesize` function. This lets us trigger
         * different results where the logic compares filesizes
         */
        $filesize_mock = $this->getMockBuilder('\StdClass')
            ->disableOriginalConstructor()
            ->addMethods(['filesize'])
            ->getMock();

        /**
         * unlink is called once, mocking this so we can verify
         * the function was called.
         */
        $unlink_mock = $this->getMockBuilder('\StdClass')
            ->disableOriginalConstructor()
            ->addMethods(['unlink'])
            ->getMock();

        /**
         * If the WP_Image_Editor errors, it returns an object
         * instead of an array. This lets us mock calls to the
         * error object.
         */
        $this->editor_error_mock = $this->getMockBuilder('\StdClass')
            ->disableOriginalConstructor()
            ->addMethods(['get_error_message'])
            ->getMock();
    }

    /**
     * An optimized image already exist in metadata, do nothing
     * and return $metadata unchanged.
     */
    public function testOptimizedAlreadyExists()
    {
        $this->metadata['original_image'] = 'file.jpg';
        $result = $this->Media->compressAllImages($this->metadata, 1);
        $this->assertEquals($result, $this->metadata);
    }

    /**
     * File could not be edited. WP_Image_Editor returned an error
     * during instantiation
     */
    public function testEditorFail()
    {
        global $is_wp_error, $error_log;
        $is_wp_error
            ->expects($this->exactly(1))
            ->method('error')
            ->willReturn(true);
        $result = $this->Media->compressAllImages($this->metadata, 1);
        $this->assertEquals($result, $this->metadata);
        $this->assertStringEndsWith('can not be edited.', $error_log);
    }

    /**
     * Unable to save file with new name. WP_Image_Editor returned an
     * error when trying to save the new file.
     */
    public function testEditorSaveFail()
    {
        global $is_wp_error, $error_log, $wp_get_image_editor;
        $is_wp_error
            ->expects($this->exactly(2))
            ->method('error')
            ->will($this->onConsecutiveCalls(false, true));

        $this->Editor->method('save')->willReturn($this->editor_error_mock);
        $wp_get_image_editor = $this->Editor;

        $result = $this->Media->compressAllImages($this->metadata, 1);
        $this->assertEquals($result, $this->metadata);
        $this->assertStringStartsWith('Error trying to save.', $error_log);
    }

    /**
     * Filesize returns < 0.75
     * Compress new image and add 'original_image` to metadata
     */
    public function testDoCompression()
    {
        global $is_wp_error, $filesize_mock, $wp_get_image_editor;
        $is_wp_error->method('error')->willReturn(false);

        $filesize_mock
            ->expects($this->exactly(2))
            ->method('filesize')
            ->will($this->onConsecutiveCalls(1, 4));

        $this->Editor
            ->method('save')
            ->willReturn(['file' => 'file-optimized.jpg', 'path' => 'fake/path']);
        $wp_get_image_editor = $this->Editor;

        $result = $this->Media->compressAllImages($this->metadata, 1);
        $this->assertArrayHasKey('original_image', $result);
        $this->assertEquals($result['original_image'], basename($this->metadata['file']));
        $this->assertStringEndsWith('optimized.jpg', $result['file']);
    }

    /**
     * Filesize returns > 0.75
     * Compression is not worth it, unlink the temp image
     */
    public function testDontDoCompression()
    {
        global $is_wp_error, $unlink_mock, $filesize_mock, $wp_get_image_editor;

        $is_wp_error->method('error')->willReturn(false);
        $unlink_mock->expects($this->once())->method('unlink');

        $filesize_mock
            ->expects($this->exactly(2))
            ->method('filesize')
            ->will($this->onConsecutiveCalls(7, 8));

        $this->Editor
            ->method('save')
            ->willReturn(['file' => 'file-optimized.jpg', 'path' => 'fake/path']);
        $wp_get_image_editor = $this->Editor;

        $result = $this->Media->compressAllImages($this->metadata, 1);
        $this->assertArrayNotHasKey('original_image', $result);
    }
}
