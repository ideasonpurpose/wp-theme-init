<?php
namespace IdeasOnPurpose\ThemeInit\Plugins;

class SEOFramework
{
    public function __construct()
    {
        // Hide author's name from SEO Framework block
        add_filter('sybre_waaijer_<3', '__return_false');

        // Move SEO Framework metabox below all custom fields
        add_filter('the_seo_framework_metabox_priority', [$this, 'returnLow']);

        /**
         * For Category Archives, use the first available Featured Image (post_thumbnail)
         * as the OGP image. If no posts contain a Featured Image, use the fallback instead.
         */
        add_filter('the_seo_framework_og_image_args', [$this, 'useFeaturedImage']);
    }

    public function returnLow() {
        return 'low';
    }

    public function useFeaturedImage($args)
    {
        global $wp_query;
        if (is_archive()) {
            foreach ($wp_query->posts as $post) {
                if (has_post_thumbnail($post->ID)) {
                    $thumbnail_id = get_post_thumbnail_id($post->ID);
                    $args['image'] = wp_get_attachment_image_src($thumbnail_id, [1000, 1000])[0];
                    break;
                }
            }
        }
        return $args;
    }
}
