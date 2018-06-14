<?php
namespace ideasonpurpose\ThemeInit\plugins;

class SeoFramework
{
    public function __construct()
    {
        // Hide author's name from SEO Framework block
        add_filter('sybre_waaijer_<3', '__return_false');

        // Move SEO Framework metabox below all custom fields
        add_filter('the_seo_framework_metabox_priority', function () {
            return 'low';
        });

        /**
         * For Category Archives, use the first available Featured Image (post_thumbnail)
         * as the OGP image. If no posts contain a Featured Image, use the fallback instead.
         */
        add_filter('the_seo_framework_og_image_args', function ($args) {
            global $wp_query;
            if (is_archive()) {
                foreach ($wp_query->posts as $post) {
                    if (has_post_thumbnail($post->ID)) {
                        $args[
                            'image'
                        ] = wp_get_attachment_image_src(
                            get_post_thumbnail_id($post->ID),
                            [1000, 1000]
                        )[0];
                        break;
                    }
                }
            }
            return $args;
        });
    }
}
