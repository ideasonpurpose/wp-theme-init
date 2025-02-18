<?php declare(strict_types=1);

namespace IdeasOnPurpose\ThemeInit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\ThemeInit\Media::class)]
#[CoversClass(\IdeasOnPurpose\ThemeInit\Media\Imagick\HQ::class)]
final class MediaTest extends TestCase
{
    private $pdf;
    private $mp4;

    protected function setUp(): void
    {
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
        /**
         * Better than mocking!
         */
        $ref = new \ReflectionClass('\IdeasOnPurpose\ThemeInit\Media');
        /**
         * @var Media $Media
         */
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
}
