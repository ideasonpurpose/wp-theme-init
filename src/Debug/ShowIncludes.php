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

    public function getFiles()
    {
        $includes = get_included_files();
        $templates = array_filter($includes, function ($t) {
            global $template;
            // return (strpos($t, get_template_directory()) !== false) && ($t != $template);
            return (strpos($t, get_template_directory()) !== false);
        });
        $this->templates = array_map(function ($t) {
            global $template;
            $t = ($t == $template)
                ? "<span class='template' style='font-weight: bold; color: #000;'>$t</span>"
                : $t;
            return (
                '<li style="list-style-type: none; color: #888; margin: 0;">' .
                str_replace(get_template_directory(), '', $t) .
                '</li>'
            );
        }, $templates);

        global $template;
        $this->templateName = str_replace(
            get_template_directory(),
            '',
            $template
        );
    }

    public function show()
    {
        $this->getFiles();
        ?>
        <div class="container" id="iop-wp-debug-template" style="font-size: 14px; color: #400; overflow: auto; margin: 3em auto; padding: 15px; border: 1px solid #ccc; border-radius: 4px; background: #eee; max-width: 90%;">
          <strong>Template: <span style="font-family: monospace; font-size: 1.1em;"><?php echo $this->templateName; ?></span></strong><br>
          Included Files:
          <ul style="padding-left: .5em; font-family: monospace; font-size: 1.1em; display: block; margin: 0.5em 0; white-space: nowrap;">
            <?php echo implode("\n", $this->templates); ?>
          </ul>
        </div>
        <?php
    }
}
