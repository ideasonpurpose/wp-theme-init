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

    // public function getFiles()
    // {
    //     $includes = get_included_files();
    //     $templates = array_filter($includes, function ($t) {
    //         global $template;
    //         // return (strpos($t, get_template_directory()) !== false) && ($t != $template);
    //         return (strpos($t, get_template_directory()) !== false);
    //     });
    //     $this->templates = array_map(function ($t) {
    //         global $template;
    //         $t = ($t == $template)
    //             ? "<span class='template' style='font-weight: bold; color: #000;'>$t</span>"
    //             : $t;
    //         return (
    //             // '<li style="list-style-type: none; color: #888; margin: 0;">' .
    //             str_replace(get_template_directory(), '', $t)
    //             // '</li>'
    //         );
    //     }, $templates);

    //     global $template;
    //     $this->templateName = str_replace(
    //         get_template_directory(),
    //         '',
    //         $template
    //     );
    // }

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
