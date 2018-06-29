<?php

/**
 * Safety placeholder in case a Kint `d()` call sneaks out to production
 * TODO: Will need to be moved to the global namespace whenever this file is namespaced
 *      https://stackoverflow.com/questions/13693868/define-global-function-from-within-php-namespace
 */
if (!function_exists('d')) {
    function d()
    {
        $args = func_get_args();
        $msg = "Kint debugger is not installed on production";
        if (is_user_logged_in()) {
            echo "<h1>$msg</h1>\n";
            echo "<h3>Admin only notification</h3>\n";
            echo '<pre>';
            echo json_encode(debug_backtrace(), JSON_PRETTY_PRINT);
            echo '</pre>';
        }
        error_log("$msg\n" . json_encode($args, JSON_PRETTY_PRINT));
    }
}

