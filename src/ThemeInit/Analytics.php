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

        echo <<<HTML

<!-- Google tag (gtag.js) -->
<script async src="{$src}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

{$commands}
</script>

HTML;
    }

    private function cloudflare(string $token): void
    {
        $token = htmlspecialchars($token, ENT_QUOTES);

        echo <<<HTML

<!-- Cloudflare Web Analytics -->
<script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "{$token}"}'></script>
<!-- End Cloudflare Web Analytics -->

HTML;
    }
}
