<?php

namespace IdeasOnPurpose\ThemeInit;

// TODO: This is a patch for now, make it compatible with PSR-3
// https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
//
// Better yet, just use Monolog and write a logger which dumps on WordPress Shutdown and console.logs.
// https://github.com/Seldaek/monolog

class Logger
{
    public function __construct($name)
    {
        $this->name = $name;
    }

    private function writeToPage($msg, $level, $color = 'red', $showTrace)
    {
        $console = ['message' => $msg];
        $trace = '';

        if ($showTrace) {
            $console['trace'] = debug_backtrace();
            ob_start();
            debug_print_backtrace();
            $trace = "\n\n" . ob_get_clean();
        }
        $msg_clean = is_string($msg) ? $msg : json_encode($msg, JSON_PRETTY_PRINT);

        error_log($level . ': ' . $msg_clean . $trace);

        $report = function () use ($msg_clean, $trace, $level, $color, $console) {
            // TODO: More styles? Images?
            // https://stackoverflow.com/a/13017382/503463
            printf(
                '<script>console.log("%%c%s", "font-weight: bold; color: %s", %s);</script>',
                $level,
                $color,
                json_encode($console)
            );

            echo "\n<!--\n\n$level: $msg_clean\n-->\n";
        };

        if (
            WP_DEBUG &&
            !defined('XMLRPC_REQUEST') &&
            !defined('REST_REQUEST') &&
            !defined('WP_INSTALLING') &&
            !wp_doing_ajax() &&
            !wp_doing_cron() &&
            !wp_is_json_request() &&
            !defined('WP_CLI')
        ) {
            // add_action('admin_enqueue_scripts', $report, 0);
            // add_action('wp_enqueue_scripts', $report, 0);
            add_action('shutdown', $report, 0);
        }
        return false;
    }

    public function error($msg, $showTrace = true)
    {
        return $this->writeToPage($msg, 'Error', '#c00', $showTrace);
    }

    public function warning($msg, $showTrace = true)
    {
        return $this->writeToPage($msg, 'Warning', 'gold', $showTrace);
    }

    public function info($msg, $showTrace = true)
    {
        return $this->writeToPage($msg, 'Info', 'dodgerblue', $showTrace);
    }
}
