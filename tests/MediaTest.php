<?php

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * Override shell-functions for mocking built-ins
 *
 * Create a PHPUnit mock from the BuiltIns class, then pass each method
 * as the global.
 */
function filesize($file): int|false
{
    global $filesize_mock;
    if (isset($filesize_mock)) {
        if (is_callable($filesize_mock)) {
            return $filesize_mock($file);
        } else {
            return $filesize_mock;
        }
    } else {
        return \filesize($file);
    }
}

function unlink($file)
{
    global $unlink_mock;

    if (isset($unlink_mock)) {
        if (is_callable($unlink_mock)) {
            return $unlink_mock($file);
        } else {
            return $unlink_mock;
        }
    } else {
        throw new \Error('fallback to the real unlink!');
        // return \unlink($file);
    }
}

class BuiltIns
{
    // public function filesize($file): int|false
    public function filesize($file)
    {
    }
    public function unlink($file): bool
    {
    }
}

#[CoversClass(\IdeasOnPurpose\ThemeInit\Media::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Media\Imagick\HQ::class)]
final class MediaTest extends TestCase
{
    private $pdf;
    private $mp4;

    protected function setUp(): void
    {
        /**
         * Reset the globals
         */
        global $filesize_mock, $unlink_mock, $wp_get_image_editor, $wp_upload_dir;
        unset($filesize_mock);
        unset($unlink_mock);
        unset($wp_get_image_editor);
        unset($wp_upload_dir);

        /**
         * Fixtures
         *
         * These are $metadata arguments to be passed to Media->compressAllImages()
         */
        $this->pdf = ['filesize' => 1506261];
        $this->mp4 = [
            'filesize' => 44075028,
            'mime_type' => 'video/mp4',
            'width' => 1280,
            'height' => 720,
            'fileformat' => 'mp4',
            'dataformat' => 'quicktime',
            'audio' => [
                'dataformat' => 'mp4',
                'codec' => 'ISO/IEC 14496-3 AAC',
                'sample_rate' => 48000,
                'channels' => 2,
                'lossless' => false,
                'channelmode' => 'stereo',
            ],
        ];
    }

    public function testJPEGQuality()
    {
        $Media = new Media();
        $quality = $Media->jpeg_quality();
        $this->assertEquals(77, $quality);

        $Media->JPEG_QUALITY = 12;
        $quality = $Media->jpeg_quality();
        $this->assertEquals(12, $quality);

        $Media->JPEG_QUALITY = -1;
        $quality = $Media->jpeg_quality();
        $this->assertEquals(0, $quality);

        $Media->JPEG_QUALITY = 150;
        $quality = $Media->jpeg_quality();
        $this->assertEquals(100, $quality);
    }

    public function testAddHQImageEditors()
    {
        $ref = new \ReflectionClass('\IdeasOnPurpose\ThemeInit\Media');
        $Media = $ref->newInstanceWithoutConstructor();

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
        $HqEditor = new ($editors[0])();
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

    /**
     * Different file types provide alternate $metadata to the
     * compressAllImages method. If $metadata['file'] is not set,
     * $metadata should be returned unchanged.
     */
    public function testMetadataPassThrough()
    {
        $Media = new Media();

        $metadata = $Media->compressAllImages($this->pdf, 1);
        $this->assertEquals($metadata, $this->pdf);

        $metadata = $Media->compressAllImages($this->mp4, 1);
        $this->assertEquals($metadata, $this->mp4);
    }

    public function testCompressAllImages_hasOriginalImage()
    {
        $ref = new \ReflectionClass(Media::class);
        $Media = $ref->newInstanceWithoutConstructor();
        $metadata = ['original_image' => 'img.jpg'];

        $actual = $Media->compressAllImages($metadata, 1);
        $this->assertEquals($metadata, $actual);
    }

    public function testCompressAllImages_noFile()
    {
        $ref = new \ReflectionClass(Media::class);
        $Media = $ref->newInstanceWithoutConstructor();
        $metadata = ['a' => 'b'];

        $actual = $Media->compressAllImages($metadata, 1);
        $this->assertEquals($metadata, $actual);
    }

    public function testCompressAllImages_noEditor()
    {
        global $wp_get_image_editor;
        $wp_get_image_editor = new \WP_Error('no editor');

        $ref = new \ReflectionClass(Media::class);
        $Media = $ref->newInstanceWithoutConstructor();
        $metadata = ['file' => 'img.jpg'];

        $actual = $Media->compressAllImages($metadata, 1);
        $this->assertEquals($metadata, $actual);
    }

    public function testCompressAllImages_errorSaving()
    {
        global $wp_get_image_editor;

        $editorMock = $this->createMock(\WP_Image_Editor::class);
        $editorMock
            ->expects($this->exactly(1))
            ->method('save')
            ->willReturn(new \WP_Error('save error'));

        $editorMock->expects($this->once())->method('generate_filename')->willReturn('name');

        $wp_get_image_editor = $editorMock;

        $ref = new \ReflectionClass(Media::class);
        $Media = $ref->newInstanceWithoutConstructor();
        $metadata = ['file' => 'img.jpg'];

        $actual = $Media->compressAllImages($metadata, 1);
        $this->assertEquals($metadata, $actual);
    }

    public function testCompressAllImages_savedIsSmaller()
    {
        global $wp_get_image_editor, $filesize_mock;

        $expected = 'path/to/image.jpg';
        $editorMock = $this->createMock(\WP_Image_Editor::class);
        $editorMock
            ->expects($this->exactly(1))
            ->method('save')
            ->willReturn([
                'path' => 'path/to/image.jpg',
                'name' => 'image.jpg',
                'file' => $expected,
            ]);

        $editorMock->expects($this->once())->method('generate_filename')->willReturn('name');
        $wp_get_image_editor = $editorMock;

        $builtInsMock = $this->createMock(BuiltIns::class);
        $builtInsMock->expects($this->exactly(2))->method('filesize')->willReturn(333, 555);

        $filesize_mock = fn($f) => $builtInsMock->filesize($f);

        $ref = new \ReflectionClass(Media::class);
        $Media = $ref->newInstanceWithoutConstructor();
        $metadata = ['file' => 'img.jpg'];

        $actual = $Media->compressAllImages($metadata, 1);
        $this->assertNotEquals($metadata, $actual);
        $this->assertStringContainsString($expected, $actual['file']);
    }

    public function testCompressAllImages_savedNotWorthIt()
    {
        global $wp_get_image_editor, $filesize_mock, $unlink_mock;

        $editorMock = $this->createMock(\WP_Image_Editor::class);
        $editorMock
            ->expects($this->exactly(1))
            ->method('save')
            ->willReturn(['path' => 'path/to/image.jpg', 'name' => 'image.jpg']);

        $editorMock->expects($this->once())->method('generate_filename')->willReturn('name');
        $wp_get_image_editor = $editorMock;

        $builtInsMock = $this->createMock(BuiltIns::class);
        $builtInsMock->expects($this->exactly(2))->method('filesize')->willReturn(111);
        $builtInsMock->expects($this->once())->method('unlink')->willReturn(true);

        $filesize_mock = fn($f) => $builtInsMock->filesize($f);
        $unlink_mock = fn($f) => $builtInsMock->unlink($f);

        $ref = new \ReflectionClass(Media::class);
        $Media = $ref->newInstanceWithoutConstructor();
        $metadata = ['file' => 'img.jpg'];

        $actual = $Media->compressAllImages($metadata, 1);
        $this->assertEquals($metadata, $actual);
    }
}
