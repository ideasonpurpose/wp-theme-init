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
