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

    private function videoTracking(): void
    {
        echo <<<'HTML'

<!-- HTML5 video tracking -->
<script>
(() => {
  const state = new WeakMap();
  const marks = [10, 25, 50, 75, 90];

  const send = (name, video, params = {}) => {
    const src = video.currentSrc;
    gtag('event', name, {
      video_provider: 'self-hosted',
      video_url: src,
      video_title: video.title || video.getAttribute('aria-label') || video.dataset.title || src.split(/[?#]/)[0].split('/').pop(),
      video_duration: Number.isFinite(video.duration) ? Math.round(video.duration) : undefined,
      video_current_time: Math.round(video.currentTime),
      video_autoplay: video.autoplay,
      ...params,
    });
  };

  const track = (e) => {
    const video = e.target;
    if (!(video instanceof HTMLVideoElement)) return;
    if (!state.has(video)) state.set(video, { sent: [], last: 0 });
    const s = state.get(video);
    if (s.done) return;

    if (e.type === 'play') {
      if (!s.started) send('video_start', video);
      s.started = true;
      return;
    }

    const duration = video.duration;
    if (!Number.isFinite(duration) || duration <= 0) return;

    // 'ended' never fires on loop, so the first wrap-around counts as complete
    const wrapped = video.loop && s.last >= 90 && video.currentTime < 1;
    if (e.type === 'ended' || wrapped) {
      s.done = true;
      send('video_complete', video, { video_current_time: Math.round(duration), video_percent: 100 });
      return;
    }

    const percent = (video.currentTime / duration) * 100;
    marks.filter((m) => percent >= m && !s.sent.includes(m)).forEach((m) => {
      s.sent.push(m);
      send('video_progress', video, { video_percent: m });
    });
    s.last = percent;
  };

  ['play', 'timeupdate', 'ended'].forEach((type) => document.addEventListener(type, track, true));
})();
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
