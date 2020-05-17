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

        $includes = array_map(function ($t) {
            return (str_replace(get_template_directory() . '/', '', $t));
        }, array_values($includes));

        $all = [];
        $theme = [];
        $vendor = [];

        foreach ($includes as $include ) {
            $all[] = $include;
            if (strpos($include, 'vendor') === 0) {
                $vendor[] = $include;
            } else {
                $theme[] = $include;
            }
        }

        $files = [
            'template' => str_replace(get_template_directory(), '', $template),
            'all_includes' => $all,
            'theme_includes' => $theme,
            'vendor_includes' => $vendor
         ];

        printf('<script>console.log("%%cPHP Includes", "font-weight: bold", %s);</script>', json_encode($files));
    }
}
