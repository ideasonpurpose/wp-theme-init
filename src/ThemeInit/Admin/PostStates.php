<?php
namespace ideasonpurpose\ThemeInit\Admin;

/**
 * A class to collect post-state labels. Initially, the only label here is
 * the 404 page template.
 */
class PostStates
{
    public function __construct()
    {
        add_filter('display_post_states', [$this, 'add_404_state'], 10, 2);
    }

    /**
     * Add the "404 Page" label to any page using the '404.php' template
     */
    function add_404_state($states, $post)
    {
        $meta = get_post_meta($post->ID, '_wp_page_template', true);

        if ($meta == '404.php') {
            $states['404'] = '404 Page';
        }

        return $states;
    }
}
