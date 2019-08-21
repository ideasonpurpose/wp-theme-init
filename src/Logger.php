<?php

namespace ideasonpurpose\ThemeInit;

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

    private function writeToPage($msg, $level, $color = 'red')
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_clean();

        error_log($level . ': ' . $msg . "\n\n" . $trace);

        $report = function () use ($msg, $trace, $level, $color) {
            // TODO: More styles? Images?
            // https://stackoverflow.com/a/13017382/503463
            printf(
                '<script>console.log("%%c%s", "font-weight: bold; color: %s", %s);</script>',
                $level,
                $color,
                json_encode(['message' => $msg, 'trace' => debug_backtrace()])
            );

            echo "\n<!--\n\n$level: $msg\n\n$trace\n-->\n";
        };

        if (WP_DEBUG) {
            // add_action('admin_enqueue_scripts', $report, 0);
            // add_action('wp_enqueue_scripts', $report, 0);
            add_action('shutdown', $report, 0);
        }
        return false;
    }

    public function error($msg)
    {
        return $this->writeToPage($msg, 'Error', '#c00');
    }

    public function warning($msg)
    {
        return $this->writeToPage($msg, 'Warning', 'gold');
    }

    public function info($msg)
    {
        return $this->writeToPage($msg, 'Info', 'dodgerblue');
    }
}
