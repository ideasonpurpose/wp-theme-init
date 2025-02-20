<?php

namespace IdeasOnPurpose\ThemeInit;

class Media
{
    public $WP_DEBUG;
    public $JPEG_QUALITY;

    public function __construct()
    {
        // bridge constants for better testing
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;
        $this->JPEG_QUALITY = defined('JPEG_QUALITY') ? JPEG_QUALITY : false;

        add_filter('jpeg_quality', [$this, 'jpeg_quality']);
        add_filter('wp_image_editors', [$this, 'addHQImageEditor']);
        add_filter('wp_generate_attachment_metadata', [$this, 'compressAllImages'], 10, 2);
    }

    /**
     * Set jpeg_quality to a 0-100 clamped JPEG_QUALITY constant
     * Defaults to 82 if no constant value is set.
     */
    public function jpeg_quality()
    {
        $q = 82; // Default value
        if ($this->JPEG_QUALITY && is_integer($this->JPEG_QUALITY)) {
            $q = max(0, min(100, $this->JPEG_QUALITY)); // Clamp constant value in the 0-100 range
        }
        return $q;
    }

    /**
     * Enable our WP_Image_Editor_Imagick_HQ class for better scaling
     */
    public function addHQImageEditor($editors): array
    {
        return array_merge([__NAMESPACE__ . '\Media\Imagick\HQ'], $editors);
    }

    /**
     * Compress every image uploaded to WordPress
     */
    public function compressAllImages($metadata, $attachment_id)
    {
        /**
         * Check to see if 'original_image' has been created yet (`big_image_size_threshold` filter)
         * If not, save out an optimized copy and update image metadata
         */
        if (array_key_exists('original_image', $metadata)) {
            return $metadata;
        }
        /**
         * Check to see if $metadata['file'] is set. As of WordPress v6, this will be
         * missing for formats which can not be edited. (pdf, mp4)
         */
        if (!array_key_exists('file', $metadata)) {
            return $metadata;
        }

        $uploads = wp_upload_dir();
        $srcFile = $uploads['basedir'] . '/' . $metadata['file'];
        $editor = wp_get_image_editor($srcFile);

        if (is_wp_error($editor)) {
            error_log("File $metadata[file] can not be edited.");
            return $metadata;
        }

        /**
         * WordPress does not expose the Imagick object from `wp_get_image_editor`
         * so there's no way to get the compressed image's filesize before it's written
         * to disk.
         */
        $saved = $editor->save($editor->generate_filename('optimized'));

        if (is_wp_error($saved)) {
            error_log('Error trying to save.', $saved->get_error_message());
            return $metadata;
        }

        /**
         * Compare filesize of the optimized image against the original
         * If the optimized filesize is less than 75% of the original, then
         * use the optimized image. If not, remove the optimized image and
         * keep using the original image.
         */

        $savedSize = filesize($saved['path']);
        $srcSize = filesize($srcFile);

        if ($savedSize && $srcSize && $savedSize / $srcSize < 0.75) {
            // Optimization successful, update $metadata to use optimized image
            // Ref: https://developer.wordpress.org/reference/functions/_wp_image_meta_replace_original/
            update_attached_file($attachment_id, $saved['path']);
            $metadata['original_image'] = basename($metadata['file']);
            $metadata['file'] = dirname($metadata['file']) . '/' . $saved['file'];
        } else {
            // Optimization not worth it, delete optimized file and use original
            unlink($saved['path']);
        }
        return $metadata;
    }
}
