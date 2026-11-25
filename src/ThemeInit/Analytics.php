<?php

namespace IdeasOnPurpose\ThemeInit;

/**
 * gtag and Cloudflare Web Analytics snippets on wp_head.
 *
 * $config is production. With WP_DEBUG, $dev replaces it completely, so
 * anything left out of $dev is off. Omit $dev, or pass [], to send nothing.
 *
 * gtag is one measurement ID or a list. gtag.js loads once (the first ID);
 * each ID gets a gtag('config') call so several properties can be tracked
 * together.
 *
 * With gtag, HTML5 <video> plays are sent as GA4 video_start, video_progress
 * and video_complete events. Disable with the iop_analytics_video_tracking filter.
 */
class Analytics
{
    public bool $WP_DEBUG = false;

    private array $config;
    private array $dev;

    public function __construct(array $config, array $dev = [])
    {
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;
        $this->config = $config;
        $this->dev = $dev;

        add_action('wp_head', [$this, 'inject']);
    }

    public function inject(): void
    {
        if (is_user_logged_in()) {
            $user = wp_get_current_user()->user_login;
            echo "\n<!-- User '$user' is logged in. Analytics snippet suppressed. -->\n";
            return;
        }

        $config = $this->WP_DEBUG ? $this->dev : $this->config;

        if (!empty($config['gtag'])) {
            $this->gtag($config['gtag']);
            if (apply_filters('iop_analytics_video_tracking', true)) {
                $this->videoTracking();
            }
        }
        if (!empty($config['cloudflare'])) {
            $this->cloudflare($config['cloudflare']);
        }
    }

    private function gtag(string|array $ids): void
    {
        $ids = is_array($ids) ? array_values($ids) : [$ids];
        $src = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ids[0]);
        $commands = implode("\n", array_map(fn($id) => "  gtag('config', '{$id}');", $ids));
        $js = str_replace('{{config}}', $commands, file_get_contents(__DIR__ . '/Analytics/gtag.js'));

        echo "\n<!-- Google tag (gtag.js) -->\n<script async src=\"{$src}\"></script>\n<script>\n{$js}</script>\n";
    }

    private function videoTracking(): void
    {
        $js = file_get_contents(__DIR__ . '/Analytics/video.js');

        echo "\n<!-- HTML5 video tracking -->\n<script>\n{$js}</script>\n";
    }

    private function cloudflare(string $token): void
    {
        $token = htmlspecialchars($token, ENT_QUOTES);
        $html = file_get_contents(__DIR__ . '/Analytics/cloudflare.html');

        echo "\n" . str_replace('{{token}}', $token, $html);
    }
}
