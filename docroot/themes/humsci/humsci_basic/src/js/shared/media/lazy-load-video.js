(function (Drupal, once) {
  Drupal.behaviors.videoLazyBehavior = {
    attach(context) {
      const videos = once('video-lazy', '.video-lazy', context);

      videos.forEach((video) => {
        const provider = video.dataset.videoProvider;
        const { videoId } = video.dataset;
        const isPlaylist = video.dataset.isPlaylist === 'true';

        // --- Vimeo: Fetch thumbnail immediately ---
        if (provider === 'vimeo' && !video.querySelector('.video-thumb')) {
          fetch(`https://vimeo.com/api/v2/video/${videoId}.json`)
            .then((res) => res.json())
            .then((data) => {
              if (data[0] && data[0].thumbnail_large) {
                const img = document.createElement('img');
                img.src = data[0].thumbnail_large;
                img.className = 'video-thumb';
                img.alt = 'Video thumbnail';
                const playBtn = video.querySelector('.video-play');
                video.insertBefore(img, playBtn);
              }
            })
            .catch((err) => console.error('Vimeo thumbnail fetch failed', err));
        }

        // --- Click to load iframe ---
        const playButton = video.querySelector('.video-play');
        playButton.addEventListener('click', () => {
          let embedUrl = '';

          if (provider === 'youtube') {
            if (isPlaylist) {
              embedUrl = `https://www.youtube.com/embed/videoseries?list=${videoId}&autoplay=1`;
            } else {
              embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            }
          } else if (provider === 'vimeo') {
            embedUrl = `https://player.vimeo.com/video/${videoId}?autoplay=1`;
          }

          const iframe = document.createElement('iframe');
          iframe.src = embedUrl;
          iframe.setAttribute('frameborder', '0');
          iframe.setAttribute('allow', 'autoplay; fullscreen; encrypted-media');
          iframe.setAttribute('allowfullscreen', 'true');
          iframe.className = 'video-iframe';

          video.innerHTML = '';
          video.appendChild(iframe);
        });
      });
    },
  };
}(Drupal, once));
