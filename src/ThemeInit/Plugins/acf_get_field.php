<?php

/**
 * ACF Pro get_field polyfill
 * mostly from copilot/grok
 */
function get_field(...$args)
{
    $backtrace = debug_backtrace();
    $caller = $backtrace[0];

    $file = $caller['file'];
    $line = $caller['line'];

    error_log("get_field was called from {$file}:{$line} with arguments: " . print_r($args, true));

    // Add a dashboard notification if called from a WordPress admin screen
    if (is_admin()) {
        add_action('admin_notices', function () use ($args, $file, $line) {
            $args_list = htmlspecialchars(print_r($args, true), ENT_QUOTES);
            echo "<div class='notice notice-warning is-dismissible'>
                    <p>
                      get_field was called from {$file}:{$line} with arguments:
                      <pre>{$args_list}</pre>
                    </p>
                  </div>";
        });
    }

    return null;
}
