<?php
namespace ideasonpurpose\ThemeInit\Debug;

class ShowIncludes
{
    public function __construct()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', [$this, 'show']);
        }
    }

    public function show()
    {
        global $template;
        $includes = array_filter(get_included_files(), function ($t) {
            return (strpos($t, get_template_directory()) !== false);
        });

        $files = [
            'template' => str_replace(get_template_directory(), '', $template),
            'included_files' => array_map(function ($t) {
                return (str_replace(get_template_directory(), '', $t));
            }, array_values($includes))
        ];

        printf('<script>console.log(%s);</script>', json_encode($files));
    }
}
